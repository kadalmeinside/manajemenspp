<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { CreditCardIcon, ArrowPathIcon, UserIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { XCircleIcon } from '@heroicons/vue/20/solid';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    pageTitle: String,
    errors: Object,
    foundSiswa: Array,
    selectedSiswa: Object,
    sppInvoices: Array,
    lastPaidPeriod: String,
    searchedPhone: String,
});

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo ? `/storage/${page.props.app_settings.app_logo}` : null);
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

// --- Page Logic ---
const displayList = computed(() => {
    if (!props.selectedSiswa) return [];
    const existingInvoices = (props.sppInvoices || []).map(inv => ({ ...inv, is_projected: false }));
    const projectedInvoices = [];
    let startProjectionDate;
    if (existingInvoices.length > 0) {
        const lastPeriod = new Date(existingInvoices[existingInvoices.length - 1].periode_tagihan);
        startProjectionDate = new Date(Date.UTC(lastPeriod.getUTCFullYear(), lastPeriod.getUTCMonth() + 1, 1));
    } else if (props.lastPaidPeriod) {
        const lastPeriod = new Date(props.lastPaidPeriod);
        startProjectionDate = new Date(Date.UTC(lastPeriod.getUTCFullYear(), lastPeriod.getUTCMonth() + 1, 1));
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
            selectedPeriods.value.push(item.periode_tagihan);
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

const getStatusClass = (status) => ({
    'PENDING': 'bg-yellow-100 text-yellow-800',
    'PROJECTED': 'bg-blue-100 text-blue-800',
}[status] || 'bg-gray-100 text-gray-800');

const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center p-4 sm:p-6">
        <!-- Background Image -->
        <div class="fixed inset-0 z-0 bg-cover bg-center" style="background-image: url('/images/bg_registration.webp');">
            <div class="absolute inset-0 bg-black/50"></div>
        </div>

        <!-- Redirecting Overlay -->
        <div v-if="isRedirecting" class="fixed inset-0 bg-black/70 flex flex-col items-center justify-center z-50">
            <ArrowPathIcon class="h-12 w-12 text-white animate-spin" />
            <p class="text-white mt-4 text-lg">Mengarahkan ke halaman pembayaran...</p>
        </div>

        <main class="w-full max-w-4xl mx-auto z-10 flex-grow flex flex-col overflow-y-auto">
            <header class="text-center mb-8 flex-shrink-0 pt-6">
                <Link href="/" class="inline-block">
                    <img v-if="appLogo" :src="appLogo" alt="App Logo" class="h-12 w-auto mx-auto">
                    <ApplicationLogo v-else class="h-12 w-auto mx-auto text-white" />
                </Link>
            </header>

            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg shadow-2xl rounded-xl p-6 md:p-8 pb-32">
                
                <!-- VIEW 1: SEARCH FORM -->
                <div v-if="!foundSiswa && !selectedSiswa">
                     <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-white">{{ pageTitle }}</h1>
                    <p class="mt-2 text-center text-gray-600 dark:text-gray-400">Masukkan No. Telepon Wali yang terdaftar.</p>
                    <form @submit.prevent="submitLookup" class="mt-6 max-w-md mx-auto space-y-6 relative">
                        <div v-if="isSearching" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center rounded-md">
                            <ArrowPathIcon class="h-8 w-8 text-gray-500 animate-spin" />
                        </div>
                        <div v-if="errors.lookup" class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4" role="alert">
                            <div class="flex">
                                <div class="flex-shrink-0"><XCircleIcon class="h-5 w-5 text-red-400" aria-hidden="true" /></div>
                                <div class="ml-3"><p class="text-sm font-medium text-red-800 dark:text-red-300">{{ errors.lookup }}</p></div>
                            </div>
                        </div>
                        <div>
                            <InputLabel for="nomor_telepon_wali" value="No. Telepon Wali" />
                            <TextInput v-model="lookupForm.nomor_telepon_wali" id="nomor_telepon_wali" type="tel" required class="mt-1 block w-full" />
                            <InputError :message="lookupForm.errors.nomor_telepon_wali" class="mt-2" />
                        </div>
                        <div>
                            <PrimaryButton type="submit" :disabled="lookupForm.processing" class="w-full flex justify-center py-3">
                                {{ lookupForm.processing ? 'Mencari...' : 'Cari Siswa' }}
                            </PrimaryButton>
                        </div>
                    </form>

                    <!-- Help Section -->
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

                <!-- VIEW 2: STUDENT SELECTION LIST -->
                <div v-else-if="foundSiswa">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pilih Siswa</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ditemukan {{ foundSiswa.length }} siswa dengan nomor telepon yang sama.</p>
                    <div class="mt-4 border-t border-gray-200 dark:border-gray-700">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-600">
                            <li v-for="siswa in foundSiswa" :key="siswa.id_siswa" class="flex items-center justify-between gap-x-6 py-4">
                                <div class="flex min-w-0 gap-x-4">
                                    <UserIcon class="h-10 w-10 flex-none rounded-full bg-gray-200 dark:bg-gray-700 p-2 text-gray-500" />
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</p>
                                        <p class="mt-1 truncate text-xs leading-5 text-gray-500 dark:text-gray-400">{{ siswa.kelas_nama }}</p>
                                    </div>
                                </div>
                                <Link :href="route('tagihan.spp.show', siswa.id_siswa)" class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">Pilih</Link>
                            </li>
                        </ul>
                    </div>
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

                <!-- VIEW 3: FULL BILLING PAGE -->
                <div v-else-if="selectedSiswa">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Tagihan untuk: {{ selectedSiswa.nama_siswa }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">NIS: {{ selectedSiswa.nis }}</p>
                        </div>
                        <Link :href="route('tagihan.spp.form')" class="text-sm text-indigo-600 hover:text-indigo-500">
                           &larr; Cari dengan Nomor Lain
                        </Link>
                    </div>
                    
                    <div v-if="showSuccessCard" class="mt-8 p-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-lg text-center">
                        <CheckCircleIcon class="h-12 w-12 text-green-500 mx-auto" />
                        <h4 class="mt-4 font-bold text-gray-800 dark:text-white">Akun Berhasil Dibuat!</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ flashMessage }}</p>
                        <Link :href="route('login')" class="mt-4 inline-block">
                            <PrimaryButton>Masuk Sekarang</PrimaryButton>
                        </Link>
                    </div>
                    
                    <div v-if="!selectedSiswa.has_user_account && !showSuccessCard" class="mt-8 p-6 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800/50 rounded-lg">
                        <h4 class="font-bold text-gray-800 dark:text-white">Lengkapi Akun Anda</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Buat akun untuk login dan mempermudah pembayaran di kemudian hari.</p>
                        <form @submit.prevent="submitCreateUser" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <div>
                                <TextInput v-model="createUserForm.name" placeholder="Nama Akun (misal: Budi Ayah Gilang)" required class="w-full" />
                                <InputError :message="createUserForm.errors.name" class="mt-1" />
                            </div>
                            <div>
                                <TextInput v-model="createUserForm.email" type="email" placeholder="Email (untuk login)" required class="w-full" />
                                <InputError :message="createUserForm.errors.email" class="mt-1" />
                            </div>
                            <div>
                                <TextInput v-model="createUserForm.password" type="password" placeholder="Password" required class="w-full" />
                                <InputError :message="createUserForm.errors.password" class="mt-1" />
                            </div>
                            <div>
                                <TextInput v-model="createUserForm.password_confirmation" type="password" placeholder="Konfirmasi Password" required class="w-full" />
                            </div>
                            <PrimaryButton type="submit" :disabled="createUserForm.processing" class="md:col-span-2">
                                {{ createUserForm.processing ? 'Menyimpan...' : 'Simpan & Buat Akun' }}
                            </PrimaryButton>
                        </form>
                    </div>

                    <div class="mt-8">
                        <div v-if="displayList.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="(item, index) in displayList" :key="item.id"
                                @click="!isItemDisabled(index) && updateSelection(item, !selectedPeriods.includes(item.periode_tagihan))" 
                                class="bg-white/50 dark:bg-gray-900/50 rounded-lg shadow-sm transition-all duration-200 p-4 flex items-center space-x-4"
                                :class="{ 
                                    'bg-slate-200 dark:bg-slate-700': selectedPeriods.includes(item.periode_tagihan),
                                    'cursor-pointer hover:shadow-md': !isItemDisabled(index),
                                    'opacity-50 cursor-not-allowed': isItemDisabled(index)
                                }">
                                <Checkbox 
                                    :checked="selectedPeriods.includes(item.periode_tagihan)" 
                                    @update:checked="updateSelection(item, $event)"
                                    :disabled="isItemDisabled(index)"
                                />
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ getShortDescription(item.description) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.total_amount_formatted }}</p>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="getStatusClass(item.status)">
                                    {{ item.is_projected ? 'Proyeksi' : 'Tersedia' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Floating Payment Bar -->
            <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 translate-y-full" enter-to-class="transform opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 translate-y-full">
                <div v-if="selectedSiswa && selectedPeriods.length > 0" class="fixed bottom-0 left-0 right-0 w-full z-40">
                    <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-t border-gray-200 dark:border-gray-700 shadow-2xl shadow-slate-400/20">
                        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ selectedPeriods.length }} bulan tagihan dipilih</h4>
                                    <p class="text-lg font-bold text-red-600 dark:text-red-500">{{ totalSelectedAmountFormatted }}</p>
                                </div>
                                <PrimaryButton @click="submitPayment" :disabled="paymentForm.processing" class="w-full sm:w-auto">
                                    <CreditCardIcon class="h-5 w-5 mr-2"/>
                                    {{ paymentForm.processing ? 'Memproses...' : 'Lanjutkan ke Pembayaran' }}
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </main>
    </div>
</template>
