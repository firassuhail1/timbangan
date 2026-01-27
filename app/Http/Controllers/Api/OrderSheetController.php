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
    // Data perhari ini
    // public function index()
    // {
    //     $auth = Auth::id();
    //     $today = Carbon::today();

    //     $orders = Ordersheet::with([
    //             'timbangans' => function ($q) use ($today) {
    //                 $q->whereDate('waktu_timbang', $today)
    //                 ->select('id', 'id_ordersheet', 'no_box', 'berat', 'waktu_timbang');
    //             }
    //         ])
    //         ->where('status', 'Success')
    //         ->whereHas('timbangans', function ($q) use ($today) {
    //             $q->whereDate('waktu_timbang', $today);
    //         })
    //         ->select(
    //             'id',
    //             'Order_code',
    //             'Buyer',
    //             'PO',
    //             'Style',
    //             'Qty_order',
    //             'Destination',
    //             'status',
    //             'created_at'
    //         )
    //         ->latest()
    //         ->get();

    //     $groupedOrders = $orders->groupBy(function ($item) {
    //         return Carbon::today()->format('d-m-Y') . '|' . $item->Buyer;
    //     });

    //     // dd($groupedOrders);

    //     return view('order.index', compact('auth', 'groupedOrders'));
    // }
    
    
    // Data keseluruhan
    public function index()
    {
        $auth = Auth::id();

        $orders = Ordersheet::with([
                'timbangans' => function ($q) {
                    $q->select('id', 'id_ordersheet', 'no_box', 'berat', 'waktu_timbang');
                }
            ])
            ->where('status', 'Success')
            ->whereHas('timbangans') // ambil semua yang punya timbangan
            ->select(
                'id',
                'Order_code',
                'Buyer',
                'PO',
                'Style',
                'Qty_order',
                'Destination',
                'status',
                'created_at'
            )
            ->latest()
            ->get();

        $groupedOrders = $orders->groupBy(function ($item) {

            $date = optional($item->timbangans->first())->waktu_timbang;

            $date = $date
                ? Carbon::parse($date)->format('d-m-Y')
                : 'Tanpa Tanggal';

            return $date . '|' . $item->Buyer;
        });

        return view('order.index', compact('auth', 'groupedOrders'));
    }

    public function reportData()
    {
        $today = Carbon::today();

        $orders = Ordersheet::with([
                'timbangans' => function ($q) use ($today) {
                    $q->whereDate('waktu_timbang', $today)
                    ->select('id', 'id_ordersheet', 'no_box', 'berat', 'waktu_timbang');
                }
            ])
            ->where('status', 'Success')
            ->whereHas('timbangans', function ($q) use ($today) {
                $q->whereDate('waktu_timbang', $today);
            })
            ->latest()
            ->get();

        $grouped = $orders->groupBy(function ($item) {
            return Carbon::today()->format('d-m-Y') . '|' . $item->Buyer;
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

    // API Kanindo
    // public function getData(Request $request)
    // {
    //     $search    = $request->query('search');
    //     $startDate = $request->query('start_date');
    //     $endDate   = $request->query('end_date');
    //     $perPage   = 10;
    //     $page      = $request->query('page', 1);

    //     $queryParams = array_filter([
    //         'search'     => $search,
    //         'start_date' => $startDate,
    //         'end_date'   => $endDate,
    //     ]);

    //     try {
    //         /** @var \Illuminate\Http\Client\Response $response */
            
    //        $response = Http::timeout(60)
    //             ->get('http://192.168.0.20/sewing/qa/ordersheet/get_ordersheet_data_json', $queryParams);

    //         // Simpan response body untuk debug
    //         $body = $response->body();

    //         if (!$response->successful()) {
    //             Log::error('Sewing API gagal', [
    //                 'status' => $response->status(),
    //                 'body'   => substr($body, 0, 1000), // batasi agar log tidak terlalu besar
    //                 'params' => $queryParams
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Server sewing mengembalikan error (status ' . $response->status() . ')'
    //             ], 502);
    //         }

    //         $json = $response->json();

    //         if (json_last_error() !== JSON_ERROR_NONE) {
    //             Log::error('Response bukan JSON valid', [
    //                 'body' => substr($body, 0, 1000),
    //                 'params' => $queryParams
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Response dari server sewing bukan JSON valid'
    //             ], 502);
    //         }

    //         // Ambil data dengan aman
    //         $rawData = data_get($json, 'data', $json);
    //         if (!is_array($rawData)) {
    //             Log::error('Struktur data tidak ditemukan', [
    //                 'body' => substr($body, 0, 500),
    //                 'params' => $queryParams
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Struktur data dari server sewing tidak sesuai (tidak ada array data)'
    //             ], 502);
    //         }

    //         $items = collect($rawData);

    //         $items = $items->map(function ($item) {

    //             return [
    //                 'id' => data_get($item, 'id')
    //                     ?? data_get($item, 'ID'),

    //                 'Buyer' => data_get($item, 'Buyer')
    //                     ?? data_get($item, 'buyer'),

    //                 'PurchaseOrderNumber' =>
    //                     data_get($item, 'PurchaseOrderNumber')
    //                     ?? data_get($item, 'po_number')
    //                     ?? data_get($item, 'PO'),

    //                 'ProductName' =>
    //                     data_get($item, 'ProductName')
    //                     ?? data_get($item, 'product_name'),

    //                 'Qty' =>
    //                     data_get($item, 'Qty')
    //                     ?? data_get($item, 'qty')
    //                     ?? 0,

    //                 'ActualFOB' =>
    //                     data_get($item, 'ActualFOB')
    //                     ?? data_get($item, 'actual_fob'),

    //                 'DocumentDate' =>
    //                     data_get($item, 'DocumentDate')
    //                     ?? data_get($item, 'document_date')
    //                     ?? data_get($item, 'date'),
    //             ];
    //         });

    //         if ($startDate && $endDate) {
    //             $start = strtotime($startDate . ' 00:00:00');
    //             $end   = strtotime($endDate . ' 23:59:59');

    //             $items = $items->filter(function ($item) use ($start, $end) {

    //                 $date = $item['DocumentDate'] ?? null;

    //                 if (!$date) return false;

    //                 $time = strtotime($date);

    //                 if ($time === false) return false;

    //                 return $time >= $start && $time <= $end;
    //             })->values();
    //         }

    //         $items = $items->sortByDesc(function ($item) {
    //             return strtotime($item['DocumentDate'] ?? '1970-01-01');    
    //         })->values();

    //         // Pagination
    //         $total    = $items->count();
    //         $results  = $items->slice(($page - 1) * $perPage, $perPage)->values();

    //         $paginator = new LengthAwarePaginator($results, $total, $perPage, $page, [
    //             'path' => $request->url()
    //         ]);

    //         return response()->json([
    //             'success'      => true,
    //             'total'        => $paginator->total(),
    //             'current_page' => $paginator->currentPage(),
    //             'last_page'    => $paginator->lastPage(),
    //             'data'         => $paginator->items(),
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Error di OrderSheetController', [
    //             'message' => $e->getMessage(),
    //             'file'    => $e->getFile() . ':' . $e->getLine(),
    //             'trace'   => $e->getTraceAsString(),
    //             'params'  => $queryParams
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan server (lihat log untuk detail)'
    //         ], 500);
    //     }
    // }

    
    // Data keseluruhan
    public function print($buyer = null)
    {
        $auth = Auth::user();

        $orders = Ordersheet::with([
                'timbangans' => function ($q) {
                    $q->select('id', 'id_ordersheet', 'no_box', 'berat', 'waktu_timbang');
                }
            ])
            ->where('status', 'Success')
            ->whereHas('timbangans')
            ->latest()
            ->get();

        if ($buyer) {
            $orders = $orders->where('Buyer', $buyer);
        }

        $groupedOrders = $orders->groupBy(function ($item) {

            $date = optional($item->timbangans->first())->waktu_timbang;

            $date = $date
                ? Carbon::parse($date)->format('d-m-Y')
                : 'Tanpa Tanggal';

            return $date . '|' . $item->Buyer;
        });

        return view('order.print', compact('auth', 'groupedOrders'));
    }

    // Data perhari ini
    // public function print($buyer = null)
    // {
    //     $auth = Auth::user();

    //     $today = Carbon::today();

    //     $orders = Ordersheet::with([
    //             'timbangans' => function ($q) use ($today) {
    //                 $q->whereDate('waktu_timbang', $today)
    //                 ->select('id', 'id_ordersheet', 'no_box', 'berat', 'waktu_timbang');
    //             }
    //         ])
    //         ->where('status', 'Success')
    //         ->whereHas('timbangans', function ($q) use ($today) {
    //             $q->whereDate('waktu_timbang', $today);
    //         })
    //         ->latest()
    //         ->get();

    //     if ($buyer) {
    //         $orders->where('Buyer', $buyer);
    //     }

    //     $groupedOrders = $orders->groupBy(function ($item) {
    //         return Carbon::today()->format('d-m-Y') . '|' . $item->Buyer;
    //     });

    //     return view('order.print', compact('auth', 'groupedOrders'));
    // }
}

