<x-layout.home title="Ordersheet Package">

    <div class="page-heading d-flex justify-content-between align-items-center">
        @php
            $deviceType = null;

            if (Auth::check()) {
                // Ambil device aktif milik user login saat ini
                $device = \App\Models\Update\Device::where('user_id', Auth::id())->where('status', 'in_use')->first();

                if ($device) {
                    // ambil huruf pertama setelah "Timbangan-" → O atau P
                    if (preg_match('/Timbangan-([OP])\d+-/', $device->esp_id, $matches)) {
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
                    <h5>Package Barang</h5>
                    <hr>
                    <div class="action-bar mb-3">

                        <!-- Tambah Data -->
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#tambah">
                            <i class="fas fa-plus me-1"></i>
                            Tambah Data
                        </button>

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
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Stitching</th>
                                    <th>Lining</th>
                                    <th>Tanggal</th>
                                    <th>Berat Terakhir (g)</th>
                                    {{-- <th>Aksi</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $i => $package)
                                    @php $lastWeight = $package->weights->first(); @endphp
                                    <tr>
                                        <td>{{ $i + $packages->firstItem() }}</td>
                                        <td>{{ $package->name }}</td>
                                        <td>{{ $package->leather_type }}</td>
                                        <td>{{ $package->size }}</td>
                                        <td>{{ $package->stitching_type }}</td>
                                        <td>{{ $package->lining_material }}</td>
                                        <td>{{ date('d/m/Y', strtotime($package->created_at)) }}</td>
                                        <td>{{ isset($lastWeight->weight) ? $lastWeight->weight + 0 : '-' }} g</td>
                                        {{-- <td>
                                            <button class="btn btn-sm btn-outline-primary btn-timbang"
                                                data-id="{{ $package->id }}">
                                                <i class="fa-solid fa-weight-scale"></i> Timbang
                                            </button>
                                        </td> --}}
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Belum ada data package.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- Pagination --}}
                        <div class="d-flex justify-content-center mt-3">
                            {{ $packages->links() }}
                        </div>
                    </div>

                    <nav id="pagination" class="d-flex justify-content-center mt-3"></nav>
                </div>
            </div>

            {{-- <div class="card report">
                <div class="card-body">
                    <div class="judul">
                        <h5 class="fw-bold text-center mb-3">Package Weight Report - <span>Laporan Timbangan
                                Package</span>
                        </h5>
                        <div class="d-flex justify-content-center">
                            <a href="#" target="_blank" class="btn btn-primary">
                                <i class="fa-solid fa-print"></i> Print Laporan
                            </a>
                        </div>
                    </div>
                    <hr>

                    <div class="cetak">
                        @if ($packages->isEmpty())
                            <div class="alert alert-info text-center">
                                <i class="fas fa-file"></i> Belum Ada Data
                            </div>
                        @else
                            @foreach ($packages as $groupKey => $packs)
                                @php
                                    [$date, $buyer] = explode('|', $groupKey);
                                @endphp

                                <div class="card shadow-sm mb-5 border-0">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <div class="text-center">
                                                <h5 class="mb-0 fw-bold">Current Weight Report
                                                </h5>
                                                <h6>
                                                    Laporan Timbangan Package
                                                </h6>
                                                <small>
                                                    Buyer: <strong>{{ $buyer }}</strong> |
                                                    {{ $date }}
                                                </small>
                                                <a href="{{ route('order.print.buyer', $buyer) }}" target="_blank"
                                                    class="btn btn-outline-info btn-sm text-end">
                                                    <i class="bi bi-printer"></i> Print
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-bordered text-center align-middle mb-0"
                                                style="white-space: nowrap; table-layout: fixed; border-collapse: collapse;">
                                                <thead class="table-light sticky-top" style="z-index: 1;">
                                                    <tr>
                                                        <th rowspan="2"
                                                            style="vertical-align: middle; width: 100px; position: sticky; left: 0; background: #f8f9fa;">
                                                            Date</th>
                                                        @for ($i = 0; $i < 10; $i++)
                                                            <th style="width: 85px;">Ctn. No</th>
                                                        @endfor
                                                        <th rowspan="2" style="vertical-align: middle; width: 90px;">
                                                            Total</th>
                                                        <th rowspan="2"
                                                            style="vertical-align: middle; width: 100px;">
                                                            Remark</th>
                                                    </tr>
                                                    <tr>
                                                        @for ($i = 0; $i < 10; $i++)
                                                            <th style="width: 85px;">Weight</th>
                                                        @endfor
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($packs as $item)
                                                        @php
                                                            $timbangans = $item->timbangans->take(10);
                                                            $noBoxes = $timbangans->pluck('no_box')->toArray();
                                                            $weights = $timbangans->pluck('berat')->toArray();
                                                            $noBoxes = array_pad($noBoxes, 10, '-');
                                                            $weights = array_pad($weights, 10, '-');
                                                            $totalWeight = array_sum(
                                                                array_filter($weights, 'is_numeric'),
                                                            );
                                                        @endphp

                                                        <!-- Baris No. Box -->
                                                        <tr>
                                                            <td rowspan="2"
                                                                style="vertical-align: middle; position: sticky; left: 0; background: #fff; z-index: 1;">
                                                                {{ $date }}
                                                            </td>
                                                            @foreach ($noBoxes as $no)
                                                                <td>{{ $no }}</td>
                                                            @endforeach
                                                            <td rowspan="2" class="fw-bold text-primary">
                                                                {{ number_format($totalWeight, 2) }}
                                                            </td>
                                                            <td rowspan="2"></td>
                                                        </tr>

                                                        <!-- Baris Weight -->
                                                        <tr class="table-secondary">
                                                            @foreach ($weights as $berat)
                                                                <td class="text-muted">{{ $berat }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div> --}}
        </section>

    </div>

    @include('package.create')

    <div class="modal fade" id="wifi" tabindex="-1" aria-labelledby="tambahLabel" aria-hidden="true">
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
    </div>

    @push('css')
        <style>
            .action-bar {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }

            .device-btn {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .device-dropdown-menu {
                max-height: 250px;
                overflow-y: auto;
            }

            .dropdown-item.active-device {
                background: #e7f1ff;
                font-weight: 600;
            }

            .dropdown-item.in-use {
                color: #4b9cff;
                font-weight: 600;
            }

            .dropdown-item.used-by-others {
                color: #888;
                pointer-events: none;
                opacity: 0.6;
            }
        </style>
    @endpush

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2/sweetalert2.all.min.js') }}"></script>
        {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

        <script>
            // Hari
            function updateDateTime() {
                const now = new Date();

                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const dayName = days[now.getDay()];
                const date = now.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
                const time = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                document.getElementById('current-day').textContent = `${dayName}, ${date}`;
                document.getElementById('current-time').textContent = time;
            }

            // Jalankan saat halaman load dan per detik
            document.addEventListener('DOMContentLoaded', () => {
                updateDateTime();
                setInterval(updateDateTime, 1000);
            });

            let currentId = null;
            let pollingInterval = null;
            let latestPreview = null;

            const searchBtn = document.getElementById('searchBtn');
            const spinner = document.getElementById('loadingSpinner');
            const tableBody = document.querySelector('#resultTable tbody');
            const pagination = document.getElementById('pagination');

            searchBtn.addEventListener('click', () => fetchData(1));

            async function fetchData(page = 1) {
                const search = document.getElementById('search').value.trim();
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;

                if (!search && !start && !end) {
                    Swal.fire('Peringatan', 'Isi setidaknya satu kolom!', 'warning');
                    return;
                }

                spinner.style.display = 'inline-block';
                tableBody.innerHTML = `<tr><td colspan="9" class="text-center">Memuat...</td></tr>`;
                if (pagination) pagination.innerHTML = '';

                try {
                    const params = new URLSearchParams({
                        page
                    });
                    if (search) params.append('search', search);
                    if (start) params.append('start_date', start);
                    if (end) params.append('end_date', end);

                    const response = await fetch(`/user/package/search?${params}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const json = await response.json();
                    spinner.style.display = 'none';

                    if (json.success && json.data && json.data.length > 0) {
                        renderTable(json.data, json.current_page);
                        renderPagination(json.current_page, json.last_page);
                    } else {
                        tableBody.innerHTML =
                            `<tr><td colspan="9" class="text-warning text-center">Tidak ditemukan</td></tr>`;
                    }
                } catch (err) {
                    spinner.style.display = 'none';
                    tableBody.innerHTML =
                        `<tr><td colspan="9" class="text-danger text-center">Gagal memuat data (401/404)</td></tr>`;
                    console.error('Fetch error:', err);
                }
            }

            function renderTable(data, currentPage) {
                if (!data || data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>`;
                    return;
                }

                let rows = '';
                data.forEach((item, i) => {
                    const no = (i + 1) + (currentPage - 1) * 10;

                    const berat = item.last_weight ?
                        parseFloat(item.last_weight).toFixed(0) + ' g' :
                        '-';

                    // Format tanggal dari created_at
                    const createdAt = item.created_at ?
                        new Date(item.created_at).toLocaleDateString('id-ID') :
                        '-';

                    rows += `
                        <tr>
                            <td>${no}</td>
                            <td>${item.name || '-'}</td>
                            <td>${item.leather_type || '-'}</td>
                            <td>${item.size || '-'}</td>
                            <td>${item.stitching_type || '-'}</td>
                            <td>${item.lining_material || '-'}</td>
                            <td>${createdAt}</td>
                            <td>${berat}</td>
                        </tr>
                    `;
                });

                tableBody.innerHTML = rows;
            }

            function renderPagination(currentPage, lastPage) {
                if (lastPage <= 1) return pagination.innerHTML = '';

                let html = `<ul class="pagination justify-content-center">`;

                html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                    </li>`;

                for (let i = 1; i <= lastPage; i++) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }

                html += `<li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                    </li>`;
                html += `</ul>`;

                pagination.innerHTML = html;

                pagination.querySelectorAll('a[data-page]').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        const page = parseInt(e.target.dataset.page);
                        if (page > 0 && page <= lastPage) fetchData(page);
                    });
                });
            }

            // Event tombol timbang untuk mengisi modal
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-timbang');
                if (!btn) return;

                const item = JSON.parse(btn.dataset.item);
                const modalElement = document.getElementById('timbangModal');
                const modal = new bootstrap.Modal(modalElement);

                // Isi form modal
                modalElement.querySelector('#info_name').value = item.name || '';
                modalElement.querySelector('#info_description').value = item.description || '';
                modalElement.querySelector('#info_type').value = item.leather_type || '';
                modalElement.querySelector('#info_size').value = item.size || '';
                modalElement.querySelector('#info_stitching').value = item.stitching_type || '';
                modalElement.querySelector('#info_lining').value = item.lining_material || '';

                modal.show();
            });

            const wifiModalEl = document.getElementById('wifi');
            const loadingEl = document.getElementById("wifiLoading");
            const loadingTextEl = document.getElementById("wifiLoadingText");
            const progressBar = document.getElementById("wifiProgressBar");

            // Show config
            wifiModalEl.addEventListener('show.bs.modal', async function() {
                try {
                    const res = await fetch("/user/wifi/config");
                    if (res.ok) {
                        const data = await res.json();
                        document.getElementById("ssidInput").value = data.ssid || '';
                        document.getElementById("passInput").value = data.password || '';
                    }
                } catch (e) {
                    console.error(e);
                }
            });

            // Handle save
            document.getElementById("wifiForm").addEventListener("submit", async function(e) {
                e.preventDefault();
                const ssid = document.getElementById("ssidInput").value;
                const password = document.getElementById("passInput").value;

                // tampilkan loading
                loadingEl.style.display = "flex";
                loadingTextEl.innerText = "Mengirim ke server...";
                progressBar.style.width = "10%";

                try {
                    const res = await fetch("/user/wifi/update", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content
                        },
                        body: JSON.stringify({
                            ssid,
                            password
                        })
                    });

                    if (!res.ok) {
                        alert("Gagal update WiFi ESP!");
                        loadingEl.style.display = "none";
                        return;
                    }

                    // server oke -> ESP sedang reconnect
                    loadingTextEl.innerText = "ESP melakukan reconnect...";
                    progressBar.style.width = "35%";

                    // cek heartbeat setiap 3 detik apakah sudah pakai SSID baru
                    let attempt = 0;
                    let maxAttempt = 12; // ~ 36 detik

                    const checkInterval = setInterval(async () => {
                        progressBar.style.width = (35 + attempt * 5) + "%";

                        attempt++;

                        const hb = await fetch("/user/esp/check-latest");
                        if (hb.ok) {
                            const d = await hb.json();
                            if (d.wifi_ssid === ssid) {
                                clearInterval(checkInterval);
                                loadingTextEl.innerText = "ESP berhasil terhubung!";
                                progressBar.style.width = "100%";

                                setTimeout(() => {
                                    loadingEl.style.display = "none";
                                    bootstrap.Modal.getInstance(wifiModalEl).hide();
                                    Swal.fire("Berhasil!", "ESP sudah terkoneksi ke WiFi baru.",
                                        "success");
                                }, 600);

                                return;
                            }
                        }

                        if (attempt >= maxAttempt) {
                            clearInterval(checkInterval);
                            loadingEl.style.display = "none";
                            Swal.fire("Timeout", "ESP terlalu lama reconnect. Coba cek WiFi-nya.",
                                "warning");
                        }

                    }, 3000);

                } catch (err) {
                    loadingEl.style.display = "none";
                    alert("Error: " + err.message);
                }
            });

            // Device
            let currentDeviceId = null;

            // Ambil user id dari Blade
            const userId = {{ Auth::check() ? Auth::id() : 'null' }};

            async function loadAvailableDevices() {
                try {
                    const res = await fetch('/user/devices/available');
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);

                    const devices = await res.json();
                    const list = document.getElementById('deviceList');
                    list.innerHTML = '';

                    // Ambil device user login (in_use)
                    const currentUserDevice = devices.find(d => d.status === 'in_use' && d.user_id === parseInt(userId));
                    if (currentUserDevice) {
                        currentDeviceId = currentUserDevice.id;
                        document.getElementById('currentDeviceName').textContent =
                            currentUserDevice.name || currentUserDevice.esp_id;
                    } else {
                        currentDeviceId = null;
                        document.getElementById('currentDeviceName').textContent = 'Pilih Device...';
                    }

                    devices.forEach(device => {
                        const isCurrent = device.id === currentDeviceId;
                        const statusBadge = device.status === 'in_use' ? 'Sedang Dipakai' :
                            device.status === 'online' ? 'Online' : 'Offline';
                        const bgClass = device.status === 'in_use' ? 'bg-success text-white' :
                            device.status === 'online' ? 'bg-light text-dark' : 'bg-danger text-white';

                        const item = document.createElement('li');
                        item.innerHTML = `
                            <a class="dropdown-item d-flex justify-content-between align-items-center ${isCurrent ? 'active' : ''}" 
                            href="javascript:void(0)" 
                            onclick="prepareSwitch(${device.id}, '${(device.name || device.esp_id).replace(/'/g, "\\'")}', '${device.esp_id}')"
                            style="background-color: ${bgClass.includes('bg-light') ? '#f8f9fa' : ''}; color: ${bgClass.includes('text-white') ? '#fff' : '#000'};">
                                <div>
                                    <div><strong>${device.name || device.esp_id}</strong></div>
                                    <small class="text-muted">ID: ${device.esp_id}</small>
                                </div>
                                <span class="badge ${bgClass} ms-2">${statusBadge}</span>
                            </a>
                        `;
                        list.appendChild(item);
                    });

                } catch (err) {
                    console.error("Gagal load device:", err);
                    document.getElementById('deviceList').innerHTML =
                        '<li><a class="dropdown-item text-danger text-center" href="#">Error loading devices</a></li>';
                }
            }

            function prepareSwitch(id, name, esp_id) {
                if (id == currentDeviceId) {
                    alert("Kamu sudah menggunakan device ini.");
                    return;
                }

                document.getElementById('targetDeviceName').textContent = name || esp_id;
                document.getElementById('targetDeviceId').textContent = esp_id;

                const modal = new bootstrap.Modal(document.getElementById('confirmSwitchModal'));
                modal.show();

                document.getElementById('confirmSwitchBtn').onclick = () => switchDevice(id);
            }

            async function switchDevice(deviceId) {
                try {
                    const res = await fetch('/user/devices/switch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            device_id: deviceId
                        })
                    });

                    if (!res.ok) {
                        const text = await res.text();
                        console.error("Response:", text);
                        throw new Error("Server error");
                    }

                    const data = await res.json();

                    if (data.success) {
                        Swal.fire('Sukses!', 'Berhasil pindah devicem!', 'success').then(() => {
                            // Redirect sesuai tipe device
                            const type = data.device_type;
                            if (type === 'O') {
                                window.location.href = '/user/ordersheet-view';
                            } else if (type === 'P') {
                                window.location.href = '/user/package-view';
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire('Error', 'Tidak dapat terhubung ke server', 'error');
                }
            }

            // Load saat halaman dibuka & refresh tiap 15 detik
            loadAvailableDevices();
            setInterval(loadAvailableDevices, 10000);
        </script>
    @endpush

</x-layout.home>
