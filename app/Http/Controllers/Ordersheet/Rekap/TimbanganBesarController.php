<?php

namespace App\Http\Controllers\Ordersheet\Rekap;

use App\Http\Controllers\Controller;
use App\Models\Ordersheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimbanganBesarController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;

        $auth = Auth::id();

        $query = Ordersheet::with('timbangans')
            ->where('status', 'Success')
            ->where('id_user', $auth)
            ->orderBy('id', 'desc');

        // Pagination default (tanpa filter search)
        $rekapbesar = $query->paginate($perPage);

        // dd($rekapbesar);

        return view('rekap.besar.index', compact('rekapbesar'));
    }

    public function getRekapData(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $perPage = 10;

        $query = Ordersheet::with([
                'timbangans:id,id_ordersheet,no_box,berat,waktu_timbang'
            ])
            ->where('status', 'Success')
            ->orderBy('id', 'desc');

        // Filter keyword
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('Order_code', 'like', "%{$search}%")
                ->orWhere('Buyer', 'like', "%{$search}%")
                ->orWhere('PO', 'like', "%{$search}%")
                ->orWhere('Style', 'like', "%{$search}%")
                ->orWhere('Destination', 'like', "%{$search}%");
            });
        }

        // Filter tanggal
        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        } elseif ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $rekapbesar = $query->paginate($perPage)->withQueryString();

        // Struktur JSON sama seperti Blade
       $rekapbesar->getCollection()->transform(function($item) {
            return [
                'id' => $item->id,
                'Order_code' => $item->Order_code,
                'Buyer' => $item->Buyer,
                'PO' => $item->PO,
                'Qty_order' => $item->Qty_order,
                'OPT_QC_TIMBANGAN' => $item->OPT_QC_TIMBANGAN,
                'timbangans' => $item->timbangans->map(fn($t) => [
                    'no_box' => $t->no_box,
                    'berat' => $t->berat,
                    'waktu_timbang' => $t->waktu_timbang,
                ]),
                'status' => $item->status,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($rekapbesar);
    }

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

    //         /**
    //          * Normalisasi field dari API
    //          */
    //         $items = $items->map(function ($item) {

    //             return [
    //                 'id' => $item['id'] ?? $item['ID'] ?? null,

    //                 'Buyer' => $item['Buyer'] ?? $item['buyer'] ?? null,

    //                 'PurchaseOrderNumber' =>
    //                     $item['PurchaseOrderNumber']
    //                     ?? $item['po_number']
    //                     ?? $item['PO']
    //                     ?? null,

    //                 'ProductName' =>
    //                     $item['ProductName']
    //                     ?? $item['product_name']
    //                     ?? null,

    //                 'Qty' =>
    //                     $item['Qty']
    //                     ?? $item['qty']
    //                     ?? 0,

    //                 'ActualFOB' =>
    //                     $item['ActualFOB']
    //                     ?? $item['actual_fob']
    //                     ?? null,

    //                 'DocumentDate' =>
    //                     $item['DocumentDate']
    //                     ?? $item['document_date']
    //                     ?? $item['date']
    //                     ?? null,
    //             ];
    //         });

    //         /**
    //          * Sort berdasarkan tanggal (terbaru dulu)
    //          */
    //         $items = $items
    //             ->sortByDesc('DocumentDate')
    //             ->values();

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
    
}
