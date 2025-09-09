<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { ref, computed, watch } from 'vue';
import { UserGroupIcon, UserPlusIcon, BanknotesIcon, ClockIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/24/outline';
import { debounce } from 'lodash';

// Registrasi komponen Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    stats: Object,
    grafikPendapatan: Object,
    grafikStatusTagihan: Object,
    pembayaranTerakhir: Array,
    siswaBaru: Array,
    siswaPerKelas: Array,
    latestJobs: Array,
    filters: Object,
    availableYears: Array,
});

const pageTitle = "Dashboard Admin";

// --- State untuk Tab & Toggle ---
const activeTab = ref('pembayaran');
const activeRevenueView = ref('total'); // 'total', 'xendit', 'manual'

// ### PEMBARUAN: Computed property untuk menampilkan pendapatan dinamis ###
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <!-- Filter Section -->
                <div class="mb-6 flex justify-end items-center gap-2">
                    <select v-model="selectedBulan" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                        <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                    </select>
                    <select v-model="selectedTahun" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                        <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link :href="route('admin.siswa.index')" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-5 group transition hover:shadow-lg hover:-translate-y-1">
                        <div class="flex items-start justify-between">
                            <div class="w-0 flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Siswa Aktif</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_siswa.value }}</p>
                            </div>
                            <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-500">
                                <UserGroupIcon class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </Link>
                     <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-5">
                        <div class="flex items-start justify-between">
                            <div class="w-0 flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Siswa Baru</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.siswa_baru.value }}</p>
                            </div>
                            <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-teal-500">
                                <UserPlusIcon class="h-6 w-6 text-white" />
                            </div>
                        </div>
                         <div class="mt-4 flex items-center text-sm">
                             <ArrowUpIcon v-if="stats.siswa_baru.change >= 0" class="h-4 w-4 text-green-500 mr-1"/>
                             <ArrowDownIcon v-else class="h-4 w-4 text-red-500 mr-1"/>
                             <span :class="stats.siswa_baru.change >= 0 ? 'text-green-600' : 'text-red-600'">{{ Math.abs(stats.siswa_baru.change).toFixed(1) }}%</span>
                            <span class="ml-1 text-gray-500 dark:text-gray-400">vs bulan lalu</span>
                        </div>
                    </div>
                    
                    <!-- ### PEMBARUAN: Kartu pendapatan dengan toggle ### -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between">
                                <div class="w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Pendapatan</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(displayedRevenue) }}</p>
                                </div>
                                <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-green-500">
                                    <BanknotesIcon class="h-6 w-6 text-white" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="mt-4 flex items-center bg-gray-100 dark:bg-gray-700 rounded-md p-1 text-xs">
                                <button @click="activeRevenueView = 'total'" :class="[activeRevenueView === 'total' ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 shadow' : 'text-gray-500 hover:text-gray-700', 'flex-1 px-2 py-1 rounded-md font-semibold transition-colors']">Total</button>
                                <button @click="activeRevenueView = 'xendit'" :class="[activeRevenueView === 'xendit' ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 shadow' : 'text-gray-500 hover:text-gray-700', 'flex-1 px-2 py-1 rounded-md font-semibold transition-colors']">Xendit</button>
                                <button @click="activeRevenueView = 'manual'" :class="[activeRevenueView === 'manual' ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 shadow' : 'text-gray-500 hover:text-gray-700', 'flex-1 px-2 py-1 rounded-md font-semibold transition-colors']">Manual</button>
                            </div>
                            <div v-if="activeRevenueView === 'total'" class="mt-3 flex items-center text-sm">
                                <ArrowUpIcon v-if="stats.pendapatan.change >= 0" class="h-4 w-4 text-green-500 mr-1"/>
                                <ArrowDownIcon v-else class="h-4 w-4 text-red-500 mr-1"/>
                                <span :class="stats.pendapatan.change >= 0 ? 'text-green-600' : 'text-red-600'">{{ Math.abs(stats.pendapatan.change).toFixed(1) }}%</span>
                                <span class="ml-1 text-gray-500 dark:text-gray-400">vs bulan lalu</span>
                            </div>
                            <div v-else class="mt-3 h-[20px]"></div> <!-- Placeholder for height consistency -->
                        </div>
                    </div>

                    <Link :href="route('admin.invoices.index', { status: 'PENDING', periode_bulan: filters.bulan, periode_tahun: filters.tahun })" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-5 group transition hover:shadow-lg hover:-translate-y-1">
                         <div class="flex items-start justify-between">
                            <div class="w-0 flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Tagihan Tertunda</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.tagihan_tertunda.count }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(stats.tagihan_tertunda.total_amount) }}</p>
                            </div>
                            <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-amber-500">
                                <ClockIcon class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Grafik Section -->
                <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Pendapatan 6 Bulan Terakhir</h3>
                            <div class="mt-4 h-[300px]">
                                <Bar :data="barChartData" :options="barChartOptions" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Status Tagihan ({{ months[selectedBulan - 1].name }})</h3>
                            <div v-if="grafikStatusTagihan.data.length > 0" class="mt-4 h-[300px]">
                                <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                            </div>
                            <div v-else class="mt-4 flex items-center justify-center h-[300px] text-center text-gray-500">
                                <p>Tidak ada data tagihan<br>pada periode ini.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aktivitas & Laporan Section -->
                <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <!-- Kartu Aktivitas dengan Tab -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Aktivitas Terbaru</h3>
                            <div class="mt-4 border-b border-gray-200 dark:border-gray-700">
                                <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                                    <button @click="activeTab = 'pembayaran'" :class="[activeTab === 'pembayaran' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm']">Pembayaran</button>
                                    <button @click="activeTab = 'siswa'" :class="[activeTab === 'siswa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm']">Siswa Baru</button>
                                    <button @click="activeTab = 'jobs'" :class="[activeTab === 'jobs' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm']">Proses Latar</button>
                                </nav>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div v-if="activeTab === 'pembayaran'">
                                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <li v-for="(pembayaran, index) in pembayaranTerakhir" :key="'pembayaran-'+index" class="py-3 flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ pembayaran.nama_siswa }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ pembayaran.tanggal_bayar }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">{{ pembayaran.total_tagihan_formatted }}</p>
                                    </li>
                                    <li v-if="pembayaranTerakhir.length === 0" class="py-3 text-center text-sm text-gray-500">Belum ada pembayaran.</li>
                                </ul>
                            </div>
                            <div v-if="activeTab === 'siswa'">
                                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <li v-for="(siswa, index) in siswaBaru" :key="'siswa-'+index" class="py-3 flex justify-between items-center">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ siswa.nama_siswa }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ siswa.tanggal_bergabung }}</p>
                                    </li>
                                    <li v-if="siswaBaru.length === 0" class="py-3 text-center text-sm text-gray-500">Belum ada siswa baru.</li>
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
                                           <span>Dijalankan oleh: {{ job.user_name }}</span>
                                           <span>{{ job.created_at }}</span>
                                       </div>
                                   </li>
                                   <li v-if="latestJobs.length === 0" class="py-3 text-center text-sm text-gray-500">Tidak ada proses berjalan.</li>
                               </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Siswa per Kelas -->
                    <div class="lg:col-span-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Siswa per Kelas</h3>
                            <ul role="list" class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="kelas in siswaPerKelas" :key="kelas.nama_kelas" class="py-3 flex justify-between items-center">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ kelas.nama_kelas }}</p>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ kelas.jumlah_siswa }} Siswa</p>
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

