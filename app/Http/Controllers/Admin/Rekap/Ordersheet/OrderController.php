<?php

namespace App\Http\Controllers\Admin\Rekap\Ordersheet;

use App\Exports\RekapOrdersheetExport;
use App\Http\Controllers\Controller;
use App\Models\Ordersheet;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    // Controller (RekapOrderController.php atau nama controller Anda)
    public function index(Request $request)
    {
        $perPage = 10;

        // Fetch users untuk dropdown filter (asumsi role 'user' atau sesuaikan)
        $users = User::where('status', 'Aktif')
            ->where('role', 'user')
            ->orderBy('username')->get();

        $query = Ordersheet::with('device', 'user', 'timbangans')
            ->where('status', 'Success')
            // ->where('id_user', auth()->id()) // Uncomment jika perlu restrict ke user login
            ->orderBy('id', 'desc');

        // Pagination default (tanpa filter search)
        $rekap_order = $query->paginate($perPage);

        return view('admin.rekap.order.view', compact('rekap_order', 'users'));
    }

    public function getRekapData(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $userId = $request->query('user_id'); // Tambah filter user
        $perPage = 10;

        $query = Ordersheet::with([
            'timbangans:id,id_ordersheet,no_box,berat,waktu_timbang',
            'device:id,esp_id', // Tambah relation device
            'user:id,username'  // Tambah relation user
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

        // Filter user
        if ($userId) {
            $query->where('id_user', $userId);
        }

        // Filter tanggal (custom atau dari periode)
        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        } elseif ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $rekapbesar = $query->paginate($perPage)->withQueryString();

        // Struktur JSON sama seperti Blade, tambah device & user
        $rekapbesar->getCollection()->transform(function ($item) {
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
                'device' => $item->device ? ['esp_id' => $item->device->esp_id] : null,
                'user' => $item->user ? ['username' => $item->user->username] : null,
                'status' => $item->status,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($rekapbesar);
    }

    public function export(Request $request)
    {
        $search    = $request->query('search');
        $userId    = $request->query('user_id');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $fileName = 'rekap_ordersheet_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new RekapOrdersheetExport($search, $userId, $startDate, $endDate),
            $fileName
        );
    }
}
