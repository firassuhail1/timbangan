<x-layout.home title="Manajemen User">

    <div class="page-heading d-flex justify-content-between align-items-center">
        <div class="judul">
            <h5 class="welcome-message">Master Data</h5>
            <h6 class="fw-bold">Manajemen User</h6>
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
                        <h5>Daftar User</h5>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah
                        </a>
                    </div>
                    <hr>

                    {{-- Filter Form --}}
                    <form action="{{ route('admin.users.index') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="entries" class="form-label fw-semibold small mb-1">Tampil</label>
                            <select name="entries" id="entries" class="form-select form-select-sm">
                                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="search" class="form-label fw-semibold small mb-1">Cari</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" id="search" class="form-control"
                                    placeholder="Cari username, line, role..." value="{{ $search }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Flash Messages --}}
                    <div class="mt-2">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered text-center" style="white-space: nowrap">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Foto</th>
                                    <th>Username</th>
                                    <th>Line</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($users->isEmpty())
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            <i class="fas fa-users me-1"></i> Belum ada data user
                                        </td>
                                    </tr>
                                @else
                                    @foreach ($users as $user)
                                        <tr>
                                            <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}
                                            </td>
                                            <td>
                                                @if ($user->foto)
                                                    <img src="{{ asset('storage/' . $user->foto) }}"
                                                        alt="Foto {{ $user->username }}" class="rounded-circle"
                                                        style="width: 36px; height: 36px; object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white"
                                                        style="width: 36px; height: 36px; font-size: 14px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-start">{{ $user->username }}</td>
                                            <td>{{ $user->line ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $roleBadge = match ($user->role) {
                                                        'admin' => 'danger',
                                                        'operator' => 'warning',
                                                        default => 'secondary',
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $roleBadge }}">
                                                    {{ ucfirst($user->role ?? 'user') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $user->status === 'Aktif' ? 'success' : 'danger' }}">
                                                    {{ $user->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                                    class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>

                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm btn-hapus-user"
                                                    data-id="{{ $user->id }}"
                                                    data-username="{{ $user->username }}"
                                                    data-self="{{ $user->id === auth()->id() ? '1' : '0' }}">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>

                                                {{-- Hidden delete form --}}
                                                <form id="form-hapus-{{ $user->id }}"
                                                    action="{{ route('admin.users.destroy', $user->id) }}"
                                                    method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex mt-2 justify-content-end">
                        {{ $users->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // DateTime
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
            document.addEventListener('DOMContentLoaded', () => {
                updateDateTime();
                setInterval(updateDateTime, 1000);
            });

            // Konfirmasi hapus
            document.querySelectorAll('.btn-hapus-user').forEach(button => {
                button.addEventListener('click', function() {
                    const isSelf = this.getAttribute('data-self') === '1';
                    const username = this.getAttribute('data-username');
                    const id = this.getAttribute('data-id');

                    if (isSelf) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tidak dapat dihapus',
                            html: `Anda tidak dapat menghapus akun Anda sendiri.`,
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'Mengerti',
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'question',
                        title: 'Hapus User?',
                        html: `Yakin ingin menghapus user <b>${username}</b>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal',
                    }).then(result => {
                        if (result.isConfirmed) {
                            document.getElementById(`form-hapus-${id}`).submit();
                        }
                    });
                });
            });
        </script>
    @endpush

</x-layout.home>
