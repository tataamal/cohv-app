<x-layouts.landing title="Welcome to KMI System">

    {{-- CSS untuk status tidak berubah, tapi kita akan gunakan class `completed` untuk status 'Selesai' --}}
    @push('styles')
    <style>
        .note-item.status-selesai {
            background-color: #f0fdf4; /* Tailwind's green-50 */
            border-left-color: #22c55e; /* green-500 */
        }
        .note-item.status-selesai .note-message,
        .note-item.status-selesai .note-meta,
        .note-item.status-selesai .note-sender {
            text-decoration: line-through;
            color: #6b7280; /* gray-500 */
        }
    </style>
    @endpush

    @php
        $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
        // Pastikan variabel $allUsers terdefinisi, jika tidak, buat collection kosong
        $allUsers = $allUsers ?? collect();
    @endphp

    <div class="flex flex-col min-h-screen p-6 md:p-8">
        {{-- Header (Tidak ada perubahan) --}}
        <header class="w-full max-w-7xl mx-auto mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 p-1.5 rounded-full shadow-sm bg-white flex items-center justify-center">
                        <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI">
                    </div>
                    <h1 class="text-base font-semibold text-gray-800 hidden sm:block">PT. Kayu Mabel Indonesia</h1>
                </div>
                
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-4 focus:outline-none">
                        <span class="font-semibold text-gray-700 hidden sm:block">{{ $user->name }}</span>
                        <i class="fa-solid fa-user"></i>
                    </button>

                    <div x-show="open" 
                         x-cloak
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 origin-top-right">
                        
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profil Saya</a>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        
        {{-- Main Content (Tidak ada perubahan) --}}
        <main class="flex-1 flex flex-col items-center justify-center text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-800 tracking-tight">Selamat Datang!</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl min-h-[28px]">
                <span id="typing-effect"></span>
                <span id="typing-cursor" class="inline-block w-0.5 h-6 bg-gray-800 animate-pulse ml-1" style="margin-bottom: -4px;"></span>
            </p>
            
            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full max-w-7xl">
                @php
                    $colorClasses = [
                        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'ring' => 'ring-purple-50', 'border' => 'hover:border-purple-500'],
                        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'ring' => 'ring-indigo-50', 'border' => 'hover:border-indigo-500'],
                        'pink'   => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600', 'ring' => 'ring-pink-50', 'border' => 'hover:border-pink-500'],
                        'sky'    => ['bg' => 'bg-sky-100', 'text' => 'text-sky-600', 'ring' => 'ring-sky-50', 'border' => 'hover:border-sky-500'],
                    ];
                    $colorKeys = array_keys($colorClasses);
                @endphp

                @forelse ($plants as $plant)
                    @php
                        $colorName = $colorKeys[$loop->index % count($colorKeys)];
                        $colors = $colorClasses[$colorName];
                        $cardClass = ($loop->first && $loop->count > 2) ? 'lg:col-span-2' : '';
                    @endphp
                    
                    <a href="{{ route('dashboard.show', [$plant->kode]) }}"
                       @click.prevent="isLoading = true; setTimeout(() => { window.location.href = $el.href }, 150)"
                       class="group block p-8 bg-white rounded-2xl shadow-lg border border-transparent {{ $colors['border'] }} hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 {{ $cardClass }}">
                        <div class="w-16 h-16 {{ $colors['bg'] }} rounded-full flex items-center justify-center mx-auto mb-4 ring-8 {{ $colors['ring'] }}">
                            <svg class="w-8 h-8 {{ $colors['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">{{ $plant->nama_bagian }}</h3>
                        <p class="mt-2 text-gray-500">Kategori: {{ $plant->kategori }}</p>
                    </a>
                @empty
                    <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-12 bg-gray-100 rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-700">Tidak Ada Plant</h3>
                        <p class="text-gray-500 mt-2">Tidak ada plant yang terhubung dengan akun Anda saat ini.</p>
                    </div>
                @endforelse
            </div>
        </main>

        {{-- Footer (Area yang dimodifikasi) --}}
        <footer class="w-full max-w-7xl mx-auto mt-12">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                {{-- Kalender (Tidak ada perubahan) --}}
                <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-lg">
                    <div id="calendar-container">
                        <div class="flex items-center justify-between mb-4">
                            <h2 id="calendar-month-year" class="text-xl font-bold text-gray-800"></h2>
                            <div class="flex items-center space-x-2">
                                <button id="prev-month" class="p-2 rounded-full text-gray-500 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></button>
                                <button id="next-month" class="p-2 rounded-full text-gray-500 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
                            </div>
                        </div>
                        <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-center"></div>
                    </div>
                </div>

                {{-- Catatan Tim --}}
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-lg flex flex-col">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Tugas & Catatan Tim</h2>
                    <form id="note-form" class="mb-4">
                        <div class="mb-3">
                            <label for="recipient-select" class="block text-sm font-medium text-gray-700 mb-1">Untuk:</label>
                             <select id="recipient-select" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" required>
                                 <option value="" disabled selected>Pilih penerima...</option>
                                 @foreach($allUsers->where('id', '!=', Auth::id()) as $user)
                                     <option value="{{ $user->id }}">{{ $user->nama }}</option> 
                                 @endforeach
                             </select>
                        </div>
                        
                        <div class="mb-3">
                             <label for="note-input" class="block text-sm font-medium text-gray-700 mb-1">Pesan:</label>
                            <textarea id="note-input" class="w-full h-24 p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-purple-500" placeholder="Tinggalkan pesan..." required></textarea>
                        </div>

                        <button type="submit" class="w-full bg-purple-600 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-purple-700 transition">Simpan Catatan</button>
                    </form>
                    
                    <div id="notes-list" class="flex-1 overflow-y-auto space-y-3 pr-2 border-t pt-4">
                        {{-- Catatan akan dirender oleh JavaScript di sini --}}
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Fungsi Efek Mengetik & Kalender (Tidak ada perubahan) ---
        function runTypingEffect(elementId, cursorId, text) {
            const element = document.getElementById(elementId);
            const cursor = document.getElementById(cursorId);
            if (!element || !cursor) return;
            let index = 0;
            element.textContent = '';
            cursor.style.display = 'inline-block';
            function type() {
                if (index < text.length) {
                    element.textContent += text.charAt(index);
                    index++;
                    setTimeout(type, 80);
                } else {
                    cursor.style.display = 'none';
                }
            }
            type();
        }
        runTypingEffect('typing-effect', 'typing-cursor', "Bagian mana yang ingin anda kerjakan hari ini?");
        
        const monthYearEl = document.getElementById('calendar-month-year');
        const calendarGridEl = document.getElementById('calendar-grid');
        const prevMonthBtn = document.getElementById('prev-month');
        const nextMonthBtn = document.getElementById('next-month');
        let currentDate = new Date();
        function renderCalendar() {
            if (!calendarGridEl) return;
            calendarGridEl.innerHTML = '';
            const month = currentDate.getMonth();
            const year = currentDate.getFullYear();
            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            if(monthYearEl) monthYearEl.textContent = `${monthNames[month]} ${year}`;
            const firstDayOfMonth = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            dayNames.forEach(day => {
                const dayEl = document.createElement('div');
                dayEl.className = 'font-semibold text-sm text-gray-500 pb-2';
                dayEl.textContent = day;
                calendarGridEl.appendChild(dayEl);
            });
            for (let i = 0; i < firstDayOfMonth; i++) calendarGridEl.appendChild(document.createElement('div'));
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'h-10 flex items-center justify-center text-sm rounded-full cursor-pointer transition';
                dayCell.textContent = day;
                if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    dayCell.classList.add('bg-purple-600', 'text-white', 'font-bold');
                } else {
                    dayCell.classList.add('text-gray-700', 'hover:bg-gray-100');
                }
                calendarGridEl.appendChild(dayCell);
            }
        }
        if(prevMonthBtn) prevMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); });
        if(nextMonthBtn) nextMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); });
        renderCalendar();

        // --- [MODIFIKASI] Fitur Catatan Tim dengan AJAX & Status ---
        const noteForm = document.getElementById('note-form');
        const noteInput = document.getElementById('note-input');
        const notesList = document.getElementById('notes-list');
        const recipientSelect = document.getElementById('recipient-select');
        const currentUserId = {{ Auth::id() ?? 'null' }};

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }

        // Fungsi untuk merender catatan dengan status
        // function renderNotes(notes) {
        //     if (!notesList) return;
        //     notesList.innerHTML = '';
        //     if (notes.length === 0) {
        //         notesList.innerHTML = '<p class="text-center text-gray-400 mt-8">Belum ada catatan.</p>';
        //         return;
        //     }

        //     notes.forEach(note => {
        //         const noteItem = document.createElement('div');
        //         noteItem.dataset.id = note.id;

        //         // Tentukan warna dan teks berdasarkan status
        //         let statusClass, statusBg, statusText, borderColor;
        //         switch(note.status) {
        //             case 'Dikerjakan':
        //                 statusClass = 'status-dikerjakan';
        //                 statusBg = 'bg-blue-500';
        //                 statusText = 'Dikerjakan';
        //                 borderColor = 'border-blue-400';
        //                 break;
        //             case 'Selesai':
        //                 statusClass = 'status-selesai';
        //                 statusBg = 'bg-green-500';
        //                 statusText = 'Selesai';
        //                 borderColor = 'border-green-400'; // Didefinisikan di CSS
        //                 break;
        //             default: // 'Baru'
        //                 statusClass = 'status-baru';
        //                 statusBg = 'bg-yellow-500';
        //                 statusText = 'Baru';
        //                 borderColor = 'border-yellow-400';
        //         }
                
        //         noteItem.className = `note-item relative bg-gray-50 p-3 rounded-lg shadow-sm border-l-4 ${borderColor} transition-all duration-300 ${statusClass}`;
                
        //         const noteTime = new Date(note.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        //         // Hanya penerima yang bisa mengubah status
        //         const canUpdateStatus = currentUserId === note.recipient_id;
        //         const updateButtonHtml = canUpdateStatus ? 
        //             `<button class="update-status-btn text-xs font-semibold text-white ${statusBg} hover:opacity-80 py-1 px-3 rounded-full transition">
        //                 Ubah Status
        //              </button>` : '';

        //         noteItem.innerHTML = `
        //             <div class="flex justify-between items-start gap-4">
        //                 <div class="flex-1">
        //                     <p class="note-meta text-xs text-gray-500 mb-1">
        //                         <strong>Untuk:</strong> ${note.recipient.nama}
        //                     </p>
        //                     <p class="note-message text-sm text-gray-800 break-words">${note.message}</p>
        //                 </div>
        //                 <div class="flex-shrink-0 text-center">
        //                     <span class="status-badge inline-block text-xs font-bold text-white ${statusBg} px-2 py-1 rounded-full mb-2">${statusText}</span>
        //                     ${updateButtonHtml}
        //                 </div>
        //             </div>
        //             <p class="note-sender text-right text-xs text-gray-400 mt-2">
        //                 <em>${noteTime} - Dari: ${note.sender.nama}</em>
        //             </p>
        //         `;
        //         notesList.appendChild(noteItem);
        //     });
        // }

        // async function fetchNotes() {   
        //     try {
        //         const response = await fetch(``); 
        //         if (!response.ok) throw new Error('Gagal mengambil data catatan');
        //         const notes = await response.json();
        //         renderNotes(notes);
        //     } catch (error) {
        //         console.error('Error fetching notes:', error);
        //         if(notesList) notesList.innerHTML = '<p class="text-center text-red-500 mt-8">Gagal memuat catatan.</p>';
        //     }
        // }

        // if(noteForm) noteForm.addEventListener('submit', async function(e) {
        //     e.preventDefault();
        //     const message = noteInput.value.trim();
        //     const recipientId = recipientSelect.value;
        //     if (!message || !recipientId) return;

        //     try {
        //         const response = await fetch(``, {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //                 'X-CSRF-TOKEN': getCsrfToken(),
        //                 'Accept': 'application/json',
        //             },
        //             body: JSON.stringify({
        //                 recipient_id: recipientId,
        //                 message: message,
        //             }),
        //         });

        //         if (!response.ok) {
        //             const errorData = await response.json();
        //             throw new Error(errorData.message || 'Gagal menyimpan catatan');
        //         }
                
        //         noteInput.value = '';
        //         recipientSelect.selectedIndex = 0;
        //         fetchNotes();
        //     } catch (error) {
        //         console.error('Error saving note:', error);
        //         alert('Gagal menyimpan catatan: ' + error.message);
        //     }
        // });

        // if(notesList) notesList.addEventListener('click', async function(e) {
        //     const updateButton = e.target.closest('.update-status-btn');
        //     if (updateButton) {
        //         const noteItem = updateButton.closest('.note-item');
        //         const noteId = noteItem.dataset.id;
                
        //         // Tambahkan efek visual saat loading
        //         updateButton.textContent = '...';
        //         updateButton.disabled = true;

        //         try {
        //             // [PERBAIKAN] Membangun URL secara manual di JavaScript
        //             const response = await fetch(`/notes/${noteId}/status`, {
        //                 method: 'PATCH',
        //                 headers: {
        //                     'X-CSRF-TOKEN': getCsrfToken(),
        //                     'Accept': 'application/json',
        //                 },
        //             });
        //             if (!response.ok) throw new Error('Gagal update status');
                    
        //             fetchNotes(); // Cara paling mudah untuk refresh list

        //         } catch (error) {
        //             console.error('Error updating status:', error);
        //             // Kembalikan teks tombol jika error
        //             updateButton.textContent = 'Ubah Status';
        //             updateButton.disabled = false;
        //         }
        //     }
        // });

        // fetchNotes();
    });
    </script>
    @endpush
</x-layouts.landing>

 