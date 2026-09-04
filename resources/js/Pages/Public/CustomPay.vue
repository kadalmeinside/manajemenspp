<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { CheckCircleIcon, ClockIcon, DocumentDuplicateIcon, QrCodeIcon, CreditCardIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    invoice: Object,
    checkoutData: Object,
    pageTitle: String,
});

const isExpired = ref(false);
const timeLeft = ref({ hours: 0, minutes: 0, seconds: 0 });
const copySuccess = ref(false);

const paymentType = computed(() => props.checkoutData.payment_type || '');
const bankCode = computed(() => props.checkoutData.bank_code || '');
const vaNumber = computed(() => props.checkoutData.va_number || props.checkoutData.virtual_account_number || '');
const qrisString = computed(() => props.checkoutData.qris_string || '');
const amount = computed(() => props.invoice.total_amount || 0);
const amountFormatted = computed(() => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount.value));

let timer;

const calculateTimeLeft = () => {
    const expireTime = new Date(props.invoice.due_date).getTime();
    const now = new Date().getTime();
    const distance = expireTime - now;

    if (distance < 0) {
        isExpired.value = true;
        clearInterval(timer);
        return;
    }

    timeLeft.value = {
        hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((distance % (1000 * 60)) / 1000),
    };
};

onMounted(() => {
    calculateTimeLeft();
    timer = setInterval(calculateTimeLeft, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const downloadQR = async () => {
    try {
        // Fetch the image with a larger size for better quality when saved
        const response = await fetch(`https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(qrisString.value)}`);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `QRIS-${props.invoice.external_id_xendit}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Gagal mengunduh QR", error);
        alert("Gagal mengunduh gambar QR. Silakan screenshot layar ini.");
    }
};

const checkStatus = () => {
    // Reload halaman untuk mendapatkan status terbaru dari backend
    router.reload();
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 font-sans selection:bg-emerald-500 selection:text-white">
        <div class="max-w-2xl mx-auto">
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ pageTitle }}</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Selesaikan pembayaran Anda sebelum batas waktu habis.</p>
            </div>

            <!-- Kartu Status -->
            <div v-if="invoice.status === 'PAID'" class="bg-emerald-50 border border-emerald-200 rounded-3xl p-8 text-center shadow-lg mb-8 dark:bg-emerald-900/20 dark:border-emerald-800">
                <div class="h-20 w-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-emerald-800/50">
                    <CheckCircleIcon class="h-10 w-10 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h2 class="text-2xl font-bold text-emerald-800 dark:text-emerald-300 mb-2">Pembayaran Berhasil!</h2>
                <p class="text-emerald-600 dark:text-emerald-400">Terima kasih, pembayaran tagihan Anda telah kami terima.</p>
                <div class="mt-8">
                    <Link :href="route('tagihan.spp.form')" class="inline-flex justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all">
                        Kembali ke Halaman Utama
                    </Link>
                </div>
            </div>

            <div v-else-if="isExpired || invoice.status === 'EXPIRED'" class="bg-red-50 border border-red-200 rounded-3xl p-8 text-center shadow-lg mb-8 dark:bg-red-900/20 dark:border-red-800">
                <div class="h-20 w-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-red-800/50">
                    <ClockIcon class="h-10 w-10 text-red-600 dark:text-red-400" />
                </div>
                <h2 class="text-2xl font-bold text-red-800 dark:text-red-300 mb-2">Waktu Pembayaran Habis</h2>
                <p class="text-red-600 dark:text-red-400">Silakan buat tagihan baru untuk melakukan pembayaran.</p>
                <div class="mt-8">
                    <Link :href="route('tagihan.spp.form')" class="inline-flex justify-center rounded-xl bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900 transition-all">
                        Kembali ke Halaman Utama
                    </Link>
                </div>
            </div>

            <div v-else class="space-y-6">
                
                <!-- Kartu Detail Tagihan -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold uppercase tracking-wider dark:bg-yellow-900/30 dark:text-yellow-400">
                                Menunggu Pembayaran
                            </span>
                            <div class="flex items-center text-red-500 font-bold bg-red-50 px-3 py-1 rounded-full dark:bg-red-900/30 dark:text-red-400 text-sm">
                                <ClockIcon class="w-4 h-4 mr-1.5" />
                                <span>{{ timeLeft.hours.toString().padStart(2, '0') }}:{{ timeLeft.minutes.toString().padStart(2, '0') }}:{{ timeLeft.seconds.toString().padStart(2, '0') }}</span>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total yang harus dibayar</p>
                        <h2 class="text-4xl sm:text-5xl font-black bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">
                            {{ amountFormatted }}
                        </h2>
                        
                        <div class="mt-4 pt-4 border-t border-dashed border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ invoice.description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ID Tagihan: {{ invoice.external_id_xendit }}</p>
                        </div>
                    </div>

                    <!-- Instruksi VA -->
                    <div v-if="paymentType === 'VA'" class="p-6 sm:p-8 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-10 w-10 bg-indigo-100 rounded-xl flex items-center justify-center dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                                <CreditCardIcon class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white uppercase">{{ bankCode }} Virtual Account</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Gunakan nomor VA di bawah untuk membayar</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl p-4 sm:p-6 mb-6">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Nomor Virtual Account</p>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <p class="text-2xl sm:text-3xl font-mono font-bold text-gray-900 dark:text-white tracking-widest break-all">
                                    {{ vaNumber }}
                                </p>
                                <button @click="copyToClipboard(vaNumber)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl font-semibold hover:bg-indigo-100 transition-colors dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50">
                                    <DocumentDuplicateIcon class="w-5 h-5 mr-2" />
                                    {{ copySuccess ? 'Tersalin!' : 'Salin' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Instruksi QRIS -->
                    <div v-if="paymentType === 'QRIS'" class="p-6 sm:p-8 bg-gray-50 dark:bg-gray-800/50 text-center">
                        <div class="inline-flex items-center justify-center gap-3 mb-6">
                            <div class="h-10 w-10 bg-pink-100 rounded-xl flex items-center justify-center dark:bg-pink-900/50 text-pink-600 dark:text-pink-400">
                                <QrCodeIcon class="w-6 h-6" />
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white uppercase">QRIS</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Scan QR Code dengan aplikasi m-Banking/E-Wallet</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-3xl p-6 mb-6 inline-block shadow-sm">
                            <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrisString)}`" alt="QRIS" class="w-48 h-48 sm:w-64 sm:h-64 mx-auto rounded-xl">
                            
                            <div class="mt-6 flex justify-center">
                                <button @click="downloadQR" class="inline-flex items-center justify-center px-4 py-2 bg-pink-50 text-pink-700 rounded-xl font-semibold hover:bg-pink-100 transition-colors dark:bg-pink-900/30 dark:text-pink-400 dark:hover:bg-pink-900/50">
                                    <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                                    Download QR
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row gap-4">
                        <button @click="checkStatus" class="flex-1 w-full inline-flex justify-center items-center px-6 py-3.5 bg-gray-900 text-white rounded-xl font-bold hover:bg-gray-800 transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                            Saya Sudah Bayar
                        </button>
                        <Link :href="route('tagihan.spp.form')" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3.5 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 text-center">
                            Kembali
                        </Link>
                    </div>
                </div>
                
                <div class="text-center px-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Pastikan Anda transfer sesuai dengan nominal hingga 3 digit terakhir jika ada. Pembayaran akan terverifikasi secara otomatis.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
