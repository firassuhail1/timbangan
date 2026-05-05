<x-layout.home title="Upload Firmware">

    <div class="page-heading d-flex justify-content-between align-items-center">
        <div class="judul">
            <h5 class="welcome-message">Firmware</h5>
            <h6 class="fw-bold">ESP 32</h6>
        </div>
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
                    <div class="title d-flex justify-content-between mb-2">
                        <h5>Riwayat File Firmware ESP</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambah">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                    </div>
                    <hr>
                    <form action="{{ route('admin.view-firmware') }}" method="GET" class="row g-3 align-items-end">

                        {{-- Entries --}}
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="entries" class="form-label fw-semibold small mb-1">Tampil</label>
                            <select name="entries" id="entries" class="form-select form-select-sm">
                                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>

                        {{-- Search --}}
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="search" class="form-label fw-semibold small mb-1">Cari</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" id="search" class="form-control"
                                    placeholder="Cari firmware..." value="{{ $search }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                    </form>

                    <div class="mt-2">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Validasi Gagal!</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered text-center" style="white-space: nowrap">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Version</th>
                                    <th>Device Type</th>
                                    <th>File Name</th>
                                    <th>File Path</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($firmware->isEmpty())
                                    <tr>
                                        <td class="text-center" colspan="8">Belum ada file</td>
                                    </tr>
                                @else
                                    <tr>
                                        @foreach ($firmware as $item)
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->version }}</td>
                                            <td>
                                                @if ($item->device_type == 'P')
                                                    <span class="badge bg-primary">Timbangan Package</span>
                                                @elseif($item->device_type == 'O')
                                                    <span class="badge bg-secondary">Timbangan Ordersheet</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->file_name }}</td>
                                            <td>
                                                {{-- download --}}
                                                <a href="{{ route('admin.firmware.download', $item->id) }}"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="fa-solid fa-download"></i> Download
                                                </a>
                                            </td>
                                            <td>
                                                @php
                                                    $badgeClass = match ($item->status) {
                                                        'draft' => 'secondary',
                                                        'uploaded' => 'primary',
                                                        'published' => 'success',
                                                        'expired' => 'danger',
                                                        default => 'dark',
                                                    };
                                                @endphp

                                                <span class="badge bg-{{ $badgeClass }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>

                                            <td>
                                                <button type="button" data-bs-toggle="modal"
                                                    data-bs-target="#posting{{ $item->id }}"
                                                    class="btn btn-outline-info btn-sm" data-bs-title="Post">
                                                    <i class="fa-solid fa-file-arrow-up"></i> Edit
                                                </button>

                                                <button type="button" data-bs-toggle="modal"
                                                    data-bs-target="#hapus{{ $item->id }}"
                                                    class="btn btn-outline-danger btn-sm btn-hapus-firmware"
                                                    data-status="{{ $item->status }}" data-bs-title="Hapus">
                                                    <i class="fa-solid fa-trash"></i> Hapus
                                                </button>
                                            </td>
                                        @endforeach
                                    <tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex mt-2 justify-content-end">
                        {{ $firmware->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </section>

        {{-- Section Device Tracking --}}
        <section class="row mt-4">
            <div class="card">
                <div class="card-body">
                    <div class="title d-flex justify-content-between mb-2">
                        <h5>Status Firmware Per Device</h5>
                        <small class="text-muted align-self-center">
                            Update otomatis setiap device kirim data
                        </small>
                    </div>
                    <hr>

                    @if($devices->isEmpty())
                        <p class="text-muted text-center">Belum ada device terdaftar.</p>
                    @else
                        {{-- Group by device_type --}}
                        @foreach($devices->groupBy('device_type') as $type => $group)
                            @php
                                $published = $publishedFirmwares->get($type);
                                $totalUpdated = $group->where('is_updated', true)->count();
                                $totalDevice  = $group->count();
                            @endphp

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0">
                                        @if($type === 'O')
                                            <span class="badge bg-secondary me-1">O</span> Timbangan Ordersheet
                                        @elseif($type === 'P')
                                            <span class="badge bg-primary me-1">P</span> Timbangan Package
                                        @else
                                            <span class="badge bg-dark me-1">{{ $type }}</span>
                                        @endif
                                    </h6>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($published)
                                            <small class="text-muted">
                                                Versi published: 
                                                <span class="badge bg-success">{{ $published->version }}</span>
                                            </small>
                                        @else
                                            <small class="text-muted">
                                                <span class="badge bg-warning text-dark">Tidak ada firmware published</span>
                                            </small>
                                        @endif
                                        <small class="text-muted">
                                            {{ $totalUpdated }}/{{ $totalDevice }} device sudah update
                                        </small>
                                    </div>
                                </div>

                                {{-- Progress bar --}}
                                @if($published && $totalDevice > 0)
                                    @php $percent = round(($totalUpdated / $totalDevice) * 100) @endphp
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                                    </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered text-center" style="white-space: nowrap">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>ESP ID</th>
                                                <th>Nama</th>
                                                <th>Versi Saat Ini</th>
                                                <th>Status Update</th>
                                                <th>Status Device</th>
                                                <th>Terakhir Online</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group as $i => $device)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td>
                                                        <code>{{ $device->esp_id }}</code>
                                                    </td>
                                                    <td>{{ $device->name ?? '-' }}</td>
                                                    <td>
                                                        @if($device->current_firmware_version)
                                                            <span class="badge bg-secondary">
                                                                {{ $device->current_firmware_version }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(is_null($device->is_updated))
                                                            <span class="badge bg-secondary">Tidak diketahui</span>
                                                        @elseif($device->is_updated)
                                                            <span class="badge bg-success">
                                                                <i class="fa-solid fa-check me-1"></i>Terbaru
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fa-solid fa-arrow-up me-1"></i>Perlu Update
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $badgeStatus = match($device->status) {
                                                                'online'  => 'success',
                                                                'in_use'  => 'primary',
                                                                'offline' => 'danger',
                                                                default   => 'secondary',
                                                            };
                                                        @endphp
                                                        <span class="badge bg-{{ $badgeStatus }}">
                                                            {{ ucfirst($device->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($device->last_seen_at)
                                                            <small>
                                                                {{ \Carbon\Carbon::parse($device->last_seen_at)
                                                                    ->timezone('Asia/Jakarta')
                                                                    ->format('d M Y H:i:s') }}
                                                            </small>
                                                        @else
                                                            <small class="text-muted">Belum pernah online</small>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </section>
    </div>

    @include('admin.master.device.create')
    @include('admin.master.device.edit')
    @include('admin.master.device.delete')

    @push('css')
    @endpush

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

            document.addEventListener('DOMContentLoaded', function() {

                // tangkap semua tombol hapus
                document.querySelectorAll('.btn-hapus-firmware').forEach(button => {

                    button.addEventListener('click', function(e) {

                        const status = this.getAttribute('data-status');

                        // Jika sudah published → blokir & tampilkan alert
                        if (status === 'published') {

                            e.preventDefault(); // penting: cegah modal terbuka
                            e.stopPropagation();

                            Swal.fire({
                                icon: 'warning',
                                title: 'Tidak dapat dihapus',
                                html: `Firmware versi <b>${this.getAttribute('data-version') || 'ini'}</b><br>
                                   sudah berstatus <b>published</b>.<br><br>
                                   <small>Ganti status published terlebih dahulu jika ingin menghapus.</small>`,
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'Mengerti',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            });

                            return false;
                        }

                        // Jika bukan published → boleh lanjut buka modal konfirmasi biasa
                        // tidak perlu lakukan apa-apa lagi di sini

                    });
                });

            });
        </script>
    @endpush

</x-layout.home>
