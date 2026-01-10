<div class="d-flex justify-content-between align-items-center mb-2">
    <div id="contextual-actions" class="d-none">
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-danger me-2" id="delete-selected-btn">
                <i class="fas fa-trash"></i> Delete Component
            </button>
            <button type="button" class="btn btn-secondary" id="clear-selected-btn">
                <i class="fas fa-times"></i> Clear Selected
            </button>
        </div>
    </div>

    <div class="ms-auto">
        <button type="button" class="btn btn-primary btn-sm text-white" id="add-component-btn">
            <i class="fas fa-plus"></i> Add Component
        </button>
    </div>
</div>
<div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
    <table class="table table-sm table-bordered table-hover mb-0 align-middle" style="font-size: 0.85rem;">
        <thead class="table-light sticky-top">
            <tr class="text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                <th class="text-center bg-light" style="width: 40px;">
                    <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                </th>
                <th class="text-center bg-light" scope="col">No.</th>
                <th class="text-center bg-light" scope="col">No. Reservasi</th>
                <th class="text-center bg-light" scope="col">Item</th>
                <th class="text-center bg-light" scope="col">Material</th>
                <th class="text-start bg-light" scope="col">Description</th>
                <th class="text-center bg-light" scope="col">Action</th>
                <th class="text-center bg-light" scope="col">Req. Qty</th>
                <th class="text-center bg-light" scope="col">Stock</th>
                <th class="text-center bg-light" scope="col">Outs. Req</th>
                <th class="text-center bg-light" scope="col">Commited</th>
                <th class="text-center bg-light" scope="col">S.Loc</th>
                <th class="text-center bg-light" scope="col">UOM</th>
                <th class="text-center bg-light" scope="col">Spec. Proc</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($components as $i => $comp)
                @php
                    $itemData = json_encode($comp);
                    $matnrRaw = $comp['MATNR'] ?? '-';
                    $matnrClean = ctype_digit($matnrRaw) ? (int) $matnrRaw : $matnrRaw;
                    $meins = strtoupper(trim($comp['MEINS'] ?? '-'));
                    $bdmngRaw = $comp['BDMNG'] ?? null;
                    $kalabRaw = $comp['KALAB'] ?? null;
                    $outsreqRaw = $comp['OUTSREQ'] ?? null;
                    $commRaw = $comp['VMENG'] ?? null;
                    $bdmng = is_numeric($bdmngRaw) ? (float) $bdmngRaw : $bdmngRaw;
                    $kalab = is_numeric($kalabRaw) ? (float) $kalabRaw : $kalabRaw;
                    $outsreq = is_numeric($outsreqRaw) ? (float) $outsreqRaw : $outsreqRaw;
                    $comm = is_numeric($commRaw) ? (float) $commRaw : $commRaw;
                    if (in_array($meins, ['ST', 'SET'])) {
                        $formattedBdmng = number_format($bdmng, 0);
                        $formattedKalab = number_format($kalab, 0);
                        $formattedOutsreq = number_format($outsreq ?? 0, 0);
                        $formattedComm = number_format($comm ?? 0, 0);
                        $meinsDisplay = 'PC';
                    } else {
                        $formattedBdmng = number_format($bdmng, 2);
                        $formattedKalab = number_format($kalab, 2);
                        $formattedOutsreq = number_format($outsreq ?? 0, 2);
                        $formattedComm = number_format($comm ?? 0, 2);
                        $meinsDisplay = $meins;
                    }
                @endphp

                <tr class="comp-row" data-item='@json($comp)'>

                    <td class="text-center align-middle">
                        <input class="form-check-input row-checkbox" type="checkbox"
                            value="{{ $comp['RSNUM'] }}-{{ $comp['RSPOS'] }}"
                            aria-label="Select row {{ $i + 1 }}">
                    </td>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td class="text-center font-monospace">{{ $comp['RSNUM'] }}</td>
                    <td class="text-center font-monospace">{{ $comp['RSPOS'] ?? '-' }}</td>
                    <td class="text-center font-monospace fw-semibold text-dark">{{ $matnrClean ?? '-' }}</td>
                    <td class="text-start text-dark fw-medium">{{ $comp['MAKTX'] ?? '-' }}</td>

                    <td class="text-center">
                        <div class="btn-group btn-group-sm gap-1" role="group">
                            <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2 edit-component-btn"
                                data-matnr="{{ $comp['MATNR'] }}" title="Edit">
                                <i class="fas fa-edit small"></i>
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm py-0 px-2 show-stock-btn"
                                data-matnr="{{ $comp['MATNR'] }}" title="Check Stock">
                                <i class="fas fa-box small"></i>
                            </button>
                        </div>
                    </td>

                    <td class="text-center font-monospace">{{ $formattedBdmng ?? '-' }}</td>
                    <td class="text-center font-monospace">{{ $formattedKalab ?? '-' }}</td>
                    <td class="text-center font-monospace">{{ $formattedOutsreq ?? '-' }}</td>
                    <td class="text-center font-monospace">{{ $formattedComm ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary rounded-0 font-monospace border border-secondary">{{ $comp['LGORT'] ?? '-' }}</span>
                    </td>
                    <td class="text-center small text-muted">{{ $meinsDisplay ?? '-' }}</td>
                    <td class="text-center">{{ $comp['LTEXT'] ?? '-' }}</td>

                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Detail Komponen (hanya tampil di layar kecil / mobile) -->
<div class="modal fade" id="componentModal" tabindex="-1" aria-labelledby="componentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="componentModalLabel">Detail Komponen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody id="componentDetailBody">
                        <!-- Isi via JS -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('componentModal');
        const body = document.getElementById('componentDetailBody');

        modalEl.addEventListener('show.bs.modal', event => {
            if (window.innerWidth >= 768) {
                event.preventDefault();
                return;
            }

            let trigger = event.relatedTarget;
            if (trigger.tagName === 'TD') {
                trigger = trigger.closest('.comp-row');
            }

            let itemData = {};
            try {
                if (trigger && trigger.dataset && trigger.dataset.item) {
                    itemData = JSON.parse(trigger.dataset.item);
                }
            } catch (e) {
                console.error('Gagal parsing data item:', e);
            }

            const allowedKeys = ['RSNUM', 'RSPOS', 'MATNR', 'MAKTX'];
            body.innerHTML = allowedKeys.map(key => `
            <tr>
                <th style="width: 40%; text-transform: capitalize;">${key}</th>
                <td>${itemData[key] ?? '-'}</td>
            </tr>
        `).join('');
        });
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const contextualActions = document.getElementById('contextual-actions');
        const clearSelectedBtn = document.getElementById('clear-selected-btn');
        const deleteSelectedBtn = document.getElementById('delete-selected-btn');

        function updateContextualButtons() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;

            if (checkedCount > 0) {
                contextualActions.classList.remove('d-none');
            } else {
                contextualActions.classList.add('d-none');
            }

            // Update status checkbox "Select All"
            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === rowCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateContextualButtons();
        });
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateContextualButtons();
            });
        });

        clearSelectedBtn.addEventListener('click', function() {
            selectAllCheckbox.checked = false;
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateContextualButtons();
        });

        deleteSelectedBtn.addEventListener('click', function() {
            const selectedItems = [];
            document.querySelectorAll('.row-checkbox:checked').forEach(checkbox => {
                selectedItems.push(checkbox.value);
            });

            console.log('Item yang akan dihapus:', selectedItems);
            Swal.fire({
                title: 'Maintenance!',
                text: 'Fitur masih dalam tahap pengembangan',
                icon: 'warning'
            });
            // Swal.fire({
            //     title: `Yakin ingin menghapus ${selectedItems.length} item?`,
            //     text: "Aksi ini tidak dapat dibatalkan!",
            //     icon: 'warning',
            //     showCancelButton: true,
            //     confirmButtonColor: '#d33',
            //     cancelButtonColor: '#3085d6',
            //     confirmButtonText: 'Ya, hapus!',
            //     cancelButtonText: 'Batal'
            // }).then((result) => {
            //     if (result.isConfirmed) {
            //         Swal.fire(
            //             'Terhapus!',
            //             `${selectedItems.length} item telah dihapus (simulasi).`,
            //             'success'
            //         );
            //         clearSelectedBtn.click();
            //     }
            // });
        });
        updateContextualButtons();
    });
</script>
