<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { 
    CheckCircleIcon, 
    ClockIcon, 
    DocumentDuplicateIcon, 
    QrCodeIcon, 
    CreditCardIcon, 
    ArrowDownTrayIcon, 
    ArrowPathIcon,
    ExclamationTriangleIcon, 
    InformationCircleIcon, 
    ChevronDownIcon, 
    DevicePhoneMobileIcon, 
    BuildingLibraryIcon,
    CheckIcon
} from '@heroicons/vue/24/outline';
import BankLogo from '@/Components/BankLogo.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import QrcodeVue from 'qrcode.vue';

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

const appName = computed(() => {
    return page.props.app_settings?.app_name || 'Persija Development';
});

const isExpired = ref(false);
const timeLeft = ref({ hours: 0, minutes: 0, seconds: 0 });
const copySuccess = ref(false);
const copyAmountSuccess = ref(false);
const openInstructions = ref(false);
const isDownloading = ref(false);
const qrContainerRef = ref(null);

const paymentType = computed(() => props.checkoutData?.payment_type || '');
const bankCode = computed(() => props.checkoutData?.bank_code || '');
const vaNumber = computed(() => props.checkoutData?.va_number || props.checkoutData?.virtual_account_number || '');
const billerCode = computed(() => props.checkoutData?.biller_code || '');
const billKey = computed(() => props.checkoutData?.bill_key || '');
const qrisString = computed(() => props.checkoutData?.qris_string || '');
const isQrUrl = computed(() => {
    return /^https?:\/\//i.test(qrisString.value) || (!qrisString.value && !!props.checkoutData?.qr_url);
});
const qrImageSrc = computed(() => {
    if (!qrisString.value) return props.checkoutData?.qr_url || '';
    if (/^https?:\/\//i.test(qrisString.value)) {
        return qrisString.value;
    }
    return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrisString.value)}`;
});
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

const copyAmount = async () => {
    try {
        const rawAmount = String(Math.round(amount.value));
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(rawAmount);
        } else {
            const textArea = document.createElement("textarea");
            textArea.value = rawAmount;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
        copyAmountSuccess.value = true;
        setTimeout(() => copyAmountSuccess.value = false, 2000);
    } catch (err) {
        console.error('Failed to copy amount:', err);
    }
};

const loadLogoImage = (src) => {
    return new Promise((resolve) => {
        if (!src) return resolve(null);
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => {
            console.warn('Gagal memuat logo untuk QR, menggunakan fallback teks');
            resolve(null);
        };
        img.src = src;
    });
};

const getQrSourceElement = () => {
    return new Promise((resolve) => {
        // Cek canvas dari qrcode.vue
        const canvas = qrContainerRef.value?.querySelector?.('canvas');
        if (canvas) {
            resolve(canvas);
            return;
        }

        // Cek elemen img yang sudah ter-render di container
        const renderedImg = qrContainerRef.value?.querySelector?.('img');
        if (renderedImg && renderedImg.complete && renderedImg.naturalWidth > 0) {
            resolve(renderedImg);
            return;
        }

        // Fallback jika belum selesai atau berupa external URL
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => {
            console.error('Gagal memuat gambar QR');
            resolve(null);
        };
        img.src = qrImageSrc.value;
    });
};

const downloadQR = async () => {
    if (isDownloading.value) return;
    isDownloading.value = true;

    try {
        const [logoImg, qrSource] = await Promise.all([
            loadLogoImage(appLogo.value),
            getQrSourceElement()
        ]);

        if (!qrSource) {
            alert('QR code belum siap. Silakan muat ulang halaman atau coba beberapa saat lagi.');
            return;
        }

        // Buat canvas beresolusi tinggi (640 x 860) untuk cetak/scan optimal
        const canvas = document.createElement('canvas');
        canvas.width = 640;
        canvas.height = 860;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // 1. Latar Belakang Kartu Putih Bersih
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // 2. Garis Aksen Header Atas (Merah Elegan)
        ctx.fillStyle = '#DC2626';
        ctx.fillRect(0, 0, canvas.width, 6);

        // 3. Border Luar
        ctx.strokeStyle = '#E2E8F0';
        ctx.lineWidth = 2;
        ctx.strokeRect(1, 1, canvas.width - 2, canvas.height - 2);

        // 4. Header: Logo Perusahaan di Ujung Kiri Atas
        const topY = 24;
        let leftTextX = 36;
        if (logoImg) {
            const aspect = logoImg.width / logoImg.height;
            let logoH = 46;
            let logoW = logoH * aspect;
            if (logoW > 140) {
                logoW = 140;
                logoH = logoW / aspect;
            }
            const logoY = topY + (46 - logoH) / 2;
            ctx.drawImage(logoImg, 36, logoY, logoW, logoH);
            leftTextX = 36 + logoW + 12;
        } else {
            ctx.fillStyle = '#DC2626';
            ctx.beginPath();
            if (ctx.roundRect) ctx.roundRect(36, topY, 46, 46, 10);
            else ctx.rect(36, topY, 46, 46);
            ctx.fill();

            ctx.fillStyle = '#FFFFFF';
            ctx.font = 'bold 20px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText((appName.value || 'PD').substring(0, 2).toUpperCase(), 59, topY + 30);
            leftTextX = 94;
        }

        // Teks Nama Perusahaan & Sub-label di samping Logo
        ctx.textAlign = 'left';
        ctx.fillStyle = '#111827';
        ctx.font = 'bold 16px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        const displayAppName = appName.value.length > 24 ? appName.value.substring(0, 22) + '...' : appName.value;
        ctx.fillText(displayAppName, leftTextX, topY + 20);

        ctx.fillStyle = '#6B7280';
        ctx.font = '500 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText(props.invoice?.is_pendaftaran ? 'Pembayaran Pendaftaran Siswa' : 'Pembayaran Resmi SPP', leftTextX, topY + 38);

        // Teks QRIS di Ujung Kanan Atas
        ctx.textAlign = 'right';
        ctx.fillStyle = '#DC2626';
        ctx.font = '900 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText('QRIS', 604, topY + 24);

        ctx.fillStyle = '#9CA3AF';
        ctx.font = 'bold 10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText('PEMBAYARAN DIGITAL', 604, topY + 40);

        // 5. Garis Pembatas Header
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(36, 84);
        ctx.lineTo(604, 84);
        ctx.stroke();

        // 6. Jarak Sedikit di Atas QR + Info ID Tagihan
        const invoiceCode = props.invoice?.external_id_xendit || props.invoice?.id || '';
        ctx.fillStyle = '#F8FAFC';
        ctx.beginPath();
        if (ctx.roundRect) ctx.roundRect(36, 94, 568, 26, 6);
        else ctx.rect(36, 94, 568, 26);
        ctx.fill();

        ctx.textAlign = 'center';
        ctx.fillStyle = '#475569';
        ctx.font = '600 11px monospace, -apple-system, sans-serif';
        ctx.fillText(`ID TAGIHAN: ${invoiceCode}`, 320, 111);

        // 7. Kotak Frame QR Code (Jarak di atas QR dipertahankan rapi)
        const qrSize = 340;
        const qrX = (640 - qrSize) / 2;
        const qrY = 136;

        ctx.fillStyle = '#FFFFFF';
        ctx.strokeStyle = '#E2E8F0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (ctx.roundRect) ctx.roundRect(qrX - 12, qrY - 12, qrSize + 24, qrSize + 24, 16);
        else ctx.rect(qrX - 12, qrY - 12, qrSize + 24, qrSize + 24);
        ctx.fill();
        ctx.stroke();

        // Gambar QR Code
        ctx.drawImage(qrSource, qrX, qrY, qrSize, qrSize);

        // 8. Pembatas di Bawah QR
        ctx.strokeStyle = '#F1F5F9';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(48, 514);
        ctx.lineTo(592, 514);
        ctx.stroke();

        // 9. Informasi Nominal & Rincian Pembayaran
        ctx.textAlign = 'center';
        ctx.fillStyle = '#64748B';
        ctx.font = 'bold 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText('TOTAL PEMBAYARAN', 320, 538);

        ctx.fillStyle = '#0F172A';
        ctx.font = '900 32px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText(amountFormatted.value, 320, 576);

        // Deskripsi & Nama Siswa
        const studentName = props.invoice?.siswa?.nama_siswa || props.invoice?.nama_siswa || '';
        const desc = props.invoice?.description || (props.invoice?.is_pendaftaran ? 'Biaya Pendaftaran' : 'Pembayaran SPP');
        ctx.fillStyle = '#334155';
        ctx.font = '600 14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        const displayDesc = studentName ? `${studentName} — ${desc}` : desc;
        ctx.fillText(displayDesc.length > 55 ? displayDesc.substring(0, 52) + '...' : displayDesc, 320, 608);

        // Batas Waktu Bayar
        if (props.invoice?.due_date) {
            const formattedDate = new Date(props.invoice.due_date).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            ctx.fillStyle = '#DC2626';
            ctx.font = '600 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillText(`Batas Pembayaran: ${formattedDate} WIB`, 320, 632);
        }

        // 10. Kotak Petunjuk Aplikasi Bank / Dompet Digital
        ctx.fillStyle = '#F8FAFC';
        ctx.strokeStyle = '#E2E8F0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (ctx.roundRect) ctx.roundRect(40, 660, 560, 66, 12);
        else ctx.rect(40, 660, 560, 66);
        ctx.fill();
        ctx.stroke();

        ctx.fillStyle = '#0F172A';
        ctx.font = 'bold 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText('Dapat discan dengan seluruh aplikasi m-Banking & E-Wallet:', 320, 686);

        ctx.fillStyle = '#64748B';
        ctx.font = '500 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText('BCA • Mandiri • BRI • BNI • GoPay • OVO • DANA • ShopeePay • LinkAja', 320, 708);

        // 11. Footer Watermark
        ctx.fillStyle = '#94A3B8';
        ctx.font = '400 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.fillText(`Dokumen pembayaran digital resmi diterbitkan oleh ${appName.value}`, 320, 762);
        ctx.fillText('Silakan simpan QR ini di galeri ponsel Anda dan scan melalui aplikasi perbankan.', 320, 780);

        // 12. Trigger Direct Download Tanpa Membuka Tab Baru
        const dataUrl = canvas.toDataURL('image/png');
        const filename = `QRIS-${invoiceCode || 'Tagihan'}.png`;

        const downloadLink = document.createElement('a');
        downloadLink.href = dataUrl;
        downloadLink.download = filename;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    } catch (err) {
        console.error('Download QR failed:', err);
        alert('Gagal mengunduh QR Code otomatis. Silakan gunakan tangkapan layar (screenshot).');
    } finally {
        isDownloading.value = false;
    }
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
                        'Buka aplikasi e-wallet pilihan Anda (Gopay, OVO, Dana, ShopeePay, LinkAja, dll).',
                        'Pilih menu Scan / Pay / Bayar.',
                        'Arahkan kamera ke QR Code atau tekan tombol Download QR di atas lalu pilih upload dari galeri.',
                        'Periksa nama penerima dan nominal tagihan (' + amountFormatted.value + ').',
                        'Konfirmasi dan masukkan PIN untuk menyelesaikan pembayaran.'
                    ]
                },
                {
                    name: 'Mobile Banking',
                    steps: [
                        'Buka aplikasi Mobile Banking yang mendukung QRIS (BCA mobile, Livin\' by Mandiri, BNI Mobile, BRImo, dll).',
                        'Pilih tombol QRIS di halaman utama aplikasi.',
                        'Arahkan kamera ke QR Code atau upload gambar QR yang telah diunduh dari galeri.',
                        'Konfirmasi nama merchant dan nominal pembayaran.',
                        'Masukkan PIN m-Banking untuk menyelesaikan transaksi.'
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
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-3 sm:py-10 sm:px-6 lg:px-8 selection:bg-rose-500 selection:text-white">
        <div class="max-w-xl mx-auto">
            <!-- Header Ringkas & Responsif -->
            <div class="text-center mb-5 sm:mb-7">
                <div class="inline-block mb-2 sm:mb-3">
                    <img v-if="appLogo" :src="appLogo" alt="App Logo" class="h-8 sm:h-10 w-auto mx-auto drop-shadow-sm object-contain">
                    <ApplicationLogo v-else class="h-8 sm:h-10 w-auto mx-auto text-gray-900 dark:text-white" />
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">Selesaikan Pembayaran</h1>
                <p class="mt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-400">Silakan ikuti instruksi pembayaran di bawah ini.</p>
            </div>

            <!-- STATUS: PAID (Pembayaran Berhasil) -->
            <div v-if="invoice.status === 'PAID'" class="bg-white border border-emerald-100 rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-center shadow-lg dark:bg-gray-800 dark:border-gray-700">
                <div class="h-20 w-20 sm:h-24 sm:w-24 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6 dark:bg-emerald-900/30">
                    <CheckCircleIcon class="h-10 w-10 sm:h-12 sm:w-12 text-emerald-500 dark:text-emerald-400" />
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mb-2">Pembayaran Berhasil!</h2>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-6">Terima kasih, pembayaran tagihan Anda telah kami terima.</p>
                
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl sm:rounded-2xl p-4 sm:p-5 mb-6 text-left max-w-sm mx-auto border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-2.5">
                        <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Total Dibayar</span>
                        <span class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">{{ amountFormatted }}</span>
                    </div>
                    <div class="flex justify-between items-start gap-4 mb-2.5">
                        <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Keterangan</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white text-right">{{ invoice.description }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2.5 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ID Tagihan</span>
                        <span class="text-[11px] sm:text-xs font-mono text-gray-600 dark:text-gray-300">{{ invoice.external_id_xendit || invoice.id }}</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link :href="invoice.is_pendaftaran ? route('registration.success.pending', { pending: invoice.id }) : route('tagihan.spp.success', { siswa: invoice.id_siswa || invoice.siswa_id, invoice_id: invoice.id })" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 transition-all active:scale-95">
                        Lihat Bukti Pembayaran
                    </Link>
                    <Link :href="invoice.is_pendaftaran ? '/' : route('tagihan.spp.form')" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-gray-700 border border-gray-300 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600 transition-all">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- STATUS: EXPIRED (Waktu Habis) -->
            <div v-else-if="invoice.status === 'EXPIRED' || isExpired" class="bg-white border border-red-100 rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-center shadow-lg dark:bg-gray-800 dark:border-gray-700">
                <div class="h-20 w-20 sm:h-24 sm:w-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6 dark:bg-red-900/30">
                    <ClockIcon class="h-10 w-10 sm:h-12 sm:w-12 text-red-500 dark:text-red-400" />
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mb-2">Waktu Habis</h2>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-6">Waktu pembayaran untuk tagihan ini telah berakhir.</p>
                <div>
                    <Link :href="invoice.is_pendaftaran ? '/' : route('tagihan.spp.form')" class="w-full sm:w-auto inline-flex justify-center rounded-xl bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        {{ invoice.is_pendaftaran ? 'Kembali ke Beranda' : 'Buat Tagihan Baru' }}
                    </Link>
                </div>
            </div>

            <!-- STATUS: PENDING (Proses Pembayaran) -->
            <div v-else class="space-y-4 sm:space-y-6">
                <!-- Kartu Detail Tagihan Utama -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl sm:rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 sm:p-6 lg:p-7 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <div class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200/60 dark:bg-amber-500/10 dark:border-amber-500/20 shadow-sm">
                                <span class="text-[10px] sm:text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Menunggu Pembayaran</span>
                            </div>
                            <div class="flex items-center text-red-500 font-bold bg-red-50 px-2.5 py-1 rounded-full dark:bg-red-900/30 dark:text-red-400 text-xs sm:text-sm shadow-sm border border-red-100 dark:border-red-900/50 flex-shrink-0">
                                <ClockIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4 mr-1 sm:mr-1.5" />
                                <span>Sisa waktu {{ timeLeft.hours > 0 ? timeLeft.hours + ':' : '' }}{{ timeLeft.minutes.toString().padStart(2, '0') }}:{{ timeLeft.seconds.toString().padStart(2, '0') }}</span>
                            </div>
                        </div>
                        
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Total yang harus dibayar</p>
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 class="text-2xl sm:text-4xl font-black bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300">
                                {{ amountFormatted }}
                            </h2>
                            <button @click="copyAmount" class="inline-flex items-center text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline gap-1 py-1 px-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/40">
                                <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                <span>{{ copyAmountSuccess ? 'Tersalin!' : 'Salin Nominal' }}</span>
                            </button>
                        </div>
                        
                        <div class="mt-3.5 pt-3.5 border-t border-dashed border-gray-200 dark:border-gray-700">
                            <p class="text-xs sm:text-sm font-semibold text-gray-800 dark:text-gray-200">{{ invoice.description }}</p>
                            <div class="flex items-center justify-between text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>ID: {{ invoice.external_id_xendit || invoice.id }}</span>
                                <span v-if="invoice.siswa?.nama_siswa" class="font-medium text-gray-700 dark:text-gray-300 truncate max-w-[180px]">{{ invoice.siswa.nama_siswa }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- METODE: VIRTUAL ACCOUNT -->
                    <div v-if="paymentType === 'VA'" class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3 mb-3.5">
                            <div class="h-8 px-2 bg-white border border-gray-200 rounded-lg flex items-center justify-center dark:bg-white flex-shrink-0 shadow-sm">
                                <BankLogo :bank="bankCode" class="h-4 w-auto" />
                            </div>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase">{{ bankCode }} Virtual Account</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Gunakan nomor VA di bawah untuk transfer</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl p-3.5 sm:p-4 mb-2 shadow-sm space-y-3">
                            <div v-if="billerCode && billKey" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-3 border-b border-gray-100 dark:border-gray-600">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Kode Perusahaan (Biller Code)</p>
                                    <div class="flex items-center justify-between">
                                        <p class="text-base font-mono font-bold text-gray-900 dark:text-white">{{ billerCode }}</p>
                                        <button @click="copyToClipboard(billerCode)" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">Salin</button>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Kode Bayar (Bill Key)</p>
                                    <div class="flex items-center justify-between">
                                        <p class="text-base font-mono font-bold text-gray-900 dark:text-white">{{ billKey }}</p>
                                        <button @click="copyToClipboard(billKey)" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">Salin</button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nomor Virtual Account</p>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                                    <p class="text-lg sm:text-2xl font-mono font-black text-gray-900 dark:text-white tracking-wider break-all bg-gray-50 dark:bg-gray-800/80 p-2.5 rounded-lg border border-gray-100 dark:border-gray-600 text-center sm:text-left">
                                        {{ vaNumber }}
                                    </p>
                                    <button @click="copyToClipboard(vaNumber)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg font-bold text-xs sm:text-sm transition-colors shadow-sm active:scale-95">
                                        <DocumentDuplicateIcon class="w-4 h-4 mr-1.5" />
                                        {{ copySuccess ? 'Tersalin!' : 'Salin Nomor VA' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- METODE: QRIS (Mobile Optimized & Branded Download) -->
                    <div v-if="paymentType === 'QRIS'" class="p-4 sm:p-7 bg-gray-50 dark:bg-gray-800/50 text-center">
                        <!-- Kartu QRIS Elegan Berstandar Nasional -->
                        <div class="w-full max-w-sm mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 sm:p-6 shadow-sm flex flex-col items-center">
                            <!-- Mini Header Atas Kartu -->
                            <div class="w-full flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-2 text-left">
                                    <img v-if="appLogo" :src="appLogo" alt="Logo" class="h-6 w-auto max-w-[80px] object-contain">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white truncate max-w-[140px]">{{ appName }}</span>
                                </div>
                                <div class="h-6 px-2 bg-white border border-gray-200 rounded flex items-center justify-center">
                                    <BankLogo bank="QRIS" class="h-4 w-auto" />
                                </div>
                            </div>

                            <!-- QR Container dengan jarak atas proporsional -->
                            <div ref="qrContainerRef" class="p-3 sm:p-4 bg-white rounded-2xl shadow-inner border border-gray-100 flex items-center justify-center my-1.5">
                                <QrcodeVue
                                    v-if="!isQrUrl && qrisString"
                                    :value="qrisString"
                                    :size="240"
                                    level="M"
                                    render-as="canvas"
                                    class="rounded-lg max-w-full h-auto"
                                />
                                <img
                                    v-else
                                    :src="qrImageSrc"
                                    alt="QRIS"
                                    crossorigin="anonymous"
                                    class="w-56 h-56 sm:w-60 sm:h-60 mx-auto rounded-lg object-contain"
                                />
                            </div>

                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 text-center">
                                NMID: <span class="font-mono font-medium">{{ invoice.external_id_xendit || invoice.id }}</span>
                            </p>

                            <!-- Tombol Download QR Otomatis -->
                            <div class="w-full mt-4">
                                <button
                                    @click="downloadQR"
                                    :disabled="isDownloading"
                                    class="w-full inline-flex items-center justify-center px-5 py-3.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all active:scale-[0.98] disabled:opacity-75 disabled:cursor-not-allowed gap-2"
                                >
                                    <ArrowDownTrayIcon v-if="!isDownloading" class="w-4 h-4 sm:w-5 sm:h-5" />
                                    <ArrowPathIcon v-else class="w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                                    <span>{{ isDownloading ? 'Menyiapkan Gambar...' : 'Download QR Code' }}</span>
                                </button>
                            </div>

                            <!-- Petunjuk untuk pengguna HP -->
                            <div class="mt-3.5 p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/30 rounded-xl text-left border border-rose-100 dark:border-rose-900/30 w-full">
                                <p class="text-[11px] leading-relaxed text-rose-800 dark:text-rose-300">
                                    <span class="font-bold">Tips Bayar di HP:</span> Download QR di atas atau ambil tangkapan layar (screenshot), lalu buka m-Banking/E-Wallet Anda dan pilih fitur scan dari galeri foto.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bagian Konfirmasi & Cek Status -->
                <div class="text-center p-4 bg-indigo-50/60 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 shadow-sm">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-3">
                        Pembayaran akan terverifikasi secara otomatis oleh sistem.<br class="hidden sm:inline" />Anda juga dapat menekan tombol di bawah setelah melakukan transfer.
                    </p>
                    <button @click="checkStatus" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 bg-gray-900 text-white rounded-xl font-bold text-xs sm:text-sm hover:bg-gray-800 active:bg-black transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 active:scale-95">
                        Cek Status Pembayaran
                    </button>
                </div>

                <!-- Panduan Cara Bayar Accordion -->
                <div v-if="paymentInstructions" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <button @click="openInstructions = !openInstructions" class="w-full flex items-center justify-between p-4 sm:px-5 sm:py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left focus:outline-none">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 flex items-center justify-center rounded-lg flex-shrink-0" :class="paymentInstructions.icon === 'phone' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-400' : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400'">
                                <DevicePhoneMobileIcon v-if="paymentInstructions.icon === 'phone'" class="w-5 h-5" />
                                <BuildingLibraryIcon v-else-if="paymentInstructions.icon === 'bank'" class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">{{ paymentInstructions.title }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500">Lihat panduan langkah demi langkah</p>
                            </div>
                        </div>
                        <ChevronDownIcon class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="{'rotate-180': openInstructions}" />
                    </button>
                    
                    <div v-show="openInstructions" class="border-t border-gray-100 dark:border-gray-700">
                        <div class="p-4 sm:p-5 bg-gray-50/50 dark:bg-gray-800/30">
                            <!-- Tabs Navigasi -->
                            <div class="flex overflow-x-auto space-x-2 border-b border-gray-200 dark:border-gray-700 pb-2 mb-3 hide-scrollbar">
                                <button v-for="(tab, index) in paymentInstructions.tabs" :key="index"
                                    @click="activeTab = index"
                                    class="whitespace-nowrap px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors focus:outline-none"
                                    :class="activeTab === index ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700'">
                                    {{ tab.name }}
                                </button>
                            </div>
                            
                            <!-- Tab Content -->
                            <div class="py-1">
                                <ol class="space-y-2.5 sm:space-y-3">
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
