<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    BanknotesIcon, 
    ArrowDownTrayIcon, 
    ArrowPathIcon,
    CurrencyDollarIcon,
    BuildingLibraryIcon
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    metrics: {
        type: Object,
        required: true
    },
    invoices: {
        type: Object,
        default: () => ({ data: [] })
    },
    withdrawals: {
        type: Object,
        default: () => ({ data: [] })
    },
    currentTab: {
        type: String,
        default: 'invoices'
    },
    gateway: {
        type: String,
        default: ''
    }
});

const selectedGateway = ref(props.gateway || '');

function filterGateway() {
    router.get(route('admin.finance.index'), {
        tab: 'invoices',
        gateway: selectedGateway.value,
    }, { preserveState: true });
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const statCards = computed(() => [
    {
        name: 'Estimasi Dana Mengendap',
        value: props.metrics.pending_balance,
        icon: BuildingLibraryIcon,
        color: 'text-indigo-600 dark:text-indigo-400',
        bg: 'bg-indigo-100 dark:bg-indigo-900/50',
        desc: 'Saldo tersedia di Xendit'
    },
    {
        name: 'Pemasukan Kotor',
        value: props.metrics.total_gross,
        icon: CurrencyDollarIcon,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-100 dark:bg-emerald-900/50',
        desc: 'Total dibayar siswa'
    },
    {
        name: 'Biaya Layanan',
        value: props.metrics.total_fee,
        icon: ArrowPathIcon,
        color: 'text-rose-600 dark:text-rose-400',
        bg: 'bg-rose-100 dark:bg-rose-900/50',
        desc: 'Potongan fee Xendit'
    },
    {
        name: 'Pendapatan Bersih',
        value: props.metrics.total_net,
        icon: BanknotesIcon,
        color: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-100 dark:bg-blue-900/50',
        desc: 'Kotor dikurangi biaya'
    },
    {
        name: 'Dana Telah Ditarik',
        value: props.metrics.total_withdraw,
        icon: ArrowDownTrayIcon,
        color: 'text-amber-600 dark:text-amber-400',
        bg: 'bg-amber-100 dark:bg-amber-900/50',
        desc: 'Total Withdraw ke Bank'
    }
]);

</script>

<template>
    <Head title="Laporan Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Laporan Keuangan
                </h2>
            </div>
        </template>

        <div class="max-w-7xl mx-auto pb-12 sm:px-6 lg:px-8 space-y-6">
            <!-- Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mt-6">
                <div v-for="stat in statCards" :key="stat.name"
                    class="relative bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 border border-gray-200 dark:border-gray-700 transition-shadow hover:shadow-md">
                    
                    <!-- Background Watermark Icon -->
                    <div class="absolute -right-4 -bottom-4 opacity-10 dark:opacity-[0.05] pointer-events-none">
                        <component :is="stat.icon" class="w-24 h-24" :class="stat.color.split(' ')[0]" />
                    </div>

                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ stat.name }}</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ formatCurrency(stat.value) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-4">{{ stat.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation & Content -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex" aria-label="Tabs">
                        <Link
                            :href="route('admin.finance.index', { tab: 'invoices' })"
                            :class="[
                                currentTab === 'invoices'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600',
                                'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                            ]"
                        >
                            Riwayat Pemasukan (Invoices)
                        </Link>
                        <Link
                            :href="route('admin.finance.index', { tab: 'withdrawals' })"
                            :class="[
                                currentTab === 'withdrawals'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600',
                                'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                            ]"
                        >
                            Riwayat Penarikan (Withdrawals)
                        </Link>
                    </nav>
                </div>

                <!-- Tab Content: Invoices -->
                <div v-if="currentTab === 'invoices'" class="p-0 sm:p-6">
                    <div class="px-6 py-4 flex justify-end">
                        <select 
                            v-model="selectedGateway" 
                            @change="filterGateway"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm text-sm"
                        >
                            <option value="">Semua Gateway</option>
                            <option value="xendit">Xendit</option>
                            <option value="midtrans">Midtrans</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gateway</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Bayar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kotor (Gross)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Potongan (Fee)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bersih (Net)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ formatDate(invoice.paid_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium">{{ invoice.siswa?.nama_siswa || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ invoice.siswa?.kelas?.nama_kelas || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatCurrency(invoice.total_amount) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-rose-600 dark:text-rose-400">{{ formatCurrency(invoice.admin_fee) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ formatCurrency(invoice.amount) }}</td>
                                </tr>
                                <tr v-if="!invoices.data.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat pembayaran yang lunas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 px-6" v-if="invoices.links?.length > 3">
                        <Pagination :links="invoices.links" />
                    </div>
                </div>

                <!-- Tab Content: Withdrawals -->
                <div v-if="currentTab === 'withdrawals'" class="p-0 sm:p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Withdraw</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Disbursement ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tujuan Bank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nominal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ formatDate(withdrawal.completed_at || withdrawal.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ withdrawal.xendit_disbursement_id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium">{{ withdrawal.bank_code }} - {{ withdrawal.account_number }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ withdrawal.account_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-amber-600 dark:text-amber-400 font-medium">{{ formatCurrency(withdrawal.amount) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="[
                                            withdrawal.status === 'COMPLETED' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 
                                            withdrawal.status === 'FAILED' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                                            'px-2.5 py-1 rounded-full text-xs font-medium'
                                        ]">
                                            {{ withdrawal.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!withdrawals.data.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat penarikan dana.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 px-6" v-if="withdrawals.links?.length > 3">
                        <Pagination :links="withdrawals.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
