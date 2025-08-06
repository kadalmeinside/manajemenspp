<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Atau AdminLayout.vue jika Anda sudah membuatnya
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
// Jika Anda membuat TextareaInput.vue, import di sini. Jika tidak, hapus impor ini.
// import TextareaInput from '@/Components/TextareaInput.vue';
import Toast from '@/Components/Toast.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

// Menggunakan computed untuk memastikan reaktivitas props
// dan memberikan nilai default yang aman jika props tidak ada saat awal render.
const kelas = computed(() => page.props.kelas); // Default ke objek dengan data & links kosong
const filters = computed(() => page.props.filters || {});
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// State untuk Modal
const showKelasModal = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id_kelas: null,
    nama_kelas: '',
    deskripsi: '',
    biaya_spp_default: null,
});

// Search
const searchQuery = ref(filters.value.search || '');

const submitFilters = () => {
    router.get(route('admin.kelas.index'), {
        search: searchQuery.value,
        page: 1, // Selalu kembali ke halaman 1 saat search baru
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['kelas', 'filters'], // Hanya minta props yang relevan
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
        onSuccess: () => {
            closeModal();
            //router.reload({ preserveScroll: true, only: ['kelas', 'flash'] });
        },
        onError: (errors) => {
            console.error('Kelas form submission error:', errors);
        }
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
                // Logika untuk pindah halaman jika item terakhir dihapus (opsional)
                const currentKelasData = kelas.value.data;
                const currentPage = kelas.value.current_page;
                if (currentKelasData.length === 1 && currentPage > 1 && kelasToDelete.value.id_kelas === currentKelasData[0].id_kelas) {
                     router.get(route('admin.kelas.index'), {
                        search: searchQuery.value,
                        page: currentPage - 1,
                    }, {
                        preserveState: false,
                        preserveScroll: true,
                        replace: true,
                        only: ['kelas', 'flash', 'filters'],
                    });
                } else {
                    router.reload({ preserveScroll: true, only: ['kelas', 'flash'] });
                }
                kelasToDelete.value = null;
                showDeleteConfirmModal.value = false;
            },
            onError: (errors) => {
                console.error('Delete kelas error:', errors);
                kelasToDelete.value = null;
                showDeleteConfirmModal.value = false;
            }
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
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
};

</script>

<template>
    <Head title="Manajemen Kelas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Kelas</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 pb-0 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex flex-col sm:flex-row items-center gap-3 flex-grow">
                                <TextInput type="text" v-model="searchQuery" placeholder="Cari nama atau deskripsi kelas..." class="w-full md:max-w-sm" aria-label="Cari kelas"/>
                            </div>
                            <PrimaryButton @click="openCreateModal" v-if="can?.create_kelas" class="w-full md:w-auto">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Tambah Kelas Baru
                            </PrimaryButton>
                        </div>
                    </div>

                    <div class="px-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Biaya SPP Default</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!kelas || !kelas.data || kelas.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data kelas.</td>
                                </tr>
                                <tr v-else v-for="kelas in kelas.data" :key="kelas.id_kelas">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ kelas.nama_kelas }}</td>
                                    <td class="px-6 py-4 whitespace-normal text-sm text-gray-500 dark:text-gray-300 break-words max-w-md">{{ kelas.deskripsi || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ formatCurrency(kelas.biaya_spp_default) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(kelas)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 p-1 mr-2" v-if="can?.edit_kelas" title="Edit Kelas">
                                            <PencilIcon class="h-5 w-5" />
                                        </button>
                                        <button @click="confirmDeleteKelas(kelas)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-1" v-if="can?.delete_kelas" title="Hapus Kelas">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="kelas && kelas.links && kelas.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap -mb-1 justify-center">
                            <template v-for="(link, key) in kelas.links" :key="key">
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

        <Modal :show="showKelasModal" @close="closeModal" :maxWidth="'xl'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3 dark:border-gray-700">
                    {{ isEditMode ? 'Edit Kelas' : 'Tambah Kelas Baru' }}
                </h2>
                <form @submit.prevent="submitKelasForm" class="space-y-6">
                    <div>
                        <InputLabel for="kelas_nama_kelas_modal" value="Nama Kelas" />
                        <TextInput id="kelas_nama_kelas_modal" type="text" class="mt-1 block w-full" v-model="form.nama_kelas" required autofocus />
                        <InputError class="mt-2" :message="form.errors.nama_kelas" />
                    </div>
                    <div>
                        <InputLabel for="kelas_deskripsi_modal" value="Deskripsi (Opsional)" />
                        <textarea id="kelas_deskripsi_modal" v-model="form.deskripsi" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"></textarea>
                        <InputError class="mt-2" :message="form.errors.deskripsi" />
                    </div>
                    <div>
                        <InputLabel for="kelas_biaya_spp_modal" value="Biaya SPP Default (Opsional)" />
                        <TextInput id="kelas_biaya_spp_modal" type="number" step="1000" class="mt-1 block w-full" v-model="form.biaya_spp_default" placeholder="Contoh: 300000" />
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
                    <DangerButton @click="deleteKelas" :class="{ 'opacity-25': router.processing || form.processing }" :disabled="router.processing || form.processing">
                        Hapus Kelas
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>