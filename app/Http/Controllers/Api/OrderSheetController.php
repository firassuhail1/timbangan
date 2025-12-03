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

    // private function getCurrentEspId()
    // {
    //     return Device::where('user_id', Auth::id())
    //                 ->where('status', 'in_use')
    //                 ->value('esp_id'); // langsung ambil string esp_id
    // }

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

        // Kelompokkan berdasarkan tanggal (d-m-Y) dan Buyer
        $groupedOrders = $orders->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('d-m-Y') . '|' . $item->Buyer;
        });

        // $device = Device::all();

        // dd($device);

        return view('order.index', compact('auth', 'groupedOrders'));
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
    // public function create(Request $request){
    //     return view('order.create');
    // }

    // public function store(Request $request)
    // {
    //     // Validasi minimal
    //     $request->validate([
    //         'Order_code' => 'required|string',
    //         'Buyer'      => 'required|string',
    //         'berat'      => 'nullable|numeric|min:0',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // 1. Simpan ke ordersheet
    //         $ordersheet = Ordersheet::create([
    //             'Order_code'         => $request->Order_code,
    //             'Buyer'              => $request->Buyer,
    //             'PO'                 => $request->PO,
    //             'Style'              => $request->Style,
    //             'Qty_order'          => $request->Qty_order,
    //             'Carton_weight_std'  => $request->Carton_weight_std,
    //             'Pcs_weight_std'     => $request->Pcs_weight_std,
    //             'PCS'                => $request->PCS,
    //             'Ctn'                => $request->Ctn,
    //             'Less_Ctn'           => $request->Less_Ctn,
    //             'Pcs_Less_Ctn'       => $request->Pcs_Less_Ctn,
    //             'Gac_date'           => $request->Gac_date,
    //             'Destination'        => $request->Destination,
    //             'Inspector'          => $request->Inspector,
    //             'OPT_QC_TIMBANGAN'   => $request->OPT_QC_TIMBANGAN ?? Auth::user()->username,
    //             'SPV_QC'             => $request->SPV_QC09,
    //             'CHIEF_FINISH_GOOD'  => $request->CHIEF_FINISH_GOOD,
    //         ]);

    //         // 2. Ambil berat: dari hidden field (modal) atau dummy (create)
    //         $berat = $request->filled('berat') 
    //             ? floatval($request->berat) 
    //             : 0.00;

    //         // 3. Simpan ke timbangan_riwayat
    //         Timbangan_riwayat::create([
    //             'id_ordersheet'          => $ordersheet->id,
    //             'berat'                  => $berat,
    //             'no_box'                 => $request->no_box,
    //             'rasio_batas_beban_min'  => $request->rasio_batas_beban_min ?? null,
    //             'rasio_batas_beban_max'  => $request->rasio_batas_beban_max ?? null,
    //             'status'                 => 'Success',
    //             'waktu_timbang'          => now(),
    //         ]);

    //         // 4. Simpan ke view table
    //         VAllOrdersheetPlusCari::create([
    //             'Order_code'          => $request->Order_code,
    //             'Buyer'               => $request->Buyer,
    //             'PurchaseOrderNumber' => $request->PO,
    //             'ProductName'         => $request->Style,
    //             'Qty'                 => $request->Qty_order,
    //             'DestinationCountry'  => $request->Destination,
    //             'GAC'                 => $request->Gac_date,
    //             'FinalDestination'    => $request->Destination,
    //             'status'              => 'Success',
    //             'cari'                => $request->Buyer . ' ' . $request->Order_code . ' ' . $request->PO,
    //         ]);

    //         DB::commit();

    //         $pesan = $berat > 0 
    //             ? "Data berhasil disimpan dengan berat: {$berat} kg!" 
    //             : "Data berhasil disimpan (tanpa berat timbangan)";

    //         return redirect()->back()->with('success', $pesan);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error simpan ordersheet: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

