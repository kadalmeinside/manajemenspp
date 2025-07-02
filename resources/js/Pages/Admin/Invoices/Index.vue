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
import Toast from '@/Components/Toast.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { PlusIcon, EyeIcon, ChevronDownIcon, PencilIcon, TrashIcon, XCircleIcon, ArrowPathIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

// Computed props
const invoiceList = computed(() => page.props.invoiceList || { data: [], links: [], current_page: 1, total: 0, per_page: 10 });
const filters = computed(() => page.props.filters || { search: '', kelas_id: '', status: '' });
const allSiswa = computed(() => page.props.allSiswa || []);
const allKelas = computed(() => page.props.allKelas || []);
const allStatus = computed(() => page.props.allStatus || []);
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

const searchQuery = ref(filters.search || '');
const selectedKelasId = ref(filters.kelas_id || '');
const selectedStatus = ref(filters.status || '');

const submitFilters = () => {
    router.get(route('admin.invoices.index'), {
        search: searchQuery.value,
        kelas_id: selectedKelasId.value,
        status: selectedStatus.value,
        page: 1,
    }, {
        preserveState: true, preserveScroll: true, replace: true,
        only: ['invoiceList', 'filters'],
    });
};
watch([searchQuery, selectedKelasId, selectedStatus], debounce(submitFilters, 300));


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
    searchQuery.value = urlParams.get('search') || filters.search || '';
    selectedKelasId.value = urlParams.get('kelas_id') || filters.kelas_id || '';
    selectedStatus.value = urlParams.get('status') || filters.status || '';
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Invoice & Tagihan</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="mb-6 p-4 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari deskripsi, siswa..." class="w-full md:col-span-1"/>
                        <select v-model="selectedKelasId" class="w-full md:col-span-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">Semua Kelas</option>
                            <option v-for="k in allKelas" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                        </select>
                        <select v-model="selectedStatus" class="w-full md:col-span-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">Semua Status</option>
                            <option v-for="status in allStatus" :key="status" :value="status">{{ status }}</option>
                        </select>

                        <div class="md:col-span-1 flex md:justify-end">
                            <Dropdown align="right" width="56" v-if="can?.create_invoice">
                                <template #trigger>
                                    <PrimaryButton class="w-full md:w-auto inline-flex items-center justify-center">
                                        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" /> Buat Tagihan
                                        <ChevronDownIcon class="ml-2 -mr-0.5 h-4 w-4" />
                                    </PrimaryButton>
                                </template>
                                <template #content>
                                    <button @click="openCreateIndividualModal" class="block w-full px-4 py-2 text-start text-sm ...">Tagihan Individual (SPP)</button>
                                    <button @click="openBulkByClassModal" class="block w-full px-4 py-2 text-start text-sm ...">Tagihan Massal (Per Kelas)</button>
                                    <button @click="openBulkAllModal" class="block w-full px-4 py-2 text-start text-sm ...">Tagihan Massal (Semua Siswa)</button>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!invoiceList.data || invoiceList.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data invoice.</td>
                                </tr>
                                <tr v-else v-for="invoice in invoiceList.data" :key="invoice.id">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ invoice.siswa_nama }} <br>
                                        <span class="text-xs text-gray-500">{{ invoice.kelas_nama }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ formatDescription(invoice.description).main }}</div>
                                        <div class="text-gray-500">{{ formatDescription(invoice.description).detail }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 ">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ invoice.due_date_formatted }}</td>
                                    <td class="px-6 py-4 text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(invoice.status)">{{ invoice.status }}</span></td>
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
                    <div v-if="invoiceList.links && invoiceList.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap -mb-1 justify-center">
                            <template v-for="(link, key) in invoiceList.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm select-none" v-html="link.label" />
                                <Link v-else 
                                    class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300"
                                    :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600 dark:text-white dark:border-indigo-700': link.active }"
                                    :href="link.url"
                                    v-html="link.label"
                                    preserve-scroll />
                            </template>
                        </div>
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
                        <select id="tagihan_id_siswa" v-model="formIndividual.id_siswa" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="" disabled>-- Pilih Siswa --</option>
                            <option v-for="siswaItem in allSiswa" :key="siswaItem.id_siswa" :value="siswaItem.id_siswa">
                                {{ siswaItem.nama_siswa }} (Email: {{ siswaItem.user ? siswaItem.user.email : 'N/A' }})
                            </option>
                        </select>
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

    </AuthenticatedLayout>
</template>
