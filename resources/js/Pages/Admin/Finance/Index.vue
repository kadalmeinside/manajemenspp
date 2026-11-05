<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    BanknotesIcon, 
    ArrowDownTrayIcon, 
    ArrowPathIcon,
    CurrencyDollarIcon,
    BuildingLibraryIcon,
    CalendarIcon,
    FunnelIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    metrics: {
        type: Object,
        required: true
    },
    channelBreakdown: {
        type: Array,
        default: () => []
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
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'this_month',
            start_date: '',
            end_date: '',
            gateway: ''
        })
    }
});

const selectedPeriod = ref(props.filters.period || 'this_month');
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');
const selectedGateway = ref(props.filters.gateway || '');

function setPeriod(period) {
    selectedPeriod.value = period;
    if (period !== 'custom') {
        applyFilters();
    }
}

function applyFilters() {
    router.get(route('admin.finance.index'), {
        tab: props.currentTab,
        period: selectedPeriod.value,
        start_date: selectedPeriod.value === 'custom' ? customStartDate.value : undefined,
        end_date: selectedPeriod.value === 'custom' ? customEndDate.value : undefined,
        gateway: selectedGateway.value,
    }, { preserveState: true });
}

function filterGateway() {
    applyFilters();
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const periodLabels = {
    today: 'Hari Ini',
    last_7_days: '7 Hari Terakhir',
    this_month: 'Bulan Ini',
    last_month: 'Bulan Lalu',
    custom: 'Kustom Tanggal',
    all: 'Semua Waktu',
};

const statCards = computed(() => [
    {
        name: 'Pendapatan Bersih (Net)',
        value: props.metrics.period_net,
        icon: BanknotesIcon,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-100 dark:bg-emerald-900/50',
        desc: 'Dana murni hak sekolah'
    },
    {
        name: 'Biaya Layanan (MDR)',
        value: props.metrics.period_fee,
        icon: ArrowPathIcon,
        color: 'text-rose-600 dark:text-rose-400',
        bg: 'bg-rose-100 dark:bg-rose-900/50',
        desc: 'Potongan payment gateway'
    },
    {
        name: 'Pemasukan Kotor (Gross)',
        value: props.metrics.period_gross,
        icon: CurrencyDollarIcon,
        color: 'text-indigo-600 dark:text-indigo-400',
        bg: 'bg-indigo-100 dark:bg-indigo-900/50',
        desc: 'Total dibayar siswa'
    },
    {
        name: 'Transaksi Berhasil',
        valueText: (props.metrics.period_transactions_count || 0) + ' Transaksi',
        icon: CheckCircleIcon,
        color: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-100 dark:bg-blue-900/50',
        desc: 'Periode ' + (periodLabels[selectedPeriod.value] || 'Terpilih')
    },
    {
        name: 'Estimasi Dana Mengendap',
        value: props.metrics.pending_balance,
        icon: BuildingLibraryIcon,
        color: 'text-amber-600 dark:text-amber-400',
        bg: 'bg-amber-100 dark:bg-amber-900/50',
        desc: 'Total Net - Total Withdraw'
    }
]);

</script>

<template>
    <Head title="Laporan Keuangan & Rekonsiliasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        Laporan Keuangan & Rekonsiliasi
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Rekapitulasi penerimaan kas, potongan biaya layanan/MDR gateway, dan pencairan dana.
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto pb-12 px-2 sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Card -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5 mt-4 sm:mt-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Period Buttons -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <button
                            type="button"
                            @click="setPeriod('today')"
                            :class="[
                                selectedPeriod === 'today'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all'
                            ]"
                        >
                            Hari Ini
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('last_7_days')"
                            :class="[
                                selectedPeriod === 'last_7_days'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all'
                            ]"
                        >
                            7 Hari Terakhir
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('this_month')"
                            :class="[
                                selectedPeriod === 'this_month'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all'
                            ]"
                        >
                            Bulan Ini
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('last_month')"
                            :class="[
                                selectedPeriod === 'last_month'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all'
                            ]"
                        >
                            Bulan Lalu
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('all')"
                            :class="[
                                selectedPeriod === 'all'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all'
                            ]"
                        >
                            Semua Waktu
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'custom'"
                            :class="[
                                selectedPeriod === 'custom'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1'
                            ]"
                        >
                            <CalendarIcon class="w-3.5 h-3.5" />
                            Kustom
                        </button>
                    </div>

                    <!-- Gateway Filter -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Gateway:</span>
                        <select 
                            v-model="selectedGateway" 
                            @change="filterGateway"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-lg shadow-sm text-xs py-1.5"
                        >
                            <option value="">Semua Gateway</option>
                            <option value="midtrans">Midtrans</option>
                            <option value="xendit">Xendit</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Date Range Bar (Expanded if Custom) -->
                <div v-if="selectedPeriod === 'custom'" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Mulai:</span>
                        <input 
                            type="date" 
                            v-model="customStartDate" 
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg text-xs py-1.5 focus:border-indigo-500"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Sampai:</span>
                        <input 
                            type="date" 
                            v-model="customEndDate" 
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg text-xs py-1.5 focus:border-indigo-500"
                        />
                    </div>
                    <button 
                        @click="applyFilters"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition"
                    >
                        Terapkan
                    </button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <div v-for="stat in statCards" :key="stat.name"
                    class="relative bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700 transition-all hover:shadow-md">
                    
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ stat.name }}</p>
                        <div :class="['p-2 rounded-lg', stat.bg]">
                            <component :is="stat.icon" class="w-5 h-5" :class="stat.color" />
                        </div>
                    </div>

                    <div class="mt-2">
                        <p class="text-lg sm:text-xl font-black text-gray-900 dark:text-white">
                            {{ stat.valueText ? stat.valueText : formatCurrency(stat.value) }}
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ stat.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Breakdown Per Kanal Pembayaran -->
            <div v-if="channelBreakdown && channelBreakdown.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white">Rekapitulasi Kanal Pembayaran</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rincian penerimaan bersih vs potongan MDR per metode pembayaran pada periode ini.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 font-semibold rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        {{ channelBreakdown.length }} Kanal
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs sm:text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Kanal / Metode</th>
                                <th class="px-5 py-3 text-center font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Transaksi</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Nominal Pokok (Net)</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Biaya Admin (MDR)</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Total Kotor (Gross)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="ch in channelBreakdown" :key="ch.channel" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-5 py-3 font-semibold text-gray-900 dark:text-white">{{ ch.channel }}</td>
                                <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300">{{ ch.count }}</td>
                                <td class="px-5 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">{{ ch.net_formatted }}</td>
                                <td class="px-5 py-3 text-right text-rose-600 dark:text-rose-400 font-medium">{{ ch.fee_formatted }}</td>
                                <td class="px-5 py-3 text-right font-bold text-gray-900 dark:text-white">{{ ch.gross_formatted }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Navigation & Content -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex" aria-label="Tabs">
                        <Link
                            :href="route('admin.finance.index', { tab: 'invoices', period: selectedPeriod, start_date: customStartDate, end_date: customEndDate, gateway: selectedGateway })"
                            :class="[
                                currentTab === 'invoices'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 font-bold'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'w-1/2 py-3.5 px-2 text-center border-b-2 text-xs sm:text-sm transition-colors duration-200'
                            ]"
                        >
                            Riwayat Tagihan Terbayar
                        </Link>
                        <Link
                            :href="route('admin.finance.index', { tab: 'withdrawals', period: selectedPeriod, start_date: customStartDate, end_date: customEndDate })"
                            :class="[
                                currentTab === 'withdrawals'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 font-bold'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'w-1/2 py-3.5 px-2 text-center border-b-2 text-xs sm:text-sm transition-colors duration-200'
                            ]"
                        >
                            Riwayat Penarikan Dana (Withdrawals)
                        </Link>
                    </nav>
                </div>

                <!-- Tab Content: Invoices -->
                <div v-if="currentTab === 'invoices'" class="p-3 sm:p-6">
                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs sm:text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Gateway / Kanal</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Tanggal Bayar</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Siswa & Kelas</th>
                                    <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Pokok (Net)</th>
                                    <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Biaya Admin</th>
                                    <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Total (Gross)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                            {{ invoice.payment_gateway }}
                                        </span>
                                        <span v-if="invoice.payment_method !== '-'" class="ml-1.5 text-xs text-gray-500 dark:text-gray-400">
                                            {{ invoice.payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-300">{{ formatDate(invoice.paid_at) }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ invoice.siswa?.nama_siswa || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ invoice.siswa?.kelas?.nama_kelas || '-' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right font-medium text-emerald-600 dark:text-emerald-400">{{ invoice.amount_formatted }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right text-rose-600 dark:text-rose-400">{{ invoice.admin_fee_formatted }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-gray-900 dark:text-white">{{ invoice.total_amount_formatted }}</td>
                                </tr>
                                <tr v-if="!invoices.data.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat pembayaran pada periode ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-3">
                        <div v-if="!invoices.data.length" class="text-center py-8 text-gray-500 text-xs">
                            Belum ada riwayat pembayaran pada periode ini.
                        </div>
                        <div v-for="invoice in invoices.data" :key="'mob-inv-'+invoice.id" class="bg-gray-50 dark:bg-gray-900/60 rounded-xl p-3.5 border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <p class="font-bold text-xs text-gray-900 dark:text-white">{{ invoice.siswa?.nama_siswa || '-' }}</p>
                                    <p class="text-[11px] text-gray-500">{{ invoice.siswa?.kelas?.nama_kelas || '-' }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                    {{ invoice.payment_gateway }} {{ invoice.payment_method !== '-' ? `(${invoice.payment_method})` : '' }}
                                </span>
                            </div>

                            <p class="text-[11px] text-gray-400 mt-1">Lunas: {{ formatDate(invoice.paid_at) }}</p>

                            <!-- 3 Mini Columns for Pokok, Admin, Total -->
                            <div class="grid grid-cols-3 gap-1.5 p-2 bg-white dark:bg-gray-800 rounded-lg text-center mt-2.5 border border-gray-100 dark:border-gray-700/80">
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 block">Pokok (Net)</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 block">{{ invoice.amount_formatted }}</span>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 block">Admin (Fee)</span>
                                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400 block">{{ invoice.admin_fee_formatted }}</span>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-gray-400 block">Total Kotor</span>
                                    <span class="text-xs font-black text-gray-900 dark:text-white block">{{ invoice.total_amount_formatted }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" v-if="invoices.links?.length > 3">
                        <Pagination :links="invoices.links" />
                    </div>
                </div>

                <!-- Tab Content: Withdrawals -->
                <div v-if="currentTab === 'withdrawals'" class="p-3 sm:p-6">
                    <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs sm:text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Tanggal Withdraw</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Disbursement ID</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Tujuan Bank</th>
                                    <th class="px-5 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Nominal</th>
                                    <th class="px-5 py-3 text-center font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-[11px]">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-5 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-300">{{ formatDate(withdrawal.completed_at || withdrawal.created_at) }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-400 font-mono text-xs">{{ withdrawal.xendit_disbursement_id }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ withdrawal.bank_code }} - {{ withdrawal.account_number }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ withdrawal.account_name }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right text-amber-600 dark:text-amber-400 font-bold">{{ formatCurrency(withdrawal.amount) }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                        <span :class="[
                                            withdrawal.status === 'COMPLETED' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 
                                            withdrawal.status === 'FAILED' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                                            'px-2.5 py-0.5 rounded-full text-xs font-semibold'
                                        ]">
                                            {{ withdrawal.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!withdrawals.data.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat penarikan dana pada periode ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View for Withdrawals -->
                    <div class="md:hidden space-y-3">
                        <div v-if="!withdrawals.data.length" class="text-center py-8 text-gray-500 text-xs">
                            Belum ada riwayat penarikan dana pada periode ini.
                        </div>
                        <div v-for="withdrawal in withdrawals.data" :key="'mob-wd-'+withdrawal.id" class="bg-gray-50 dark:bg-gray-900/60 rounded-xl p-3.5 border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <p class="font-bold text-xs text-gray-900 dark:text-white">{{ withdrawal.bank_code }} - {{ withdrawal.account_number }}</p>
                                    <p class="text-[11px] text-gray-500">{{ withdrawal.account_name }}</p>
                                </div>
                                <span :class="[
                                    withdrawal.status === 'COMPLETED' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 
                                    withdrawal.status === 'FAILED' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                                    'px-2 py-0.5 rounded-full text-[10px] font-semibold'
                                ]">
                                    {{ withdrawal.status }}
                                </span>
                            </div>
                            <div class="flex justify-between items-end mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-[10px] text-gray-400">{{ formatDate(withdrawal.completed_at || withdrawal.created_at) }}</span>
                                <span class="text-xs font-black text-amber-600 dark:text-amber-400">{{ formatCurrency(withdrawal.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" v-if="withdrawals.links?.length > 3">
                        <Pagination :links="withdrawals.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
