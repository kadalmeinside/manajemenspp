<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Toast from '@/Components/Toast.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, EyeIcon, ChevronDownIcon, PencilIcon, TrashIcon, XCircleIcon, ArrowPathIcon, ArrowDownTrayIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

// Computed props
const invoiceList = computed(() => page.props.invoiceList || { data: [], links: [], current_page: 1, total: 0, per_page: 10, from: 0, to: 0 });
const filters = computed(() => page.props.filters || { search: '', kelas_id: '', status: '', periode_bulan: '', periode_tahun: '' });
const allSiswa = computed(() => page.props.allSiswa || []);
const allKelas = computed(() => page.props.allKelas || []);
const allStatus = computed(() => page.props.allStatus || []);
const availableYears = computed(() => page.props.availableYears || [new Date().getFullYear()]);
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// Modal States
const showIndividualModal = ref(false);
const showBulkByClassModal = ref(false);
const showBulkAllModal = ref(false);
const showCancelConfirmModal = ref(false);
const invoiceToCancel = ref(null);
const showRecreateConfirmModal = ref(false);
const invoiceToRecreate = ref(null); 
const showExportModal = ref(false);
const showMobileFilters = ref(false);

// Data untuk form periode
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 10 }, (_, i) => currentYear - 5 + i);
const months = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, name: new Date(0, i).toLocaleString('id-ID', { month: 'long' }) }));
const defaultJatuhTempo = new Date(currentYear, new Date().getMonth() + 1, 10).toISOString().slice(0,10);

// Forms
const formIndividual = useForm({
    id_siswa: '',
    periode_tagihan_bulan: new Date().getMonth() + 1,
    periode_tagihan_tahun: new Date().getFullYear(),
    jumlah_spp_ditagih: '',
    admin_fee_ditagih: 0,
    tanggal_jatuh_tempo: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10).toISOString().slice(0,10),
    send_whatsapp_notif: true,
});

const formBulkByClass = useForm({
    id_kelas: '',
    periode_tagihan_bulan: new Date().getMonth() + 1,
    periode_tagihan_tahun: new Date().getFullYear(),
    tanggal_jatuh_tempo: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10).toISOString().slice(0,10),
    jenis_jumlah_spp: 'default',
    jumlah_spp_manual: null,
    jenis_admin_fee: 'default',
    admin_fee_manual: null,
    send_whatsapp_notif: true,
});

const formBulkAll = useForm({
    periode_tagihan_bulan: new Date().getMonth() + 1,
    periode_tagihan_tahun: new Date().getFullYear(),
    tanggal_jatuh_tempo: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10).toISOString().slice(0,10),
    send_whatsapp_notif: true,
});

const cancelActionForm = useForm({});
const recreateActionForm = useForm({});

const todayString = new Date().toISOString().slice(0,10);

const formExport = useForm({
    range_type: 'today',
    start_date: todayString,
    end_date: todayString,
});

watch(() => formExport.range_type, (newType) => {
    const end = new Date();
    let start = new Date();
    
    if (newType === 'today') {
        // start is already today
    } else if (newType === '7_days') {
        start.setDate(end.getDate() - 6);
    } else if (newType === '14_days') {
        start.setDate(end.getDate() - 13);
    } else if (newType === '30_days') {
        start.setDate(end.getDate() - 29);
    }
    
    if (newType !== 'custom') {
        formExport.start_date = start.toISOString().slice(0,10);
        formExport.end_date = end.toISOString().slice(0,10);
    }
});

const submitExportForm = () => {
    window.location.href = route('admin.invoices.export_paid') + '?start_date=' + formExport.start_date + '&end_date=' + formExport.end_date;
    showExportModal.value = false;
};

const searchQuery = ref(filters.value.search || '');
const selectedKelasId = ref(filters.value.kelas_id || '');
const selectedStatus = ref(filters.value.status || '');
const selectedPeriodeBulan = ref(filters.value.periode_bulan || '');
const selectedPeriodeTahun = ref(filters.value.periode_tahun || '');

const submitFilters = () => {
    router.get(route('admin.invoices.index'), {
        search: searchQuery.value,
        kelas_id: selectedKelasId.value,
        status: selectedStatus.value,
        periode_bulan: selectedPeriodeBulan.value,
        periode_tahun: selectedPeriodeTahun.value,
        page: 1,
    }, {
        preserveState: true, preserveScroll: true, replace: true,
        only: ['invoiceList', 'filters'],
    });
};
watch([searchQuery, selectedKelasId, selectedStatus, selectedPeriodeBulan, selectedPeriodeTahun], debounce(submitFilters, 300));


const openCreateIndividualModal = () => { formIndividual.reset(); showIndividualModal.value = true; };
const closeIndividualModal = () => { showIndividualModal.value = false; };

const openBulkByClassModal = () => { formBulkByClass.reset(); showBulkByClassModal.value = true; };
const closeBulkByClassModal = () => { showBulkByClassModal.value = false; };

const openBulkAllModal = () => { formBulkAll.reset(); showBulkAllModal.value = true; };
const closeBulkAllModal = () => { showBulkAllModal.value = false; };

watch(() => formIndividual.id_siswa, (newSiswaId) => {
    if (newSiswaId) {
        const siswa = allSiswa.value.find(s => s.id_siswa === newSiswaId);
        if (siswa) {
            if (siswa.jumlah_spp_custom > 0) {
                formIndividual.jumlah_spp_ditagih = parseFloat(siswa.jumlah_spp_custom);
            } else if (siswa.id_kelas) {
                const kelas = allKelas.value.find(k => k.id_kelas === siswa.id_kelas);
                formIndividual.jumlah_spp_ditagih = (kelas && kelas.biaya_spp_default > 0) ? parseFloat(kelas.biaya_spp_default) : '';
            } else {
                formIndividual.jumlah_spp_ditagih = '';
            }
            formIndividual.admin_fee_ditagih = parseFloat(siswa.admin_fee_custom) || 0;
        }
    }
});

const submitIndividualForm = () => {
    formIndividual.post(route('admin.invoices.store'), {
        preserveScroll: true,
        onFinish: () => {
            closeIndividualModal();
        },
    });
};

const submitBulkByClassForm = () => {
    formBulkByClass.post(route('admin.invoices.bulk_store'), {
        preserveScroll: true,
        onFinish: () => { 
            closeBulkByClassModal(); 
        },
    });
};

const submitBulkAllForm = () => {
    formBulkAll.post(route('admin.invoices.bulk_store_all'), {
        preserveScroll: true,
        onFinish: () => { 
            closeBulkAllModal(); 
        },
    });
};

const confirmCancelInvoice = (invoice) => {
    invoiceToCancel.value = invoice;
    showCancelConfirmModal.value = true;
};

const cancelInvoice = () => {
    if (!invoiceToCancel.value) return;
    cancelActionForm.delete(route('admin.invoices.destroy', invoiceToCancel.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showCancelConfirmModal.value = false;
            invoiceToCancel.value = null;
        },
    });
};

const confirmRecreateInvoice = (invoice) => {
    invoiceToRecreate.value = invoice;
    showRecreateConfirmModal.value = true;
};

const recreateInvoice = () => {
    if (!invoiceToRecreate.value) return;
    recreateActionForm.post(route('admin.invoices.recreate', invoiceToRecreate.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showRecreateConfirmModal.value = false;
            invoiceToRecreate.value = null;
        }
    });
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || filters.value.search || '';
    selectedKelasId.value = urlParams.get('kelas_id') || filters.value.kelas_id || '';
    selectedStatus.value = urlParams.get('status') || filters.value.status || '';
    selectedPeriodeBulan.value = urlParams.get('periode_bulan') || filters.value.periode_bulan || '';
    selectedPeriodeTahun.value = urlParams.get('periode_tahun') || filters.value.periode_tahun || '';
});

// Helper Function
const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const formatDescription = (desc) => {
    if (!desc) return { main: '', detail: '' };
    const parts = desc.split(' - ');
    if (parts.length > 1) {
        return {
            main: parts[0],
            detail: parts.slice(1).join(' - ')
        };
    }
    return { main: desc, detail: '' };
};
</script>

<template>
    <Head title="Manajemen Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="hidden md:block font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Invoice & Tagihan</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-full mx-auto px-1 sm:px-0">
                <!-- MOBILE: Search & Info Card (Sticky) -->
                <div class="sticky -top-4 z-10 bg-white dark:bg-gray-800 -mx-4 -mt-8 px-4 pt-4 pb-4 mb-4 border-b border-t-0 border-gray-200 dark:border-gray-700 shadow-sm lg:hidden rounded-b-2xl">
                    <div class="flex gap-2">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari deskripsi, siswa..." class="w-full bg-gray-50 border-gray-200 dark:bg-gray-900/50 dark:border-gray-700" aria-label="Cari invoice"/>
                        <SecondaryButton @click="showMobileFilters = true" class="px-3 shrink-0 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" /></svg>
                        </SecondaryButton>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            <span v-if="invoiceList.total > 0">
                                Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-300">{{ invoiceList.from }}–{{ invoiceList.to }}</span>
                                dari <span class="font-semibold text-gray-700 dark:text-gray-300">{{ invoiceList.total }}</span> invoice
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                    </div>
                </div>

                <!-- MOBILE: Action Button Card -->
                <div class="lg:hidden mb-4 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg p-4" v-if="can?.create_invoice">
                    <Dropdown align="left" width="48" class="w-full">
                        <template #trigger>
                            <PrimaryButton class="w-full flex justify-center py-2.5">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" /> Buat Tagihan
                                <ChevronDownIcon class="ml-2 -mr-0.5 h-4 w-4" />
                            </PrimaryButton>
                        </template>
                        <template #content>
                            <button @click="openCreateIndividualModal" class="block w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Tagihan Individual (SPP)</button>
                            <button @click="openBulkByClassModal" class="block w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Tagihan Massal (Per Kelas)</button>
                            <button @click="openBulkAllModal" class="block w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Tagihan Massal (Semua Siswa)</button>
                        </template>
                    </Dropdown>
                </div>

                <!-- DESKTOP: Filter & Search Card -->
                <div class="hidden lg:block mb-6 p-4 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari deskripsi, siswa..." class="w-full lg:col-span-2" aria-label="Cari invoice"/>
                        <select v-model="selectedKelasId" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" aria-label="Filter kelas">
                            <option value="">Semua Kelas</option>
                            <option v-for="k in allKelas" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                        </select>
                        <select v-model="selectedStatus" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" aria-label="Filter status">
                            <option value="">Semua Status</option>
                            <option v-for="status in allStatus" :key="status" :value="status">{{ status }}</option>
                        </select>
                        <!-- Filter Periode -->
                        <select v-model="selectedPeriodeBulan" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" aria-label="Filter bulan periode">
                            <option value="">Semua Bulan</option>
                            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                        </select>
                        <select v-model="selectedPeriodeTahun" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" aria-label="Filter tahun periode">
                            <option value="">Semua Tahun</option>
                            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="invoiceList.total > 0">
                                Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-300">{{ invoiceList.from }}–{{ invoiceList.to }}</span>
                                dari <span class="font-semibold text-gray-700 dark:text-gray-300">{{ invoiceList.total }}</span> invoice
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <SecondaryButton @click="showExportModal = true" class="inline-flex items-center justify-center">
                                <ArrowDownTrayIcon class="-ml-0.5 mr-1.5 h-5 w-5" /> Export Lunas
                            </SecondaryButton>
                            <Dropdown align="right" width="56" v-if="can?.create_invoice">
                                <template #trigger>
                                    <PrimaryButton class="inline-flex items-center justify-center">
                                        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" /> Buat Tagihan
                                        <ChevronDownIcon class="ml-2 -mr-0.5 h-4 w-4" />
                                    </PrimaryButton>
                                </template>
                                <template #content>
                                    <button @click="openCreateIndividualModal" class="block w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Tagihan Individual (SPP)</button>
                                    <button @click="openBulkByClassModal" class="block w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Tagihan Massal (Per Kelas)</button>
                                    <button @click="openBulkAllModal" class="block w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Tagihan Massal (Semua Siswa)</button>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>

                <div class="bg-transparent sm:bg-white/80 sm:dark:bg-gray-800/80 sm:shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto pb-4">
                        <!-- Tampilan Desktop (Table) -->
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!invoiceList.data || invoiceList.data.length === 0">
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data invoice.</td>
                                </tr>
                                <tr v-else v-for="invoice in invoiceList.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ invoice.siswa_nama }} <br>
                                        <span class="text-xs text-gray-500">{{ invoice.kelas_nama }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ formatDescription(invoice.description).main }}</div>
                                        <div class="text-gray-500">{{ formatDescription(invoice.description).detail }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 ">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <span v-if="invoice.paid_at_formatted" class="text-green-600 dark:text-green-400 font-medium">{{ invoice.paid_at_formatted }}</span>
                                        <span v-else>{{ invoice.due_date_formatted }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(invoice.status)">{{ invoice.status }}</span>
                                    </td>
                                    <!-- Kolom Metode Bayar -->
                                    <td class="px-6 py-4 text-sm">
                                        <span v-if="invoice.payment_method === 'manual'" class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200">Manual</span>
                                        <span v-else-if="invoice.status === 'PAID'" class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200">Xendit</span>
                                        <span v-else class="text-gray-400 text-xs">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ invoice.created_at_formatted }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a v-if="invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-blue-600 hover:text-blue-900 p-1" title="Lihat Invoice Xendit"><EyeIcon class="h-5 w-5" /></a>
                                            
                                            <button v-if="invoice.status === 'PENDING' && can?.create_invoice" @click="confirmCancelInvoice(invoice)" class="text-gray-400 hover:text-red-600 p-1" title="Batalkan Invoice"><XCircleIcon class="h-5 w-5" /></button>
                                            
                                            <button v-if="invoice.status === 'EXPIRED' && can?.create_invoice" 
                                                    @click="confirmRecreateInvoice(invoice)" 
                                                    class="text-gray-400 hover:text-green-600 p-1" 
                                                    title="Buat Ulang Invoice">
                                                <ArrowPathIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Mobile (Card) -->
                        <div class="block md:hidden space-y-4 mt-2">
                            <div v-if="!invoiceList.data || invoiceList.data.length === 0" class="text-center text-sm text-gray-500 py-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                Tidak ada data invoice.
                            </div>
                            <div v-else v-for="invoice in invoiceList.data" :key="'mob-'+invoice.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm flex flex-col gap-3">
                                <div class="flex justify-between items-start border-b border-gray-100 dark:border-gray-700 pb-2">
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white leading-tight">{{ invoice.siswa_nama }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ invoice.kelas_nama }}</p>
                                    </div>
                                    <span :class="['px-2 py-1 inline-flex text-[10px] leading-tight font-semibold rounded-full', getStatusClass(invoice.status)]">
                                        {{ invoice.status }}
                                    </span>
                                </div>
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDescription(invoice.description).main }}</p>
                                    <p class="text-xs text-gray-500">{{ formatDescription(invoice.description).detail }}</p>
                                </div>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <p class="text-xs text-gray-500">Jatuh Tempo: {{ invoice.due_date_formatted }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Metode: <span class="font-medium text-gray-700 dark:text-gray-300">{{ invoice.payment_method || '-' }}</span></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-base font-bold text-green-600 dark:text-green-400">{{ invoice.amount_formatted }}</p>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2">
                                    <a v-if="invoice.invoice_url && invoice.status !== 'PAID' && invoice.status !== 'EXPIRED'" :href="invoice.invoice_url" target="_blank" class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-md hover:bg-indigo-100" title="Buka Link Xendit">
                                        <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                                    </a>
                                    <button v-if="invoice.invoice_url && invoice.status !== 'PAID' && invoice.status !== 'EXPIRED'" @click="copyToClipboard(invoice.invoice_url)" class="p-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-md hover:bg-gray-200" title="Copy Link Pembayaran">
                                        <DocumentDuplicateIcon class="h-4 w-4" />
                                    </button>
                                    <button v-if="can?.pay_manual_invoice && invoice.status !== 'PAID' && invoice.status !== 'EXPIRED'" @click="payManual(invoice.id)" class="p-2 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-md hover:bg-green-100" title="Tandai Lunas Manual">
                                        <CheckCircleIcon class="h-4 w-4" />
                                    </button>
                                    <button v-if="can?.void_invoice && (invoice.status === 'PENDING' || invoice.status === 'UNPAID')" @click="confirmCancelInvoice(invoice.id)" class="p-2 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-md hover:bg-red-100" title="Batalkan Invoice">
                                        <XCircleIcon class="h-4 w-4" />
                                    </button>
                                    <button v-if="can?.create_invoice && invoice.status === 'EXPIRED'" @click="confirmRecreateInvoice(invoice.id)" class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-md hover:bg-blue-100" title="Buat Ulang Invoice">
                                        <ArrowPathIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paginasi -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center sm:rounded-b-lg rounded-lg sm:mt-0 mt-2 shadow-sm sm:shadow-none gap-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                            <span v-if="invoiceList.total > 0">
                                Menampilkan <span class="font-medium">{{ invoiceList.from }}</span>–<span class="font-medium">{{ invoiceList.to }}</span>
                                dari <span class="font-medium">{{ invoiceList.total }}</span> invoice
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                        <Pagination :links="invoiceList.links" />
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showCancelConfirmModal" :closeable="!cancelActionForm.processing" @close="showCancelConfirmModal = false" maxWidth="md">
            <div class="p-6 relative">
                 <div v-if="cancelActionForm.processing" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center z-20 rounded-lg">
                    <svg class="animate-spin h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span class="ml-3 text-gray-700 dark:text-gray-300">Memproses...</span>
                </div>
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Konfirmasi Pembatalan Invoice</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin membatalkan invoice ini? Status akan berubah menjadi EXPIRED dan link pembayaran tidak akan berlaku lagi.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showCancelConfirmModal = false" :disabled="cancelActionForm.processing">Tidak</SecondaryButton>
                    <DangerButton @click="cancelInvoice" :class="{ 'opacity-25': cancelActionForm.processing }" :disabled="cancelActionForm.processing">
                        {{ cancelActionForm.processing ? 'Memproses...' : 'Ya, Batalkan Invoice' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRecreateConfirmModal" :closeable="!recreateActionForm.processing" @close="showRecreateConfirmModal = false" maxWidth="md">
            <div class="p-6 relative">
                 <div v-if="recreateActionForm.processing" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center z-20 rounded-lg">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span class="ml-3 text-gray-700 dark:text-gray-300">Memproses...</span>
                </div>
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Konfirmasi Buat Ulang Invoice</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Anda akan membuat invoice baru berdasarkan tagihan yang sudah kedaluwarsa ini. Invoice lama akan tetap ada sebagai riwayat. Lanjutkan?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showRecreateConfirmModal = false" :disabled="recreateActionForm.processing">Tidak</SecondaryButton>
                    <PrimaryButton @click="recreateInvoice" :class="{ 'opacity-25': recreateActionForm.processing }" :disabled="recreateActionForm.processing">
                        {{ recreateActionForm.processing ? 'Memproses...' : 'Ya, Buat Ulang' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showExportModal" @close="showExportModal = false" :maxWidth="'md'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">
                    Export Invoice Lunas (Excel)
                </h2>
                <form @submit.prevent="submitExportForm" class="space-y-4">
                    <div>
                        <InputLabel for="export_range_type" value="Pilih Rentang Waktu" />
                        <select id="export_range_type" v-model="formExport.range_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="today">Hari Ini</option>
                            <option value="7_days">7 Hari Terakhir</option>
                            <option value="14_days">14 Hari Terakhir</option>
                            <option value="30_days">30 Hari Terakhir</option>
                            <option value="custom">Kustom Range</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="export_start_date" value="Tanggal Awal Lunas" :required="true"/>
                        <TextInput id="export_start_date" type="date" class="mt-1 block w-full" v-model="formExport.start_date" :max="todayString" :disabled="formExport.range_type !== 'custom'" required />
                    </div>
                    <div>
                        <InputLabel for="export_end_date" value="Tanggal Akhir Lunas" :required="true"/>
                        <TextInput id="export_end_date" type="date" class="mt-1 block w-full" v-model="formExport.end_date" :max="todayString" :disabled="formExport.range_type !== 'custom'" required />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <SecondaryButton @click="showExportModal = false" type="button">Batal</SecondaryButton>
                        <PrimaryButton>Export Excel</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showIndividualModal" @close="closeIndividualModal" :maxWidth="'2xl'">
            <div v-if="formIndividual.processing" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 z-50 flex items-center justify-center rounded-lg">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="ml-3 text-gray-700 dark:text-gray-300">Memproses...</span>
            </div>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">
                    Buat Tagihan SPP Individual
                </h2>
                <form @submit.prevent="submitIndividualForm" class="space-y-6">
                    <div>
                        <InputLabel for="tagihan_id_siswa" value="Pilih Siswa" :required="true"/>
                        <SearchableSelect
                            v-model="formIndividual.id_siswa"
                            :options="allSiswa.map(s => ({ value: s.id_siswa, label: s.nama_siswa + ' - ' + (s.kelas ? s.kelas.nama_kelas : 'Tidak Ada Kelas') + ' (' + (s.user ? s.user.email : 'N/A') + ')' }))"
                            placeholder="Ketik untuk mencari Siswa..."
                        />
                        <InputError class="mt-2" :message="formIndividual.errors.id_siswa" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="tagihan_periode_bulan_ind" value="Bulan Periode" :required="true"/>
                            <select id="tagihan_periode_bulan_ind" v-model="formIndividual.periode_tagihan_bulan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="formIndividual.errors.periode_tagihan_bulan" />
                        </div>
                        <div>
                            <InputLabel for="tagihan_periode_tahun_ind" value="Tahun Periode" :required="true"/>
                            <select id="tagihan_periode_tahun_ind" v-model="formIndividual.periode_tagihan_tahun" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <InputError class="mt-2" :message="formIndividual.errors.periode_tagihan_tahun" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="tagihan_jumlah_spp_ind" value="Jumlah SPP Ditagih" :required="true"/>
                        <TextInput id="tagihan_jumlah_spp_ind" type="number" step="1000" class="mt-1 block w-full" v-model.number="formIndividual.jumlah_spp_ditagih" required placeholder="Otomatis terisi saat siswa dipilih" />
                        <InputError class="mt-2" :message="formIndividual.errors.jumlah_spp_ditagih" />
                    </div>
                    <div>
                        <InputLabel for="tagihan_admin_fee_ind" value="Admin Fee (Opsional)" />
                        <TextInput id="tagihan_admin_fee_ind" type="number" step="1000" class="mt-1 block w-full" v-model.number="formIndividual.admin_fee_ditagih" placeholder="0" />
                        <InputError class="mt-2" :message="formIndividual.errors.admin_fee_ditagih" />
                    </div>
                    <div>
                        <InputLabel for="tagihan_jatuh_tempo_ind" value="Tanggal Jatuh Tempo" :required="true"/>
                        <TextInput id="tagihan_jatuh_tempo_ind" type="date" class="mt-1 block w-full" v-model="formIndividual.tanggal_jatuh_tempo" required />
                        <InputError class="mt-2" :message="formIndividual.errors.tanggal_jatuh_tempo" />
                    </div>
                    <div>
                        <label class="flex items-center">
                            <Checkbox v-model:checked="formIndividual.send_whatsapp_notif" name="send_whatsapp_notif" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Kirim Notifikasi Invoice via WhatsApp</span>
                        </label>
                        <InputError class="mt-2" :message="formIndividual.errors.send_whatsapp_notif" />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeIndividualModal" type="button" :disabled="formIndividual.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': formIndividual.processing }" :disabled="formIndividual.processing">
                            <span v-if="!formIndividual.processing">Buat Tagihan</span>
                            <span v-else>Memproses...</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showBulkByClassModal" @close="closeBulkByClassModal" :maxWidth="'2xl'">
            <div v-if="formBulkByClass.processing" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 z-50 flex items-center justify-center rounded-lg">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="ml-3 text-gray-700 dark:text-gray-300">Sedang mengenerate tagihan, mohon tunggu...</span>
            </div>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">
                    Buat Tagihan SPP Massal (Per Kelas)
                </h2>
                <form @submit.prevent="submitBulkByClassForm" class="space-y-6">
                    <div>
                        <InputLabel for="bulk_id_kelas" value="Pilih Kelas" :required="true"/>
                        <select id="bulk_id_kelas" v-model="formBulkByClass.id_kelas" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="" disabled>-- Pilih Kelas --</option>
                            <option v-for="kelasItem in allKelas" :key="kelasItem.id_kelas" :value="kelasItem.id_kelas">
                                {{ kelasItem.nama_kelas }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="formBulkByClass.errors.id_kelas" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="bulk_periode_bulan" value="Bulan Periode" :required="true"/>
                            <select id="bulk_periode_bulan" v-model="formBulkByClass.periode_tagihan_bulan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="formBulkByClass.errors.periode_tagihan_bulan" />
                        </div>
                        <div>
                            <InputLabel for="bulk_periode_tahun" value="Tahun Periode" :required="true"/>
                                <select id="bulk_periode_tahun" v-model="formBulkByClass.periode_tagihan_tahun" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <InputError class="mt-2" :message="formBulkByClass.errors.periode_tagihan_tahun" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="bulk_jatuh_tempo" value="Tanggal Jatuh Tempo" :required="true"/>
                        <TextInput id="bulk_jatuh_tempo" type="date" class="mt-1 block w-full" v-model="formBulkByClass.tanggal_jatuh_tempo" required />
                        <InputError class="mt-2" :message="formBulkByClass.errors.tanggal_jatuh_tempo" />
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium text-gray-900 dark:text-gray-100">Jumlah SPP</legend>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center">
                                <input type="radio" v-model="formBulkByClass.jenis_jumlah_spp" value="default" class="form-radio h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Gunakan SPP Default Siswa/Kelas</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" v-model="formBulkByClass.jenis_jumlah_spp" value="manual" class="form-radio h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Input Jumlah Manual (untuk semua siswa)</span>
                            </label>
                        </div>
                        <div v-if="formBulkByClass.jenis_jumlah_spp === 'manual'" class="mt-2">
                            <TextInput type="number" step="1000" class="mt-1 block w-full" v-model.number="formBulkByClass.jumlah_spp_manual" placeholder="Jumlah SPP Manual"/>
                            <InputError class="mt-2" :message="formBulkByClass.errors.jumlah_spp_manual" />
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-medium text-gray-900 dark:text-gray-100">Admin Fee</legend>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center">
                                <input type="radio" v-model="formBulkByClass.jenis_admin_fee" value="default" class="form-radio h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Gunakan Admin Fee Default Siswa</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" v-model="formBulkByClass.jenis_admin_fee" value="manual" class="form-radio h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Input Admin Fee Manual (untuk semua siswa)</span>
                            </label>
                        </div>
                        <div v-if="formBulkByClass.jenis_admin_fee === 'manual'" class="mt-2">
                            <TextInput type="number" step="1000" class="mt-1 block w-full" v-model.number="formBulkByClass.admin_fee_manual" placeholder="Admin Fee Manual"/>
                            <InputError class="mt-2" :message="formBulkByClass.errors.admin_fee_manual" />
                        </div>
                    </fieldset>
                    
                    <div class="mt-4">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="formBulkByClass.send_whatsapp_notif" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Kirim Notifikasi Invoice via WhatsApp</span>
                        </label>
                        <InputError class="mt-2" :message="formBulkByClass.errors.send_whatsapp_notif" />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeBulkByClassModal" type="button" :disabled="formBulkByClass.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': formBulkByClass.processing }" :disabled="formBulkByClass.processing">
                            <span v-if="!formBulkByClass.processing">Generate Tagihan</span>
                            <span v-else>Memproses...</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showBulkAllModal" @close="closeBulkAllModal" :maxWidth="'2xl'">
            <div v-if="formBulkAll.processing" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 z-50 flex items-center justify-center rounded-lg">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="ml-3 text-gray-700 dark:text-gray-300">Memulai proses di latar belakang...</span>
            </div>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">
                    Generate Tagihan untuk Semua Siswa Aktif
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Proses ini akan berjalan di latar belakang (queue). Tagihan hanya akan dibuat untuk siswa aktif yang belum memiliki tagihan pada periode yang dipilih. Jumlah SPP dan Admin Fee akan diambil dari pengaturan default masing-masing siswa/kelas.
                </p>
                <form @submit.prevent="submitBulkAllForm" class="space-y-6 mt-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="bulk_all_periode_bulan" value="Bulan Periode" :required="true"/>
                            <select id="bulk_all_periode_bulan" v-model="formBulkAll.periode_tagihan_bulan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="formBulkAll.errors.periode_tagihan_bulan" />
                        </div>
                        <div>
                            <InputLabel for="bulk_all_periode_tahun" value="Tahun Periode" :required="true"/>
                            <select id="bulk_all_periode_tahun" v-model="formBulkAll.periode_tagihan_tahun" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <InputError class="mt-2" :message="formBulkAll.errors.periode_tagihan_tahun" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="bulk_all_jatuh_tempo" value="Tanggal Jatuh Tempo" :required="true"/>
                        <TextInput id="bulk_all_jatuh_tempo" type="date" class="mt-1 block w-full" v-model="formBulkAll.tanggal_jatuh_tempo" required />
                        <InputError class="mt-2" :message="formBulkAll.errors.tanggal_jatuh_tempo" />
                    </div>
                    <div class="mt-4">
                        <label class="flex items-center">
                            <Checkbox v-model:checked="formBulkAll.send_whatsapp_notif" name="send_whatsapp_notif" />
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Kirim Notifikasi Invoice via WhatsApp</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeBulkAllModal" type="button" :disabled="formBulkAll.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': formBulkAll.processing }" :disabled="formBulkAll.processing">
                            <span v-if="!formBulkAll.processing">Mulai Proses</span>
                            <span v-else>Memulai...</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Mobile Filter Modal -->
        <Modal :show="showMobileFilters" @close="showMobileFilters = false" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-3 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Filter Pencarian</h2>
                    <button @click="showMobileFilters = false" class="text-gray-400 hover:text-gray-500">
                        <XCircleIcon class="w-6 h-6" />
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Kelas" />
                        <select v-model="selectedKelasId" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">Semua Kelas</option>
                            <option v-for="k in allKelas" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="selectedStatus" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">Semua Status</option>
                            <option v-for="status in allStatus" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Bulan Periode" />
                        <select v-model="selectedPeriodeBulan" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">Semua Bulan</option>
                            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Tahun Periode" />
                        <select v-model="selectedPeriodeTahun" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">Semua Tahun</option>
                            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                    <div class="pt-4 border-t dark:border-gray-700">
                        <PrimaryButton class="w-full justify-center" @click="showMobileFilters = false">
                            Terapkan Filter
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
