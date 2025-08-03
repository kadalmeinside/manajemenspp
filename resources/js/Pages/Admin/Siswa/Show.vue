<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ArrowLeftIcon, PencilIcon, LinkIcon } from '@heroicons/vue/20/solid';
import { debounce } from 'lodash';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Toast from '@/Components/Toast.vue'; // <-- Import Toast
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const page = usePage();

const props = defineProps({
    siswa: Object,
    pendingInvoices: { type: Array, default: () => [] },
    paidInvoices: { type: Array, default: () => [] },
    expiredInvoices: { type: Array, default: () => [] },
    pageTitle: String,
    filters: Object,
    availableYears: { type: Array, default: () => [] },
    allKelas: { type: Array, default: () => [] },
    statusSiswaOptions: { type: Array, default: () => [] },
});

// --- State untuk Notifikasi Flash ---
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// --- State untuk Filter & Tab ---
const selectedTahun = ref(props.filters.tahun);
const activeTab = ref('pending');

// --- State & Logika untuk Modal Edit ---
const isEditModalOpen = ref(false);
const form = useForm({
    nama_siswa: '',
    user_name: '',
    email_wali: '',
    nomor_telepon_wali: '',
    user_password: '',
    user_password_confirmation: '',
    id_kelas: '',
    status_siswa: '',
    tanggal_lahir: '',
    tanggal_bergabung: '',
    jumlah_spp_custom: null,
    admin_fee_custom: null,
});

// --- Computed Property untuk Formatting Angka di Form ---
const sppCustomFormatted = computed({
  get() {
    const numberValue = parseFloat(form.jumlah_spp_custom);
    if (isNaN(numberValue)) return '';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(numberValue);
  },
  set(newValue) {
    const numericString = newValue.replace(/[^0-9]/g, '');
    form.jumlah_spp_custom = numericString ? parseInt(numericString, 10) : null;
  }
});

const adminFeeCustomFormatted = computed({
  get() {
    const numberValue = parseFloat(form.admin_fee_custom);
    if (isNaN(numberValue)) return '';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(numberValue);
  },
  set(newValue) {
    const numericString = newValue.replace(/[^0-9]/g, '');
    form.admin_fee_custom = numericString ? parseInt(numericString, 10) : null;
  }
});

const openEditModal = () => {
    // Isi form dengan data siswa saat ini
    form.nama_siswa = props.siswa.nama_siswa;
    form.user_name = props.siswa.user_name;
    form.email_wali = props.siswa.email_wali;
    form.nomor_telepon_wali = props.siswa.nomor_telepon_wali;
    form.id_kelas = props.siswa.id_kelas;
    form.status_siswa = props.siswa.status_siswa;
    form.tanggal_lahir = props.siswa.tanggal_lahir;
    form.tanggal_bergabung = props.siswa.tanggal_bergabung;
    form.jumlah_spp_custom = parseFloat(props.siswa.jumlah_spp_custom) || null;
    form.admin_fee_custom = parseFloat(props.siswa.admin_fee_custom) || null;
    isEditModalOpen.value = true;
};

const closeModal = () => {
    isEditModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitUpdate = () => {
    form.put(route('admin.siswa.update', props.siswa.id_siswa), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- Watcher untuk Filter Tahun ---
watch(selectedTahun, debounce((value) => {
    router.get(route('admin.siswa.show', props.siswa.id_siswa), {
        tahun: value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['pendingInvoices', 'paidInvoices', 'expiredInvoices', 'filters'],
    });
}, 300));

// --- Helper Functions ---
const getStatusClass = (status) => {
    if (status === 'PAID' || status === 'Aktif') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};

const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.siswa.index')" class="text-gray-400 hover:text-gray-600">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ pageTitle }}: {{ siswa.nama_siswa }}
                </h2>
            </div>
        </template>

         <Toast :message="flashMessage" :type="flashType" />

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Kartu Biodata Siswa -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Biodata Siswa</h3>
                    <SecondaryButton @click="openEditModal">
                        <PencilIcon class="h-4 w-4 mr-2" />
                        Edit
                    </SecondaryButton>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.nama_siswa }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">NIS</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.nis ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">No. Telepon Wali</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.nomor_telepon_wali ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kelas</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.kelas_nama ?? 'Belum ada kelas' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm"><span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="getStatusClass(siswa.status_siswa)">{{ siswa.status_siswa }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email Wali</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.email_wali ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Jumlah SPP</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.jumlah_spp_custom_formatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Admin Fee</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.admin_fee_custom_formatted }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Riwayat Invoice -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                 <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Riwayat Invoice</h3>
                    <select v-model="selectedTahun" class="w-full sm:w-auto text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                        <option v-for="year in availableYears" :key="year" :value="year">Tahun {{ year }}</option>
                    </select>
                </div>
                <div class="px-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                        <button @click="activeTab = 'pending'" :class="[activeTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Tertunda <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ pendingInvoices.length }}</span>
                        </button>
                        <button @click="activeTab = 'paid'" :class="[activeTab === 'paid' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Lunas <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ paidInvoices.length }}</span>
                        </button>
                        <button @click="activeTab = 'expired'" :class="[activeTab === 'expired' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Kadaluarsa/Gagal <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ expiredInvoices.length }}</span>
                        </button>
                    </nav>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Bayar</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="activeTab === 'pending'">
                                <tr v-if="pendingInvoices.length === 0"><td colspan="4" class="px-6 py-10 text-center text-gray-500">Tidak ada tagihan tertunda.</td></tr>
                                <tr v-for="invoice in pendingInvoices" :key="invoice.id">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ getShortDescription(invoice.description) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.paid_at_formatted }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium"><a v-if="invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-indigo-600 hover:text-indigo-900">Lihat</a></td>
                                </tr>
                            </template>
                            <template v-if="activeTab === 'paid'">
                                <tr v-if="paidInvoices.length === 0"><td colspan="4" class="px-6 py-10 text-center text-gray-500">Tidak ada tagihan lunas.</td></tr>
                                <tr v-for="invoice in paidInvoices" :key="invoice.id">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ getShortDescription(invoice.description) }}
                                        <div v-if="invoice.paymentParent" class="text-xs text-gray-500 flex items-center mt-1" title="Dibayar via Tagihan Gabungan">
                                            <LinkIcon class="h-3 w-3 mr-1" />
                                            <span>{{ getShortDescription(invoice.paymentParent.description) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.paid_at_formatted }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a v-if="invoice.paymentParent?.xendit_payment_url || invoice.xendit_payment_url" :href="invoice.paymentParent?.xendit_payment_url || invoice.xendit_payment_url" target="_blank" class="text-indigo-600 hover:text-indigo-900">Lihat</a>
                                    </td>
                                </tr>
                            </template>
                            <template v-if="activeTab === 'expired'">
                                <tr v-if="expiredInvoices.length === 0"><td colspan="4" class="px-6 py-10 text-center text-gray-500">Tidak ada tagihan kadaluarsa/gagal.</td></tr>
                                <tr v-for="invoice in expiredInvoices" :key="invoice.id">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ getShortDescription(invoice.description) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.paid_at_formatted }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium"><a v-if="invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-indigo-600 hover:text-indigo-900">Lihat</a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Edit Biodata -->
        <Modal :show="isEditModalOpen" @close="closeModal" :maxWidth="'2xl'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Edit Siswa</h2>
                <form @submit.prevent="submitUpdate" class="space-y-6" novalidate>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="nama_siswa_modal" value="Nama Lengkap Siswa" required />
                            <TextInput id="nama_siswa_modal" type="text" class="mt-1 block w-full" v-model="form.nama_siswa" required />
                            <InputError class="mt-2" :message="form.errors.nama_siswa" />
                        </div>
                         <div>
                            <InputLabel for="user_name_modal" value="Nama Akun Login (Wali/Siswa)" required />
                            <TextInput id="user_name_modal" type="text" class="mt-1 block w-full" v-model="form.user_name" required />
                            <InputError class="mt-2" :message="form.errors.user_name" />
                        </div>
                        <div>
                            <InputLabel for="email_wali_modal" value="Email Wali (Untuk Login)" required />
                            <TextInput id="email_wali_modal" type="email" class="mt-1 block w-full" v-model="form.email_wali" required />
                            <InputError class="mt-2" :message="form.errors.email_wali" />
                        </div>
                         <div>
                            <InputLabel for="nomor_telepon_wali_modal" value="No. Telepon Wali" />
                            <TextInput id="nomor_telepon_wali_modal" type="text" class="mt-1 block w-full" v-model="form.nomor_telepon_wali" />
                            <InputError class="mt-2" :message="form.errors.nomor_telepon_wali" />
                        </div>
                        <div>
                            <InputLabel for="password_edit_modal" value="Password Baru Akun (Opsional)" />
                            <TextInput id="password_edit_modal" type="password" class="mt-1 block w-full" v-model="form.user_password" placeholder="Isi jika ingin ganti password"/>
                            <InputError class="mt-2" :message="form.errors.user_password" />
                        </div>
                        <div v-if="form.user_password">
                            <InputLabel for="password_confirmation_edit_modal" value="Konfirmasi Password Baru" />
                            <TextInput id="password_confirmation_edit_modal" type="password" class="mt-1 block w-full" v-model="form.user_password_confirmation" />
                            <InputError class="mt-2" :message="form.errors.user_password_confirmation" />
                        </div>
                        <div>
                            <InputLabel for="id_kelas_modal" value="Kelas" required />
                            <select id="id_kelas_modal" v-model="form.id_kelas" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="" disabled>Pilih Kelas</option>
                                <option v-for="k in allKelas" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.id_kelas" />
                        </div>
                        <div>
                            <InputLabel for="nis_modal" value="Nomor Induk Siswa (NIS)" />
                            <TextInput id="nis_modal" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-900" :value="siswa.nis" readonly />
                        </div>
                        <div>
                            <InputLabel for="status_siswa_modal" value="Status Siswa" required />
                            <select id="status_siswa_modal" v-model="form.status_siswa" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option v-for="status in statusSiswaOptions" :key="status" :value="status">{{ status }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status_siswa" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_lahir_modal" value="Tanggal Lahir" required />
                            <TextInput id="tanggal_lahir_modal" type="date" class="mt-1 block w-full" v-model="form.tanggal_lahir" required />
                            <InputError class="mt-2" :message="form.errors.tanggal_lahir" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_bergabung_modal" value="Tanggal Bergabung" required />
                            <TextInput id="tanggal_bergabung_modal" type="date" class="mt-1 block w-full" v-model="form.tanggal_bergabung" required />
                            <InputError class="mt-2" :message="form.errors.tanggal_bergabung" />
                        </div>
                        <div>
                            <InputLabel for="jumlah_spp_custom_modal" value="SPP Custom (Opsional)" />
                            <TextInput id="jumlah_spp_custom_modal" type="text" class="mt-1 block w-full" v-model="sppCustomFormatted" />
                            <InputError class="mt-2" :message="form.errors.jumlah_spp_custom" />
                        </div>
                         <div>
                            <InputLabel for="admin_fee_custom_modal" value="Admin Fee Custom (Opsional)" />
                            <TextInput id="admin_fee_custom_modal" type="text" class="mt-1 block w-full" v-model="adminFeeCustomFormatted" />
                            <InputError class="mt-2" :message="form.errors.admin_fee_custom" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button" :disabled="form.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Update Siswa
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
