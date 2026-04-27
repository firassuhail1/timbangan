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

    //     $orders = Ordersheet::with([
    //         'timbangans' => function ($q) {
    //             $q->select(
    //                 'id',
    //                 'id_ordersheet',
    //                 'no_box',
    //                 'berat',
    //                 'pcs',
    //                 'waktu_timbang',
    //                 'rasio_batas_beban_min',
    //                 'rasio_batas_beban_max'
    //             )
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
    //             'line',
    //             'Qty_order',
    //             'PCS',
    //             'Ctn',
    //             'Less_Ctn',
    //             'Pcs_Less_Ctn',
    //             'Destination',
    //             'Gac_date',
    //             'Inspector',
    //             'OPT_QC_TIMBANGAN',
    //             'SPV_QC',
    //             'CHIEF_FINISH_GOOD',
    //             'status',
    //             'created_at'
    //         )
    //         ->latest()
    //         ->get();

    //     // Group utama by KJ (untuk progress keseluruhan)
    //     // Setiap KJ punya sub-group per line
    //     $groupedByKJ = $orders->groupBy(function ($item) {
    //         return $item->KJ ?? $item->Order_code;
    //     })->map(function ($kjOrders) {
    //         // Sub-group by line dalam 1 KJ
    //         $byLine = $kjOrders->groupBy('line')->sortKeys();

    //         // Semua timbangan dari semua line dalam KJ ini
    //         $allTimbangans = $kjOrders->flatMap(fn($o) => $o->timbangans);

    //         // Info dari record pertama
    //         $first      = $kjOrders->first();
    //         $qtyTotal   = intval($first->Qty_order) ?: 0;
    //         $qtySudah   = $allTimbangans->sum('pcs');
    //         $totalBerat = $allTimbangans->sum('berat');
    //         $qtySisa    = max(0, $qtyTotal - $qtySudah);

    //         // Tanggal timbang pertama
    //         $firstDate = optional($allTimbangans->sortBy('waktu_timbang')->first())->waktu_timbang;
    //         $date      = $firstDate
    //             ? \Carbon\Carbon::parse($firstDate)->format('d-m-Y')
    //             : 'Tanpa Tanggal';

    //         return [
    //             'kj'           => $first->KJ ?? $first->Order_code,
    //             'order_code'   => $first->Order_code,
    //             'buyer'        => $first->Buyer ?? '-',
    //             'style'        => $first->Style ?? '-',
    //             'date'         => $date,
    //             'qty_total'    => $qtyTotal,
    //             'qty_sudah'    => $qtySudah,
    //             'qty_sisa'     => $qtySisa,
    //             'total_berat'  => $totalBerat,
    //             'total_carton' => $allTimbangans->count(),
    //             'by_line'      => $byLine,      // collection per line
    //             'all_timbangans' => $allTimbangans,
    //         ];
    //     });

    //     return view('order.index', compact('auth', 'groupedByKJ'));
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
                    'rasio_batas_beban_min',
                    'rasio_batas_beban_max',
                    'waktu_timbang'
                )->orderBy('waktu_timbang');
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
                'ColorDescription',
                'Line',
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

        // ── Level 1: KJ ──────────────────────────────────────────
        $groupedByKJ = $orders
            ->groupBy(fn($o) => trim($o->KJ ?? $o->Order_code))
            ->map(function ($kjOrders, $kj) {

                // ── Level 2: PO ───────────────────────────────────
                $byPO = $kjOrders
                    ->groupBy(fn($o) => $o->PO ?? '-')
                    ->map(function ($poOrders, $po) {

                        // ── Level 3: Style ────────────────────────
                        $byStyle = $poOrders
                            ->groupBy(fn($o) => $o->Style ?? '-')
                            ->map(function ($styleOrders, $style) {

                                // ── Level 4: Color ────────────────
                                $byColor = $styleOrders
                                    ->groupBy(fn($o) => $o->ColorDescription ?? '-')
                                    ->map(function ($colorOrders, $color) {

                                        // ── Level 5: ORDER ID (ini yg baru) ──
                                        // Group by id dulu, supaya record berbeda destination
                                        // tidak saling menimpa
                                        $byOrderId = $colorOrders
                                            ->groupBy(fn($o) => $o->id)
                                            ->map(function ($orderRows, $orderId) {

                                                // ── Level 6: Line ─────────
                                                $byLine = $orderRows
                                                    ->groupBy(fn($o) => $o->Line ?? '-')
                                                    ->sortKeys()
                                                    ->map(function ($lineOrders, $line) {
                                                        $timbangans = $lineOrders
                                                            ->flatMap(fn($o) => $o->timbangans)
                                                            ->sortBy('waktu_timbang')
                                                            ->values();

                                                        $firstOrder = $lineOrders->first();

                                                        return [
                                                            'line'         => $line,
                                                            'timbangans'   => $timbangans,
                                                            'qty_sudah'    => $timbangans->sum('pcs'),
                                                            'total_berat'  => $timbangans->sum('berat'),
                                                            'total_carton' => $timbangans->count(),
                                                            'carton_std'   => $firstOrder->Carton_weight_std,
                                                            'pcs_std'      => $firstOrder->Pcs_weight_std,
                                                            'inspector'    => $firstOrder->Inspector,
                                                            'opt_qc'       => $firstOrder->OPT_QC_TIMBANGAN,
                                                            'spv_qc'       => $firstOrder->SPV_QC,
                                                            'chief'        => $firstOrder->CHIEF_FINISH_GOOD,
                                                        ];
                                                    });

                                                $allT       = $byLine->flatMap(fn($l) => $l['timbangans']);
                                                $firstOrder = $orderRows->first();
                                                $firstDate  = optional(
                                                    $allT->sortBy('waktu_timbang')->first()
                                                )->waktu_timbang;

                                                return [
                                                    'order_id'     => $orderId,
                                                    'order_code'   => $firstOrder->Order_code,
                                                    'buyer'        => $firstOrder->Buyer        ?? '-',
                                                    'qty_order'    => $firstOrder->Qty_order    ?? 0,
                                                    'destination'  => $firstOrder->Destination  ?? '-',
                                                    'gac_date'     => $firstOrder->Gac_date
                                                        ? \Carbon\Carbon::parse($firstOrder->Gac_date)->format('d-m-Y')
                                                        : '-',
                                                    'less_ctn'     => $firstOrder->Less_Ctn     ?? '-',
                                                    'pcs_less_ctn' => $firstOrder->Pcs_Less_Ctn ?? '-',
                                                    'date'         => $firstDate
                                                        ? \Carbon\Carbon::parse($firstDate)->format('d-m-Y')
                                                        : 'Tanpa Tanggal',
                                                    'qty_sudah'    => $allT->sum('pcs'),
                                                    'total_berat'  => $allT->sum('berat'),
                                                    'total_carton' => $allT->count(),
                                                    'qty_sisa'     => max(0, intval($firstOrder->Qty_order) - $allT->sum('pcs')),
                                                    'by_line'      => $byLine,
                                                ];
                                            });

                                        // Aggregate semua timbangan dalam color ini (lintas order id)
                                        $allT = $byOrderId->flatMap(
                                            fn($o) => $o['by_line']->flatMap(fn($l) => $l['timbangans'])
                                        );

                                        return [
                                            'color'        => $color,
                                            'qty_sudah'    => $allT->sum('pcs'),
                                            'total_berat'  => $allT->sum('berat'),
                                            'total_carton' => $allT->count(),
                                            'by_order_id'  => $byOrderId, // <-- ganti by_line ke by_order_id
                                        ];
                                    });

                                $allT = $byColor->flatMap(
                                    fn($c) => $c['by_order_id']->flatMap(
                                        fn($o) => $o['by_line']->flatMap(fn($l) => $l['timbangans'])
                                    )
                                );

                                return [
                                    'style'        => $style,
                                    'qty_sudah'    => $allT->sum('pcs'),
                                    'total_berat'  => $allT->sum('berat'),
                                    'total_carton' => $allT->count(),
                                    'by_color'     => $byColor,
                                ];
                            });

                        $allT = $byStyle->flatMap(fn($s) => $s['by_color']->flatMap(
                            fn($c) => $c['by_order_id']->flatMap(
                                fn($o) => $o['by_line']->flatMap(fn($l) => $l['timbangans'])
                            )
                        ));

                        return [
                            'po'           => $po,
                            'qty_sudah'    => $allT->sum('pcs'),
                            'total_berat'  => $allT->sum('berat'),
                            'total_carton' => $allT->count(),
                            'by_style'     => $byStyle,
                        ];
                    });

                $allT = $byPO->flatMap(fn($p) => $p['by_style']->flatMap(
                    fn($s) => $s['by_color']->flatMap(
                        fn($c) => $c['by_order_id']->flatMap(
                            fn($o) => $o['by_line']->flatMap(fn($l) => $l['timbangans'])
                        )
                    )
                ));

                $first = $kjOrders->first();

                return [
                    'kj'           => $kj,
                    'buyer'        => $first->Buyer ?? '-',
                    'qty_sudah'    => $allT->sum('pcs'),
                    'total_berat'  => $allT->sum('berat'),
                    'total_carton' => $allT->count(),
                    'by_po'        => $byPO,
                ];
            });

        return view('order.index', compact('auth', 'groupedByKJ'));
    }

    public function formalReport(Request $request)
    {
        Log::info('masuk');
        $start = $request->get('start', now()->format('Y-m-d'));
        $end   = $request->get('end',   now()->format('Y-m-d'));

        // Ambil semua Ordersheet yang punya timbangan di rentang waktu
        $orders = Ordersheet::with([
            'timbangans' => function ($q) use ($start, $end) {
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
                    ->whereDate('waktu_timbang', '>=', $start)
                    ->whereDate('waktu_timbang', '<=', $end)
                    ->orderBy('waktu_timbang');
            }
        ])
            ->where('status', 'Success')
            ->whereHas('timbangans', function ($q) use ($start, $end) {
                $q->whereDate('waktu_timbang', '>=', $start)
                    ->whereDate('waktu_timbang', '<=', $end);
            })
            ->select(
                'id',
                'Order_code',
                'KJ',
                'Buyer',
                'PO',
                'Style',
                'ColorDescription',
                'Line',
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
            ->get()
            // Hanya yang punya timbangan di rentang ini
            ->filter(fn($o) => $o->timbangans->isNotEmpty());

        // Pisah Nike vs Non-Nike (case-insensitive)
        $nike    = $orders->filter(fn($o) => strtolower(trim($o->Buyer)) === 'nike');
        $nonNike = $orders->filter(fn($o) => strtolower(trim($o->Buyer)) !== 'nike');

        // ── Format NIKE ──────────────────────────────────────────
        // Tiap Ordersheet = 1 row di tabel
        $nikeRows = $nike->map(function ($o) {
            $firstDate = optional($o->timbangans->first())->waktu_timbang;
            return [
                'order_code'        => $o->Order_code,
                'kj'                => trim($o->KJ ?? '-'),
                'buyer'             => $o->Buyer,
                'style'             => $o->Style,
                'color'             => $o->ColorDescription,
                'pcs'               => $o->PCS,           // MOC / isi per carton
                'qty_order'         => $o->Qty_order,
                'gac_date'          => $o->Gac_date
                    ? \Carbon\Carbon::parse($o->Gac_date)->format('d-m-Y')
                    : '-',
                'destination'       => $o->Destination,
                'line'              => $o->Line,
                'carton_weight_std' => $o->Carton_weight_std,
                'tanggal'           => $firstDate
                    ? \Carbon\Carbon::parse($firstDate)->format('d-m-Y')
                    : '-',
                // Hanya berat, tanpa no_box (Nike belum pakai no box)
                'timbangans'        => $o->timbangans->map(fn($t) => [
                    'berat'      => $t->berat,
                    'waktu'      => $t->waktu_timbang
                        ? \Carbon\Carbon::parse($t->waktu_timbang)->format('H:i')
                        : '-',
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        // ── Format NON-NIKE ──────────────────────────────────────
        // Group by Order_code (sudah unik per order+line)
        // Tiap order = 1 blok laporan
        $nonNikeBlocks = $nonNike->groupBy('Order_code')->map(function ($orderGroup) {

            $first       = $orderGroup->first();
            $allTimbang  = $orderGroup->flatMap(fn($o) => $o->timbangans)->sortBy('waktu_timbang')->values();

            // By Line
            $byLine = $orderGroup->groupBy('Line')->sortKeys()->map(function ($lineOrders, $line) {
                $timbangans = $lineOrders->flatMap(fn($o) => $o->timbangans)
                    ->sortBy('waktu_timbang')
                    ->values();

                return [
                    'line'        => $line,
                    'timbangans'  => $timbangans->map(fn($t) => [
                        'no_box'                => $t->no_box,
                        'berat'                 => $t->berat,
                        'pcs'                   => $t->pcs,
                        'waktu_timbang'         => optional($t->waktu_timbang instanceof \Carbon\Carbon
                            ? $t->waktu_timbang
                            : \Carbon\Carbon::parse($t->waktu_timbang))->format('Y-m-d H:i:s'),
                        'rasio_batas_beban_min' => $t->rasio_batas_beban_min,
                        'rasio_batas_beban_max' => $t->rasio_batas_beban_max,
                    ])->values()->toArray(),
                    'total_carton' => $timbangans->count(),
                    'total_berat'  => $timbangans->sum('berat'),
                    'qty_sudah'    => $timbangans->sum('pcs'),
                ];
            })->values()->toArray();

            return [
                'order_code'        => $first->Order_code,
                'kj'                => trim($first->KJ ?? '-'),
                'buyer'             => $first->Buyer,
                'po'                => $first->PO,
                'style'             => $first->Style,
                'color'             => $first->ColorDescription,
                'line_list'         => $orderGroup->pluck('Line')->unique()->sort()->values()->toArray(),
                'pcs_default'       => $first->PCS,   // M = MOC
                'qty_order'         => $first->Qty_order,
                'less_ctn'          => $first->Less_Ctn,
                'pcs_less_ctn'      => $first->Pcs_Less_Ctn,
                'carton_weight_std' => $first->Carton_weight_std,
                'pcs_weight_std'    => $first->Pcs_weight_std,
                'gac_date'          => $first->Gac_date
                    ? \Carbon\Carbon::parse($first->Gac_date)->format('d/m/Y')
                    : '-',
                'destination'       => $first->Destination,
                'inspector'         => $first->Inspector,
                'opt_qc'            => $first->OPT_QC_TIMBANGAN,
                'spv_qc'            => $first->SPV_QC,
                'chief'             => $first->CHIEF_FINISH_GOOD,
                'total_carton'      => $allTimbang->count(),
                'total_berat'       => $allTimbang->sum('berat'),
                'qty_sudah'         => $allTimbang->sum('pcs'),
                'by_line'           => $byLine,
            ];
        })->values()->toArray();

        return response()->json([
            'success'  => true,
            'nike'     => $nikeRows,
            'non_nike' => $nonNikeBlocks,
        ]);
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
    // public function getData(Request $request)
    // {
    //     $search    = $request->query('search');
    //     $startDate = $request->query('start_date');
    //     $endDate   = $request->query('end_date');
    //     // $perPage   = 10;
    //     $perPage = min((int) $request->query('per_page', 10), 9999);
    //     $page      = (int) $request->query('page', 1);

    //     $queryParams = array_filter([
    //         'search'     => $search,
    //         'start_date' => $startDate,
    //         'end_date'   => $endDate,
    //     ]);

    //     // ✅ FIX 1: Cache key unik per kombinasi parameter
    //     $cacheKey = 'ordersheet_' . md5(serialize($queryParams));

    //     try {
    //         // ✅ VALIDASI: Batasi range tanggal maksimal 3 bulan
    //         if ($startDate && $endDate) {
    //             $start = strtotime($startDate);
    //             $end = strtotime($endDate);
    //             $diffDays = ($end - $start) / 86400;

    //             if ($diffDays > 90) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Range tanggal maksimal 3 bulan (90 hari)'
    //                 ], 422);
    //             }
    //         }

    //         // ✅ FIX 2: Cache data mentah selama 5 menit
    //         // Semua request dengan parameter sama hanya fetch 1x ke server sewing
    //         $items = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($queryParams) {

    //             Log::info('Fetching dari server sewing', $queryParams);

    //             // Build URL manual agar semicolon tidak di-encode
    //             $baseUrl = 'http://192.168.0.20/sewing/qa/ordersheet/get_ordersheet_data_json';
    //             $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

    //             // Kembalikan semicolon yang ter-encode
    //             $queryString = str_replace('%3B', ';', $queryString);

    //             $fullUrl = $baseUrl . '?' . $queryString;

    //             Log::info('URL yang dipanggil: ' . $fullUrl);

    //             $response = Http::timeout(60)->get($fullUrl);

    //             // Log::info('Response status: ' . $response->status());
    //             // Log::info('Response body: ' . $response->body());

    //             if (!$response->successful()) {
    //                 Log::error('Sewing API gagal', [
    //                     'status' => $response->status(),
    //                     // ✅ FIX 3: Jangan log body 2.4MB! Cukup 500 char pertama
    //                     'body'   => substr($response->body(), 0, 500),
    //                     'params' => $queryParams
    //                 ]);
    //                 throw new \RuntimeException('Server sewing error: ' . $response->status());
    //             }

    //             $json = $response->json();

    //             $rawData = null;
    //             if (isset($json['data']) && is_array($json['data'])) {
    //                 $rawData = $json['data'];
    //             } elseif (is_array($json) && array_is_list($json)) {
    //                 $rawData = $json;
    //             }

    //             if (!is_array($rawData)) {
    //                 throw new \RuntimeException('Struktur data dari server sewing tidak sesuai');
    //             }

    //             // ✅ FIX 4: Map + deduplikasi + sort — dilakukan SEKALI, lalu di-cache
    //             return collect($rawData)
    //                 ->map(fn($item) => [
    //                     'id'                  => data_get($item, 'id') ?? data_get($item, 'ID'),
    //                     'KJ'                  => data_get($item, 'KJ') ?? data_get($item, 'KJ'),
    //                     'ProductCode'         => data_get($item, 'ProductCode') ?? data_get($item, 'Style'),
    //                     'ColorDescription'    => data_get($item, 'ColorDescription') ?? data_get($item, 'Color'),
    //                     'Buyer'               => data_get($item, 'Buyer') ?? data_get($item, 'buyer'),
    //                     'PurchaseOrderNumber' => data_get($item, 'PurchaseOrderNumber') ?? data_get($item, 'po_number') ?? data_get($item, 'PO'),
    //                     'ProductName'         => data_get($item, 'ProductName') ?? data_get($item, 'product_name'),
    //                     'Qty'                 => data_get($item, 'Qty') ?? data_get($item, 'qty') ?? 0,
    //                     'ActualFOB'           => data_get($item, 'ActualFOB') ?? data_get($item, 'actual_fob'),
    //                     'GAC'                 => data_get($item, 'GAC') ?? data_get($item, 'GAC'),
    //                     // 'DocumentDate'        => data_get($item, 'DocumentDate') ?? data_get($item, 'document_date') ?? data_get($item, 'date'),
    //                 ])
    //                 ->unique('id')
    //                 ->sortByDesc(fn($item) => strtotime($item['GAC'] ?? '1970-01-01'))
    //                 ->values()
    //                 ->all(); // ✅ simpan sebagai array biasa di cache, bukan Collection
    //         });

    //         $items = collect($items);

    //         // Pagination
    //         $total    = $items->count();
    //         $results  = $items->slice(($page - 1) * $perPage, $perPage)->values();

    //         $paginator = new LengthAwarePaginator($results, $total, $perPage, $page, [
    //             'path' => $request->url()
    //         ]);

    //         // ✅ TAMBAHKAN LOG DI SINI
    //         // Log::info("Data dikirim ke browser :", $paginator->items());

    //         return response()->json([
    //             'success'      => true,
    //             'total'        => $paginator->total(),
    //             'current_page' => $paginator->currentPage(),
    //             'last_page'    => $paginator->lastPage(),
    //             'data'         => $paginator->items(),
    //         ]);
    //     } catch (\RuntimeException $e) {
    //         // Error yang kita throw sendiri (API gagal, struktur salah)
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
    //     } catch (\Exception $e) {
    //         Log::error('Error di OrderSheetController', [
    //             'message' => $e->getMessage(),
    //             'file'    => $e->getFile() . ':' . $e->getLine(),
    //         ]);
    //         return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
    //     }
    // }

    public function getData(Request $request)
    {
        $search    = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $perPage   = min((int) $request->query('per_page', 10), 9999);
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
            $itemsRaw = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($queryParams) {

                // ============================================================
                // MODE DUMMY / API SWITCH
                // ============================================================
                $useDummy = true; // Set ke false jika ingin pakai API asli

                if ($useDummy) {
                    // Path ke file dummy kamu
                    $path = storage_path('app/dummy_orders.txt');

                    if (!file_exists($path)) {
                        throw new \RuntimeException("File dummy tidak ditemukan di: " . $path);
                    }

                    $rawContent = file_get_contents($path);
                    $rawData = json_decode($rawContent, true);

                    // Cek apakah JSON valid (tidak null)
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException("Format JSON di file dummy rusak: " . json_last_error_msg());
                    }
                } else {
                    $baseUrl = 'http://192.168.0.20/sewing/qa/ordersheet/get_ordersheet_data_json';
                    $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
                    $queryString = str_replace('%3B', ';', $queryString);
                    $fullUrl = $baseUrl . '?' . $queryString;

                    Log::info('Memanggil API Sewing: ' . $fullUrl);

                    $response = Http::timeout(60)->get($fullUrl);

                    if (!$response->successful()) {
                        throw new \RuntimeException('Server sewing error: ' . $response->status());
                    }

                    $json = $response->json();
                    $rawData = (isset($json['data']) && is_array($json['data'])) ? $json['data'] : $json;
                }

                if (!is_array($rawData)) {
                    throw new \RuntimeException('Struktur data server/dummy tidak sesuai (bukan array)');
                }

                // Mapping & Cleaning
                return collect($rawData)
                    ->map(fn($item) => [
                        'id'                  => data_get($item, 'id') ?? data_get($item, 'ID'),
                        'KJ'                  => data_get($item, 'KJ'),
                        'ProductCode'         => data_get($item, 'ProductCode') ?? data_get($item, 'Style'),
                        'ColorDescription'    => data_get($item, 'ColorDescription') ?? data_get($item, 'Color'),
                        'FinalDestination'    => data_get($item, 'FinalDestination') ?? data_get($item, 'Destination'),
                        'Buyer'               => data_get($item, 'Buyer'),
                        'PurchaseOrderNumber' => data_get($item, 'PurchaseOrderNumber') ?? data_get($item, 'PO'),
                        'ProductName'         => data_get($item, 'ProductName'),
                        'Qty'                 => (int) (data_get($item, 'Qty') ?? 0),
                        'ActualFOB'           => data_get($item, 'ActualFOB'),
                        'GAC'                 => data_get($item, 'GAC'),
                    ])
                    ->unique('id')
                    ->sortByDesc(fn($item) => strtotime($item['GAC'] ?? '1970-01-01'))
                    ->values()
                    ->all(); // Simpan sebagai array di cache
            });

            // Ubah kembali array cache menjadi Collection untuk Pagination
            $items = collect($itemsRaw);

            // ✅ MANUAL PAGINATION
            $total    = $items->count();
            $results  = $items->slice(($page - 1) * $perPage, $perPage)->values();

            $paginator = new LengthAwarePaginator($results, $total, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return response()->json([
                'success'      => true,
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'data'         => $paginator->items(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        } catch (\Exception $e) {
            Log::error('Error di OrderSheetController', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server internal'], 500);
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
