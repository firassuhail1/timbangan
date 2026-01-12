<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Ordersheet;
use App\Models\Timbangan_riwayat;
use App\Models\Update\Device;
use App\Models\VAllOrdersheetPlusCari;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class OrderSheetController extends Controller
{
    public function index()
    {
        $auth = Auth::id();

        // Ambil data ordersheet beserta timbangannya
        $orders = Ordersheet::with([
                'timbangans:id,id_ordersheet,no_box,berat,waktu_timbang'
            ])
            ->where('status', 'Success')
            ->select('id', 'Order_code', 'Buyer', 'PO', 'Style', 'Qty_order', 'Destination', 'status', 'created_at')
            ->latest('created_at')
            ->get();

        $groupedOrders = $orders->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('d-m-Y') . '|' . $item->Buyer;
        });

        // $device = Device::all();

        // dd($device);

        return view('order.index', compact('auth', 'groupedOrders'));
    }

    public function reportData()
    {
        $orders = Ordersheet::with([
                'timbangans:id,id_ordersheet,no_box,berat,waktu_timbang'
            ])
            ->where('status', 'Success')
            ->select('id', 'Order_code', 'Buyer', 'PO', 'Style', 'Qty_order', 'Destination', 'status', 'created_at')
            ->latest('created_at')
            ->get();

        $grouped = $orders->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('d-m-Y') . '|' . $item->Buyer;
        });

        return response()->json([
            'success' => true,
            'data' => $grouped
        ]);
    }    
    public function getData(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $perPage = 10;

        $query = VAllOrdersheetPlusCari::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ID', 'LIKE', "%{$search}%")
                ->orWhere('Order_code', 'LIKE', "%{$search}%")
                ->orWhere('Buyer', 'LIKE', "%{$search}%")
                ->orWhere('PurchaseOrderNumber', 'LIKE', "%{$search}%")
                ->orWhere('ProductName', 'LIKE', "%{$search}%")
                ->orWhere('Qty', 'LIKE', "%{$search}%");
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('DocumentDate', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('DocumentDate', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('DocumentDate', '<=', $endDate);
        }

        $data = $query->orderBy('DocumentDate', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'total' => $data->total(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'data' => $data->items(),
        ]);
    }

    public function print($buyer = null)
    {
        $auth = Auth::user();

        $query = Ordersheet::with([
                'timbangans:id,id_ordersheet,no_box,berat'
            ])
            ->where('status', 'Success')
            ->select('id', 'Order_code', 'Buyer', 'PO', 'Style', 'Qty_order', 'Destination', 'Less_Ctn', 'Pcs_Less_Ctn', 'Carton_weight_std', 'Pcs_weight_std', 'Inspector', 'created_at');

        if ($buyer) {
            $query->where('Buyer', $buyer);
        }

        $orders = $query->latest('created_at')->get();

        // dd($orders);

        // Group by Buyer|Date seperti di index
        $groupedOrders = $orders->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('d-m-Y') . '|' . $item->Buyer;
        });

        return view('order.print', compact('auth', 'groupedOrders'));
    }
}

