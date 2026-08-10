<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, LineElement, PointElement } from 'chart.js';
import { ref, computed, watch } from 'vue';
import { UserGroupIcon, UserPlusIcon, BanknotesIcon, ClockIcon, ArrowUpIcon, ArrowDownIcon, ExclamationTriangleIcon, DocumentTextIcon, ChartBarIcon, CheckCircleIcon, CalendarDaysIcon, UserMinusIcon } from '@heroicons/vue/24/outline';
import { debounce } from 'lodash';

import { usePage } from '@inertiajs/vue3';

// Registrasi komponen Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, LineElement, PointElement);

const props = defineProps({
    stats: Object,
    annual_stats: Object,
    grafikPendapatan: Object,
    grafikStatusTagihan: Object,
    grafikPendaftar: Object,
    aktivitasPublik: Array,
    siswaPerKelas: Array,
    latestJobs: Array,
    filters: Object,
    availableYears: Array,
    alerts: Object,
});

const page = usePage();
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const isSuperAdmin = computed(() => userRoles.value.includes('admin'));

const pageTitle = "Dashboard Admin";

// --- State untuk Tab & Toggle ---
const activeTab = ref('aktivitas');
const activeRevenueView = ref('total'); // 'total', 'xendit', 'manual'

// Helper untuk ikon aktivitas publik
const getActivityStyles = (type) => {
    switch(type) {
        case 'pendaftaran_pending': return { icon: ClockIcon, color: 'text-yellow-600 dark:text-yellow-400', bg: 'bg-yellow-100 dark:bg-yellow-900/30' };
        case 'pendaftaran_lunas': return { icon: UserPlusIcon, color: 'text-green-600 dark:text-green-400', bg: 'bg-green-100 dark:bg-green-900/30' };
        case 'pembayaran_lunas': return { icon: BanknotesIcon, color: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-100 dark:bg-blue-900/30' };
        case 'cuti_disetujui': return { icon: CalendarDaysIcon, color: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-100 dark:bg-indigo-900/30' };
        case 'siswa_resign': return { icon: UserMinusIcon, color: 'text-red-600 dark:text-red-400', bg: 'bg-red-100 dark:bg-red-900/30' };
        default: return { icon: DocumentTextIcon, color: 'text-gray-600 dark:text-gray-400', bg: 'bg-gray-100 dark:bg-gray-800' };
    }
};

// Computed property untuk menampilkan pendapatan dinamis
const displayedRevenue = computed(() => {
    switch (activeRevenueView.value) {
        case 'xendit':
            return props.stats.pendapatan.xendit;
        case 'manual':
            return props.stats.pendapatan.manual;
        default: // 'total'
            return props.stats.pendapatan.total;
    }
});

// Logika untuk filter bulan dan tahun
const selectedTahun = ref(props.filters.tahun);
const selectedBulan = ref(props.filters.bulan);
const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, name: new Date(0, i).toLocaleString('id-ID', { month: 'long' }) }));

const submitFilters = () => {
    router.get(route('admin.dashboard'), {
        tahun: selectedTahun.value,
        bulan: selectedBulan.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
watch([selectedTahun, selectedBulan], debounce(submitFilters, 300));


// Data & Options untuk Grafik
const formatCurrency = (value) => {
    if (value === null || value === undefined || isNaN(parseFloat(value))) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const barChartData = computed(() => ({
  labels: props.grafikPendapatan.labels,
  datasets: [{
      label: 'Pendapatan (IDR)',
      backgroundColor: 'rgba(79, 70, 229, 0.8)',
      borderColor: 'rgba(79, 70, 229, 1)',
      data: props.grafikPendapatan.data,
      borderRadius: 4,
  }],
}));

const lineChartData = computed(() => ({
  labels: props.grafikPendaftar.labels,
  datasets: [{
      label: 'Pendaftar Baru',
      backgroundColor: 'rgba(16, 185, 129, 0.2)',
      borderColor: 'rgba(16, 185, 129, 1)',
      pointBackgroundColor: 'rgba(16, 185, 129, 1)',
      data: props.grafikPendaftar.data,
      tension: 0.4,
      fill: true
  }],
}));

const doughnutChartData = computed(() => ({
    labels: props.grafikStatusTagihan.labels.map(label => label.charAt(0).toUpperCase() + label.slice(1).toLowerCase()),
    datasets: [{
        backgroundColor: props.grafikStatusTagihan.labels.map(label => {
            if (label === 'PAID') return '#22c55e'; // green-500
            if (label === 'PENDING') return '#f59e0b'; // amber-500
            if (label === 'EXPIRED') return '#ef4444'; // red-500
            if (label === 'FAILED') return '#64748b'; // slate-500
            return '#9ca3af'; // gray-400
        }),
        data: props.grafikStatusTagihan.data,
    }]
}));

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { ticks: { callback: value => 'Rp ' + (value / 1000) + 'k' } }
  }
};
const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, ticks: { precision: 0 } }
  }
};
const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
};

// Helper untuk status Job
const getJobStatusClass = (status) => {
    if (status === 'finished') return 'bg-green-100 text-green-800';
    if (status === 'failed') return 'bg-red-100 text-red-800';
    if (status === 'pending') return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
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
            <div class="max-w-7xl mx-auto">
                
                <!-- ============================================== -->
                <!-- SECTION 1: ANNUAL & OVERALL OVERVIEW (MODERN)  -->
                <!-- ============================================== -->
                <div v-if="isSuperAdmin" class="mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 px-1 sm:px-0 space-y-2 sm:space-y-0">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2 w-full sm:w-auto">
                            <ChartBarIcon class="w-6 h-6 text-indigo-500" />
                            Ringkasan Tahunan
                        </h3>
                        <!-- Year Filter for Annual Data -->
                        <select v-model="selectedTahun" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm w-full sm:w-auto">
                            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>

                    <!-- Top Annual Cards (Modern Glass/Gradient Style) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 px-1 sm:px-0">
                        <!-- Total Pendapatan Tahunan -->
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 opacity-20">
                                <BanknotesIcon class="w-32 h-32" />
                            </div>
                            <p class="text-indigo-100 text-sm font-medium uppercase tracking-wider relative z-10">Total Pendapatan {{ selectedTahun }}</p>
                            <p class="text-2xl font-bold mt-2 relative z-10">{{ formatCurrency(annual_stats.pendapatan_total) }}</p>
                        </div>

                        <!-- Payment Rate Tahunan -->
                        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                            <div class="absolute -right-6 -top-6 opacity-20">
                                <CheckCircleIcon class="w-32 h-32" />
                            </div>
                            <p class="text-emerald-100 text-sm font-medium uppercase tracking-wider relative z-10">Payment Rate {{ selectedTahun }}</p>
                            <div class="flex items-end gap-2 mt-2 relative z-10">
                                <p class="text-3xl font-bold">{{ annual_stats.payment_rate }}%</p>
                                <p class="text-sm mb-1 opacity-80">({{ annual_stats.tagihan_lunas_count }}/{{ annual_stats.tagihan_semua_count }} Tagihan)</p>
                            </div>
                        </div>

                        <!-- Total Siswa Aktif -->
                        <Link :href="route('admin.siswa.index')" class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden hover:-translate-y-1 transition transform duration-200 block">
                            <div class="absolute -right-6 -top-6 opacity-20">
                                <UserGroupIcon class="w-32 h-32" />
                            </div>
                            <p class="text-blue-100 text-sm font-medium uppercase tracking-wider relative z-10">Total Siswa Aktif</p>
                            <p class="text-3xl font-bold mt-2 relative z-10">{{ stats.total_siswa.value }}</p>
                        </Link>

                        <!-- Total Tunggakan Keseluruhan -->
                        <Link :href="route('admin.invoices.index', { status: 'PENDING' })" class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden hover:-translate-y-1 transition transform duration-200 block">
                            <div class="absolute -right-6 -top-6 opacity-20">
                                <DocumentTextIcon class="w-32 h-32" />
                            </div>
                            <p class="text-rose-100 text-sm font-medium uppercase tracking-wider relative z-10">Total Tunggakan (All Time)</p>
                            <p class="text-2xl font-bold mt-2 relative z-10">{{ formatCurrency(stats.total_tunggakan.total_amount) }}</p>
                            <p class="text-sm mt-1 opacity-80 relative z-10">{{ stats.total_tunggakan.count }} Tagihan Tertunda</p>
                        </Link>
                    </div>

                    <!-- Annual Charts -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4 px-1 sm:px-0">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tren Pendaftar Baru ({{ selectedTahun }})</h3>
                            <div class="mt-4 h-[250px]">
                                <Line :data="lineChartData" :options="lineChartOptions" />
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendapatan 6 Bulan Terakhir</h3>
                            <div class="mt-4 h-[250px]">
                                <Bar :data="barChartData" :options="barChartOptions" />
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Divider -->
                <div v-if="isSuperAdmin" class="relative py-4">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300 dark:border-gray-600 border-dashed"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-3 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Laporan Bulanan</span>
                    </div>
                </div>


                <!-- ============================================== -->
                <!-- SECTION 2: MONTHLY SNAPSHOT                    -->
                <!-- ============================================== -->
                <div class="mt-4 mb-8">
                    <!-- Month Filter -->
                    <div class="flex items-center justify-between mb-4 px-1 sm:px-0">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            Snapshot Bulan:
                            <select v-model="selectedBulan" class="text-lg font-bold border-none bg-transparent text-indigo-600 dark:text-indigo-400 focus:ring-0 p-0 ml-1 cursor-pointer">
                                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                            </select>
                        </h3>
                    </div>

                    <!-- Peringatan & Perhatian -->
                    <div v-if="alerts && (alerts.cuti_pending > 0 || alerts.expired_invoices > 0 || alerts.siswa_tanpa_tagihan > 0 || alerts.pendaftar_menunggu_spp > 0)" class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 px-1 sm:px-0">
                        <Link v-if="alerts.cuti_pending > 0" :href="route('admin.leaves.index', { status: 'pending' })" class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl hover:bg-yellow-100 transition-colors">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-400 dark:bg-yellow-600 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ alerts.cuti_pending }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">Pengajuan Cuti Menunggu</p>
                            </div>
                        </Link>
                        <Link v-if="alerts.expired_invoices > 0" :href="route('admin.invoices.index', { status: 'EXPIRED', periode_bulan: filters.bulan, periode_tahun: filters.tahun })" class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl hover:bg-red-100 transition-colors">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-500 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ alerts.expired_invoices }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-red-800 dark:text-red-300">Invoice Expired</p>
                            </div>
                        </Link>
                        
                        <Link v-if="alerts.pendaftar_menunggu_spp > 0" :href="route('admin.siswa.pendaftar_lunas')" class="flex items-center gap-3 p-4 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-700 rounded-xl hover:bg-teal-100 transition-colors">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-teal-500 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ alerts.pendaftar_menunggu_spp }}</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-teal-800 dark:text-teal-400">Aktivasi SPP Pendaftar</h4>
                                <p class="text-xs text-teal-600 dark:text-teal-500">Tentukan jadwal SPP.</p>
                            </div>
                        </Link>

                        <div v-if="alerts.siswa_tanpa_tagihan > 0" class="flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-xl">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-orange-400 dark:bg-orange-600 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ alerts.siswa_tanpa_tagihan }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-orange-800 dark:text-orange-300">Siswa Belum Ditagih SPP</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Stats Cards -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 px-1 sm:px-0">
                        <!-- Pendapatan Bulan Ini -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendapatan Bulan Ini</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(displayedRevenue) }}</p>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1 text-xs">
                                    <button @click="activeRevenueView = 'total'" :class="[activeRevenueView === 'total' ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 shadow' : 'text-gray-500 hover:text-gray-700', 'flex-1 px-2 py-1 rounded-md font-semibold transition-colors']">Total</button>
                                    <button @click="activeRevenueView = 'xendit'" :class="[activeRevenueView === 'xendit' ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 shadow' : 'text-gray-500 hover:text-gray-700', 'flex-1 px-2 py-1 rounded-md font-semibold transition-colors']">Xendit</button>
                                    <button @click="activeRevenueView = 'manual'" :class="[activeRevenueView === 'manual' ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 shadow' : 'text-gray-500 hover:text-gray-700', 'flex-1 px-2 py-1 rounded-md font-semibold transition-colors']">Manual</button>
                                </div>
                                <div v-if="activeRevenueView === 'total'" class="mt-2 flex items-center text-xs">
                                    <ArrowUpIcon v-if="stats.pendapatan.change >= 0" class="h-3 w-3 text-green-500 mr-1"/>
                                    <ArrowDownIcon v-else class="h-3 w-3 text-red-500 mr-1"/>
                                    <span :class="stats.pendapatan.change >= 0 ? 'text-green-600' : 'text-red-600'">{{ Math.abs(stats.pendapatan.change).toFixed(1) }}%</span>
                                    <span class="ml-1 text-gray-400">vs bln lalu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tagihan Tertunda Bulan Ini -->
                        <Link :href="route('admin.invoices.index', { status: 'PENDING', periode_bulan: filters.bulan, periode_tahun: filters.tahun })" class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tagihan Tertunda (Bulan Ini)</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.tagihan_tertunda.count }}</p>
                            <p class="mt-1 text-sm text-amber-600 dark:text-amber-400 font-medium">{{ formatCurrency(stats.tagihan_tertunda.total_amount) }}</p>
                        </Link>

                        <!-- Siswa Baru Bulan Ini -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa Baru Bergabung</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.siswa_baru.value }}</p>
                            <div class="mt-2 flex items-center text-xs">
                                <ArrowUpIcon v-if="stats.siswa_baru.change >= 0" class="h-3 w-3 text-green-500 mr-1"/>
                                <ArrowDownIcon v-else class="h-3 w-3 text-red-500 mr-1"/>
                                <span :class="stats.siswa_baru.change >= 0 ? 'text-green-600' : 'text-red-600'">{{ Math.abs(stats.siswa_baru.change).toFixed(1) }}%</span>
                                <span class="ml-1 text-gray-400">vs bln lalu</span>
                            </div>
                        </div>

                        <!-- Status Tagihan Chart -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 w-full text-left">Status Tagihan SPP</p>
                            <div v-if="grafikStatusTagihan.data.length > 0" class="h-[120px] w-full">
                                <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                            </div>
                            <div v-else class="h-[120px] flex items-center justify-center text-xs text-gray-400 text-center">
                                Tidak ada data tagihan
                            </div>
                        </div>
                    </div>
                </div>


                <!-- ============================================== -->
                <!-- SECTION 3: ACTIVITY & DETAILS                  -->
                <!-- ============================================== -->
                <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-3 px-1 sm:px-0">
                    <!-- Kartu Aktivitas dengan Tab -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700">
                        <div class="px-6 pt-6 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Aktivitas Terbaru</h3>
                            <Link :href="route('admin.laporan.aktivitas')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Lihat Semua</Link>
                        </div>
                        <div class="px-6 mt-4 border-b border-gray-100 dark:border-gray-700">
                                <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                                    <button @click="activeTab = 'aktivitas'" :class="[activeTab === 'aktivitas' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition']">Aktivitas Publik</button>
                                    <button @click="activeTab = 'jobs'" :class="[activeTab === 'jobs' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition']">Proses Latar</button>
                                </nav>
                            </div>
                        
                        <div class="p-6 max-h-[400px] overflow-y-auto">
                            <div v-if="activeTab === 'aktivitas'">
                                <ul role="list" class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <li v-for="(aktivitas, index) in aktivitasPublik" :key="'aktivitas-'+index" class="py-4 flex justify-between items-center group">
                                        <div class="flex items-start gap-4">
                                            <div :class="['flex-shrink-0 p-2 rounded-lg', getActivityStyles(aktivitas.type).bg]">
                                                <component :is="getActivityStyles(aktivitas.type).icon" :class="['w-5 h-5', getActivityStyles(aktivitas.type).color]" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ aktivitas.title }}</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ aktivitas.description }}</p>
                                                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                                    <ClockIcon class="w-3.5 h-3.5" />
                                                    {{ aktivitas.date_formatted }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-1.5 ml-4">
                                            <div class="flex items-center gap-3">
                                                <p v-if="aktivitas.amount_formatted" class="text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                                    {{ aktivitas.amount_formatted }}
                                                </p>
                                                <Link v-if="aktivitas.id_siswa" :href="route('admin.siswa.show', aktivitas.id_siswa)" class="hidden group-hover:flex p-1.5 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-md transition-colors" title="Lihat Profil Siswa">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                                </Link>
                                            </div>
                                            <span v-if="aktivitas.payment_method" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 uppercase">
                                                {{ aktivitas.payment_method }}
                                            </span>
                                        </div>
                                    </li>
                                    <li v-if="aktivitasPublik.length === 0" class="py-3 text-center text-sm text-gray-500">Belum ada aktivitas.</li>
                                </ul>
                            </div>
                            <div v-if="activeTab === 'jobs'">
                               <ul role="list" class="space-y-4">
                                   <li v-for="job in latestJobs" :key="job.id">
                                       <div class="flex justify-between items-center mb-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate pr-4" :title="job.name">{{ job.name }}</p>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="getJobStatusClass(job.status)">{{ job.status }}</span>
                                       </div>
                                       <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: job.progress + '%' }"></div>
                                       </div>
                                       <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex justify-between">
                                           <span>Oleh: {{ job.user_name }}</span>
                                           <span>{{ job.created_at }}</span>
                                       </div>
                                   </li>
                                   <li v-if="latestJobs.length === 0" class="py-3 text-center text-sm text-gray-500">Tidak ada proses berjalan.</li>
                               </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Siswa per Kelas -->
                    <div class="lg:col-span-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Siswa per Kelas</h3>
                            <ul role="list" class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                                <li v-for="kelas in siswaPerKelas" :key="kelas.nama_kelas" class="py-3 flex justify-between items-center">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ kelas.nama_kelas }}</p>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded-md">{{ kelas.jumlah_siswa }} Siswa</p>
                                </li>
                                <li v-if="siswaPerKelas.length === 0" class="py-3 text-center text-sm text-gray-500">Belum ada data kelas.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
