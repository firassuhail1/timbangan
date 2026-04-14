<div class="modal fade" id="tambah" tabindex="-1" aria-labelledby="timbangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <!-- Responsive fullscreen di HP -->
        <div class="modal-content">
            <div class="modal-header text-dark">
                <h5 class="modal-title" id="timbangModalLabel">Uploud File Firmware ESP</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.firmware.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card">
                    <div class="card-body">
                        <div class="modal-body p-md-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Version</label>
                                        <input type="text" class="form-control mt-2 mb-2" name="version"
                                            id="version" placeholder="Version">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Device Type</label>
                                        <select name="device_type" id="device_type" class="form-select mt-2 mb-2">
                                            <option value="">-- Device Type --</option>
                                            <option value="O">Timbangan Ordersheet</option>
                                            <option value="P">Timbangan Package</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">File Version</label>
                                        <input type="file" class="form-control mt-2 mb-2" name="file"
                                            id="file">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Status File</label>
                                        <select name="status" id="status" class="form-select mt-2 mb-2">
                                            <option value="">-- Status --</option>
                                            <option value="draft">Draft</option>
                                            <option value="uploaded">Uploaded</option>
                                            <option value="published">Published</option>
                                            <option value="expired">Expired</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="">Notes</label>
                                        <textarea cols="5" rows="5" name="notes" id="notes" class="form-control mt-2 mb-2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="modal-footer justify-content-center gap-2 flex-wrap">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan
                            </button>
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                                <i class="fa-solid fa-circle-xmark"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
