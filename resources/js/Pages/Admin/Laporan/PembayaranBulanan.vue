<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    pageTitle: String,
    laporanData: Object, // Ini adalah objek paginasi
    allKelas: Array,
    availableYears: Array,
    filters: Object,
});

// State untuk filter
const searchQuery = ref(props.filters.search || '');
const selectedTahun = ref(props.filters.tahun || new Date().getFullYear());
const selectedKelasId = ref(props.filters.kelas_id || '');

const months = Array.from({ length: 12 }, (_, i) => new Date(0, i).toLocaleString('id-ID', { month: 'short' }));

// Fungsi untuk mengirim filter ke backend
const submitFilters = () => {
    router.get(route('admin.laporan.pembayaran_bulanan'), {
        search: searchQuery.value,
        tahun: selectedTahun.value,
        kelas_id: selectedKelasId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['laporanData', 'filters'],
    });
};

// Terapkan filter secara otomatis saat ada perubahan (dengan debounce)
watch([searchQuery, selectedTahun, selectedKelasId], debounce(submitFilters, 300));

// URL untuk export (dengan filter aktif)
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    params.set('tahun', selectedTahun.value);
    if (selectedKelasId.value) params.set('kelas_id', selectedKelasId.value);
    if (searchQuery.value) params.set('search', searchQuery.value);
    return route('admin.laporan.export') + '?' + params.toString();
});

// Summary per baris (berapa bulan PAID per siswa)
const getRowPaidCount = (statuses) => {
    return Object.values(statuses).filter(s => s.status === 'PAID').length;
};

// Summary per kolom (total PAID per bulan dari data di halaman ini)
const columnSummary = computed(() => {
    const summary = {};
    for (let b = 1; b <= 12; b++) {
        summary[b] = { paid: 0, total: 0 };
    }
    if (!props.laporanData?.data) return summary;
    for (const siswa of props.laporanData.data) {
        for (let b = 1; b <= 12; b++) {
            const s = siswa.statuses[b]?.status;
            if (s && s !== 'N/A') summary[b].total++;
            if (s === 'PAID') summary[b].paid++;
        }
    }
    return summary;
});

// Helper untuk styling status
const getStatusClass = (status) => {
    const classes = {
        'PAID': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        'PENDING': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        'EXPIRED': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        'FAILED': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        'N/A': 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || '';
};

</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="hidden md:block font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-full mx-auto px-1 sm:px-0">
                <!-- Card Filter -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-4 sm:p-6">
                        <!-- Filter Section -->
                        <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                            <div class="w-full sm:flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <TextInput
                                    type="text"
                                    v-model="searchQuery"
                                    placeholder="Cari nama siswa..."
                                    class="w-full"
                                />
                                <select v-model="selectedKelasId" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                    <option value="">Semua Kelas</option>
                                    <option v-for="kelas in allKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                                </select>
                                <select v-model="selectedTahun" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                    <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>
                            <!-- Tombol Export -->
                            <a :href="exportUrl"
                               class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-md shadow-sm transition-colors flex-shrink-0"
                               title="Export ke Excel"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                <span class="hidden sm:inline">Export Excel</span>
                                <span class="sm:hidden">Export</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="bg-transparent sm:bg-white sm:dark:bg-gray-800 sm:shadow-sm sm:rounded-lg">
                    
                    <!-- Tabel dengan Header Sticky (Desktop) -->
                    <div class="hidden md:block relative max-h-[70vh] overflow-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                <tr>
                                    <th class="sticky left-0 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                                    <th v-for="month in months" :key="month" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ month }}</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-indigo-50 dark:bg-indigo-900/30">Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="laporanData.data.length === 0">
                                    <td :colspan="15" class="px-6 py-10 text-center text-gray-500">Tidak ada data untuk ditampilkan.</td>
                                </tr>
                                <tr v-for="siswa in laporanData.data" :key="siswa.id_siswa" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="sticky left-0 bg-white dark:bg-gray-800 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ siswa.nama_kelas }}</td>
                                    <td v-for="bulan in 12" :key="bulan" class="px-3 py-4 text-center">
                                        <!-- ### PERUBAHAN: Menambahkan badge titik untuk pembayaran manual ### -->
                                        <span 
                                            class="px-2 py-0.5 inline-flex items-center text-xs leading-5 font-semibold rounded-full" 
                                            :class="getStatusClass(siswa.statuses[bulan].status)"
                                            :title="siswa.statuses[bulan].status === 'PAID' && siswa.statuses[bulan].payment_method === 'manual' ? 'Pembayaran Manual' : ''"
                                        >
                                            <span v-if="siswa.statuses[bulan].status === 'PAID' && siswa.statuses[bulan].payment_method === 'manual'" class="h-2 w-2 mr-1.5 bg-slate-500 rounded-full"></span>
                                            {{ siswa.statuses[bulan].status }}
                                        </span>
                                    </td>
                                    <!-- Kolom total bayar per baris -->
                                    <td class="px-3 py-4 text-center bg-indigo-50/50 dark:bg-indigo-900/10">
                                        <span class="text-sm font-bold" :class="getRowPaidCount(siswa.statuses) === 12 ? 'text-green-600 dark:text-green-400' : getRowPaidCount(siswa.statuses) > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'">
                                            {{ getRowPaidCount(siswa.statuses) }}/12
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <!-- Baris summary bawah -->
                            <tfoot class="bg-gray-100 dark:bg-gray-700/50 sticky bottom-0 z-10 border-t-2 border-gray-300 dark:border-gray-600">
                                <tr>
                                    <td class="sticky left-0 bg-gray-100 dark:bg-gray-700/50 px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Total Bayar (hal ini)</td>
                                    <td class="px-6 py-3 text-xs text-gray-500"></td>
                                    <td v-for="b in 12" :key="'sum-'+b" class="px-3 py-3 text-center">
                                        <span class="text-xs font-bold" :class="columnSummary[b].paid > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-400'">
                                            {{ columnSummary[b].paid }}<span class="font-normal text-gray-400">/{{ columnSummary[b].total }}</span>
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center bg-indigo-50 dark:bg-indigo-900/20"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Tampilan Mobile (Card Grid) -->
                    <div class="block md:hidden space-y-4 mt-2">
                        <div v-if="laporanData.data.length === 0" class="text-center text-sm text-gray-500 py-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            Tidak ada data untuk ditampilkan.
                        </div>
                        <div v-for="siswa in laporanData.data" :key="'mob-'+siswa.id_siswa" class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start border-b border-gray-100 dark:border-gray-700 pb-2 mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ siswa.nama_kelas }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Total Dibayar</p>
                                    <span class="text-sm font-bold" :class="getRowPaidCount(siswa.statuses) === 12 ? 'text-green-600 dark:text-green-400' : getRowPaidCount(siswa.statuses) > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'">
                                        {{ getRowPaidCount(siswa.statuses) }} / 12
                                    </span>
                                </div>
                            </div>
                            <!-- Grid 3 kolom untuk bulan -->
                            <div class="grid grid-cols-3 gap-2">
                                <div v-for="(monthName, index) in months" :key="'mob-month-'+index" class="flex flex-col items-center p-2 rounded bg-gray-50 dark:bg-gray-700/50">
                                    <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ monthName }}</span>
                                    <span 
                                        class="px-1.5 py-0.5 inline-flex items-center text-[9px] leading-tight font-semibold rounded-sm w-full justify-center" 
                                        :class="getStatusClass(siswa.statuses[index+1].status)"
                                    >
                                        <span v-if="siswa.statuses[index+1].status === 'PAID' && siswa.statuses[index+1].payment_method === 'manual'" class="h-1.5 w-1.5 mr-1 bg-slate-500 rounded-full"></span>
                                        {{ siswa.statuses[index+1].status === 'PENDING' ? 'PEND' : (siswa.statuses[index+1].status === 'EXPIRED' ? 'EXP' : siswa.statuses[index+1].status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginasi -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center sm:rounded-b-lg rounded-lg sm:mt-0 mt-2 shadow-sm sm:shadow-none gap-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                            <span v-if="laporanData.total > 0">
                                Menampilkan <span class="font-medium">{{ laporanData.from }}</span>–<span class="font-medium">{{ laporanData.to }}</span>
                                dari <span class="font-medium">{{ laporanData.total }}</span> invoice
                            </span>
                        </p>
                        <Pagination :links="laporanData.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

