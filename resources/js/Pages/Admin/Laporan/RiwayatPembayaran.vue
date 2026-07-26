<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';
import { 
    CreditCardIcon, 
    MagnifyingGlassIcon,
    CurrencyDollarIcon,
    CalendarDaysIcon,
    CheckBadgeIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pageTitle: String,
    payments: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');

// Debounce search to avoid too many requests
watch(search, debounce(function (value) {
    router.get(
        route('admin.laporan.riwayat_pembayaran'),
        { search: value, type: typeFilter.value },
        { preserveState: true, replace: true }
    );
}, 300));

watch(typeFilter, function (value) {
    router.get(
        route('admin.laporan.riwayat_pembayaran'),
        { search: search.value, type: value },
        { preserveState: true, replace: true }
    );
});

// Helper for badge colors
const getTypeBadgeColor = (payment) => {
    if (payment.type === 'spp' || payment.is_single_gabungan) {
        return 'bg-blue-100 text-blue-800 border-blue-200';
    }
    if (payment.type === 'pendaftaran') {
        return 'bg-purple-100 text-purple-800 border-purple-200';
    }
    if (payment.type === 'pembayaran_gabungan' || payment.type === 'pembayaran_spp_gabungan') {
        return 'bg-teal-100 text-teal-800 border-teal-200';
    }
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

const getTypeLabel = (payment) => {
    if (payment.type === 'spp' || payment.is_single_gabungan) return 'SPP Bulanan';
    if (payment.type === 'pendaftaran') return 'Pendaftaran';
    if (payment.type === 'pembayaran_gabungan' || payment.type === 'pembayaran_spp_gabungan') return 'Tagihan Gabungan';
    return payment.type.toUpperCase();
};

const getTypeIcon = (payment) => {
    if (payment.type === 'spp' || payment.is_single_gabungan) return CalendarDaysIcon;
    if (payment.type === 'pembayaran_gabungan' || payment.type === 'pembayaran_spp_gabungan') return CalendarDaysIcon;
    if (payment.type === 'pendaftaran') return AcademicCapIcon;
    return CreditCardIcon;
};

</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

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
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Penerimaan Hari Ini</p>
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
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Penerimaan Bulan Ini</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.month }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200/50 dark:border-gray-700/50">
                    <div class="p-6">
                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
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
                            
                            <div class="w-full sm:w-auto">
                                <select 
                                    v-model="typeFilter"
                                    class="block w-full sm:w-48 rounded-xl border-gray-300 dark:border-gray-600 bg-white/50 dark:bg-gray-900/50 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm transition-colors"
                                >
                                    <option value="">Semua Jenis</option>
                                    <option value="spp">SPP Bulanan</option>
                                    <option value="pendaftaran">Pendaftaran</option>
                                    <option value="pembayaran_spp_gabungan">Tagihan Gabungan</option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal & Waktu</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa & Kelas</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nominal</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white/30 dark:bg-gray-900/30 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                        <!-- Paid At -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg">
                                                    <CheckBadgeIcon class="w-5 h-5" />
                                                </div>
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                                    {{ payment.paid_at }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <!-- Siswa -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ payment.siswa_nama }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ payment.kelas_nama }}</div>
                                        </td>
                                        
                                        <!-- Type & Desc -->
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col items-start gap-1.5">
                                                <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border', getTypeBadgeColor(payment)]">
                                                    <component :is="getTypeIcon(payment)" class="w-3.5 h-3.5" />
                                                    {{ getTypeLabel(payment) }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 max-w-[250px]" :title="payment.description">
                                                    {{ payment.description }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <!-- Amount -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ payment.total_amount_formatted }}
                                            </span>
                                        </td>
                                        
                                        <!-- Method -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                {{ payment.payment_method }}
                                            </span>
                                        </td>
                                    </tr>
                                    
                                    <tr v-if="payments.data.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col items-center gap-3">
                                                <CreditCardIcon class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                                <p>Belum ada riwayat pembayaran yang ditemukan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="mt-6">
                            <Pagination :links="payments.links" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
