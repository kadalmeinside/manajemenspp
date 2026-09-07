const fs = require('fs');
let code = fs.readFileSync('resources/js/Pages/Public/CustomPay.vue', 'utf8');

// 1. Remove Mandiri logic
code = code.replace(
    /const isMandiri = computed\(\(\) => bankCode\.value === 'MANDIRI'\);\nconst mandiriBillerCode = computed\(\(\) => props\.checkoutData\?\.raw_response\?\.biller_code \|\| vaNumber\.value\.substring\(0, 5\)\);\nconst mandiriBillKey = computed\(\(\) => props\.checkoutData\?\.raw_response\?\.bill_key \|\| vaNumber\.value\.substring\(5\)\);/,
    "const isMandiri = computed(() => false);"
);

// 2. Change Mandiri instructions
code = code.replace(
    /title: 'Cara Bayar Mandiri Bill Payment',[\s\S]*?tabs: \[[\s\S]*?\]\n                \}/,
    `title: 'Cara Bayar Mandiri Virtual Account',
            icon: 'bank',
            tabs: [
                {
                    name: "Livin' by Mandiri",
                    steps: [
                        "Buka aplikasi Livin' by Mandiri.",
                        'Pilih menu Transfer atau Bayar.',
                        'Pilih Virtual Account, masukkan nomor VA: ' + vaNumber.value + '.',
                        'Livin\\' by Mandiri akan otomatis mendeteksi nama perusahaan dan nominal tagihan.',
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
                }`
);

// 3. Import BankLogo
code = code.replace(
    /import { Head, Link, router } from '@inertiajs\/vue3';\nimport { computed, ref, onMounted, onUnmounted } from 'vue';\nimport { CheckCircleIcon, ClockIcon, DocumentDuplicateIcon, QrCodeIcon, CreditCardIcon, ArrowDownTrayIcon, ExclamationTriangleIcon, InformationCircleIcon, ChevronDownIcon } from '@heroicons\/vue\/24\/outline';/,
    `import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { CheckCircleIcon, ClockIcon, DocumentDuplicateIcon, QrCodeIcon, CreditCardIcon, ArrowDownTrayIcon, ExclamationTriangleIcon, InformationCircleIcon, ChevronDownIcon, DevicePhoneMobileIcon, BuildingLibraryIcon } from '@heroicons/vue/24/outline';
import BankLogo from '@/Components/BankLogo.vue';`
);

// 4. Update Badge
code = code.replace(
    /<div class="flex items-center gap-2">\n                            <span class="relative flex h-2\.5 w-2\.5">\n                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"><\/span>\n                                <span class="relative inline-flex rounded-full h-2\.5 w-2\.5 bg-yellow-500"><\/span>\n                            <\/span>\n                            <span class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">Menunggu Pembayaran<\/span>\n                        <\/div>/,
    `<div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-amber-50 border border-amber-200/60 dark:bg-amber-500/10 dark:border-amber-500/20 shadow-sm">
                            <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-widest">Menunggu Pembayaran</span>
                        </div>`
);

// 5. Update Bank Logo VA
code = code.replace(
    /<div class="h-10 w-10 bg-indigo-100 rounded-xl flex items-center justify-center dark:bg-indigo-900\/50 text-indigo-600 dark:text-indigo-400 flex-shrink-0">\n                                <CreditCardIcon class="w-6 h-6" \/>\n                            <\/div>/,
    `<div class="h-10 px-3 bg-white border border-gray-200 rounded-xl flex items-center justify-center dark:bg-white flex-shrink-0 shadow-sm">
                                <BankLogo :bank="bankCode" class="h-6 w-auto" />
                            </div>`
);

// 6. Update QRIS Logo
code = code.replace(
    /<div class="h-10 w-10 bg-pink-100 rounded-xl flex items-center justify-center dark:bg-pink-900\/50 text-pink-600 dark:text-pink-400 flex-shrink-0">\n                                <QrCodeIcon class="w-6 h-6" \/>\n                            <\/div>/,
    `<div class="h-10 px-3 bg-white border border-gray-200 rounded-xl flex items-center justify-center dark:bg-white flex-shrink-0 shadow-sm">
                                <BankLogo bank="QRIS" class="h-6 w-auto" />
                            </div>`
);

// 7. Update SVG Icon in Instructions
code = code.replace(
    /title: 'Cara Bayar QRIS',\n            icon: '📱',/,
    `title: 'Cara Bayar QRIS',
            icon: 'phone',`
);
code = code.replace(
    /title: 'Cara Bayar BCA Virtual Account',\n            icon: '🏦',/,
    `title: 'Cara Bayar BCA Virtual Account',
            icon: 'bank',`
);
code = code.replace(
    /title: 'Cara Bayar BNI Virtual Account',\n            icon: '🏦',/,
    `title: 'Cara Bayar BNI Virtual Account',
            icon: 'bank',`
);
code = code.replace(
    /<span class="text-xl">{{ paymentInstructions\.icon }}<\/span>/,
    `<div class="h-10 w-10 flex items-center justify-center rounded-xl flex-shrink-0" :class="paymentInstructions.icon === 'phone' ? 'bg-pink-100 text-pink-600 dark:bg-pink-900/50 dark:text-pink-400' : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400'">
                                <DevicePhoneMobileIcon v-if="paymentInstructions.icon === 'phone'" class="w-6 h-6" />
                                <BuildingLibraryIcon v-else-if="paymentInstructions.icon === 'bank'" class="w-6 h-6" />
                            </div>`
);

// 8. Restore PAID state details and link
code = code.replace(
    /<div v-if="invoice\.status === 'PAID'" class="bg-emerald-50 border border-emerald-200 rounded-3xl p-8 text-center shadow-lg dark:bg-emerald-900\/20 dark:border-emerald-800">\n                <div class="h-20 w-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 dark:bg-emerald-800\/50">\n                    <CheckCircleIcon class="h-10 w-10 text-emerald-600 dark:text-emerald-400" \/>\n                <\/div>\n                <h2 class="text-2xl font-bold text-emerald-800 dark:text-emerald-300 mb-2">Pembayaran Berhasil!<\/h2>\n                <p class="text-emerald-600 dark:text-emerald-400">Terima kasih, pembayaran tagihan Anda telah kami terima\.<\/p>\n                <div class="mt-8">\n                    <Link :href="route\('tagihan\.spp\.form'\)" class="inline-flex justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-all">\n                        Kembali ke Halaman Utama\n                    <\/Link>\n                <\/div>\n            <\/div>/,
    `<!-- PAID -->
            <div v-if="invoice.status === 'PAID'" class="bg-white border border-emerald-100 rounded-3xl p-8 text-center shadow-lg dark:bg-gray-800 dark:border-gray-700">
                <div class="h-24 w-24 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 dark:bg-emerald-900/30">
                    <CheckCircleIcon class="h-12 w-12 text-emerald-500 dark:text-emerald-400" />
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mb-2">Pembayaran Berhasil!</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Terima kasih, pembayaran tagihan Anda telah kami terima.</p>
                
                <!-- Detail Ringkas -->
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
            </div>`
);

// 9. Restore Description in PENDING state
code = code.replace(
    /<h2 class="text-4xl sm:text-5xl font-black bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">\n                                {{ amountFormatted }}\n                            <\/h2>/,
    `<h2 class="text-4xl sm:text-5xl font-black bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">
                                {{ amountFormatted }}
                            </h2>
                            <div class="mt-4 pt-4 border-t border-dashed border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ invoice.description }}</p>
                            </div>`
);

fs.writeFileSync('resources/js/Pages/Public/CustomPay.vue', code);
