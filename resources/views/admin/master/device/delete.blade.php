@foreach ($firmware as $dev)
    <div class="modal fade" id="hapus{{ $dev->id }}" tabindex="-1" aria-labelledby="hapusLabel{{ $dev->id }}"
        aria-hidden="true">

        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="hapusLabel{{ $dev->id }}">
                        Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('admin.firmware.delete', $dev->id) }}" method="POST"
                    entype="multipart/form-data">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body">

                        <p class="mb-2 text-center">
                            Anda yakin ingin menghapus firmware berikut?
                        </p>

                        <div class="border rounded p-3">
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
                                            <th>Device Type</th>
                                            <td>:</td>
                                            <td>{{ $dev->device_type }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>:</td>
                                            <td>{{ ucfirst($dev->status) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p class="text-danger mt-3 small text-center">
                            <strong>Tindakan ini tidak dapat dibatalkan.</strong>
                        </p>

                    </div>

                    <div class="modal-footer justify-content-center">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fa-solid fa-trash me-1"></i> Hapus
                        </button>

                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="fa-solid fa-circle-xmark"></i> Batal
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endforeach
