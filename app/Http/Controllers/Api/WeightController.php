<?php

namespace App\Http\Controllers\Api;

use App\Events\TareCommand;
use App\Http\Controllers\Controller;
use App\Models\Ordersheet;
use App\Models\Timbangan_riwayat;
use App\Models\Update\Device;
use App\Models\VAllOrdersheetPlusCari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WeightController extends Controller
{
    // public function setCurrentId(Request $request)
    // {
    //     $id = $request->input('id');

    //     if (!$id) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'ID tidak valid'
    //         ]);
    //     }

    //     // Ambil device milik user yg sedang login
    //     // $device = Device::where('user_id', Auth::id())->first();
    //     $device = Device::where('user_id', Auth::id())
    //             ->where('status', 'in_use')
    //             ->first();

    //     if (!$device) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Device tidak ditemukan untuk user ini'
    //         ]);
    //     }

    //     $espId = $device->esp_id;

    //     Log::info('Set current ID', ['id' => $id, 'esp_id' => $espId]);

    //     // Cache current ID
    //     $cacheCurrent = Cache::get("current_id_{$espId}");

    //     if ($cacheCurrent && $cacheCurrent !== $id) {
    //         Cache::forget("weight_preview_{$espId}_{$cacheCurrent}");
    //         Cache::forget("timbang_preview_{$espId}_{$cacheCurrent}");
    //     }

    //     Cache::put("current_id_{$espId}", $id, now()->addMinutes(10));
    //     Cache::put("weight_preview_{$espId}_{$id}", 0, now()->addMinutes(10));

    //     // Simpan file per user
    //     $userId = Auth::id();
    //     $folder = storage_path("app/public/current_id");

    //     if (!file_exists($folder)) mkdir($folder, 0777, true);

    //     $filePath = $folder . "/{$userId}.txt";

    //     if (!file_exists($filePath)) {
    //         file_put_contents($filePath, $id);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'ID aktif diubah',
    //         'current_id' => $id,
    //         'esp_id' => $espId
    //     ]);
    // }

    public function setCurrentId(Request $request)
    {
        $id = (string) $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'ID tidak valid'], 400);
        }

        $device = Device::where('user_id', Auth::id())
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device tidak aktif'], 403);
        }

        $espId = $device->esp_id;
        $ttl   = now()->addMinutes(30);

        $oldId = Cache::get("current_id_{$espId}");
        if ($oldId && $oldId !== $id) {
            Cache::forget("weight_preview_{$espId}_{$oldId}");
        }

        Cache::put("current_id_{$espId}", $id, $ttl);
        Cache::put("weight_preview_{$espId}_{$id}", 0, $ttl);

        Log::info('SET CURRENT ID', compact('espId', 'id'));

        return response()->json([
            'success'    => true,
            'current_id' => $id,
            'esp_id'     => $espId
        ]);
    }

    public function cekIdAktif(Request $request)
    {
        $device = Device::where('api_key', $request->header('X-API-KEY'))
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return response()->json(['current_id' => null]);
        }

        return response()->json([
            'current_id' => Cache::get("current_id_{$device->esp_id}"),
            'esp_id'     => $device->esp_id
        ]);
    }

    // public function cekIdAktif(Request $request)
    // {
    //     $apiKey = $request->header('X-API-KEY');

    //     $device = Device::where('api_key', $apiKey)->first();

    //     if (!$device) {
    //         return response()->json(['current_id' => null]);
    //     }

    //     $espId = $device->esp_id;

    //     $id = Cache::get("current_id_{$espId}");

    //     return response()->json([
    //         'current_id' => $id,
    //         'esp_id' => $espId
    //     ]);
    // }


    public function terimaBerat(Request $request)
    {
        $device = Device::where('api_key', $request->header('X-API-KEY'))
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $id    = (string) $request->input('id');
        $berat = $request->input('berat');

        if (!$id || $berat === null) {
            return response()->json(['message' => 'Data tidak lengkap'], 400);
        }

        Cache::put(
            "weight_preview_{$device->esp_id}_{$id}",
            $berat,
            now()->addSeconds(15)
        );

        Log::info('BERAT MASUK', [
            'esp_id' => $device->esp_id,
            'order'  => $id,
            'kg'     => $berat
        ]);

        return response()->json(['status' => 'OK']);
    }

    // public function terimaBerat(Request $request)
    // {
    //     $apiKey = $request->header('X-API-KEY');

    //     $device = Device::where('api_key', $apiKey)
    //                     ->where('status', 'in_use')
    //                     ->first();

    //     if (!$device) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Unauthorized atau device tidak aktif'
    //         ], 401);
    //     }

    //     $id    = $request->input('id');      // ← dari order yang sedang ditimbang
    //     $berat = $request->input('berat');

    //     if (!$id || $berat === null) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Data id atau berat tidak lengkap'
    //         ], 400);
    //     }

    //     // Cache unik per timbangan + per order → anti bentrok walau banyak user
    //     $cacheKey = "weight_preview_{$device->esp_id}_{$id}";

    //     Cache::put($cacheKey, $berat, now()->addMinutes(5)); // 12 detik cukup untuk polling 1–1.5 detik

    //     Log::info('Berat diterima dari timbangan', [
    //         'esp_id'    => $device->esp_id,
    //         'device_id' => $device->id,
    //         'order_id'  => $id,
    //         'berat_kg'  => $berat,
    //         'user_id'   => $device->user_id,
    //         'cache_key' => $cacheKey,
    //     ]);

    //     return response('OK', 200);
    // }


    public function getPreview($id)
    {
        $device = Device::where('user_id', Auth::id())
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return response()->json(['success' => false], 403);
        }

        $berat = Cache::get("weight_preview_{$device->esp_id}_{$id}", 0);

        return response()->json([
            'success' => true,
            'berat'   => round((float) $berat, 3)
        ]);
    }

    // public function getPreview($id)
    // {
    //     $device = Device::where('user_id', Auth::id())
    //                     ->where('status', 'in_use')
    //                     ->first();

    //     if (!$device) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Timbangan belum dipilih atau device tidak aktif'
    //         ], 403);
    //     }

    //     $cacheKey = "weight_preview_{$device->esp_id}_{$id}";
    //     $berat    = Cache::get($cacheKey, 0.0);

    //     return response()->json([
    //         'success' => true,
    //         'berat'   => round((float) $berat, 3)
    //     ]);
    // }


    public function cekPerintah(Request $request)
    {
        $device = Device::where('api_key', $request->header('X-API-KEY'))
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return response()->json(['tare' => false]);
        }

        return response()->json([
            'current_id' => Cache::get("current_id_{$device->esp_id}"),
            'tare'       => Cache::pull("tare_now_{$device->esp_id}") === true
        ]);
    }


    // public function cekPerintah(Request $request)
    // {
    //     $apiKey = $request->header('X-API-KEY');
    //     $device = Device::where('api_key', $apiKey)
    //                     ->where('status', 'in_use')
    //                     ->first();

    //     if (!$device) {
    //         return response()->json(['current_id' => '', 'tare' => false]);
    //     }

    //     $espId = $device->esp_id;

    //     // Ambil current_id dari session user yang sedang pakai device ini
    //     $currentId = '';
    //     if ($device->user_id) {
    //         $userSession = \Illuminate\Support\Facades\Session::getId(); // tidak bisa langsung
    //         // Alternatif aman: simpan di database saat login
    //         $currentId = Cache::get("active_order_{$espId}", '');
    //     }

    //     // Cek apakah ada perintah tare sekali pakai
    //     $tareNow = Cache::pull("tare_now_{$espId}") === true; // pull = ambil + hapus

    //     return response()->json([
    //         'current_id' => $currentId,
    //         'tare'       => $tareNow
    //     ]);
    // }


    // User klik tombol Stabilkan
    // public function tare(Request $request)
    // {
    //     // Ambil device aktif milik user login saat ini
    //     $device = Device::where('user_id', Auth::id())
    //             ->where('status', 'in_use')
    //             ->first();

    //     if (!$device) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Timbangan tidak aktif atau belum dipilih'
    //         ], 400);
    //     }

    //     // Perintah sekali pakai: ESP32 akan langsung tare saat polling berikutnya
    //     $cacheKey = "tare_now_{$device->esp_id}";
    //     Cache::put($cacheKey, true, now()->addSeconds(10));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Perintah stabilisasi dikirim!',
    //     ]);
    // }

    // public function tare(Request $request)
    // {
    //     $device = Device::where('user_id', Auth::id())
    //                     ->where('status', 'in_use')
    //                     ->first();

    //     if (!$device) {
    //         return response()->json(['success' => false], 403);
    //     }

    //     Cache::put("tare_now_{$device->esp_id}", true, now()->addSeconds(10));

    //     return response()->json(['success' => true]);
    // }

    public function tare(Request $request)
    {
        $device = Device::where('user_id', Auth::id())
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return response()->json(['success' => false], 403);
        }

        Log::info('device : ' . $device);
        // Broadcast perintah tare ke ESP32 via Reverb
        broadcast(new TareCommand($device->esp_id));

        return response()->json(['success' => true]);
    }


    public function simpan(Request $request)
    {
        Log::info('--- Memulai Validasi Data Timbang ---');
        Log::info('Data yang diterima: ', $request->all());

        // 1. Definisikan Validator secara manual
        // Pesan kustom dalam Bahasa Indonesia yang simpel
        $messages = [
            'Order_code.required' => 'Kode Order belum diisi, tolong cek lagi ya.',
            'Buyer.required'      => 'Nama Pembeli jangan dikosongkan.',
            'berat.required'      => 'Timbangan belum stabil atau berat belum masuk.',
            'berat.numeric'       => 'Data berat harus berupa angka.',
            'no_box.required'     => 'Nomor Box harus diisi, jangan sampai tertukar.',
            'rasio_batas_beban_min.required' => 'Batas beban minimum belum ditentukan.',
            'rasio_batas_beban_max.required' => 'Batas beban maksimum belum ditentukan.',
        ];

        $validator = Validator::make($request->all(), [
            'Order_code'            => 'required|string',
            'Buyer'                 => 'required|string',
            'berat'                 => 'required|numeric|min:0.01',
            'no_box'                => 'required',
            'rasio_batas_beban_min' => 'required|numeric',
            'rasio_batas_beban_max' => 'required|numeric'
        ], $messages); // Masukkan variabel $messages di sini

        if ($validator->fails()) {
            // Ambil pesan pertama saja agar user tidak pusing baca banyak error
            $errors = $validator->errors()->all();
            $pesanSingkat = $errors[0];

            Log::warning('User salah input: ' . $pesanSingkat);

            return response()->json([
                'success' => false,
                'message' => $pesanSingkat, // Kirim satu pesan yang paling jelas
                'errors'  => $validator->errors()
            ], 422);
        }

        Log::info('Validasi Berhasil. Melanjutkan ke proses simpan...');

        $device = Device::where('user_id', Auth::id())
            ->where('status', 'in_use')
            ->first();
        Log::info('ketiga');

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak aktif atau tidak ditemukan'
            ], 400);
        }

        Log::info('keempat');
        DB::beginTransaction();
        try {

            $existingOrdersheet = Ordersheet::where('Order_code', $request->Order_code)->first();
            $existingV = VAllOrdersheetPlusCari::where('Order_code', $request->Order_code)->first();

            Log::info('kelima');
            $device = Device::where('user_id', Auth::id())
                ->where('status', 'in_use')
                ->first();
            Log::info('keenam');

            // Buat Ordersheet baru setiap timbang
            $ordersheet = Ordersheet::create([
                'id_user'           => Auth::id(),
                'id_device'         => $device?->id,
                'Order_code'        => $request->Order_code,
                'Buyer'             => $request->Buyer,
                'PO'                => $request->PO,
                'Style'             => $request->Style,
                'Qty_order'         => $request->Qty_order,
                'Carton_weight_std' => $request->Carton_weight_std,
                'Pcs_weight_std'    => $request->Pcs_weight_std,
                'PCS'               => $request->PCS,
                'Ctn'               => $request->Ctn,
                'Less_Ctn'          => $request->Less_Ctn,
                'Pcs_Less_Ctn'      => $request->Pcs_Less_Ctn,
                'Gac_date'          => $request->Gac_date,
                'Destination'       => $request->Destination,
                'Inspector'         => $request->Inspector,
                'OPT_QC_TIMBANGAN'  => $request->OPT_QC_TIMBANGAN ?? Auth::user()->username,
                'SPV_QC'            => $request->SPV_QC,
                'CHIEF_FINISH_GOOD' => $request->CHIEF_FINISH_GOOD,
                'status'            => 'Success'
            ]);

            // $ordersheet = Ordersheet::updateOrCreate(
            //     ['Order_code' => $request->Order_code],
            //     [
            //         'id_user'             => Auth::id(),
            //         'id_device'           => $device?->id,
            //         'Buyer'               => $request->Buyer ?? $existingOrdersheet?->Buyer,
            //         'PO'                  => $request->PO ?? $existingOrdersheet?->PO,
            //         'Style'               => $request->Style ?? $existingOrdersheet?->Style,
            //         'Qty_order'           => $request->Qty_order ?? $existingOrdersheet?->Qty_order,
            //         'Carton_weight_std'   => $request->Carton_weight_std ?? $existingOrdersheet?->Carton_weight_std,
            //         'Pcs_weight_std'      => $request->Pcs_weight_std ?? $existingOrdersheet?->Pcs_weight_std,
            //         'PCS'                 => $request->PCS ?? $existingOrdersheet?->PCS,
            //         'Ctn'                 => $request->Ctn ?? $existingOrdersheet?->Ctn,
            //         'Less_Ctn'            => $request->Less_Ctn ?? $existingOrdersheet?->Less_Ctn,
            //         'Pcs_Less_Ctn'        => $request->Pcs_Less_Ctn ?? $existingOrdersheet?->Pcs_Less_Ctn,
            //         'Gac_date'            => $request->Gac_date ?? $existingOrdersheet?->Gac_date,
            //         'Destination'         => $request->Destination ?? $existingOrdersheet?->Destination,
            //         'Inspector'           => $request->Inspector ?? $existingOrdersheet?->Inspector,
            //         'OPT_QC_TIMBANGAN'    => $request->OPT_QC_TIMBANGAN ?? Auth::user()->username,
            //         'SPV_QC'              => $request->SPV_QC ?? $existingOrdersheet?->SPV_QC,
            //         'CHIEF_FINISH_GOOD'   => $request->CHIEF_FINISH_GOOD ?? $existingOrdersheet?->CHIEF_FINISH_GOOD,
            //         'status'              => 'Success'
            //     ]
            // );

            $berat = floatval($request->berat);

            Timbangan_riwayat::create([
                'id_user'                    => Auth::id(),
                'id_device'                  => $device?->id,
                'id_ordersheet'              => $ordersheet->id,
                'berat'                      => $berat,
                'no_box'                     => $request->no_box,
                'rasio_batas_beban_min'      => $request->rasio_batas_beban_min,
                'rasio_batas_beban_max'      => $request->rasio_batas_beban_max,
                'status'                     => 'Success',
                'waktu_timbang'              => now(),
            ]);

            VAllOrdersheetPlusCari::updateOrCreate(
                ['Order_code' => $request->Order_code],
                [
                    'Buyer'               => $request->Buyer ?? $existingV?->Buyer,
                    'PurchaseOrderNumber' => $request->PO ?? $existingV?->PurchaseOrderNumber,
                    'ProductName'         => $request->Style ?? $existingV?->ProductName,
                    'Qty'                 => $request->Qty_order ?? $existingV?->Qty,
                    'DestinationCountry'  => $request->Destination ?? $existingV?->DestinationCountry,
                    'GAC'                 => $request->Gac_date ?? $existingV?->GAC,
                    'FinalDestination'    => $request->Destination ?? $existingV?->FinalDestination,
                    'status'              => 'Success',
                    'cari'                => ($request->Buyer ?? $existingV?->Buyer)
                        . ' ' . $request->Order_code . ' '
                        . ($request->PO ?? $existingV?->PurchaseOrderNumber),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $berat > 0
                    ? "Data berhasil disimpan dengan berat: {$berat} kg!"
                    : "Data berhasil disimpan (tanpa berat timbangan)",
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

    public function getRiwayat()
    {
        $records = Timbangan_riwayat::latest()->take(20)->get();

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }
}
