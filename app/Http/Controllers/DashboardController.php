<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStatistics()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Hitung total tiket yang dibuat pada bulan ini
        $totalTickets = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->count();
        
        // Hitung jumlah tiket yang masih aktif
        $activeTickets = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', '!=', 'resolved')->count();

        // Hitung jumlah tiket yang telah diselesaikan
        $resolvedTickets = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'resolved')->count();

        // Hitung rata-rata waktu penyelesaian tiket
        $avgResolutionTime = Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'resolved')->whereNotNull('completed_at')->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time'))->value('avg_time') ?? 0;

        // Hitung jumalah distribusi tiket
        $statusDistribution = [
            'open' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'open')->count(),

            'onprogress' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'onprogress')->count(),
            
            'resolved' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'resolved')->count(),

            'rejected' => Ticket::whereBetween('created_at', [$currentMonth, $endOfMonth])->where('status', 'rejected')->count(),
        ];

        $dashboardData = [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'resolved_tickets' => $resolvedTickets,
            'avg_resolution_time' => round($avgResolutionTime, 1),
            'status_distribution' => $statusDistribution
        ];

        return response()->json([
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => new DashboardResource($dashboardData)
        ]);

    }
}
