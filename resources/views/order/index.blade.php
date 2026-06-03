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

                        <div id="searchScannerIndicator" class="scanner-indicator scanner-idle"
                            title="Scan barcode karton untuk langsung mencari di tabel">
                            <i class="fa-solid fa-barcode"></i>
                            <span id="searchScannerText">Scan untuk cari</span>
                        </div>
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
                    <div class="row g-3 align-items-end mb-1">
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

                    {{-- Hint pencarian — di luar row agar tidak ganggu alignment --}}
                    <div class="d-flex align-items-start gap-1 mb-3">
                        <i class="bi bi-info-circle text-muted" style="font-size: 12px; margin-top: 2px;"></i>
                        <small class="text-muted">
                            Pisahkan dengan titik koma untuk pencarian multi-kolom.
                            Contoh: <code>KJ-283728;STYLE-1;700</code> (order no; no. style; qty)
                        </small>
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
                                    <th>Order No</th>
                                    <th>No Style</th>
                                    <th>Color</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>PO Number</th>
                                    <th>Buyer</th>
                                    <th>Destination</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="11" class="text-muted text-center py-4">
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
                    <!-- Judul -->
                    <div class="judul">
                        <h5 class="fw-bold text-center mb-3">Carton Weight Report - <span>Laporan Timbangan
                                Karton</span></h5>
                    </div>
                    <hr>

                    {{-- ═══════════════════════════════════════════════════════
             1. MY REPORT (data milik user login sendiri)
        ═══════════════════════════════════════════════════════ --}}
                    <div class="formal-report-wrap" id="my-report-wrap" style="margin-bottom: 24px;">

                        <div class="formal-report-header">
                            <div>
                                <div class="formal-report-title">
                                    👤 Laporan Saya
                                    <small>Hanya timbangan yang Anda kerjakan sendiri</small>
                                </div>
                            </div>
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

                    <hr style="border-top: 2px solid #dee2e6; margin: 28px 0;">

                    {{-- ═══════════════════════════════════════════════════════
             2. FORMAL REPORT (semua user)
        ═══════════════════════════════════════════════════════ --}}
                    <div class="formal-report-wrap" id="formal-report-wrap" style="margin-bottom: 24px;">

                        <div class="formal-report-header">
                            <div>
                                <div class="formal-report-title">
                                    📋 Laporan Semua User
                                    <small>Seluruh timbangan dari semua operator</small>
                                </div>
                            </div>
                        </div>

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

                        <div class="formal-tabs">
                            <div class="formal-tab active" data-formal-tab="nike">
                                <i class="fas fa-check-circle" style="font-size:11px;"></i>
                                NIKE
                                <span class="tab-badge" id="formal-nike-count-badge">0</span>
                            </div>
                            <div class="formal-tab" data-formal-tab="non-nike">
                                <i class="fas fa-layer-group" style="font-size:11px;"></i>
                                NON-NIKE
                                <span class="tab-badge" id="formal-non-nike-count-badge">0</span>
                            </div>
                        </div>

                        <div class="formal-panel active" id="formal-panel-nike">
                            <div id="formal-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-search"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Klik "Tampilkan" untuk memuat laporan
                                </div>
                            </div>
                        </div>

                        <div class="formal-panel" id="formal-panel-non-nike">
                            <div id="formal-non-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-search"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Klik "Tampilkan" untuk memuat laporan
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- END FORMAL REPORT --}}

                    <hr style="border-top: 2px solid #dee2e6; margin: 28px 0;">

                    {{-- ═══════════════════════════════════════════════════════
             3. REPORT PER BUYER
        ═══════════════════════════════════════════════════════ --}}
                    <div class="formal-report-wrap" id="buyer-report-wrap" style="margin-bottom: 24px;">

                        <div class="formal-report-header">
                            <div>
                                <div class="formal-report-title">
                                    🏷️ Laporan Per Buyer
                                    <small>Filter laporan berdasarkan buyer tertentu</small>
                                </div>
                            </div>
                        </div>

                        <div class="formal-filter-bar">
                            <div>
                                <label>Tanggal Mulai</label>
                                <input type="date" id="buyer-date-start" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label>Tanggal Akhir</label>
                                <input type="date" id="buyer-date-end" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div>
                                <label>Buyer</label>
                                <select id="buyer-select"
                                    style="font-size:11px;padding:4px 8px;border:1px solid #ced4da;border-radius:4px;
                               background:#fff;cursor:pointer;min-width:160px;">
                                    <option value="">-- Pilih Buyer --</option>
                                    {{-- Diisi via JS dari endpoint /user/order/buyers --}}
                                </select>
                            </div>
                            <div style="display:flex; gap:6px; align-items:flex-end;">
                                <button class="btn-filter" id="btn-buyer-filter">
                                    <i class="fas fa-search" style="font-size:10px;"></i> Tampilkan
                                </button>
                                <button class="btn-reset-filter" id="btn-buyer-reset">Reset</button>
                            </div>
                            <div style="margin-left:auto; font-size:11px; color:var(--muted); align-self:flex-end;">
                                Menampilkan: <strong id="buyer-range-label">Hari ini</strong>
                            </div>
                        </div>

                        <div class="formal-tabs">
                            <div class="formal-tab active" data-buyer-tab="nike">
                                <i class="fas fa-check-circle" style="font-size:11px;"></i>
                                NIKE
                                <span class="tab-badge" id="buyer-nike-count-badge">0</span>
                            </div>
                            <div class="formal-tab" data-buyer-tab="non-nike">
                                <i class="fas fa-layer-group" style="font-size:11px;"></i>
                                NON-NIKE
                                <span class="tab-badge" id="buyer-non-nike-count-badge">0</span>
                            </div>
                        </div>

                        <div class="formal-panel active" id="buyer-panel-nike">
                            <div id="buyer-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-filter"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Pilih buyer lalu klik "Tampilkan"
                                </div>
                            </div>
                        </div>

                        <div class="formal-panel" id="buyer-panel-non-nike">
                            <div id="buyer-non-nike-report-content">
                                <div class="formal-empty">
                                    <i class="fas fa-filter"
                                        style="font-size:24px; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    Pilih buyer lalu klik "Tampilkan"
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- END REPORT PER BUYER --}}

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
                                                            <textarea id="info_keterangan" name="keterangan" class="form-control form-control-sm" rows="2"
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
                                                        <!-- <button class="btn btn-outline-warning btn-sm" type="button"
                                                            id="btnScanBarcode">
                                                            <i class="fa-solid fa-barcode"></i>
                                                            <span class="d-none d-sm-inline"> Scan</span>
                                                        </button> -->
                                                        <div id="scannerIndicator"
                                                            class="scanner-indicator scanner-idle"
                                                            title="Arahkan scanner ke barcode karton">
                                                            <i class="fa-solid fa-barcode"></i>
                                                            <span id="scannerText">Siap scan</span>
                                                        </div>
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

    <!-- <div class="modal fade" id="scannerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
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
    </div> -->

    @push('css')
        <link rel="stylesheet" href="{{ asset('auth/css/order.css') }}">
        <style>
            .scanner-indicator {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                border: 1px solid transparent;
                transition: background 0.25s, color 0.25s, border-color 0.25s;
                user-select: none;
                min-width: 130px;
            }

            .scanner-idle {
                background: #f0f4ff;
                color: #435ebe;
                border-color: #c5d0f5;
            }

            .scanner-scanning {
                background: #fff8e1;
                color: #856404;
                border-color: #ffc107;
                animation: scanner-pulse 0.4s ease-in-out infinite alternate;
            }

            .scanner-loading {
                background: #e8f4fd;
                color: #0a5fa0;
                border-color: #90caf9;
            }

            .scanner-success {
                background: #e6f9f0;
                color: #1a6a3e;
                border-color: #48c78e;
            }

            .scanner-error {
                background: #fdecea;
                color: #b71c1c;
                border-color: #ef9a9a;
            }

            @keyframes scanner-pulse {
                from {
                    opacity: 1;
                }

                to {
                    opacity: 0.65;
                }
            }
        </style>
    @endpush

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2/sweetalert2.all.min.js') }}"></script>
        {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
        <!-- <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script> -->

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

            // Cek device saat modal timbangan akan dibuka
            document.getElementById('timbangModal').addEventListener('show.bs.modal', function () {
                const espId = window.APP?.espId?.trim();

                const oldBanner = document.getElementById('device-warning-banner');
                if (oldBanner) oldBanner.remove();

                if (!espId) {
                    const banner = document.createElement('div');
                    banner.id = 'device-warning-banner';
                    banner.innerHTML = `
                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-0 rounded-0 border-0 border-bottom border-warning">
                            <i class="fa-solid fa-triangle-exclamation fs-5 mt-1 text-warning"></i>
                            <div>
                                <strong>Device Timbangan Belum Dipilih</strong><br>
                                <span class="small">
                                    Anda belum memilih device timbangan aktif. 
                                    Berat bisa diisi manual, atau pilih device via tombol 
                                    <strong><i class="fa-solid fa-microchip"></i> Device</strong> di halaman utama.
                                </span>
                            </div>
                            <button type="button" class="btn-close ms-auto" 
                                onclick="document.getElementById('device-warning-banner').remove()">
                            </button>
                        </div>
                    `;

                    const modalBody = this.querySelector('.modal-body');
                    modalBody.insertAdjacentElement('beforebegin', banner);
                }
                // Tidak ada disable apapun — semua tetap normal
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

    <!-- @php
        $logoPath = public_path('assets/images/logo/kanindo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp -->

    @push('js')
        <script>
            window.LOGO_URL = "{{ asset('assets/images/logo/kanindo.png') }}";
        </script>
        <script src="{{ asset('auth/js/report.js') }}" defer></script>
    @endpush

</x-layout.home>
