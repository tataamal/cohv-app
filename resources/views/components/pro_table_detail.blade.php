{{-- resources/views/components/pro_table_detail.blade.php (Modern & Responsive) --}}

<style>
    .pro-table-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-top: 1rem;
    }
    
    .pro-table {
        margin: 0;
        font-size: 0.875rem;
    }
    
    .pro-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .pro-table thead th {
        font-weight: 600;
        padding: 1rem 0.75rem;
        border: none;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .pro-table tbody tr.main-row {
        cursor: pointer;
        transition: all 0.2s ease;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .pro-table tbody tr.main-row:hover {
        background-color: #f8f9ff;
        transform: scale(1.01);
    }
    
    .pro-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border: none;
    }
    
    .row-number {
        width: 50px;
        font-weight: 600;
        color: #667eea;
        font-size: 0.95rem;
    }
    
    .pro-number {
        font-weight: 600;
        color: #2d3748;
    }
    
    .detail-row {
        background: transparent !important;
        border: none !important;
    }
    
    .detail-content {
        padding: 1.5rem;
        background: #fafbff;
        margin: 0.5rem 1rem 1rem 1rem;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        border: 1px solid #e6e9ff;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .detail-item {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        border-left: 3px solid #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.12);
        transition: all 0.3s ease;
    }
    
    .detail-item:hover {
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }
    
    .detail-label {
        font-size: 0.75rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        font-size: 0.95rem;
        color: #2d3748;
        font-weight: 500;
    }
    
    .qty-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .qty-order { background: #e6f7ff; color: #0066cc; }
    .qty-gr { background: #e6ffe6; color: #009900; }
    .qty-outs { background: #ffe6e6; color: #cc0000; }
    
    .status-badge {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .expand-icon {
        transition: transform 0.3s ease;
        color: #667eea;
        font-size: 1.2rem;
    }
    
    .expand-icon.rotated {
        transform: rotate(180deg);
    }
    
    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #a0aec0;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .table-footer {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #e2e8f0;
        font-size: 0.875rem;
        color: #718096;
    }
    
    .back-button-wrapper {
        margin-bottom: 1.5rem;
    }
    
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        text-decoration: none;
    }
    
    .back-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.45);
        color: white;
    }
    
    .back-button:active {
        transform: translateY(-1px);
    }
    
    .back-button i {
        transition: transform 0.3s ease;
    }
    
    .back-button:hover i {
        transform: translateX(-3px);
    }
    
    @media (max-width: 768px) {
        .pro-table-wrapper {
            border-radius: 8px;
        }
        
        .detail-content {
            margin: 0.5rem;
            padding: 1rem;
        }
        
        .detail-grid {
            grid-template-columns: 1fr;
        }
        
        .back-button {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="pro-table-wrapper">
    <div style="max-height: 600px; overflow-y: auto;">
        <table class="table pro-table mb-0">
            <thead class="sticky-top">
                <tr>
                    <th class="text-center">No</th>
                    <th class="text-center">PRO Number</th>
                    <th class="text-center">SO Number</th>
                    <th class="text-center">SO Item</th>
                    <th class="text-center" style="width: 50px;"></th>
                </tr>
            </thead>
            
            <tbody>
                @forelse ($proDetails as $index => $pro)
                    {{-- Baris Utama --}}
                    <tr class="main-row" onclick="toggleDetail({{ $index }})">
                        <td class="text-center row-number">{{ $index + 1 }}</td>
                        <td class="text-center pro-number">{{ $pro->pro_number }}</td>
                        <td class="text-center">{{ $pro->so_number }}</td>
                        <td class="text-center">{{ $pro->so_item }}</td>
                        <td class="text-center">
                            <i class="fas fa-chevron-down expand-icon" id="icon-{{ $index }}"></i>
                        </td>
                    </tr>
                    
                    {{-- Baris Detail (Tersembunyi) --}}
                    <tr class="detail-row" id="detail-{{ $index }}" style="display: none;">
                        <td colspan="5" class="p-0">
                            <div class="detail-content">
                                <div class="detail-grid">
                                    {{-- Material Info --}}
                                    <div class="detail-item">
                                        <div class="detail-label">Material Code</div>
                                        <div class="detail-value">{{ $pro->material_code }}</div>
                                    </div>
                                    
                                    <div class="detail-item" style="grid-column: span 2;">
                                        <div class="detail-label">Description</div>
                                        <div class="detail-value">{{ $pro->description }}</div>
                                    </div>
                                    
                                    {{-- Plant & MRP --}}
                                    <div class="detail-item">
                                        <div class="detail-label">Plant</div>
                                        <div class="detail-value">{{ $pro->plant }}</div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">MRP Controller</div>
                                        <div class="detail-value">{{ $pro->mrp_controller }}</div>
                                    </div>
                                    
                                    {{-- Quantities --}}
                                    <div class="detail-item">
                                        <div class="detail-label">Order Quantity</div>
                                        <div class="detail-value">
                                            <span class="qty-badge qty-order">
                                                {{ number_format($pro->order_quantity, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">GR Quantity</div>
                                        <div class="detail-value">
                                            <span class="qty-badge qty-gr">
                                                {{ number_format($pro->gr_quantity, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Outstanding GR</div>
                                        <div class="detail-value">
                                            <span class="qty-badge qty-outs">
                                                {{ number_format($pro->outs_gr_quantity, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    {{-- Dates --}}
                                    <div class="detail-item">
                                        <div class="detail-label">Start Date</div>
                                        <div class="detail-value">
                                            <i class="far fa-calendar-alt me-2"></i>
                                            {{ \Carbon\Carbon::parse($pro->start_date)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">End Date</div>
                                        <div class="detail-value">
                                            <i class="far fa-calendar-alt me-2"></i>
                                            {{ \Carbon\Carbon::parse($pro->end_date)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    
                                    {{-- Status --}}
                                    <div class="detail-item">
                                        <div class="detail-label">Status</div>
                                        <div class="detail-value">
                                            <span class="status-badge">{{ $pro->stats }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-0">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p class="mb-0 fw-semibold">Tidak ada Production Order</p>
                                <small>Tidak ada data yang cocok dengan filter saat ini</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($proDetails->count() > 0)
        <div class="table-footer">
            <i class="fas fa-info-circle me-2"></i>
            Menampilkan <strong>{{ $proDetails->count() }}</strong> data Production Order
        </div>
    @endif
</div>

<script>
function toggleDetail(index) {
    const detailRow = document.getElementById(`detail-${index}`);
    const icon = document.getElementById(`icon-${index}`);
    
    if (detailRow.style.display === 'none') {
        // Tutup semua detail lain
        document.querySelectorAll('.detail-row').forEach(row => {
            row.style.display = 'none';
        });
        document.querySelectorAll('.expand-icon').forEach(ic => {
            ic.classList.remove('rotated');
        });
        
        // Buka detail yang diklik
        detailRow.style.display = 'table-row';
        icon.classList.add('rotated');
    } else {
        detailRow.style.display = 'none';
        icon.classList.remove('rotated');
    }
}
</script>