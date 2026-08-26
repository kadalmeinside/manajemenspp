<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { 
    CheckCircleIcon,
    InformationCircleIcon,
    DocumentTextIcon,
    CheckIcon,
    CreditCardIcon,
    BanknotesIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { CurrencyDollarIcon } from '@heroicons/vue/24/solid';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    sppInvoices: Array, // Daftar PENDING invoices
    lastPaidPeriod: String, // Periode terakhir yang PAID
    siswa: Object,
    pageTitle: String,
    errors: Object,
});

const selectedPeriods = ref([]);

const paymentForm = useForm({
    periods: [],
});

const displayList = computed(() => {
    if (!props.siswa) return [];

    const existingInvoices = props.sppInvoices.map(inv => ({ ...inv, is_projected: false }));
    const projectedInvoices = [];
    
    // ### LOGIKA BARU & LEBIH ROBUST UNTUK PROYEKSI ###
    let startProjectionDate;
    
    // Absolute Lower Bound (Bulan Mulai SPP)
    let absoluteStart = new Date();
    const mulaiSppDateStr = props.siswa?.mulai_spp_date;
    if (mulaiSppDateStr) {
        const parts = mulaiSppDateStr.split('-').map(Number);
        absoluteStart = new Date(Date.UTC(parts[0], parts[1] - 1, 1));
    } else {
        absoluteStart = new Date(Date.UTC(absoluteStart.getFullYear(), absoluteStart.getMonth(), 1)); // Fallback: bulan ini
    }
    
    if (existingInvoices.length > 0) {
        // Jika ADA invoice PENDING: Mulai proyeksi dari bulan SETELAH invoice PENDING terakhir
        const lastPeriod = new Date(existingInvoices[existingInvoices.length - 1].periode_tagihan);
        startProjectionDate = new Date(Date.UTC(lastPeriod.getUTCFullYear(), lastPeriod.getUTCMonth() + 1, 1));
    } else if (props.lastPaidPeriod) {
        // Jika TIDAK ADA invoice PENDING, tapi ADA riwayat PAID: Mulai dari bulan SETELAH invoice PAID terakhir
        const lastPeriod = new Date(props.lastPaidPeriod);
        startProjectionDate = new Date(Date.UTC(lastPeriod.getUTCFullYear(), lastPeriod.getUTCMonth() + 1, 1));
    } else {
        // Jika TIDAK ADA PENDING dan TIDAK ADA PAID (siswa baru): Mulai dari batas bawah
        startProjectionDate = absoluteStart;
    }
    
    // Jika hasil proyeksi ternyata masih lebih kecil dari batas mulai SPP,
    // paksa mulai dari batas mulai SPP.
    if (startProjectionDate < absoluteStart) {
        startProjectionDate = absoluteStart;
    }
    
    // Sisa logika untuk membuat proyeksi tetap sama
    let currentPeriod = startProjectionDate;
    const endOfYear = new Date(Date.UTC(currentPeriod.getUTCFullYear(), 11, 31));

    while (currentPeriod <= endOfYear) {
        const year = currentPeriod.getUTCFullYear();
        const month = String(currentPeriod.getUTCMonth() + 1).padStart(2, '0');
        const day = '01';
        const periodString = `${year}-${month}-${day}`;
        const monthName = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(currentPeriod);
        
        const sppAmount = props.siswa.jumlah_spp_custom || 0;

        projectedInvoices.push({
            id: `proj-${periodString}`,
            description: `SPP Bulan ${monthName}`,
            total_amount: sppAmount,
            total_amount_formatted: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(sppAmount),
            status: 'PROJECTED',
            periode_tagihan: periodString,
            is_projected: true,
        });
        
        currentPeriod.setUTCMonth(currentPeriod.getUTCMonth() + 1);
    }

    return [...existingInvoices, ...projectedInvoices];
});


const totalSelectedAmount = computed(() => {
    if (selectedPeriods.value.length === 0) return 0;
    
    const totalSpp = displayList.value
        .filter(item => selectedPeriods.value.includes(item.periode_tagihan))
        .reduce((total, item) => total + item.total_amount, 0);
        
    const adminFee = props.siswa.admin_fee_custom || 0;
    return totalSpp + adminFee;
});


const totalSelectedAmountFormatted = computed(() => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalSelectedAmount.value);
});

const errorMessage = ref('');

const updateSelection = (item, isChecked) => {
    const clickedIndex = displayList.value.findIndex(i => i.id === item.id);
    if (isChecked) {
        if (clickedIndex === 0 || selectedPeriods.value.includes(displayList.value[clickedIndex - 1].periode_tagihan)) {
            selectedPeriods.value.push(item.periode_tagihan);
            errorMessage.value = ''; // clear error
        } else {
            errorMessage.value = 'Harap lunasi tagihan bulan sebelumnya terlebih dahulu.';
            setTimeout(() => errorMessage.value = '', 4000);
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
    'PENDING': 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800',
    'PROJECTED': 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
}[status] || 'bg-gray-100 text-gray-800 border-gray-200');

const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};

const summarySppPendingCount = computed(() => props.sppInvoices.length);
const summarySppPendingAmount = computed(() => props.sppInvoices.reduce((sum, inv) => sum + inv.total_amount, 0));

const grandTotalPendingAmount = computed(() => summarySppPendingAmount.value);

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
</script>

<template>
    <Head :title="pageTitle" />
    <SiswaLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-extrabold text-xl md:text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center">
                    <DocumentTextIcon class="h-6 w-6 md:h-8 md:w-8 mr-2 md:mr-3 text-red-600 dark:text-red-400" />
                    {{ pageTitle }}
                </h2>
            </div>
        </template>

        <div class="py-4 md:py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="errors.error" class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 dark:text-red-300 dark:bg-red-900/30 rounded-lg shadow-sm">
                    {{ errors.error }}
                </div>
                
                <!-- Ringkasan Tagihan Card -->
                <div v-if="summarySppPendingCount > 0" class="mb-10 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-900 to-red-800 p-6 sm:px-10 sm:py-8 text-white relative overflow-hidden">
                        <!-- Decorative circles -->
                        <div class="absolute -top-16 -right-16 w-64 h-64 bg-white opacity-5 rounded-full blur-2xl"></div>
                        <div class="absolute -bottom-20 -left-10 w-48 h-48 bg-rose-500 opacity-20 rounded-full blur-2xl"></div>
                        
                        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div>
                                <h3 class="text-red-200 font-bold tracking-wider uppercase text-sm mb-1">Total Kewajiban Pembayaran</h3>
                                <p class="text-4xl sm:text-5xl font-black">{{ formatCurrency(grandTotalPendingAmount) }}</p>
                            </div>
                            
                            <div class="flex gap-4 sm:gap-6 bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/10">
                                <div>
                                    <p class="text-red-200 text-xs font-bold uppercase tracking-wider mb-1 flex items-center"><DocumentTextIcon class="w-3 h-3 mr-1"/> Belum Dibayar ({{ summarySppPendingCount }} Bulan)</p>
                                    <p class="text-xl font-bold">{{ formatCurrency(summarySppPendingAmount) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-8 rounded-r-xl shadow-sm flex items-start">
                    <InformationCircleIcon class="h-5 w-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" />
                    <div>
                        <h4 class="font-bold text-red-900 dark:text-red-300 text-sm">Informasi Pembayaran</h4>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-0.5">
                            Pilih tagihan SPP yang ingin dibayar. Pembayaran bulan berikutnya hanya bisa dipilih jika bulan sebelumnya sudah dipilih.
                        </p>
                    </div>
                </div>

                <!-- Bagian Header SPP -->
                <div class="flex items-center mb-6">
                    <div class="bg-gradient-to-br from-red-500 to-rose-500 p-2 rounded-xl shadow-lg shadow-red-500/30 mr-4">
                        <DocumentTextIcon class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="font-extrabold text-xl md:text-2xl text-gray-900 dark:text-white">
                        Tagihan Bulanan (SPP)
                    </h3>
                </div>

                <!-- Inline Error Alert -->
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 -translate-y-2" enter-to-class="transform opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 -translate-y-2">
                    <div v-if="errorMessage" class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/50 flex items-start text-rose-600 dark:text-rose-400 shadow-sm">
                        <InformationCircleIcon class="h-5 w-5 mr-3 mt-0.5 flex-shrink-0" />
                        <div>
                            <h4 class="font-bold text-sm">Pilih Secara Berurutan</h4>
                            <p class="text-sm mt-0.5">{{ errorMessage }}</p>
                        </div>
                    </div>
                </transition>

                <div v-if="displayList.length > 0" class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden divide-y divide-gray-100 dark:divide-gray-700/60">
                    <template v-for="item in displayList" :key="item.id">
                        <!-- Invoice List Item -->
                        <div @click="updateSelection(item, !selectedPeriods.includes(item.periode_tagihan))" 
                             class="group relative transition-all duration-300 cursor-pointer flex items-center p-4 sm:p-6"
                             :class="[
                                 selectedPeriods.includes(item.periode_tagihan) ? 'bg-gradient-to-r from-white from-50% via-emerald-50 via-75% to-emerald-500 dark:from-gray-800 dark:via-emerald-900/30 dark:to-emerald-600' : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/20',
                                 (!selectedPeriods.includes(item.periode_tagihan) && (displayList.findIndex(i => i.id === item.id) > 0 && !selectedPeriods.includes(displayList[displayList.findIndex(i => i.id === item.id) - 1].periode_tagihan))) ? 'opacity-60 grayscale-[30%]' : ''
                             ]">
                             
                             <!-- Aksen Background Icon Uang -->
                             <div v-if="selectedPeriods.includes(item.periode_tagihan)" class="absolute -bottom-8 -right-4 transform -rotate-12 pointer-events-none z-0 overflow-hidden">
                                 <BanknotesIcon class="w-36 h-36 text-white/20" />
                             </div>

                             <div class="absolute left-0 top-0 bottom-0 w-1 transition-colors duration-200 z-10" :class="selectedPeriods.includes(item.periode_tagihan) ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-transparent'"></div>
                            
                            <div class="flex-shrink-0 mr-4 sm:mr-6 flex items-center justify-center w-6 h-6 rounded border-2 transition-colors duration-200 z-10"
                                 :class="selectedPeriods.includes(item.periode_tagihan) ? 'bg-emerald-500 border-emerald-500 shadow-sm shadow-emerald-500/50' : 'border-gray-300 dark:border-gray-600 group-hover:border-emerald-400'">
                                <CheckIcon v-if="selectedPeriods.includes(item.periode_tagihan)" class="h-4 w-4 text-white font-bold" />
                            </div>
                            
                            <div class="flex-grow min-w-0 pr-4 z-10">
                                <h3 class="text-base sm:text-lg font-bold leading-tight truncate transition-colors duration-300" :class="selectedPeriods.includes(item.periode_tagihan) ? 'text-gray-900 dark:text-white' : 'text-gray-900 dark:text-white'">
                                    {{ getShortDescription(item.description) }}
                                </h3>
                                <div class="flex items-center gap-2 mt-1 sm:mt-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border transition-colors duration-300" 
                                          :class="selectedPeriods.includes(item.periode_tagihan) ? 'bg-white/50 border-emerald-200 text-emerald-800 dark:bg-gray-800/50 dark:border-emerald-700 dark:text-emerald-200' : getStatusClass(item.status)">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="item.is_projected ? 'bg-gray-400' : 'bg-emerald-500'"></span>
                                        {{ item.is_projected ? 'Proyeksi' : 'Tersedia' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex-shrink-0 text-right z-10 relative">
                                <span class="text-base sm:text-lg font-black transition-colors duration-300 drop-shadow-sm" 
                                      :class="selectedPeriods.includes(item.periode_tagihan) ? 'text-white dark:text-white' : 'text-gray-900 dark:text-white'">
                                    {{ item.total_amount_formatted }}
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <CheckCircleIcon class="h-10 w-10 text-green-500" />
                    </div>
                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">SPP Lunas!</h4>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 text-lg">Tidak ada tagihan SPP yang perlu dibayar hingga akhir tahun.</p>
                </div>

                <!-- End of SPP Section -->

                <div class="pb-32"></div> 
            </div>
        </div>

        <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 translate-y-10" enter-to-class="transform opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 translate-y-10">
            <div v-if="selectedPeriods.length > 0" class="fixed bottom-0 left-0 right-0 w-full z-[60] md:pl-72 transition-all duration-300">
                <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-t border-gray-200 dark:border-gray-700 shadow-[0_-10px_40px_-10px_rgba(0,0,0,0.1)] dark:shadow-none p-4 sm:p-6 pb-6 md:pb-6">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center space-x-3 w-full sm:w-auto">
                                <button @click="selectedPeriods = []" class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 rounded-full transition-colors flex-shrink-0" title="Batal Pilih">
                                    <XMarkIcon class="h-6 w-6" />
                                </button>
                                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                    <span class="text-red-600 dark:text-red-400 font-black text-lg tracking-tighter">Rp</span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ selectedPeriods.length }} bulan terpilih</h4>
                                    <p class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">{{ totalSelectedAmountFormatted }}</p>
                                </div>
                            </div>
                            <button @click="submitPayment" :disabled="paymentForm.processing" class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-4 bg-red-600 text-white rounded-xl font-extrabold text-sm shadow-xl shadow-red-600/30 hover:bg-red-500 focus:outline-none transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                                <CreditCardIcon class="h-5 w-5 mr-2"/>
                                {{ paymentForm.processing ? 'Memproses...' : 'Lanjut Pembayaran' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </SiswaLayout>
</template>
