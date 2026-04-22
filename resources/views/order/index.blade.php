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
                                        placeholder="Cari KJ / Buyer / PO..." style="width: 200px;">
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
                                    @php $kjId = 'kj-' . md5($kj) @endphp

                                    <div class="kj-group card border-0 shadow-sm mb-3"
                                        id="{{ $kjId }}-wrapper" data-kj="{{ strtolower($kj) }}"
                                        data-buyer="{{ strtolower($kjData['buyer']) }}"
                                        data-po="{{ strtolower($kjData['by_po']->keys()->implode(' ')) }}">

                                        {{-- KJ HEADER --}}
                                        <div class="card-header bg-white py-2 px-3 kj-toggle"
                                            data-target="{{ $kjId }}" style="cursor:pointer;">
                                            <div class="row align-items-center g-2">
                                                <div class="col-12 col-md-7">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <i class="fas fa-chevron-right kj-chevron text-muted"
                                                            id="{{ $kjId }}-chevron"
                                                            style="transition:transform 0.2s; font-size:11px;"></i>
                                                        <span
                                                            class="badge bg-primary px-2 py-1">{{ $kj }}</span>
                                                        <span
                                                            class="text-muted small fw-semibold">{{ $kjData['buyer'] }}</span>
                                                        <span class="badge bg-light text-dark border small"
                                                            style="color: rgb(20, 20, 20) !important;">
                                                            {{ $kjData['by_po']->count() }} PO
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-5">
                                                    <div class="d-flex gap-3 justify-content-md-end flex-wrap">
                                                        <div class="text-center">
                                                            <div class="fw-bold text-primary small">
                                                                {{ $kjData['total_carton'] }}</div>
                                                            <div style="font-size:10px;" class="text-muted">Carton
                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-success small">
                                                                {{ number_format($kjData['qty_sudah']) }}</div>
                                                            <div style="font-size:10px;" class="text-muted">Pcs</div>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-info small">
                                                                {{ number_format($kjData['total_berat'], 2) }} kg</div>
                                                            <div style="font-size:10px;" class="text-muted">Total
                                                                Berat</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- KJ BODY --}}
                                        <div class="kj-body collapse" id="{{ $kjId }}-body">
                                            <div class="p-3">

                                                {{-- ── LEVEL 2: PO ── --}}
                                                @foreach ($kjData['by_po'] as $po => $poData)
                                                    @php $poId = $kjId . '-po-' . md5($po) @endphp

                                                    <div class="po-group mb-3">
                                                        {{-- PO HEADER --}}
                                                        <div class="d-flex align-items-center gap-2 px-2 py-2 rounded po-toggle"
                                                            data-target="{{ $poId }}"
                                                            style="background:#f0f4ff; cursor:pointer; border-left: 3px solid #4361ee;">
                                                            <i class="fas fa-chevron-right po-chevron text-primary"
                                                                id="{{ $poId }}-chevron"
                                                                style="transition:transform 0.2s; font-size:10px;"></i>
                                                            <span class="badge bg-info text-dark px-2">PO:
                                                                {{ $po }}</span>
                                                            <span class="small text-muted">
                                                                {{ $poData['by_style']->count() }} style ·
                                                                {{ $poData['total_carton'] }} carton ·
                                                                {{ number_format($poData['qty_sudah']) }} pcs ·
                                                                {{ number_format($poData['total_berat'], 2) }} kg
                                                            </span>
                                                        </div>

                                                        {{-- PO BODY --}}
                                                        <div class="po-body collapse ps-3 pt-2"
                                                            id="{{ $poId }}-body">

                                                            {{-- ── LEVEL 3: STYLE ── --}}
                                                            @foreach ($poData['by_style'] as $style => $styleData)
                                                                @php $styleId = $poId . '-style-' . md5($style) @endphp

                                                                <div class="style-group mb-2">
                                                                    {{-- STYLE HEADER --}}
                                                                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded style-toggle"
                                                                        data-target="{{ $styleId }}"
                                                                        style="background:#fff8e1; cursor:pointer; border-left: 3px solid #f9a825;">
                                                                        <i class="fas fa-chevron-right style-chevron text-warning"
                                                                            id="{{ $styleId }}-chevron"
                                                                            style="transition:transform 0.2s; font-size:10px;"></i>
                                                                        <span
                                                                            class="badge bg-warning text-dark px-2">{{ $style }}</span>
                                                                        <span class="small text-muted">
                                                                            {{ $styleData['by_color']->count() }} color
                                                                            ·
                                                                            {{ $styleData['total_carton'] }} carton ·
                                                                            {{ number_format($styleData['qty_sudah']) }}
                                                                            pcs ·
                                                                            {{ number_format($styleData['total_berat'], 2) }}
                                                                            kg
                                                                        </span>
                                                                    </div>

                                                                    {{-- STYLE BODY --}}
                                                                    <div class="style-body collapse ps-3 pt-2"
                                                                        id="{{ $styleId }}-body">

                                                                        {{-- ── LEVEL 4: COLOR ── --}}
                                                                        @foreach ($styleData['by_color'] as $color => $colorData)
                                                                            @php $colorId = $styleId . '-color-' . md5($color) @endphp

                                                                            <div class="color-group mb-3 border rounded p-2"
                                                                                style="border-left: 3px solid #2ec4b6 !important;">

                                                                                {{-- COLOR HEADER --}}
                                                                                <div
                                                                                    class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                                                                    <div
                                                                                        class="d-flex align-items-center gap-2 flex-wrap">
                                                                                        <span class="badge px-2 py-1"
                                                                                            style="background:#2ec4b6; color:#fff;">
                                                                                            <i
                                                                                                class="fas fa-palette me-1"></i>{{ $color }}
                                                                                        </span>
                                                                                        <span class="small text-muted">
                                                                                            Order:
                                                                                            <strong>{{ $colorData['order_code'] }}</strong>
                                                                                        </span>
                                                                                    </div>

                                                                                    {{-- INFO QTY & DEST --}}
                                                                                    <div
                                                                                        class="d-flex gap-3 flex-wrap align-items-center">
                                                                                        <div class="text-center">
                                                                                            <div
                                                                                                class="fw-bold small text-dark">
                                                                                                {{ number_format($colorData['qty_sudah']) }}
                                                                                                /
                                                                                                {{ number_format($colorData['qty_order']) }}
                                                                                                <span
                                                                                                    class="text-muted"
                                                                                                    style="font-size:10px;">pcs</span>
                                                                                            </div>
                                                                                            <div style="font-size:10px;"
                                                                                                class="text-muted">Qty
                                                                                                Ditimbang</div>
                                                                                        </div>
                                                                                        <div class="text-center">
                                                                                            <div
                                                                                                class="fw-bold small {{ $colorData['qty_sisa'] == 0 ? 'text-success' : 'text-warning' }}">
                                                                                                {{ $colorData['qty_sisa'] == 0 ? '✓ Selesai' : number_format($colorData['qty_sisa']) . ' pcs' }}
                                                                                            </div>
                                                                                            <div style="font-size:10px;"
                                                                                                class="text-muted">Sisa
                                                                                                Qty</div>
                                                                                        </div>
                                                                                        <div class="text-center">
                                                                                            <div
                                                                                                class="fw-bold small text-info">
                                                                                                {{ number_format($colorData['total_berat'], 2) }}
                                                                                                kg</div>
                                                                                            <div style="font-size:10px;"
                                                                                                class="text-muted">
                                                                                                Total Berat</div>
                                                                                        </div>
                                                                                        <div class="text-center">
                                                                                            <div
                                                                                                class="fw-bold small text-secondary">
                                                                                                {{ $colorData['destination'] }}
                                                                                            </div>
                                                                                            <div style="font-size:10px;"
                                                                                                class="text-muted">
                                                                                                Destination</div>
                                                                                        </div>
                                                                                        <div class="text-center">
                                                                                            <div
                                                                                                class="fw-bold small text-muted">
                                                                                                {{ $colorData['gac_date'] }}
                                                                                            </div>
                                                                                            <div style="font-size:10px;"
                                                                                                class="text-muted">GAC
                                                                                                Date</div>
                                                                                        </div>
                                                                                        <a href="{{ route('order.print.orderCode', $colorData['order_code']) }}"
                                                                                            target="_blank"
                                                                                            class="btn btn-outline-primary btn-sm"
                                                                                            onclick="event.stopPropagation()">
                                                                                            <i
                                                                                                class="bi bi-printer"></i>
                                                                                        </a>
                                                                                    </div>
                                                                                </div>

                                                                                {{-- ── LEVEL 5: LINE ── --}}
                                                                                @foreach ($colorData['by_line'] as $lineNo => $lineData)
                                                                                    @php
                                                                                        $lineUid =
                                                                                            $colorId .
                                                                                            '-line-' .
                                                                                            $lineNo;
                                                                                    @endphp

                                                                                    <div class="line-section mt-2">
                                                                                        {{-- LINE HEADER --}}
                                                                                        <div class="d-flex justify-content-between align-items-center
                                                                        flex-wrap gap-2 px-2 py-1 rounded mb-2"
                                                                                            style="background:#e8f5e9; border-left: 3px solid #43a047;">
                                                                                            <div
                                                                                                class="d-flex align-items-center gap-2 flex-wrap">
                                                                                                <span
                                                                                                    class="badge bg-success px-2"
                                                                                                    style="font-size:11px;">
                                                                                                    Line
                                                                                                    {{ $lineNo }}
                                                                                                </span>
                                                                                                <span
                                                                                                    class="small text-muted">
                                                                                                    {{ $lineData['total_carton'] }}
                                                                                                    carton ·
                                                                                                    {{ number_format($lineData['qty_sudah']) }}
                                                                                                    pcs ·
                                                                                                    {{ number_format($lineData['total_berat'], 2) }}
                                                                                                    kg
                                                                                                </span>
                                                                                            </div>
                                                                                            {{-- Filter & search per line --}}
                                                                                            <div
                                                                                                class="d-flex gap-2 align-items-center flex-wrap">
                                                                                                <input type="text"
                                                                                                    class="form-control form-control-sm line-search"
                                                                                                    data-line-id="{{ $lineUid }}"
                                                                                                    placeholder="Cari no. carton..."
                                                                                                    style="width:140px; font-size:11px;">
                                                                                                <select
                                                                                                    class="form-select form-select-sm line-sort"
                                                                                                    data-line-id="{{ $lineUid }}"
                                                                                                    style="width:120px; font-size:11px;">
                                                                                                    <option
                                                                                                        value="asc">
                                                                                                        Urut ↑</option>
                                                                                                    <option
                                                                                                        value="desc">
                                                                                                        Urut ↓</option>
                                                                                                    <option
                                                                                                        value="berat-desc">
                                                                                                        Berat ↓</option>
                                                                                                    <option
                                                                                                        value="berat-asc">
                                                                                                        Berat ↑</option>
                                                                                                </select>
                                                                                                <span
                                                                                                    class="small text-muted"
                                                                                                    id="{{ $lineUid }}-showing"></span>
                                                                                            </div>
                                                                                        </div>

                                                                                        {{-- CARTON CARDS --}}
                                                                                        <div class="row g-2 carton-grid"
                                                                                            id="{{ $lineUid }}-grid">
                                                                                            @foreach ($lineData['timbangans'] as $idx => $t)
                                                                                                @php
                                                                                                    $beratVal = floatval(
                                                                                                        $t->berat,
                                                                                                    );
                                                                                                    $pcsVal = intval(
                                                                                                        $t->pcs,
                                                                                                    );
                                                                                                    $waktu = $t->waktu_timbang
                                                                                                        ? \Carbon\Carbon::parse(
                                                                                                            $t->waktu_timbang,
                                                                                                        )->format('H:i')
                                                                                                        : '-';
                                                                                                    $min = floatval(
                                                                                                        $t->rasio_batas_beban_min ??
                                                                                                            0,
                                                                                                    );
                                                                                                    $max = floatval(
                                                                                                        $t->rasio_batas_beban_max ??
                                                                                                            0,
                                                                                                    );
                                                                                                    $statusColor =
                                                                                                        'success';
                                                                                                    $statusLabel = 'OK';
                                                                                                    if (
                                                                                                        $min > 0 &&
                                                                                                        $max > 0
                                                                                                    ) {
                                                                                                        if (
                                                                                                            $beratVal <
                                                                                                            $min
                                                                                                        ) {
                                                                                                            $statusColor =
                                                                                                                'danger';
                                                                                                            $statusLabel =
                                                                                                                'Kurang';
                                                                                                        } elseif (
                                                                                                            $beratVal >
                                                                                                            $max
                                                                                                        ) {
                                                                                                            $statusColor =
                                                                                                                'warning';
                                                                                                            $statusLabel =
                                                                                                                'Lebih';
                                                                                                        }
                                                                                                    }
                                                                                                @endphp
                                                                                                <div class="col-6 col-sm-4 col-md-3 col-xl-2 carton-item"
                                                                                                    data-line-id="{{ $lineUid }}"
                                                                                                    data-no-box="{{ strtolower($t->no_box ?? '') }}"
                                                                                                    data-berat="{{ $beratVal }}"
                                                                                                    data-idx="{{ $t->id }}">
                                                                                                    <div class="card border-{{ $statusColor }} h-100"
                                                                                                        style="border-width:2px !important;">
                                                                                                        <div
                                                                                                            class="card-body p-2 text-center">
                                                                                                            <div
                                                                                                                class="d-flex justify-content-between mb-1">
                                                                                                                <span
                                                                                                                    class="badge bg-light text-dark border"
                                                                                                                    style="font-size:9px;">#{{ $idx + 1 }}</span>
                                                                                                                <span
                                                                                                                    class="badge bg-{{ $statusColor }}"
                                                                                                                    style="font-size:9px;">{{ $statusLabel }}</span>
                                                                                                            </div>
                                                                                                            <div class="fw-bold text-dark"
                                                                                                                style="font-size:0.82rem;">
                                                                                                                {{ $t->no_box ?? '-' }}
                                                                                                            </div>
                                                                                                            <div class="text-primary fw-bold"
                                                                                                                style="font-size:1rem;">
                                                                                                                {{ number_format($beratVal, 2) }}
                                                                                                                <span
                                                                                                                    class="text-muted"
                                                                                                                    style="font-size:0.68rem;">kg</span>
                                                                                                            </div>
                                                                                                            <div class="text-muted"
                                                                                                                style="font-size:0.7rem;">
                                                                                                                {{ number_format($pcsVal) }}
                                                                                                                pcs ·
                                                                                                                {{ $waktu }}
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>

                                                                                        {{-- Pagination carton per line --}}
                                                                                        <div class="d-flex justify-content-center mt-2"
                                                                                            id="{{ $lineUid }}-pagination">
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                                {{-- END LINE --}}

                                                                            </div>
                                                                        @endforeach
                                                                        {{-- END COLOR --}}

                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            {{-- END STYLE --}}

                                                        </div>
                                                    </div>
                                                @endforeach
                                                {{-- END PO --}}

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                {{-- END KJ --}}
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
                                                        <th>Color Description</th>
                                                        <td><input type="text" id="info_color_description"
                                                                name="ColorDescription"
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

            .kj-toggle:hover,
            .po-toggle:hover,
            .style-toggle:hover {
                filter: brightness(0.97);
            }

            .carton-item .card {
                transition: transform 0.12s, box-shadow 0.12s;
            }

            .carton-item .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0, 0, 0, .1) !important;
            }

            .carton-item.rpt-hidden {
                display: none !important;
            }

            .kj-group.rpt-hidden {
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

                const KJ_PAGE = 10
                const CARTON_PAGE = 30

                // ── KJ list ──────────────────────────────────────────────
                let allKJ = Array.from(document.querySelectorAll('.kj-group'))
                let kjPage = 1

                function filteredKJ() {
                    const q = (document.getElementById('kj-search')?.value || '').toLowerCase().trim()
                    return allKJ.filter(el =>
                        !q ||
                        el.dataset.kj.includes(q) ||
                        el.dataset.buyer.includes(q) ||
                        el.dataset.po.includes(q)
                    )
                }

                function renderKJPage(page) {
                    const list = filteredKJ()
                    const total = list.length
                    const tp = Math.ceil(total / KJ_PAGE) || 1
                    if (page > tp) page = tp
                    kjPage = page

                    const start = (page - 1) * KJ_PAGE
                    const visible = new Set(list.slice(start, start + KJ_PAGE))
                    allKJ.forEach(el => el.classList.toggle('rpt-hidden', !visible.has(el)))

                    const s = document.getElementById('kj-showing')
                    if (s) s.textContent = total === 0 ? '0' :
                        `${start + 1}–${Math.min(start + KJ_PAGE, total)} dari ${total}`

                    renderKJPag(page, tp)
                }

                function renderKJPag(cur, total) {
                    const el = document.getElementById('kj-pagination')
                    if (!el || total <= 1) {
                        if (el) el.innerHTML = '';
                        return
                    }
                    let h = `<nav><ul class="pagination pagination-sm mb-0">`
                    h += pagBtn(cur - 1, '‹', cur === 1)
                    for (let p = 1; p <= total; p++) {
                        if (total <= 7 || p === 1 || p === total || Math.abs(p - cur) <= 1)
                            h += pagBtn(p, p, false, p === cur)
                        else if (p === cur - 2 || p === cur + 2)
                            h += `<li class="page-item disabled"><span class="page-link">…</span></li>`
                    }
                    h += pagBtn(cur + 1, '›', cur === total)
                    h += `</ul></nav>`
                    el.innerHTML = h
                    el.querySelectorAll('[data-p]').forEach(b => b.addEventListener('click', e => {
                        e.preventDefault()
                        renderKJPage(parseInt(b.dataset.p))
                    }))
                }

                function pagBtn(p, label, disabled, active = false) {
                    return `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
            <a class="page-link" href="#" data-p="${p}">${label}</a></li>`
                }

                // Search
                document.getElementById('kj-search')?.addEventListener('input', () => renderKJPage(1))

                // Expand / collapse all
                document.getElementById('btn-expand-all')?.addEventListener('click', () => {
                    document.querySelectorAll('.kj-body,.po-body,.style-body').forEach(b => b.classList.add(
                        'show'))
                    document.querySelectorAll('.kj-chevron,.po-chevron,.style-chevron').forEach(c => c.style
                        .transform = 'rotate(90deg)')
                    // Init semua carton grid
                    document.querySelectorAll('.carton-grid').forEach(g => {
                        const lineId = g.id.replace('-grid', '')
                        initLineGrid(lineId)
                    })
                })
                document.getElementById('btn-collapse-all')?.addEventListener('click', () => {
                    document.querySelectorAll('.kj-body,.po-body,.style-body').forEach(b => b.classList.remove(
                        'show'))
                    document.querySelectorAll('.kj-chevron,.po-chevron,.style-chevron').forEach(c => c.style
                        .transform = 'rotate(0deg)')
                })

                // Toggle KJ
                document.querySelectorAll('.kj-toggle').forEach(h => h.addEventListener('click', function() {
                    toggle(this.dataset.target, 'kj-chevron', 'kj-body')
                }))

                // Toggle PO
                document.querySelectorAll('.po-toggle').forEach(h => h.addEventListener('click', function() {
                    toggle(this.dataset.target, 'po-chevron', 'po-body')
                }))

                // Toggle Style
                document.querySelectorAll('.style-toggle').forEach(h => h.addEventListener('click', function() {
                    const id = this.dataset.target
                    const body = document.getElementById(`${id}-body`)
                    const chevron = document.getElementById(`${id}-chevron`)
                    if (!body) return
                    const open = body.classList.toggle('show')
                    if (chevron) chevron.style.transform = open ? 'rotate(90deg)' : 'rotate(0deg)'
                    // Init carton grids saat style dibuka
                    if (open && !body.dataset.init) {
                        body.dataset.init = '1'
                        body.querySelectorAll('.carton-grid').forEach(g => {
                            initLineGrid(g.id.replace('-grid', ''))
                        })
                    }
                }))

                function toggle(targetId, chevronClass, bodyClass) {
                    const body = document.getElementById(`${targetId}-body`)
                    const chevron = document.getElementById(`${targetId}-chevron`)
                    if (!body) return
                    const open = body.classList.toggle('show')
                    if (chevron) chevron.style.transform = open ? 'rotate(90deg)' : 'rotate(0deg)'
                }

                // ── Carton grid per line ──────────────────────────────────
                const lineState = {} // { lineId: { page } }

                function initLineGrid(lineId) {
                    if (!lineState[lineId]) lineState[lineId] = {
                        page: 1
                    }
                    renderLineGrid(lineId, 1)
                }

                function renderLineGrid(lineId, page) {
                    const search = (document.querySelector(`.line-search[data-line-id="${lineId}"]`)?.value || '')
                        .toLowerCase().trim()
                    const sort = document.querySelector(`.line-sort[data-line-id="${lineId}"]`)?.value || 'asc'

                    let items = Array.from(document.querySelectorAll(`.carton-item[data-line-id="${lineId}"]`))

                    // Filter
                    let filtered = items.filter(el =>
                        !search || el.dataset.noBox.includes(search)
                    )

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
                    const tp = Math.ceil(total / CARTON_PAGE) || 1
                    if (page > tp) page = tp

                    const start = (page - 1) * CARTON_PAGE
                    const pageSet = new Set(filtered.slice(start, start + CARTON_PAGE))

                    const grid = document.getElementById(`${lineId}-grid`)
                    if (grid) filtered.forEach(el => grid.appendChild(el))

                    items.forEach(el => el.classList.toggle('rpt-hidden', !pageSet.has(el)))

                    const show = document.getElementById(`${lineId}-showing`)
                    if (show) {
                        const from = total === 0 ? 0 : start + 1
                        const to = Math.min(start + CARTON_PAGE, total)
                        show.textContent = total === 0 ? '0 carton' : `${from}–${to} / ${total}`
                    }

                    renderLinePag(lineId, page, tp)
                }

                function renderLinePag(lineId, cur, total) {
                    const el = document.getElementById(`${lineId}-pagination`)
                    if (!el || total <= 1) {
                        if (el) el.innerHTML = '';
                        return
                    }
                    let h = `<nav><ul class="pagination pagination-sm mb-0 flex-wrap">`
                    h += pagBtn(cur - 1, '‹', cur === 1)
                    for (let p = 1; p <= total; p++) {
                        if (total <= 7 || p === 1 || p === total || Math.abs(p - cur) <= 1)
                            h += pagBtn(p, p, false, p === cur)
                        else if (p === cur - 2 || p === cur + 2)
                            h += `<li class="page-item disabled"><span class="page-link">…</span></li>`
                    }
                    h += pagBtn(cur + 1, '›', cur === total)
                    h += `</ul></nav>`
                    el.innerHTML = h
                    el.querySelectorAll('[data-p]').forEach(b => b.addEventListener('click', e => {
                        e.preventDefault()
                        const p = parseInt(b.dataset.p)
                        renderLineGrid(lineId, p)
                        document.getElementById(`${lineId}-grid`)?.closest('.line-section')
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            })
                    }))
                }

                // Event: search & sort per line
                document.addEventListener('input', e => {
                    if (e.target.classList.contains('line-search'))
                        renderLineGrid(e.target.dataset.lineId, 1)
                })
                document.addEventListener('change', e => {
                    if (e.target.classList.contains('line-sort'))
                        renderLineGrid(e.target.dataset.lineId, 1)
                })

                // Initial render
                renderKJPage(1)
            })
        </script>
    @endpush

</x-layout.home>
