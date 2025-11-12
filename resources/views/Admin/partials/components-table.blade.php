<div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
    <table class="table table-sm table-striped table-bordered align-middle">
        <thead class="table-light sticky-top">
            <tr>
                <th class="text-center" style="width: 5%;">No.</th>
                <th class="text-center">No. Reservasi</th>
                <th class="text-center">Item</th>
                <th class="text-center">Material</th>
                <th class="text-center">Description</th>
                <th class="text-center">Action</th>
                <th class="text-center">Req. Qty</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Outs. Req</th>
                <th class="text-center">Commited</th>
                <th class="text-center">S.Loc</th>
                <th class="text-center">UOM</th>
                <th class="text-center">Spec. Proc</th>
            </tr>
        </thead>
        <tbody>
            @foreach($components as $i => $comp)
                @php
                    $itemData = json_encode($comp);

                    // Bersihkan leading zero jika hanya angka
                    $matnrRaw = $comp['MATNR'] ?? '-';
                    $matnrClean = (ctype_digit($matnrRaw)) ? (int)$matnrRaw : $matnrRaw;

                    // Normalisasi satuan (MEINS)
                    $meins = strtoupper(trim($comp['MEINS'] ?? '-'));

                    // Ambil data numerik
                    $bdmngRaw = $comp['BDMNG'] ?? null;
                    $kalabRaw = $comp['KALAB'] ?? null;
                    $outsreqRaw = $comp['OUTSREQ'] ?? null;
                    $commRaw = $comp['VMENG'] ?? null;

                    $bdmng = is_numeric($bdmngRaw) ? (float)$bdmngRaw : $bdmngRaw;
                    $kalab = is_numeric($kalabRaw) ? (float)$kalabRaw : $kalabRaw;
                    $outsreq = is_numeric($outsreqRaw) ? (float)$outsreqRaw : $outsreqRaw;
                    $comm = is_numeric($commRaw) ? (float)$commRaw : $commRaw;

                    // Format angka sesuai satuan
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

                <tr class="comp-row"
                    data-item='@json($comp)'
                    data-bs-toggle="modal"
                    data-bs-target="#componentModal">
                    
                    <!-- Desktop view -->
                    <td class="text-center d-none d-md-table-cell">{{ $i + 1 }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $comp['RSNUM'] }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $comp['RSPOS'] ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $matnrClean ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $comp['MAKTX'] ?? '-' }}</td>

                    <td class="text-center d-none d-md-table-cell">
                        <div class="btn-group btn-group-sm gap-2" role="group">
                            <button type="button" 
                                    class="btn btn-warning text-white" 
                                    onclick="editComponent('{{ $comp['MATNR'] }}')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" 
                                    class="btn btn-info text-white" 
                                    onclick="showStock('{{ $comp['MATNR'] }}')">
                                <i class="fas fa-box"></i> Stock
                            </button>
                        </div>
                    </td>

                    <td class="text-center d-none d-md-table-cell">{{ $formattedBdmng ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $formattedKalab ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $formattedOutsreq ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $formattedComm ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $comp['LGORT'] ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $meinsDisplay ?? '-' }}</td>
                    <td class="text-center d-none d-md-table-cell">{{ $comp['LTEXT'] ?? '-' }}</td>

                    <!-- Mobile view -->
                    <td class="d-md-none" colspan="9" style="padding: 4px; background-color: #f8f9fa;">
                        <div class="bg-white border rounded-3 shadow-sm p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark">{{ $comp['MAKTX'] ?? 'No Description' }}</div>
                                <div class="text-muted small">Material: {{ $matnrClean ?? '-' }}</div>
                                <div class="text-muted small">Item: {{ $comp['RSPOS'] ?? '-' }}</div>
                            </div>
                            <i class="fas fa-chevron-right text-primary"></i>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Detail Komponen (hanya tampil di layar kecil / mobile) -->
<div class="modal fade" id="componentModal" tabindex="-1" aria-labelledby="componentModalLabel" aria-hidden="true">
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
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('componentModal');
    const body = document.getElementById('componentDetailBody');

    modalEl.addEventListener('show.bs.modal', event => {
        // Hanya tampil di mobile
        if (window.innerWidth >= 768) {
            event.preventDefault();
            return;
        }

        const trigger = event.relatedTarget;
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
});
</script>
