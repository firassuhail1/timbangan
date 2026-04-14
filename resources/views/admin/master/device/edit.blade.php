@foreach ($firmware as $dev)
    <div class="modal fade" id="posting{{ $dev->id }}" tabindex="-1" aria-labelledby="timbangModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <!-- Responsive fullscreen di HP -->
            <div class="modal-content">
                <div class="modal-header text-dark">
                    <h5 class="modal-title" id="timbangModalLabel">Post File Firmware</h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.firmware.post', $dev->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-md-4">
                        <div class="table-responsive">
                            <table class="table table-responsive table-sm align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <th width="30%">Name</th>
                                        <td>:</td>
                                        <td>{{ $dev->file_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Firmware Version</th>
                                        <td>:</td>
                                        <td>{{ $dev->version }}</td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>:</td>
                                        <td>{{ $dev->notes }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>:</td>
                                        <td>
                                            <select name="status" id="status_{{ $dev->id }}"
                                                class="form-select form-select-sm">
                                                <option value="draft" {{ $dev->status == 'draft' ? 'selected' : '' }}>
                                                    Draft</option>
                                                <option value="uploaded"
                                                    {{ $dev->status == 'uploaded' ? 'selected' : '' }}>Uploaded</option>
                                                <option value="published"
                                                    {{ $dev->status == 'published' ? 'selected' : '' }}>Published
                                                </option>
                                                <option value="expired"
                                                    {{ $dev->status == 'expired' ? 'selected' : '' }}>Expired</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="verifikasi">
                        <h6 class="text-center">Apakah Anda yakin ingin mengubah status firmware ini?</h6>
                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer justify-content-center gap-2 flex-wrap">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fa-solid fa-circle-check"></i> Ya
                        </button>
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="fa-solid fa-circle-xmark"></i> Tidak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
