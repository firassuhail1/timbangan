<?php

namespace App\Http\Controllers\Admin\Rekap\Package;

use App\Http\Controllers\Controller;
use App\Models\OrdersheetPackage;
use Illuminate\Http\Request;

class PackController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;

        $query = OrdersheetPackage::with('weights')
            ->whereHas('weights', function ($q) {
                $q->where('status', 'Success');
            })
            ->orderBy('id', 'desc');

        // Pagination default (tanpa filter search)
        $rekap_package = $query->paginate($perPage);

        // dd($rekap_package);

        return view('admin.rekap.package.index', compact('rekap_package'));
    }
}
