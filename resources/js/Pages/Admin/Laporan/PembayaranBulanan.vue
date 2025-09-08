<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import TextInput from '@/Components/TextInput.vue';

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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filter Section -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                    </div>

                    <!-- Tabel dengan Header Sticky -->
                    <div class="relative max-h-[70vh] overflow-auto border-t dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                <tr>
                                    <th class="sticky left-0 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                                    <th v-for="month in months" :key="month" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ month }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="laporanData.data.length === 0">
                                    <td :colspan="14" class="px-6 py-10 text-center text-gray-500">Tidak ada data untuk ditampilkan.</td>
                                </tr>
                                <tr v-for="siswa in laporanData.data" :key="siswa.id_siswa">
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
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginasi -->
                    <div v-if="laporanData.links.length > 3" class="p-4 border-t dark:border-gray-700">
                        <div class="flex flex-wrap -mb-1 justify-center">
                            <template v-for="(link, key) in laporanData.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 border rounded select-none" v-html="link.label" />
                                <Link v-else
                                      class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                      :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600': link.active }"
                                      :href="link.url"
                                      v-html="link.label"
                                      preserve-scroll />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

