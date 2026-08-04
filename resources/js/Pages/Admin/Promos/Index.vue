<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';
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
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');

watch(searchQuery, debounce((value) => {
    router.get(route('admin.promos.index'), { search: value }, { preserveState: true, preserveScroll: true, replace: true });
}, 300));

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
            <h2 class="hidden md:block font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ pageTitle }}</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-full mx-auto px-1 sm:px-0">
                <!-- MOBILE: Search & Info Card (Sticky) -->
                <div class="sticky -top-4 z-10 bg-white dark:bg-gray-800 -mx-4 -mt-8 px-4 pt-4 pb-4 mb-4 border-b border-t-0 border-gray-200 dark:border-gray-700 shadow-sm lg:hidden rounded-b-2xl">
                    <div class="flex gap-2">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari nama atau kode promo..." class="w-full bg-gray-50 border-gray-200 dark:bg-gray-900/50 dark:border-gray-700" aria-label="Cari promo"/>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            <span v-if="promoList.total > 0">
                                Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-300">{{ promoList.from }}–{{ promoList.to }}</span>
                                dari <span class="font-semibold text-gray-700 dark:text-gray-300">{{ promoList.total }}</span> promo
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                    </div>
                </div>

                <!-- MOBILE: Action Button Card -->
                <div class="lg:hidden mb-4 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <PrimaryButton @click="openCreateModal" class="w-full flex justify-center py-2.5">
                        <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" /> Tambah Promo Baru
                    </PrimaryButton>
                </div>

                <!-- DESKTOP: Tools Card -->
                <div class="hidden lg:block mb-6 p-6 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari nama atau kode promo..." class="w-full md:max-w-sm" aria-label="Cari promo"/>
                        <PrimaryButton @click="openCreateModal" class="w-full md:w-auto">
                            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Tambah Promo Baru
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Promo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Diskon</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode Aktif</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
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
                                            <div class="flex justify-end gap-3">
                                                <button @click="openEditModal(promo)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 p-2 rounded-md transition-colors" title="Edit Promo">
                                                    <PencilIcon class="h-4 w-4"/>
                                                </button>
                                                <button @click="openDeleteModal(promo)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 p-2 rounded-md transition-colors" title="Hapus Promo">
                                                    <TrashIcon class="h-4 w-4"/>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Mobile (Card) -->
                        <div class="block md:hidden space-y-4 mt-2">
                            <div v-if="promoList.data.length === 0" class="text-center text-sm text-gray-500 py-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                Belum ada data promo.
                            </div>
                            <div v-else v-for="promo in promoList.data" :key="'mob-'+promo.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col gap-2">
                                <div class="flex justify-between items-start border-b border-gray-100 dark:border-gray-700 pb-2">
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white leading-tight">{{ promo.nama_promo }}</h3>
                                        <div class="mt-1">
                                            <span v-if="promo.kode_promo" class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ promo.kode_promo }}</span>
                                            <span v-else class="text-xs text-gray-400 italic">Otomatis</span>
                                        </div>
                                    </div>
                                    <span :class="['px-2 py-1 inline-flex text-[10px] leading-tight font-semibold rounded-full', promo.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                        {{ promo.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm mt-1">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Diskon</p>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            <span v-if="promo.tipe_diskon === 'persen'">{{ promo.nilai_diskon }}%</span>
                                            <span v-else>{{ formatCurrency(promo.nilai_diskon) }}</span>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Kelas</p>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ promo.kelas.nama_kelas }}</p>
                                    </div>
                                    <div class="col-span-2 mt-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Periode</p>
                                        <p class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatDate(promo.tanggal_mulai) }} - {{ promo.tanggal_berakhir ? formatDate(promo.tanggal_berakhir) : 'Selamanya' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="pt-3 mt-1 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2">
                                    <button @click="openEditModal(promo)" class="p-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-md hover:bg-indigo-100" title="Edit Promo">
                                        <PencilIcon class="h-4 w-4" />
                                    </button>
                                    <button @click="openDeleteModal(promo)" class="p-1.5 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-md hover:bg-red-100" title="Hapus Promo">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paginasi -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center sm:rounded-b-lg rounded-lg sm:mt-0 mt-2 shadow-sm sm:shadow-none gap-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                            <span v-if="promoList.total > 0">
                                Menampilkan <span class="font-medium">{{ promoList.from }}</span>–<span class="font-medium">{{ promoList.to }}</span>
                                dari <span class="font-medium">{{ promoList.total }}</span> promo
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                        <Pagination :links="promoList.links" />
                    </div>
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
