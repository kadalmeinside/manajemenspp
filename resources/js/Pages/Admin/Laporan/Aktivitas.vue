<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { debounce } from 'lodash';
import { 
    CreditCardIcon, 
    MagnifyingGlassIcon,
    CurrencyDollarIcon,
    CalendarDaysIcon,
    CheckBadgeIcon,
    AcademicCapIcon,
    ClockIcon,
    NoSymbolIcon,
    QueueListIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pageTitle: String,
    activities: Object,
    kelasList: Array,
    filters: Object,
    stats: Object,
});

const today = new Date().toISOString().split('T')[0];

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const kelasFilter = ref(props.filters.kelas_id || '');
const startDate = ref('');
const endDate = ref('');
const sort = ref('desc');
const groupBy = ref(false); // Khusus Export

const showExportModal = ref(false);

const openExportModal = () => {
    // Default to last 30 days if empty
    if (!startDate.value || !endDate.value) {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - 30);
        
        endDate.value = end.toISOString().split('T')[0];
        startDate.value = start.toISOString().split('T')[0];
    }
    showExportModal.value = true;
};

const closeExportModal = () => {
    showExportModal.value = false;
};

const applyFilters = () => {
    router.get(
        route('admin.laporan.aktivitas'),
        { 
            search: search.value, 
            type: typeFilter.value,
            kelas_id: kelasFilter.value
        },
        { preserveState: true, replace: true }
    );
};

// Debounce search
watch(search, debounce(function () {
    applyFilters();
}, 300));

watch([typeFilter, kelasFilter], function () {
    applyFilters();
});

const exportData = (format) => {
    if (!startDate.value || !endDate.value) {
        alert("Mohon pilih Rentang Tanggal (Mulai dan Selesai) terlebih dahulu.");
        return;
    }
    
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    if (end < start) {
        alert("Tanggal selesai tidak boleh lebih awal dari tanggal mulai.");
        return;
    }
    
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
    
    if (diffDays > 31) {
        alert("Rentang tanggal maksimal adalah 31 hari. Silakan persempit pencarian Anda agar proses ekspor tidak gagal.");
        return;
    }

    const params = new URLSearchParams({
        search: search.value,
        type: typeFilter.value,
        kelas_id: kelasFilter.value,
        start_date: startDate.value,
        end_date: endDate.value,
        sort: sort.value,
        group_by: groupBy.value ? 'true' : 'false',
        format: format
    });
    
    window.location.href = `${route('admin.laporan.aktivitas.export')}?${params.toString()}`;
    closeExportModal();
};

// Helper for badge colors
const getTypeBadgeColor = (type) => {
    if (type === 'pembayaran_lunas' || type === 'pendaftaran_lunas') {
        return 'bg-green-100 text-green-800 border-green-200';
    }
    if (type === 'pendaftaran_pending') {
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    }
    if (type === 'cuti_disetujui') {
        return 'bg-blue-100 text-blue-800 border-blue-200';
    }
    if (type === 'siswa_resign') {
        return 'bg-red-100 text-red-800 border-red-200';
    }
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

const getTypeIcon = (type) => {
    if (type === 'pembayaran_lunas' || type === 'pendaftaran_lunas') return CheckBadgeIcon;
    if (type === 'pendaftaran_pending') return ClockIcon;
    if (type === 'cuti_disetujui') return CalendarDaysIcon;
    if (type === 'siswa_resign') return NoSymbolIcon;
    return QueueListIcon;
};

</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="hidden md:block text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Today's Revenue -->
                    <div class="relative overflow-hidden bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm sm:rounded-2xl p-6 transition-all duration-300 hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-green-400/20 to-emerald-600/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-xl">
                                <CurrencyDollarIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Penerimaan Tagihan Hari Ini</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.today }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Month's Revenue -->
                    <div class="relative overflow-hidden bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm sm:rounded-2xl p-6 transition-all duration-300 hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-blue-400/20 to-indigo-600/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                                <CalendarDaysIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Penerimaan Tagihan Bulan Ini</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.month }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Filter -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl overflow-hidden shadow-sm sm:rounded-lg border border-gray-200/50 dark:border-gray-700/50 mb-6">
                    <div class="p-4 sm:p-6">
                        <!-- Filters & Export -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div class="flex-1 w-full sm:max-w-md relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input 
                                    v-model="search" 
                                    type="text" 
                                    placeholder="Cari nama siswa..." 
                                    class="pl-10 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm transition-colors" 
                                />
                            </div>
                            
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4 w-full sm:w-auto">
                                <select v-model="kelasFilter" class="block w-full sm:w-44 rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs sm:text-sm py-2">
                                    <option value="">Semua Kelas</option>
                                    <option v-for="k in kelasList" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                                </select>

                                <select v-model="typeFilter" class="block w-full sm:w-44 rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs sm:text-sm py-2">
                                    <option value="">Semua Aktivitas</option>
                                    <option value="pendaftaran_pending">Pendaftaran (Menunggu)</option>
                                    <option value="pendaftaran_lunas">Pendaftaran (Lunas)</option>
                                    <option value="pembayaran_lunas">Pembayaran Tagihan (Lunas)</option>
                                    <option value="cuti_disetujui">Cuti Disetujui</option>
                                    <option value="siswa_resign">Siswa Resign</option>
                                </select>

                                <button @click="openExportModal" class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 transition duration-150 whitespace-nowrap shadow-sm">
                                    Export Laporan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="bg-transparent sm:bg-white/80 sm:dark:bg-gray-800/80 sm:backdrop-blur-xl sm:shadow-sm sm:rounded-lg sm:border sm:border-gray-200/50 sm:dark:border-gray-700/50">
                    <!-- Desktop Table -->
                    <div class="hidden md:block p-4 sm:p-6">
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs sm:text-sm">
                                <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-[11px]">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Waktu</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aktivitas</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa / Kelas</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pokok (Net)</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Biaya Admin</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total (Gross)</th>
                                        <th scope="col" class="px-4 py-3 text-center font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white/30 dark:bg-gray-900/30 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="act in activities.data" :key="act.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                        <!-- Time -->
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900 dark:text-gray-200" :title="act.date_full">
                                                    {{ act.date }}
                                                </span>
                                                <span class="text-[11px] text-gray-400">{{ act.date_full }}</span>
                                            </div>
                                        </td>
                                        
                                        <!-- Activity -->
                                        <td class="px-4 py-3.5 max-w-[200px]">
                                            <div class="flex flex-col items-start gap-1">
                                                <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border', getTypeBadgeColor(act.type)]">
                                                    <component :is="getTypeIcon(act.type)" class="w-3 h-3" />
                                                    {{ act.title }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate w-full" :title="act.description">
                                                    {{ act.description }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <!-- Siswa / Kelas -->
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ act.nama_siswa }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ act.nama_kelas }}</div>
                                        </td>
                                        
                                        <!-- Pokok (Net) -->
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-medium text-emerald-600 dark:text-emerald-400">
                                            {{ act.net_amount ? act.net_amount : (act.amount ? act.amount : '-') }}
                                        </td>

                                        <!-- Admin Fee -->
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                            <span v-if="act.admin_fee_raw > 0" class="text-amber-600 dark:text-amber-400 font-medium">{{ act.admin_fee }}</span>
                                            <span v-else class="text-gray-400 text-xs">-</span>
                                        </td>
                                        
                                        <!-- Total Bayar -->
                                        <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-gray-900 dark:text-white">
                                            {{ act.amount ? act.amount : '-' }}
                                        </td>
                                        
                                        <!-- Payment Method -->
                                        <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                            <span v-if="act.payment_method" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 uppercase">
                                                {{ act.payment_method }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">-</span>
                                        </td>
                                    </tr>
                                    
                                    <tr v-if="activities.data.length === 0">
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col items-center gap-3">
                                                <QueueListIcon class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                                <p>Belum ada aktivitas publik yang ditemukan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden space-y-3 p-2">
                        <div v-for="act in activities.data" :key="'mob-'+act.id" class="bg-white dark:bg-gray-800 rounded-xl p-3.5 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-2">
                            <div class="flex justify-between items-start gap-2 border-b border-gray-100 dark:border-gray-700/80 pb-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-xs text-gray-900 dark:text-white leading-tight truncate">{{ act.nama_siswa }}</h3>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ act.nama_kelas }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border', getTypeBadgeColor(act.type)]">
                                        <component :is="getTypeIcon(act.type)" class="w-3 h-3" />
                                        {{ act.title }}
                                    </span>
                                    <span v-if="act.payment_method" class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 uppercase">
                                        {{ act.payment_method }}
                                    </span>
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ act.description }}</p>

                            <!-- Mini Grid 3 Kolom: Pokok, Admin, Total jika ada amount -->
                            <div v-if="act.amount" class="grid grid-cols-3 gap-1.5 p-2 bg-gray-50 dark:bg-gray-900/60 rounded-lg text-center border border-gray-100 dark:border-gray-700/80">
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 block">Pokok</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 block truncate">{{ act.net_amount ? act.net_amount : act.amount }}</span>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 block">Admin</span>
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400 block truncate">{{ act.admin_fee_raw > 0 ? act.admin_fee : 'Rp 0' }}</span>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 block">Total</span>
                                    <span class="text-xs font-black text-gray-900 dark:text-white block truncate">{{ act.amount }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-[10px] text-gray-400 pt-1">
                                <span>{{ act.date }}</span>
                                <span>{{ act.date_full }}</span>
                            </div>
                        </div>
                        
                        <div v-if="activities.data.length === 0" class="text-center text-xs text-gray-500 py-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col items-center gap-2">
                                <QueueListIcon class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                                <p>Belum ada aktivitas publik yang ditemukan.</p>
                            </div>
                        </div>
                    </div>
                        
                        <!-- Pagination -->
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center sm:rounded-b-lg rounded-lg sm:mt-0 mt-2 shadow-sm sm:shadow-none gap-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                                <span v-if="activities.total > 0">
                                    Menampilkan <span class="font-medium">{{ activities.from }}</span>–<span class="font-medium">{{ activities.to }}</span>
                                    dari <span class="font-medium">{{ activities.total }}</span> aktivitas
                                </span>
                            </p>
                            <Pagination :links="activities.links" />
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- Export Modal -->
        <Modal :show="showExportModal" @close="closeExportModal">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Export Laporan Aktivitas</h2>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rentang Tanggal</label>
                        <div class="flex items-center gap-2">
                            <input type="date" v-model="startDate" :max="today" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" />
                            <span class="text-gray-500">-</span>
                            <input type="date" v-model="endDate" :max="today" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" />
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pengurutan (Sort)</label>
                        <select v-model="sort" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <option value="desc">Terbaru</option>
                            <option value="asc">Terlama</option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer mt-4">
                            <input type="checkbox" v-model="groupBy" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            Pisahkan per grup aktivitas (Tabel terpisah)
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <SecondaryButton @click="closeExportModal">Batal</SecondaryButton>
                    <button @click="exportData('pdf')" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cetak PDF
                    </button>
                    <button @click="exportData('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Export Excel
                    </button>
                </div>
            </div>
        </Modal>
        
    </AuthenticatedLayout>
</template>
