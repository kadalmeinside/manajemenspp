<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { EyeIcon, BanknotesIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    invoiceList: Object, // Prop dari controller, berisi objek paginasi
    pageTitle: String,
    errorMessage: String,
});

// State untuk tab aktif di tampilan mobile
const activeTab = ref('pending'); // 'pending' atau 'history'

// Computed properties untuk mengakses data dengan aman
const allInvoices = computed(() => props.invoiceList?.data || []);
const links = computed(() => props.invoiceList?.links || []);

// Computed properties baru untuk memfilter invoice berdasarkan tab
const pendingInvoices = computed(() => allInvoices.value.filter(invoice => invoice.status === 'PENDING'));
const historyInvoices = computed(() => allInvoices.value.filter(invoice => invoice.status !== 'PENDING'));


// Helper untuk styling status
const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>

<template>
    <Head :title="pageTitle" />

    <SiswaLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="errorMessage" class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md shadow-md" role="alert">
                    <p class="font-bold">Terjadi Kesalahan</p>
                    <p>{{ errorMessage }}</p>
                </div>

                <!-- ======================================== -->
                <!-- === Tampilan Mobile dengan Tab BARU === -->
                <!-- ======================================== -->
                <div class="md:hidden">
                    <!-- Navigasi Tab -->
                    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex -mb-px" aria-label="Tabs">
                            <button @click="activeTab = 'pending'" :class="[activeTab === 'pending' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300', 'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm']">
                                Tagihan Tertunda
                            </button>
                            <button @click="activeTab = 'history'" :class="[activeTab === 'history' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300', 'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm']">
                                Riwayat Pembayaran
                            </button>
                        </nav>
                    </div>

                    <!-- Konten Tab -->
                    <div class="space-y-4">
                        <!-- Konten Tab Tertunda -->
                        <div v-if="activeTab === 'pending'">
                            <div v-if="pendingInvoices.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-10">
                                Tidak ada tagihan yang tertunda.
                            </div>
                            <div v-else v-for="invoice in pendingInvoices" :key="invoice.id" class="bg-white mb-3 dark:bg-gray-800 rounded-lg shadow-sm p-4 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ invoice.description }}</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ invoice.total_amount_formatted }}</p>
                                    </div>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(invoice.status)">
                                        {{ invoice.status }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Jatuh Tempo: {{ invoice.due_date_formatted }}
                                </div>
                                <div v-if="invoice.can_pay && invoice.xendit_payment_url" class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <a :href="invoice.xendit_payment_url" target="_blank" class="w-full inline-flex items-center justify-center text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800">
                                        <BanknotesIcon class="h-5 w-5 mr-2" />
                                        Bayar Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Konten Tab Riwayat -->
                         <div v-if="activeTab === 'history'">
                            <div v-if="historyInvoices.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-10">
                                Belum ada riwayat pembayaran.
                            </div>
                            <div v-else v-for="invoice in historyInvoices" :key="invoice.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ invoice.description }}</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ invoice.total_amount_formatted }}</p>
                                    </div>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(invoice.status)">
                                        {{ invoice.status }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Jatuh Tempo: {{ invoice.due_date_formatted }}
                                </div>
                                <div v-if="invoice.xendit_payment_url" class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                    <a :href="invoice.xendit_payment_url" target="_blank" class="text-sm text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200 inline-flex items-center" title="Lihat Invoice">
                                        <EyeIcon class="h-4 w-4 mr-1"/> Lihat Invoice
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tampilan Tabel untuk Desktop (Tidak Berubah) -->
                <div class="hidden md:block bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Tagihan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="allInvoices.length === 0 && !errorMessage">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data tagihan.</td>
                                </tr>
                                <tr v-else v-for="invoice in allInvoices" :key="invoice.id">
                                    <td class="px-6 py-4 whitespace-normal text-sm font-medium text-gray-900 dark:text-white">{{ invoice.description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ invoice.due_date_formatted }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(invoice.status)">
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a v-if="invoice.can_pay && invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800">
                                            Bayar Sekarang
                                        </a>
                                        <a v-else-if="invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200" title="Lihat Invoice">
                                            Lihat Invoice
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination (Tidak Berubah) -->
                <div v-if="links && links.length > 3" class="mt-6">
                    <div class="flex flex-wrap -mb-1 justify-center">
                        <template v-for="(link, key) in links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600 select-none" v-html="link.label" />
                            <Link v-else
                                  class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300"
                                  :class="{ 'bg-red-600 text-white border-red-600': link.active }"
                                  :href="link.url"
                                  v-html="link.label"
                                  preserve-scroll />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>
