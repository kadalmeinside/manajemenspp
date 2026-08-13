<template>
    <Head title="Laporan Analitik" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Laporan Analitik & Tren
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Header Actions & Filters -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Filter Analitik</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih periode untuk melihat perbandingan data.</p>
                    </div>
                    <form @submit.prevent="updateFilters" class="flex flex-wrap sm:flex-nowrap items-center gap-3">
                        <select v-model="form.bulan" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option v-for="(nama, index) in namaBulan" :key="index" :value="index + 1">{{ nama }}</option>
                        </select>
                        
                        <select v-model="form.tahun" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                        </select>
                        
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm disabled:opacity-50">
                            Terapkan
                        </button>
                    </form>
                </div>

                <!-- Revenue MoM Card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Pendapatan Bulan Ini</h3>
                        <div v-if="!revenue_mom" class="animate-pulse flex space-x-4">
                            <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                        <div v-else>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(revenue_mom.current) }}
                            </div>
                            <div class="mt-2 flex items-center text-sm">
                                <span :class="revenue_mom.is_positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" class="font-medium flex items-center">
                                    <svg v-if="revenue_mom.is_positive" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                                    {{ revenue_mom.change_percentage > 0 ? '+' : '' }}{{ revenue_mom.change_percentage }}%
                                </span>
                                <span class="ml-2 text-gray-500 dark:text-gray-400">vs bulan lalu ({{ formatCurrency(revenue_mom.previous) }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods (Donut) -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Metode Pembayaran (Pendapatan)</h3>
                        <div v-if="!payment_methods" class="animate-pulse flex justify-center">
                            <div class="h-32 w-32 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        </div>
                        <div v-else class="h-48 relative flex justify-center">
                            <Doughnut :data="paymentMethodsChartData" :options="donutChartOptions" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Registration Trends (Line Chart) -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Tren Pendaftaran (6 Bulan)</h3>
                        <div v-if="!registration_trends" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div v-else class="h-64 relative">
                            <Line :data="registrationTrendsChartData" :options="lineChartOptions" />
                        </div>
                    </div>

                    <!-- Resignation Rate (Bar Chart) -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Siswa Keluar / Resign per Kelas</h3>
                        <div v-if="!resignation_rate" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div v-else class="h-64 relative">
                            <Bar :data="resignationRateChartData" :options="barChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Payment Rate Per Class (Horizontal Bar or Table/Bar) -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Tingkat Pelunasan SPP per Kelas</h3>
                    <div v-if="!payment_rate" class="animate-pulse space-y-4">
                        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                    </div>
                    <div v-else>
                        <div class="h-96 relative">
                            <Bar :data="paymentRateChartData" :options="horizontalBarChartOptions" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Chart.js imports
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    LineElement,
    PointElement,
    ArcElement,
    Filler
} from 'chart.js';
import { Bar, Line, Doughnut } from 'vue-chartjs';

ChartJS.register(
    Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale,
    LineElement, PointElement, ArcElement, Filler
);

const props = defineProps({
    filters: Object,
    availableYears: Array,
    // Lazy loaded props
    revenue_mom: Object,
    payment_rate: Array,
    registration_trends: Object,
    payment_methods: Object,
    resignation_rate: Object,
});

const form = useForm({
    tahun: props.filters.tahun,
    bulan: props.filters.bulan,
});

const namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
};

const updateFilters = () => {
    form.get(route('admin.analytics.index'), {
        preserveState: true,
        preserveScroll: true,
        only: ['revenue_mom', 'payment_rate', 'registration_trends', 'payment_methods', 'resignation_rate', 'filters']
    });
};

onMounted(() => {
    // Request lazy loaded props immediately upon mount
    router.reload({
        only: ['revenue_mom', 'payment_rate', 'registration_trends', 'payment_methods', 'resignation_rate']
    });
});

// --- Chart Configurations ---

// 1. Payment Methods (Donut)
const paymentMethodsChartData = computed(() => {
    if (!props.payment_methods) return { labels: [], datasets: [] };
    return {
        labels: props.payment_methods.labels,
        datasets: [{
            data: props.payment_methods.data_revenue,
            backgroundColor: ['#3b82f6', '#94a3b8'], // Blue for Xendit, Slate for Manual
            borderWidth: 0,
            hoverOffset: 4
        }]
    };
});
const donutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.label || '';
                    if (label) { label += ': '; }
                    if (context.parsed !== null) {
                        label += formatCurrency(context.parsed);
                    }
                    return label;
                }
            }
        }
    }
};

// 2. Registration Trends (Line)
const registrationTrendsChartData = computed(() => {
    if (!props.registration_trends) return { labels: [], datasets: [] };
    return {
        labels: props.registration_trends.labels,
        datasets: [{
            label: 'Pendaftar Baru',
            data: props.registration_trends.data,
            borderColor: '#10b981', // Emerald
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#10b981',
        }]
    };
});
const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
    }
};

// 3. Resignation Rate (Bar)
const resignationRateChartData = computed(() => {
    if (!props.resignation_rate) return { labels: [], datasets: [] };
    return {
        labels: props.resignation_rate.labels,
        datasets: [{
            label: 'Jumlah Resign',
            data: props.resignation_rate.data,
            backgroundColor: '#f43f5e', // Rose
            borderRadius: 4,
        }]
    };
});
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } }
    }
};

// 4. Payment Rate Per Class (Horizontal Bar)
const paymentRateChartData = computed(() => {
    if (!props.payment_rate) return { labels: [], datasets: [] };
    return {
        labels: props.payment_rate.map(r => r.nama_kelas),
        datasets: [{
            label: 'Tingkat Pelunasan (%)',
            data: props.payment_rate.map(r => r.payment_rate),
            backgroundColor: props.payment_rate.map(r => r.payment_rate >= 80 ? '#10b981' : (r.payment_rate >= 50 ? '#f59e0b' : '#ef4444')),
            borderRadius: 4,
        }]
    };
});
const horizontalBarChartOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: function(context) {
                    const dataIndex = context.dataIndex;
                    const rateData = props.payment_rate[dataIndex];
                    return `${context.parsed.x}% (${rateData.paid} dari ${rateData.total} lunas)`;
                }
            }
        }
    },
    scales: {
        x: { beginAtZero: true, max: 100 }
    }
};

</script>
