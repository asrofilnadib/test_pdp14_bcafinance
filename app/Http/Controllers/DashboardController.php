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
}
