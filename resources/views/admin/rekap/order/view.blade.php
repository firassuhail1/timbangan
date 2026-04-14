<x-layout.home title="Rekap Ordersheet">

    <div class="page-heading d-flex justify-content-between align-items-center">
        <h5 class="welcome-message">Rekap Ordersheet</h5>

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

                    <div class="row g-3 align-items-end">

                        <!-- Cari -->
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label fw-semibold">Cari</label>
                            <input type="text" id="search" class="form-control"
                                placeholder="Order code, buyer, PO...">
                        </div>

                        <!-- User -->
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label fw-semibold">User</label>
                            <select id="user_id" class="form-select">
                                <option value="">Semua User</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->username }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Periode -->
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label fw-semibold">Periode</label>
                            <select id="periode" class="form-select">
                                <option value="custom">Custom Range</option>
                                <option value="today">Hari ini</option>
                                <option value="this_month">Bulan ini</option>
                                <option value="3months">3 Bulan terakhir</option>
                                <option value="6months">6 Bulan terakhir</option>
                                <option value="1year">1 Tahun terakhir</option>
                            </select>
                        </div>

                        <!-- Tanggal Mulai -->
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" id="start_date" class="form-control">
                        </div>

                        <!-- Tanggal Akhir -->
                        <div class="col-xl-4 col-md-6">
                            <label class="form-label fw-semibold">Tanggal Akhir</label>
                            <input type="date" id="end_date" class="form-control">
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-xl-4 col-md-12">
                            <div class="d-flex flex-wrap gap-2 justify-content-xl-end">

                                <button type="button" id="searchBtn" class="btn btn-primary px-4">
                                    <i class="bi bi-search me-1"></i> Cari
                                </button>

                                <button type="button" id="exportBtn" class="btn btn-success px-4">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                </button>

                            </div>
                        </div>

                    </div>

                    <hr>

                    <!-- Loading Spinner -->
                    <div class="text-center my-4">
                        <div class="spinner-border text-primary" id="loadingSpinner"
                            style="display:none; width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Tabel Hasil -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle text-center" id="resultTable"
                            style="white-space: nowrap;">
                            <thead class="table-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>Timbangan</th>
                                    <th>OPT QC Timbangan</th>
                                    <th>Tanggal / Jam</th>
                                    <th>Order Code</th>
                                    <th>Buyer</th>
                                    <th>PO</th>
                                    <th>No. Box</th>
                                    <th>Weight</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Isi via AJAX atau default dari controller -->
                                @forelse ($rekap_order as $item)
                                    <tr>
                                        <td>
                                            {{ $loop->iteration + ($rekap_order->currentPage() - 1) * $rekap_order->perPage() }}
                                        </td>
                                        <td>
                                            {{ $item->device->esp_id ?? '-' }}
                                        </td>
                                        <td>{{ $item->user->username ?? '-' }}</td>
                                        <td>
                                            {{ $item->created_at->format('d-m-Y') }} / <br>
                                            {{ $item->created_at->format('H:i') }}
                                        </td>
                                        <td>{{ $item->Order_code }}</td>
                                        <td>{{ $item->Buyer }}</td>
                                        <td>{{ $item->PO }}</td>
                                        <td>
                                            @foreach ($item->timbangans as $timbang)
                                                {{ $timbang->no_box }}<br>
                                            @endforeach
                                        </td>
                                        <td class="text-primary fw-bold">
                                            @foreach ($item->timbangans as $timbang)
                                                {{ number_format($timbang->berat, 2) }} kg<br>
                                            @endforeach
                                        </td>
                                        <td>{{ $item->Qty_order }}</td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-circle-check"></i> {{ $item->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-5">
                                            Silakan gunakan filter di atas untuk menampilkan data
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $rekap_order->links('pagination::bootstrap-5') }}
                    </div>

                    <nav id="pagination" class="d-flex justify-content-center mt-3"></nav>

                </div>
            </div>
        </section>
    </div>

    @push('css')
    @endpush

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2/sweetalert2.all.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initDateTime();
                initSearch();
            });

            function initDateTime() {
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

                function updateDateTime() {
                    const now = new Date();
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

                updateDateTime(); // jalankan sekali saat load
                setInterval(updateDateTime, 1000); // update tiap detik
            }

            function initSearch() {
                const searchBtn = document.getElementById('searchBtn');
                const periodeSelect = document.getElementById('periode');
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const spinner = document.getElementById('loadingSpinner');
                const tableBody = document.querySelector('#resultTable tbody');
                const pagination = document.getElementById('pagination');

                // Fungsi untuk mengatur tanggal otomatis berdasarkan periode
                function setDateRange(period) {
                    const today = new Date();
                    let start = new Date();

                    switch (period) {
                        case 'today':
                            start = today;
                            break;
                        case 'this_month':
                            start = new Date(today.getFullYear(), today.getMonth(), 1);
                            break;
                        case '3months':
                            start.setMonth(today.getMonth() - 3);
                            break;
                        case '6months':
                            start.setMonth(today.getMonth() - 6);
                            break;
                        case '1year':
                            start.setFullYear(today.getFullYear() - 1);
                            break;
                        default:
                            return; // custom → biarkan user isi sendiri
                    }

                    startDateInput.value = start.toISOString().split('T')[0];
                    endDateInput.value = today.toISOString().split('T')[0];
                }

                // Ketika pilihan periode berubah
                periodeSelect.addEventListener('change', function() {
                    const val = this.value;
                    if (val !== 'custom') {
                        setDateRange(val);
                    }
                });

                // Default: tampilkan bulan ini saat halaman pertama kali dibuka
                if (!startDateInput.value && !endDateInput.value) {
                    setDateRange('this_month');
                    periodeSelect.value = 'this_month';
                }

                // Event klik tombol Cari
                searchBtn.addEventListener('click', () => fetchData(1));

                async function fetchData(page = 1) {
                    const search = document.getElementById('search')?.value.trim() || '';
                    const userId = document.getElementById('user_id')?.value || '';
                    const start = document.getElementById('start_date')?.value || '';
                    const end = document.getElementById('end_date')?.value || '';

                    spinner.style.display = 'inline-block';
                    tableBody.innerHTML = `<tr><td colspan="11" class="text-center">Memuat...</td></tr>`;
                    pagination.innerHTML = '';

                    try {
                        const params = new URLSearchParams({
                            page
                        });
                        if (search) params.append('search', search);
                        if (userId) params.append('user_id', userId);
                        if (start) params.append('start_date', start);
                        if (end) params.append('end_date', end);

                        const res = await fetch(`/admin/rekap/order-data?${params}`);
                        const json = await res.json();

                        spinner.style.display = 'none';

                        if (json.data && json.data.length > 0) {
                            renderTable(json.data, json.current_page);
                            renderPagination(json.current_page, json.last_page);
                        } else {
                            tableBody.innerHTML =
                                `<tr><td colspan="11" class="text-warning text-center">Tidak ditemukan</td></tr>`;
                        }
                    } catch (err) {
                        spinner.style.display = 'none';
                        tableBody.innerHTML =
                            `<tr><td colspan="11" class="text-danger text-center">Terjadi kesalahan</td></tr>`;
                        console.error(err);
                    }
                }

                function getStatusBadge(status) {
                    switch (status) {
                        case 'Success':
                            return '<span class="badge bg-success">Success</span>';
                        case 'Pending':
                            return '<span class="badge bg-warning text-dark">Pending</span>';
                        case 'Failed':
                            return '<span class="badge bg-danger">Failed</span>';
                        default:
                            return '<span class="badge bg-secondary">' + (status || '-') + '</span>';
                    }
                }

                function renderTable(data, currentPage) {
                    let rows = '';
                    data.forEach((item, i) => {
                        const no = (i + 1) + (currentPage - 1) * 10;
                        const dt = new Date(item.created_at);
                        const day = dt.getDate().toString().padStart(2, '0');
                        const month = (dt.getMonth() + 1).toString().padStart(2, '0');
                        const year = dt.getFullYear();
                        const hours = dt.getHours().toString().padStart(2, '0');
                        const minutes = dt.getMinutes().toString().padStart(2, '0');

                        const semuaBox = item.timbangans.length > 0 ?
                            item.timbangans.map(t => t.no_box).join('<br>') :
                            '-';
                        const semuaBerat = item.timbangans.length > 0 ?
                            item.timbangans.map(t => `${t.berat} kg`).join('<br>') :
                            '-';

                        rows += `
                        <tr>
                            <td>${no}</td>
                            <td>${item.device?.esp_id || '-'}</td>
                            <td>${item.user?.username || '-'}</td>
                            <td>${day}-${month}-${year} <br> ${hours}:${minutes}</td>
                            <td>${item.Order_code || '-'}</td>
                            <td>${item.Buyer || '-'}</td>
                            <td>${item.PO || '-'}</td>
                            <td>${semuaBox}</td>
                            <td>${semuaBerat}</td>
                            <td>${item.Qty_order || 0}</td>
                            <td>${getStatusBadge(item.status)}</td>
                        </tr>`;
                    });
                    tableBody.innerHTML = rows;
                }

                function renderPagination(currentPage, lastPage) {
                    if (lastPage <= 1) {
                        pagination.innerHTML = '';
                        return;
                    }

                    let html = `<ul class="pagination justify-content-center">`;
                    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage-1}">Previous</a>
                 </li>`;
                    for (let i = 1; i <= lastPage; i++) {
                        html += `<li class="page-item ${i===currentPage?'active':''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
                    }
                    html += `<li class="page-item ${currentPage===lastPage?'disabled':''}">
                    <a class="page-link" href="#" data-page="${currentPage+1}">Next</a>
                 </li></ul>`;
                    pagination.innerHTML = html;

                    pagination.querySelectorAll('a[data-page]').forEach(link => {
                        link.addEventListener('click', e => {
                            e.preventDefault();
                            const page = parseInt(link.dataset.page);
                            if (page > 0 && page <= lastPage) fetchData(page);
                        });
                    });
                }
            }

            const exportBtn = document.getElementById('exportBtn');

            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    const search = document.getElementById('search')?.value.trim() || '';
                    const userId = document.getElementById('user_id')?.value || '';
                    const start = document.getElementById('start_date')?.value || '';
                    const end = document.getElementById('end_date')?.value || '';

                    const params = new URLSearchParams();
                    if (search) params.append('search', search);
                    if (userId) params.append('user_id', userId);
                    if (start) params.append('start_date', start);
                    if (end) params.append('end_date', end);

                    const url = `/admin/rekap/order/export?${params.toString()}`;

                    console.log('Export URL:', url); // ← untuk debug

                    // Optional: tampilkan pesan sementara
                    // alert('Sedang menyiapkan file Excel...');

                    window.location.href = url;
                });
            } else {
                console.warn('Tombol exportBtn tidak ditemukan di halaman');
            }
        </script>
    @endpush
</x-layout.home>
