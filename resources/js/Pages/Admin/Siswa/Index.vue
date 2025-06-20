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
import SelectInput from '@/Components/SelectInput.vue';
import Toast from '@/Components/Toast.vue';
import { PlusIcon, EyeIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

const siswaList = computed(() => page.props.siswaList || { data: [], links: [], current_page: 1, total: 0, per_page: 10 });
const filters = computed(() => page.props.filters || { search: '', kelas_id: '' });
const allKelas = computed(() => page.props.allKelas || []);
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const statusSiswaOptions = ['Aktif', 'Non-Aktif', 'Lulus', 'Cuti', 'pending_payment'];

const showSiswaModal = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id_siswa: null,
    nama_siswa: '',
    tanggal_lahir: '',
    status_siswa: 'Aktif',
    id_kelas: '',
    email_wali: '',
    nomor_telepon_wali: '',
    tanggal_bergabung: new Date().toISOString().slice(0,10),
    jumlah_spp_custom: null,
    admin_fee_custom: null,
    user_name: '',
    user_password: '',
    user_password_confirmation: '',
});

const searchQuery = ref(filters.value.search || '');
const selectedKelasId = ref(filters.value.kelas_id || '');

const submitFilters = () => {
    router.get(route('admin.siswa.index'), {
        search: searchQuery.value,
        kelas_id: selectedKelasId.value,
        page: 1,
    }, {
        preserveState: true, preserveScroll: true, replace: true,
        only: ['siswaList', 'filters'],
    });
};
watch([searchQuery, selectedKelasId], debounce(submitFilters, 300));

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.status_siswa = 'Aktif';
    form.tanggal_bergabung = new Date().toISOString().slice(0,10);
    form.clearErrors();
    showSiswaModal.value = true;
};

const openEditModal = (siswaItem) => {
    const data = siswaItem.full_data_for_edit;
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id_siswa = data.id_siswa;
    form.nama_siswa = data.nama_siswa;
    form.tanggal_lahir = data.tanggal_lahir;
    form.status_siswa = data.status_siswa;
    form.id_kelas = data.id_kelas;
    form.email_wali = data.user.email;
    form.nomor_telepon_wali = data.nomor_telepon_wali;
    form.tanggal_bergabung = data.tanggal_bergabung;
    form.jumlah_spp_custom = parseFloat(data.jumlah_spp_custom) || null;
    form.admin_fee_custom = parseFloat(data.admin_fee_custom) || null;
    form.user_name = data.user.name;
    form.user_password = '';
    form.user_password_confirmation = '';
    showSiswaModal.value = true;
};

const closeModal = () => {
    showSiswaModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitSiswaForm = () => {
    form.clearErrors();
    let hasClientErrors = false;

    if (!form.nama_siswa) { form.setError('nama_siswa', 'Nama Siswa wajib diisi.'); hasClientErrors = true; }
    if (!form.user_name) { form.setError('user_name', 'Nama Akun Login wajib diisi.'); hasClientErrors = true; }
    if (!form.email_wali) { form.setError('email_wali', 'Email Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.id_kelas) { form.setError('id_kelas', 'Kelas wajib dipilih.'); hasClientErrors = true; }
    if (!form.tanggal_lahir) { form.setError('tanggal_lahir', 'Tanggal Lahir wajib diisi.'); hasClientErrors = true; }
    if (!form.tanggal_bergabung) { form.setError('tanggal_bergabung', 'Tanggal Bergabung wajib diisi.'); hasClientErrors = true; }

    if (!isEditMode.value) {
        if (!form.user_password) { form.setError('user_password', 'Password wajib diisi.'); hasClientErrors = true; }
        if (form.user_password && !form.user_password_confirmation) { form.setError('user_password_confirmation', 'Konfirmasi password wajib diisi.'); hasClientErrors = true; }
    }
    if (form.user_password && form.user_password !== form.user_password_confirmation) {
        form.setError('user_password_confirmation', 'Konfirmasi password tidak cocok.');
        hasClientErrors = true;
    }
    
    if (hasClientErrors) return;

    const submissionRoute = isEditMode.value
        ? route('admin.siswa.update', form.id_siswa)
        : route('admin.siswa.store');
    const httpMethod = isEditMode.value ? 'put' : 'post';

    form.submit(httpMethod, submissionRoute, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            //router.reload({ preserveScroll: true, only: ['siswaList', 'flash'] });
        },
        onError: (errors) => { console.error('Siswa form submission error:', errors); }
    });
};

const showDeleteConfirmModal = ref(false);
const siswaToDelete = ref(null);

const confirmDeleteSiswa = (siswaItem) => {
    siswaToDelete.value = siswaItem;
    showDeleteConfirmModal.value = true;
};

const deleteSiswa = () => {
    if (siswaToDelete.value) {
        const currentSiswaData = siswaList.value.data;
        const currentPage = siswaList.value.current_page;
        const wasLastItemOnPage = currentSiswaData.length === 1 && currentPage > 1 && siswaToDelete.value.id_siswa === currentSiswaData[0].id_siswa;

        router.delete(route('admin.siswa.destroy', siswaToDelete.value.id_siswa), {
            preserveScroll: true,
            onSuccess: () => {
                siswaToDelete.value = null;
                showDeleteConfirmModal.value = false;
                if (wasLastItemOnPage) {
                     router.get(route('admin.siswa.index'), {
                        search: searchQuery.value,
                        kelas_id: selectedKelasId.value,
                        page: currentPage - 1,
                    }, {
                        preserveState: false, preserveScroll: true, replace: true,
                        only: ['siswaList', 'flash', 'filters'],
                    });
                } else {
                    router.reload({ preserveScroll: true, only: ['siswaList', 'flash'] });
                }
            },
            onError: (errors) => { console.error('Delete siswa error:', errors); }
        });
    }
};

// State untuk Modal Impor
const showImportModal = ref(false);
const importForm = useForm({
    file_import: null,
});


// Fungsi untuk membuka modal impor
const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    showImportModal.value = true;
};

// Fungsi untuk menutup modal impor
const closeImportModal = () => {
    showImportModal.value = false;
};

// Fungsi untuk submit file impor
const submitImport = () => {
    importForm.post(route('admin.siswa.import'), {
        onSuccess: () => {
            closeImportModal();
            router.reload({ only: ['siswaList', 'flash'] });
        },
        onError: (errors) => {
            // Error validasi file (misal bukan excel) akan otomatis ditangani
            console.error('Import error:', errors);
        }
    });
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || (filters.value ? filters.value.search : '') || '';
    selectedKelasId.value = urlParams.get('kelas_id') || (filters.value ? filters.value.kelas_id : '') || '';
});
</script>

<template>
    <Head title="Manajemen Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Siswa</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 pb-0 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex flex-col sm:flex-row items-center gap-3 flex-grow">
                                <TextInput type="text" v-model="searchQuery" placeholder="Cari nama, email wali, kelas..." class="w-full sm:w-auto md:flex-grow lg:max-w-md" aria-label="Cari Siswa"/>
                                <select v-model="selectedKelasId" class="w-full sm:w-auto md:flex-grow lg:max-w-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" aria-label="Filter berdasarkan kelas">
                                    <option value="">Semua Kelas</option>
                                    <option v-for="kelasItem in allKelas" :key="kelasItem.id_kelas" :value="kelasItem.id_kelas">{{ kelasItem.nama_kelas }}</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <a :href="route('admin.siswa.export')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Ekspor
                                </a>
                                <PrimaryButton @click="openImportModal" v-if="can?.create_siswa">
                                    Impor
                                </PrimaryButton>
                                <PrimaryButton @click="openCreateModal" v-if="can?.create_siswa" class="w-full md:w-auto mt-2 md:mt-0">
                                    <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Tambah Siswa Baru
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email Wali (Login)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Bergabung</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!siswaList || !siswaList.data || siswaList.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data siswa.</td>
                                </tr>
                                <tr v-else v-for="item in siswaList.data" :key="item.id_siswa">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ item.nama_siswa }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ item.kelas_nama }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ item.email_wali }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                         <span :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            item.status_siswa === 'Aktif' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                            item.status_siswa === 'Non-Aktif' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                            item.status_siswa === 'Lulus' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' // Cuti
                                        ]">
                                            {{ item.status_siswa }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ item.tanggal_bergabung_formatted }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Link :href="route('admin.siswa.show', item.id_siswa)" class="text-gray-400 hover:text-indigo-600 p-1" title="Lihat Detail">
                                                <EyeIcon class="h-5 w-5" />
                                            </Link>
                                            <button @click="openEditModal(item)" class="text-gray-400 hover:text-indigo-600 p-1" v-if="can?.edit_siswa" title="Edit Siswa"><PencilSquareIcon class="h-5 w-5" /></button>
                                            <button @click="confirmDeleteSiswa(item)" class="text-gray-400 hover:text-red-600 p-1" v-if="can?.delete_siswa" title="Hapus Siswa"><TrashIcon class="h-5 w-5" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="siswaList && siswaList.links && siswaList.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap -mb-1 justify-center">
                            <template v-for="(link, key) in siswaList.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600 select-none" v-html="link.label" />
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

        <Modal :show="showImportModal" @close="closeImportModal" maxWidth="lg">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3">
                    Impor Data Siswa dari Excel
                </h2>
                <form @submit.prevent="submitImport">
                    <div>
                        <InputLabel for="file_import" value="Pilih File (.xlsx, .xls)" />
                        <input
                            type="file"
                            @input="importForm.file_import = $event.target.files[0]"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"
                            required
                        />
                        <progress v-if="importForm.progress" :value="importForm.progress.percentage" max="100" class="w-full mt-2">
                            {{ importForm.progress.percentage }}%
                        </progress>
                        <InputError class="mt-2" :message="importForm.errors.file_import" />
                        <p class="mt-2 text-xs text-gray-500">
                            Pastikan file Excel Anda memiliki heading: <strong>nama_siswa, status, kelas, email_wali, no_telepon_wali, tanggal_bergabung</strong>.
                        </p>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="closeImportModal" type="button" :disabled="importForm.processing">Batal</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': importForm.processing }" :disabled="importForm.processing">
                            <span v-if="!importForm.processing">Impor Data</span>
                            <span v-else>Mengunggah...</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal untuk Create/Edit Siswa -->
        <Modal :show="showSiswaModal" @close="closeModal" :maxWidth="'2xl'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">{{ isEditMode ? 'Edit Siswa' : 'Tambah Siswa Baru' }}</h2>
                <form @submit.prevent="submitSiswaForm" class="space-y-6" novalidate>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="nama_siswa_modal" value="Nama Lengkap Siswa" required />
                            <TextInput id="nama_siswa_modal" type="text" class="mt-1 block w-full" v-model="form.nama_siswa" @input="form.clearErrors('nama_siswa')" required />
                            <InputError class="mt-2" :message="form.errors.nama_siswa" />
                        </div>
                         <div>
                            <InputLabel for="user_name_modal" value="Nama Akun Login (Wali/Siswa)" required />
                            <TextInput id="user_name_modal" type="text" class="mt-1 block w-full" v-model="form.user_name" @input="form.clearErrors('user_name')" required />
                            <InputError class="mt-2" :message="form.errors.user_name" />
                        </div>
                        <div>
                            <InputLabel for="email_wali_modal" value="Email Wali (Untuk Login)" required />
                            <TextInput id="email_wali_modal" type="email" class="mt-1 block w-full" v-model="form.email_wali" @input="form.clearErrors('email_wali')" required />
                            <InputError class="mt-2" :message="form.errors.email_wali" />
                        </div>
                         <div>
                            <InputLabel for="nomor_telepon_wali_modal" value="No. Telepon Wali" />
                            <TextInput id="nomor_telepon_wali_modal" type="text" class="mt-1 block w-full" v-model="form.nomor_telepon_wali" @input="form.clearErrors('nomor_telepon_wali')" />
                            <InputError class="mt-2" :message="form.errors.nomor_telepon_wali" />
                        </div>
                        <div v-if="!isEditMode">
                            <InputLabel for="password_modal" value="Password Akun" required />
                            <TextInput id="password_modal" type="password" class="mt-1 block w-full" v-model="form.user_password" @input="form.clearErrors('user_password')" required />
                            <InputError class="mt-2" :message="form.errors.user_password" />
                        </div>
                        <div v-if="!isEditMode">
                            <InputLabel for="password_confirmation_modal" value="Konfirmasi Password Akun" required />
                            <TextInput id="password_confirmation_modal" type="password" class="mt-1 block w-full" v-model="form.user_password_confirmation" @input="form.clearErrors('user_password_confirmation')" required />
                            <InputError class="mt-2" :message="form.errors.user_password_confirmation" />
                        </div>
                         <div v-if="isEditMode">
                            <InputLabel for="password_edit_modal" value="Password Baru Akun (Opsional)" />
                            <TextInput id="password_edit_modal" type="password" class="mt-1 block w-full" v-model="form.user_password" @input="form.clearErrors('user_password')" placeholder="Isi jika ingin ganti password"/>
                            <InputError class="mt-2" :message="form.errors.user_password" />
                        </div>
                        <div v-if="isEditMode && form.user_password">
                            <InputLabel for="password_confirmation_edit_modal" value="Konfirmasi Password Baru" />
                            <TextInput id="password_confirmation_edit_modal" type="password" class="mt-1 block w-full" v-model="form.user_password_confirmation" @input="form.clearErrors('user_password_confirmation')" />
                            <InputError class="mt-2" :message="form.errors.user_password_confirmation" />
                        </div>
                        <div>
                            <InputLabel for="id_kelas_modal" value="Kelas" required />
                            <select id="id_kelas_modal" v-model="form.id_kelas" @change="form.clearErrors('id_kelas')" class="mt-1 block w-full ...">
                                <option value="" disabled>Pilih Kelas</option>
                                <option v-for="k in allKelas" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.id_kelas" />
                        </div>
                        <div>
                            <InputLabel for="status_siswa_modal" value="Status Siswa" required />
                            <select id="status_siswa_modal" v-model="form.status_siswa" @change="form.clearErrors('status_siswa')" class="mt-1 block w-full ...">
                                <option v-for="status in statusSiswaOptions" :key="status" :value="status">{{ status }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status_siswa" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_lahir_modal" value="Tanggal Lahir" required />
                            <TextInput id="tanggal_lahir_modal" type="date" class="mt-1 block w-full" v-model="form.tanggal_lahir" @input="form.clearErrors('tanggal_lahir')" required />
                            <InputError class="mt-2" :message="form.errors.tanggal_lahir" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_bergabung_modal" value="Tanggal Bergabung" required />
                            <TextInput id="tanggal_bergabung_modal" type="date" class="mt-1 block w-full" v-model="form.tanggal_bergabung" @input="form.clearErrors('tanggal_bergabung')" required />
                            <InputError class="mt-2" :message="form.errors.tanggal_bergabung" />
                        </div>
                        <div>
                            <InputLabel for="jumlah_spp_custom_modal" value="SPP Custom (Opsional)" />
                            <TextInput id="jumlah_spp_custom_modal" type="number" class="mt-1 block w-full" v-model.number="form.jumlah_spp_custom" @input="form.clearErrors('jumlah_spp_custom')"/>
                            <InputError class="mt-2" :message="form.errors.jumlah_spp_custom" />
                        </div>
                         <div>
                            <InputLabel for="admin_fee_custom_modal" value="Admin Fee Custom (Opsional)" />
                            <TextInput id="admin_fee_custom_modal" type="number" class="mt-1 block w-full" v-model.number="form.admin_fee_custom" @input="form.clearErrors('admin_fee_custom')" />
                            <InputError class="mt-2" :message="form.errors.admin_fee_custom" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t ...">
                        <SecondaryButton @click="closeModal" type="button" :disabled="form.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditMode ? 'Update Siswa' : 'Simpan Siswa' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Hapus Siswa
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus data siswa "<span class="font-semibold">{{ siswaToDelete?.nama_siswa }}</span>"?
                    Aksi ini juga akan menghapus akun login yang terhubung dan tidak dapat dibatalkan.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteConfirmModal = false" type="button"> Batal </SecondaryButton>
                    <DangerButton @click="deleteSiswa" :class="{ 'opacity-25': router.processing }" :disabled="router.processing">
                        Ya, Hapus
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>