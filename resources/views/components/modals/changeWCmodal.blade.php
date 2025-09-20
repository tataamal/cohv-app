<div class="modal fade" id="changeWcModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {{-- Kita akan handle submit dengan JS, beri ID pada form --}}
                <form id="changeWcForm"> 
                    @csrf
                    {{-- Hidden input untuk data dinamis --}}
                    <input type="hidden" id="changeWcAufnr" name="aufnr">
                    <input type="hidden" id="changeWcVornr" name="vornr">
                    <input type="hidden" id="changeWcPwwrk" name="pwwrk">
                    <input type="hidden" id="changeWcPlant" name="plant" value="{{ $plant }}">

                    <div class="modal-header">
                        <h5 class="modal-title">Change Work Center</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Field 1: Work Center Asal (Read-only) --}}
                        <div class="mb-3">
                            <label for="changeWcAsal" class="form-label">Work Center Asal</label>
                            <input type="text" id="changeWcAsal" class="form-control" readonly style="background-color: #e9ecef;">
                        </div>

                        {{-- Field 2: Work Center Tujuan (Dropdown) --}}
                        <div class="mb-3">
                            <label for="changeWcTujuan" class="form-label">Work Center Tujuan</label>
                            <select id="changeWcTujuan" name="work_center_tujuan" class="form-select" required>
                                <option value="" selected disabled>-- Pilih Work Center --</option>
                                {{-- Loop data dari controller --}}
                                @foreach ($workCenters as $wc)
                                    <option value="{{ $wc->kode_wc }}">{{ $wc->kode_wc }} - {{ $wc->description }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        {{-- Ganti type="submit" menjadi type="button" dan beri ID --}}
                        <button type="button" id="confirmChangeWcBtn" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>