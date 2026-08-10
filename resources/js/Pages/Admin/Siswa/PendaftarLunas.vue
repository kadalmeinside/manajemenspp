<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { EyeIcon, PencilSquareIcon, Squares2X2Icon, Bars3Icon, FunnelIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';
import Pagination from '@/Components/Pagination.vue';

const page = usePage();

const siswaList = computed(() => page.props.siswaList || { data: [], links: [], current_page: 1, total: 0, per_page: 15, from: 0, to: 0 });
const filters = computed(() => page.props.filters || { search: '' });
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const activeTab = ref(filters.value.tab || 'menunggu');
const searchQuery = ref(filters.value.search || '');
const isLoading = ref(false);
const viewMode = ref('grid');

const getStatusBadgeClass = (status) => {
    const map = {
        'Aktif': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        'Non-Aktif': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
    };
    return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const submitFilters = () => {
    isLoading.value = true;
    router.get(route('admin.siswa.pendaftar_lunas'), {
        search: searchQuery.value,
        tab: activeTab.value,
        page: 1,
    }, {
        preserveState: true, preserveScroll: true, replace: true,
        only: ['siswaList', 'filters'],
        onFinish: () => { isLoading.value = false; }
    });
};
watch([searchQuery, activeTab], debounce(submitFilters, 300));

// State untuk Modal Mulai SPP
const showMulaiSppModal = ref(false);
const mulaiSppForm = useForm({
    id_siswa: null,
    mulai_spp_date: '',
});

const openMulaiSppModal = (siswa) => {
    mulaiSppForm.reset();
    mulaiSppForm.clearErrors();
    mulaiSppForm.id_siswa = siswa.id_siswa;
    mulaiSppForm.mulai_spp_date = new Date().toISOString().slice(0, 7);
    showMulaiSppModal.value = true;
};

const closeMulaiSppModal = () => {
    showMulaiSppModal.value = false;
};

const submitMulaiSpp = () => {
    mulaiSppForm.post(route('admin.siswa.set_mulai_spp', mulaiSppForm.id_siswa), {
        onSuccess: () => {
            closeMulaiSppModal();
        },
    });
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || (filters.value ? filters.value.search : '') || '';
    activeTab.value = urlParams.get('tab') || (filters.value ? filters.value.tab : 'menunggu') || 'menunggu';
});
</script>

<template>
    <Head title="Aktivasi Jadwal SPP (Pendaftar Lunas)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="hidden md:block font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">Aktivasi Jadwal SPP (Pendaftar Baru)</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-full mx-auto px-1 sm:px-0">
                <!-- MOBILE: Search & Info Card (Sticky) -->
                <div class="sticky -top-4 z-10 bg-white dark:bg-gray-800 -mx-4 -mt-8 px-4 pt-4 pb-4 mb-4 border-b border-t-0 border-gray-200 dark:border-gray-700 shadow-sm lg:hidden rounded-b-2xl">
                    <div class="flex gap-2">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari nama, email..." class="w-full bg-gray-50 border-gray-200 dark:bg-gray-900/50 dark:border-gray-700" aria-label="Cari Siswa"/>
                    </div>
                    <div class="flex mt-3 border-b border-gray-200 dark:border-gray-700">
                        <button @click="activeTab = 'menunggu'" :class="['w-1/2 py-2 text-sm font-medium border-b-2', activeTab === 'menunggu' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700']">
                            Menunggu
                        </button>
                        <button @click="activeTab = 'riwayat'" :class="['w-1/2 py-2 text-sm font-medium border-b-2', activeTab === 'riwayat' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700']">
                            Riwayat
                        </button>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            <span v-if="siswaList.total > 0">
                                Menampilkan <span class="font-semibold text-gray-700 dark:text-gray-300">{{ siswaList.from }}–{{ siswaList.to }}</span>
                                dari <span class="font-semibold text-gray-700 dark:text-gray-300">{{ siswaList.total }}</span> siswa
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                    </div>
                </div>

                <!-- DESKTOP: Filters & Tools Card -->
                <div class="hidden lg:block mb-6 p-6 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="w-full sm:w-1/2 md:w-1/3">
                                <TextInput type="text" v-model="searchQuery" placeholder="Cari pendaftar..." class="w-full md:w-64 bg-gray-50 border-gray-200 dark:bg-gray-900/50 dark:border-gray-700" aria-label="Cari Siswa"/>
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                <div class="flex border-b border-gray-200 dark:border-gray-700 ml-4 hidden lg:flex">
                                    <button @click="activeTab = 'menunggu'" :class="['px-4 py-4 text-sm font-medium border-b-2', activeTab === 'menunggu' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700']">
                                        Menunggu Aktivasi
                                    </button>
                                    <button @click="activeTab = 'riwayat'" :class="['px-4 py-4 text-sm font-medium border-b-2', activeTab === 'riwayat' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700']">
                                        Riwayat Aktivasi
                                    </button>
                                </div>
                                <!-- Toggle View -->
                                <div class="hidden sm:flex items-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 p-1">
                                    <button @click="viewMode = 'grid'" :class="{'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400': viewMode === 'grid', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': viewMode !== 'grid'}" class="p-1.5 rounded" title="Tampilan Grid">
                                        <Squares2X2Icon class="h-5 w-5" />
                                    </button>
                                    <button @click="viewMode = 'list'" :class="{'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400': viewMode === 'list', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': viewMode !== 'list'}" class="p-1.5 rounded" title="Tampilan List">
                                        <Bars3Icon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-300">Daftar siswa baru yang telah membayar registrasi lunas, namun jadwal SPP-nya belum diaktifkan/ditentukan.</p>
                            <div class="flex items-center gap-2 justify-end w-full md:w-auto shrink-0 flex-wrap">
                                <div v-if="isLoading" class="flex items-center text-xs text-indigo-600 dark:text-indigo-400 font-medium mr-2">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962A7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memuat Data...
                                </div>
                                <p v-else class="text-xs text-gray-500 dark:text-gray-400 mr-2 whitespace-nowrap">
                                    <span v-if="siswaList.total > 0">Total: <span class="font-semibold">{{ siswaList.total }}</span> siswa</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-transparent sm:bg-white/80 sm:dark:bg-gray-800/80 sm:shadow-sm sm:rounded-lg">

                    <div class="px-0 sm:px-6 pb-4 overflow-x-auto">
                        <!-- Tampilan List (Table) -->
                        <div v-if="viewMode === 'list'" class="hidden sm:block">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email Wali</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Tgl Pendaftaran
                                    </th>
                                    <th v-if="activeTab === 'riwayat'" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Mulai SPP
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!siswaList || !siswaList.data || siswaList.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada pendaftar baru yang menunggu jadwal SPP.</td>
                                </tr>
                                <tr v-else v-for="siswa in siswaList.data" :key="siswa.id_siswa">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ siswa.kelas_nama }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ siswa.email_wali }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ siswa.tanggal_bergabung_formatted || '-' }}
                                    </td>
                                    <td v-if="activeTab === 'riwayat'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ siswa.mulai_spp_date_formatted || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <PrimaryButton v-if="activeTab === 'menunggu'" @click="openMulaiSppModal(siswa)" class="bg-indigo-600 hover:bg-indigo-700 py-1.5 px-3">
                                            Set Jadwal SPP
                                        </PrimaryButton>
                                        <span v-else class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Sudah Diaktifkan
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Grid (Card) -->
                        <div :class="{'sm:hidden': viewMode === 'list', 'grid': true}" class="grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-4 px-1 sm:px-0">
                            <div v-if="!siswaList || !siswaList.data || siswaList.data.length === 0" class="text-center text-sm text-gray-500 py-4 col-span-full">
                                Tidak ada pendaftar baru yang menunggu jadwal SPP.
                            </div>
                            <div v-else v-for="item in siswaList.data" :key="'mobile-'+item.id_siswa" class="bg-white dark:bg-gray-800 sm:dark:bg-gray-700 border border-gray-200 dark:border-gray-700 sm:dark:border-gray-600 rounded-lg p-4 shadow-sm flex flex-col gap-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white">{{ item.nama_siswa }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">{{ item.kelas_nama }}</p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    <p><span class="font-medium">Wali:</span> {{ item.email_wali }}</p>
                                    <p><span class="font-medium">Bergabung:</span> {{ item.tanggal_bergabung_formatted }}</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 sm:dark:border-gray-600 flex justify-end gap-2">
                                    <button @click="openMulaiSppModal(item)" v-if="can?.edit_siswa" class="w-full justify-center flex items-center px-3 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 font-medium shadow-sm transition">
                                        Tentukan Jadwal SPP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paginasi -->
                    <div class="px-0 sm:px-6 py-4 bg-transparent sm:bg-gray-50 sm:dark:bg-gray-700/50 sm:border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center sm:rounded-b-lg rounded-b-none sm:mt-0 mt-2 gap-4">
                        <p class="hidden sm:block text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                            <span v-if="siswaList.total > 0">
                                Menampilkan <span class="font-medium">{{ siswaList.from }}</span>–<span class="font-medium">{{ siswaList.to }}</span>
                                dari <span class="font-medium">{{ siswaList.total }}</span> siswa
                            </span>
                            <span v-else>Tidak ada data yang cocok</span>
                        </p>
                        <div class="w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0">
                            <Pagination :links="siswaList.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tentukan Jadwal SPP Pertama -->
        <Modal :show="showMulaiSppModal" @close="closeMulaiSppModal" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3">
                    Tentukan Jadwal Mulai SPP
                </h2>
                <form @submit.prevent="submitMulaiSpp">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Tentukan mulai bulan apa tagihan SPP bulanan otomatis akan dibuat untuk siswa ini.
                    </p>
                    <div>
                        <InputLabel for="mulai_spp_date" value="Bulan & Tahun Mulai" required />
                        <TextInput 
                            id="mulai_spp_date" 
                            type="month" 
                            class="mt-1 block w-full" 
                            v-model="mulaiSppForm.mulai_spp_date" 
                            required 
                        />
                        <InputError class="mt-2" :message="mulaiSppForm.errors.mulai_spp_date" />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="closeMulaiSppModal" type="button" :disabled="mulaiSppForm.processing">Batal</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': mulaiSppForm.processing }" :disabled="mulaiSppForm.processing">
                            <span v-if="!mulaiSppForm.processing">Simpan</span>
                            <span v-else>Menyimpan...</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
