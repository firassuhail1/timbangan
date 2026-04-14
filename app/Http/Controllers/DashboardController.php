<?php

namespace App\Http\Controllers;

use App\Models\Ordersheet;
use App\Models\Timbangan_riwayat;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    public function index()
    {
        $auth = Auth::user();

        // Total milik user
        $totalOrdersheet = Ordersheet::where('id_user', $auth->id)->count();

        $totalTimbangan = Timbangan_riwayat::where('id_user', $auth->id)->count();

        $totalSuccess = Timbangan_riwayat::where('id_user', $auth->id)
            ->where('status', 'Success')
            ->count();

        $totalRejected = Timbangan_riwayat::where('id_user', $auth->id)
            ->where('status', 'Rejected')
            ->count();

        // Grafik jumlah timbangan per ordersheet (milik user)
        $chartDataRaw = Timbangan_riwayat::where('id_user', $auth->id)
            ->selectRaw('id_ordersheet, COUNT(*) as total')
            ->groupBy('id_ordersheet')
            ->with('ordersheet:id,Order_code')
            ->get();

        $chartLabels = $chartDataRaw->pluck('ordersheet.Order_code');
        $chartData   = $chartDataRaw->pluck('total');

        // Ordersheet terbaru milik user
        $latestOrders = Ordersheet::where('id_user', $auth->id)
            ->latest()
            ->take(5)
            ->get();

        return view('index', compact(
            'totalOrdersheet',
            'totalTimbangan',
            'totalSuccess',
            'totalRejected',
            'latestOrders',
            'chartLabels',
            'chartData',
            'auth'
        ));
    }

    public function admin()
    {
        $auth = Auth::user();
        $totalOrdersheet = Ordersheet::count();
        $totalTimbangan = Timbangan_riwayat::count();
        $totalSuccess = Timbangan_riwayat::where('status', 'Success')->count();
        $totalRejected = Timbangan_riwayat::where('status', 'Rejected')->count();

        // Grafik jumlah timbangan per ordersheet
        $chartDataRaw = Timbangan_riwayat::selectRaw('id_ordersheet, COUNT(*) as total')
            ->groupBy('id_ordersheet')
            ->with('ordersheet:id,Order_code')
            ->get();

        $chartLabels = $chartDataRaw->pluck('ordersheet.Order_code');
        $chartData = $chartDataRaw->pluck('total');

        // Ambil ordersheet terbaru
        $latestOrders = Ordersheet::latest()->take(5)->get();

        return view('index', compact(
            'totalOrdersheet',
            'totalTimbangan',
            'totalSuccess',
            'totalRejected',
            'latestOrders',
            'chartLabels',
            'chartData',
            'auth'
        ));
    }
}
