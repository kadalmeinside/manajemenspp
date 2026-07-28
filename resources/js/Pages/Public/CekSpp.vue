<script setup>
// Force rebuild for cache busting
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { CreditCardIcon, ArrowPathIcon, UserIcon, CheckCircleIcon, BanknotesIcon, CalendarIcon, DocumentTextIcon, ClockIcon } from '@heroicons/vue/24/outline';
import { XCircleIcon } from '@heroicons/vue/20/solid';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    pageTitle: String,
    errors: Object,
    foundSiswa: Array,
    selectedSiswa: Object,
    sppInvoices: Array,
    historyInvoices: Array,   // riwayat: PAID, EXPIRED, FAILED
    paidMonths: Array,        // ['2025-1', '2025-2', ...]
    pendingLeaveMonths: Array,
    lastPeriod: String,
    searchedPhone: String,
});

const page = usePage();
const appLogo = computed(() => {
    if (page.props.app_settings?.app_logo_cek_spp) return `/storage/${page.props.app_settings.app_logo_cek_spp}`;
    if (page.props.app_settings?.app_logo) return `/storage/${page.props.app_settings.app_logo}`;
    return null;
});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');
const showSuccessCard = computed(() => flashMessage.value && flashType.value === 'success');

// --- State & Forms ---
const isSearching = ref(false);
const isRedirecting = ref(false);

const lookupForm = useForm({
    nomor_telepon_wali: props.searchedPhone || '',
});

const createUserForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const selectedPeriods = ref([]);
const paymentForm = useForm({ periods: [] });

// --- State Cuti ---
const showLeaveModal = ref(false);
const leaveForm = useForm({
    id_siswa: '',
    months: [new Date().getMonth() + 1],
    year: String(new Date().getFullYear()),
    reason: '',
});

// --- Intercept: Pembaruan Syarat & Ketentuan ---
const missingAgreements = computed(() => page.props.missing_agreement);
const isMissingAgreement = computed(() => !!missingAgreements.value);

const agreementForm = useForm({
    legal_document_id: missingAgreements.value?.document?.id || null,
    id_siswa: missingAgreements.value?.siswa?.map(s => s.id_siswa) || [],
    terms_accepted: false,
});

watch(missingAgreements, (newVal) => {
    if (newVal) {
        agreementForm.legal_document_id = newVal.document?.id || null;
        agreementForm.id_siswa = newVal.siswa?.map(s => s.id_siswa) || [];
    }
}, { immediate: true });

const submitAgreement = () => {
    if (!agreementForm.terms_accepted) return;
    agreementForm.post(route('tagihan.spp.agreements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            agreementForm.reset();
        },
    });
};

const selectSiswa = (siswa) => {
    isRedirecting.value = true;
    router.get(route('tagihan.spp.show', siswa.id_siswa), {}, {
        onFinish: () => isRedirecting.value = false
    });
};

const openLeaveModal = () => {
    leaveForm.id_siswa = props.selectedSiswa.id_siswa;
    leaveForm.year = String(new Date().getFullYear());
    const currentMonth = new Date().getMonth() + 1;
    // reset selections when opening, check if current month is disabled
    leaveForm.months = isMonthDisabled(currentMonth) ? [] : [currentMonth];
    showLeaveModal.value = true;
};

const toggleMonth = (m) => {
    if (isMonthDisabled(m)) return;
    const index = leaveForm.months.indexOf(m);
    if (index > -1) {
        leaveForm.months.splice(index, 1);
    } else {
        leaveForm.months.push(m);
    }
};

const isMonthDisabled = (month) => {
    const year = leaveForm.year;
    if (!year) return false;
    const key = `${year}-${month}`;
    return (props.paidMonths || []).includes(key) || (props.pendingLeaveMonths || []).includes(key);
};

const submitLeave = () => {
    leaveForm.post(route('student-leaves.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showLeaveModal.value = false;
            leaveForm.reset();
            // Optional: Show success toast handled by flash message
        },
    });
};

// --- Logika Halaman Tagihan ---
const displayList = computed(() => {
    if (!props.selectedSiswa) return [];

    const existingInvoices = (props.sppInvoices || []).map(inv => ({ ...inv, is_projected: false }));
    const projectedInvoices = [];
    
    let startProjectionDate;
    
    if (props.lastPeriod) {
        const parts = props.lastPeriod.split('-').map(Number);
        const lastDate = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
        startProjectionDate = new Date(Date.UTC(lastDate.getUTCFullYear(), lastDate.getUTCMonth() + 1, 1));
    } else {
        const today = new Date();
        startProjectionDate = new Date(Date.UTC(today.getFullYear(), today.getMonth(), 1));
    }
    
    let currentPeriod = startProjectionDate;
    const endOfYear = new Date(Date.UTC(currentPeriod.getUTCFullYear(), 11, 31));

    while (currentPeriod <= endOfYear) {
        const year = currentPeriod.getUTCFullYear();
        const month = String(currentPeriod.getUTCMonth() + 1).padStart(2, '0');
        const day = '01';
        const periodString = `${year}-${month}-${day}`;
        const monthName = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(currentPeriod);
        const totalAmount = (props.selectedSiswa.jumlah_spp_custom || 0);
        projectedInvoices.push({
            id: `proj-${periodString}`,
            description: `SPP Bulan ${monthName}`,
            total_amount: totalAmount,
            total_amount_formatted: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalAmount),
            status: 'PROJECTED',
            periode_tagihan: periodString,
            is_projected: true,
        });
        currentPeriod.setUTCMonth(currentPeriod.getUTCMonth() + 1);
    }
    return [...existingInvoices, ...projectedInvoices];
});

const totalSelectedAmount = computed(() => {
    if (selectedPeriods.value.length === 0) return 0;
    const totalSpp = displayList.value.filter(item => selectedPeriods.value.includes(item.periode_tagihan)).reduce((total, item) => total + item.total_amount, 0);
    const adminFee = props.selectedSiswa.admin_fee_custom || 0;
    return totalSpp + adminFee;
});

const totalSelectedAmountFormatted = computed(() => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalSelectedAmount.value));

const submitLookup = () => {
    isSearching.value = true;
    lookupForm.post(route('tagihan.spp.find'), {
        onFinish: () => isSearching.value = false,
    });
};

const submitCreateUser = () => {
    createUserForm.post(route('tagihan.spp.create_user', props.selectedSiswa.id_siswa), {
        preserveScroll: true,
        onError: () => {
            if (!createUserForm.name) createUserForm.setError('name', 'Nama akun wajib diisi.');
            if (!createUserForm.email) createUserForm.setError('email', 'Email wajib diisi.');
            if (!createUserForm.password) createUserForm.setError('password', 'Password wajib diisi.');
        }
    });
};

const updateSelection = (item, isChecked) => {
    const clickedIndex = displayList.value.findIndex(i => i.id === item.id);
    if (isChecked) {
        if (clickedIndex === 0 || selectedPeriods.value.includes(displayList.value[clickedIndex - 1].periode_tagihan)) {
            // ### PERBAIKAN: Mencegah duplikat ###
            if (!selectedPeriods.value.includes(item.periode_tagihan)) {
                selectedPeriods.value.push(item.periode_tagihan);
            }
        }
    } else {
        const periodsToRemove = displayList.value.slice(clickedIndex).map(i => i.periode_tagihan);
        selectedPeriods.value = selectedPeriods.value.filter(p => !periodsToRemove.includes(p));
    }
};

const isItemDisabled = (index) => {
    if (index === 0) return false; 
    return !selectedPeriods.value.includes(displayList.value[index - 1].periode_tagihan);
};

const submitPayment = () => {
    isRedirecting.value = true;
    paymentForm.periods = selectedPeriods.value.sort();
    paymentForm.post(route('tagihan.spp.pay', props.selectedSiswa.id_siswa)); 
};

// --- State tab di halaman tagihan utama ---
const activeTab = ref('tagihan'); // 'tagihan' | 'riwayat'

const getHistoryStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
    return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

const formatPeriod = (dateStr) => {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    const date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2] || 1));
    return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(date);
};
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center p-4 sm:p-6">
        <!-- Background Image -->
        <div class="fixed inset-0 z-0">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/images/bg_registration.webp');"></div>
            <div class="absolute inset-0 bg-gray-100/90 dark:bg-gray-900/95 backdrop-blur-md"></div>
        </div>

        <!-- Redirecting Overlay -->
        <div v-if="isRedirecting" class="fixed inset-0 bg-black/70 flex flex-col items-center justify-center z-50">
            <ArrowPathIcon class="h-12 w-12 text-white animate-spin" />
            <p class="text-white mt-4 text-lg">Mengarahkan ke halaman pembayaran...</p>
        </div>

        <main class="w-full mx-auto z-10 flex-grow flex flex-col transition-all duration-700" :class="selectedSiswa ? 'max-w-6xl pb-48 sm:pb-32' : 'max-w-4xl overflow-y-auto'">
            <header class="text-center mb-8 flex-shrink-0 pt-6 transition-all duration-700">
                <Link href="/" class="inline-block">
                    <img v-if="appLogo" :src="appLogo" alt="App Logo" class="h-12 w-auto mx-auto drop-shadow-sm">
                    <ApplicationLogo v-else class="h-12 w-auto mx-auto text-gray-900 dark:text-white" />
                </Link>
                <div v-if="!selectedSiswa" class="mt-4">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Persija Development</h1>
                    <h2 class="text-lg text-gray-600 dark:text-gray-400">{{ pageTitle }}</h2>
                </div>
            </header>

            <!-- ============================== -->
            <!-- STATE 1 & 2: PENCARIAN & PILIH -->
            <!-- ============================== -->
            <div v-if="!selectedSiswa" class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-xl border border-gray-200 dark:border-gray-700 rounded-3xl p-6 md:p-8 relative max-w-xl mx-auto w-full">
                
                <!-- Tampilan 1: Form Pencarian -->
                <div v-if="!foundSiswa && !selectedSiswa">
                    <p class="text-center text-gray-600 dark:text-gray-400">Masukkan No. Telepon Wali yang terdaftar untuk mencari siswa.</p>
                    <form @submit.prevent="submitLookup" class="mt-6 max-w-md mx-auto space-y-6 relative">
                        <div v-if="isSearching" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center rounded-md z-10">
                            <ArrowPathIcon class="h-8 w-8 text-gray-500 animate-spin" />
                        </div>
                        <div v-if="errors.lookup" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md" role="alert">
                            <span>{{ errors.lookup }}</span>
                        </div>
                        <div>
                            <label for="nomor_telepon_wali" class="block text-sm font-medium text-gray-700 dark:text-gray-300">No. Telepon Wali</label>
                            <input v-model="lookupForm.nomor_telepon_wali" @input="lookupForm.nomor_telepon_wali = lookupForm.nomor_telepon_wali.replace(/\D/g, '')" id="nomor_telepon_wali" type="tel" inputmode="numeric" placeholder="Contoh: 081234567890" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <div v-if="lookupForm.errors.nomor_telepon_wali" class="text-red-500 text-xs mt-1">{{ lookupForm.errors.nomor_telepon_wali }}</div>
                        </div>
                        <div>
                            <button type="submit" :disabled="lookupForm.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 transition-colors">
                                <span v-if="!lookupForm.processing">Cari Siswa</span>
                                <span v-else>Mencari...</span>
                            </button>
                        </div>
                    </form>

                    <!-- Bagian Bantuan -->
                    <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-6 max-w-md mx-auto">
                        <p>
                            Nomor Anda tidak terdaftar, padahal anak Anda sudah menjadi siswa aktif?
                            <br class="sm:hidden" />
                            Silakan hubungi kami melalui
                            <a href="https://wa.me/62811386846" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-green-600 hover:text-green-500 hover:underline dark:text-green-500 dark:hover:text-green-400">
                                <span>WhatsApp</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                                </svg>
                            </a>.
                        </p>
                    </div>
                </div>

                <!-- Tampilan 2: Daftar Pilihan Siswa -->
                <div v-else-if="foundSiswa">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pilih Siswa</h3>
                        <button @click="foundSiswa = null" class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <ArrowPathIcon class="h-4 w-4 mr-1"/> Cari Nomor Lain
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ditemukan {{ foundSiswa.length }} siswa dengan nomor telepon yang sama.</p>
                    <div class="mt-5 grid grid-cols-1 gap-4">
                        <div v-for="siswa in foundSiswa" :key="siswa.id_siswa" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl p-4 flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex min-w-0 gap-x-4 items-center">
                                <div class="h-12 w-12 flex-none rounded-2xl bg-indigo-50 dark:bg-gray-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <UserIcon class="h-6 w-6" />
                                </div>
                                <div class="min-w-0 flex-auto">
                                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</p>
                                    <p class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ siswa.kelas_nama }}</p>
                                </div>
                            </div>
                            <Link :href="route('tagihan.spp.show', siswa.id_siswa)" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-colors">Pilih</Link>
                        </div>
                    </div>
                     <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-6 max-w-md mx-auto">
                        <p>
                            Bukan siswa yang Anda cari?
                            <br class="sm:hidden" />
                             Silakan hubungi kami melalui
                            <a href="https://wa.me/62811386846" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-green-600 hover:text-green-500 hover:underline dark:text-green-500 dark:hover:text-green-400">
                               <span>WhatsApp</span> 
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                                </svg>
                            </a>.
                        </p>
                    </div>
                </div>
            </div> <!-- End Container State 1 & 2 -->

            <!-- ============================== -->
            <!-- STATE 3: HALAMAN TAGIHAN UTAMA -->
            <!-- ============================== -->
                <div v-if="selectedSiswa" class="w-full animate-fade-in pb-8">
                
                <!-- Dashboard Header Card (Student Info & Cuti) -->
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-lg border border-gray-200 dark:border-gray-700 rounded-3xl p-6 md:p-8 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
                    <!-- Decorative bg -->
                    <div class="absolute -right-10 -top-10 bg-emerald-500/10 w-40 h-40 rounded-full blur-3xl"></div>
                    
                    <div class="flex items-center gap-5 relative z-10">
                        <div class="h-16 w-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white shadow-inner font-bold text-2xl">
                            {{ selectedSiswa.nama_siswa.charAt(0) }}
                        </div>
                        <div>
                            <h3 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ selectedSiswa.nama_siswa }}</h3>
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    NIS: {{ selectedSiswa.nis }}
                                </span>
                                <span class="hidden sm:inline text-gray-300 dark:text-gray-600">&bull;</span>
                                <Link :href="route('tagihan.spp.form')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                    Ganti Nomor
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Cuti Best Practice UI (Sembunyikan Sementara) -->
                    <div v-if="false" class="relative z-10 w-full md:w-auto mt-4 md:mt-0">
                        <button @click="openLeaveModal" class="w-full sm:w-auto relative group px-6 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 hover:border-emerald-500 dark:border-gray-700 dark:hover:border-emerald-400 shadow-sm hover:shadow-emerald-500/20 transition-all duration-300 text-sm font-bold text-gray-700 hover:text-emerald-700 dark:text-gray-200 dark:hover:text-emerald-400 flex items-center justify-center gap-2 overflow-hidden">
                            <span class="absolute inset-0 bg-emerald-50 dark:bg-emerald-900/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out"></span>
                            <CalendarIcon class="w-5 h-5 relative z-10 text-emerald-500 group-hover:-rotate-12 transition-transform duration-300" />
                            <span class="relative z-10">Ajukan Cuti</span>
                        </button>
                    </div>
                </div>
                    
                    <div v-if="showSuccessCard" class="mb-6 p-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-2xl text-center shadow-sm">
                        <CheckCircleIcon class="h-12 w-12 text-green-500 mx-auto" />
                        <h4 class="mt-4 font-bold text-gray-800 dark:text-white">Akun Berhasil Dibuat!</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ flashMessage }}</p>
                    </div>
                    
                    <div v-if="!selectedSiswa.has_user_account && !showSuccessCard" class="mb-8 p-6 sm:p-8 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-10 -mr-10 bg-white/10 w-48 h-48 rounded-full blur-2xl pointer-events-none"></div>
                        <div class="relative z-10">
                            <h4 class="text-xl sm:text-2xl font-black mb-1">Permudah Pembayaran Berikutnya!</h4>
                            <p class="text-indigo-100 text-sm mb-6 max-w-lg leading-relaxed">Buat akun untuk melacak riwayat pembayaran anak Anda dan mengelola tagihan dengan lebih praktis.</p>
                            
                            <form @submit.prevent="submitCreateUser" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start bg-white/10 p-5 rounded-2xl border border-white/20 backdrop-blur-md">
                                <div>
                                    <TextInput v-model="createUserForm.name" placeholder="Nama Akun (misal: Budi Ayah Gilang)" required class="w-full bg-white/90 border-transparent focus:border-white focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-500 rounded-xl" />
                                    <InputError :message="createUserForm.errors.name" class="mt-1 text-pink-200" />
                                </div>
                                <div>
                                    <TextInput v-model="createUserForm.email" type="email" placeholder="Email (untuk login)" required class="w-full bg-white/90 border-transparent focus:border-white focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-500 rounded-xl" />
                                    <InputError :message="createUserForm.errors.email" class="mt-1 text-pink-200" />
                                </div>
                                <div>
                                    <TextInput v-model="createUserForm.password" type="password" placeholder="Password" required class="w-full bg-white/90 border-transparent focus:border-white focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-500 rounded-xl" />
                                    <InputError :message="createUserForm.errors.password" class="mt-1 text-pink-200" />
                                </div>
                                <div>
                                    <TextInput v-model="createUserForm.password_confirmation" type="password" placeholder="Konfirmasi Password" required class="w-full bg-white/90 border-transparent focus:border-white focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-500 rounded-xl" />
                                </div>
                                <div class="md:col-span-2 mt-2">
                                    <button type="submit" :disabled="createUserForm.processing" class="w-full py-3 rounded-xl bg-white text-indigo-700 font-bold hover:bg-indigo-50 hover:shadow-lg transition-all flex justify-center items-center">
                                        {{ createUserForm.processing ? 'Memproses...' : 'Buat Akun Sekarang' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tab Bar -->
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 mb-4">
                        <nav class="flex" aria-label="Tabs">
                            <button
                                @click="activeTab = 'tagihan'"
                                :class="['flex-1 py-4 px-6 text-sm font-semibold rounded-tl-2xl rounded-bl-2xl transition-all flex items-center justify-center gap-2', activeTab === 'tagihan' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700']"
                            >
                                <DocumentTextIcon class="h-5 w-5" />
                                Tagihan Aktif
                                <span v-if="displayList.filter(i => !i.is_projected).length > 0" class="ml-2 inline-flex items-center justify-center h-5 w-5 text-xs font-bold rounded-full" :class="activeTab === 'tagihan' ? 'bg-white/30 text-white' : 'bg-indigo-100 text-indigo-700'">{{ displayList.filter(i => !i.is_projected).length }}</span>
                            </button>
                            <button
                                @click="activeTab = 'riwayat'"
                                :class="['flex-1 py-4 px-6 text-sm font-semibold rounded-tr-2xl rounded-br-2xl transition-all flex items-center justify-center gap-2', activeTab === 'riwayat' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700']"
                            >
                                <ClockIcon class="h-5 w-5" />
                                Riwayat Bayar
                                <span v-if="(historyInvoices || []).length > 0" class="ml-2 inline-flex items-center justify-center h-5 w-5 text-xs font-bold rounded-full" :class="activeTab === 'riwayat' ? 'bg-white/30 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">{{ (historyInvoices || []).length }}</span>
                            </button>
                        </nav>
                    </div>

                    <!-- TAB: Tagihan Aktif (cards pilih bayar) -->
                    <div v-show="activeTab === 'tagihan'">
                        <div v-if="displayList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                            <div v-for="(item, index) in displayList" :key="item.id"
                                @click="!isItemDisabled(index) && updateSelection(item, !selectedPeriods.includes(item.periode_tagihan))" 
                                class="relative overflow-hidden rounded-2xl transition-all duration-300 p-5 flex items-start space-x-4 cursor-pointer group"
                                :class="{ 
                                    'bg-gradient-to-r from-white from-50% via-emerald-50 via-75% to-emerald-500 text-white shadow-xl shadow-emerald-500/30 border-2 border-emerald-100 dark:border-emerald-500/30 scale-[1.02]': selectedPeriods.includes(item.periode_tagihan), 
                                    'bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-md hover:shadow-lg border-2 border-white dark:border-gray-600': !isItemDisabled(index) && !selectedPeriods.includes(item.periode_tagihan), 
                                    'bg-gray-50 dark:bg-gray-800/80 border-2 border-gray-200 dark:border-gray-700 opacity-60 cursor-not-allowed grayscale': isItemDisabled(index) 
                                }">
                                <!-- Aksen Background Icon Uang -->
                                <div v-if="selectedPeriods.includes(item.periode_tagihan)" class="absolute -bottom-8 -right-4 transform -rotate-12 pointer-events-none z-0">
                                    <BanknotesIcon class="w-36 h-36 text-white/20" />
                                </div>
                                
                                <div class="pt-1 z-10">
                                    <Checkbox 
                                        :checked="selectedPeriods.includes(item.periode_tagihan)" 
                                        @click.stop
                                        @update:checked="updateSelection(item, $event)" 
                                        :disabled="isItemDisabled(index)" 
                                        class="h-5 w-5 rounded transition duration-200 cursor-pointer"
                                        :class="selectedPeriods.includes(item.periode_tagihan) ? 'border-emerald-400 text-emerald-600 focus:ring-emerald-500 bg-white/60' : 'border-gray-300 text-emerald-600 focus:ring-emerald-600'" />
                                </div>
                                <div class="flex-1 z-10">
                                    <p class="font-bold text-lg leading-tight mb-1 transition-colors"
                                       :class="selectedPeriods.includes(item.periode_tagihan) ? 'text-emerald-900 drop-shadow-sm' : 'text-gray-900 dark:text-white'">
                                        {{ formatPeriod(item.periode_tagihan) }}
                                    </p>
                                    <p class="text-sm font-semibold transition-colors"
                                       :class="selectedPeriods.includes(item.periode_tagihan) ? 'text-emerald-800' : 'text-emerald-600 dark:text-emerald-400'">
                                        {{ item.total_amount_formatted }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-[11px] font-bold tracking-wider uppercase rounded-full shadow-sm z-10" 
                                      :class="selectedPeriods.includes(item.periode_tagihan) ? 'bg-white/20 text-white border border-white/20' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'">
                                    SPP
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center py-10 text-gray-500 dark:text-gray-400 bg-white/60 dark:bg-gray-800/60 rounded-2xl">
                            <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400" />
                            <p class="mt-2 font-medium">Semua tagihan lunas!</p>
                            <p class="text-sm">Tidak ada tagihan yang perlu dibayar saat ini.</p>
                        </div>
                    </div>

                    <!-- TAB: Riwayat Pembayaran -->
                    <div v-show="activeTab === 'riwayat'">
                        <div v-if="!historyInvoices || historyInvoices.length === 0" class="text-center py-10 text-gray-500 dark:text-gray-400 bg-white/60 dark:bg-gray-800/60 rounded-2xl">
                            <p class="font-medium">Belum ada riwayat pembayaran.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="invoice in historyInvoices" :key="invoice.id"
                                 class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex-grow">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ formatPeriod(invoice.periode_tagihan) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ invoice.description }}</p>
                                    <p v-if="invoice.paid_at_formatted" class="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center gap-1">
                                        <CheckCircleIcon class="h-3.5 w-3.5" />
                                        Dibayar: {{ invoice.paid_at_formatted }}
                                        <span v-if="invoice.payment_method === 'manual'" class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Manual</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <span class="text-lg font-bold" :class="invoice.status === 'PAID' ? 'text-green-600 dark:text-green-400' : 'text-gray-500'">{{ invoice.total_amount_formatted }}</span>
                                    <span class="px-3 py-1 text-xs font-bold uppercase rounded-full" :class="getHistoryStatusClass(invoice.status)">
                                        {{ invoice.status === 'PAID' ? 'Lunas' : invoice.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <!-- Floating Payment Bar -->
            <transition enter-active-class="transition ease-out duration-500" enter-from-class="transform opacity-0 translate-y-full scale-95" enter-to-class="transform opacity-100 translate-y-0 scale-100" leave-active-class="transition ease-in duration-300" leave-from-class="transform opacity-100 translate-y-0 scale-100" leave-to-class="transform opacity-0 translate-y-full scale-95">
                <div v-if="selectedSiswa && selectedPeriods.length > 0" class="fixed bottom-0 left-0 right-0 px-2 sm:px-6 pb-6 pt-6 z-50 flex justify-center bg-gray-100 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 shadow-[0_-10px_30px_rgba(0,0,0,0.1)]">
                    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-6 transform transition-all hover:scale-[1.02]">
                        
                        <div class="flex items-center gap-4 w-full sm:w-auto">
                            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/50 dark:to-emerald-900/50 flex items-center justify-center flex-shrink-0 border border-green-300/50 dark:border-green-600/50 shadow-inner">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xl">{{ selectedPeriods.length }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-0.5">Total Tagihan</p>
                                <p class="text-xl sm:text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">
                                    {{ totalSelectedAmountFormatted }}
                                </p>
                            </div>
                        </div>

                        <button @click="submitPayment" :disabled="paymentForm.processing" class="w-full sm:w-auto relative group overflow-hidden rounded-2xl p-[2px]">
                            <span class="absolute inset-0 bg-gradient-to-r from-green-400 via-emerald-400 to-teal-400 rounded-2xl opacity-70 group-hover:opacity-100 blur transition-opacity duration-300"></span>
                            <div class="relative bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 text-white font-bold px-8 py-3.5 rounded-[14px] flex items-center justify-center transition-all shadow-inner border border-white/20">
                                <CreditCardIcon v-if="!paymentForm.processing" class="h-5 w-5 mr-2 group-hover:animate-pulse" />
                                <svg v-else class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span class="tracking-wide">{{ paymentForm.processing ? 'Memproses...' : 'Bayar Sekarang' }}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </transition>
        </main>

        <footer class="w-full max-w-4xl mx-auto mt-8 text-center text-sm text-gray-600 dark:text-gray-400 relative transition-all duration-700 pb-8">
            <p>Contact Center: 0811-2626-323</p>
            <div class="mt-8 border-t border-gray-300 dark:border-gray-700 py-6 text-center text-sm">
                <p>Copyright &copy; 2025 Persija Development. All Rights Reserved.</p>
                <div class="mt-2 space-x-4">
                    <Link :href="route('legal.terms')" class="hover:text-gray-900 dark:hover:text-white hover:underline">Syarat & Ketentuan</Link>
                    <span class="text-gray-400 dark:text-gray-600">&middot;</span>
                    <Link :href="route('legal.refund')" class="hover:text-gray-900 dark:hover:text-white hover:underline">Kebijakan Pengembalian</Link>
                    <span class="text-gray-400 dark:text-gray-600">&middot;</span>
                    <Link :href="route('legal.privacy')" class="hover:text-gray-900 dark:hover:text-white hover:underline">Kebijakan Privasi</Link>
                </div>
            </div>
        </footer>

        <!-- Modal Ajukan Cuti -->
        <Modal :show="showLeaveModal" @close="showLeaveModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ajukan Cuti</h2>
                <form @submit.prevent="submitLeave" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6">
                        <div class="sm:col-span-3">
                            <InputLabel value="Bulan Cuti" />
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mt-2">
                                <button v-for="m in 12" :key="m" type="button"
                                    @click="toggleMonth(m)"
                                    :disabled="isMonthDisabled(m)"
                                    :class="[
                                        'px-2 py-2 text-sm font-semibold rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500',
                                        leaveForm.months.includes(m) 
                                            ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white border-transparent shadow-md transform scale-105' 
                                            : (isMonthDisabled(m) 
                                                ? 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700/50 dark:text-gray-600' 
                                                : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 dark:bg-gray-800/80 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
                                    ]">
                                    <span class="block">{{ new Date(0, m-1).toLocaleString('id-ID', { month: 'short' }) }}</span>
                                    <span v-if="(props.pendingLeaveMonths || []).includes(leaveForm.year+'-'+m)" class="block text-[10px] opacity-75 font-normal -mt-1">(Proses)</span>
                                    <span v-else-if="(props.paidMonths || []).includes(leaveForm.year+'-'+m)" class="block text-[10px] opacity-75 font-normal -mt-1">(Lunas)</span>
                                </button>
                            </div>
                            <InputError :message="leaveForm.errors.months" class="mt-1" />
                        </div>
                        <div class="sm:col-span-1">
                            <InputLabel value="Tahun" />
                            <TextInput type="number" 
                                v-model="leaveForm.year" 
                                :max="new Date().getFullYear()" 
                                :min="new Date().getFullYear() - 1" 
                                class="w-full mt-2 rounded-xl border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 focus:bg-white focus:ring-indigo-500 transition-colors" />
                            <InputError :message="leaveForm.errors.year" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Alasan Cuti" />
                        <textarea v-model="leaveForm.reason" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" placeholder="Contoh: Sakit, Liburan Keluarga, dll."></textarea>
                         <InputError :message="leaveForm.errors.reason" />
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                         <SecondaryButton @click="showLeaveModal = false">Batal</SecondaryButton>
                         <PrimaryButton :disabled="leaveForm.processing">Ajukan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Missing Agreement Intercept Modal -->
        <Modal :show="isMissingAgreement" maxWidth="2xl" :closeable="false">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 border-b pb-4">
                    Pembaruan Syarat & Ketentuan
                </h2>
                
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p class="mb-4">
                        Halo, sistem mendeteksi bahwa data pendaftaran anak Anda berikut ini belum dilengkapi dengan persetujuan Syarat & Ketentuan elektronik terbaru:
                    </p>
                    
                    <ul class="list-disc pl-5 mb-4 font-semibold text-gray-800 dark:text-gray-200">
                        <li v-for="siswa in missingAgreements?.siswa" :key="siswa.id_siswa">
                            {{ siswa.nama_siswa }}
                        </li>
                    </ul>

                    <p class="mb-2">Untuk melanjutkan akses layanan dan melihat tagihan, silakan baca dan setujui dokumen berikut:</p>
                </div>

                <div class="mt-2 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 prose prose-sm dark:prose-invert">
                    <div v-html="missingAgreements?.document?.content"></div>
                </div>

                <form @submit.prevent="submitAgreement" class="mt-6">
                    <div class="flex items-start mb-4">
                        <div class="flex items-center h-5">
                            <input id="terms_accepted" type="checkbox" v-model="agreementForm.terms_accepted" required
                                class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <label for="terms_accepted" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Saya telah membaca, memahami, dan menyetujui seluruh Syarat dan Ketentuan di atas untuk pendaftaran anak saya.
                        </label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" 
                                :disabled="agreementForm.processing || !agreementForm.terms_accepted"
                                :class="{'opacity-50 cursor-not-allowed': agreementForm.processing || !agreementForm.terms_accepted}"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Saya Setuju & Lanjutkan
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script>
// Separate script block for logic to avoid bloating setup
</script>

<style scoped>
/* Add any custom styles here */
</style>

