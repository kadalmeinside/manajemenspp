<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    laporanData: Array,
    allKelas: Array,
    years: Array,
    filters: Object,
});

const pageTitle = "Laporan Pembayaran Bulanan";
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// State untuk filter
const selectedTahun = ref(props.filters.tahun);
const selectedKelasId = ref(props.filters.kelas_id || '');

// Fungsi untuk mengirim ulang filter
const submitFilters = () => {
    router.get(route('admin.laporan.pembayaran_bulanan'), {
        tahun: selectedTahun.value,
        kelas_id: selectedKelasId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Terapkan filter secara otomatis saat nilainya berubah dengan debounce
watch([selectedTahun, selectedKelasId], debounce(submitFilters, 300));

// Helper untuk styling status
const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    return 'text-gray-400 dark:text-gray-500'; // N/A
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
            <div class="max-w-full mx-auto">
                <div class="mb-6 p-4 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <div class="w-full sm:w-auto">
                            <label for="tahun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                            <select id="tahun" v-model="selectedTahun" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto">
                             <label for="kelas" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                            <select id="kelas" v-model="selectedKelasId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Semua Kelas</option>
                                <option v-for="kelas in allKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Laporan Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700 z-10 w-48">Nama Siswa</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Kelas</th>
                                    <th v-for="(month, index) in months" :key="index" scope="col" class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[100px]">
                                        {{ month }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="laporanData.length === 0">
                                    <td colspan="14" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada data untuk ditampilkan dengan filter yang dipilih.
                                    </td>
                                </tr>
                                <tr v-else v-for="siswa in laporanData" :key="siswa.id_siswa">
                                    <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-800">{{ siswa.nama_siswa }}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ siswa.nama_kelas }}</td>
                                    <td v-for="bulan in 12" :key="bulan" class="px-2 py-4 whitespace-nowrap text-center text-sm">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(siswa.statuses[bulan])">
                                            {{ siswa.statuses[bulan] }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
