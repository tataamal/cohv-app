{{-- ========= ADD COMPONENT MODAL ========= --}}
{{-- Perubahan: Tambah 'backdrop-blur-sm' untuk efek blur --}}
{{-- Perubahan: 'inset-0' dan 'h-screen w-screen' untuk ukuran satu layar --}}
<div id="modal-add-component" class="fixed inset-0 z-50 flex items-center justify-center 
                                      bg-black bg-opacity-50 backdrop-blur-sm 
                                      h-screen w-screen hidden">
    {{-- Perubahan: 'max-w-md' ke 'max-w-xl' (opsional, untuk sedikit lebih lebar) --}}
    {{-- Perubahan: 'max-h-[90vh]' ke 'max-h-[calc(100vh-4rem)]' (lebih presisi) --}}
    {{-- Perubahan: 'mx-auto' dan 'my-auto' untuk senter jika tidak full screen --}}
    <div class="bg-white rounded-lg shadow-xl w-full max-w-xl 
                max-h-[calc(100vh-4rem)] overflow-y-auto 
                mx-auto my-auto p-6"> {{-- Tambahkan p-6 di sini agar padding konsisten --}}
        
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Add Component for Production Order</h2> {{-- Ubah ukuran & bold --}}
            <button onclick="closeModalAddComponent()" class="text-gray-500 hover:text-gray-700 focus:outline-none transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"> {{-- Ubah ukuran icon --}}
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="add-component-form" method="POST" action="{{ route('component.add') }}">
            @csrf
            <input type="hidden" id="add-component-aufnr" name="iv_aufnr">
            <input type="hidden" id="add-component-vornr" name="iv_vornr">
            <input type="hidden" id="add-component-plant" name="iv_werks">
            
            <div class="space-y-5"> {{-- Ubah jarak antar elemen --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Production Order</label>
                    <input type="text" id="display-aufnr" value="" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-800" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Operation Number</label>
                    <input type="text" id="display-vornr" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-800" readonly>
                </div>

                <div>
                    <label for="add-component-plant-select" class="block text-sm font-medium text-gray-700 mb-1">Plant *</label>
                    <select id="add-component-plant-select" name="iv_werks" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">-- Select Plant --</option>
                        <option value="1000">1000</option>
                        <option value="1100">1100</option>
                        <option value="1200">1200</option>
                        <option value="1300">1300</option>
                        <option value="2000">2000</option>
                        <option value="3000">3000</option>
                    </select>
                </div>

                <div>
                    <label for="add-component-matnr" class="block text-sm font-medium text-gray-700 mb-1">Material Number *</label>
                    <input type="text" id="add-component-matnr" name="iv_matnr" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter material number, e.g., 10000001" required>
                </div>

                <div>
                    <label for="add-component-bdmng" class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" id="add-component-bdmng" name="iv_bdmng" step="0.001" min="0.001"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter quantity, e.g., 10.500" required>
                </div>

                <div>
                    <label for="add-component-meins" class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure *</label>
                    <select id="add-component-meins" name="iv_meins" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Unit</option>
                        <option value="PC">PC - Piece</option>
                        <option value="KG">KG - Kilogram</option>
                        <option value="M">M - Meter</option>
                        <option value="L">L - Liter</option>
                        <option value="M2">M2 - Square Meter</option>
                        <option value="M3">M3 - Cubic Meter</option>
                        <option value="SET">SET - Set</option>
                        <option value="PAA">PAA - Pair</option>
                        <option value="ROL">ROL - Roll</option>
                        <option value="BTL">BTL - Bottle</option>
                        {{-- Tambahkan unit lain jika diperlukan --}}
                    </select>
                </div>

                <div>
                    <label for="add-component-lgort" class="block text-sm font-medium text-gray-700 mb-1">Storage Location *</label>
                    <input type="text" id="add-component-lgort" name="iv_lgort"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter storage location, e.g., 0001" required>
                </div>

                <input type="hidden" name="iv_postp" value="L">
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 mt-6"> {{-- Tambah gap dan ubah justify --}}
                <button type="button" onclick="closeModalAddComponent()"
                        class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                    Cancel
                </button>
                <button type="submit" id="add-component-submit-btn"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                    Add Component
                </button>
            </div>
        </form>
    </div>
</div>