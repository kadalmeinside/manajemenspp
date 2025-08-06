<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'; // <-- PERBAIKAN: 'router' ditambahkan di sini
import { ref, computed } from 'vue';
import { PencilIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Toast from '@/Components/Toast.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    pageTitle: String,
    promoList: Object,
    allKelas: Array,
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// --- State untuk Modal ---
const showPromoModal = ref(false);
const isEditMode = ref(false);
const selectedPromo = ref(null);
const showDeleteModal = ref(false);

const form = useForm({
    id_kelas: '',
    nama_promo: '',
    kode_promo: '',
    tipe_diskon: 'tetap',
    nilai_diskon: 0,
    tanggal_mulai: '',
    tanggal_berakhir: null,
    berlaku_selamanya: false,
    is_active: true,
});

// --- Logika Modal ---
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.is_active = true;
    form.tipe_diskon = 'tetap';
    showPromoModal.value = true;
};

const openEditModal = (promo) => {
    isEditMode.value = true;
    selectedPromo.value = promo;
    form.id_kelas = promo.id_kelas;
    form.nama_promo = promo.nama_promo;
    form.kode_promo = promo.kode_promo;
    form.tipe_diskon = promo.tipe_diskon;
    form.nilai_diskon = promo.nilai_diskon;
    form.tanggal_mulai = promo.tanggal_mulai;
    form.tanggal_berakhir = promo.tanggal_berakhir;
    form.berlaku_selamanya = promo.tanggal_berakhir === null;
    form.is_active = promo.is_active;
    showPromoModal.value = true;
};

const closeModal = () => {
    showPromoModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (form.berlaku_selamanya) {
        form.tanggal_berakhir = null;
    }
    if (isEditMode.value) {
        form.put(route('admin.promos.update', selectedPromo.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.promos.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const openDeleteModal = (promo) => {
    selectedPromo.value = promo;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedPromo.value = null;
};

const deletePromo = () => {
    router.delete(route('admin.promos.destroy', selectedPromo.value.id), {
        onSuccess: () => closeDeleteModal(),
    });
};

// --- Helper Functions ---
const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
const formatDate = (dateString) => dateString ? new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ pageTitle }}</h2>
                
            </div>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 pb-0 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex items-center gap-2">
                                <PrimaryButton @click="openCreateModal">
                                    <PlusIcon class="h-5 w-5 mr-2" />
                                    Tambah Promo
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Promo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diskon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode Aktif</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="promoList.data.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada data promo.</td>
                                </tr>
                                <tr v-for="promo in promoList.data" :key="promo.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ promo.nama_promo }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span v-if="promo.kode_promo" class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ promo.kode_promo }}</span>
                                        <span v-else class="text-gray-400 italic">Otomatis</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ promo.kelas.nama_kelas }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span v-if="promo.tipe_diskon === 'persen'">{{ promo.nilai_diskon }}%</span>
                                        <span v-else>{{ formatCurrency(promo.nilai_diskon) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(promo.tanggal_mulai) }} - {{ promo.tanggal_berakhir ? formatDate(promo.tanggal_berakhir) : 'Selamanya' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="promo.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ promo.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button @click="openEditModal(promo)" class="text-indigo-600 hover:text-indigo-900">
                                            <PencilIcon class="h-5 w-5"/>
                                        </button>
                                        <button @click="openDeleteModal(promo)" class="text-red-600 hover:text-red-900">
                                            <TrashIcon class="h-5 w-5"/>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :links="promoList.links" class="p-6" />
                </div>
            </div>
        </div>

        <!-- Modal Create/Edit Promo -->
        <Modal :show="showPromoModal" @close="closeModal" maxWidth="2xl">
            <form @submit.prevent="submitForm" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">
                    {{ isEditMode ? 'Edit Promo' : 'Tambah Promo Baru' }}
                </h2>
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="nama_promo" value="Nama Promo" required />
                            <TextInput id="nama_promo" v-model="form.nama_promo" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.nama_promo" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="kode_promo" value="Kode Promo (Opsional)" />
                            <TextInput id="kode_promo" v-model="form.kode_promo" type="text" class="mt-1 block w-full" placeholder="Biarkan kosong untuk promo otomatis" />
                            <InputError :message="form.errors.kode_promo" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="id_kelas" value="Berlaku untuk Kelas" required />
                            <select id="id_kelas" v-model="form.id_kelas" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                <option value="" disabled>Pilih Kelas</option>
                                <option v-for="kelas in allKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                            </select>
                            <InputError :message="form.errors.id_kelas" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="tipe_diskon" value="Tipe Diskon" required />
                            <select id="tipe_diskon" v-model="form.tipe_diskon" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm">
                                <option value="tetap">Nominal Tetap (Rp)</option>
                                <option value="persen">Persentase (%)</option>
                            </select>
                            <InputError :message="form.errors.tipe_diskon" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="nilai_diskon" value="Nilai Diskon" required />
                            <TextInput id="nilai_diskon" v-model.number="form.nilai_diskon" type="number" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.nilai_diskon" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_mulai" value="Tanggal Mulai" required />
                            <TextInput id="tanggal_mulai" v-model="form.tanggal_mulai" type="date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.tanggal_mulai" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_berakhir" value="Tanggal Berakhir (Opsional)" />
                            <TextInput id="tanggal_berakhir" v-model="form.tanggal_berakhir" type="date" class="mt-1 block w-full" :disabled="form.berlaku_selamanya" />
                            <InputError :message="form.errors.tanggal_berakhir" class="mt-2" />
                        </div>
                        <div class="flex items-center space-x-2">
                            <input id="berlaku_selamanya" v-model="form.berlaku_selamanya" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label for="berlaku_selamanya" class="text-sm text-gray-700 dark:text-gray-300">Berlaku Selamanya</label>
                        </div>
                        <div>
                            <InputLabel value="Status" />
                            <div class="mt-2 flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.is_active" :value="true" class="text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.is_active" :value="false" class="text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tidak Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <SecondaryButton @click="closeModal" type="button">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ isEditMode ? 'Update Promo' : 'Simpan Promo' }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal Konfirmasi Hapus -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" maxWidth="lg">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Hapus Promo</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Anda yakin ingin menghapus promo "{{ selectedPromo?.nama_promo }}"? Tindakan ini tidak dapat diurungkan.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeDeleteModal">Batal</SecondaryButton>
                    <DangerButton @click="deletePromo">Ya, Hapus</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
