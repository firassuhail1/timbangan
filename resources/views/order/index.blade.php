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
                                    <td colspan="10" class="text-muted text-center py-4">
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
                        {{-- <div class="d-flex justify-content-center">
                            <a href="{{ route('order.print') }}" target="_blank" class="btn btn-primary">
                                <i class="fa-solid fa-print"></i> Print Laporann
                            </a>
                        </div> --}}
                    </div>
                    <hr>

                    {{-- MY REPORT (per user login) --}}
                    <div class="formal-report-wrap" id="my-report-wrap" style="margin-bottom: 24px;">

                        <div class="formal-report-header">
                            <div>
                                <div class="formal-report-title">
                                    👤 Laporan Saya
                                    <small>Hanya timbangan yang Anda kerjakan sendiri</small>
                                </div>
                            </div>
                            <!-- <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button class="btn-print-formal" onclick="printMyReport()">
                                    <i class="bi bi-printer"></i> Print Laporan Saya
                                </button>
                            </div> -->
                        </div>

                        <div class="formal-filter-bar">
                            <div>
                                <label>Tanggal Mulai</label>
                                <input type="date" id="my-date-start" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label>Tanggal Akhir</label>
                                <input type="date" id="my-date-end" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div style="display:flex; gap:6px; align-items:flex-end;">
                                <button class="btn-filter" id="btn-my-filter">
                                    <i class="fas fa-search" style="font-size:10px;"></i> Tampilkan
                                </button>
                                <button class="btn-reset-filter" id="btn-my-reset">Reset</button>
                            </div>
                            <div style="margin-left:auto; font-size:11px; color:var(--muted); align-self:flex-end;">
                                Menampilkan: <strong id="my-range-label">Hari ini</strong>
                            </div>
                        </div>

                        <div class="formal-tabs">
                            <div class="formal-tab active" data-my-tab="nike">
                                <i class="fas fa-check-circle" style="font-size:11px;"></i>
                                NIKE
                                <span class="tab-badge" id="my-nike-count-badge">0</span>
                            </div>
                            <div class="formal-tab" data-my-tab="non-nike">
                                <i class="fas fa-layer-group" style="font-size:11px;"></i>
                                NON-NIKE
                                <span class="tab-badge" id="my-non-nike-count-badge">0</span>
                            </div>
                        </div>

                        <div class="formal-panel active" id="my-panel-nike">
                            <div id="my-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-search"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Klik "Tampilkan" untuk memuat laporan Anda
                                </div>
                            </div>
                        </div>

                        <div class="formal-panel" id="my-panel-non-nike">
                            <div id="my-non-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-search"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Klik "Tampilkan" untuk memuat laporan Anda
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- END MY REPORT --}}

                    {{-- REPORT FORMAL ASLI --}}
                    <!-- <div class="formal-report-wrap" id="formal-report-wrap">

                        {{-- HEADER --}}
                        <div class="formal-report-header">
                            <div>
                                <div class="formal-report-title">
                                    📋 Carton Weight Report
                                    <small>Laporan Timbangan Karton — PT. Kanindo Makmur Jaya</small>
                                </div>
                            </div>
                            {{-- <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button class="btn-print-formal" onclick="printFormalReport()">
                                    <i class="bi bi-printer"></i> Print Laporan
                                </button>
                            </div> --}}
                        </div>

                        {{-- FILTER BAR --}}
                        <div class="formal-filter-bar">
                            <div>
                                <label>Tanggal Mulai</label>
                                <input type="date" id="formal-date-start" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label>Tanggal Akhir</label>
                                <input type="date" id="formal-date-end" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div style="display:flex; gap:6px; align-items:flex-end;">
                                <button class="btn-filter" id="btn-formal-filter">
                                    <i class="fas fa-search" style="font-size:10px;"></i> Tampilkan
                                </button>
                                <button class="btn-reset-filter" id="btn-formal-reset">Reset</button>
                            </div>
                            <div style="margin-left:auto; font-size:11px; color:var(--muted); align-self:flex-end;">
                                Menampilkan: <strong id="formal-range-label">Hari ini</strong>
                            </div>
                        </div>

                        {{-- TABS --}}
                        <div class="formal-tabs">
                            <div class="formal-tab active" data-tab="nike">
                                <i class="fas fa-check-circle" style="font-size:11px;"></i>
                                NIKE
                                <span class="tab-badge" id="nike-count-badge">0</span>
                            </div>
                            <div class="formal-tab" data-tab="non-nike">
                                <i class="fas fa-layer-group" style="font-size:11px;"></i>
                                NON-NIKE
                                <span class="tab-badge" id="non-nike-count-badge">0</span>
                            </div>
                        </div>

                        {{-- NIKE PANEL --}}
                        <div class="formal-panel active" id="panel-nike">
                            <div id="nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-search"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Klik "Tampilkan" untuk memuat laporan
                                </div>
                            </div>
                        </div>

                        {{-- NON-NIKE PANEL --}}
                        <div class="formal-panel" id="panel-non-nike">
                            <div id="non-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-search"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Klik "Tampilkan" untuk memuat laporan
                                </div>
                            </div>
                        </div>

                    </div> -->

                    {{-- ═══════════════════════════════════════════════════════
     PRINT TEMPLATE (hidden, hanya saat print)
═══════════════════════════════════════════════════════ --}}
                    <div id="print-formal-area" style="display:none;"></div>
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
                    class="position-absolute top-0 inset-s-0 w-100 h-100 bg-white bg-opacity-90 d-none flex-column justify-content-center align-items-center"
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
                                                        <th>Tipe Asal <span class="text-danger">*</span></th>
                                                        <td>
                                                            <select id="tipe_asal" name="Tipe_Asal"
                                                                class="form-select form-select-sm"
                                                                onchange="toggleAsalInput(this.value)">
                                                                <option value="">-- Pilih Tipe --</option>
                                                                <option value="sewing">Sewing</option>
                                                                <option value="subcon">Subcon</option>
                                                            </select>
                                                            <input type="hidden" id="hidden_tipe_asal"
                                                                name="Tipe_Asal" value="">
                                                        </td>
                                                    </tr>
                                                    <tr id="row_line">
                                                        <th>Asal Line <span class="text-danger">*</span></th>
                                                        <td>
                                                            <input type="text" id="info_line" name="Line"
                                                                class="form-control form-control-sm"
                                                                placeholder="Masukkan nomor line">
                                                        </td>
                                                    </tr>
                                                    <tr id="row_subcon" style="display: none;">
                                                        <th>Nama Subcon <span class="text-danger">*</span></th>
                                                        <td>
                                                            <input type="text" id="info_subcon" name="Subcon"
                                                                class="form-control form-control-sm"
                                                                placeholder="Masukkan kode subcon">
                                                        </td>
                                                    </tr>
                                                    {{-- <tr>
                                                        <th>Order No.</th>
                                                        <td><input type="text" id="info_order_code"
                                                                name="Order_code" class="form-control form-control-sm"
                                                                readonly></td>
                                                    </tr> --}}
                                                    <input type="hidden" id="info_order_code" name="Order_code"
                                                        class="form-control form-control-sm" readonly>
                                                    <input type="hidden" id="info_checking_ke" name="checking_ke"
                                                        value="1">
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
                                                        <th>Color Description</th>
                                                        <td><input type="text" id="info_color_description"
                                                                name="ColorDescription"
                                                                class="form-control form-control-sm"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Destination</th>
                                                        <td><input type="text" class="form-control form-control-sm"
                                                                id="info_FinalDestination" name="Destination"></td>
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
                                                                            value="1">
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
                                                        <th width="40%">Keterangan</th>
                                                        <td>
                                                            <textarea id="info_keterangan" name="keterangan" 
                                                                class="form-control form-control-sm" 
                                                                rows="2" 
                                                                placeholder="Tulis keterangan (opsional)..."></textarea>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th width="40%">GAC Date</th>
                                                        <td><input type="date" class="form-control form-control-sm"
                                                                id="info_GAC" name="Gac_date"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Inspector</th>
                                                        <td><input type="text" class="form-control form-control-sm"
                                                                id="info_inspector" name="Inspector"
                                                                value="{{ Auth::user()->username }}"></td>
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
                                                        No. Carton
                                                        <span class="text-danger" id="no_box_required_mark">*</span>
                                                        <span class="text-muted" id="no_box_optional_mark"
                                                            style="display:none;">(opsional untuk Nike)</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control form-control-sm"
                                                            name="no_box" id="no_box" placeholder="A001">
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
        <link rel="stylesheet" href="{{ asset('auth/css/order.css') }}">
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

        <script>
            function toggleAsalInput(value) {
                const rowLine = document.getElementById('row_line');
                const rowSubcon = document.getElementById('row_subcon');
                const inputLine = document.getElementById('info_line');
                const inputSubcon = document.getElementById('info_subcon');
                const hiddenTipe = document.getElementById('hidden_tipe_asal'); // ← tambah ini

                if (value === 'sewing') {
                    rowLine.style.display = '';
                    rowSubcon.style.display = 'none';
                    inputLine.required = true;
                    inputSubcon.required = false;
                    inputSubcon.value = '';
                    hiddenTipe.value = 'sewing'; // ← set value
                } else if (value === 'subcon') {
                    rowLine.style.display = 'none';
                    rowSubcon.style.display = '';
                    inputLine.required = false;
                    inputSubcon.required = true;
                    inputLine.value = '';
                    hiddenTipe.value = 'subcon'; // ← set value
                } else {
                    rowLine.style.display = 'none';
                    rowSubcon.style.display = 'none';
                    inputLine.required = false;
                    inputSubcon.required = false;
                    hiddenTipe.value = ''; // ← kosongkan
                }
            }

            // Jalankan saat awal load jika ada value default
            document.addEventListener('DOMContentLoaded', function() {
                const tipeAsal = document.getElementById('tipe_asal');
                if (tipeAsal) toggleAsalInput(tipeAsal.value);
            });

            // Fungsi pembantu untuk memicu pengecekan
            function triggerCheck() {
                if (window.currentSelectedItem) {
                    checkAndPromptChecking(window.currentSelectedItem);
                } else {
                    console.warn("Item belum terpilih, fetch dibatalkan.");
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const tipeAsal = document.getElementById('tipe_asal');
                const inputLine = document.getElementById('info_line');
                const inputSubcon = document.getElementById('info_subcon');

                // 1. Jalankan toggle UI saat load
                if (tipeAsal) toggleAsalInput(tipeAsal.value);

                // 2. Jalankan fetch hanya JIKA detail sudah dipilih
                if (inputLine) {
                    inputLine.addEventListener('change', triggerCheck);
                }
                if (inputSubcon) {
                    inputSubcon.addEventListener('change', triggerCheck);
                }
            });
        </script>

        <script src="{{ asset('auth/js/order.js') }}"></script>
        @vite(['resources/js/app.js'])
    @endpush

    {{-- ===== JS — Pagination & Filter per group ===== --}}
    {{-- @push('js')
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
    @endpush --}}

    @php
        $logoPath = public_path('assets/images/logo/kanindo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    {{-- JS UNTUK FORMAL REPORT --}}
    @push('js')
        <script>
            const LOGO_BASE64 = @json($logoBase64);

            (function() {
                'use strict';

                // ─── INITIALIZATION ──────────────────────────────────────────
                const init = () => {
                    // ✅ Scope ke dalam #formal-report-wrap saja
                    document.querySelectorAll('#formal-report-wrap .formal-tab').forEach(tab => {
                        tab.addEventListener('click', function() {
                            document.querySelectorAll('#formal-report-wrap .formal-tab').forEach(t => t
                                .classList.remove('active'));
                            document.querySelectorAll('#formal-report-wrap .formal-panel').forEach(p =>
                                p.classList.remove('active'));
                            this.classList.add('active');
                            const targetPanel = document.getElementById('panel-' + this.dataset.tab);
                            if (targetPanel) targetPanel.classList.add('active');
                        });
                    });

                    document.getElementById('btn-formal-filter')?.addEventListener('click', loadFormalReport);
                    document.getElementById('btn-formal-reset')?.addEventListener('click', () => {
                        const today = new Date().toISOString().split('T')[0];
                        document.getElementById('formal-date-start').value = today;
                        document.getElementById('formal-date-end').value = today;
                        document.getElementById('formal-range-label').textContent = 'Hari ini';
                        loadFormalReport();
                    });

                    loadFormalReport();
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }

                // ─── CORE DATA LOADER ────────────────────────────────────────
                async function loadFormalReport() {
                    const start = document.getElementById('formal-date-start')?.value || '';
                    const end = document.getElementById('formal-date-end')?.value || '';
                    const nikeContent = document.getElementById('nike-report-content');
                    const nonNikeContent = document.getElementById('non-nike-report-content');
                    const label = document.getElementById('formal-range-label');

                    if (label) {
                        const today = new Date().toISOString().split('T')[0];
                        label.textContent = (start === today && end === today) ? 'Hari ini' : (start + ' s/d ' + end);
                    }

                    if (nikeContent) nikeContent.innerHTML = loadingHTML();
                    if (nonNikeContent) nonNikeContent.innerHTML = loadingHTML();

                    try {
                        const params = new URLSearchParams();
                        if (start) params.append('start', start);
                        if (end) params.append('end', end);

                        const res = await fetch('/user/order/formal-report?' + params, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const json = await res.json();
                        if (!json.success) throw new Error(json.message || 'Gagal memuat data dari server.');

                        renderNike(json.nike || []);
                        renderNonNike(json.non_nike || []);
                    } catch (err) {
                        const errHTML = '<div class="formal-empty" style="color:#ef5350;padding:20px;">' +
                            '<i class="fas fa-exclamation-circle" style="font-size:24px;display:block;margin-bottom:8px;"></i>' +
                            '<b>Error:</b> ' + err.message + '</div>';
                        if (nikeContent) nikeContent.innerHTML = errHTML;
                        if (nonNikeContent) nonNikeContent.innerHTML = errHTML;
                    }
                }

                function loadingHTML() {
                    return '<div class="formal-empty" style="padding:40px;">' +
                        '<div class="spinner-border text-primary" style="width:24px;height:24px;" role="status"></div>' +
                        '<div style="margin-top:8px;font-size:12px;color:#666;">Menghubungkan ke server...</div>' +
                        '</div>';
                }

                // ─── RENDER NIKE ─────────────────────────────────────────────
                function renderNike(rows) {
                    const el = document.getElementById('nike-report-content');
                    const badge = document.getElementById('nike-count-badge');
                    const COLS = 25;
                    const ROWS_PER_PAGE = 24;

                    if (!el) return;

                    if (!rows.length) {
                        if (badge) badge.textContent = '0';
                        el.innerHTML = '<div class="formal-empty">Tidak ada data NIKE pada rentang tanggal ini</div>';
                        return;
                    }

                    if (badge) badge.textContent = rows.length;

                    // ── Pisah normal vs double check ─────────────────────────────────────────
                    const rowsNormal = rows.filter(r => (parseInt(r.checking_ke) || 1) === 1);
                    const rowsDouble = rows.filter(r => (parseInt(r.checking_ke) || 1) >= 2);

                    // ── Bangun allRows dari satu kumpulan baris ───────────────────────────────
                    function buildAllRows(rowSet) {
                        const result = [];
                        rowSet.forEach(r => {
                            const chunks = chunkArray(r.timbangans, COLS);
                            const rowspan = chunks.length;
                            chunks.forEach((chunk, chunkIdx) => {
                                const padded = [...chunk, ...Array(COLS - chunk.length).fill(null)];
                                const chunkLen = chunk.length;
                                let tdBerats = '';
                                padded.forEach(t => {
                                    tdBerats += t ?
                                        `<td class="td-berat">${parseFloat(t.berat).toFixed(2)}</td>` :
                                        `<td class="td-empty">-</td>`;
                                });
                                result.push({
                                    order: r,
                                    chunkIdx,
                                    rowspan,
                                    chunkLen,
                                    tdBerats
                                });
                            });
                        });
                        return result;
                    }

                    // ── Render satu grup Nike ─────────────────────────────────────────────────
                    function renderNikeGroup(targetEl, rowSet, groupLabel, groupColor, idPrefix) {
                        if (!rowSet.length) {
                            targetEl.innerHTML += `<div class="formal-empty" style="color:#aaa;font-size:11px;margin:8px 0;">
                                <em>Tidak ada data Nike ${groupLabel} pada rentang ini</em></div>`;
                            return;
                        }

                        // ── Group by tanggal ──────────────────────────────────────────────────
                        const byDate = {};
                        rowSet.forEach(r => {
                            const tgl = r.tanggal || 'Tanpa Tanggal';
                            if (!byDate[tgl]) byDate[tgl] = [];
                            byDate[tgl].push(r);
                        });

                        const sortedDates = Object.keys(byDate).sort((a, b) => {
                            const parse = d => {
                                const [dd, mm, yyyy] = (d || '').split('-');
                                return new Date(`${yyyy}-${mm}-${dd}`);
                            };
                            return parse(a) - parse(b);
                        });

                        // Tiap tanggal → chunk per ROWS_PER_PAGE → { date, pages[] }
                        const datePages = sortedDates.map(tgl => {
                            const allRows = buildAllRows(byDate[tgl]);
                            const chunks  = chunkArray(allRows, ROWS_PER_PAGE);
                            return { date: tgl, pages: chunks };
                        });

                        // State navigasi
                        let curDateIdx  = 0;
                        let curSheetIdx = 0; // lembar dalam 1 tanggal

                        const wrapperId = `${idPrefix}-wrapper`;
                        const totalCarton = rowSet.reduce((s, r) => s + r.timbangans.length, 0);

                        // ── Build container ───────────────────────────────────────────────────
                        const groupDiv = document.createElement('div');
                        groupDiv.id = wrapperId;
                        groupDiv.style.marginBottom = '20px';
                        groupDiv.innerHTML =
                            // Header grup
                            `<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;
                                gap:10px;margin-bottom:10px;padding:8px 12px;border-radius:6px;
                                background:${groupColor}18;border-left:4px solid ${groupColor};">
                                <div style="font-size:12px;font-weight:700;color:${groupColor};">${groupLabel}</div>
                                <div id="${idPrefix}-meta" style="font-size:11px;color:#666;"></div>
                                <button class="btn-print-formal" id="${idPrefix}-print-btn">
                                    <i class="bi bi-printer"></i> Print Lembar <span id="${idPrefix}-cur-page">1</span>
                                </button>
                            </div>` +

                            // Navigasi tanggal
                            `<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;
                                margin-bottom:10px;padding:8px 10px;background:#f8f9fa;border-radius:6px;border:1px solid #dee2e6;">

                                <!-- Prev/Next tanggal -->
                                <button id="${idPrefix}-prev-date" class="rpt-page-btn" style="min-width:32px;">‹‹</button>

                                <!-- Dropdown tanggal -->
                                <select id="${idPrefix}-date-select"
                                    style="font-size:11px;padding:3px 6px;border:1px solid #ced4da;
                                        border-radius:4px;background:#fff;cursor:pointer;max-width:160px;">
                                    ${sortedDates.map((tgl, i) =>
                                        `<option value="${i}">📅 ${tgl}</option>`
                                    ).join('')}
                                </select>

                                <!-- Prev/Next lembar dalam 1 tanggal -->
                                <button id="${idPrefix}-prev-sheet" class="rpt-page-btn" style="min-width:28px;">‹</button>
                                <span id="${idPrefix}-sheet-label"
                                    style="font-size:11px;color:#555;white-space:nowrap;">Lembar 1/1</span>
                                <button id="${idPrefix}-next-sheet" class="rpt-page-btn" style="min-width:28px;">›</button>

                                <button id="${idPrefix}-next-date" class="rpt-page-btn" style="min-width:32px;">››</button>

                                <!-- Info total -->
                                <span style="margin-left:auto;font-size:10px;color:#999;">
                                    ${sortedDates.length} hari · ${totalCarton} carton total
                                </span>
                            </div>` +

                            // Konten tabel
                            `<div id="${idPrefix}-content"></div>`;

                        targetEl.appendChild(groupDiv);

                        function buildTableHTML(pageRows) {
                            let thNums = '';
                            for (let i = 1; i <= COLS; i++) {
                                thNums += `<th style="min-width:36px;font-size:10px;">${i}</th>`;
                            }

                            let tbody = '';
                            pageRows.forEach(row => {
                                const r = row.order;
                                const infoTds = row.chunkIdx === 0 ?
                                    `<td class="td-order" rowspan="${row.rowspan}" style="text-align:left;font-size:10px;word-break:break-all;max-width:100px;">${r.kj}</td>` +
                                    `<td rowspan="${row.rowspan}" style="font-size:11px;">${r.style || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}">${r.color || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}">${r.pcs || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}">${r.qty_order || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}" style="font-size:10px;">${r.gac_date || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}" style="font-size:10px;max-width:80px;">${r.destination || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}">${r.line || '-'}</td>` +
                                    `<td rowspan="${row.rowspan}">${r.carton_weight_std || '-'}</td>` :
                                    '';

                                tbody +=
                                    `<tr>${infoTds}${row.tdBerats}<td class="td-total">${row.chunkLen}</td><td></td></tr>`;
                            });

                            // Baris kosong pengisi
                            const emptyNeeded = ROWS_PER_PAGE - pageRows.length;
                            if (emptyNeeded > 0) {
                                const es = `style="border:1px solid #dee2e6;color:#ccc;font-size:10px;"`;
                                let ei = '',
                                    eb = '';
                                for (let i = 0; i < 9; i++) ei += `<td ${es}>-</td>`;
                                for (let i = 0; i < COLS; i++) eb += `<td ${es}>-</td>`;
                                for (let r = 0; r < emptyNeeded; r++) {
                                    tbody += `<tr>${ei}${eb}<td ${es}>-</td><td ${es}></td></tr>`;
                                }
                            }

                            return `<div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">` +
                                `<table class="nike-table"><thead>` +
                                `<tr>` +
                                `<th rowspan="2" style="min-width:100px;">Order No.</th>` +
                                `<th rowspan="2" style="min-width:90px;">Style</th>` +
                                `<th rowspan="2">CLR</th>` +
                                `<th rowspan="2">Isi Karton</th>` +
                                `<th rowspan="2">Qty Order</th>` +
                                `<th rowspan="2">GAC</th>` +
                                `<th rowspan="2" style="min-width:70px;">Destination</th>` +
                                `<th rowspan="2">Dari Line</th>` +
                                `<th rowspan="2">Standar Berat</th>` +
                                `<th colspan="${COLS}" style="background:#2d4fad;">Actual Berat Karton</th>` +
                                `<th rowspan="2">Total Karton</th>` +
                                `<th rowspan="2" style="min-width:50px;">Ket</th>` +
                                `</tr>` +
                                `<tr>${thNums}</tr>` +
                                `</thead><tbody>${tbody}</tbody></table></div>`;
                        }

                        function buildPagHTML(cur, total) {
                            if (total <= 1) return '';
                            let html = `<div class="rpt-pagination" style="margin-top:10px;">`;
                            html +=
                                `<button class="${idPrefix}-pag rpt-page-btn" data-page="${cur-1}" ${cur===1?'disabled':''}>‹</button>`;
                            for (let p = 1; p <= total; p++) {
                                if (total <= 7 || p === 1 || p === total || Math.abs(p - cur) <= 1) {
                                    html +=
                                        `<button class="${idPrefix}-pag rpt-page-btn${p===cur?' active':''}" data-page="${p}">${p}</button>`;
                                } else if (p === cur - 2 || p === cur + 2) {
                                    html += `<span class="rpt-page-btn" style="cursor:default;">…</span>`;
                                }
                            }
                            html +=
                                `<button class="${idPrefix}-pag rpt-page-btn" data-page="${cur+1}" ${cur===total?'disabled':''}>›</button>`;
                            html += `</div>`;
                            return html;
                        }

                        // ── Render ────────────────────────────────────────────────────────────
                        function render() {
                            const dp        = datePages[curDateIdx];
                            const pageRows  = dp.pages[curSheetIdx] || [];
                            const totalSheets = dp.pages.length;

                            // Meta info
                            const metaEl = document.getElementById(`${idPrefix}-meta`);
                            if (metaEl) metaEl.textContent =
                                `📅 ${dp.date} · Lembar ${curSheetIdx + 1}/${totalSheets} · ${byDate[dp.date].length} order`;

                            // Tombol print label
                            const curPageEl = document.getElementById(`${idPrefix}-cur-page`);
                            if (curPageEl) curPageEl.textContent = curSheetIdx + 1;

                            // Sheet label
                            const sheetLabel = document.getElementById(`${idPrefix}-sheet-label`);
                            if (sheetLabel) sheetLabel.textContent = `Lembar ${curSheetIdx + 1} / ${totalSheets}`;

                            // Disable/enable tombol
                            document.getElementById(`${idPrefix}-prev-date`).disabled  = curDateIdx === 0;
                            document.getElementById(`${idPrefix}-next-date`).disabled  = curDateIdx === datePages.length - 1;
                            document.getElementById(`${idPrefix}-prev-sheet`).disabled = curSheetIdx === 0;
                            document.getElementById(`${idPrefix}-next-sheet`).disabled = curSheetIdx === totalSheets - 1;

                            // Sync dropdown
                            const sel = document.getElementById(`${idPrefix}-date-select`);
                            if (sel) sel.value = curDateIdx;

                            // Render tabel
                            const contentEl = document.getElementById(`${idPrefix}-content`);
                            if (contentEl) contentEl.innerHTML = buildTableHTML(pageRows);
                        }

                        // ── Event listeners ───────────────────────────────────────────────────
                        groupDiv.querySelector(`#${idPrefix}-prev-date`).addEventListener('click', () => {
                            if (curDateIdx > 0) { curDateIdx--; curSheetIdx = 0; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-next-date`).addEventListener('click', () => {
                            if (curDateIdx < datePages.length - 1) { curDateIdx++; curSheetIdx = 0; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-prev-sheet`).addEventListener('click', () => {
                            if (curSheetIdx > 0) { curSheetIdx--; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-next-sheet`).addEventListener('click', () => {
                            if (curSheetIdx < datePages[curDateIdx].pages.length - 1) { curSheetIdx++; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-date-select`).addEventListener('change', function() {
                            curDateIdx  = parseInt(this.value);
                            curSheetIdx = 0;
                            render();
                        });

                        // Print button
                        groupDiv.querySelector(`#${idPrefix}-print-btn`).addEventListener('click', () => {
                            const dp   = datePages[curDateIdx];
                            printNikePage(curSheetIdx + 1, dp.pages[curSheetIdx] || [], dp.date);
                        });

                        render();
                    }

                    // ── Bersihkan container, render dua grup ─────────────────────────────────
                    el.innerHTML = '';

                    renderNikeGroup(
                        el, rowsNormal,
                        '📋 Timbangan Pertama (Checking #1)',
                        '#435ebe', 'nike-normal'
                    );

                    if (rowsDouble.length > 0) {
                        const sep = document.createElement('div');
                        sep.style.cssText = 'border-top:2px dashed #ff6b35;margin:16px 0 12px;padding-top:8px;';
                        sep.innerHTML = `<span style="background:#fff3e0;color:#ff6b35;font-size:11px;font-weight:700;
                            padding:3px 10px;border-radius:12px;border:1.5px solid #ff6b35;">
                            ⚠ Lembar Double Check — Checking #2 dst.</span>`;
                        el.appendChild(sep);

                        renderNikeGroup(
                            el, rowsDouble,
                            '🔁 Double Check (Checking #2+)',
                            '#ff6b35', 'nike-double'
                        );
                    }
                }

                // ── Print Nike ───────────────────────────────────────────────
                function printNikePage(pageNum, pageRows, dates) {

                    const ROWS_PER_PAGE = 24;
                    const COLS = 25;
                    const start = document.getElementById('formal-date-start')?.value || '';
                    const end = document.getElementById('formal-date-end')?.value || '';

                    let thNums = '';
                    for (let i = 1; i <= COLS; i++) {
                        thNums += '<th style="min-width:28px;font-size:9px;">' + i + '</th>';
                    }

                    let tbody = '';
                    pageRows.forEach(row => {
                        const r = row.order;
                        const infoTds = row.chunkIdx === 0
                            ? `<td class="td-order" rowspan="${row.rowspan}" style="text-align:left;font-size:10px;word-break:break-all;max-width:100px;">${r.kj}</td>` +
                            `<td rowspan="${row.rowspan}" style="font-size:11px;">${r.style || '-'}</td>` +
                            `<td rowspan="${row.rowspan}">${r.color || '-'}</td>` +
                            `<td rowspan="${row.rowspan}">${r.pcs || '-'}</td>` +
                            `<td rowspan="${row.rowspan}">${r.qty_order || '-'}</td>` +
                            `<td rowspan="${row.rowspan}" style="font-size:10px;">${r.gac_date || '-'}</td>` +
                            `<td rowspan="${row.rowspan}" style="font-size:10px;max-width:80px;">${r.destination || '-'}</td>` +
                            `<td rowspan="${row.rowspan}">${r.line || '-'}</td>` +
                            `<td rowspan="${row.rowspan}">${r.carton_weight_std || '-'}</td>`
                            : '';

                        const ketTd = row.chunkIdx === 0
                            ? `<td rowspan="${row.rowspan}" style="min-width:80px;vertical-align:top;padding:4px;font-size:8px;">` +
                                `${(r.keterangan || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') || '-'}` +
                            `</td>`
                            : '';

                        tbody += `<tr>${infoTds}${row.tdBerats}<td class="td-total">${row.chunkLen}</td>${ketTd}</tr>`;
                    });

                    // Baris kosong pengisi sampai 24
                    const emptyNeeded = ROWS_PER_PAGE - pageRows.length;
                    if (emptyNeeded > 0) {
                        const es = 'style="border:1px solid #ccc;"';
                        let ei = '',
                            eb = '';
                        for (let i = 0; i < 9; i++) ei += '<td ' + es + '>-</td>';
                        for (let i = 0; i < COLS; i++) eb += '<td ' + es + '>-</td>';
                        for (let r = 0; r < emptyNeeded; r++) {
                            tbody += '<tr>' + ei + eb + '<td ' + es + '>-</td><td ' + es + '></td></tr>';
                        }
                    }

                    const dateLabel = start + (start !== end ? ' s/d ' + end : '');
                    const printed = new Date().toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const hariTanggal = start ?
                        new Date(start).toLocaleDateString('id-ID', {
                            weekday: 'long',
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        }) :
                        '-';

                    // Ambil nama TTD dari row pertama
                    const firstOrder = pageRows.length > 0 ? pageRows[0].order : null;
                    const optQc = firstOrder?.opt_qc && firstOrder.opt_qc !== '-' ? firstOrder.opt_qc : '';
                    const spvQc = firstOrder?.spv_qc && firstOrder.spv_qc !== '-' ? firstOrder.spv_qc : '';
                    const chief = firstOrder?.chief && firstOrder.chief !== '-' ? firstOrder.chief : '';

                    const css =
                        '@page{size:A4 landscape;margin:6mm 8mm;}' +
                        '*{box-sizing:border-box;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}' +
                        'table{border-spacing:0;}' +
                        'body{font-family:"Segoe UI",Arial,sans-serif;font-size:8px;color:#000;margin:0;padding:0;}' +

                        /* ── Form Header (logo + judul + doc info) ── */
                        '.form-header{display:flex;align-items:stretch;border:1.5px solid #333;border-bottom:none;margin-bottom:0;}' +

                        '.logo-area{width:110px;min-width:110px;border-right:1px solid #333;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px;gap:3px;}' +
                        '.logo-box{width:42px;height:42px;border:2.5px solid #1a3a7a;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#1a3a7a;}' +
                        '.company-name{font-size:6.5px;font-weight:700;text-align:center;color:#1a3a7a;line-height:1.4;}' +

                        '.title-area{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px 8px;border-right:1px solid #333;}' +
                        '.title-area h4{margin:0;font-size:13px;font-weight:700;text-align:center;text-transform:uppercase;}' +
                        '.title-area p{margin:2px 0 0;font-size:10px;text-align:center;}' +

                        '.doc-area{width:175px;min-width:175px;}' +
                        '.doc-area table{width:100%;border-collapse:collapse;height:100%;}' +
                        '.doc-area td{border:1px solid #ddd;padding:2px 4px;vertical-align:middle;font-size:7px;}' +
                        '.doc-area td:first-child{font-weight:600;white-space:nowrap;background:#f5f5f5;width:52%;}' +

                        /* ── Baris bawah header: Hari & Tanggal + TTD ── */
                        '.sub-header{display:flex;align-items:stretch;border:1.5px solid #333;margin-bottom:4px;}' +

                        '.hari-tanggal-area{flex:1;display:flex;align-items:center;padding:4px 8px;font-size:9px;font-weight:600;border-right:1px solid #333;}' +

                        /* ── Tanda Tangan 3 Kolom ── */
                        '.sign-area-3{width:430px;min-width:430px;display:flex;align-items:stretch;}' +
                        '.sign-tbl-3{width:100%;height:100%;border-collapse:collapse;font-size:7px;text-align:center;}' +
                        '.sign-tbl-3 th{background:#f0f0f0!important;border:1px solid #999!important;padding:2px 3px;font-weight:700;font-size:6.5px;vertical-align:middle;color:#000!important;}' +
                        '.sign-tbl-3 td.sign-td{border:1px solid #999;height:50px;vertical-align:bottom;padding:2px 6px;font-weight:700;font-size:7px;min-width:130px;}' +

                        /* ── Main Table ── */
                        'table.main-tbl{width:100%;border-collapse:collapse;font-size:7.5px;}' +
                        'table.main-tbl th{background:#435ebe!important;color:#fff!important;padding:2px 3px;border:1px solid #3551b0;text-align:center;}' +
                        'table.main-tbl td{padding:2px 3px;border:1px solid #ccc!important;text-align:center;vertical-align:middle;}' +
                        '.bg-blue{background:#2d4fad!important;}' + 
                        '.ket-edit{display:none!important;}' +
                        '.ket-display{font-size:8px!important;}';

                    const html =
                        '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">' +
                        '<title>Carton Weight Report NIKE — Lembar ' + pageNum + '</title>' +
                        '<style>' + css + '</style>' +
                        '</head><body>' +

                        /* ══ BARIS 1: Logo + Judul + Info Dokumen ══ */
                        '<div class="form-header">' +

                        /* Logo */
                        '<div class="logo-area">' +
                        (LOGO_BASE64 ?
                            '<img src="' + LOGO_BASE64 +
                            '" style="width:60px;height:auto;max-height:50px;object-fit:contain;" />' :
                            '<div class="logo-box">M</div>'
                        ) +
                        '</div>' +

                        /* Judul */
                        '<div class="title-area">' +
                        '<h4>PT. Kanindo Makmur Jaya</h4>' +
                        '<p><strong>CARTON WEIGHT REPORT</strong></p>' +
                        '<p style="font-size:9px;color:#555;">Nike &nbsp;·&nbsp; Lembar ' + pageNum + '</p>' +
                        '</div>' +

                        /* Info Dokumen */
                        '<div class="doc-area">' +
                        '<table>' +
                        '<tr><td>No. Dokumen</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Tgl. Terbit</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Revisi</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Tgl. Efektif</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Departemen</td><td>&nbsp;</td></tr>' +
                        '</table>' +
                        '</div>' +

                        '</div>' + /* end form-header baris 1 */

                        /* ══ BARIS 2: Hari & Tanggal + TTD 3 Kolom ══ */
                        '<div class="sub-header">' +

                        /* Hari & Tanggal */
                        '<div class="hari-tanggal-area">' +
                        'HARI &amp; TANGGAL : ' + hariTanggal +
                        '<br><span style="font-size:7.5px;font-weight:400;color:#555;">' +
                        'Periode: ' + dateLabel +
                        ' &nbsp;·&nbsp; Dicetak: ' + printed +
                        '</span>' +
                        '</div>' +

                        /* TTD 3 Kolom */
                        '<div class="sign-area-3">' +
                        '<table class="sign-tbl-3">' +
                        '<thead>' +
                        '<tr>' +
                        '<th>OPT QC TIMBANGAN</th>' +
                        '<th>SPV QC</th>' +
                        '<th>CHIEF FINISH GOOD</th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tbody>' +
                        '<tr>' +
                        '<td class="sign-td">' + optQc + '</td>' +
                        '<td class="sign-td">' + spvQc + '</td>' +
                        '<td class="sign-td">' + chief + '</td>' +
                        '</tr>' +
                        '</tbody>' +
                        '</table>' +
                        '</div>' +

                        '</div>' + /* end sub-header baris 2 */

                        /* ══ TABEL UTAMA ══ */
                        '<table class="main-tbl">' +
                        '<thead>' +
                        '<tr>' +
                        '<th rowspan="2" style="min-width:80px;">Order No.</th>' +
                        '<th rowspan="2">Style</th>' +
                        '<th rowspan="2">CLR</th>' +
                        '<th rowspan="2">Isi<br>Karton</th>' +
                        '<th rowspan="2">Qty<br>Order</th>' +
                        '<th rowspan="2">GAC</th>' +
                        '<th rowspan="2">Destination</th>' +
                        '<th rowspan="2">Dari<br>Line</th>' +
                        '<th rowspan="2">Standar<br>Berat</th>' +
                        '<th colspan="' + COLS + '" class="bg-blue">Actual Berat Karton</th>' +
                        '<th rowspan="2">Total<br>Karton</th>' +
                        '<th rowspan="2">Ket</th>' +
                        '</tr>' +
                        '<tr>' + thNums + '</tr>' +
                        '</thead>' +
                        '<tbody>' + tbody + '</tbody>' +
                        '</table>' +

                        '<script>window.onload=()=>{window.print();}<\/script>' +
                        '</body></html>';

                    const blob = new Blob([html], { type: 'text/html' });
                    const url = URL.createObjectURL(blob);
                    const win = window.open(url, '_blank');
                }

                // ─── RENDER NON-NIKE ─────────────────────────────────────────
                function renderNonNike(orders) {
                    const el = document.getElementById('non-nike-report-content');
                    const badge = document.getElementById('non-nike-count-badge');

                    const COLS_PER_BLOCK = 10;
                    const ROWS_PER_BLOCK = 5;
                    const CARTON_PER_BLOCK = COLS_PER_BLOCK * ROWS_PER_BLOCK;
                    const BLOCKS_PER_PAGE = 4;

                    if (!el) return;

                    if (!orders.length) {
                        if (badge) badge.textContent = '0';
                        el.innerHTML = '<div class="formal-empty">Tidak ada data Non-Nike pada rentang tanggal ini</div>';
                        return;
                    }

                    if (badge) badge.textContent = orders.length;

                    // ── Bangun semua blok, lalu pisah berdasarkan checking_ke ────────────────
                    const buildAllBlocks = (orderList) => {
                        const blocks = [];
                        orderList.forEach(order => {
                            (order.by_line || []).forEach(lineGroup => {
                                const timbangans = lineGroup.timbangans || [];
                                const cartonChunks = chunkArray(timbangans, CARTON_PER_BLOCK);

                                cartonChunks.forEach((chunk, blockIdx) => {
                                blocks.push({
                                    buyer: order.buyer || '-',
                                    kj: order.kj || order.order_code || '-',
                                    order_code: order.order_code || '-',
                                    po: order.po || '-',
                                    style: order.style || '-',
                                    color: order.color || '-',
                                    qty_order: order.qty_order || 0,
                                    carton_weight_std: order.carton_weight_std,
                                    pcs_weight_std: order.pcs_weight_std,
                                    gac_date: order.gac_date || '-',
                                    destination: order.destination || '-',
                                    inspector: order.inspector || '-',
                                    opt_qc: order.opt_qc || '-',
                                    spv_qc: order.spv_qc || '-',
                                    chief: order.chief || '-',
                                    line: lineGroup.line,
                                    subcon: order.subcon || null,
                                    checking_ke: parseInt(order.checking_ke) || 1,
                                    pcs_default: order.pcs_default || '-',
                                    // ↓ TAMBAHKAN DUA BARIS INI
                                    ordersheet_id: order.ordersheet_id || '',
                                    keterangan: order.keterangan || '',
                                    // ↑ SAMPAI SINI
                                    timbangans: chunk,
                                    blockIdx,
                                    totalCartonInLine: timbangans.length,
                                    startNo: blockIdx * CARTON_PER_BLOCK + 1,
                                });
                            });
                            });
                        });
                        return blocks;
                    };

                    const allBlocks = buildAllBlocks(orders);

                    // ── Pisah: checking_ke === 1 vs checking_ke >= 2 ─────────────────────────
                    const blocksNormal = allBlocks.filter(b => b.checking_ke === 1);
                    const blocksDouble = allBlocks.filter(b => b.checking_ke >= 2);

                    // ── Badge checking_ke ─────────────────────────────────────────────────────
                    function checkingBadgeHTML(checkingKe, fontSize = '10px') {
                        const bg = checkingKe > 1 ? '#ff6b35' : '#6c757d';
                        const label = `Checking #${checkingKe}`;
                        return `<span style="background:${bg};color:#fff;font-size:${fontSize};` +
                            `font-weight:700;padding:2px 8px;border-radius:4px;margin-left:6px;">${label}</span>`;
                    }

                    // ── Build HTML 1 blok ─────────────────────────────────────────────────────
                    function buildBlockHTML(block) {
                        const isContinued = block.blockIdx > 0;
                        const startNo = block.startNo;
                        const cartons = block.timbangans;
                        let tbodyRows = '';

                        for (let row = 0; row < ROWS_PER_BLOCK; row++) {
                            const startIdx = row * COLS_PER_BLOCK;
                            const rowCartons = cartons.slice(startIdx, startIdx + COLS_PER_BLOCK);
                            const padded = [...rowCartons, ...Array(COLS_PER_BLOCK - rowCartons.length).fill(null)];
                            const rowTotalBerat = rowCartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0);
                            const hasData = rowCartons.length > 0;

                            let tdBoxes = '',
                                tdWeights = '';

                            padded.forEach((t, colIdx) => {
                                const no = startNo + startIdx + colIdx;
                                tdBoxes += t ?
                                    `<td class="td-box" style="font-weight:600;font-size:10px;">${t.no_box || no}</td>` :
                                    `<td class="td-empty" style="color:#ddd;">-</td>`;
                            });

                            padded.forEach(t => {
                                if (t) {
                                    const bv = parseFloat(t.berat);
                                    const mn = parseFloat(t.rasio_batas_beban_min || 0);
                                    const mx = parseFloat(t.rasio_batas_beban_max || 0);
                                    let cls = 'w-ok';
                                    if (mn > 0 && mx > 0) {
                                        if (bv < mn) cls = 'w-kurang';
                                        else if (bv > mx) cls = 'w-lebih';
                                    }
                                    tdWeights +=
                                        `<td class="td-w ${cls}" style="font-weight:700;font-size:11px;">${bv.toFixed(2)}</td>`;
                                } else {
                                    tdWeights += `<td class="td-empty" style="color:#ddd;">-</td>`;
                                }
                            });

                            const rowDate = rowCartons[0]?.waktu_timbang ?
                                rowCartons[0].waktu_timbang.substring(0, 10) : '-';

                            tbodyRows +=
                                `<tr>` +
                                `<td class="td-date" rowspan="2" style="font-size:10px;color:#666;vertical-align:middle;white-space:nowrap;">${rowDate}</td>` +
                                tdBoxes +
                                `<td class="td-total" rowspan="2" style="font-weight:700;color:#2dce89;vertical-align:middle;">` +
                                `${hasData ? rowTotalBerat.toFixed(2) : '-'}` +
                                `</td>` +
                                // ── Kolom Remark: hanya baris pertama (row===0) yang berisi keterangan ──
                                (row === 0
                                    ? `<td rowspan="${ROWS_PER_BLOCK * 2}" style="vertical-align:top;padding:4px;" class="ket-cell">` +
                                        `<div class="ket-display" ` +
                                            `style="font-size:10px;cursor:pointer;min-height:24px;padding:3px 4px;` +
                                                `border:1px dashed #ced4da;border-radius:3px;background:#fafafa;" ` +
                                            `title="Klik untuk edit" data-ordersheet-id="${block.ordersheet_id}">` +
                                            `${block.keterangan
                                                ? block.keterangan.replace(/</g,'&lt;')
                                                : '<em style="font-size:9px;color:#bbb;">Klik untuk tambah...</em>'}` +
                                        `</div>` +
                                        `<div class="ket-edit" style="display:none;flex-direction:column;gap:3px;margin-top:2px;">` +
                                            `<textarea class="ket-input" data-ordersheet-id="${block.ordersheet_id}" ` +
                                                `rows="3" placeholder="Tulis keterangan..." ` +
                                                `style="width:100%;font-size:10px;padding:4px;border:1px solid #ced4da;` +
                                                    `border-radius:4px;resize:vertical;box-sizing:border-box;"` +
                                            `>${(block.keterangan || '').replace(/</g,'&lt;')}</textarea>` +
                                            `<div style="display:flex;gap:3px;">` +
                                                `<button class="ket-save-btn" data-ordersheet-id="${block.ordersheet_id}" ` +
                                                    `style="font-size:9px;padding:2px 5px;background:#435ebe;color:#fff;` +
                                                        `border:none;border-radius:3px;cursor:pointer;">💾</button>` +
                                                `<button class="ket-cancel-btn" ` +
                                                    `style="font-size:9px;padding:2px 5px;background:#6c757d;color:#fff;` +
                                                        `border:none;border-radius:3px;cursor:pointer;">✕</button>` +
                                            `</div>` +
                                            `<span class="ket-status" style="font-size:9px;display:none;"></span>` +
                                        `</div>` +
                                        `</td>`
                                    : '') +
                                `</tr>` +
                                `<tr>${tdWeights}</tr>`;
                        }

                        const continuedBadge = isContinued ?
                            `<span style="background:#fff3cd;color:#856404;font-size:10px;font-weight:600;` +
                            `padding:2px 8px;border-radius:4px;margin-left:8px;">Lanjutan</span>` :
                            '';

                        const thHeaders = Array.from({
                                length: COLS_PER_BLOCK
                            }, (_, i) =>
                            `<th style="min-width:50px;">#${i + 1}</th>`
                        ).join('');

                        const totalBerat = block.timbangans
                            .reduce((s, t) => s + parseFloat(t?.berat || 0), 0).toFixed(2);

                        return (
                            `<div class="non-nike-block" style="margin-bottom:12px;">` +
                            `<div class="nn-info-wrap">` +

                            `<div class="nn-info-left">` +
                            `<table class="nn-info-table">` +
                            `<tr><th colspan="2" style="background:#435ebe;color:#fff;text-align:center;font-size:11px;padding:4px;">` +
                            `CARTON WEIGHT REPORT ` +
                            continuedBadge +
                            checkingBadgeHTML(block.checking_ke) +
                            `<button class="btn-print-block" onclick="printSingleBlock(this)" ` +
                            `style="float:right;background:#fff;color:#435ebe;border:none;border-radius:3px;` +
                            `padding:1px 6px;font-size:10px;cursor:pointer;font-weight:700;" ` +
                            `data-block='${JSON.stringify(block).replace(/'/g, "&#39;")}'>` +
                            `<i class="bi bi-printer"></i> Print Block</button>` +
                            `</th></tr>` +
                            `<tr><th>BUYER</th><td><strong>${block.buyer}</strong></td></tr>` +
                            `<tr><th>Order No. (KJ)</th><td><strong>${block.kj}</strong></td></tr>` +
                            `<tr><th>PO#</th><td>${block.po}</td></tr>` +
                            `<tr><th>Style</th><td>${block.style}</td></tr>` +
                            `<tr><th>Color</th><td>${block.color}</td></tr>` +
                            `<tr><th>Qty Order</th><td>` +
                            `${parseInt(block.qty_order || 0).toLocaleString()} pcs ` +
                            `<span style="font-size:10px;color:#666;">` +
                            `${block.subcon ? 'S = ' + block.subcon : 'L = ' + (block.line || '-')} ` +
                            `&nbsp;·&nbsp; M = ${block.pcs_default}` +
                            `</span>` +
                            `</td></tr>` +
                            `<tr><th>Carton Weight Std.</th><td>${block.carton_weight_std ? parseFloat(block.carton_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
                            `<tr><th>Pcs Weight Std.</th><td>${block.pcs_weight_std ? parseFloat(block.pcs_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
                            `</table>` +
                            `</div>` +

                            `<div class="nn-info-right">` +
                            `<table class="nn-info-table">` +
                            `<tr><th colspan="2" style="background:#435ebe;color:#fff;text-align:center;font-size:11px;padding:4px;">&nbsp;${continuedBadge}</th></tr>` +
                            `<tr><th>GAC Date</th><td>${block.gac_date}</td></tr>` +
                            `<tr><th>Destination</th><td>${block.destination}</td></tr>` +
                            `<tr><th>Inspector</th><td>${block.inspector}</td></tr>` +
                            `<tr><th>Total Carton</th><td><strong>${block.timbangans.length}</strong> / ${block.totalCartonInLine}</td></tr>` +
                            // `<tr><th>Total Berat</th><td><strong>${totalBerat} kg</strong></td></tr>` +
                            `<tr><th>Total Berat</th><td><strong>${totalBerat} kg</strong></td></tr>` +
                            `</table>` +
                            `<div class="nn-sign-wrap" style="margin-top:6px;">` +
                            `<table class="nn-sign-table">` +
                            `<thead><tr><th>OPT QC TIMBANGAN</th><th>SPV QC</th><th>CHIEF FINISH GOOD</th></tr></thead>` +
                            `<tbody><tr>` +
                            `<td style="height:45px;vertical-align:bottom;font-weight:700;">${block.opt_qc !== '-' ? block.opt_qc : ''}</td>` +
                            `<td style="height:45px;vertical-align:bottom;font-weight:700;">${block.spv_qc !== '-' ? block.spv_qc : ''}</td>` +
                            `<td style="height:45px;vertical-align:bottom;font-weight:700;">${block.chief !== '-'  ? block.chief  : ''}</td>` +
                            `</tr></tbody>` +
                            `</table>` +
                            `</div>` +
                            `</div>` +
                            `</div>` +

                            `<div class="nn-carton-wrap">` +
                            `<table class="nn-carton-table">` +
                            `<thead>` +
                            `<tr>` +
                            `<th rowspan="2" style="min-width:50px;">Date</th>` +
                            `<th colspan="${COLS_PER_BLOCK}">Ctn. No &amp; Weight (kg)</th>` +
                            `<th rowspan="2" style="min-width:50px;">Total (kg)</th>` +
                            `<th rowspan="2" style="min-width:45px;">Remark</th>` +
                            `</tr>` +
                            `<tr>${thHeaders}</tr>` +
                            `</thead>` +
                            `<tbody>${tbodyRows}</tbody>` +
                            `</table>` +
                            `</div>` +
                            `</div>`
                        );
                    }

                    // ── Render satu grup (normal ATAU double check) ───────────────────────────
                    // groupLabel  : label untuk header di atas pagination ("Timbangan Pertama" / "Double Check")
                    // groupColor  : warna aksen header (#435ebe untuk normal, #ff6b35 untuk double)
                    // idPrefix    : prefix untuk id elemen pagination agar tidak bentrok
                    function renderGroup(targetEl, blocks, groupLabel, groupColor, idPrefix) {
                        if (!blocks.length) {
                            targetEl.innerHTML += `<div class="formal-empty" style="color:#aaa;font-size:11px;margin:8px 0;">
                                <em>Tidak ada data ${groupLabel} pada rentang ini</em></div>`;
                            return;
                        }

                        // ── Group by tanggal ──────────────────────────────────────────────────
                        const byDate = {};
                        blocks.forEach(b => {
                            const tgl = b.timbangans?.[0]?.waktu_timbang?.substring(0, 10) || 'Tanpa Tanggal';
                            if (!byDate[tgl]) byDate[tgl] = [];
                            byDate[tgl].push(b);
                        });

                        const sortedDates = Object.keys(byDate).sort();

                        // Tiap tanggal → chunk per BLOCKS_PER_PAGE
                        const datePages = sortedDates.map(tgl => {
                            const chunks = chunkArray(byDate[tgl], BLOCKS_PER_PAGE);
                            return { date: tgl, pages: chunks };
                        });

                        // State
                        let curDateIdx  = 0;
                        let curSheetIdx = 0;

                        const wrapperId = `${idPrefix}-wrapper`;
                        const pagId     = `${idPrefix}-pag`;

                        // ── Build container ───────────────────────────────────────────────────
                        const groupDiv = document.createElement('div');
                        groupDiv.id = wrapperId;
                        groupDiv.style.marginBottom = '20px';
                        groupDiv.innerHTML =
                            // Header grup
                            `<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;
                                gap:10px;margin-bottom:10px;padding:8px 12px;border-radius:6px;
                                background:${groupColor}18;border-left:4px solid ${groupColor};">
                                <div style="font-size:12px;font-weight:700;color:${groupColor};">${groupLabel}</div>
                                <div id="${idPrefix}-meta" style="font-size:11px;color:#666;"></div>
                                <button class="btn-print-formal" id="${idPrefix}-print-btn">
                                    <i class="bi bi-printer"></i> Print Lembar <span id="${idPrefix}-cur-page">1</span>
                                </button>
                            </div>` +

                            // Navigasi tanggal
                            `<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;
                                margin-bottom:10px;padding:8px 10px;background:#f8f9fa;border-radius:6px;border:1px solid #dee2e6;">

                                <button id="${idPrefix}-prev-date" class="rpt-page-btn" style="min-width:32px;">‹‹</button>

                                <select id="${idPrefix}-date-select"
                                    style="font-size:11px;padding:3px 6px;border:1px solid #ced4da;
                                        border-radius:4px;background:#fff;cursor:pointer;max-width:160px;">
                                    ${sortedDates.map((tgl, i) =>
                                        `<option value="${i}">📅 ${tgl}</option>`
                                    ).join('')}
                                </select>

                                <button id="${idPrefix}-prev-sheet" class="rpt-page-btn" style="min-width:28px;">‹</button>
                                <span id="${idPrefix}-sheet-label"
                                    style="font-size:11px;color:#555;white-space:nowrap;">Lembar 1/1</span>
                                <button id="${idPrefix}-next-sheet" class="rpt-page-btn" style="min-width:28px;">›</button>

                                <button id="${idPrefix}-next-date" class="rpt-page-btn" style="min-width:32px;">››</button>

                                <span style="margin-left:auto;font-size:10px;color:#999;">
                                    ${sortedDates.length} hari · ${blocks.length} blok total
                                </span>
                            </div>` +

                            // Konten blok
                            `<div id="${idPrefix}-blocks"></div>`;

                        targetEl.appendChild(groupDiv);

                        // ── Render ────────────────────────────────────────────────────────────
                        function render() {
                            const dp          = datePages[curDateIdx];
                            const pageBlocks  = dp.pages[curSheetIdx] || [];
                            const totalSheets = dp.pages.length;

                            // Meta
                            const metaEl = document.getElementById(`${idPrefix}-meta`);
                            if (metaEl) metaEl.textContent =
                                `📅 ${dp.date} · Lembar ${curSheetIdx + 1}/${totalSheets} · ${byDate[dp.date].length} blok`;

                            const curPageEl = document.getElementById(`${idPrefix}-cur-page`);
                            if (curPageEl) curPageEl.textContent = curSheetIdx + 1;

                            const sheetLabel = document.getElementById(`${idPrefix}-sheet-label`);
                            if (sheetLabel) sheetLabel.textContent = `Lembar ${curSheetIdx + 1} / ${totalSheets}`;

                            // Disable/enable
                            document.getElementById(`${idPrefix}-prev-date`).disabled  = curDateIdx === 0;
                            document.getElementById(`${idPrefix}-next-date`).disabled  = curDateIdx === datePages.length - 1;
                            document.getElementById(`${idPrefix}-prev-sheet`).disabled = curSheetIdx === 0;
                            document.getElementById(`${idPrefix}-next-sheet`).disabled = curSheetIdx === totalSheets - 1;

                            // Sync dropdown
                            const sel = document.getElementById(`${idPrefix}-date-select`);
                            if (sel) sel.value = curDateIdx;

                            // Render blok
                            const blocksArea = document.getElementById(`${idPrefix}-blocks`);
                            if (blocksArea) blocksArea.innerHTML = pageBlocks.map(b => buildBlockHTML(b)).join('');
                        }

                        // ── Event listeners ───────────────────────────────────────────────────
                        groupDiv.querySelector(`#${idPrefix}-prev-date`).addEventListener('click', () => {
                            if (curDateIdx > 0) { curDateIdx--; curSheetIdx = 0; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-next-date`).addEventListener('click', () => {
                            if (curDateIdx < datePages.length - 1) { curDateIdx++; curSheetIdx = 0; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-prev-sheet`).addEventListener('click', () => {
                            if (curSheetIdx > 0) { curSheetIdx--; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-next-sheet`).addEventListener('click', () => {
                            if (curSheetIdx < datePages[curDateIdx].pages.length - 1) { curSheetIdx++; render(); }
                        });
                        groupDiv.querySelector(`#${idPrefix}-date-select`).addEventListener('change', function() {
                            curDateIdx  = parseInt(this.value);
                            curSheetIdx = 0;
                            render();
                        });

                        // Print
                        groupDiv.querySelector(`#${idPrefix}-print-btn`).addEventListener('click', () => {
                            printNonNikePage(curSheetIdx + 1, datePages[curDateIdx].pages[curSheetIdx] || []);
                        });

                        render();
                    }

                    // ── Bersihkan container, lalu render dua grup ─────────────────────────────
                    el.innerHTML = '';

                    renderGroup(
                        el,
                        blocksNormal,
                        '📋 Timbangan Pertama (Checking #1)',
                        '#435ebe',
                        'nn-normal'
                    );

                    if (blocksDouble.length > 0) {
                        // Garis pemisah
                        const sep = document.createElement('div');
                        sep.style.cssText = 'border-top:2px dashed #ff6b35;margin:16px 0 12px;padding-top:8px;';
                        sep.innerHTML = `<span style="background:#fff3e0;color:#ff6b35;font-size:11px;font-weight:700;
            padding:3px 10px;border-radius:12px;border:1.5px solid #ff6b35;">
            ⚠ Lembar Double Check — Checking #2 dst.</span>`;
                        el.appendChild(sep);

                        renderGroup(
                            el,
                            blocksDouble,
                            '🔁 Double Check (Checking #2+)',
                            '#ff6b35',
                            'nn-double'
                        );
                    }
                }

                // ── Print Non-Nike — 4 blok per lembar seperti form fisik ───
                function printNonNikePage(pageNum, pageBlocks) {
                    const start = document.getElementById('formal-date-start')?.value || '';
                    const end = document.getElementById('formal-date-end')?.value || '';
                    const COLS = 10;
                    const ROWS = 5;

                    let blocksHTML = '';

                    pageBlocks.forEach(block => {
                        const isContinued = block.blockIdx > 0;
                        const cartons = block.timbangans;
                        let tbodyRows = '';

                        for (let row = 0; row < ROWS; row++) {
                            const startIdx = row * COLS;
                            const rowCartons = cartons.slice(startIdx, startIdx + COLS);
                            const padded = [...rowCartons, ...Array(COLS - rowCartons.length).fill(null)];
                            const rowTotal = rowCartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0);
                            const hasData = rowCartons.length > 0;
                            const rowDate = rowCartons[0]?.waktu_timbang?.substring(0, 10) || '-';

                            let tdBoxes = '',
                                tdWeights = '';
                            padded.forEach((t, ci) => {
                                const no = block.startNo + startIdx + ci;
                                tdBoxes += t ?
                                    '<td>' + (t.no_box || no) + '</td>' :
                                    '<td style="color:#ddd;">-</td>';

                                if (t) {
                                    const bv = parseFloat(t.berat);
                                    const mn = parseFloat(t.rasio_batas_beban_min || 0);
                                    const mx = parseFloat(t.rasio_batas_beban_max || 0);
                                    let style = '';
                                    if (mn > 0 && mx > 0) {
                                        if (bv < mn) style = 'color:red;font-weight:bold;';
                                        else if (bv > mx) style = 'color:orange;font-weight:bold;';
                                    }
                                    tdWeights += '<td style="' + style + '">' + bv.toFixed(2) + '</td>';
                                } else {
                                    tdWeights += '<td style="color:#ddd;">-</td>';
                                }
                            });

                            tbodyRows += '<tr>' +
                                '<td rowspan="2" style="font-size:7px;color:#555;vertical-align:middle;white-space:nowrap;">' +
                                rowDate + '</td>' +
                                tdBoxes +
                                '<td rowspan="2" style="font-weight:700;vertical-align:middle;">' + (hasData ?
                                    rowTotal.toFixed(2) : '-') + '</td>' +
                                (row === 0
                                    ? '<td rowspan="' + (ROWS * 2) + '" style="vertical-align:top;padding:3px;font-size:7px;">' +
                                        (block.keterangan || '-') +
                                    '</td>'
                                    : '') +
                                '</tr>' +
                                '<tr>' + tdWeights + '</tr>';
                        }

                        const thCols = Array.from({
                                length: COLS
                            }, (_, i) =>
                            '<th>#' + (i + 1) + '</th>'
                        ).join('');

                        const totalBerat = block.timbangans.reduce((s, t) => s + parseFloat(t?.berat || 0), 0)
                            .toFixed(2);

                        function checkingBadgePrint(ke) {
                            if (ke <= 1) return '';
                            return '<span style="background:#ff6b35;color:#fff;padding:1px 6px;border-radius:3px;font-size:7px;">Checking #' + ke + '</span>';
                        }
            
                        // Setiap blok = 1 form seperti di foto fisik
                        blocksHTML += '<div class="block-wrap">' +
                            '<div class="block-title">CARTON WEIGHT REPORT &nbsp;—&nbsp; Laporan Timbangan Karton' +
                            (isContinued ?
                                ' <span style="background:#fff3cd;color:#856404;padding:1px 6px;border-radius:3px;font-size:8px;">Lanjutan</span>' :
                                '') +
                            checkingBadgePrint(block.checking_ke) +
                            '</div>'

                            // Info section: kiri + kanan
                            +
                            '<div class="block-info">'

                            // Kiri
                            +
                            '<div class="block-info-left">' +
                            '<table class="info-tbl">' +
                            '<tr><td>BUYER</td><td><strong>' + block.buyer + '</strong></td></tr>' +
                            '<tr><td>Order No.</td><td><strong>' + block.kj + '</strong></td></tr>' +
                            '<tr><td>PO#</td><td>' + block.po + '</td></tr>' +
                            '<tr><td>Style</td><td>' + block.style + '</td></tr>' +
                            '<tr><td>Qty Order</td><td>' + parseInt(block.qty_order || 0).toLocaleString() +
                            ' pcs</td></tr>' +
                            '<tr><td>Ctn / Less Ctn</td><td>' + block.timbangans.length + ' / -</td></tr>' +
                            '<tr><td>Carton Wgt Std.</td><td>' + (block.carton_weight_std ? parseFloat(block
                                .carton_weight_std).toFixed(2) + ' kg' : '-') + '</td></tr>' +
                            '<tr><td>Pcs Wgt Std.</td><td>' + (block.pcs_weight_std ? parseFloat(block
                                .pcs_weight_std).toFixed(2) + ' kg' : '-') + '</td></tr>' +
                            '<tr><td colspan="2" style="padding-top:2px;">' +
                            '<span style="font-size:8px;">' +
                            (block.subcon ?
                                'S = <strong>' + block.subcon + '</strong>' :
                                'L = <strong>' + (block.line || '-') + '</strong>'
                            ) +
                            ' &nbsp;&nbsp; M = <strong>' + block.pcs_default +
                            '</strong> &nbsp;&nbsp; Pcs Less Ctn = -</span>' +
                            '</td></tr>' +
                            '</table>' +
                            '</div>'

                            // Kanan
                            +
                            '<div class="block-info-right">' +
                            '<table class="info-tbl">' +
                            '<tr><td>GAC date</td><td>' + block.gac_date + '</td></tr>' +
                            '<tr><td>Destination</td><td>' + block.destination + '</td></tr>' +
                            '<tr><td>Inspector</td><td>' + block.inspector + '</td></tr>' +
                            '</table>'
                            // Tanda tangan
                            +
                            '<table class="sign-tbl">' +
                            '<tr><th>OPT QC TIMBANGAN</th><th>SPV QC</th><th>CHIEF FINISH GOOD</th></tr>' +
                            '<tr>' +
                            '<td style="height:20px;vertical-align:bottom;">' + (block.opt_qc !== '-' ? block
                                .opt_qc : '') + '</td>' +
                            '<td style="height:20px;vertical-align:bottom;">' + (block.spv_qc !== '-' ? block
                                .spv_qc : '') + '</td>' +
                            '<td style="height:20px;vertical-align:bottom;">' + (block.chief !== '-' ? block.chief :
                                '') + '</td>' +
                            '</tr>' +
                            '</table>' +
                            '</div>'

                            +
                            '</div>' // end block-info

                            // Carton table
                            +
                            '<table class="carton-tbl">' +
                            '<thead>' +
                            '<tr>' +
                            '<th rowspan="2">Date</th>' +
                            '<th colspan="' + COLS + '">Ctn. No &amp; Weight (Kg)</th>' +
                            '<th rowspan="2">Total (kg)</th>' +
                            '<th rowspan="2">Remark</th>' +
                            '</tr>' +
                            '<tr>' + thCols + '</tr>' +
                            '</thead>' +
                            '<tbody>' + tbodyRows + '</tbody>' +
                            '</table>'

                            +
                            '</div>'; // end block-wrap
                    });

                    const dateLabel = start + (start !== end ? ' s/d ' + end : '');
                    const printed = new Date().toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });

                    // CSS: 4 blok per halaman F4 landscape, mirip form fisik
                    const css = '<style>' +
                        '@page{size:210mm 330mm;margin:4mm 6mm;}' +
                        '*{box-sizing:border-box;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}' +
                        'body{font-family:"Segoe UI",Arial,sans-serif;font-size:7px;color:#000;margin:0;padding:0;}' +

                        /* ── Page grid ── */
                        '.page-grid{display:grid;grid-template-columns:1fr;grid-template-rows:repeat(4,1fr);gap:1mm;height:calc(310mm - 22mm);}' +
                        '.block-wrap{border:1px solid #999;border-radius:2px;overflow:hidden;display:flex;flex-direction:column;min-height:0;}' +
                        '.block-title{background:#435ebe!important;color:#fff!important;text-align:center;font-size:7px;font-weight:700;padding:1px 3px;}' +
                        '.block-info{display:flex;border-bottom:1px solid #ccc;}' +
                        '.block-info-left{width:55%;padding:1px 3px;border-right:1px solid #ccc;}' +
                        '.block-info-right{width:45%;padding:1px 3px;display:flex;flex-direction:column;}' +
                        '.info-tbl{width:100%;border-collapse:collapse;font-size:6.5px;}' +
                        '.info-tbl td{padding:0 2px;border:none;line-height:1.3;}' +
                        '.info-tbl td:first-child{color:#555;width:38%;font-weight:600;white-space:nowrap;}' +
                        '.info-tbl td:last-child{font-weight:600;}' +
                        '.sign-tbl{width:100%;border-collapse:collapse;font-size:6px;text-align:center;margin-top:2px;}' +
                        '.sign-tbl th{background:#f0f0f0!important;border:1px solid #999!important;padding:1px;font-weight:700;font-size:6px;}' +
                        '.sign-tbl td{border:1px solid #999!important;height:28px!important;vertical-align:bottom!important;padding:2px!important;font-weight:700;font-size:6.5px;line-height:normal!important;}' +
                        '.carton-tbl{width:100%;border-collapse:collapse;font-size:6px;flex:1;}' +
                        '.carton-tbl th{background:#435ebe!important;color:#fff!important;border:1px solid #000!important;padding:1px 0!important;text-align:center;font-weight:700;line-height:1.1;}' +
                        '.carton-tbl td{border:1px solid #ccc!important;padding:0px 0!important;text-align:center;vertical-align:middle;line-height:0;font-size:7px;}' +
                        '.carton-tbl tbody tr{height:auto!important;}' +
                        '.ket-edit{display:none!important;}' +
                        '.ket-display{display:block!important;font-size:7px!important;}' +

                        /* ── Form header (GABUNG DI SINI, jangan di style terpisah) ── */
                        '.form-header{display:flex;align-items:stretch;border:1.5px solid #333;margin-bottom:5px;}' +
                        '.logo-area{width:110px;min-width:110px;border-right:1px solid #333;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px;gap:3px;}' +
                        '.logo-box{width:42px;height:42px;border:2.5px solid #1a3a7a;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#1a3a7a;}' +
                        '.title-area{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px 8px;border-right:1px solid #333;}' +
                        '.title-area h4{margin:0;font-size:13px;font-weight:700;text-align:center;text-transform:uppercase;}' +
                        '.title-area p{margin:2px 0 0;font-size:10px;text-align:center;}' +
                        '.doc-area{width:175px;min-width:175px;}' +
                        '.doc-area table{width:100%;border-collapse:collapse;height:100%;}' +
                        '.doc-area td{border:1px solid #ddd;padding:2px 4px;vertical-align:middle;font-size:7px;}' +
                        '.doc-area td:first-child{font-weight:600;white-space:nowrap;background:#f5f5f5;width:52%;}' +
                        '</style>';

                    const hariTanggal = start ?
                        new Date(start).toLocaleDateString('id-ID', {
                            weekday: 'long',
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        }) :
                        '-';

                    const html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">' +
                        '<title>Carton Weight Report NON-NIKE — Hal. ' + pageNum + '</title>' +
                        
                        /* Tambah CSS header form (sama dengan Nike) */
                        css +

                        '</head><body>' +

                        /* ══ HEADER FORM ══ */
                        '<div class="form-header">' +

                        /* Logo */
                        '<div class="logo-area">' +
                        (typeof LOGO_BASE64 !== "undefined" && LOGO_BASE64 ?
                            '<img src="' + LOGO_BASE64 +
                            '" style="width:60px;height:auto;max-height:50px;object-fit:contain;" />' :
                            '<div class="logo-box">M</div>'
                        ) +
                        // '<div class="company-name">PT. KANINDO<br>MAKMUR JAYA</div>' +
                        '</div>' +

                        /* Judul */
                        '<div class="title-area">' +
                        '<h4>PT. Kanindo Makmur Jaya</h4>' +
                        '<p><strong>CARTON WEIGHT REPORT</strong></p>' +
                        '<p style="font-size:9px;color:#555;">Non-Nike &nbsp;·&nbsp; Lembar ' + pageNum + '</p>' +
                        '</div>' +

                        /* Info Dokumen (kosong / template) */
                        '<div class="doc-area">' +
                        '<table>' +
                        '<tr><td>No. Dokumen</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Tgl. Terbit</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Revisi</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Tgl. Efektif</td><td>&nbsp;</td></tr>' +
                        '<tr><td>Departemen</td><td>&nbsp;</td></tr>' +
                        '</table>' +
                        '</div>' +

                        // /* Tanda Tangan 3 Kolom (kosong di header, nama ada di tiap block) */
                        // '<div class="sign-area-3">' +
                        // '<table class="sign-tbl-3">' +
                        // '<thead>' +
                        // '<tr>' +
                        // '<th>OPT QC TIMBANGAN</th>' +
                        // '<th>SPV QC</th>' +
                        // '<th>CHIEF FINISH GOOD</th>' +
                        // '</tr>' +
                        // '</thead>' +
                        // '<tbody>' +
                        // '<tr>' +
                        // '<td class="sign-td"></td>' +
                        // '<td class="sign-td"></td>' +
                        // '<td class="sign-td"></td>' +
                        // '</tr>' +
                        // '</tbody>' +
                        // '</table>' +
                        // '</div>' +

                        '</div>' + /* end form-header */

                        // /* ══ HARI & TANGGAL ══ */
                        // '<div class="hari-tanggal">' +
                        // 'HARI &amp; TANGGAL : ' + hariTanggal +
                        // ' &nbsp;&nbsp;|&nbsp;&nbsp; Periode: ' + dateLabel +
                        // ' &nbsp;&nbsp;|&nbsp;&nbsp; Dicetak: ' + printed +
                        // '</div>' +

                        /* ══ GRID BLOK ══ */
                        '<div class="page-grid">' +
                        blocksHTML +
                        '</div>' +

                        '<script>window.onload=()=>{window.print();}<\/script>' +
                        '</body></html>';

                    const blob = new Blob([html], { type: 'text/html' });
                    const url = URL.createObjectURL(blob);
                    const win = window.open(url, '_blank');
                }

                // ── Print Laporan (button paling atas) ───────────────────────
                window.printFormalReport = function() {
                    const activeTab = document.querySelector('#formal-report-wrap .formal-tab.active')?.dataset.tab ||
                        'nike';
                    const start = document.getElementById('formal-date-start')?.value || '';
                    const end = document.getElementById('formal-date-end')?.value || '';
                    const label = activeTab.toUpperCase();
                    const content = document.getElementById(activeTab + '-report-content')?.innerHTML || '';
                    const printed = new Date().toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
                    const dateLabel = start + (start !== end ? ' s/d ' + end : '');

                    // Ambil semua CSS dari halaman yang sudah ada
                    let pageCSS = '';
                    Array.from(document.styleSheets).forEach(sheet => {
                        try {
                            Array.from(sheet.cssRules || []).forEach(rule => {
                                pageCSS += rule.cssText + '\n';
                            });
                        } catch (e) {}
                    });

                    const extraCSS = '<style>' +
                        pageCSS +
                        '@page{size:A4 landscape;margin:10mm;}' +
                        'body{font-family:"Segoe UI",Arial,sans-serif;font-size:9px;color:#000;margin:0;}' +
                        '.btn-print-formal,.rpt-pagination,.nn-page-btn,.nike-page-btn{display:none!important;}' +
                        '*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}' +
                        '</style>';

                    const html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">' +
                        '<title>Report ' + label + ' — ' + dateLabel + '</title>' +
                        extraCSS +
                        '</head><body>' +
                        '<div class="print-header" style="text-align:center;border-bottom:2px solid #000;margin-bottom:15px;padding-bottom:5px;">' +
                        '<h3 style="margin:0;">PT. KANINDO MAKMUR JAYA</h3>' +
                        '<p style="margin:0;">CARTON WEIGHT REPORT — ' + label + ' (' + dateLabel + ')</p>' +
                        '<p style="margin:0;font-size:9px;color:#555;">Dicetak: ' + printed + '</p>' +
                        '</div>' +
                        content +
                        '<script>window.onload=()=>{window.print();window.close();}<\/script>' +
                        '</body></html>';

                    const win = window.open('', '_blank');
                    win.document.write(html);
                    win.document.close();
                };

                // UNTUK MY REPORT PER USER
                // ─── MY REPORT INIT ──────────────────────────────────────────
                const initMyReport = () => {
                    // Tab switching khusus my-report
                    document.querySelectorAll('[data-my-tab]').forEach(tab => {
                        tab.addEventListener('click', function() {
                            document.querySelectorAll('[data-my-tab]').forEach(t => t.classList.remove(
                                'active'));
                            document.querySelectorAll('#my-report-wrap .formal-panel').forEach(p => p
                                .classList.remove('active'));
                            this.classList.add('active');
                            const targetPanel = document.getElementById('my-panel-' + this.dataset
                                .myTab);
                            if (targetPanel) targetPanel.classList.add('active');
                        });
                    });

                    document.getElementById('btn-my-filter')?.addEventListener('click', loadMyReport);
                    document.getElementById('btn-my-reset')?.addEventListener('click', () => {
                        const today = new Date().toISOString().split('T')[0];
                        document.getElementById('my-date-start').value = today;
                        document.getElementById('my-date-end').value = today;
                        document.getElementById('my-range-label').textContent = 'Hari ini';
                        loadMyReport();
                    });

                    loadMyReport();
                };

                async function loadMyReport() {
                    const start = document.getElementById('my-date-start')?.value || '';
                    const end = document.getElementById('my-date-end')?.value || '';
                    const nikeEl = document.getElementById('my-nike-report-content');
                    const nonNikeEl = document.getElementById('my-non-nike-report-content');
                    const label = document.getElementById('my-range-label');

                    if (label) {
                        const today = new Date().toISOString().split('T')[0];
                        label.textContent = (start === today && end === today) ? 'Hari ini' : (start + ' s/d ' + end);
                    }
                    if (nikeEl) nikeEl.innerHTML = loadingHTML();
                    if (nonNikeEl) nonNikeEl.innerHTML = loadingHTML();

                    try {
                        const params = new URLSearchParams();
                        if (start) params.append('start', start);
                        if (end) params.append('end', end);

                        const res = await fetch('/user/order/my-report?' + params, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const json = await res.json();
                        if (!json.success) throw new Error(json.message || 'Gagal memuat data.');

                        renderMyNike(json.nike || []);
                        renderMyNonNike(json.non_nike || []);
                    } catch (err) {
                        const errHTML = '<div class="formal-empty" style="color:#ef5350;padding:20px;">' +
                            '<i class="fas fa-exclamation-circle" style="font-size:24px;display:block;margin-bottom:8px;"></i>' +
                            '<b>Error:</b> ' + err.message + '</div>';
                        if (nikeEl) nikeEl.innerHTML = errHTML;
                        if (nonNikeEl) nonNikeEl.innerHTML = errHTML;
                    }
                }

                // Reuse fungsi renderNike & renderNonNike yang sudah ada,
                // tapi arahkan ke elemen "my-"
                function renderMyNike(rows) {
                    // Sementara tukar target elemennya, lalu panggil ulang logic render
                    const origNike = document.getElementById('nike-report-content');
                    const origBadge = document.getElementById('nike-count-badge');
                    const myNike = document.getElementById('my-nike-report-content');
                    const myBadge = document.getElementById('my-nike-count-badge');

                    // Swap
                    myNike.id = 'nike-report-content';
                    myBadge.id = 'nike-count-badge';
                    if (origNike) origNike.id = '__orig_nike';
                    if (origBadge) origBadge.id = '__orig_badge';

                    renderNike(rows); // fungsi yg sudah ada

                    // Swap balik
                    myNike.id = 'my-nike-report-content';
                    myBadge.id = 'my-nike-count-badge';
                    if (origNike) origNike.id = 'nike-report-content';
                    if (origBadge) origBadge.id = 'nike-count-badge';
                }

                function renderMyNonNike(orders) {
                    const origEl = document.getElementById('non-nike-report-content');
                    const origBadge = document.getElementById('non-nike-count-badge');
                    const myEl = document.getElementById('my-non-nike-report-content');
                    const myBadge = document.getElementById('my-non-nike-count-badge');

                    myEl.id = 'non-nike-report-content';
                    myBadge.id = 'non-nike-count-badge';
                    if (origEl) origEl.id = '__orig_nn';
                    if (origBadge) origBadge.id = '__orig_nn_badge';

                    renderNonNike(orders); // fungsi yg sudah ada

                    myEl.id = 'my-non-nike-report-content';
                    myBadge.id = 'my-non-nike-count-badge';
                    if (origEl) origEl.id = 'non-nike-report-content';
                    if (origBadge) origBadge.id = 'non-nike-count-badge';
                }

                window.printMyReport = function() {
                    const activeTab = document.querySelector('#my-report-wrap [data-my-tab].active')?.dataset.myTab ||
                        'nike';
                    const start = document.getElementById('my-date-start')?.value || '';
                    const end = document.getElementById('my-date-end')?.value || '';
                    const label = activeTab.toUpperCase();
                    const content = document.getElementById('my-' + activeTab.replace('-', '') + '-report-content')
                        ?.innerHTML ||
                        document.getElementById('my-' + activeTab + '-report-content')?.innerHTML || '';
                    const printed = new Date().toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
                    const dateLabel = start + (start !== end ? ' s/d ' + end : '');

                    let pageCSS = '';
                    Array.from(document.styleSheets).forEach(sheet => {
                        try {
                            Array.from(sheet.cssRules || []).forEach(r => {
                                pageCSS += r.cssText + '\n';
                            });
                        } catch (e) {}
                    });

                    const html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">' +
                        '<title>Laporan Saya — ' + label + ' — ' + dateLabel + '</title>' +
                        '<style>' + pageCSS +
                        '@page{size:A4 landscape;margin:10mm;}' +
                        'body{font-family:"Segoe UI",Arial,sans-serif;font-size:9px;color:#000;margin:0;}' +
                        '.btn-print-formal,.rpt-pagination,.nn-page-btn,.nike-page-btn{display:none!important;}' +
                        '*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}' +
                        '</style></head><body>' +
                        '<div style="text-align:center;border-bottom:2px solid #000;margin-bottom:15px;padding-bottom:5px;">' +
                        '<h3 style="margin:0;">PT. KANINDO MAKMUR JAYA</h3>' +
                        '<p style="margin:0;">LAPORAN SAYA — ' + label + ' (' + dateLabel + ')</p>' +
                        '<p style="margin:0;font-size:9px;color:#555;">Dicetak: ' + printed + '</p>' +
                        '</div>' + content +
                        '<script>window.onload=()=>{window.print();window.close();}<\/script>' +
                        '</body></html>';

                    const win = window.open('', '_blank');
                    win.document.write(html);
                    win.document.close();
                };

                // Panggil initMyReport setelah DOM ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMyReport);
                } else {
                    initMyReport();
                }
                // END MY REPORT PER USER

                // ── Print Single Block Non-Nike ──────────────────────────────
                window.printSingleBlock = function(btn) {
                    const block = JSON.parse(btn.dataset.block);
                    const COLS = 10;
                    const ROWS = 5;

                    const isContinued = block.blockIdx > 0;
                    const cartons = block.timbangans;
                    let tbodyRows = '';

                    for (let row = 0; row < ROWS; row++) {
                        const startIdx = row * COLS;
                        const rowCartons = cartons.slice(startIdx, startIdx + COLS);
                        const padded = [...rowCartons, ...Array(COLS - rowCartons.length).fill(null)];
                        const rowTotal = rowCartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0);
                        const hasData = rowCartons.length > 0;
                        const rowDate = rowCartons[0]?.waktu_timbang?.substring(0, 10) || '-';

                        let tdBoxes = '',
                            tdWeights = '';
                        padded.forEach((t, ci) => {
                            const no = block.startNo + startIdx + ci;
                            tdBoxes += t ?
                                '<td>' + (t.no_box || no) + '</td>' :
                                '<td style="color:#ddd;">-</td>';

                            if (t) {
                                const bv = parseFloat(t.berat);
                                const mn = parseFloat(t.rasio_batas_beban_min || 0);
                                const mx = parseFloat(t.rasio_batas_beban_max || 0);
                                let style = '';
                                if (mn > 0 && mx > 0) {
                                    if (bv < mn) style = 'color:red;font-weight:bold;';
                                    else if (bv > mx) style = 'color:orange;font-weight:bold;';
                                }
                                tdWeights += '<td style="' + style + '">' + bv.toFixed(2) + '</td>';
                            } else {
                                tdWeights += '<td style="color:#ddd;">-</td>';
                            }
                        });

                        tbodyRows += '<tr>' +
                            '<td rowspan="2" style="font-size:7px;color:#555;vertical-align:middle;white-space:nowrap;">' +
                            rowDate + '</td>' +
                            tdBoxes +
                            '<td rowspan="2" style="font-weight:700;vertical-align:middle;">' + (hasData ? rowTotal
                                .toFixed(2) : '-') + '</td>' +
                            + (row === 0
                                ? `<td rowspan="${ROWS * 2}" style="vertical-align:top;padding:3px;font-size:7px;">` +
                                    `${block.keterangan || '-'}` +
                                `</td>`
                                : '')
                            '</tr>' +
                            '<tr>' + tdWeights + '</tr>';
                    }

                    const thCols = Array.from({
                        length: COLS
                    }, (_, i) => '<th>#' + (i + 1) + '</th>').join('');
                    const totalBerat = cartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0).toFixed(2);
                    const printed = new Date().toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });

                    const css = `
                        @page { size: 210mm 140mm; margin: 4mm 6mm; }
                        * { box-sizing:border-box; -webkit-print-color-adjust:exact!important; print-color-adjust:exact!important; }
                        body { font-family:"Segoe UI",Arial,sans-serif; font-size:7px; color:#000; margin:0; padding:0; }

                        .form-header { display:flex; align-items:stretch; border:1.5px solid #333; margin-bottom:3px; }
                        .logo-area { width:90px; min-width:90px; border-right:1px solid #333; display:flex; align-items:center; justify-content:center; padding:3px; }
                        .title-area { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3px 6px; border-right:1px solid #333; }
                        .title-area h4 { margin:0; font-size:10px; font-weight:700; text-align:center; text-transform:uppercase; }
                        .title-area p  { margin:1px 0 0; font-size:8px; text-align:center; }
                        .doc-area { width:150px; min-width:150px; }
                        .doc-area table { width:100%; border-collapse:collapse; height:100%; }
                        .doc-area td { border:1px solid #ddd; padding:1px 3px; vertical-align:middle; font-size:6.5px; }
                        .doc-area td:first-child { font-weight:600; white-space:nowrap; background:#f5f5f5; width:52%; }

                        .block-wrap  { border:1px solid #999; border-radius:2px; overflow:hidden; }
                        .block-title { background:#435ebe!important; color:#fff!important; text-align:center; font-size:8px; font-weight:700; padding:2px 4px; }
                        .block-info  { display:flex; border-bottom:1px solid #ccc; }
                        .block-info-left  { width:55%; padding:2px 4px; border-right:1px solid #ccc; }
                        .block-info-right { width:45%; padding:2px 4px; display:flex; flex-direction:column; }

                        .info-tbl { width:100%; border-collapse:collapse; font-size:7px; }
                        .info-tbl td { padding:0 2px; border:none; line-height:1.4; }
                        .info-tbl td:first-child { color:#555; width:38%; font-weight:600; white-space:nowrap; }
                        .info-tbl td:last-child  { font-weight:600; }

                        .sign-tbl { width:100%; border-collapse:collapse; font-size:7px; text-align:center; margin-top:3px; }
                        .sign-tbl th { background:#f0f0f0!important; border:1px solid #999!important; padding:1px; font-weight:700; font-size:7px; }
                        .sign-tbl td { border:1px solid #999!important; height:30px!important; vertical-align:bottom!important; padding:2px!important; font-weight:700; font-size:7px; }

                        .carton-tbl { width:100%; border-collapse:collapse; font-size:7px; }
                        .carton-tbl th { background:#435ebe!important; color:#fff!important; border:1px solid #000!important; padding:1px 0!important; text-align:center; font-weight:700; }
                        .carton-tbl td { border:1px solid #ccc!important; padding:1px 0!important; text-align:center; vertical-align:middle; font-size:7px; }
                    `;

                    const html = `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
                        <title>Block — ${block.kj} / ${block.line}</title>
                        <style>${css}</style>
                        </head><body>

                        <!-- HEADER FORM -->
                        <div class="form-header">
                            <div class="logo-area">
                                ${(typeof LOGO_BASE64 !== 'undefined' && LOGO_BASE64)
                                    ? '<img src="' + LOGO_BASE64 + '" style="width:55px;height:auto;max-height:40px;object-fit:contain;" />'
                                    : '<div style="font-size:18px;font-weight:900;color:#1a3a7a;">M</div>'}
                            </div>
                            <div class="title-area">
                                <h4>PT. Kanindo Makmur Jaya</h4>
                                <p><strong>CARTON WEIGHT REPORT</strong></p>
                                <p style="font-size:7px;color:#555;">Non-Nike &nbsp;·&nbsp; Dicetak: ${printed}</p>
                            </div>
                            <div class="doc-area">
                                <table>
                                    <tr><td>No. Dokumen</td><td>&nbsp;</td></tr>
                                    <tr><td>Tgl. Terbit</td><td>&nbsp;</td></tr>
                                    <tr><td>Revisi</td><td>&nbsp;</td></tr>
                                    <tr><td>Tgl. Efektif</td><td>&nbsp;</td></tr>
                                    <tr><td>Departemen</td><td>&nbsp;</td></tr>
                                </table>
                            </div>
                        </div>

                        <!-- BLOCK -->
                        <div class="block-wrap">
                            <div class="block-title">
                                CARTON WEIGHT REPORT &nbsp;—&nbsp; Laporan Timbangan Karton
                                ${isContinued ? '<span style="background:#fff3cd;color:#856404;padding:1px 6px;border-radius:3px;font-size:7px;">Lanjutan</span>' : ''}
                                ${block.checking_ke > 1 ? '<span style="background:#ff6b35;color:#fff;padding:1px 6px;border-radius:3px;font-size:8px;">Checking #' + block.checking_ke + '</span>' : ''}
                            </div>

                            <div class="block-info">
                                <div class="block-info-left">
                                    <table class="info-tbl">
                                        <tr><td>BUYER</td><td><strong>${block.buyer}</strong></td></tr>
                                        <tr><td>Order No.</td><td><strong>${block.kj}</strong></td></tr>
                                        <tr><td>PO#</td><td>${block.po}</td></tr>
                                        <tr><td>Style</td><td>${block.style}</td></tr>
                                        <tr><td>Color</td><td>${block.color}</td></tr>
                                        <tr><td>Qty Order</td><td>${parseInt(block.qty_order || 0).toLocaleString()} pcs</td></tr>
                                        <tr><td>Ctn / Less Ctn</td><td>${cartons.length} / -</td></tr>
                                        <tr><td>Carton Wgt Std.</td><td>${block.carton_weight_std ? parseFloat(block.carton_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>
                                        <tr><td>Pcs Wgt Std.</td><td>${block.pcs_weight_std ? parseFloat(block.pcs_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>
                                        <tr><td colspan="2"><span style="font-size:7px;">${block.subcon ? 'S = <strong>' + block.subcon + '</strong>' : 'L = <strong>' + (block.line || '-') + '</strong>'} &nbsp; M = <strong>${block.pcs_default}</strong></span></td></tr>
                                    </table>
                                </div>
                                <div class="block-info-right">
                                    <table class="info-tbl">
                                        <tr><td>GAC date</td><td>${block.gac_date}</td></tr>
                                        <tr><td>Destination</td><td>${block.destination}</td></tr>
                                        <tr><td>Inspector</td><td>${block.inspector}</td></tr>
                                        <tr><td>Total Carton</td><td><strong>${cartons.length}</strong> / ${block.totalCartonInLine}</td></tr>
                                    </table>
                                    <table class="sign-tbl">
                                        <tr>
                                            <th>OPT QC TIMBANGAN</th>
                                            <th>SPV QC</th>
                                            <th>CHIEF FINISH GOOD</th>
                                        </tr>
                                        <tr>
                                            <td style="height:35px;vertical-align:bottom;">${block.opt_qc !== '-' ? block.opt_qc : ''}</td>
                                            <td style="height:35px;vertical-align:bottom;">${block.spv_qc !== '-' ? block.spv_qc : ''}</td>
                                            <td style="height:35px;vertical-align:bottom;">${block.chief !== '-' ? block.chief : ''}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <table class="carton-tbl">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Date</th>
                                        <th colspan="${COLS}">Ctn. No &amp; Weight (Kg)</th>
                                        <th rowspan="2">Total (kg)</th>
                                        <th rowspan="2">Remark</th>
                                    </tr>
                                    <tr>${thCols}</tr>
                                </thead>
                                <tbody>${tbodyRows}</tbody>
                            </table>
                        </div>

                        <script>window.onload=()=>{window.print();};<\/script>
                        </body></html>`;

                    const win = window.open('', '_blank');
                    win.document.write(html);
                    win.document.close();
                };

                // ─── UTILS ───────────────────────────────────────────────────
                function chunkArray(arr, size) {
                    const chunks = [];
                    for (let i = 0; i < arr.length; i += size) {
                        chunks.push(arr.slice(i, i + size));
                    }
                    return chunks;
                }

            })();

            // ── Save Keterangan (inline edit di laporan) ──────────────────────────────
            document.addEventListener('click', async function (e) {
                const btn = e.target.closest('.ket-save-btn')
                if (!btn) return

                const ordersheetId = btn.dataset.ordersheetId
                if (!ordersheetId) {
                    alert('ID Ordersheet tidak ditemukan. Simpan timbangan terlebih dahulu.')
                    return
                }

                const wrap    = btn.closest('td, div')
                const input   = wrap?.querySelector('.ket-input') 
                            ?? btn.parentElement?.querySelector('.ket-input')
                const statusEl = wrap?.querySelector('.ket-status')
                            ?? btn.parentElement?.querySelector('.ket-status')

                if (!input) return

                const keterangan = input.value.trim()
                const originalText = btn.textContent
                btn.disabled    = true
                btn.textContent = '...'

                try {
                    const res = await fetch('/user/order/update-keterangan', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify({ ordersheet_id: ordersheetId, keterangan }),
                    })

                    const json = await res.json()

                    if (json.success) {
                        // Update teks display
                        const cell = btn.closest('.ket-cell')
                        if (cell) {
                            const displayEl = cell.querySelector('.ket-display')
                            if (displayEl) {
                                displayEl.innerHTML = keterangan 
                                    || '<span style="color:#bbb;font-style:italic;">—</span>'
                            }
                            // Tutup editor
                            cell.querySelector('.ket-edit').style.display = 'none'
                            cell.querySelector('.ket-display').style.display = 'block'
                        }
                        
                        if (statusEl) {
                            statusEl.textContent  = '✓ Tersimpan'
                            statusEl.style.color  = 'green'
                            statusEl.style.display = 'inline'
                            setTimeout(() => { statusEl.style.display = 'none' }, 2500)
                        }
                    } else {
                        throw new Error(json.message || 'Gagal')
                    }
                } catch (err) {
                    if (statusEl) {
                        statusEl.textContent  = '✗ ' + err.message
                        statusEl.style.color  = 'red'
                        statusEl.style.display = 'inline'
                    }
                } finally {
                    btn.disabled    = false
                    btn.textContent = originalText
                }
            })

            // Enter di textarea → save
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey && e.target.classList.contains('ket-input')) {
                    e.preventDefault()
                    e.target.closest('td, div')?.querySelector('.ket-save-btn')
                        ?.click()
                }
            })

            // Klik teks → buka editor
            document.addEventListener('click', function(e) {
                const display = e.target.closest('.ket-display')
                if (!display) return
                const cell = display.closest('.ket-cell')
                display.style.display = 'none'
                const editDiv = cell.querySelector('.ket-edit')
                editDiv.style.display = 'flex'
                editDiv.querySelector('.ket-input')?.focus()
            })

            // Tombol cancel → tutup editor
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.ket-cancel-btn')
                if (!btn) return
                const cell = btn.closest('.ket-cell')
                cell.querySelector('.ket-edit').style.display = 'none'
                cell.querySelector('.ket-display').style.display = 'block'
            })
        </script>
    @endpush

</x-layout.home>
