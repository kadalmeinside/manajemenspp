<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CreditCardIcon, CheckCircleIcon, CalendarDaysIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    sppInvoices: Array,
    siswa: Object,
    pageTitle: String,
    errors: Object,
});

// --- STATE ---
const selectedPeriods = ref([]);

// --- FORM ---
const paymentForm = useForm({
    periods: [],
});

// --- COMPUTED PROPERTIES ---
const displayList = computed(() => {
    if (!props.siswa) return [];
    const existingInvoices = props.sppInvoices.map(inv => ({ ...inv, is_projected: false }));
    const projectedInvoices = [];
    const lastPeriod = existingInvoices.length > 0
        ? new Date(existingInvoices[existingInvoices.length - 1].periode_tagihan)
        : new Date();
    const endOfYear = new Date(lastPeriod.getFullYear(), 11, 31);
    let currentPeriod = new Date(lastPeriod.getFullYear(), lastPeriod.getMonth() + 1, 1);

    while (currentPeriod <= endOfYear) {
        const periodString = currentPeriod.toISOString().slice(0, 10);
        const monthName = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(currentPeriod);
        projectedInvoices.push({
            id: `proj-${periodString}`,
            description: `SPP ${monthName}`,
            total_amount: props.siswa.jumlah_spp_custom,
            total_amount_formatted: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(props.siswa.jumlah_spp_custom),
            status: 'PROJECTED',
            periode_tagihan: periodString,
            is_projected: true,
        });
        currentPeriod.setMonth(currentPeriod.getMonth() + 1);
    }
    return [...existingInvoices, ...projectedInvoices];
});

// --- FUNGSI UNTUK MEMOTONG DESKRIPSI ---
const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};

const totalSelectedAmount = computed(() => {
    return displayList.value
        .filter(item => selectedPeriods.value.includes(item.periode_tagihan))
        .reduce((total, item) => total + item.total_amount, 0);
});

const totalSelectedAmountFormatted = computed(() => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalSelectedAmount.value);
});

// --- FUNCTIONS ---
const updateSelection = (item, isChecked) => {
    const clickedIndex = displayList.value.findIndex(i => i.id === item.id);
    if (isChecked) {
        if (clickedIndex === 0 || selectedPeriods.value.includes(displayList.value[clickedIndex - 1].periode_tagihan)) {
            selectedPeriods.value.push(item.periode_tagihan);
        }
    } else {
        const periodsToRemove = displayList.value.slice(clickedIndex).map(i => i.periode_tagihan);
        selectedPeriods.value = selectedPeriods.value.filter(p => !periodsToRemove.includes(p));
    }
};

const submitPayment = () => {
    paymentForm.periods = selectedPeriods.value.sort();
    paymentForm.post(route('siswa.invoices.unified_pay'), {
        preserveScroll: true,
    });
};

const getStatusClass = (status) => ({
    'PENDING': 'bg-yellow-100 text-yellow-800',
    'PROJECTED': 'bg-blue-100 text-blue-800',
}[status] || 'bg-gray-100 text-gray-800');
</script>

<template>
    <Head :title="pageTitle" />
    <SiswaLayout>
        <template #header>
             <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="errors.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    {{ errors.error }}
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <InformationCircleIcon class="h-5 w-5 text-yellow-400" aria-hidden="true" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Pilih tagihan yang ingin dibayar. Pembayaran harus dilakukan secara berurutan.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="displayList.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template v-for="item in displayList" :key="item.id">
                        <div @click="updateSelection(item, !selectedPeriods.includes(item.periode_tagihan))" 
                             class="bg-white dark:bg-gray-800 rounded-lg shadow-sm transition-all duration-200 mx-2 p-4 flex items-center space-x-4 cursor-pointer hover:shadow-md"
                             :class="{ 'bg-indigo-100 dark:bg-indigo-700/50': selectedPeriods.includes(item.periode_tagihan) }">
                            <Checkbox 
                                :checked="selectedPeriods.includes(item.periode_tagihan)"
                                @update:checked.stop="updateSelection(item, $event)"
                            />
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">{{ getShortDescription(item.description) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.total_amount_formatted }}</p>
                            </div>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="getStatusClass(item.status)">
                                {{ item.is_projected ? '' : '' }}
                            </span>
                        </div>
                    </template>
                </div>

                <div v-else class="text-center text-gray-500 dark:text-gray-400 py-10 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400" />
                    <h4 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Semua Lunas!</h4>
                    <p>Tidak ada tagihan SPP yang perlu dibayar saat ini.</p>
                </div>

                <div class="pb-24"></div> 
            </div>
        </div>

        <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 translate-y-full" enter-to-class="transform opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 translate-y-full">
            <div v-if="selectedPeriods.length > 0" class="fixed bottom-0 left-0 right-0 w-full z-40">
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-t border-gray-200 dark:border-gray-700 shadow-2xl shadow-slate-400/20">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ selectedPeriods.length }} bulan tagihan dipilih</h4>
                                <p class="text-lg font-bold text-red-600 dark:text-red-500">{{ totalSelectedAmountFormatted }}</p>
                            </div>
                            <PrimaryButton @click="submitPayment" :disabled="paymentForm.processing" class="w-full sm:w-auto">
                                <CreditCardIcon class="h-5 w-5 mr-2"/>
                                {{ paymentForm.processing ? 'Memproses...' : 'Lanjutkan ke Pembayaran' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </SiswaLayout>
</template>