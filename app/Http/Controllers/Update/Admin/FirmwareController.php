<?php

namespace App\Http\Controllers\Update\Admin;

use App\Http\Controllers\Controller;
use App\Models\Update\Firmwares;
// use App\Models\User;
// use App\Notifications\FirmwarePublishedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Notification;
use App\Models\Update\Device;

class FirmwareController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->input('search');
        $entries = $request->input('entries', 10);

        $query = Firmwares::orderBy('id', 'desc');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('version', 'LIKE', "%{$search}%")
                ->orWhere('device_type', 'LIKE', "%{$search}%")
                ->orWhere('file_name', 'LIKE', "%{$search}%")
                ->orWhere('file_path', 'LIKE', "%{$search}%")
                ->orWhere('notes', 'LIKE', "%{$search}%")
                ->orWhere('status', 'LIKE', "%{$search}%");
            });
        }

        $firmware = $query->paginate($entries);
        $firmware->appends($request->query());

        // Ambil firmware published per device_type
        $publishedFirmwares = Firmwares::where('status', 'published')
            ->get()
            ->keyBy('device_type');

        // Ambil semua device beserta status versinya
        $devices = Device::orderBy('device_type')
            ->orderBy('esp_id')
            ->get()
            ->map(function ($device) use ($publishedFirmwares) {
                $published = $publishedFirmwares->get($device->device_type);

                $device->published_version = $published?->version;
                $device->is_updated = $published
                    ? $device->current_firmware_version === $published->version
                    : null;

                return $device;
            });

        return view('admin.master.view', compact(
            'firmware', 'search', 'entries', 'devices', 'publishedFirmwares'
        ));
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50|regex:/^[\d\.]+$/', // batasi hanya angka & titik
            'device_type' => 'required|in:O,P',
            'file' => [
                'required',
                'file',
                'max:20480', // 20MB
                function ($attribute, $value, $fail) {
                    $originalName = strtolower($value->getClientOriginalName());
                    if (!str_ends_with($originalName, '.bin')) {
                        $fail('File harus berakhiran .bin (contoh: sketch_name.ino.bin).');
                    }
                },
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $deviceType = $validated['device_type'];
            $version    = $validated['version'];

            // Cek duplikat version + device_type
            if (Firmwares::where('version', $version)
                        ->where('device_type', $deviceType)
                        ->exists()) {
                throw new \Exception('Versi firmware untuk tipe device ini sudah ada.');
            }

            $file = $request->file('file');

            // Buat nama file yang aman dan unik
            $fileName = sprintf(
                "%s_v%s_%s.bin",
                $deviceType,
                str_replace('.', '_', $version),
                time()
            );

            $destinationPath = public_path('firmware');

            if (!file_exists($destinationPath)) {
                if (!mkdir($destinationPath, 0755, true)) {
                    throw new \Exception('Gagal membuat folder firmware.');
                }
            }

            $absolutePath = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

            // Pindahkan file
            if (!$file->move($destinationPath, $fileName)) {
                throw new \Exception('Gagal memindahkan file firmware.');
            }

            clearstatcache();
            if (!file_exists($absolutePath) || !is_readable($absolutePath)) {
                throw new \Exception('File berhasil dipindah tapi tidak terdeteksi.');
            }

            // Hitung SHA256
            $checksum = hash_file('sha256', $absolutePath);
            if (empty($checksum) || strlen($checksum) !== 64) {
                throw new \Exception('Gagal menghitung checksum SHA256.');
            }

            // Log detail (bagus untuk audit)
            Log::info('Firmware uploaded successfully', [
                'version'     => $version,
                'device_type' => $deviceType,
                'file_name'   => $fileName,
                'path'        => 'firmware/' . $fileName,
                'absolute_path' => $absolutePath,
                'sha256'      => $checksum,
                'file_size'   => filesize($absolutePath) . ' bytes',
                'uploaded_by' => $request->user()?->id ?? 'system', // tambah info user jika ada auth
            ]);

            Firmwares::create([
                'version'     => $version,
                'device_type' => $deviceType,
                'file_name'   => $fileName,
                'file_path'   => 'firmware/' . $fileName,
                'checksum'    => $checksum,  // SHA256 lowercase 64 char
                'notes'       => $validated['notes'] ?? null,
                'status'      => $request->input('status', 'draft'),
            ]);

            DB::commit();

            return back()->with('success', "Firmware v{$version} ({$deviceType}) berhasil diupload. SHA256: {$checksum}");

        } catch (\Exception $e) {
            DB::rollBack();

            // Hapus file jika sudah terupload tapi gagal simpan DB
            if (isset($absolutePath) && file_exists($absolutePath)) {
                @unlink($absolutePath);
            }

            Log::error('Firmware upload failed', [
                'error'   => $e->getMessage(),
                'version' => $validated['version'] ?? 'unknown',
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal upload firmware: ' . $e->getMessage()]);
        }
    }

    public function download($id)
    {
        $firmware = Firmwares::findOrFail($id);

        $filePath = public_path($firmware->file_path);

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath, $firmware->file_name);
    }

    public function postToUsers(Request $request, $id)
    {
        $request->validate([
            'status' => 'required'
        ]);

        $firmware = Firmwares::findOrFail($id);

        if ($request->status === 'published') {

            // Expire firmware lama
            Firmwares::where('device_type', $firmware->device_type)
                ->where('status', 'published')
                ->where('id', '!=', $firmware->id)
                ->update([
                    'status' => 'expired',
                    'released_at' => null
                ]);

            $firmware->released_at = now();
        } else {
            $firmware->released_at = null;
        }

        $firmware->status = $request->status;
        $firmware->save();

        return back()->with('success', 'Status firmware berhasil diperbarui.');
    }

    public function delete($id)
    {
        $firmware = Firmwares::findOrFail($id);

        if ($firmware->status === 'published') {
            return redirect()->back()->with([
                'error_delete' => 'Firmware yang masih published tidak dapat dihapus.'
            ]);
        }

        $filePath = public_path($firmware->file_path);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $firmware->delete();

        return redirect()->back()->with('success', 'Firmware berhasil dihapus.');
    }
}
