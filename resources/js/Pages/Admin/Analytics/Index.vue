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
                        <select v-model="form.kelas" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Kelas</option>
                            <option v-for="kelas in availableKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                        </select>

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

                <!-- ========================================== -->
                <!-- ADVANCED ANALYTICS (SaaS METRICS)          -->
                <!-- ========================================== -->
                <div class="mt-8 mb-4">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Metrik Finansial Lanjutan (SaaS)</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Proyeksi jangka panjang dan analisis kesehatan arus kas.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- MRR & ARR -->
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 p-6 rounded-2xl shadow-md text-white relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 opacity-10">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <h3 class="text-indigo-100 text-sm font-bold uppercase tracking-wider mb-2 relative z-10">Monthly Recurring Revenue (MRR)</h3>
                        <div v-if="!mrr_data" class="animate-pulse h-10 bg-indigo-500/50 rounded w-1/2"></div>
                        <div v-else class="relative z-10">
                            <p class="text-3xl font-extrabold">{{ formatCurrency(mrr_data.mrr) }}</p>
                            <p class="text-sm mt-2 opacity-80">ARR (Tahunan): {{ formatCurrency(mrr_data.arr) }}</p>
                        </div>
                    </div>

                    <!-- CLTV -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-6 rounded-2xl shadow-md text-white relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 opacity-10">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-emerald-100 text-sm font-bold uppercase tracking-wider mb-2 relative z-10">Customer Lifetime Value (CLTV)</h3>
                        <div v-if="!cltv_data" class="animate-pulse h-10 bg-emerald-400/50 rounded w-1/2"></div>
                        <div v-else class="relative z-10">
                            <p class="text-3xl font-extrabold">{{ formatCurrency(cltv_data.cltv) }}</p>
                            <p class="text-sm mt-2 opacity-80">Masa Retensi Rata-rata: {{ cltv_data.avg_retention_months }} Bulan</p>
                        </div>
                    </div>

                    <!-- Time to Pay -->
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 p-6 rounded-2xl shadow-md text-white relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 opacity-10">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-blue-100 text-sm font-bold uppercase tracking-wider mb-2 relative z-10">Time-to-Pay (Rata-rata)</h3>
                        <div v-if="!time_to_pay" class="animate-pulse h-10 bg-blue-400/50 rounded w-1/2"></div>
                        <div v-else class="relative z-10">
                            <p class="text-3xl font-extrabold">{{ time_to_pay.avg_days }} Hari</p>
                            <p class="text-sm mt-2 opacity-80">Jarak rata-rata terbit tagihan ke lunas</p>
                        </div>
                    </div>
                </div>

                <!-- Aging Receivables Chart -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Aging Receivables (Tunggakan Usia)</h3>
                    <div v-if="!aging_receivables" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div v-else class="h-64 relative">
                        <Bar :data="agingReceivablesChartData" :options="agingReceivablesChartOptions" />
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- KELAS COMPARISON                          -->
                <!-- ========================================== -->
                <div class="mt-8 mb-4">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Perbandingan Kinerja Antar Kelas</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Rangkuman distribusi pendapatan dan siswa baru (Tampil untuk semua kelas tanpa filter).</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Kelas Revenue -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Porsi Pendapatan Kelas</h3>
                        <div v-if="!revenue_per_kelas" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded w-64 mx-auto"></div>
                        <div v-else class="h-64 relative flex justify-center">
                            <Doughnut :data="kelasRevenueChartData" :options="kelasRevenueChartOptions" />
                        </div>
                    </div>

                    <!-- Kelas Registrations -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Pendaftar Baru per Kelas</h3>
                        <div v-if="!registration_per_kelas" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div v-else class="h-64 relative">
                            <Bar :data="kelasRegistrationChartData" :options="kelasRegistrationChartOptions" />
                        </div>
                    </div>
                </div>
                
                <!-- Kelas Payment Transition -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Transisi Pembayaran Online (Per Kelas)</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">Persentase tagihan Belum Lunas, Lunas Manual, dan Lunas Xendit untuk memantau adopsi pembayaran online.</p>
                    <div v-if="!payment_transition" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div v-else class="h-64 relative">
                        <Bar :data="paymentTransitionChartData" :options="paymentTransitionChartOptions" />
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
    mrr_data: Object,
    aging_receivables: Object,
    cltv_data: Object,
    time_to_pay: Object,
    availableKelas: Array,
    revenue_per_kelas: Object,
    registration_per_kelas: Object,
    payment_transition: Object,
});

const form = useForm({
    tahun: props.filters.tahun,
    bulan: props.filters.bulan,
    kelas: props.filters.kelas || '',
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
        only: ['revenue_mom', 'payment_rate', 'registration_trends', 'payment_methods', 'resignation_rate', 'mrr_data', 'aging_receivables', 'cltv_data', 'time_to_pay', 'revenue_per_kelas', 'registration_per_kelas', 'payment_transition', 'filters']
    });
};

onMounted(() => {
    // Request lazy loaded props immediately upon mount
    router.reload({
        only: ['revenue_mom', 'payment_rate', 'registration_trends', 'payment_methods', 'resignation_rate', 'mrr_data', 'aging_receivables', 'cltv_data', 'time_to_pay', 'revenue_per_kelas', 'registration_per_kelas', 'payment_transition']
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
        legend: { 
            position: 'bottom',
            labels: {
                generateLabels: (chart) => {
                    const datasets = chart.data.datasets;
                    if (!datasets.length || !chart.data.labels) return [];
                    return chart.data.labels.map((label, i) => {
                        const value = datasets[0].data[i];
                        // Fetch the color manually since getDatasetMeta might not be fully initialized yet on first render
                        const bgColor = Array.isArray(datasets[0].backgroundColor) 
                            ? datasets[0].backgroundColor[i] 
                            : datasets[0].backgroundColor;
                            
                        return {
                            text: `${label} (${formatCurrency(value)})`,
                            fillStyle: bgColor,
                            hidden: false,
                            index: i
                        };
                    });
                }
            }
        },
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

// 5. Aging Receivables
const agingReceivablesChartData = computed(() => {
    if (!props.aging_receivables) return { labels: [], datasets: [] };
    return {
        labels: props.aging_receivables.labels,
        datasets: [{
            label: 'Tunggakan (Rp)',
            backgroundColor: ['#ef4444', '#f97316', '#b91c1c'], // red-500, orange-500, red-700
            data: props.aging_receivables.data,
            borderRadius: 4
        }]
    };
});

const agingReceivablesChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: { 
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                    if (value >= 1000) return 'Rp ' + (value / 1000) + 'k';
                    return 'Rp ' + value;
                }
            }
        }
    }
};

// 6. Kelas Comparison (Revenue)
const kelasRevenueChartData = computed(() => {
    if (!props.revenue_per_kelas) return { labels: [], datasets: [] };
    return {
        labels: props.revenue_per_kelas.labels,
        datasets: [{
            data: props.revenue_per_kelas.data,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'],
            borderWidth: 0,
        }]
    };
});

const kelasRevenueChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed !== null) {
                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed);
                    }
                    return label;
                }
            }
        }
    }
};

// 7. Kelas Comparison (Registration)
const kelasRegistrationChartData = computed(() => {
    if (!props.registration_per_kelas) return { labels: [], datasets: [] };
    return {
        labels: props.registration_per_kelas.labels,
        datasets: [{
            label: 'Siswa Baru',
            backgroundColor: '#6366f1',
            data: props.registration_per_kelas.data,
            borderRadius: 4
        }]
    };
});

const kelasRegistrationChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } }
    }
};

// 8. Payment Transition (Stacked Bar)
const paymentTransitionChartData = computed(() => {
    if (!props.payment_transition) return { labels: [], datasets: [] };
    return {
        labels: props.payment_transition.labels,
        datasets: [
            {
                label: 'Belum Lunas (%)',
                backgroundColor: '#ef4444', // Red
                data: props.payment_transition.unpaid,
            },
            {
                label: 'Lunas Manual (%)',
                backgroundColor: '#f59e0b', // Yellow
                data: props.payment_transition.manual,
            },
            {
                label: 'Lunas Xendit (%)',
                backgroundColor: '#10b981', // Green
                data: props.payment_transition.xendit,
            }
        ]
    };
});

const paymentTransitionChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label = label.replace(' (%)', '') + ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += context.parsed.y + '%';
                        
                        // Add raw counts to tooltip
                        if (props.payment_transition?.raw_stats) {
                            const className = context.label;
                            const stats = props.payment_transition.raw_stats[className];
                            if (stats) {
                                if (context.datasetIndex === 0) label += ` (${stats.unpaid} tagihan)`;
                                if (context.datasetIndex === 1) label += ` (${stats.manual} tagihan)`;
                                if (context.datasetIndex === 2) label += ` (${stats.xendit} tagihan)`;
                            }
                        }
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        x: { stacked: true },
        y: { 
            stacked: true, 
            min: 0, 
            max: 100,
            ticks: {
                callback: function(value) {
                    return value + '%';
                }
            }
        }
    }
};

</script>
