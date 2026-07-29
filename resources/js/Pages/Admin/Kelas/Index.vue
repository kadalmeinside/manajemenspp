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
import Toast from '@/Components/Toast.vue';
import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

const kelas = computed(() => page.props.kelas);
const filters = computed(() => page.props.filters || {});
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const showKelasModal = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id_kelas: null,
    nama_kelas: '',
    deskripsi: '',
    biaya_spp_default: null,
    biaya_pendaftaran: null,
});

const onCurrencyKeyDown = (event) => {
    if ([46, 8, 9, 27, 13].indexOf(event.keyCode) !== -1 ||
        (event.keyCode === 65 && (event.ctrlKey === true || event.metaKey === true)) ||
        (event.keyCode === 67 && (event.ctrlKey === true || event.metaKey === true)) ||
        (event.keyCode === 86 && (event.ctrlKey === true || event.metaKey === true)) ||
        (event.keyCode === 88 && (event.ctrlKey === true || event.metaKey === true)) ||
        (event.keyCode >= 35 && event.keyCode <= 40)) {
        return;
    }
    if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
        event.preventDefault();
    }
};

const biayaSppFormatted = computed({
  get() {
    if (form.biaya_spp_default === null || form.biaya_spp_default === undefined) return '';
    return new Intl.NumberFormat('id-ID').format(form.biaya_spp_default);
  },
  set(newValue) {
    const numericString = newValue.replace(/[^0-9]/g, '');
    form.biaya_spp_default = numericString ? parseInt(numericString, 10) : null;
  }
});

const biayaPendaftaranFormatted = computed({
  get() {
    if (form.biaya_pendaftaran === null || form.biaya_pendaftaran === undefined) return '';
    return new Intl.NumberFormat('id-ID').format(form.biaya_pendaftaran);
  },
  set(newValue) {
    const numericString = newValue.replace(/[^0-9]/g, '');
    form.biaya_pendaftaran = numericString ? parseInt(numericString, 10) : null;
  }
});


const searchQuery = ref(filters.value.search || '');

const submitFilters = () => {
    router.get(route('admin.kelas.index'), {
        search: searchQuery.value,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['kelas', 'filters'],
    });
};
watch(searchQuery, debounce(submitFilters, 300));

// Fungsi Modal
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    showKelasModal.value = true;
};

const openEditModal = (kelasItem) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id_kelas = kelasItem.id_kelas;
    form.nama_kelas = kelasItem.nama_kelas;
    form.deskripsi = kelasItem.deskripsi;
    form.biaya_spp_default = kelasItem.biaya_spp_default;
    form.biaya_pendaftaran = kelasItem.biaya_pendaftaran;
    showKelasModal.value = true;
};

const closeModal = () => {
    showKelasModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitKelasForm = () => {
    const submissionRoute = isEditMode.value
        ? route('admin.kelas.update', form.id_kelas)
        : route('admin.kelas.store');
    const httpMethod = isEditMode.value ? 'put' : 'post';

    form.submit(httpMethod, submissionRoute, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// Delete Kelas
const showDeleteConfirmModal = ref(false);
const kelasToDelete = ref(null);

const confirmDeleteKelas = (kelasItem) => {
    kelasToDelete.value = kelasItem;
    showDeleteConfirmModal.value = true;
};

const deleteKelas = () => {
    if (kelasToDelete.value) {
        router.delete(route('admin.kelas.destroy', kelasToDelete.value.id_kelas), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmModal.value = false;
                kelasToDelete.value = null;
            },
        });
    }
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || filters.value.search || '';
});

// Helper untuk format mata uang
const formatCurrency = (value) => {
    if (value === null || value === undefined || isNaN(parseFloat(value))) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

</script>

<template>
    <Head title="Manajemen Kelas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="hidden md:block font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Kelas</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />
        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-full mx-auto px-1 sm:px-0">
                <!-- MOBILE: Search & Info Card (Sticky) -->
                <div class="sticky -top-4 z-10 bg-white dark:bg-gray-800 -mx-4 -mt-8 px-4 pt-4 pb-4 mb-4 border-b border-t-0 border-gray-200 dark:border-gray-700 shadow-sm lg:hidden rounded-b-2xl">
                    <div class="flex gap-2">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari nama kelas..." class="w-full bg-gray-50 border-gray-200 dark:bg-gray-900/50 dark:border-gray-700" aria-label="Cari kelas"/>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            <span v-if="kelas.total > 0">
                                Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-300">{{ kelas.from }}–{{ kelas.to }}</span>
                                dari <span class="font-semibold text-gray-700 dark:text-gray-300">{{ kelas.total }}</span> kelas
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                    </div>
                </div>

                <!-- MOBILE: Action Button Card -->
                <div class="lg:hidden mb-4 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg p-4" v-if="can?.create_kelas">
                    <PrimaryButton @click="openCreateModal" class="w-full flex justify-center py-2.5">
                        <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" /> Tambah Kelas Baru
                    </PrimaryButton>
                </div>

                <!-- DESKTOP: Filters & Tools Card -->
                <div class="hidden lg:block mb-6 p-6 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari nama atau deskripsi kelas..." class="w-full md:max-w-sm" aria-label="Cari kelas"/>
                        <PrimaryButton @click="openCreateModal" v-if="can?.create_kelas" class="w-full md:w-auto">
                            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Tambah Kelas Baru
                        </PrimaryButton>
                    </div>
                </div>

                <div class="bg-transparent sm:bg-white sm:dark:bg-gray-800 sm:shadow-sm sm:rounded-lg">
                    <div class="px-0 sm:px-6 pb-4 overflow-x-auto">
                        <!-- Tampilan Desktop (Table) -->
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Kelas</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Biaya Pendaftaran</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Biaya SPP Default</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!kelas || !kelas.data || kelas.data.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data kelas.</td>
                                    </tr>
                                    <tr v-else v-for="kelasItem in kelas.data" :key="kelasItem.id_kelas">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ kelasItem.nama_kelas }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ formatCurrency(kelasItem.biaya_pendaftaran) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ formatCurrency(kelasItem.biaya_spp_default) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(kelasItem)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 p-1 mr-2" v-if="can?.edit_kelas" title="Edit Kelas">
                                                <PencilIcon class="h-5 w-5" />
                                            </button>
                                            <button @click="confirmDeleteKelas(kelasItem)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-1" v-if="can?.delete_kelas" title="Hapus Kelas">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Mobile (Card) -->
                        <div class="block md:hidden space-y-4 mt-2 px-1 sm:px-0">
                            <div v-if="!kelas || !kelas.data || kelas.data.length === 0" class="text-center text-sm text-gray-500 py-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                Tidak ada data kelas.
                            </div>
                            <div v-else v-for="kelasItem in kelas.data" :key="'mob-'+kelasItem.id_kelas" class="bg-white dark:bg-gray-800 sm:dark:bg-gray-700 border border-gray-200 dark:border-gray-700 sm:dark:border-gray-600 rounded-lg p-4 shadow-sm flex flex-col gap-3">
                                <div class="flex justify-between items-start border-b border-gray-100 dark:border-gray-700 sm:dark:border-gray-600 pb-2">
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg leading-tight">{{ kelasItem.nama_kelas }}</h3>
                                    <div class="flex gap-2">
                                        <button @click="openEditModal(kelasItem)" class="p-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-md hover:bg-indigo-100" v-if="can?.edit_kelas" title="Edit Kelas">
                                            <PencilIcon class="h-4 w-4" />
                                        </button>
                                        <button @click="confirmDeleteKelas(kelasItem)" class="p-1.5 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-md hover:bg-red-100" v-if="can?.delete_kelas" title="Hapus Kelas">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Pendaftaran</p>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(kelasItem.biaya_pendaftaran) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">SPP Default</p>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(kelasItem.biaya_spp_default) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paginasi -->
                    <div class="px-0 sm:px-6 py-4 bg-transparent sm:bg-gray-50 sm:dark:bg-gray-700/50 sm:border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center sm:rounded-b-lg rounded-b-none sm:mt-0 mt-2 gap-4">
                        <p class="hidden sm:block text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                            <span v-if="kelas.total > 0">
                                Menampilkan <span class="font-medium">{{ kelas.from }}</span>–<span class="font-medium">{{ kelas.to }}</span>
                                dari <span class="font-medium">{{ kelas.total }}</span> kelas
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                        <div class="w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0">
                            <Pagination :links="kelas.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showKelasModal" @close="closeModal" :maxWidth="'xl'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3 dark:border-gray-700">
                    {{ isEditMode ? 'Edit Kelas' : 'Tambah Kelas Baru' }}
                </h2>
                <form @submit.prevent="submitKelasForm" class="space-y-6">
                    <div>
                        <InputLabel for="kelas_nama_kelas_modal" value="Nama Kelas" required/>
                        <TextInput id="kelas_nama_kelas_modal" type="text" class="mt-1 block w-full" v-model="form.nama_kelas" required autofocus />
                        <InputError class="mt-2" :message="form.errors.nama_kelas" />
                    </div>
                    <div>
                        <InputLabel for="kelas_deskripsi_modal" value="Deskripsi (Opsional)" />
                        <textarea id="kelas_deskripsi_modal" v-model="form.deskripsi" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"></textarea>
                        <InputError class="mt-2" :message="form.errors.deskripsi" />
                    </div>
                    <div>
                        <InputLabel for="kelas_biaya_pendaftaran_modal" value="Biaya Pendaftaran (Opsional)" />
                        <!-- ### PERBAIKAN: Menggunakan v-model dan @keydown ### -->
                        <TextInput id="kelas_biaya_pendaftaran_modal" type="text" inputmode="numeric" class="mt-1 block w-full" 
                                   v-model="biayaPendaftaranFormatted"
                                   @keydown="onCurrencyKeyDown"
                                   placeholder="Contoh: 1500000" />
                        <InputError class="mt-2" :message="form.errors.biaya_pendaftaran" />
                    </div>
                    <div>
                        <InputLabel for="kelas_biaya_spp_modal" value="Biaya SPP Default (Opsional)" />
                        <!-- ### PERBAIKAN: Menggunakan v-model dan @keydown ### -->
                        <TextInput id="kelas_biaya_spp_modal" type="text" inputmode="numeric" class="mt-1 block w-full" 
                                   v-model="biayaSppFormatted"
                                   @keydown="onCurrencyKeyDown"
                                   placeholder="Contoh: 300000" />
                        <InputError class="mt-2" :message="form.errors.biaya_spp_default" />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditMode ? 'Update Kelas' : 'Simpan Kelas' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Hapus Kelas
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus kelas "<span class="font-semibold">{{ kelasToDelete?.nama_kelas }}</span>"?
                    Aksi ini tidak dapat dibatalkan.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteConfirmModal = false" type="button"> Batal </SecondaryButton>
                    <DangerButton @click="deleteKelas" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Hapus Kelas
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
