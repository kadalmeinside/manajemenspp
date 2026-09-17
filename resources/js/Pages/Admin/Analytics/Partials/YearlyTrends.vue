<template>
    <div class="space-y-6">
        <div v-if="!yearly_trends" class="animate-pulse space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-96 flex items-center justify-center">
                <div class="text-gray-400">Memuat Grafik Pendapatan...</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-96 flex items-center justify-center">
                <div class="text-gray-400">Memuat Grafik Pendaftar...</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-96 flex items-center justify-center">
                <div class="text-gray-400">Memuat Grafik Siswa Keluar...</div>
            </div>
        </div>
        
        <div v-else class="space-y-6">
            <!-- Revenue Line Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tren Pendapatan Tahunan</h3>
                <div class="h-80">
                    <Line :data="revenueChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Pendaftar Bar Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tren Pendaftar Baru</h3>
                <div class="h-80">
                    <Bar :data="pendaftarChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Keluar Bar Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tren Siswa Keluar</h3>
                <div class="h-80">
                    <Bar :data="keluarChartData" :options="chartOptions" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const props = defineProps({
    yearly_trends: {
        type: Object,
        default: null
    }
});

// Color palette for classes
const colors = [
    '#3b82f6', // blue-500
    '#ef4444', // red-500
    '#10b981', // emerald-500
    '#f59e0b', // amber-500
    '#8b5cf6', // violet-500
    '#ec4899', // pink-500
    '#06b6d4', // cyan-500
    '#6366f1', // indigo-500
    '#14b8a6', // teal-500
    '#f97316', // orange-500
];

const getDatasetColor = (index) => colors[index % colors.length];

const revenueChartData = computed(() => {
    if (!props.yearly_trends) return { labels: [], datasets: [] };
    
    const datasets = [];
    let colorIndex = 0;
    
    for (const [kelas, data] of Object.entries(props.yearly_trends.revenue)) {
        datasets.push({
            label: kelas,
            data: data,
            borderColor: getDatasetColor(colorIndex),
            backgroundColor: getDatasetColor(colorIndex) + '20', // Add transparency
            tension: 0.3,
            fill: true,
            borderWidth: 2,
        });
        colorIndex++;
    }
    
    return {
        labels: props.yearly_trends.labels,
        datasets: datasets
    };
});

const pendaftarChartData = computed(() => {
    if (!props.yearly_trends) return { labels: [], datasets: [] };
    
    const datasets = [];
    let colorIndex = 0;
    
    for (const [kelas, data] of Object.entries(props.yearly_trends.pendaftar)) {
        datasets.push({
            label: kelas,
            data: data,
            backgroundColor: getDatasetColor(colorIndex),
            borderWidth: 0,
        });
        colorIndex++;
    }
    
    return {
        labels: props.yearly_trends.labels,
        datasets: datasets
    };
});

const keluarChartData = computed(() => {
    if (!props.yearly_trends || !props.yearly_trends.keluar) return { labels: [], datasets: [] };
    
    const datasets = [];
    let colorIndex = 0;
    
    for (const [kelas, data] of Object.entries(props.yearly_trends.keluar)) {
        datasets.push({
            label: kelas,
            data: data,
            backgroundColor: getDatasetColor(colorIndex),
            borderWidth: 0,
        });
        colorIndex++;
    }
    
    return {
        labels: props.yearly_trends.labels,
        datasets: datasets
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)'
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    }
};
</script>
