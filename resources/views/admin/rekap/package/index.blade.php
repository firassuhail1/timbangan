<x-layout.home title="Rekap Ordersheet">

    <div class="page-heading d-flex justify-content-between align-items-center">
        <h5 class="welcome-message">Rekap Package</h5>

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
                            style="min-width: 1200px; white-space: nowrap;">
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
                                <!-- Isi via AJAX atau default dari controller -->
                                @forelse ($rekap_package as $item)
                                    <!-- ... isi row sama seperti sebelumnya ... -->
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
                        {{ $rekap_package->links('pagination::bootstrap-5') }}
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
        </script>
    @endpush
</x-layout.home>
