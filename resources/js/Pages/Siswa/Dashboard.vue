<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BanknotesIcon, ClockIcon, CreditCardIcon, CheckBadgeIcon, ExclamationTriangleIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { ref } from 'vue';

// --- PERUBAHAN PROPS ---
const props = defineProps({
    pageTitle: String,
    siswaName: String,
    id_siswa: String,
    paidMonths: Array, // ['2025-1', '2025-2', ...]
    pendingLeaveMonths: Array,
    overdueInvoices: Array, // Sebelumnya: upcomingInvoice: Object
    overdueTotal: Object,   // Prop baru untuk total tertunggak
    paymentSummary: Object,
    errorMessage: String,
});

// Fungsi untuk memotong deskripsi
const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};

// --- State Cuti ---
const showLeaveModal = ref(false);
const leaveForm = useForm({
    id_siswa: props.id_siswa,
    months: [new Date().getMonth() + 1],
    year: String(new Date().getFullYear()),
    reason: '',
});

const openLeaveModal = () => {
    leaveForm.year = String(new Date().getFullYear());
    const currentMonth = new Date().getMonth() + 1;
    // reset selections when opening, check if current month is disabled
    leaveForm.months = isMonthDisabled(currentMonth) ? [] : [currentMonth];
    showLeaveModal.value = true;
};

const toggleMonth = (m) => {
    if (isMonthDisabled(m)) return;
    const index = leaveForm.months.indexOf(m);
    if (index > -1) {
        leaveForm.months.splice(index, 1);
    } else {
        leaveForm.months.push(m);
    }
};

const isMonthDisabled = (month) => {
    const year = leaveForm.year;
    if (!year) return false;
    const key = `${year}-${month}`;
    return (props.paidMonths || []).includes(key) || (props.pendingLeaveMonths || []).includes(key);
};

const submitLeave = () => {
    leaveForm.id_siswa = props.id_siswa; // Ensure ID is set
    leaveForm.post(route('student-leaves.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showLeaveModal.value = false;
            leaveForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="pageTitle" />
    <SiswaLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Selamat Datang, {{ siswaName }}!
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="errorMessage" class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md shadow-md" role="alert">
                    <p class="font-bold">Terjadi Kesalahan</p>
                    <p>{{ errorMessage }}</p>
                </div>

                <div v-else class="space-y-8">
                    <div v-if="overdueInvoices && overdueInvoices.length > 0" class="bg-red-50 dark:bg-gray-800 border border-red-200 dark:border-red-900/50 overflow-hidden shadow-lg rounded-xl p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-red-800 dark:text-red-300">Tagihan Tertunggak</h3>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                                    Anda memiliki {{ overdueTotal.count }} tagihan yang telah melewati jatuh tempo.
                                </p>
                            </div>
                            <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-red-500">
                                <ExclamationTriangleIcon class="h-6 w-6 text-white"/>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                           <div v-for="invoice in overdueInvoices" :key="invoice.id" class="flex justify-between items-center text-sm">
                               <span class="text-gray-700 dark:text-gray-300">{{ getShortDescription(invoice.description) }}</span>
                               <span class="font-medium text-gray-900 dark:text-white">{{ invoice.total_amount_formatted }}</span>
                           </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-red-200 dark:border-gray-700 flex flex-col sm:flex-row items-baseline justify-between gap-4">
                            <div>
                                <p class="text-sm text-red-700 dark:text-red-400">Total Tertunggak</p>
                                <p class="text-3xl font-bold text-red-800 dark:text-red-300">{{ overdueTotal.formatted }}</p>
                            </div>
                            <Link :href="route('siswa.tagihan.index')" class="w-full sm:w-auto inline-flex items-center justify-center text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-6 py-3 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800 transition-transform hover:scale-105">
                                <CreditCardIcon class="h-5 w-5 mr-2" />
                                Bayar Tagihan
                            </Link>
                        </div>
                    </div>
                    
                    <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl p-6 text-center">
                        <CheckBadgeIcon class="mx-auto h-12 w-12 text-green-500" />
                        <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Tidak Ada Tunggakan!</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Semua tagihan Anda hingga saat ini sudah lunas. Terima kasih!</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-md rounded-lg p-5">
                            <div class="flex items-start justify-between">
                                <div class="w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Terbayar/Tahun</p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ paymentSummary.total_paid_formatted }}</p>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ paymentSummary.total_paid_count }} tagihan lunas</p>
                                </div>
                                <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-green-500">
                                    <BanknotesIcon class="h-6 w-6 text-white"/>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-md rounded-lg p-5">
                            <div class="flex items-start justify-between">
                                <div class="w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Kewajiban</p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ paymentSummary.total_unpaid_formatted }}</p>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ paymentSummary.total_unpaid_count }} tagihan belum lunas</p>
                                </div>
                                <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-yellow-500">
                                    <ClockIcon class="h-6 w-6 text-white"/>
                                </div>
                            </div>
                        </div> -->
                    </div>

                    <div class="text-center pt-4">
                        <Link :href="route('siswa.tagihan.index')" class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                            Lihat Semua Riwayat Tagihan &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Card Ajukan Cuti -->
                 <div v-if="false" class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium">Pengajuan Cuti</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ajukan cuti untuk bulan tertentu agar tagihan SPP disesuaikan.</p>
                        </div>
                        <SecondaryButton @click="openLeaveModal">
                            <CalendarDaysIcon class="h-5 w-5 mr-2" />
                            Ajukan Cuti
                        </SecondaryButton>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Ajukan Cuti -->
        <Modal :show="showLeaveModal" @close="showLeaveModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ajukan Cuti</h2>
                <form @submit.prevent="submitLeave" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6">
                        <div class="sm:col-span-3">
                            <InputLabel value="Bulan Cuti" />
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mt-2">
                                <button v-for="m in 12" :key="m" type="button"
                                    @click="toggleMonth(m)"
                                    :disabled="isMonthDisabled(m)"
                                    :class="[
                                        'px-2 py-2 text-sm font-semibold rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500',
                                        leaveForm.months.includes(m) 
                                            ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white border-transparent shadow-md transform scale-105' 
                                            : (isMonthDisabled(m) 
                                                ? 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700/50 dark:text-gray-600' 
                                                : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 dark:bg-gray-800/80 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
                                    ]">
                                    <span class="block">{{ new Date(0, m-1).toLocaleString('id-ID', { month: 'short' }) }}</span>
                                    <span v-if="(props.pendingLeaveMonths || []).includes(leaveForm.year+'-'+m)" class="block text-[10px] opacity-75 font-normal -mt-1">(Proses)</span>
                                    <span v-else-if="(props.paidMonths || []).includes(leaveForm.year+'-'+m)" class="block text-[10px] opacity-75 font-normal -mt-1">(Lunas)</span>
                                </button>
                            </div>
                            <InputError :message="leaveForm.errors.months" class="mt-1" />
                        </div>
                        <div class="sm:col-span-1">
                            <InputLabel value="Tahun" />
                            <TextInput type="number" 
                                v-model="leaveForm.year" 
                                :max="new Date().getFullYear()" 
                                :min="new Date().getFullYear() - 1" 
                                class="w-full mt-2 rounded-xl border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 focus:bg-white focus:ring-indigo-500 transition-colors" />
                            <InputError :message="leaveForm.errors.year" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Alasan Cuti" />
                        <textarea v-model="leaveForm.reason" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" placeholder="Contoh: Sakit, Liburan Keluarga, dll."></textarea>
                         <InputError :message="leaveForm.errors.reason" />
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                         <SecondaryButton @click="showLeaveModal = false">Batal</SecondaryButton>
                         <PrimaryButton :disabled="leaveForm.processing">Ajukan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </SiswaLayout>
</template>