<template>
    <Head title="Laporan Analitik" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Laporan Analitik & Tren
            </h2>
        </template>

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Tabs & Filters Header -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="flex flex-col md:flex-row md:items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 gap-4">
                        
                        <!-- Tabs -->
                        <div class="flex space-x-1 bg-gray-100 dark:bg-gray-900 p-1 rounded-lg">
                            <button 
                                @click="form.tab = 'ringkasan'" 
                                :class="['px-4 py-2 text-sm font-medium rounded-md transition-all', form.tab === 'ringkasan' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                            >
                                Ringkasan Bulanan
                            </button>
                            <button 
                                @click="form.tab = 'tahunan'" 
                                :class="['px-4 py-2 text-sm font-medium rounded-md transition-all', form.tab === 'tahunan' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                            >
                                Tren Tahunan
                            </button>
                        </div>

                        <!-- Filters -->
                        <form @submit.prevent class="flex flex-wrap sm:flex-nowrap items-center gap-3">
                            <select v-if="form.tab === 'ringkasan'" v-model="form.bulan" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option v-for="(nama, index) in namaBulan" :key="index" :value="index + 1">{{ nama }}</option>
                            </select>
                            
                            <select v-model="form.tahun" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                            </select>
                            
                            <div class="w-6 h-6 flex items-center justify-center ml-2 transition-all duration-300">
                                <!-- Loading Spinner -->
                                <svg v-if="isLoading" class="animate-spin text-blue-600 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <!-- Success Checkmark -->
                                <svg v-else-if="isSuccess" class="text-green-500 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </form>
                    </div>

                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ form.tab === 'ringkasan' ? `Ringkasan: ${namaBulan[form.bulan - 1]} ${form.tahun}` : `Performa & Tren Tahun ${form.tahun}` }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            {{ form.tab === 'ringkasan' ? 'Pilih periode untuk mengubah data ringkasan.' : 'Melihat tren pendapatan dan pendaftaran masing-masing kelas.' }}
                        </p>
                        
                        <!-- Render Tab Content -->
                        <MonthlySummary v-if="form.tab === 'ringkasan'" :summary_data="summary_data" />
                        <YearlyTrends v-if="form.tab === 'tahunan'" :yearly_trends="yearly_trends" />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MonthlySummary from './Partials/MonthlySummary.vue';
import YearlyTrends from './Partials/YearlyTrends.vue';

const props = defineProps({
    filters: Object,
    availableYears: Array,
    summary_data: Object,
    yearly_trends: Object,
});

const form = useForm({
    tahun: props.filters.tahun,
    bulan: props.filters.bulan,
    tab: props.filters.tab || 'ringkasan',
});

const namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const isLoading = ref(false);
const isSuccess = ref(true);

const updateFilters = (onlyProps) => {
    isLoading.value = true;
    isSuccess.value = false;

    form.get(route('admin.analytics.index'), {
        preserveState: true,
        preserveScroll: true,
        only: onlyProps,
        onFinish: () => {
            isLoading.value = false;
            isSuccess.value = true;
        }
    });
};

// Auto reload on filter change
watch(() => form.bulan, () => {
    if (form.tab === 'ringkasan') updateFilters(['summary_data', 'filters']);
});

watch(() => form.tahun, () => {
    const propsToLoad = form.tab === 'ringkasan' ? ['summary_data', 'filters'] : ['yearly_trends', 'filters'];
    updateFilters(propsToLoad);
});

watch(() => form.tab, (newTab) => {
    // When switching tabs, check if we need to load data
    if (newTab === 'tahunan' && !props.yearly_trends) {
        updateFilters(['yearly_trends', 'filters']);
    } else if (newTab === 'ringkasan' && !props.summary_data) {
        updateFilters(['summary_data', 'filters']);
    } else {
        // Just update URL silently
        updateFilters(['filters']);
    }
});

onMounted(() => {
    // Request lazy loaded props immediately upon mount based on active tab
    const initialProps = form.tab === 'ringkasan' ? ['summary_data'] : ['yearly_trends'];
    router.reload({
        only: initialProps
    });
});
</script>
