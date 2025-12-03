<?php

namespace App\Http\Controllers\Ordersheet;

use App\Http\Controllers\Controller;
use App\Models\OrdersheetPackage;
use App\Models\OrdersheetPackageweight;
use App\Models\Update\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    public function index()
    {
        $auth = Auth::user();

        // Ambil semua package beserta berat terakhir
        $packages = OrdersheetPackage::with(['weights' => function ($q) {
            $q->orderBy('waktu_timbang', 'desc')->limit(1); // Ambil berat terakhir
                }])->orderBy('created_at', 'desc')
                ->where('id_user', Auth::id())
                ->paginate(10);
        
        // $device = Device::all(); 
        // dd(vars: $device);

        return view('package.view', compact('packages', 'auth'));
    }

    public function apiSearch(Request $request)
    {
        $search    = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $perPage   = $request->query('per_page', 10);

        $userId = Auth::id();

        $query = OrdersheetPackage::with('weights')
            ->where('id_user', $userId)
            ->select('id', 'name', 'description', 'leather_type', 'color', 'size', 
                    'stitching_type', 'lining_material', 'created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('leather_type', 'LIKE', "%{$search}%")
                ->orWhere('color', 'LIKE', "%{$search}%")
                ->orWhere('size', 'LIKE', "%{$search}%")
                ->orWhere('stitching_type', 'LIKE', "%{$search}%")
                ->orWhere('lining_material', 'LIKE', "%{$search}%")
                ->orWhereHas('weights', function ($w) use ($search) {
                    $w->where('no_package', 'LIKE', "%{$search}%")
                        ->orWhere('weight', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Ambil berat terakhir untuk setiap package
        $items = $data->getCollection()->map(function ($package) {
            $lastWeight = $package->weights()
                ->orderBy('waktu_timbang', 'desc')
                ->first();

            $package->last_weight = $lastWeight?->weight ?? null;
            $package->last_no_package = $lastWeight?->no_package ?? null;
            $package->last_timbang = $lastWeight?->waktu_timbang ?? null;

            return $package;
        });

        $data->setCollection($items);

        return response()->json([
            'success'      => true,
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }

    // public function getData(Request $request)
    // {
    //     $search = $request->query('search');
    //     $startDate = $request->query('start_date');
    //     $endDate = $request->query('end_date');
    //     $perPage = 10;

    //     $query = OrdersheetPackage::query();

    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('name', 'LIKE', "%{$search}%")
    //             ->orWhere('description', 'LIKE', "%{$search}%")
    //             ->orWhere('leather_type', 'LIKE', "%{$search}%")
    //             ->orWhere('color', 'LIKE', "%{$search}%")
    //             ->orWhere('stitching_type', 'LIKE', "%{$search}%")
    //             ->orWhere('lining_material', 'LIKE', "%{$search}%")
    //             ->orWhere('size', 'LIKE', "%{$search}%")

    //             // Perbaikan besar di sini
    //             ->orWhereHas('weights', function ($w) use ($search) {
    //                     $w->where('no_package', 'LIKE', "%{$search}%")
    //                     ->orWhere('weight', 'LIKE', "%{$search}%");
    //             });
    //         });
    //     }

    //     if ($startDate && $endDate) {
    //         $query->whereBetween('created_at', [
    //             $startDate.' 00:00:00',
    //             $endDate.' 23:59:59'
    //         ]);
    //     } elseif ($startDate) {
    //         $query->where('created_at', '>=', $startDate.' 00:00:00');
    //     } elseif ($endDate) {
    //         $query->where('created_at', '<=', $endDate.' 23:59:59');
    //     }

    //     $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

    //     return response()->json([
    //         'success' => true,
    //         'total' => $data->total(),
    //         'current_page' => $data->currentPage(),
    //         'last_page' => $data->lastPage(),
    //         'data' => $data->items(),
    //     ]);
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'berat' => 'required|numeric|min:0',
            'no_package' => 'required|string',
            'rasio_batas_beban_min' => 'required|numeric',
            'rasio_batas_beban_max' => 'required|numeric'
        ]);

        DB::beginTransaction();

        $device = Device::where('user_id', Auth::id())
                ->where('status', 'in_use')
                ->first();

        try {
            $package = OrdersheetPackage::updateOrCreate(
                ['name' => $request->name],
                [
                    'id_user' => Auth::id(),
                    'id_device' => $device?->id,
                    'description' => $request->description,
                    'leather_type' => $request->leather_type,
                    'color' => $request->color,
                    'size' => $request->size,
                    'stitching_type' => $request->stitching_type,
                    'lining_material' => $request->lining_material,
                ]
            );

            $weightData = OrdersheetPackageweight::create([
                'id_user' => Auth::id(),
                'id_device' => $device?->id,
                'id_package' => $package->id,
                'weight' => $request->berat,
                'no_package' => $request->no_package,
                'rasio_batas_beban_min' => $request->rasio_batas_beban_min,
                'rasio_batas_beban_max' => $request->rasio_batas_beban_max,
                'status' => 'Success',
                'waktu_timbang' => now(),
            ]);

            DB::commit();

            // kembalikan data lengkap agar JS bisa render
            return response()->json([
                'success' => true,
                'message' => "Berhasil simpan package '{$package->name}' dengan berat {$request->berat} gram",
                'package' => $package,
                'weight' => $weightData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error simpan ordersheet: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}
