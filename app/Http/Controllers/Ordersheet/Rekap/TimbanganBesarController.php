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
    
}
