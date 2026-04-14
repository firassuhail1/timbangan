<x-layout.home title="Dashboard">

    <div class="page-heading d-flex justify-content-between align-items-center">
        <div>
            <h3>Dashboard</h3>
            <h5 class="welcome-message">Selamat Datang,
                <span class="text-warning">{{ Auth::user()->username ?? '-' }}</span>
            </h5>
        </div>

        {{-- Bagian waktu di sebelah kanan --}}
        <div class="text-end">
            <h6 id="current-day" class="mb-0 fw-bold"></h6>
            <small id="current-time" class="text-muted"></small>
        </div>
    </div>

    <hr>

    <div class="page-content">
        <section class="row">

            {{-- ===== STATISTIK CARD ===== --}}
            <div class="row g-4 mb-4">

                {{-- Total Ordersheet --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper bg-primary-subtle text-primary me-3">
                                <i class="fa-solid fa-box"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Total Ordersheet</p>
                                <h4 class="fw-bold mb-0">{{ number_format($totalOrdersheet) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total Timbangan --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper bg-info-subtle text-info me-3">
                                <i class="fa-solid fa-dolly"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Total Box Ditimbang</p>
                                <h4 class="fw-bold mb-0">{{ number_format($totalTimbangan) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Success --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper bg-success-subtle text-success me-3">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Box Sukses</p>
                                <h4 class="fw-bold mb-0 text-success">{{ number_format($totalSuccess) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rejected --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper bg-danger-subtle text-danger me-3">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small">Box Rejected</p>
                                <h4 class="fw-bold mb-0 text-danger">{{ number_format($totalRejected) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <hr>

            {{-- ===== GRAFIK ===== --}}
            <div class="col-12 col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5>Grafik Timbangan per Ordersheet</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartTimbangan"></canvas>
                    </div>
                </div>
            </div>

            <hr>

            {{-- ===== TABEL RINGKAS ===== --}}
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5>Ordersheet Terbaru</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-bordered text-center">
                            <thead class="table-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Buyer</th>
                                    <th>PO</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestOrders as $order)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $order->Order_code }}</td>
                                        <td>{{ $order->Buyer }}</td>
                                        <td>{{ $order->PO }}</td>
                                        <td>{{ $order->Qty_order }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </section>
    </div>

    @push('css')
        <style>
            .card h3 {
                font-size: 1.8rem;
            }

            .stat-card {
                transition: all 0.25s ease;
                border-radius: 12px;
            }

            .stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            }

            .icon-wrapper {
                width: 80px;
                height: 80px;
                border-radius: 20px;
                font-size: 32px;
            }
        </style>
    @endpush

    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
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

            const ctx = document.getElementById('chartTimbangan');
            const chartTimbangan = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Total Box Ditimbang',
                        data: {!! json_encode($chartData) !!},
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            document.querySelectorAll('.stat-card h4').forEach(el => {
                let target = parseInt(el.innerText.replace(/,/g, ''));
                let count = 0;
                let step = Math.ceil(target / 50);

                let interval = setInterval(() => {
                    count += step;
                    if (count >= target) {
                        el.innerText = target.toLocaleString();
                        clearInterval(interval);
                    } else {
                        el.innerText = count.toLocaleString();
                    }
                }, 20);
            });
        </script>
    @endpush

</x-layout.home>
