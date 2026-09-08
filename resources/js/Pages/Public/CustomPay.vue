<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { CheckCircleIcon, ClockIcon, DocumentDuplicateIcon, QrCodeIcon, CreditCardIcon, ArrowDownTrayIcon, ExclamationTriangleIcon, InformationCircleIcon, ChevronDownIcon, DevicePhoneMobileIcon, BuildingLibraryIcon } from '@heroicons/vue/24/outline';
import BankLogo from '@/Components/BankLogo.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    invoice: Object,
    checkoutData: Object,
    pageTitle: String,
});

const page = usePage();
const appLogo = computed(() => {
    if (page.props.app_settings?.app_logo_cek_spp) return `/storage/${page.props.app_settings.app_logo_cek_spp}`;
    if (page.props.app_settings?.app_logo) return `/storage/${page.props.app_settings.app_logo}`;
    return null;
});

const isExpired = ref(false);
const timeLeft = ref({ hours: 0, minutes: 0, seconds: 0 });
const copySuccess = ref(false);
const openInstructions = ref(false);

const paymentType = computed(() => props.checkoutData?.payment_type || '');
const bankCode = computed(() => props.checkoutData?.bank_code || '');
const vaNumber = computed(() => props.checkoutData?.va_number || props.checkoutData?.virtual_account_number || '');
const qrisString = computed(() => props.checkoutData?.qris_string || '');
const amount = computed(() => props.invoice?.total_amount || 0);
const amountFormatted = computed(() => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount.value));

let timer;
let pollingTimer;

const checkStatus = () => {
    router.reload({ only: ['invoice', 'checkoutData'] });
};

const calculateTimeLeft = () => {
    if (props.invoice.status !== 'PENDING') return;
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
    if (props.invoice.status === 'PENDING') {
        calculateTimeLeft();
        timer = setInterval(calculateTimeLeft, 1000);
        pollingTimer = setInterval(() => {
            checkStatus();
        }, 5000);
    }
});

onUnmounted(() => {
    clearInterval(timer);
    clearInterval(pollingTimer);
});

const copyToClipboard = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback for non-HTTPS or unsupported environments
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (!successful) throw new Error('Fallback copy failed');
        }
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
        alert('Gagal menyalin otomatis. Silakan blok dan salin nomor secara manual.');
    }
};

const downloadQR = () => {
    const link = document.createElement('a');
    link.href = `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(qrisString.value)}`;
    link.download = `QRIS-${props.invoice.external_id_xendit || props.invoice.id}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const paymentInstructions = computed(() => {
    if (paymentType.value === 'QRIS') {
        return {
            title: 'Cara Bayar QRIS',
            icon: 'phone',
            tabs: [
                {
                    name: 'E-Wallet',
                    steps: [
                        'Buka aplikasi e-wallet pilihan Anda (Gopay, OVO, Dana, LinkAja, dll).',
                        'Pilih menu Scan / Pay.',
                        'Arahkan kamera ke QR Code atau upload gambar QR yang telah diunduh.',
                        'Periksa nominal tagihan (' + amountFormatted.value + ') dan konfirmasi pembayaran.',
                        'Selesai.'
                    ]
                },
                {
                    name: 'Mobile Banking',
                    steps: [
                        'Buka aplikasi Mobile Banking yang mendukung QRIS (BCA mobile, Livin\' by Mandiri, BNI Mobile, dll).',
                        'Pilih tombol QRIS di halaman utama aplikasi.',
                        'Scan QR Code pada layar ini.',
                        'Konfirmasi nama merchant dan nominal pembayaran.',
                        'Masukkan PIN untuk menyelesaikan.'
                    ]
                }
            ]
        };
    } else if (paymentType.value === 'VA') {
        const bank = bankCode.value;
        if (bank === 'BCA') {
            return {
                title: 'Cara Bayar BCA Virtual Account',
                icon: 'bank',
                tabs: [
                    {
                        name: 'm-BCA',
                        steps: [
                            'Login ke aplikasi BCA mobile.',
                            'Pilih m-BCA dan masukkan kode akses m-BCA.',
                            'Pilih menu m-Transfer.',
                            'Pilih BCA Virtual Account.',
                            'Masukkan nomor VA: ' + vaNumber.value + '.',
                            'Periksa nama dan nominal (' + amountFormatted.value + ') di layar konfirmasi.',
                            'Pilih OK dan masukkan PIN m-BCA Anda.'
                        ]
                    },
                    {
                        name: 'KlikBCA',
                        steps: [
                            'Login pada aplikasi KlikBCA Individual.',
                            'Pilih menu Transfer Dana.',
                            'Pilih Transfer ke BCA Virtual Account.',
                            'Masukkan nomor VA: ' + vaNumber.value + '.',
                            'Pilih Lanjutkan.',
                            'Periksa detail pembayaran lalu masukkan respon KeyBCA Appli 1.',
                            'Pilih Kirim.'
                        ]
                    },
                    {
                        name: 'ATM BCA',
                        steps: [
                            'Masukkan kartu ATM dan PIN BCA Anda.',
                            'Pilih menu Penarikan Tunai / Transaksi Lainnya.',
                            'Pilih menu Transfer.',
                            'Pilih menu ke Rekening BCA Virtual Account.',
                            'Masukkan nomor VA: ' + vaNumber.value + ' lalu tekan Benar.',
                            'Periksa detail pembayaran dan tekan Ya jika benar.'
                        ]
                    }
                ]
            };
        } else if (bank === 'MANDIRI') {
            return {
                title: 'Cara Bayar Mandiri Virtual Account',
                icon: 'bank',
                tabs: [
                    {
                        name: "Livin' by Mandiri",
                        steps: [
                            "Buka aplikasi Livin' by Mandiri.",
                            'Pilih menu Transfer atau Bayar.',
                            'Pilih Virtual Account, masukkan nomor VA: ' + vaNumber.value + '.',
                            'Livin\' by Mandiri akan otomatis mendeteksi nama perusahaan dan nominal tagihan.',
                            'Periksa nominal ' + amountFormatted.value + ', lalu konfirmasi dengan PIN Anda.',
                        ]
                    },
                    {
                        name: 'ATM Mandiri',
                        steps: [
                            'Masukkan kartu ATM Mandiri dan PIN Anda.',
                            'Pilih Bayar/Beli → Lainnya → Lainnya → Multi Payment.',
                            'Masukkan 10 digit pertama nomor VA sebagai Kode Perusahaan, lalu tekan Benar.',
                            'Masukkan sisa digit nomor VA sebagai Nomor Tagihan, lalu tekan Benar.',
                            'Periksa detail tagihan dan konfirmasi pembayaran.',
                        ]
                    }
                ]
            };
        } else if (bank === 'BNI') {
            return {
                title: 'Cara Bayar BNI Virtual Account',
                icon: 'bank',
                tabs: [
                    {
                        name: 'BNI Mobile Banking',
                        steps: [
                            'Login ke aplikasi BNI Mobile Banking.',
                            'Pilih menu Transfer.',
                            'Pilih menu Virtual Account Billing.',
                            'Pilih Rekening Debet, lalu pilih Input Baru.',
                            'Masukkan nomor VA: ' + vaNumber.value + '.',
                            'Nominal (' + amountFormatted.value + ') akan otomatis muncul. Konfirmasi dan masukkan Password Transaksi.'
                        ]
                    },
                    {
                        name: 'BNI Internet Banking',
                        steps: [
                            'Login ke BNI Internet Banking.',
                            'Pilih menu Transfer.',
                            'Pilih Virtual Account Billing.',
                            'Pilih Rekening Debet dan masukkan nomor VA: ' + vaNumber.value + '.',
                            'Periksa rincian dan masukkan kode otentikasi (Token BNI e-Secure).'
                        ]
                    },
                    {
                        name: 'ATM BNI',
                        steps: [
                            'Masukkan Kartu ATM dan PIN BNI Anda.',
                            'Pilih Menu Lainnya → Transfer.',
                            'Pilih jenis rekening asal, lalu pilih Virtual Account Billing.',
                            'Masukkan nomor VA: ' + vaNumber.value + ' lalu tekan Benar.',
                            'Periksa rincian tagihan dan konfirmasi.'
                        ]
                    }
                ]
            };
        } else if (bank === 'BRI') {
            return {
                title: 'Cara Bayar BRI Virtual Account (BRIVA)',
                icon: 'bank',
                tabs: [
                    {
                        name: 'BRImo',
                        steps: [
                            'Login ke aplikasi BRImo.',
                            'Pilih menu BRIVA.',
                            'Masukkan nomor BRIVA: ' + vaNumber.value + '.',
                            'Periksa rincian tagihan.',
                            'Pilih Bayar dan masukkan PIN BRImo.'
                        ]
                    },
                    {
                        name: 'ATM BRI',
                        steps: [
                            'Masukkan Kartu ATM dan PIN BRI.',
                            'Pilih menu Transaksi Lain → Pembayaran → Lainnya → BRIVA.',
                            'Masukkan nomor BRIVA: ' + vaNumber.value + ' lalu tekan Benar.',
                            'Periksa detail pembayaran, lalu tekan Ya jika sudah sesuai.'
                        ]
                    }
                ]
            };
        } else if (bank === 'PERMATA') {
             return {
                title: 'Cara Bayar Permata Virtual Account',
                icon: 'bank',
                tabs: [
                    {
                        name: 'PermataMobile X',
                        steps: [
                            'Login ke aplikasi PermataMobile X.',
                            'Pilih menu Bayar Tagihan (Pay Bills).',
                            'Pilih menu Virtual Account.',
                            'Masukkan nomor VA: ' + vaNumber.value + '.',
                            'Periksa rincian tagihan.',
                            'Pilih Konfirmasi Pembayaran.'
                        ]
                    },
                    {
                        name: 'ATM Permata',
                        steps: [
                            'Masukkan Kartu ATM dan PIN Permata.',
                            'Pilih menu Transaksi Lainnya → Pembayaran → Transaksi Lainnya → Virtual Account.',
                            'Masukkan nomor VA: ' + vaNumber.value + ' lalu tekan Benar.',
                            'Periksa rincian tagihan, lalu pilih Ya.'
                        ]
                    }
                ]
            };
        }
    }
    
    return null;
});

const activeTab = ref(0);
</script>

<template>
    <Head :title="pageTitle" />
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 selection:bg-indigo-500 selection:text-white">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-block mb-3 sm:mb-5">
                    <img v-if="appLogo" :src="appLogo" alt="App Logo" class="h-8 sm:h-10 w-auto mx-auto drop-shadow-sm">
                    <ApplicationLogo v-else class="h-8 sm:h-10 w-auto mx-auto text-gray-900 dark:text-white" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Selesaikan Pembayaran</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Silakan ikuti instruksi pembayaran di bawah ini.</p>
            </div>

            <!-- PAID -->
            <div v-if="invoice.status === 'PAID'" class="bg-white border border-emerald-100 rounded-3xl p-8 text-center shadow-lg dark:bg-gray-800 dark:border-gray-700">
                <div class="h-24 w-24 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 dark:bg-emerald-900/30">
                    <CheckCircleIcon class="h-12 w-12 text-emerald-500 dark:text-emerald-400" />
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">Pembayaran Berhasil!</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Terima kasih, pembayaran tagihan Anda telah kami terima.</p>
                
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-5 mb-8 text-left max-w-sm mx-auto border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Dibayar</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ amountFormatted }}</span>
                    </div>
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Keterangan</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ invoice.description }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">ID Tagihan</span>
                        <span class="text-xs font-mono text-gray-600 dark:text-gray-300">{{ invoice.external_id_xendit || invoice.id }}</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('tagihan.spp.sukses', { siswa: invoice.siswa_id, invoice_id: invoice.id })" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-emerald-600 px-8 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 transition-all">
                        Lihat Bukti Pembayaran
                    </Link>
                    <Link :href="route('tagihan.spp.form')" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-gray-700 border border-gray-300 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600 transition-all">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- EXPIRED -->
            <div v-else-if="invoice.status === 'EXPIRED' || isExpired" class="bg-white border border-red-100 rounded-3xl p-8 text-center shadow-lg dark:bg-gray-800 dark:border-gray-700">
                <div class="h-24 w-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 dark:bg-red-900/30">
                    <ClockIcon class="h-12 w-12 text-red-500 dark:text-red-400" />
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">Waktu Habis</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Waktu pembayaran untuk tagihan ini telah berakhir.</p>
                <div class="mt-8">
                    <Link :href="route('tagihan.spp.form')" class="inline-flex justify-center rounded-xl bg-gray-900 px-6 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Buat Tagihan Baru
                    </Link>
                </div>
            </div>

            <div v-else class="space-y-6">
                <!-- Kartu Detail Tagihan -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex flex-row items-center justify-between gap-2 sm:gap-4 mb-6">
                            <div class="inline-flex items-center px-2.5 py-1 sm:px-3.5 sm:py-1.5 rounded-full bg-amber-50 border border-amber-200/60 dark:bg-amber-500/10 dark:border-amber-500/20 shadow-sm">
                                <span class="text-[10px] sm:text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wide sm:tracking-widest">Menunggu Pembayaran</span>
                            </div>
                            <div class="flex items-center text-red-500 font-bold bg-red-50 px-2.5 py-1 sm:px-3.5 sm:py-1.5 rounded-full dark:bg-red-900/30 dark:text-red-400 text-xs sm:text-sm shadow-sm border border-red-100 dark:border-red-900/50 flex-shrink-0">
                                <ClockIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 mr-1 sm:mr-1.5" />
                                <span>Sisa waktu {{ timeLeft.hours > 0 ? timeLeft.hours + ':' : '' }}{{ timeLeft.minutes.toString().padStart(2, '0') }}:{{ timeLeft.seconds.toString().padStart(2, '0') }}</span>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total yang harus dibayar</p>
                        <h2 class="text-3xl sm:text-4xl font-black bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">
                            {{ amountFormatted }}
                        </h2>
                        
                        <div class="mt-4 pt-4 border-t border-dashed border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ invoice.description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ID: {{ invoice.external_id_xendit || invoice.id }}</p>
                        </div>
                    </div>

                    <!-- Instruksi VA -->
                    <div v-if="paymentType === 'VA'" class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-8 px-2 bg-white border border-gray-200 rounded-lg flex items-center justify-center dark:bg-white flex-shrink-0 shadow-sm">
                                <BankLogo :bank="bankCode" class="h-4 w-auto" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white uppercase">{{ bankCode }} Virtual Account</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Gunakan nomor VA di bawah untuk membayar</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl p-4 mb-2 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nomor Virtual Account</p>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <p class="text-lg sm:text-xl font-mono font-black text-gray-900 dark:text-white tracking-wide break-all">
                                    {{ vaNumber }}
                                </p>
                                <button @click="copyToClipboard(vaNumber)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg font-bold text-sm hover:bg-indigo-100 transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50">
                                    <DocumentDuplicateIcon class="w-4 h-4 mr-1.5" />
                                    {{ copySuccess ? 'Tersalin!' : 'Salin' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Instruksi QRIS -->
                    <div v-if="paymentType === 'QRIS'" class="p-6 sm:p-8 bg-gray-50 dark:bg-gray-800/50 text-center">
                        <div class="inline-flex items-center justify-center gap-3 mb-6">
                            <div class="h-10 px-3 bg-white border border-gray-200 rounded-xl flex items-center justify-center dark:bg-white flex-shrink-0 shadow-sm">
                                <BankLogo bank="QRIS" class="h-6 w-auto" />
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-bold text-gray-900 dark:text-white uppercase">QRIS</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Scan QR Code dengan aplikasi m-Banking/E-Wallet</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-3xl p-6 mb-2 inline-block shadow-sm">
                            <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrisString)}`" alt="QRIS" class="w-48 h-48 sm:w-64 sm:h-64 mx-auto rounded-xl shadow-sm">
                            
                            <div class="mt-6 flex justify-center">
                                <button @click="downloadQR" class="inline-flex items-center justify-center px-5 py-2.5 bg-pink-50 text-pink-700 rounded-xl font-bold hover:bg-pink-100 transition-colors shadow-sm dark:bg-pink-900/30 dark:text-pink-400 dark:hover:bg-pink-900/50">
                                    <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                                    Download QR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center px-4 py-4 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-900/30 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">
                        Pembayaran akan terverifikasi secara otomatis.<br/>Anda juga bisa menekan tombol di bawah untuk mengecek status.
                    </p>
                    <button @click="checkStatus" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-gray-900 text-white rounded-xl font-bold text-sm hover:bg-gray-800 transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Cek Status Pembayaran
                    </button>
                </div>

                <!-- Cara Pembayaran Accordion -->
                <div v-if="paymentInstructions" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <button @click="openInstructions = !openInstructions" class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left focus:outline-none">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 flex items-center justify-center rounded-lg flex-shrink-0" :class="paymentInstructions.icon === 'phone' ? 'bg-pink-100 text-pink-600 dark:bg-pink-900/50 dark:text-pink-400' : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400'">
                                <DevicePhoneMobileIcon v-if="paymentInstructions.icon === 'phone'" class="w-5 h-5" />
                                <BuildingLibraryIcon v-else-if="paymentInstructions.icon === 'bank'" class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ paymentInstructions.title }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500">Lihat panduan langkah demi langkah</p>
                            </div>
                        </div>
                        <ChevronDownIcon class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="{'rotate-180': openInstructions}" />
                    </button>
                    
                    <div v-show="openInstructions" class="border-t border-gray-100 dark:border-gray-700">
                        <div class="px-5 py-4 bg-gray-50/50 dark:bg-gray-800/30">
                            <!-- Tabs Navigation -->
                            <div class="flex overflow-x-auto space-x-2 border-b border-gray-200 dark:border-gray-700 pb-2 mb-3 hide-scrollbar">
                                <button v-for="(tab, index) in paymentInstructions.tabs" :key="index"
                                    @click="activeTab = index"
                                    class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors focus:outline-none"
                                    :class="activeTab === index ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700'">
                                    {{ tab.name }}
                                </button>
                            </div>
                            
                            <!-- Tab Content -->
                            <div class="py-2">
                                <ol class="space-y-3">
                                    <li v-for="(step, sIndex) in paymentInstructions.tabs[activeTab].steps" :key="sIndex" class="flex items-start text-xs text-gray-600 dark:text-gray-300">
                                        <span class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400 font-bold text-[10px] mr-2 mt-0.5">
                                            {{ sIndex + 1 }}
                                        </span>
                                        <span class="leading-relaxed">{{ step }}</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
