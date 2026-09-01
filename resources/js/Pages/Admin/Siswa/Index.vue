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
import { PlusIcon, EyeIcon, PencilSquareIcon, TrashIcon, ArrowUpTrayIcon, ArrowDownTrayIcon, Squares2X2Icon, Bars3Icon, FunnelIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { UserGroupIcon, UserMinusIcon, SparklesIcon, PauseCircleIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';
import Pagination from '@/Components/Pagination.vue';

const page = usePage();

const siswaList = computed(() => page.props.siswaList || { data: [], links: [], current_page: 1, total: 0, per_page: 15, from: 0, to: 0 });
const filters = computed(() => page.props.filters || { search: '', kelas_id: '', status_siswa: '', spp_belum_diset: false });
const allKelas = computed(() => page.props.allKelas || []);
const allStatusSiswa = computed(() => page.props.allStatusSiswa || ['Aktif', 'Non-Aktif', 'Lulus', 'Cuti', 'pending_payment', 'Keluar']);
const can = computed(() => page.props.can || {});
const statistics = computed(() => page.props.statistics || { total_aktif: 0, total_cuti: 0, total_nonaktif: 0, total_baru: 0 });
const analyticsPerClass = computed(() => page.props.analytics_per_class || []);
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const statusSiswaOptions = ['Aktif', 'Non-Aktif', 'Lulus', 'Cuti', 'pending_payment', 'Keluar'];

const showSiswaModal = ref(false);
const showAnalyticsModal = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id_siswa: null,
    nis: '',
    nama_siswa: '',
    tanggal_lahir: '',
    status_siswa: 'Aktif',
    id_kelas: '',
    email_wali: '',
    nomor_telepon_wali: '',
    tanggal_bergabung: new Date().toISOString().slice(0,10),
    mulai_spp_date: '',
    jumlah_spp_custom: null,
    admin_fee_custom: null,
    user_name: '',
    user_password: '',
    user_password_confirmation: '',
});

const searchQuery = ref(filters.value.search || '');
const selectedKelasId = ref(filters.value.kelas_id || '');
const selectedStatusSiswa = ref(filters.value.status_siswa || '');
const sppBelumDiset = ref(filters.value.spp_belum_diset === 'true' || filters.value.spp_belum_diset === true);
const isLoading = ref(false);
const viewMode = ref('grid');
const showMobileFilters = ref(false);

const isMobileSearchSticky = ref(false);
const mobileSearchSentinel = ref(null);

const activeFilterCount = computed(() => {
    let count = 0;
    if (selectedKelasId.value) count++;
    if (selectedStatusSiswa.value) count++;
    if (sppBelumDiset.value) count++;
    return count;
});

const getStatusBadgeClass = (status) => {
    const map = {
        'Aktif': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        'Non-Aktif': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        'Lulus': 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        'Cuti': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        'pending_payment': 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
        'Keluar': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const submitFilters = () => {
    isLoading.value = true;
    router.get(route('admin.siswa.index'), {
        search: searchQuery.value,
        kelas_id: selectedKelasId.value,
        status_siswa: selectedStatusSiswa.value,
        spp_belum_diset: sppBelumDiset.value ? 'true' : null,
        page: 1,
    }, {
        preserveState: true, preserveScroll: true, replace: true,
        only: ['siswaList', 'filters'],
        onFinish: () => { isLoading.value = false; }
    });
};
watch([searchQuery, selectedKelasId, selectedStatusSiswa, sppBelumDiset], debounce(submitFilters, 300));

const setSppDefault = () => {
    if (form.id_kelas) {
        const selectedKelas = allKelas.value.find(k => k.id_kelas === form.id_kelas);
        if (selectedKelas && selectedKelas.biaya_spp_default !== undefined) {
            form.jumlah_spp_custom = Number(selectedKelas.biaya_spp_default);
            form.clearErrors('jumlah_spp_custom');
        } else {
            alert('Kelas yang dipilih tidak memiliki konfigurasi biaya SPP default.');
        }
    } else {
        alert('Silakan pilih kelas terlebih dahulu.');
    }
};

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
    form.nis = data.nis;
    form.id_siswa = data.id_siswa;
    form.nama_siswa = data.nama_siswa;
    form.tanggal_lahir = data.tanggal_lahir;
    form.status_siswa = data.status_siswa;
    form.id_kelas = data.id_kelas;
    form.email_wali = data.user.email;
    form.nomor_telepon_wali = data.nomor_telepon_wali;
    form.tanggal_bergabung = data.tanggal_bergabung;
    form.mulai_spp_date = data.mulai_spp_date || '';
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
    selectedStatusSiswa.value = urlParams.get('status_siswa') || (filters.value ? filters.value.status_siswa : '') || '';
    sppBelumDiset.value = urlParams.get('spp_belum_diset') === 'true' || (filters.value && (filters.value.spp_belum_diset === 'true' || filters.value.spp_belum_diset === true));

    if (mobileSearchSentinel.value) {
        const observer = new IntersectionObserver(
            ([e]) => {
                // If sentinel is not fully visible (scrolled past), it means the sticky element is stuck at top
                isMobileSearchSticky.value = !e.isIntersecting && e.boundingClientRect.top < 0;
            },
            { threshold: [1] }
        );
        observer.observe(mobileSearchSentinel.value);
    }
});
</script>

<template>
    <Head title="Manajemen Siswa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="hidden md:block font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Siswa</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <!-- Stat Cards Section -->
        <div class="pt-0 md:pt-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Statistik Siswa</h3>
                <SecondaryButton @click="showAnalyticsModal = true" class="!px-3 !py-1.5 text-xs">
                    <ChartBarIcon class="w-4 h-4 mr-1.5" />
                    Detail Analitik
                </SecondaryButton>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Aktif -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between group hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Aktif</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_aktif }}</p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl group-hover:scale-110 transition-transform">
                        <UserGroupIcon class="h-6 w-6" />
                    </div>
                </div>
                
                <!-- Baru -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between group hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Baru (Bulan Ini)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_baru }}</p>
                    </div>
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl group-hover:scale-110 transition-transform">
                        <SparklesIcon class="h-6 w-6" />
                    </div>
                </div>

                <!-- Cuti -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between group hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sedang Cuti</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_cuti }}</p>
                    </div>
                    <div class="p-3 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 rounded-xl group-hover:scale-110 transition-transform">
                        <PauseCircleIcon class="h-6 w-6" />
                    </div>
                </div>

                <!-- Nonaktif / Keluar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between group hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nonaktif/Keluar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ statistics.total_nonaktif }}</p>
                    </div>
                    <div class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl group-hover:scale-110 transition-transform">
                        <UserMinusIcon class="h-6 w-6" />
                    </div>
                </div>
            </div>
        </div>

        <div class="pb-12 pt-0 md:pt-4">
            <div class="max-w-full mx-auto px-1 sm:px-0">
                <div ref="mobileSearchSentinel" class="h-px w-full absolute -mt-px pointer-events-none"></div>
                <!-- MOBILE: Search & Info Card (Sticky) -->
                <div 
                    :class="[
                        'sticky top-0 z-10 bg-white dark:bg-gray-800 px-4 pt-4 pb-4 mb-4 border-b border-gray-200 dark:border-gray-700 lg:hidden mt-4 transition-all duration-200',
                        isMobileSearchSticky ? 'rounded-b-2xl -mx-4 rounded-t-none border-t-0 shadow-md' : 'rounded-2xl border border-t mx-0 sm:mx-0 shadow-sm' 
                    ]"
                >
                    <div class="flex gap-2">
                        <TextInput type="text" v-model="searchQuery" placeholder="Cari nama, kelas..." class="w-full bg-gray-50 border-gray-200 dark:bg-gray-900/50 dark:border-gray-700" aria-label="Cari Siswa"/>
                        <SecondaryButton @click="showMobileFilters = true" class="px-3 shrink-0 flex items-center justify-center relative">
                            <FunnelIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                            <span v-if="activeFilterCount > 0" class="absolute top-1 right-1 flex h-3 w-3 items-center justify-center rounded-full bg-indigo-600 text-[8px] font-bold text-white">{{ activeFilterCount }}</span>
                        </SecondaryButton>
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

                <!-- MOBILE: Action Button Card -->
                <div class="lg:hidden mb-4 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg p-4" v-if="can?.create_siswa || can?.import_siswa || can?.export_siswa">
                    <PrimaryButton v-if="can?.create_siswa" @click="openCreateModal" class="w-full flex justify-center py-2.5 mb-2">
                        <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" /> Siswa Baru
                    </PrimaryButton>
                    <div class="flex gap-2" v-if="can?.import_siswa || can?.export_siswa">
                        <button @click="openImportModal" v-if="can?.import_siswa" class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition">
                            <ArrowDownTrayIcon class="-ml-1 mr-1.5 h-4 w-4" aria-hidden="true" /> Impor
                        </button>
                        <a v-if="can?.export_siswa" :href="route('admin.siswa.export')" class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition">
                            <ArrowUpTrayIcon class="-ml-1 mr-1.5 h-4 w-4" aria-hidden="true" /> Ekspor
                        </a>
                    </div>
                </div>

                <!-- DESKTOP: Filters & Tools Card -->
                <div class="hidden lg:block mb-6 p-6 bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                    <div class="space-y-4">
                        <!-- Baris Utama (Search, Toggle View, & Action Buttons) -->
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="w-full sm:w-1/2 md:w-1/3">
                                <TextInput type="text" v-model="searchQuery" placeholder="Cari nama, email wali, kelas..." class="w-full" aria-label="Cari Siswa"/>
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                <!-- Toggle View -->
                                <div class="hidden sm:flex items-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 p-1">
                                    <button @click="viewMode = 'grid'" :class="{'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400': viewMode === 'grid', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': viewMode !== 'grid'}" class="p-1.5 rounded" title="Tampilan Grid">
                                        <Squares2X2Icon class="h-5 w-5" />
                                    </button>
                                    <button @click="viewMode = 'list'" :class="{'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400': viewMode === 'list', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': viewMode !== 'list'}" class="p-1.5 rounded" title="Tampilan List">
                                        <Bars3Icon class="h-5 w-5" />
                                    </button>
                                </div>
                                <PrimaryButton @click="openCreateModal" v-if="can?.create_siswa" class="whitespace-nowrap shadow-sm">
                                    <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" /> Siswa Baru
                                </PrimaryButton>
                            </div>
                        </div>
                        
                        <!-- Baris Kedua (Filters, Checkbox, Import/Export & Total) -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-wrap">
                                <select v-model="selectedKelasId" class="w-full sm:w-auto border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm" aria-label="Filter berdasarkan kelas">
                                    <option value="">Semua Kelas</option>
                                    <option v-for="kelasItem in allKelas" :key="kelasItem.id_kelas" :value="kelasItem.id_kelas">{{ kelasItem.nama_kelas }}</option>
                                </select>
                                <select v-model="selectedStatusSiswa" class="w-full sm:w-auto border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm" aria-label="Filter berdasarkan status">
                                    <option value="">Semua Status</option>
                                    <option v-for="s in allStatusSiswa" :key="s" :value="s">{{ s === 'pending_payment' ? 'Menunggu Pembayaran' : s }}</option>
                                </select>
                                
                                <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer ml-1 whitespace-nowrap">
                                    <input type="checkbox" v-model="sppBelumDiset" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="isLoading" />
                                    <span :class="{'opacity-50': isLoading}">SPP Belum Diset</span>
                                </label>
                            </div>
                            
                            <div class="flex items-center gap-2 justify-end w-full md:w-auto shrink-0 flex-wrap">
                                <div v-if="isLoading" class="flex items-center text-xs text-indigo-600 dark:text-indigo-400 font-medium mr-2">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memuat Data...
                                </div>
                                <p v-else class="text-xs text-gray-500 dark:text-gray-400 mr-2 whitespace-nowrap">
                                    <span v-if="siswaList.total > 0">Total: <span class="font-semibold">{{ siswaList.total }}</span> siswa</span>
                                </p>
                                <button @click="openImportModal" v-if="can?.import_siswa" class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition whitespace-nowrap">
                                    <ArrowDownTrayIcon class="-ml-1 mr-1.5 h-4 w-4" aria-hidden="true" /> Impor
                                </button>
                                <a v-if="can?.export_siswa" :href="route('admin.siswa.export')" class="inline-flex items-center justify-center px-3 py-2 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition whitespace-nowrap">
                                    <ArrowUpTrayIcon class="-ml-1 mr-1.5 h-4 w-4" aria-hidden="true" /> Ekspor
                                </a>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email Wali (Login)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!siswaList || !siswaList.data || siswaList.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data siswa.</td>
                                </tr>
                                <tr v-else v-for="item in siswaList.data" :key="item.id_siswa">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ item.nama_siswa }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ item.kelas_nama }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ item.email_wali }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                         <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusBadgeClass(item.status_siswa)]">
                                            {{ item.status_siswa === 'pending_payment' ? 'Menunggu Pembayaran' : item.status_siswa }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Link :href="route('admin.siswa.show', item.id_siswa)" class="text-gray-400 hover:text-indigo-600 p-1" title="Lihat Detail">
                                                <EyeIcon class="h-5 w-5" />
                                            </Link>
                                            <button @click="openEditModal(item)" class="text-gray-400 hover:text-indigo-600 p-1" v-if="can?.edit_siswa" title="Edit Siswa"><PencilSquareIcon class="h-5 w-5" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>

                        <!-- Tampilan Grid (Card) -->
                        <div :class="{'sm:hidden': viewMode === 'list', 'grid': true}" class="grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-4 px-1 sm:px-0">
                            <div v-if="!siswaList || !siswaList.data || siswaList.data.length === 0" class="text-center text-sm text-gray-500 py-4 col-span-full">
                                Tidak ada data siswa.
                            </div>
                            <div v-else v-for="item in siswaList.data" :key="'mobile-'+item.id_siswa" class="bg-white dark:bg-gray-800 sm:dark:bg-gray-700 border border-gray-200 dark:border-gray-700 sm:dark:border-gray-600 rounded-lg p-4 shadow-sm flex flex-col gap-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white">{{ item.nama_siswa }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">{{ item.kelas_nama }}</p>
                                    </div>
                                    <span :class="['px-2 py-1 inline-flex text-[10px] leading-tight font-semibold rounded-full', getStatusBadgeClass(item.status_siswa)]">
                                        {{ item.status_siswa === 'pending_payment' ? 'Menunggu Pembayaran' : item.status_siswa }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p><span class="font-medium">Wali:</span> {{ item.email_wali }}</p>
                                    <p><span class="font-medium">Gabung:</span> {{ item.tanggal_bergabung_formatted }}</p>
                                </div>
                                <div class="mt-2 pt-3 border-t border-gray-100 dark:border-gray-700 sm:dark:border-gray-600 flex justify-end gap-2">
                                    <Link :href="route('admin.siswa.show', item.id_siswa)" class="p-2 bg-gray-100 dark:bg-gray-700 sm:dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-500" title="Lihat Detail">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <button @click="openEditModal(item)" v-if="can?.edit_siswa" class="p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/60" title="Edit Siswa">
                                        <PencilSquareIcon class="h-4 w-4" />
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
                            <InputLabel for="nis_modal" value="Nomor Induk Siswa (NIS)" />
                            <TextInput
                                id="nis_modal"
                                type="text"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-900"
                                v-model="form.nis"
                                :placeholder="isEditMode ? '' : 'Akan digenerate otomatis'"
                                readonly
                            />
                            <InputError class="mt-2" :message="form.errors.nis" />
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
                            <InputLabel for="mulai_spp_date_edit_modal" value="Bulan Mulai SPP (Opsional)" />
                            <TextInput id="mulai_spp_date_edit_modal" type="month" class="mt-1 block w-full" v-model="form.mulai_spp_date" @input="form.clearErrors('mulai_spp_date')" placeholder="Kosongkan jika belum ada" />
                            <InputError class="mt-2" :message="form.errors.mulai_spp_date" />
                        </div>
                        <div>
                            <InputLabel for="jumlah_spp_custom_modal" value="SPP Custom (Wajib)" required />
                            <div class="flex items-center space-x-2 mt-1">
                                <TextInput id="jumlah_spp_custom_modal" type="number" class="block w-full" v-model.number="form.jumlah_spp_custom" @input="form.clearErrors('jumlah_spp_custom')" required />
                                <SecondaryButton @click="setSppDefault" type="button" class="whitespace-nowrap px-3 py-2 text-xs" :disabled="!form.id_kelas" title="Ambil nilai SPP default dari Kelas">Ambil Default</SecondaryButton>
                            </div>
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

        <!-- Mobile Filter Modal -->
        <Modal :show="showMobileFilters" @close="showMobileFilters = false" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-3 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Filter Pencarian</h2>
                    <button @click="showMobileFilters = false" class="text-gray-400 hover:text-gray-500">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Kelas" class="mb-1" />
                        <select v-model="selectedKelasId" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm">
                            <option value="">Semua Kelas</option>
                            <option v-for="kelasItem in allKelas" :key="kelasItem.id_kelas" :value="kelasItem.id_kelas">{{ kelasItem.nama_kelas }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Status Siswa" class="mb-1" />
                        <select v-model="selectedStatusSiswa" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm">
                            <option value="">Semua Status</option>
                            <option v-for="s in allStatusSiswa" :key="s" :value="s">{{ s === 'pending_payment' ? 'Menunggu Pembayaran' : s }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" v-model="sppBelumDiset" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="isLoading" />
                            <span :class="{'opacity-50': isLoading}">Tampilkan hanya yang SPP-nya belum diset</span>
                        </label>
                    </div>
                    <div class="pt-4 border-t dark:border-gray-700">
                        <PrimaryButton class="w-full justify-center" @click="showMobileFilters = false">
                            Terapkan Filter
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <Modal :show="showAnalyticsModal" @close="showAnalyticsModal = false" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <ChartBarIcon class="w-6 h-6 mr-2 text-indigo-600 dark:text-indigo-400" />
                        Analitik Siswa Per Cabang/Kelas
                    </h2>
                    <button @click="showAnalyticsModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Cabang / Kelas</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Siswa Aktif</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Baru (Bulan Ini)</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Sedang Cuti</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">Nonaktif / Keluar</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-for="c in analyticsPerClass" :key="c.id_kelas" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ c.nama_kelas }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        {{ c.aktif }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ c.baru }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">
                                        {{ c.cuti }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                        {{ c.nonaktif }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="analyticsPerClass.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data kelas yang tersedia.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <PrimaryButton @click="showAnalyticsModal = false">Tutup</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>