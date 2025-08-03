<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BanknotesIcon, ClockIcon, CreditCardIcon, CheckBadgeIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

// --- PERUBAHAN PROPS ---
const props = defineProps({
    pageTitle: String,
    siswaName: String,
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
            </div>
        </div>
    </SiswaLayout>
</template>