<x-layout.home title="Rekap Ordersheet">

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
            <h5 class="welcome-message">Rekap Timbangan Ordersheet</h5>
        @elseif ($deviceType === 'P')
            <h5 class="welcome-message">Rekap Timbangan Package</h5>
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
                        <table class="table table-striped table-bordered align-middle text-center" id="resultTable"
                            style="white-space: nowrap">
                            <thead class="table-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Order Code</th>
                                    <th>Buyer</th>
                                    <th>PO</th>
                                    <th>No. Box</th>
                                    <th>Weight</th>
                                    <th>Qty</th>
                                    <th>OPT QC Timbangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rekapbesar as $item)
                                    <tr>
                                        <td>{{ $loop->iteration + ($rekapbesar->currentPage() - 1) * $rekapbesar->perPage() }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }}</td>
                                        <td>{{ $item->Order_code }}</td>
                                        <td>{{ $item->Buyer }}</td>
                                        <td>{{ $item->PO }}</td>
                                        <td>
                                            @foreach ($item->timbangans as $t)
                                                {{ $t->no_box }}<br>
                                            @endforeach
                                        </td>
                                        <td class="text-primary fw-bold">
                                            @foreach ($item->timbangans as $t)
                                                {{ $t->berat }} kg<br>
                                            @endforeach
                                        </td>
                                        <td>{{ $item->Qty_order }}</td>
                                        <td>{{ $item->OPT_QC_TIMBANGAN ?? '-' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match ($item->status) {
                                                    'Success' => 'bg-success',
                                                    'Pending' => 'bg-warning text-dark',
                                                    'Failed' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ $item->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            Silakan cari data untuk memulai timbangan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $rekapbesar->links('pagination::bootstrap-5') }}
                    </div>

                    <nav id="pagination" class="d-flex justify-content-center mt-3"></nav>
                </div>
            </div>
        </section>
    </div>

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2/sweetalert2.all.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initDateTime()
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
                const spinner = document.getElementById('loadingSpinner');
                const tableBody = document.querySelector('#resultTable tbody');
                const pagination = document.getElementById('pagination');

                // Event klik tombol Cari
                searchBtn.addEventListener('click', () => fetchData(1));

                async function fetchData(page = 1) {
                    const search = document.getElementById('search')?.value.trim() || '';
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
                        if (start) params.append('start_date', start);
                        if (end) params.append('end_date', end);

                        const res = await fetch(`/user/rekap/get-Rekapdata?${params}`);
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
                            <td>${day}/${month}/${year}</td>
                            <td>${hours}:${minutes}</td>
                            <td>${item.Order_code || '-'}</td>
                            <td>${item.Buyer || '-'}</td>
                            <td>${item.PO || '-'}</td>
                            <td>${semuaBox}</td>
                            <td>${semuaBerat}</td>
                            <td>${item.Qty_order || 0}</td>
                            <td>${item.OPT_QC_TIMBANGAN || '-'}</td>
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
        </script>
    @endpush

</x-layout.home>
