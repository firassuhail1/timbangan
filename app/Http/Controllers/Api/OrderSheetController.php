<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ordersheet;
use App\Models\Timbangan_riwayat;
use App\Models\Update\Device;
use App\Models\VAllOrdersheetPlusCari;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    // public function index()
    // {
    //     $auth = Auth::id();

    //     $orders = Ordersheet::with([
    //         'timbangans' => function ($q) {
    //             $q->select('id', 'id_ordersheet', 'no_box', 'pcs', 'berat', 'rasio_batas_beban_min', 'rasio_batas_beban_max', 'waktu_timbang')
    //                 ->orderBy('waktu_timbang');
    //         }
    //     ])
    //         ->where('status', 'Success')
    //         ->whereHas('timbangans')
    //         ->select(
    //             'id',
    //             'Order_code',
    //             'KJ',
    //             'Buyer',
    //             'PO',
    //             'Style',
    //             'Qty_order',
    //             'Line',
    //             'PCS',      // ← pcs per carton
    //             'Ctn',      // ← nomor carton terakhir (input user)
    //             'Destination',
    //             'status',
    //             'created_at'
    //         )
    //         ->latest()
    //         ->get();

    //     // Group by Order_code (KJ)
    //     $groupedOrders = $orders->groupBy(function ($item) {
    //         $date = optional($item->timbangans->first())->waktu_timbang;
    //         $date = $date
    //             ? \Carbon\Carbon::parse($date)->format('d-m-Y')
    //             : 'Tanpa Tanggal';

    //         return $date . '|' . $item->Order_code . '|' . $item->KJ . '|' . $item->Line;
    //     });

    //     return view('order.index', compact('auth', 'groupedOrders'));
    // }

    public function index()
    {
        $auth = Auth::id();

        $orders = Ordersheet::with([
            'timbangans' => function ($q) {
                $q->select(
                    'id',
                    'id_ordersheet',
                    'no_box',
                    'berat',
                    'pcs',
                    'waktu_timbang',
                    'rasio_batas_beban_min',
                    'rasio_batas_beban_max'
                )
                    ->orderBy('waktu_timbang');
            }
        ])
            ->where('status', 'Success')
            ->whereHas('timbangans')
            ->select(
                'id',
                'Order_code',
                'KJ',
                'Buyer',
                'PO',
                'Style',
                'line',
                'Qty_order',
                'PCS',
                'Ctn',
                'Less_Ctn',
                'Pcs_Less_Ctn',
                'Destination',
                'Gac_date',
                'Inspector',
                'OPT_QC_TIMBANGAN',
                'SPV_QC',
                'CHIEF_FINISH_GOOD',
                'status',
                'created_at'
            )
            ->latest()
            ->get();

        // Group utama by KJ (untuk progress keseluruhan)
        // Setiap KJ punya sub-group per line
        $groupedByKJ = $orders->groupBy(function ($item) {
            return $item->KJ ?? $item->Order_code;
        })->map(function ($kjOrders) {
            // Sub-group by line dalam 1 KJ
            $byLine = $kjOrders->groupBy('line')->sortKeys();

            // Semua timbangan dari semua line dalam KJ ini
            $allTimbangans = $kjOrders->flatMap(fn($o) => $o->timbangans);

            // Info dari record pertama
            $first      = $kjOrders->first();
            $qtyTotal   = intval($first->Qty_order) ?: 0;
            $qtySudah   = $allTimbangans->sum('pcs');
            $totalBerat = $allTimbangans->sum('berat');
            $qtySisa    = max(0, $qtyTotal - $qtySudah);

            // Tanggal timbang pertama
            $firstDate = optional($allTimbangans->sortBy('waktu_timbang')->first())->waktu_timbang;
            $date      = $firstDate
                ? \Carbon\Carbon::parse($firstDate)->format('d-m-Y')
                : 'Tanpa Tanggal';

            return [
                'kj'           => $first->KJ ?? $first->Order_code,
                'order_code'   => $first->Order_code,
                'buyer'        => $first->Buyer ?? '-',
                'style'        => $first->Style ?? '-',
                'date'         => $date,
                'qty_total'    => $qtyTotal,
                'qty_sudah'    => $qtySudah,
                'qty_sisa'     => $qtySisa,
                'total_berat'  => $totalBerat,
                'total_carton' => $allTimbangans->count(),
                'by_line'      => $byLine,      // collection per line
                'all_timbangans' => $allTimbangans,
            ];
        });

        return view('order.index', compact('auth', 'groupedByKJ'));
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

    // public function getData(Request $request)
    // {
    //     $search = $request->query('search');
    //     $startDate = $request->query('start_date');
    //     $endDate = $request->query('end_date');
    //     $perPage = 10;

    //     $query = VAllOrdersheetPlusCari::query();

    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('ID', 'LIKE', "%{$search}%")
    //                 ->orWhere('KJ', 'LIKE', "%{$search}%")
    //                 ->orWhere('ProductCode', 'LIKE', "%{$search}%")
    //                 ->orWhere('ColorDescription', 'LIKE', "%{$search}%")
    //                 ->orWhere('Buyer', 'LIKE', "%{$search}%")
    //                 ->orWhere('PurchaseOrderNumber', 'LIKE', "%{$search}%")
    //                 ->orWhere('ProductName', 'LIKE', "%{$search}%")
    //                 ->orWhere('Qty', 'LIKE', "%{$search}%");
    //         });
    //     }

    //     if ($startDate && $endDate) {
    //         $query->whereBetween('DocumentDate', [$startDate, $endDate]);
    //     } elseif ($startDate) {
    //         $query->where('DocumentDate', '>=', $startDate);
    //     } elseif ($endDate) {
    //         $query->where('DocumentDate', '<=', $endDate);
    //     }

    //     $data = $query->orderBy('DocumentDate', 'desc')->paginate($perPage);

    //     Log::info('data : ' . $data);
    //     return response()->json([
    //         'success' => true,
    //         'total' => $data->total(),
    //         'current_page' => $data->currentPage(),
    //         'last_page' => $data->lastPage(),
    //         'data' => $data->items(),
    //     ]);
    // }

    // API Kanindo
    public function getData(Request $request)
    {
        $search    = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        // $perPage   = 10;
        $perPage = min((int) $request->query('per_page', 10), 9999);
        $page      = (int) $request->query('page', 1);

        $queryParams = array_filter([
            'search'     => $search,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        // ✅ FIX 1: Cache key unik per kombinasi parameter
        $cacheKey = 'ordersheet_' . md5(serialize($queryParams));

        try {
            // ✅ VALIDASI: Batasi range tanggal maksimal 3 bulan
            if ($startDate && $endDate) {
                $start = strtotime($startDate);
                $end = strtotime($endDate);
                $diffDays = ($end - $start) / 86400;
                
                if ($diffDays > 90) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Range tanggal maksimal 3 bulan (90 hari)'
                    ], 422);
                }
            }

            // ✅ FIX 2: Cache data mentah selama 5 menit
            // Semua request dengan parameter sama hanya fetch 1x ke server sewing
            $items = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($queryParams) {

                Log::info('Fetching dari server sewing', $queryParams);

                // Build URL manual agar semicolon tidak di-encode
                $baseUrl = 'http://192.168.0.20/sewing/qa/ordersheet/get_ordersheet_data_json';
                $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
                
                // Kembalikan semicolon yang ter-encode
                $queryString = str_replace('%3B', ';', $queryString);
                
                $fullUrl = $baseUrl . '?' . $queryString;
                
                Log::info('URL yang dipanggil: ' . $fullUrl);

                $response = Http::timeout(60)->get($fullUrl);

                // Log::info('Response status: ' . $response->status());
                // Log::info('Response body: ' . $response->body());
                
                if (!$response->successful()) {
                    Log::error('Sewing API gagal', [
                        'status' => $response->status(),
                        // ✅ FIX 3: Jangan log body 2.4MB! Cukup 500 char pertama
                        'body'   => substr($response->body(), 0, 500),
                        'params' => $queryParams
                    ]);
                    throw new \RuntimeException('Server sewing error: ' . $response->status());
                }

                $json = $response->json();

                $rawData = null;
                if (isset($json['data']) && is_array($json['data'])) {
                    $rawData = $json['data'];
                } elseif (is_array($json) && array_is_list($json)) {
                    $rawData = $json;
                }

                if (!is_array($rawData)) {
                    throw new \RuntimeException('Struktur data dari server sewing tidak sesuai');
                }

                // ✅ FIX 4: Map + deduplikasi + sort — dilakukan SEKALI, lalu di-cache
                return collect($rawData)
                    ->map(fn($item) => [
                        'id'                  => data_get($item, 'id') ?? data_get($item, 'ID'),
                        'KJ'                  => data_get($item, 'KJ') ?? data_get($item, 'KJ'),
                        'ProductCode'         => data_get($item, 'ProductCode') ?? data_get($item, 'Style'),
                        'ColorDescription'    => data_get($item, 'ColorDescription') ?? data_get($item, 'Color'),
                        'Buyer'               => data_get($item, 'Buyer') ?? data_get($item, 'buyer'),
                        'PurchaseOrderNumber' => data_get($item, 'PurchaseOrderNumber') ?? data_get($item, 'po_number') ?? data_get($item, 'PO'),
                        'ProductName'         => data_get($item, 'ProductName') ?? data_get($item, 'product_name'),
                        'Qty'                 => data_get($item, 'Qty') ?? data_get($item, 'qty') ?? 0,
                        'ActualFOB'           => data_get($item, 'ActualFOB') ?? data_get($item, 'actual_fob'),
                        'GAC'                 => data_get($item, 'GAC') ?? data_get($item, 'GAC'),
                        // 'DocumentDate'        => data_get($item, 'DocumentDate') ?? data_get($item, 'document_date') ?? data_get($item, 'date'),
                    ])
                    ->unique('id')
                    ->sortByDesc(fn($item) => strtotime($item['GAC'] ?? '1970-01-01'))
                    ->values()
                    ->all(); // ✅ simpan sebagai array biasa di cache, bukan Collection
            });

            $items = collect($items);

            // Pagination
            $total    = $items->count();
            $results  = $items->slice(($page - 1) * $perPage, $perPage)->values();

            $paginator = new LengthAwarePaginator($results, $total, $perPage, $page, [
                'path' => $request->url()
            ]);

            // ✅ TAMBAHKAN LOG DI SINI
            // Log::info("Data dikirim ke browser :", $paginator->items());

            return response()->json([
                'success'      => true,
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'data'         => $paginator->items(),
            ]);
        } catch (\RuntimeException $e) {
            // Error yang kita throw sendiri (API gagal, struktur salah)
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        } catch (\Exception $e) {
            Log::error('Error di OrderSheetController', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }
    }

    /**
     * Print semua, atau filter by buyer
     */
    public function print($buyer = null)
    {
        $auth = Auth::user();

        $orders = Ordersheet::with([
            'timbangans' => function ($q) {
                $q->select(
                    'id',
                    'id_ordersheet',
                    'no_box',
                    'berat',
                    'pcs',
                    'rasio_batas_beban_min',
                    'rasio_batas_beban_max',
                    'waktu_timbang'
                )
                    ->orderBy('waktu_timbang');
            }
        ])
            ->where('status', 'Success')
            ->whereHas('timbangans')
            ->select(
                'id',
                'Order_code',
                'KJ',
                'Buyer',
                'PO',
                'Style',
                'line',
                'Qty_order',
                'PCS',
                'Ctn',
                'Less_Ctn',
                'Pcs_Less_Ctn',
                'Carton_weight_std',
                'Pcs_weight_std',
                'Gac_date',
                'Destination',
                'Inspector',
                'OPT_QC_TIMBANGAN',
                'SPV_QC',
                'CHIEF_FINISH_GOOD',
                'status',
                'created_at'
            )
            ->latest()
            ->get();

        if ($buyer) {
            $orders = $orders->where('Buyer', $buyer);
        }

        // Group by KJ → sub-group by line
        $groupedByKJ = $orders->groupBy(function ($item) {
            return $item->KJ ?? $item->Order_code;
        })->map(function ($kjOrders) {
            $byLine        = $kjOrders->groupBy('line')->sortKeys();
            $allTimbangans = $kjOrders->flatMap(fn($o) => $o->timbangans)->sortBy('waktu_timbang')->values();
            $first         = $kjOrders->first();

            $firstDate = optional($allTimbangans->first())->waktu_timbang;
            $date      = $firstDate
                ? \Carbon\Carbon::parse($firstDate)->format('d-m-Y')
                : 'Tanpa Tanggal';

            return [
                'kj'            => $first->KJ ?? $first->Order_code,
                'order_code'    => $first->Order_code,
                'buyer'         => $first->Buyer ?? '-',
                'style'         => $first->Style ?? '-',
                'po'            => $first->PO ?? '-',
                'line_list'     => $byLine->keys()->sort()->values(),
                'date'          => $date,
                'qty_total'     => intval($first->Qty_order) ?: 0,
                'qty_sudah'     => $allTimbangans->sum('pcs'),
                'total_berat'   => $allTimbangans->sum('berat'),
                'total_carton'  => $allTimbangans->count(),
                'carton_std'    => $first->Carton_weight_std,
                'pcs_std'       => $first->Pcs_weight_std,
                'less_ctn'      => $first->Less_Ctn,
                'pcs_less_ctn'  => $first->Pcs_Less_Ctn,
                'gac_date'      => $first->Gac_date
                    ? \Carbon\Carbon::parse($first->Gac_date)->format('d-m-Y')
                    : '-',
                'destination'   => $first->Destination ?? '-',
                'inspector'     => $first->Inspector ?? '-',
                'opt_qc'        => $first->OPT_QC_TIMBANGAN ?? '-',
                'spv_qc'        => $first->SPV_QC ?? '-',
                'chief'         => $first->CHIEF_FINISH_GOOD ?? '-',
                'by_line'       => $byLine,
                'all_timbangans' => $allTimbangans,
            ];
        });

        return view('order.print', compact('auth', 'groupedByKJ'));
    }

    /**
     * Print by Order Code spesifik
     */
    public function printByOrderCode($orderCode)
    {
        $auth = Auth::user();

        $orders = Ordersheet::with([
            'timbangans' => function ($q) {
                $q->select(
                    'id',
                    'id_ordersheet',
                    'no_box',
                    'berat',
                    'pcs',
                    'rasio_batas_beban_min',
                    'rasio_batas_beban_max',
                    'waktu_timbang'
                )
                    ->orderBy('waktu_timbang');
            }
        ])
            ->where('status', 'Success')
            ->where('Order_code', $orderCode)
            ->whereHas('timbangans')
            ->select(
                'id',
                'Order_code',
                'KJ',
                'Buyer',
                'PO',
                'Style',
                'line',
                'Qty_order',
                'PCS',
                'Ctn',
                'Less_Ctn',
                'Pcs_Less_Ctn',
                'Carton_weight_std',
                'Pcs_weight_std',
                'Gac_date',
                'Destination',
                'Inspector',
                'OPT_QC_TIMBANGAN',
                'SPV_QC',
                'CHIEF_FINISH_GOOD',
                'status',
                'created_at'
            )
            ->latest()
            ->get();

        // Sama strukturnya, hanya 1 KJ
        $groupedByKJ = $orders->groupBy(function ($item) {
            return $item->KJ ?? $item->Order_code;
        })->map(function ($kjOrders) {
            $byLine        = $kjOrders->groupBy('line')->sortKeys();
            $allTimbangans = $kjOrders->flatMap(fn($o) => $o->timbangans)->sortBy('waktu_timbang')->values();
            $first         = $kjOrders->first();

            $firstDate = optional($allTimbangans->first())->waktu_timbang;
            $date      = $firstDate
                ? \Carbon\Carbon::parse($firstDate)->format('d-m-Y')
                : 'Tanpa Tanggal';

            return [
                'kj'            => $first->KJ ?? $first->Order_code,
                'order_code'    => $first->Order_code,
                'buyer'         => $first->Buyer ?? '-',
                'style'         => $first->Style ?? '-',
                'po'            => $first->PO ?? '-',
                'line_list'     => $byLine->keys()->sort()->values(),
                'date'          => $date,
                'qty_total'     => intval($first->Qty_order) ?: 0,
                'qty_sudah'     => $allTimbangans->sum('pcs'),
                'total_berat'   => $allTimbangans->sum('berat'),
                'total_carton'  => $allTimbangans->count(),
                'carton_std'    => $first->Carton_weight_std,
                'pcs_std'       => $first->Pcs_weight_std,
                'less_ctn'      => $first->Less_Ctn,
                'pcs_less_ctn'  => $first->Pcs_Less_Ctn,
                'gac_date'      => $first->Gac_date
                    ? \Carbon\Carbon::parse($first->Gac_date)->format('d-m-Y')
                    : '-',
                'destination'   => $first->Destination ?? '-',
                'inspector'     => $first->Inspector ?? '-',
                'opt_qc'        => $first->OPT_QC_TIMBANGAN ?? '-',
                'spv_qc'        => $first->SPV_QC ?? '-',
                'chief'         => $first->CHIEF_FINISH_GOOD ?? '-',
                'by_line'       => $byLine,
                'all_timbangans' => $allTimbangans,
            ];
        });

        return view('order.print', compact('auth', 'groupedByKJ'));
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
