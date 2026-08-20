<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function stats(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $query = $this->scopedQuery(Auth::user());

            $counts = [];
            foreach (Pengajuan::STATUSES as $status) {
                $counts[$status] = (clone $query)->where('status', $status)->count();
            }

            return response()->json([
                'total' => (clone $query)->count(),
                'counts' => $counts,
                'monthly' => $this->monthlySeries($query),
                'dealers' => $this->dealerSeries($query),
            ]);
        } catch (\Throwable $e) {
            Log::error('Dashboard stats gagal: '.$e->getMessage());

            return response()->json(['message' => 'Gagal memuat statistik.'], 500);
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

    private function monthlySeries($query): array
    {
        $start = now()->subMonths(5)->startOfMonth();
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[$month->format('Y-m')] = $month->translatedFormat('M Y');
        }

        $rows = (clone $query)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bulan, COUNT(*) as total')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->pluck('total', 'bulan');

        $series = [];
        foreach ($labels as $key => $label) {
            $series[] = [
                'bulan' => $key,
                'label' => $label,
                'total' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    private function dealerSeries($query): array
    {
        return (clone $query)
            ->join('dealers', 'dealers.id', '=', 'pengajuans.dealer_id')
            ->selectRaw('dealers.nama as nama, COUNT(pengajuans.id) as total')
            ->groupBy('dealers.id', 'dealers.nama')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'nama' => $row->nama,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }
}
