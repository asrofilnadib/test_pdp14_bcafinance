<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanRequest;
use App\Http\Requests\UpdatePengajuanRequest;
use App\Models\Dealer;
use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PengajuanController extends Controller
{
    public function index()
    {
        return view('pengajuan.index');
    }

    public function create()
    {
        if (! Auth::user()->canCreatePengajuan()) {
            abort(403);
        }

        return view('pengajuan.form', ['mode' => 'create', 'pengajuanId' => null]);
    }

    public function edit(int $id)
    {
        $pengajuan = $this->findAccessible($id);

        if (! $pengajuan->canEdit() || ! $this->ownsDraft($pengajuan, Auth::user())) {
            abort(403, 'Pengajuan ini tidak dapat diubah.');
        }

        return view('pengajuan.form', ['mode' => 'edit', 'pengajuanId' => $pengajuan->id]);
    }

    public function show(int $id)
    {
        $this->findAccessible($id);

        return view('pengajuan.show', ['pengajuanId' => $id]);
    }

    public function json(int $id)
    {
        $pengajuan = $this->findAccessible($id);
        $pengajuan->load(['dealer', 'marketing', 'approver', 'disburser', 'dokumens']);

        return response()->json($this->transform($pengajuan));
    }

    public function dealers()
    {
        $dealers = Dealer::query()->orderBy('nama')->get(['id', 'nama', 'alamat']);

        return response()->json($dealers);
    }

    public function datatable(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $user = Auth::user();
            $base = $this->scopedQuery($user);
            $recordsTotal = (clone $base)->count();

            $query = clone $base;
            $status = $request->input('status');
            if (is_string($status) && $status !== '' && in_array($status, Pengajuan::STATUSES, true)) {
                $query->where('status', $status);
            }

            $search = trim((string) $request->input('search.value', ''));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('nomor', 'like', '%'.$search.'%')
                        ->orWhere('konsumen_nama', 'like', '%'.$search.'%')
                        ->orWhere('konsumen_nik', 'like', '%'.$search.'%')
                        ->orWhere('merk_kendaraan', 'like', '%'.$search.'%');
                });
            }

            $recordsFiltered = (clone $query)->count();

            $columns = ['nomor', 'konsumen_nama', 'konsumen_nik', 'merk_kendaraan', 'dealer_id', 'status', 'created_at', 'id'];
            $orderCol = (int) $request->input('order.0.column', 6);
            $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
            $orderField = $columns[$orderCol] ?? 'created_at';

            $start = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 10);
            if ($length < 1 || $length > 100) {
                $length = 10;
            }

            $rows = $query->with('dealer')
                ->orderBy($orderField, $orderDir)
                ->skip($start)
                ->take($length)
                ->get();

            $data = $rows->map(function (Pengajuan $row) use ($user) {
                return [
                    'id' => $row->id,
                    'nomor' => e($row->nomor),
                    'konsumen' => e($row->konsumen_nama),
                    'nik' => e($row->konsumen_nik),
                    'kendaraan' => e(trim($row->merk_kendaraan.' '.$row->model_kendaraan)),
                    'dealer' => e($row->dealer?->nama ?? '-'),
                    'status' => $row->status,
                    'status_label' => Pengajuan::statusLabel($row->status),
                    'tanggal' => $row->created_at?->format('d/m/Y H:i'),
                    'can_edit' => $row->canEdit() && $this->ownsDraft($row, $user),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Datatable pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal memuat data pengajuan.'], 500);
        }
    }

    public function store(StorePengajuanRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $data = $this->payload($request->validated(), $user);
            $data['nomor'] = $this->nextNomor();
            $data['status'] = Pengajuan::STATUS_DRAFT;
            $data['marketing_id'] = $user->id;

            $pengajuan = Pengajuan::query()->create($data);

            DB::commit();

            return response()->json([
                'message' => 'Pengajuan tersimpan sebagai draft.',
                'id' => $pengajuan->id,
                'nomor' => $pengajuan->nomor,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Simpan pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal menyimpan pengajuan.'], 500);
        }
    }

    public function update(UpdatePengajuanRequest $request, int $id)
    {
        $pengajuan = $this->findAccessible($id);

        if (! $pengajuan->canEdit() || ! $this->ownsDraft($pengajuan, Auth::user())) {
            return response()->json(['message' => 'Pengajuan ini tidak dapat diubah.'], 403);
        }

        try {
            DB::beginTransaction();

            $data = $this->payload($request->validated(), Auth::user(), $pengajuan);
            if ($pengajuan->status === Pengajuan::STATUS_REJECTED) {
                $data['status'] = Pengajuan::STATUS_DRAFT;
                $data['approved_by'] = null;
                $data['approved_at'] = null;
                $data['catatan_approval'] = $pengajuan->catatan_approval;
            }

            $pengajuan->update($data);

            DB::commit();

            return response()->json(['message' => 'Pengajuan berhasil diperbarui.', 'id' => $pengajuan->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal memperbarui pengajuan.'], 500);
        }
    }

    public function submit(int $id)
    {
        $pengajuan = $this->findAccessible($id);

        if (! $pengajuan->canEdit() || ! $this->ownsDraft($pengajuan, Auth::user())) {
            return response()->json(['message' => 'Pengajuan ini tidak dapat diajukan.'], 403);
        }

        $validator = Validator::make($pengajuan->toArray(), [
            'konsumen_nama' => ['required'],
            'konsumen_nik' => ['required', 'digits:16'],
            'konsumen_tgl_lahir' => ['required'],
            'status_perkawinan' => ['required'],
            'merk_kendaraan' => ['required'],
            'model_kendaraan' => ['required'],
            'tipe_kendaraan' => ['required'],
            'warna_kendaraan' => ['required'],
            'harga_kendaraan' => ['required', 'numeric', 'min:1'],
            'asuransi' => ['required'],
            'down_payment' => ['required', 'numeric', 'min:0'],
            'lama_kredit' => ['required', 'integer', 'min:1'],
            'angsuran' => ['required', 'numeric', 'min:1'],
            'dealer_id' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Lengkapi data pengajuan sebelum diajukan.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($pengajuan->status_perkawinan === 'menikah' && blank($pengajuan->data_pasangan)) {
            return response()->json(['message' => 'Data pasangan wajib diisi jika status menikah.'], 422);
        }

        $pengajuan->load('dokumens');
        foreach (DokumenPengajuan::TIPE_AWAL as $tipe) {
            if (! $pengajuan->hasDokumen($tipe)) {
                return response()->json([
                    'message' => 'Dokumen '.DokumenPengajuan::label($tipe).' belum diunggah.',
                ], 422);
            }
        }

        try {
            $pengajuan->update(['status' => Pengajuan::STATUS_SUBMITTED]);

            return response()->json(['message' => 'Pengajuan berhasil dikirim ke atasan marketing.']);
        } catch (\Throwable $e) {
            Log::error('Submit pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal mengajukan data.'], 500);
        }
    }

    public function approve(Request $request, int $id)
    {
        if (! Auth::user()->isAtasan()) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $pengajuan = $this->findAccessible($id);
        if ($pengajuan->status !== Pengajuan::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Hanya pengajuan yang menunggu approval yang dapat disetujui.'], 422);
        }

        try {
            $pengajuan->update([
                'status' => Pengajuan::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'catatan_approval' => $request->input('catatan'),
            ]);

            return response()->json(['message' => 'Pengajuan disetujui.']);
        } catch (\Throwable $e) {
            Log::error('Approve pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal menyetujui pengajuan.'], 500);
        }
    }

    public function reject(Request $request, int $id)
    {
        if (! Auth::user()->isAtasan()) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'catatan' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Catatan penolakan wajib diisi.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengajuan = $this->findAccessible($id);
        if ($pengajuan->status !== Pengajuan::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Hanya pengajuan yang menunggu approval yang dapat ditolak.'], 422);
        }

        try {
            $pengajuan->update([
                'status' => Pengajuan::STATUS_REJECTED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'catatan_approval' => $request->input('catatan'),
            ]);

            return response()->json(['message' => 'Pengajuan ditolak.']);
        } catch (\Throwable $e) {
            Log::error('Reject pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal menolak pengajuan.'], 500);
        }
    }

    public function markPrinted(int $id)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $pengajuan = $this->findAccessible($id);
        if (! in_array($pengajuan->status, [Pengajuan::STATUS_APPROVED, Pengajuan::STATUS_PRINTED], true)) {
            return response()->json(['message' => 'Dokumen hanya dapat dicetak setelah pengajuan disetujui.'], 422);
        }

        try {
            if ($pengajuan->status === Pengajuan::STATUS_APPROVED) {
                $pengajuan->update(['status' => Pengajuan::STATUS_PRINTED]);
            }

            return response()->json(['message' => 'Status dokumen dicatat sebagai dicetak.']);
        } catch (\Throwable $e) {
            Log::error('Mark printed gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal memperbarui status cetak.'], 500);
        }
    }

    public function cetakKontrak(int $id)
    {
        $pengajuan = $this->findAccessible($id);
        $pengajuan->load(['dealer', 'marketing']);

        return view('pengajuan.cetak-kontrak', ['pengajuan' => $pengajuan]);
    }

    public function cetakPo(int $id)
    {
        $pengajuan = $this->findAccessible($id);
        $pengajuan->load(['dealer', 'marketing']);

        return view('pengajuan.cetak-po', ['pengajuan' => $pengajuan]);
    }

    public function disburse(int $id)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $pengajuan = $this->findAccessible($id);
        $pengajuan->load('dokumens');

        if ($pengajuan->status !== Pengajuan::STATUS_SIGNED) {
            return response()->json(['message' => 'Pencairan hanya dapat dilakukan setelah dokumen TTD lengkap.'], 422);
        }

        try {
            $pengajuan->update([
                'status' => Pengajuan::STATUS_DISBURSED,
                'disbursed_by' => Auth::id(),
                'disbursed_at' => now(),
            ]);

            return response()->json(['message' => 'Pencairan dana berhasil dicatat.']);
        } catch (\Throwable $e) {
            Log::error('Pencairan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal mencatat pencairan dana.'], 500);
        }
    }

    public function destroy(int $id)
    {
        $pengajuan = $this->findAccessible($id);

        if ($pengajuan->status !== Pengajuan::STATUS_DRAFT || ! $this->ownsDraft($pengajuan, Auth::user())) {
            return response()->json(['message' => 'Hanya draft milik Anda yang dapat dihapus.'], 403);
        }

        try {
            $pengajuan->delete();

            return response()->json(['message' => 'Pengajuan dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Hapus pengajuan gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal menghapus pengajuan.'], 500);
        }
    }

    private function payload(array $validated, User $user, ?Pengajuan $existing = null): array
    {
        $data = $validated;
        $data['dealer_id'] = $user->isDealer() ? $user->dealer_id : ($validated['dealer_id'] ?? $existing?->dealer_id);

        $harga = (float) ($data['harga_kendaraan'] ?? 0);
        $dp = (float) ($data['down_payment'] ?? 0);
        $tenor = (int) ($data['lama_kredit'] ?? 0);
        if ($harga > 0 && $tenor > 0) {
            $data['angsuran'] = round(max($harga - $dp, 0) / $tenor, 2);
        }

        return $data;
    }

    private function nextNomor(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'JKL-'.$date.'-';
        $last = Pengajuan::withTrashed()
            ->where('nomor', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('nomor')
            ->value('nomor');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
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

    private function findAccessible(int $id): Pengajuan
    {
        $pengajuan = $this->scopedQuery(Auth::user())->find($id);
        if (! $pengajuan) {
            abort(404, 'Pengajuan tidak ditemukan.');
        }

        return $pengajuan;
    }

    private function ownsDraft(Pengajuan $pengajuan, User $user): bool
    {
        if ($user->isDealer()) {
            return (int) $pengajuan->dealer_id === (int) $user->dealer_id;
        }

        if ($user->isMarketing()) {
            return (int) $pengajuan->marketing_id === (int) $user->id;
        }

        return false;
    }

    private function transform(Pengajuan $pengajuan): array
    {
        return [
            'id' => $pengajuan->id,
            'nomor' => $pengajuan->nomor,
            'status' => $pengajuan->status,
            'status_label' => Pengajuan::statusLabel($pengajuan->status),
            'dealer_id' => $pengajuan->dealer_id,
            'dealer' => $pengajuan->dealer?->only(['id', 'nama', 'alamat', 'telepon']),
            'marketing' => $pengajuan->marketing?->only(['id', 'name', 'email']),
            'konsumen_nama' => $pengajuan->konsumen_nama,
            'konsumen_nik' => $pengajuan->konsumen_nik,
            'konsumen_tgl_lahir' => $pengajuan->konsumen_tgl_lahir?->format('Y-m-d'),
            'status_perkawinan' => $pengajuan->status_perkawinan,
            'data_pasangan' => $pengajuan->data_pasangan,
            'merk_kendaraan' => $pengajuan->merk_kendaraan,
            'model_kendaraan' => $pengajuan->model_kendaraan,
            'tipe_kendaraan' => $pengajuan->tipe_kendaraan,
            'warna_kendaraan' => $pengajuan->warna_kendaraan,
            'harga_kendaraan' => $pengajuan->harga_kendaraan,
            'asuransi' => $pengajuan->asuransi,
            'down_payment' => $pengajuan->down_payment,
            'lama_kredit' => $pengajuan->lama_kredit,
            'angsuran' => $pengajuan->angsuran,
            'catatan_approval' => $pengajuan->catatan_approval,
            'approved_by' => $pengajuan->approver?->name,
            'approved_at' => $pengajuan->approved_at?->format('d/m/Y H:i'),
            'disbursed_by' => $pengajuan->disburser?->name,
            'disbursed_at' => $pengajuan->disbursed_at?->format('d/m/Y H:i'),
            'created_at' => $pengajuan->created_at?->format('d/m/Y H:i'),
            'can_edit' => $pengajuan->canEdit() && $this->ownsDraft($pengajuan, Auth::user()),
            'dokumens' => $pengajuan->dokumens->map(fn (DokumenPengajuan $doc) => [
                'id' => $doc->id,
                'tipe' => $doc->tipe,
                'tipe_label' => DokumenPengajuan::label($doc->tipe),
                'nama_asli' => $doc->nama_asli,
                'mime' => $doc->mime,
                'is_image' => $doc->isImage(),
                'url' => '/dokumen/'.$doc->id.'/file',
            ]),
        ];
    }
}
