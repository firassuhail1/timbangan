<x-layout.home title="Edit User">

    <div class="page-heading d-flex justify-content-between align-items-center">
        <div class="judul">
            <h5 class="welcome-message">Master Data</h5>
            <h6 class="fw-bold">Edit User</h6>
        </div>
        <div class="text-end">
            <h6 id="current-day" class="mb-0 fw-bold"></h6>
            <small id="current-time" class="text-muted"></small>
        </div>
    </div>

    <hr>

    <div class="page-content">
        <section class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">

                        <div class="title d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Form Edit User</h5>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <hr>

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong><i class="fas fa-exclamation-triangle me-1"></i> Validasi Gagal!</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">

                                {{-- Foto Preview --}}
                                <div class="col-12 text-center mb-2">
                                    <div class="d-inline-block position-relative">
                                        <img id="foto-preview"
                                            src="{{ $user->foto ? asset('storage/' . $user->foto) : asset('images/default-avatar.png') }}"
                                            alt="Foto {{ $user->username }}" class="rounded-circle border"
                                            style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;"
                                            onclick="document.getElementById('foto').click()">
                                        <span class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-1"
                                            style="cursor: pointer;" onclick="document.getElementById('foto').click()">
                                            <i class="fas fa-camera text-white" style="font-size: 12px;"></i>
                                        </span>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">Klik foto untuk mengganti (opsional, maks.
                                            2MB)</small>
                                    </div>
                                    <input type="file" name="foto" id="foto" class="d-none"
                                        accept="image/jpg,image/jpeg,image/png,image/webp">
                                    @error('foto')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Username --}}
                                <div class="col-12 col-md-6">
                                    <label for="username" class="form-label fw-semibold small mb-1">
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="username" id="username"
                                        class="form-control form-control-sm @error('username') is-invalid @enderror"
                                        value="{{ old('username', $user->username) }}" placeholder="Masukkan username"
                                        required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Line --}}
                                <div class="col-12 col-md-6">
                                    <label for="line" class="form-label fw-semibold small mb-1">
                                        Line <small class="text-muted fw-normal">(opsional)</small>
                                    </label>
                                    <input type="text" name="line" id="line"
                                        class="form-control form-control-sm @error('line') is-invalid @enderror"
                                        value="{{ old('line', $user->line) }}" placeholder="Contoh: Line A, Line B">
                                    @error('line')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Role --}}
                                <div class="col-12 col-md-6">
                                    <label for="role" class="form-label fw-semibold small mb-1">
                                        Role <span class="text-danger">*</span>
                                    </label>
                                    <select name="role" id="role"
                                        class="form-select form-select-sm @error('role') is-invalid @enderror" required>
                                        <option value="admin"
                                            {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin
                                        </option>
                                        <option value="operator"
                                            {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>Operator
                                        </option>
                                        <option value="user"
                                            {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User
                                        </option>
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Status --}}
                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label fw-semibold small mb-1">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select name="status" id="status"
                                        class="form-select form-select-sm @error('status') is-invalid @enderror"
                                        required>
                                        <option value="Aktif"
                                            {{ old('status', $user->status) == 'Aktif' ? 'selected' : '' }}>Aktif
                                        </option>
                                        <option value="Nonaktif"
                                            {{ old('status', $user->status) == 'Nonaktif' ? 'selected' : '' }}>Nonaktif
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Password baru (opsional) --}}
                                <div class="col-12">
                                    <div class="alert alert-info py-2 px-3 small mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Kosongkan field password jika tidak ingin mengubah password.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="password" class="form-label fw-semibold small mb-1">
                                        Password Baru
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" name="password" id="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Kosongkan jika tidak diubah">
                                        <button type="button" class="btn btn-outline-secondary btn-toggle-pass"
                                            data-target="password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="password_confirmation" class="form-label fw-semibold small mb-1">
                                        Konfirmasi Password Baru
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" name="password_confirmation"
                                            id="password_confirmation" class="form-control"
                                            placeholder="Ulangi password baru">
                                        <button type="button" class="btn btn-outline-secondary btn-toggle-pass"
                                            data-target="password_confirmation">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>{{-- end row --}}

                            <hr class="mt-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('js')
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
            document.addEventListener('DOMContentLoaded', () => {
                updateDateTime();
                setInterval(updateDateTime, 1000);
            });

            // Preview foto
            document.getElementById('foto').addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        document.getElementById('foto-preview').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Toggle password
            document.querySelectorAll('.btn-toggle-pass').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('fa-eye', 'fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('fa-eye-slash', 'fa-eye');
                    }
                });
            });
        </script>
    @endpush

</x-layout.home>
