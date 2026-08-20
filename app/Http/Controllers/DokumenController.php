<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDokumenRequest;
use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function store(UploadDokumenRequest $request, string $public_id)
    {
        $pengajuan = $this->findAccessible($public_id);
        $user = Auth::user();
        $tipe = $request->input('tipe');

        if (in_array($tipe, DokumenPengajuan::TIPE_AWAL, true)) {
            if (! $pengajuan->canEdit() || ! $this->ownsDraft($pengajuan, $user)) {
                return response()->json(['message' => 'Dokumen awal hanya dapat diunggah pada draft milik Anda.'], 403);
            }
        }

        if (in_array($tipe, DokumenPengajuan::TIPE_TTD, true)) {
            if ($pengajuan->status !== Pengajuan::STATUS_PRINTED || ! $user->canCreatePengajuan()) {
                return response()->json(['message' => 'Dokumen TTD hanya dapat diunggah setelah kontrak/PO dicetak.'], 403);
            }
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $path = $file->store('dokumen/pengajuan/'.$pengajuan->id, 'local');

            $existing = DokumenPengajuan::query()
                ->where('pengajuan_id', $pengajuan->id)
                ->where('tipe', $tipe)
                ->first();

            if ($existing) {
                Storage::disk('local')->delete($existing->path);
                $existing->delete();
            }

            $dokumen = DokumenPengajuan::query()->create([
                'pengajuan_id' => $pengajuan->id,
                'tipe' => $tipe,
                'path' => $path,
                'nama_asli' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'uploaded_by' => $user->id,
            ]);

            if (in_array($tipe, DokumenPengajuan::TIPE_TTD, true)) {
                $pengajuan->load('dokumens');
                $hasAllTtd = collect(DokumenPengajuan::TIPE_TTD)->every(
                    fn (string $need) => $pengajuan->dokumens->contains('tipe', $need) || $need === $tipe
                );
                if ($hasAllTtd) {
                    $pengajuan->update(['status' => Pengajuan::STATUS_SIGNED]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Dokumen berhasil diunggah.',
                'dokumen' => [
                    'id' => $dokumen->id,
                    'tipe' => $dokumen->tipe,
                    'tipe_label' => DokumenPengajuan::label($dokumen->tipe),
                    'nama_asli' => $dokumen->nama_asli,
                    'mime' => $dokumen->mime,
                    'is_image' => $dokumen->isImage(),
                    'url' => '/dokumen/'.$dokumen->id.'/file',
                ],
                'status' => $pengajuan->fresh()->status,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Upload dokumen gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal mengunggah dokumen.'], 500);
        }
    }

    public function file(int $id)
    {
        $dokumen = DokumenPengajuan::query()->with('pengajuan')->findOrFail($id);
        $this->assertAccessible($dokumen->pengajuan);

        $disk = Storage::disk('local');
        if (! $disk->exists($dokumen->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->file($disk->path($dokumen->path), [
            'Content-Type' => $dokumen->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$dokumen->nama_asli.'"',
        ]);
    }

    public function destroy(int $id)
    {
        $dokumen = DokumenPengajuan::query()->with('pengajuan')->findOrFail($id);
        $pengajuan = $dokumen->pengajuan;
        $this->assertAccessible($pengajuan);
        $user = Auth::user();

        if (in_array($dokumen->tipe, DokumenPengajuan::TIPE_AWAL, true)) {
            if (! $pengajuan->canEdit() || ! $this->ownsDraft($pengajuan, $user)) {
                return response()->json(['message' => 'Dokumen ini tidak dapat dihapus.'], 403);
            }
        } elseif (in_array($dokumen->tipe, DokumenPengajuan::TIPE_TTD, true)) {
            if ($pengajuan->status !== Pengajuan::STATUS_PRINTED || ! $user->canCreatePengajuan()) {
                return response()->json(['message' => 'Dokumen TTD tidak dapat dihapus pada status ini.'], 403);
            }
        } else {
            return response()->json(['message' => 'Dokumen ini tidak dapat dihapus.'], 403);
        }

        try {
            Storage::disk('local')->delete($dokumen->path);
            $dokumen->delete();

            return response()->json(['message' => 'Dokumen dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Hapus dokumen gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal menghapus dokumen.'], 500);
        }
    }

    private function scopedQuery(User $user)
    {
        $query = Pengajuan::query();

        if ($user->isDealer()) {
            $query->where('dealer_id', $user->dealer_id);
        } elseif ($user->isMarketing()) {
            $query->where('marketing_id', $user->id);
        }

        return $query;
    }

    private function findAccessible(string $publicId): Pengajuan
    {
        $pengajuan = $this->scopedQuery(Auth::user())
            ->where('public_id', $publicId)
            ->first();
        if (! $pengajuan) {
            abort(404, 'Pengajuan tidak ditemukan.');
        }

        return $pengajuan;
    }

    private function assertAccessible(Pengajuan $pengajuan): void
    {
        $found = $this->scopedQuery(Auth::user())->find($pengajuan->id);
        if (! $found) {
            abort(404, 'Pengajuan tidak ditemukan.');
        }
    }

    private function ownsDraft(Pengajuan $pengajuan, User $user): bool
    {
        if ($user->isSuperUser()) {
            return true;
        }

        if ($user->isDealer()) {
            return (int) $pengajuan->dealer_id === (int) $user->dealer_id;
        }

        if ($user->isMarketing()) {
            return (int) $pengajuan->marketing_id === (int) $user->id;
        }

        return false;
    }
}
