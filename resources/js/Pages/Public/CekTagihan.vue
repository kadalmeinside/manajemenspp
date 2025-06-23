<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ArrowPathIcon, BanknotesIcon, CalendarDaysIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    pageTitle: String,
    errors: Object,
    siswa: Object,
    invoiceList: Array,
});

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo ? `/storage/${page.props.app_settings.app_logo}` : null);

const form = useForm({
    nis: '',
    tanggal_lahir: '',
});

// State untuk tab aktif
const activeTab = ref('pending');

// Filter invoices berdasarkan tab
const pendingInvoices = computed(() => props.invoiceList?.filter(invoice => invoice.status === 'PENDING') || []);
const historyInvoices = computed(() => props.invoiceList?.filter(invoice => invoice.status !== 'PENDING') || []);

const submit = () => {
    form.post(route('tagihan.check_status'), {
        preserveState: true,
        onError: () => form.reset('nis', 'tanggal_lahir'),
    });
};

const refreshData = () => {
    router.reload({ only: ['invoiceList'] });
};

const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center p-4 sm:p-6">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0 bg-cover bg-center" style="background-image: url('/images/bg_registration.jpg');"></div>
        <div class="absolute inset-0 bg-black/50 z-0"></div>

        <main class="w-full max-w-4xl mx-auto z-10 flex-grow flex flex-col">
            <!-- Header -->
            <header class="text-center mb-8">
                <Link href="/" class="inline-block">
                    <img v-if="appLogo" :src="appLogo" alt="App Logo" class="h-12 w-auto mx-auto">
                    <ApplicationLogo v-else class="h-12 w-auto mx-auto text-white" />
                </Link>
                <h1 class="mt-4 text-3xl font-bold text-white tracking-tight">Persija Development</h1>
                <h2 class="text-lg text-white/80">{{ pageTitle }}</h2>
            </header>

            <!-- Konten Utama -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg shadow-2xl rounded-xl p-6 md:p-8 flex-grow">
                <!-- Form Pencarian -->
                <div v-if="!siswa">
                    <p class="text-center text-gray-600 dark:text-gray-400">Masukkan NIS dan Tanggal Lahir untuk melihat status pembayaran.</p>
                    <form @submit.prevent="submit" class="mt-6 max-w-md mx-auto space-y-6">
                        <div v-if="errors.lookup" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md" role="alert">
                            <span>{{ errors.lookup }}</span>
                        </div>
                        <div>
                            <label for="nis" class="block text-sm font-medium text-gray-700 dark:text-gray-300">NIS (Nomor Induk Siswa)</label>
                            <input v-model="form.nis" id="nis" type="text" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                            <div v-if="form.errors.nis" class="text-red-500 text-xs mt-1">{{ form.errors.nis }}</div>
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Lahir</label>
                            <input v-model="form.tanggal_lahir" id="tanggal_lahir" type="date" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                             <div v-if="form.errors.tanggal_lahir" class="text-red-500 text-xs mt-1">{{ form.errors.tanggal_lahir }}</div>
                        </div>
                        <div>
                            <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                <span v-if="!form.processing">Cek Tagihan</span>
                                <span v-else>Mencari...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tampilan Hasil -->
                <div v-else>
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Hasil untuk: {{ siswa.nama_siswa }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">NIS: {{ siswa.nis }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                           <button @click="refreshData" class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                               <ArrowPathIcon class="h-4 w-4 mr-1"/> Refresh
                           </button>
                           <Link :href="route('tagihan.check_form')" class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                               Cari Siswa Lain
                           </Link>
                        </div>
                    </div>
                    
                    <!-- Navigasi Tab -->
                    <div class="mt-6 border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                            <button @click="activeTab = 'pending'" :class="[activeTab === 'pending' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Tagihan Tertunda ({{ pendingInvoices.length }})
                            </button>
                            <button @click="activeTab = 'history'" :class="[activeTab === 'history' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                                Riwayat Pembayaran
                            </button>
                        </nav>
                    </div>

                    <!-- Daftar Tagihan -->
                    <div class="mt-6 space-y-4">
                        <div v-show="activeTab === 'pending'">
                            <div v-if="pendingInvoices.length === 0" class="text-center text-gray-500 py-10">Tidak ada tagihan yang perlu dibayar.</div>
                            <div v-else v-for="invoice in pendingInvoices" :key="invoice.id" class="bg-white dark:bg-gray-800/50 p-4 rounded-lg shadow-sm">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-grow">
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ invoice.description }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-1">
                                            <CalendarDaysIcon class="h-4 w-4 mr-1.5"/>
                                            Jatuh Tempo: {{ invoice.due_date_formatted }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(invoice.status)">
                                            {{ invoice.status }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ invoice.total_amount_formatted }}</p>
                                    <a v-if="invoice.can_pay && invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-2">
                                        <BanknotesIcon class="h-5 w-5 mr-2" />
                                        Bayar Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div v-show="activeTab === 'history'">
                            <div v-if="historyInvoices.length === 0" class="text-center text-gray-500 py-10">Belum ada riwayat pembayaran.</div>
                             <div v-else v-for="invoice in historyInvoices" :key="invoice.id" class="bg-white dark:bg-gray-800/50 p-4 rounded-lg shadow-sm">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-grow">
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ invoice.description }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-1">
                                            <CheckCircleIcon v-if="invoice.status === 'PAID'" class="h-4 w-4 mr-1.5 text-green-500"/>
                                            <ClockIcon v-else class="h-4 w-4 mr-1.5 text-red-500"/>
                                            <span v-if="invoice.status === 'PAID'">Lunas pada: {{ invoice.paid_at_formatted }}</span>
                                            <span v-else>Status: {{ invoice.status }}</span>
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <p class="text-lg font-bold text-right" :class="invoice.status === 'PAID' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">{{ invoice.total_amount_formatted }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
