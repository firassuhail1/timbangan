<x-layout.home title="Timbangan Ordersheet">

    <div class="page-heading d-flex justify-content-between align-items-center">
        @php
            $deviceType = null;

            if (Auth::check()) {
                // Ambil device aktif milik user login saat ini
                $device = \App\Models\Update\Device::where('user_id', Auth::id())->where('status', 'in_use')->first();

                if ($device) {
                    // ambil huruf pertama setelah "Timbangan-" → O atau P
                    if (preg_match('/Timbangan-([OP])\d+/', $device->esp_id, $matches)) {
                        $deviceType = $matches[1];
                    }
                }
            }
        @endphp

        @if ($deviceType === 'O')
            <h5 class="welcome-message">Sistem Timbangan Ordersheet</h5>
        @elseif ($deviceType === 'P')
            <h5 class="welcome-message">Sistem Timbangan Package</h5>
        @endif

        <div class="text-end">
            <h6 id="current-day" class="mb-0 fw-bold"></h6>
            <small id="current-time" class="text-muted"></small>
        </div>
    </div>

    <hr>

    <div class="page-content">
        <section class="row">
            <div class="card">
                <div class="card-body">
                    <div class="action-bar mb-3">

                        <!-- WIFI Setting -->
                        {{-- <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#wifi">
                            <i class="fa-solid fa-wifi me-1"></i>
                            Wifi
                        </button> --}}

                        <!-- Device Selector -->
                        <div class="dropdown">
                            <button class="btn btn-info dropdown-toggle device-btn" type="button"
                                data-bs-toggle="dropdown">
                                <i class="fa-solid fa-microchip"></i>
                                <span id="currentDeviceName">Memuat Device...</span>
                            </button>

                            <ul class="dropdown-menu device-dropdown-menu" id="deviceList">
                                <li>
                                    <span class="dropdown-item text-center disabled">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                        Memuat...
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <button type="button" id="resetSearchBtn" class="btn btn-outline-primary">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                        </button>
                    </div>

                    <!-- Modal Konfirmasi Pindah Device -->
                    <div class="modal fade" id="confirmSwitchModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content shadow-lg">
                                <div class="modal-header text-dark">
                                    <h5 class="modal-title">
                                        <i class="fa-solid fa-arrow-right-arrow-left me-2"></i> Konfirmasi Pindah Device
                                    </h5>
                                    <button type="button" class="btn-close btn-close-dark"
                                        data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Anda akan berpindah ke device:</p>
                                    <h5 class="fw-bold" id="targetDeviceName"></h5>
                                    <small class="text-muted d-block mt-1" id="targetDeviceId"></small>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fa-solid fa-xmark"></i> Batal
                                    </button>
                                    <button type="button" class="btn btn-primary" id="confirmSwitchBtn">
                                        <i class="fa-solid fa-check"></i> Ya, Pindah Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>

                    {{-- PENCARIAN --}}
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold">Cari</label>
                            <input type="text" id="search" class="form-control" placeholder="Masukkan data">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold">Tanggal Akhir</label>
                            <input type="date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-3 col-sm-6 d-grid">
                            <button type="button" id="searchBtn" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                        </div>
                    </div>

                    <!-- Loading Spinner -->
                    <div class="text-center my-3">
                        <div class="spinner-border text-primary" id="loadingSpinner" style="display:none;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Tabel Hasil -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle text-center" id="resultTable">
                            <thead class="table-info">
                                <tr>
                                    <th>No</th>
                                    <th>KJ</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>PO Number</th>
                                    <th>Buyer</th>
                                    {{-- <th>FOB</th> --}}
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-muted text-center py-4">
                                        Silakan cari data untuk memulai timbangan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <nav id="pagination" class="d-flex justify-content-center mt-3"></nav>
                </div>
            </div>

            <div class="card report">
                <div class="card-body">
                    <!-- Carton Weight Report -->
                    <div class="judul">
                        <h5 class="fw-bold text-center mb-3">Carton Weight Report - <span>Laporan Timbangan
                                Karton</span>
                        </h5>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('order.print') }}" target="_blank" class="btn btn-primary">
                                <i class="fa-solid fa-print"></i> Print Laporan
                            </a>
                        </div>
                    </div>
                    <hr>

                    <div class="cetak" id="reportContainer">
                        @if ($groupedByKJ->isEmpty())
                            <div class="alert alert-info text-center">
                                <i class="fas fa-file-alt me-2"></i> Belum Ada Data Timbangan
                            </div>
                        @else
                            {{-- ===== KONTROL ATAS ===== --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div class="small text-muted">
                                    Total KJ: <strong>{{ $groupedByKJ->count() }}</strong> |
                                    Menampilkan <strong id="kj-showing">-</strong>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <input type="text" id="kj-search" class="form-control form-control-sm"
                                        placeholder="Cari KJ / Buyer..." style="width: 180px;">
                                    <button class="btn btn-sm btn-outline-secondary" id="btn-expand-all">
                                        <i class="fas fa-expand-alt me-1"></i> Buka Semua
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="btn-collapse-all">
                                        <i class="fas fa-compress-alt me-1"></i> Tutup Semua
                                    </button>
                                </div>
                            </div>

                            {{-- ===== LIST KJ ===== --}}
                            <div id="kj-list">
                                @foreach ($groupedByKJ as $kj => $kjData)
                                    @php
                                        $kjId = 'kj-' . md5($kj);
                                        $lines = $kjData['by_line'];
                                        $lineNumbers = $lines->keys()->sort()->values();
                                    @endphp

                                    <div class="kj-group card border-0 shadow-sm mb-3"
                                        id="{{ $kjId }}-wrapper" data-kj="{{ strtolower($kj) }}"
                                        data-buyer="{{ strtolower($kjData['buyer']) }}">

                                        {{-- ===== KJ HEADER (klik untuk expand) ===== --}}
                                        <div class="card-header bg-white py-2 px-3 kj-toggle"
                                            data-target="{{ $kjId }}" style="cursor: pointer;">

                                            <div class="row align-items-center g-2">
                                                {{-- Info KJ --}}
                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <i class="fas fa-chevron-down kj-chevron text-muted"
                                                            id="{{ $kjId }}-chevron"
                                                            style="transition: transform 0.2s; font-size: 12px;"></i>
                                                        <span class="badge bg-primary px-2">{{ $kjData['kj'] }}</span>
                                                        @foreach ($lineNumbers as $ln)
                                                            <span class="badge bg-light text-dark border"
                                                                style="font-size: 11px; color: rgb(43, 43, 43) !important;">
                                                                Line {{ $ln }}
                                                            </span>
                                                        @endforeach
                                                        <span class="text-muted small">{{ $kjData['buyer'] }}</span>
                                                    </div>
                                                    <div class="small text-muted mt-1 ms-3">
                                                        {{ $kjData['date'] }} · {{ $kjData['style'] }}
                                                    </div>
                                                </div>

                                                {{-- Stat ringkas --}}
                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex gap-3 justify-content-md-end flex-wrap">
                                                        <div class="text-center">
                                                            <div class="fw-bold text-primary">
                                                                {{ $kjData['total_carton'] }}</div>
                                                            <div class="text-muted" style="font-size: 11px;">Carton
                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-success">
                                                                {{ number_format($kjData['qty_sudah']) }}</div>
                                                            <div class="text-muted" style="font-size: 11px;">Pcs
                                                                Ditimbang</div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-info">
                                                                {{ number_format($kjData['total_berat'], 2) }} kg</div>
                                                            <div class="text-muted" style="font-size: 11px;">Total
                                                                Berat</div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div
                                                                class="fw-bold {{ $kjData['qty_sisa'] == 0 ? 'text-success' : 'text-warning' }}">
                                                                {{ $kjData['qty_sisa'] == 0 ? '✓ Selesai' : number_format($kjData['qty_sisa']) . ' pcs' }}
                                                            </div>
                                                            <div class="text-muted" style="font-size: 11px;">
                                                                Sisa dari {{ number_format($kjData['qty_total']) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('order.print.orderCode', $kjData['order_code']) }}"
                                                                target="_blank" class="btn btn-outline-primary btn-sm"
                                                                onclick="event.stopPropagation()">
                                                                <i class="bi bi-printer"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== KJ BODY (collapsible) ===== --}}
                                        <div class="kj-body collapse" id="{{ $kjId }}-body">
                                            <div class="card-body p-3">

                                                {{-- Filter line dropdown --}}
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <label class="small fw-semibold mb-0">Filter Line:</label>
                                                        <select class="form-select form-select-sm line-filter"
                                                            data-kj-id="{{ $kjId }}" style="width: 130px;">
                                                            <option value="all">Semua Line</option>
                                                            @foreach ($lineNumbers as $ln)
                                                                <option value="{{ $ln }}">Line
                                                                    {{ $ln }}</option>
                                                            @endforeach
                                                        </select>

                                                        <label class="small fw-semibold mb-0 ms-2">Sort:</label>
                                                        <select class="form-select form-select-sm carton-sort-kj"
                                                            data-kj-id="{{ $kjId }}" style="width: 130px;">
                                                            <option value="asc">No. Urut ↑</option>
                                                            <option value="desc">No. Urut ↓</option>
                                                            <option value="berat-desc">Berat ↓</option>
                                                            <option value="berat-asc">Berat ↑</option>
                                                        </select>

                                                        <input type="text"
                                                            class="form-control form-control-sm carton-search-kj"
                                                            data-kj-id="{{ $kjId }}"
                                                            placeholder="Cari no. carton..." style="width: 150px;">
                                                    </div>
                                                    <div class="small text-muted">
                                                        Tampil: <strong id="{{ $kjId }}-showing">-</strong>
                                                        carton
                                                    </div>
                                                </div>

                                                {{-- Stat per line --}}
                                                <div class="row g-2 mb-3">
                                                    @foreach ($lines as $lineNo => $lineOrders)
                                                        @php
                                                            $lineTimbangans = $lineOrders->flatMap(
                                                                fn($o) => $o->timbangans,
                                                            );
                                                            $lineQty = $lineTimbangans->sum('pcs');
                                                            $lineBerat = $lineTimbangans->sum('berat');
                                                            $lineCarton = $lineTimbangans->count();
                                                        @endphp
                                                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                                            <div class="bg-light rounded p-2 text-center border">
                                                                <div class="badge bg-secondary mb-1">Line
                                                                    {{ $lineNo }}</div>
                                                                <div class="fw-bold text-primary small">
                                                                    {{ $lineCarton }} carton</div>
                                                                <div class="text-success small">
                                                                    {{ number_format($lineQty) }} pcs</div>
                                                                <div class="text-info small">
                                                                    {{ number_format($lineBerat, 2) }} kg</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                {{-- Grid carton --}}
                                                <div class="carton-grid-kj row g-2" id="{{ $kjId }}-grid">
                                                    @foreach ($lines as $lineNo => $lineOrders)
                                                        @foreach ($lineOrders as $order)
                                                            @foreach ($order->timbangans as $idx => $t)
                                                                @php
                                                                    $beratVal = floatval($t->berat);
                                                                    $pcsVal = intval($t->pcs);
                                                                    $waktu = $t->waktu_timbang
                                                                        ? \Carbon\Carbon::parse(
                                                                            $t->waktu_timbang,
                                                                        )->format('H:i')
                                                                        : '-';
                                                                    $min = floatval($t->rasio_batas_beban_min ?? 0);
                                                                    $max = floatval($t->rasio_batas_beban_max ?? 0);
                                                                    $statusColor = 'success';
                                                                    $statusLabel = 'Normal';
                                                                    if ($min > 0 && $max > 0) {
                                                                        if ($beratVal < $min) {
                                                                            $statusColor = 'danger';
                                                                            $statusLabel = 'Kurang';
                                                                        } elseif ($beratVal > $max) {
                                                                            $statusColor = 'warning';
                                                                            $statusLabel = 'Lebih';
                                                                        }
                                                                    }
                                                                @endphp
                                                                <div class="col-6 col-sm-4 col-md-3 col-lg-2 carton-card-item"
                                                                    data-kj-id="{{ $kjId }}"
                                                                    data-line="{{ $lineNo }}"
                                                                    data-no-box="{{ strtolower($t->no_box ?? '') }}"
                                                                    data-berat="{{ $beratVal }}"
                                                                    data-idx="{{ $t->id }}">

                                                                    <div class="card border-{{ $statusColor }} h-100 carton-card"
                                                                        style="border-width: 2px !important;">
                                                                        <div
                                                                            class="card-body p-2 text-center position-relative">
                                                                            <div
                                                                                class="d-flex justify-content-between mb-1">
                                                                                <span class="badge bg-info text-dark"
                                                                                    style="font-size: 9px;">
                                                                                    L{{ $lineNo }}
                                                                                </span>
                                                                                <span
                                                                                    class="badge bg-{{ $statusColor }}"
                                                                                    style="font-size: 9px;">
                                                                                    {{ $statusLabel }}
                                                                                </span>
                                                                            </div>
                                                                            <div class="fw-bold text-dark"
                                                                                style="font-size: 0.85rem;">
                                                                                {{ $t->no_box ?? '-' }}
                                                                            </div>
                                                                            <div class="text-primary fw-bold"
                                                                                style="font-size: 1.05rem;">
                                                                                {{ number_format($beratVal, 2) }}
                                                                                <span class="text-muted"
                                                                                    style="font-size: 0.7rem;">kg</span>
                                                                            </div>
                                                                            <div class="text-muted"
                                                                                style="font-size: 0.72rem;">
                                                                                {{ number_format($pcsVal) }} pcs ·
                                                                                {{ $waktu }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endforeach
                                                    @endforeach
                                                </div>

                                                {{-- Pagination carton per KJ --}}
                                                <div class="d-flex justify-content-center mt-3"
                                                    id="{{ $kjId }}-pagination"></div>

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Pagination KJ --}}
                            <div class="d-flex justify-content-center mt-4" id="kj-pagination"></div>

                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- <div class="modal fade" id="wifi" tabindex="-1" aria-labelledby="tambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-lg-down">
            <div class="modal-content overflow-hidden">
                <!-- Header -->
                <div class="modal-header text-dark">
                    <h5 class="modal-title" id="wifiLabel">
                        <i class="fa-solid fa-gear me-2"></i> Setting Wifi
                    </h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form id="wifiForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="ssidInput" class="form-label">SSID</label>
                            <input type="text" id="ssidInput" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="passInput" class="form-label">Password</label>
                            <input type="text" id="passInput" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>
                            Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-circle-xmark me-2"></i> Batal</button>
                    </div>
                </form>

                <!-- Tambahkan ini di dalam modal, sebelum </div> modal-content -->
                <div id="wifiLoading"
                    class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-90 d-none flex-column justify-content-center align-items-center"
                    style="z-index: 9999;">
                    <div class="spinner-border text-primary mb-4" style="width: 4rem; height: 4rem;"></div>
                    <h5 id="wifiLoadingText" class="text-center">Mengirim konfigurasi...</h5>
                    <div class="progress w-75 mt-4" style="height: 20px;">
                        <div id="wifiProgressBar"
                            class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                            style="width: 0%;">0%</div>
                    </div>
                    <small class="text-muted mt-2">Mohon tunggu hingga ESP terhubung kembali...</small>
                </div>
            </div>
        </div>
    </div> --}}

    <div class="modal fade" id="timbangModal" tabindex="-1" aria-labelledby="timbangModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <!-- Responsive fullscreen di HP -->
            <div class="modal-content">
                <div class="modal-header text-dark">
                    <h5 class="modal-title" id="timbangModalLabel">Detail Ordersheet & Laporan</h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-3 p-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3 p-md-4">
                            <form id="formOrdersheet" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id="info_id" name="id">
                                <input type="hidden" name="berat" id="hiddenBerat" value="0">

                                <!-- ==== BAGIAN INFORMASI ORDERSHEET ==== -->
                                <h5 class="fw-bold mb-3 text-primary">Informasi Ordersheet</h5>
                                <hr class="my-3">

                                <div class="row g-3">
                                    <!-- KOLOM KIRI -->
                                    <div class="col-12 col-lg-6">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th width="40%">BUYER</th>
                                                        <td><input type="text" id="info_buyer" name="Buyer"
                                                                class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Order No.</th>
                                                        <td><input type="text" id="info_order_code"
                                                                name="Order_code" class="form-control form-control-sm"
                                                                readonly></td>
                                                    </tr>
                                                    <tr>
                                                        <th>KJ.</th>
                                                        <td><input type="text" id="info_kj" name="KJ"
                                                                class="form-control form-control-sm" readonly></td>
                                                    </tr>
                                                    <tr>
                                                        <th>PO#</th>
                                                        <td><input type="text" id="info_purchaseordernumber"
                                                                name="PO" class="form-control form-control-sm">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Style</th>
                                                        <td><input type="text" id="info_style" name="Style"
                                                                class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Asal Line <span class="text-danger">*</span></th>
                                                        <td><input type="text" id="info_line" name="Line"
                                                                class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Qty Order</th>
                                                        <td><input type="number" id="info_qty_order"
                                                                name="Qty_order" class="form-control form-control-sm"
                                                                placeholder="0"></td>
                                                    </tr>
                                                    <tr>
                                                        <th></th>
                                                        <td>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">Pcs</span>
                                                                        <input type="number" id="info_pcs"
                                                                            name="PCS" class="form-control"
                                                                            placeholder="0">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">Ctn</span>
                                                                        <input type="number" id="info_ctn"
                                                                            name="Ctn" class="form-control"
                                                                            placeholder="0">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th></th>
                                                        {{-- <td>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">Less Ctn</span>
                                                                        <input type="number" id="info_less_ctn"
                                                                            name="Less_Ctn" class="form-control"
                                                                            placeholder="0">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">Pcs Less</span>
                                                                        <input type="number" id="info_pcs_less_ctn"
                                                                            name="Pcs_Less_Ctn" class="form-control"
                                                                            placeholder="0">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td> --}}
                                                    </tr>
                                                    <tr>
                                                        <th>Carton Weight Std.</th>
                                                        <td><input type="text" id="info_carton_weight"
                                                                name="Carton_weight_std"
                                                                class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Pcs Weight Std.</th>
                                                        <td><input type="text" id="info_pcs_weight"
                                                                name="Pcs_weight_std"
                                                                class="form-control form-control-sm"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- KOLOM KANAN -->
                                    <div class="col-12 col-lg-6">
                                        <div class="table-responsive mb-3">
                                            <table class="table table-bordered table-sm align-middle mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th width="40%">GAC Date</th>
                                                        <td><input type="date" class="form-control form-control-sm"
                                                                id="info_GAC" name="Gac_date"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Destination</th>
                                                        <td><input type="text" class="form-control form-control-sm"
                                                                id="info_FinalDestination" name="Destination"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Inspector</th>
                                                        <td><input type="text" class="form-control form-control-sm"
                                                                id="info_inspector" name="Inspector"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- TANDA TANGAN -->
                                        <hr class="d-block d-lg-none my-3">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-center align-middle"
                                                style="font-size: 13px;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="p-2">OPT QC<br><small>TIMBANGAN</small></th>
                                                        <th class="p-2">SPV QC</th>
                                                        <th class="p-2">CHIEF FINISH<br><small>GOOD</small></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr style="height: 100px;">
                                                        <td class="p-1 position-relative align-bottom">
                                                            <input type="text"
                                                                class="form-control-plaintext text-center fw-semibold user-name"
                                                                value="{{ Auth::user()->username ?? '-' }}"
                                                                name="OPT_QC_TIMBANGAN" id="OPT_QC_TIMBANGAN">
                                                        </td>
                                                        <td class="p-1 position-relative align-bottom">
                                                            <input type="text"
                                                                class="form-control-plaintext text-center fw-semibold user-name"
                                                                name="SPV_QC" id="SPV_QC" placeholder="Nama">
                                                        </td>
                                                        <td class="p-1 position-relative align-bottom">
                                                            <input type="text"
                                                                class="form-control-plaintext text-center fw-semibold user-name"
                                                                name="CHIEF_FINISH_GOOD" id="CHIEF_FINISH_GOOD"
                                                                placeholder="Nama">
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- ==== BAGIAN TIMBANGAN ==== -->
                                <hr class="my-4">
                                <h5 class="fw-bold mb-3 text-primary">Berat Barang & No. Carton</h5>
                                <hr class="mt-2 mb-3">

                                <div class="row g-3 g-md-4">
                                    <!-- KOLOM KIRI: INPUT DATA -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body p-3">
                                                <!-- No. Carton + Tombol Scan Barcode -->
                                                <div class="mb-3">
                                                    <label for="no_box"
                                                        class="form-label fw-semibold small text-muted">
                                                        No. Carton <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control form-control-sm"
                                                            name="no_box" id="no_box" placeholder="A001"
                                                            required>
                                                        <button class="btn btn-outline-warning btn-sm" type="button"
                                                            id="btnScanBarcode">
                                                            <i class="fa-solid fa-barcode"></i>
                                                            <span class="d-none d-sm-inline"> Scan</span>
                                                        </button>
                                                    </div>
                                                    <small class="text-muted">Tekan tombol scan atau ketik
                                                        manual</small>
                                                </div>

                                                <div class="row g-2">
                                                    <!-- Rasio Min -->
                                                    <div class="col-6">
                                                        <label for="rasio_batas_beban_min"
                                                            class="form-label fw-semibold small text-muted">
                                                            Batas Min <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="number" class="form-control form-control-sm"
                                                            name="rasio_batas_beban_min" id="rasio_batas_beban_min"
                                                            placeholder="0" step="0.01" required>
                                                    </div>
                                                    <!-- Rasio Max -->
                                                    <div class="col-6">
                                                        <label for="rasio_batas_beban_max"
                                                            class="form-label fw-semibold small text-muted">
                                                            Batas Max <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="number" class="form-control form-control-sm"
                                                            name="rasio_batas_beban_max" id="rasio_batas_beban_max"
                                                            placeholder="0" step="0.01" required>
                                                    </div>
                                                </div>

                                                <!-- Lost Weight -->
                                                <div class="mt-3 text-center">
                                                    <label
                                                        class="form-label fw-semibold text-success small d-block">Rasio
                                                        Lost Weight</label>
                                                    <input type="text"
                                                        class="form-control form-control-sm text-center bg-light fw-bold"
                                                        style="max-width: 180px; margin: 0 auto; font-size: 0.9rem;"
                                                        name="lost_weight" id="lost_weight" readonly
                                                        placeholder="0.00 kg">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KOLOM KANAN: DISPLAY BERAT -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border-0 shadow-sm h-80">
                                            <div class="card-body p-3 text-center">
                                                <div class="alert alert-success py-2 mb-3 small">
                                                    <strong>Timbangan</strong>
                                                </div>

                                                <!-- Berat Real-time -->
                                                <div class="p-3 bg-gradient rounded border shadow-sm"
                                                    style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                                                    <h1 id="currentWeight" class="display-5 fw-bold text-primary mb-0"
                                                        style="font-size: 3.5rem;">
                                                        0.00
                                                    </h1>
                                                    <p class="text-muted small mb-1">Kg</p>
                                                    <small id="previewStatus"
                                                        class="text-warning d-block fw-bold">Menunggu data...</small>
                                                </div>

                                                <div class="mt-2 sticky-bottom pb-2 bg-body">
                                                    <small class="text-muted">Pastikan timbangan stabil sebelum
                                                        simpan</small>
                                                    <hr>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        id="tare">
                                                        <i class="fa-solid fa-thumbtack"></i> Stabilkan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="manualMode">
                                                <label class="form-check-label fw-bold" for="manualMode">
                                                    Mode Manual (Tanpa Timbangan)
                                                </label>
                                            </div>

                                            <input type="number" step="0.01" min="0"
                                                class="form-control text-center" id="manualWeight"
                                                placeholder="Masukkan berat (Kg)" disabled>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer justify-content-center gap-2 flex-wrap">
                    <button id="btnSimpanTimbang" class="btn btn-success px-4" disabled>
                        <i class="fa-solid fa-floppy-disk"></i> Simpan
                    </button>
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="fa-solid fa-circle-xmark"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scannerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-dark">
                    <h6 class="modal-title fw-bold">
                        <i class="fa-solid fa-camera me-2"></i>Scan Barcode Carton
                    </h6>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4 bg-dark">
                    <div id="reader"
                        style="width: 100%; max-width: 400px; margin: 0 auto; border: 4px solid #0d6efd; border-radius: 12px; overflow: hidden;">
                    </div>

                    <p class="text-white mb-3 fw-semibold mt-3" id="scanStatus">Memuat kamera...</p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning btn-sm px-3 d-none" id="torchToggle">
                            <i class="fa-solid fa-lightbulb me-1"></i> Nyalakan Lampu
                        </button>
                        <button type="button" class="btn btn-info btn-sm px-3 d-none" id="switchCamera">
                            <i class="fa-solid fa-camera-rotate me-1"></i> Ganti Kamera
                        </button>
                        <button type="button" class="btn btn-danger btn-sm px-3" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .action-bar {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }

            .ctn-cell {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                line-height: 1.2;
            }

            .ctn-no {
                font-weight: 600;
            }

            .weight {
                font-size: 0.9em;
                color: #555;
            }

            /* Optional: buat tinggi sama seperti header */
            th,
            td {
                vertical-align: middle !important;
                padding: 6px;
            }

            th {
                font-weight: 600;
            }

            #currentWeight {
                font-size: 2.5rem;
                transition: color 0.3s;
            }

            .riwayat-item {
                animation: fadeIn 0.5s;
            }

            /* Chrome, Safari, Edge, Opera */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            /* Firefox */
            input[type=number] {
                -moz-appearance: textfield;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            @media (max-width: 576px) {
                #timbangModal .modal-body {
                    padding-bottom: 10px !important;
                }
            }

            .carton-item .card {
                transition: transform 0.15s ease, box-shadow 0.15s ease;
                position: relative;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12) !important;
            }

            .carton-item .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
            }

            .carton-item.d-none-filtered {
                display: none !important;
            }

            .report-group {
                animation: fadeInUp 0.3s ease;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .kj-group {
                transition: box-shadow 0.2s;
            }

            .kj-group:hover {
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
            }

            .kj-toggle:hover {
                background: #f8f9fa !important;
            }

            .carton-card {
                transition: transform 0.15s, box-shadow 0.15s;
                cursor: default;
            }

            .carton-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
            }

            .carton-card-item.kj-hidden {
                display: none !important;
            }

            .kj-group.kj-hidden {
                display: none !important;
            }
        </style>
    @endpush

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2/sweetalert2.all.min.js') }}"></script>
        {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

        <script>
            window.APP = {
                userId: {{ Auth::id() }},
                isAuth: {{ Auth::check() ? 'true' : 'false' }},
                espId: "{{ optional(\App\Models\Update\Device::where('user_id', Auth::id())->where('status', 'in_use')->first())->esp_id }}"
            }

            document.addEventListener("wheel", function(event) {
                // Cek apakah elemen yang sedang aktif (focus) adalah input type number
                if (document.activeElement.type === "number") {
                    document.activeElement.blur(); // Paksa lepas focus agar scroll halaman tetap jalan
                }
            });
        </script>

        <script src="{{ asset('auth/js/order.js') }}"></script>
        @vite(['resources/js/app.js'])
    @endpush

    {{-- ===== JS — Pagination & Filter per group ===== --}}
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const CARTON_PAGE_SIZE = 30 // tampil 30 carton per halaman

                // Init semua group
                document.querySelectorAll('.report-group').forEach(group => {
                    const groupId = group.id.replace('-wrapper', '')
                    initCartonGroup(groupId)
                })

                // Search per group
                document.querySelectorAll('.carton-search').forEach(input => {
                    input.addEventListener('input', function() {
                        const groupId = this.dataset.group
                        filterAndRender(groupId, 1)
                    })
                })

                // Sort per group
                document.querySelectorAll('.carton-sort').forEach(select => {
                    select.addEventListener('change', function() {
                        const groupId = this.dataset.group
                        filterAndRender(groupId, 1)
                    })
                })

                function initCartonGroup(groupId) {
                    filterAndRender(groupId, 1)
                }

                function getItems(groupId) {
                    return Array.from(
                        document.querySelectorAll(`.carton-item[data-group="${groupId}"]`)
                    )
                }

                function filterAndRender(groupId, page) {
                    const searchEl = document.querySelector(`.carton-search[data-group="${groupId}"]`)
                    const sortEl = document.querySelector(`.carton-sort[data-group="${groupId}"]`)
                    const showingEl = document.getElementById(`${groupId}-showing`)

                    const keyword = searchEl?.value.toLowerCase().trim() || ''
                    const sort = sortEl?.value || 'asc'

                    let items = getItems(groupId)

                    // Filter by keyword
                    const filtered = items.filter(item => {
                        if (!keyword) return true
                        return item.dataset.noBox.includes(keyword)
                    })

                    // Sort
                    filtered.sort((a, b) => {
                        if (sort === 'asc') return parseInt(a.dataset.idx) - parseInt(b.dataset.idx)
                        if (sort === 'desc') return parseInt(b.dataset.idx) - parseInt(a.dataset.idx)
                        if (sort === 'berat-desc') return parseFloat(b.dataset.berat) - parseFloat(a.dataset
                            .berat)
                        if (sort === 'berat-asc') return parseFloat(a.dataset.berat) - parseFloat(b.dataset
                            .berat)
                        return 0
                    })

                    const total = filtered.length
                    const totalPage = Math.ceil(total / CARTON_PAGE_SIZE) || 1
                    if (page > totalPage) page = totalPage

                    const start = (page - 1) * CARTON_PAGE_SIZE
                    const pageItems = filtered.slice(start, start + CARTON_PAGE_SIZE)
                    const pageSet = new Set(pageItems)

                    // Re-order DOM dan show/hide
                    const grid = document.getElementById(`${groupId}-grid`)
                    filtered.forEach(item => grid.appendChild(item)) // reorder sesuai sort

                    items.forEach(item => {
                        if (pageSet.has(item)) {
                            item.classList.remove('d-none-filtered')
                        } else {
                            item.classList.add('d-none-filtered')
                        }
                    })

                    // Update showing info
                    const from = total === 0 ? 0 : start + 1
                    const to = Math.min(start + CARTON_PAGE_SIZE, total)
                    if (showingEl) showingEl.textContent = total === 0 ? '0' : `${from}–${to}`

                    // Render pagination
                    renderCartonPagination(groupId, page, totalPage)
                }

                function renderCartonPagination(groupId, currentPage, totalPage) {
                    const el = document.getElementById(`${groupId}-pagination`)
                    if (!el) return

                    if (totalPage <= 1) {
                        el.innerHTML = ''
                        return
                    }

                    let html = `<nav><ul class="pagination pagination-sm mb-0 flex-wrap">`

                    // Prev
                    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-group="${groupId}" data-page="${currentPage - 1}">‹</a>
        </li>`

                    // Pages
                    for (let p = 1; p <= totalPage; p++) {
                        // Tampilkan semua jika <= 7, atau pakai ellipsis
                        if (
                            totalPage <= 7 ||
                            p === 1 ||
                            p === totalPage ||
                            Math.abs(p - currentPage) <= 1
                        ) {
                            html += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-group="${groupId}" data-page="${p}">${p}</a>
                </li>`
                        } else if (
                            p === currentPage - 2 ||
                            p === currentPage + 2
                        ) {
                            html += `<li class="page-item disabled"><span class="page-link">…</span></li>`
                        }
                    }

                    // Next
                    html += `<li class="page-item ${currentPage === totalPage ? 'disabled' : ''}">
            <a class="page-link" href="#" data-group="${groupId}" data-page="${currentPage + 1}">›</a>
        </li>`

                    html += `</ul></nav>`
                    el.innerHTML = html

                    // Event listener
                    el.querySelectorAll('a[data-page]').forEach(link => {
                        link.addEventListener('click', e => {
                            e.preventDefault()
                            const p = parseInt(link.dataset.page)
                            const g = link.dataset.group
                            if (p > 0 && p <= totalPage) {
                                filterAndRender(g, p)
                                // Scroll ke group ini
                                document.getElementById(`${g}-wrapper`)?.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                })
                            }
                        })
                    })
                }
            })
        </script>
    @endpush

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {

                const KJ_PAGE_SIZE = 10 // jumlah KJ per halaman
                const CARTON_PAGE_SIZE = 30 // jumlah carton per halaman per KJ

                // =========================================================
                // KJ LIST — search + paginate
                // =========================================================
                let allKjGroups = Array.from(document.querySelectorAll('.kj-group'))
                let kjCurrentPage = 1

                function filterKJ() {
                    const keyword = document.getElementById('kj-search')?.value.toLowerCase().trim() || ''
                    return allKjGroups.filter(el => {
                        if (!keyword) return true
                        return el.dataset.kj.includes(keyword) || el.dataset.buyer.includes(keyword)
                    })
                }

                function renderKJPage(page) {
                    const filtered = filterKJ()
                    const total = filtered.length
                    const totalPage = Math.ceil(total / KJ_PAGE_SIZE) || 1
                    if (page > totalPage) page = totalPage
                    kjCurrentPage = page

                    const start = (page - 1) * KJ_PAGE_SIZE
                    const visible = new Set(filtered.slice(start, start + KJ_PAGE_SIZE))

                    allKjGroups.forEach(el => {
                        el.classList.toggle('kj-hidden', !visible.has(el))
                    })

                    // Update showing
                    const showingEl = document.getElementById('kj-showing')
                    if (showingEl) {
                        const from = total === 0 ? 0 : start + 1
                        const to = Math.min(start + KJ_PAGE_SIZE, total)
                        showingEl.textContent = total === 0 ? '0' : `${from}–${to} dari ${total}`
                    }

                    renderKJPagination(page, totalPage)
                }

                function renderKJPagination(currentPage, totalPage) {
                    const el = document.getElementById('kj-pagination')
                    if (!el || totalPage <= 1) {
                        if (el) el.innerHTML = '';
                        return
                    }

                    let html = `<nav><ul class="pagination pagination-sm mb-0">`
                    html += `<li class="page-item ${currentPage===1?'disabled':''}">
            <a class="page-link kj-page-btn" href="#" data-page="${currentPage-1}">‹</a></li>`

                    for (let p = 1; p <= totalPage; p++) {
                        if (totalPage <= 7 || p === 1 || p === totalPage || Math.abs(p - currentPage) <= 1) {
                            html += `<li class="page-item ${p===currentPage?'active':''}">
                    <a class="page-link kj-page-btn" href="#" data-page="${p}">${p}</a></li>`
                        } else if (p === currentPage - 2 || p === currentPage + 2) {
                            html += `<li class="page-item disabled"><span class="page-link">…</span></li>`
                        }
                    }

                    html += `<li class="page-item ${currentPage===totalPage?'disabled':''}">
            <a class="page-link kj-page-btn" href="#" data-page="${currentPage+1}">›</a></li>`
                    html += `</ul></nav>`
                    el.innerHTML = html

                    el.querySelectorAll('.kj-page-btn').forEach(btn => {
                        btn.addEventListener('click', e => {
                            e.preventDefault()
                            const p = parseInt(btn.dataset.page)
                            if (p > 0 && p <= totalPage) renderKJPage(p)
                        })
                    })
                }

                // Search KJ
                document.getElementById('kj-search')?.addEventListener('input', () => renderKJPage(1))

                // Expand / collapse all
                document.getElementById('btn-expand-all')?.addEventListener('click', () => {
                    document.querySelectorAll('.kj-body').forEach(b => b.classList.add('show'))
                    document.querySelectorAll('.kj-chevron').forEach(c => c.style.transform = 'rotate(0deg)')
                })
                document.getElementById('btn-collapse-all')?.addEventListener('click', () => {
                    document.querySelectorAll('.kj-body').forEach(b => b.classList.remove('show'))
                    document.querySelectorAll('.kj-chevron').forEach(c => c.style.transform = 'rotate(-90deg)')
                })

                // Toggle collapse per KJ
                document.querySelectorAll('.kj-toggle').forEach(header => {
                    header.addEventListener('click', function() {
                        const targetId = this.dataset.target
                        const body = document.getElementById(`${targetId}-body`)
                        const chevron = document.getElementById(`${targetId}-chevron`)
                        if (!body) return

                        const isOpen = body.classList.toggle('show')
                        if (chevron) chevron.style.transform = isOpen ? 'rotate(0deg)' :
                            'rotate(-90deg)'

                        // Init carton pagination saat pertama dibuka
                        if (isOpen && !body.dataset.initialized) {
                            body.dataset.initialized = 'true'
                            initCartonGroup(targetId)
                        }
                    })
                })

                // =========================================================
                // CARTON GRID — filter line + search + sort + paginate
                // =========================================================
                function initCartonGroup(kjId) {
                    renderCartonGrid(kjId, 1)
                }

                function getCartonItems(kjId) {
                    return Array.from(document.querySelectorAll(`.carton-card-item[data-kj-id="${kjId}"]`))
                }

                function renderCartonGrid(kjId, page) {
                    const lineFilter = document.querySelector(`.line-filter[data-kj-id="${kjId}"]`)?.value || 'all'
                    const sortVal = document.querySelector(`.carton-sort-kj[data-kj-id="${kjId}"]`)?.value || 'asc'
                    const keyword = document.querySelector(`.carton-search-kj[data-kj-id="${kjId}"]`)?.value
                        .toLowerCase().trim() || ''

                    let items = getCartonItems(kjId)

                    // Filter line
                    let filtered = items.filter(item => {
                        if (lineFilter !== 'all' && item.dataset.line != lineFilter) return false
                        if (keyword && !item.dataset.noBox.includes(keyword)) return false
                        return true
                    })

                    // Sort
                    filtered.sort((a, b) => {
                        if (sortVal === 'asc') return parseInt(a.dataset.idx) - parseInt(b.dataset.idx)
                        if (sortVal === 'desc') return parseInt(b.dataset.idx) - parseInt(a.dataset.idx)
                        if (sortVal === 'berat-desc') return parseFloat(b.dataset.berat) - parseFloat(a.dataset
                            .berat)
                        if (sortVal === 'berat-asc') return parseFloat(a.dataset.berat) - parseFloat(b.dataset
                            .berat)
                        return 0
                    })

                    const total = filtered.length
                    const totalPage = Math.ceil(total / CARTON_PAGE_SIZE) || 1
                    if (page > totalPage) page = totalPage

                    const start = (page - 1) * CARTON_PAGE_SIZE
                    const pageSet = new Set(filtered.slice(start, start + CARTON_PAGE_SIZE))

                    // Reorder DOM
                    const grid = document.getElementById(`${kjId}-grid`)
                    filtered.forEach(item => grid.appendChild(item))

                    // Show/hide
                    items.forEach(item => item.classList.toggle('kj-hidden', !pageSet.has(item)))

                    // Update showing
                    const showEl = document.getElementById(`${kjId}-showing`)
                    if (showEl) {
                        const from = total === 0 ? 0 : start + 1
                        const to = Math.min(start + CARTON_PAGE_SIZE, total)
                        showEl.textContent = total === 0 ? '0' : `${from}–${to} dari ${total}`
                    }

                    renderCartonPagination(kjId, page, totalPage)
                }

                function renderCartonPagination(kjId, currentPage, totalPage) {
                    const el = document.getElementById(`${kjId}-pagination`)
                    if (!el) return
                    if (totalPage <= 1) {
                        el.innerHTML = '';
                        return
                    }

                    let html = `<nav><ul class="pagination pagination-sm mb-0 flex-wrap">`
                    html += `<li class="page-item ${currentPage===1?'disabled':''}">
            <a class="page-link" href="#" data-kj="${kjId}" data-page="${currentPage-1}">‹</a></li>`

                    for (let p = 1; p <= totalPage; p++) {
                        if (totalPage <= 7 || p === 1 || p === totalPage || Math.abs(p - currentPage) <= 1) {
                            html += `<li class="page-item ${p===currentPage?'active':''}">
                    <a class="page-link" href="#" data-kj="${kjId}" data-page="${p}">${p}</a></li>`
                        } else if (p === currentPage - 2 || p === currentPage + 2) {
                            html += `<li class="page-item disabled"><span class="page-link">…</span></li>`
                        }
                    }

                    html += `<li class="page-item ${currentPage===totalPage?'disabled':''}">
            <a class="page-link" href="#" data-kj="${kjId}" data-page="${currentPage+1}">›</a></li>`
                    html += `</ul></nav>`
                    el.innerHTML = html

                    el.querySelectorAll('a[data-page]').forEach(btn => {
                        btn.addEventListener('click', e => {
                            e.preventDefault()
                            const p = parseInt(btn.dataset.page)
                            const k = btn.dataset.kj
                            if (p > 0 && p <= totalPage) {
                                renderCartonGrid(k, p)
                                document.getElementById(`${k}-wrapper`)?.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                })
                            }
                        })
                    })
                }

                // Event: line filter, sort, search per KJ
                document.addEventListener('change', e => {
                    if (e.target.classList.contains('line-filter') || e.target.classList.contains(
                            'carton-sort-kj')) {
                        renderCartonGrid(e.target.dataset.kjId, 1)
                    }
                })
                document.addEventListener('input', e => {
                    if (e.target.classList.contains('carton-search-kj')) {
                        renderCartonGrid(e.target.dataset.kjId, 1)
                    }
                })

                // Initial render KJ list
                renderKJPage(1)
            })
        </script>
    @endpush

</x-layout.home>
