<template>
    <div class="space-y-6">
        <!-- Global Summary Cards -->
        <div v-if="!summary_data" class="animate-pulse grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="i in 4" :key="i" class="h-32 bg-gray-200 dark:bg-gray-700 rounded-2xl"></div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Pendapatan Bulan Ini -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-6 -top-6 opacity-20">
                    <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-indigo-100 text-sm font-medium uppercase tracking-wider relative z-10">Pendapatan</p>
                <p class="text-2xl font-bold mt-2 relative z-10">{{ formatCurrency(summary_data.global.pendapatan) }}</p>
            </div>

            <!-- Pendaftar Bulan Ini -->
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-6 -top-6 opacity-20">
                    <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <p class="text-blue-100 text-sm font-medium uppercase tracking-wider relative z-10">Pendaftar</p>
                <p class="text-3xl font-bold mt-2 relative z-10">{{ summary_data.global.pendaftar }}</p>
            </div>

            <!-- Cuti Bulan Ini -->
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-6 -top-6 opacity-20">
                    <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <p class="text-amber-100 text-sm font-medium uppercase tracking-wider relative z-10">Siswa Cuti</p>
                <p class="text-3xl font-bold mt-2 relative z-10">{{ summary_data.global.cuti }}</p>
            </div>

            <!-- Keluar Bulan Ini -->
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-6 -top-6 opacity-20">
                    <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path></svg>
                </div>
                <p class="text-rose-100 text-sm font-medium uppercase tracking-wider relative z-10">Siswa Keluar</p>
                <p class="text-3xl font-bold mt-2 relative z-10">{{ summary_data.global.keluar }}</p>
            </div>
        </div>

        <!-- Per Kelas Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden mt-8">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Total Siswa Aktif Semua Kelas</h3>
            </div>
            
            <div v-if="!summary_data" class="p-6 animate-pulse space-y-4">
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-full" v-for="i in 5" :key="i"></div>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Kelas</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa Aktif</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendapatan</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendaftar</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cuti</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keluar</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="kelas in summary_data.per_kelas" :key="kelas.id_kelas" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ kelas.nama_kelas }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right font-bold text-blue-600 dark:text-blue-400">{{ kelas.siswa_aktif }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ formatCurrency(kelas.pendapatan) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ kelas.pendaftar }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ kelas.cuti }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ kelas.keluar }}</td>
                        </tr>
                        <tr v-if="summary_data.per_kelas.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data kelas yang tersedia.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="summary_data.per_kelas.length > 0" class="bg-gray-50 dark:bg-gray-900 border-t-2 border-gray-200 dark:border-gray-700">
                        <tr>
                            <th scope="row" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 text-left">Total Keseluruhan</th>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400 text-right">{{ summary_data.global.siswa_aktif }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 text-right">{{ formatCurrency(summary_data.global.pendapatan) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 text-right">{{ summary_data.global.pendaftar }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 text-right">{{ summary_data.global.cuti }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 text-right">{{ summary_data.global.keluar }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    summary_data: {
        type: Object,
        default: null
    }
});

const formatCurrency = (value) => {
    if (value === null || value === undefined || isNaN(parseFloat(value))) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
};
</script>
