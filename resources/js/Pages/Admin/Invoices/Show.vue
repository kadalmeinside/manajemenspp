<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeftIcon, DocumentArrowDownIcon, CheckCircleIcon, XCircleIcon, EnvelopeIcon, PrinterIcon } from '@heroicons/vue/20/solid';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toast from '@/Components/Toast.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    parentInvoice: { type: Object, default: null },
    companyInfo: { type: Object, required: true },
    can: { type: Object, required: true },
});

const formatCurrency = (amount) => {
    return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
};

const formatDate = (dateString, withTime = false) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    if (withTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return d.toLocaleDateString('id-ID', options) + (withTime ? ' WIB' : '');
};

// Modal Cancel
const showCancelModal = ref(false);
const cancelForm = useForm({});
const confirmCancel = () => { showCancelModal.value = true; };
const closeCancelModal = () => { showCancelModal.value = false; };
const executeCancel = () => {
    cancelForm.delete(route('admin.invoices.destroy', props.invoice.id), {
        onSuccess: () => closeCancelModal(),
    });
};

// Modal Mark as Paid
const showMarkPaidModal = ref(false);
const markPaidForm = useForm({ bukti_pembayaran: null });
const confirmMarkPaid = () => { showMarkPaidModal.value = true; };
const closeMarkPaidModal = () => { showMarkPaidModal.value = false; markPaidForm.reset(); };
const executeMarkPaid = () => {
    markPaidForm.post(route('admin.invoices.mark_as_paid', props.invoice.id), {
        onSuccess: () => closeMarkPaidModal(),
    });
};
const handleFileChange = (e) => { markPaidForm.bukti_pembayaran = e.target.files[0]; };

const handlePrint = () => { window.print(); };

</script>

<template>
    <Head title="Detail Tagihan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="flex items-center">
                    <Link :href="route('admin.invoices.index')" class="mr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <ArrowLeftIcon class="h-6 w-6" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Tagihan</h2>
                </div>
                <div class="flex space-x-2">
                    <button v-if="invoice.status === 'PENDING' && can?.create_invoice" @click="confirmCancel" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-red-300 dark:border-red-600 rounded-md font-semibold text-xs text-red-600 dark:text-red-400 uppercase tracking-widest shadow-sm hover:bg-red-50 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <XCircleIcon class="h-4 w-4 mr-2" /> Batalkan
                    </button>
                    <button v-if="invoice.status === 'PENDING' && can?.create_invoice" @click="confirmMarkPaid" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <CheckCircleIcon class="h-4 w-4 mr-2" /> Tandai Lunas
                    </button>
                    <a v-if="invoice.status === 'PAID'" :href="route('admin.pdf.invoice', invoice.id)" target="_blank" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <DocumentArrowDownIcon class="h-4 w-4 mr-2" /> Unduh Kuitansi
                    </a>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 print-container">
                
                <Toast />

                <!-- Bulk Payment Notice -->
                <div v-if="parentInvoice" class="mb-6 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Tagihan ini telah dilunasi secara tergabung dengan tagihan lain (Pembayaran Gabungan) pada <strong>{{ formatDate(parentInvoice.created_at, true) }}</strong> sejumlah <strong>{{ formatCurrency(parentInvoice.total_amount) }}</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Invoice Web Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl print-card relative">
                    
                    <!-- Decorative Top Border -->
                    <div class="h-2 w-full bg-gradient-to-r from-orange-600 to-amber-500"></div>

                    <div class="p-8 sm:p-12">
                        
                        <!-- Header / Kop -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-200 dark:border-gray-700 pb-8 mb-8">
                            <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                                <div v-if="companyInfo.logo" class="h-16 w-16">
                                    <img :src="companyInfo.logo" alt="Logo" class="h-full w-full object-contain" />
                                </div>
                                <div v-else class="h-16 w-16 bg-orange-50 border-2 border-orange-500 text-orange-500 rounded-full flex items-center justify-center font-bold text-2xl">
                                    {{ companyInfo.name.substring(0,1).toUpperCase() }}
                                </div>
                                <div>
                                    <h1 class="text-lg font-extrabold text-orange-600 dark:text-orange-500 uppercase tracking-wide">{{ companyInfo.name }}</h1>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ companyInfo.address }}<br>{{ companyInfo.contact }}</p>
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Dokumen Tagihan</p>
                                <h2 class="text-2xl font-black text-gray-800 dark:text-gray-100">INVOICE</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono mt-1">#{{ invoice.id.substring(0, 8).toUpperCase() }}</p>
                            </div>
                        </div>

                        <!-- Status Bar -->
                        <div class="mb-8 rounded-lg p-4 flex flex-col sm:flex-row justify-between items-center" 
                             :class="{
                                'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800': invoice.status === 'PAID',
                                'bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800': invoice.status === 'PENDING',
                                'bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800': invoice.status === 'EXPIRED',
                             }">
                            <div class="flex items-center">
                                <span v-if="invoice.status === 'PAID'" class="px-3 py-1 rounded-full bg-emerald-800 text-emerald-100 text-xs font-bold uppercase tracking-wider">LUNAS</span>
                                <span v-else-if="invoice.status === 'PENDING'" class="px-3 py-1 rounded-full bg-amber-500 text-white text-xs font-bold uppercase tracking-wider">MENUNGGU PEMBAYARAN</span>
                                <span v-else-if="invoice.status === 'EXPIRED'" class="px-3 py-1 rounded-full bg-red-600 text-white text-xs font-bold uppercase tracking-wider">KADALUARSA / DIBATALKAN</span>
                                <span v-else class="px-3 py-1 rounded-full bg-gray-500 text-white text-xs font-bold uppercase tracking-wider">{{ invoice.status }}</span>
                            </div>
                            <div class="mt-2 sm:mt-0 text-sm text-gray-600 dark:text-gray-300">
                                <span v-if="invoice.status === 'PAID'">Tanggal Bayar: <strong>{{ formatDate(invoice.paid_at, true) }}</strong></span>
                                <span v-else>Jatuh Tempo: <strong>{{ formatDate(invoice.due_date) }}</strong></span>
                            </div>
                        </div>

                        <!-- Customer & Invoice Meta -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-2 mb-3">Ditagihkan Kepada</h3>
                                <p class="font-extrabold text-gray-800 dark:text-gray-200 text-base mb-1">{{ invoice.siswa?.user?.name || 'Wali Siswa' }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                    Siswa: <strong class="text-gray-800 dark:text-gray-200">{{ invoice.siswa?.nama_siswa || '-' }}</strong><br>
                                    NIS: <strong class="text-gray-800 dark:text-gray-200">{{ invoice.siswa?.nis || '-' }}</strong><br>
                                    Kelas: <strong class="text-gray-800 dark:text-gray-200">{{ invoice.siswa?.kelas?.nama_kelas || '-' }}</strong>
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-2 mb-3 text-left md:text-right">Detail Transaksi</h3>
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400 text-left md:text-right">
                                    <p>Tanggal Dibuat: <strong class="text-gray-800 dark:text-gray-200">{{ formatDate(invoice.created_at) }}</strong></p>
                                    
                                    <!-- Payment Info Block -->
                                    <template v-if="invoice.status === 'PAID'">
                                        <p>
                                            Metode Pembayaran: 
                                            <strong class="text-gray-800 dark:text-gray-200 uppercase">
                                                <span v-if="invoice.payment_method === 'MANUAL'">Manual Transfer / Tunai</span>
                                                <span v-else>Payment Gateway ({{ invoice.payment_gateway || 'Xendit' }})</span>
                                            </strong>
                                        </p>
                                        <p v-if="invoice.payment_method === 'MANUAL' && invoice.bukti_pembayaran">
                                            Bukti Transfer: 
                                            <a :href="'/storage/' + invoice.bukti_pembayaran" target="_blank" class="text-blue-600 hover:underline">
                                                Lihat Lampiran
                                            </a>
                                        </p>
                                        <p v-else-if="invoice.payment_method !== 'MANUAL' && invoice.xendit_payment_url">
                                            Link Pembayaran: 
                                            <a :href="invoice.xendit_payment_url" target="_blank" class="text-blue-600 hover:underline">
                                                Buka Referensi Gateway
                                            </a>
                                        </p>
                                    </template>
                                    
                                    <p v-if="invoice.type">Tipe: <strong class="text-gray-800 dark:text-gray-200 uppercase">{{ invoice.type.replace(/_/g, ' ') }}</strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div class="mt-8 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12 text-center">No</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-40">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    
                                    <template v-if="invoice.child_invoices && invoice.child_invoices.length > 0">
                                        <tr v-for="(child, idx) in invoice.child_invoices" :key="child.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">{{ idx + 1 }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                {{ child.description }}
                                                <div v-if="child.periode_tagihan" class="text-xs text-gray-500 mt-1">Periode: {{ formatDate(child.periode_tagihan).split(' ').slice(1).join(' ') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ formatCurrency(child.amount) }}</td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">1</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                {{ invoice.description }}
                                                <div v-if="invoice.periode_tagihan" class="text-xs text-gray-500 mt-1">Periode: {{ formatDate(invoice.periode_tagihan).split(' ').slice(1).join(' ') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ formatCurrency(invoice.amount) }}</td>
                                        </tr>
                                    </template>

                                    <!-- Subtotal -->
                                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                                        <td colspan="2" class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right">Subtotal</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right font-medium">{{ formatCurrency(invoice.amount) }}</td>
                                    </tr>
                                    <!-- Admin Fee -->
                                    <tr v-if="invoice.admin_fee > 0" class="bg-gray-50 dark:bg-gray-800/50">
                                        <td colspan="2" class="px-6 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right">Biaya Admin</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right font-medium">{{ formatCurrency(invoice.admin_fee) }}</td>
                                    </tr>
                                    <!-- Total -->
                                    <tr class="bg-orange-50 dark:bg-orange-900/20 border-t-2 border-orange-200 dark:border-orange-800">
                                        <td colspan="2" class="px-6 py-4 whitespace-nowrap text-base font-bold text-gray-900 dark:text-gray-100 text-right">TOTAL</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-lg font-black text-orange-600 dark:text-orange-500 text-right">{{ formatCurrency(invoice.total_amount) }}</td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                        <!-- Payment Link CTA -->
                        <div v-if="invoice.status === 'PENDING' && invoice.xendit_payment_url_dynamic" class="mt-8 flex justify-end">
                            <a :href="invoice.xendit_payment_url_dynamic" target="_blank" class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white border border-transparent rounded-lg font-bold text-sm text-white dark:text-gray-900 uppercase tracking-widest shadow-md hover:bg-gray-700 dark:hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Bayar Sekarang / Buka Link
                                <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>

                        <!-- Footer / Notes -->
                        <div class="mt-12 pt-8 border-t border-dashed border-gray-300 dark:border-gray-600 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Catatan</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                    Dokumen ini diterbitkan secara otomatis oleh sistem {{ companyInfo.name }} dan sah tanpa tanda tangan basah.<br>
                                    Pembayaran yang sudah diterima tidak dapat dikembalikan (non-refundable).
                                </p>
                            </div>
                            <div class="text-center md:text-right flex flex-col justify-end items-center md:items-end">
                                <p class="text-xs text-orange-600 dark:text-orange-500 font-bold uppercase tracking-widest mb-10">Diterbitkan Oleh</p>
                                <div>
                                    <p class="font-bold text-gray-800 dark:text-gray-200 text-sm">Admin {{ companyInfo.name }}</p>
                                    <div class="h-px w-32 bg-gray-300 dark:bg-gray-600 mt-1 mb-1 ml-auto"></div>
                                    <p class="text-xs text-gray-400">Sistem Manajemen Akademi</p>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Bottom Decor -->
                    <div class="bg-gray-50 dark:bg-gray-900 py-4 px-8 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-400">
                        <div class="flex items-center">
                            <PrinterIcon class="h-4 w-4 mr-1 cursor-pointer hover:text-gray-600" @click="handlePrint" />
                            <span class="hidden sm:inline">Cetak Dokumen</span>
                        </div>
                        <div>Dicetak / Diakses: {{ formatDate(new Date(), true) }}</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Cancel -->
        <Modal :show="showCancelModal" @close="closeCancelModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                    <XCircleIcon class="h-6 w-6 text-red-500 mr-2" /> Konfirmasi Pembatalan
                </h2>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin membatalkan tagihan ini? Jika tagihan dibatalkan, tagihan pada payment gateway (Xendit) juga akan menjadi kadaluarsa dan tidak dapat dibayar lagi.
                </p>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closeCancelModal" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">Tutup</button>
                    <button type="button" @click="executeCancel" :disabled="cancelForm.processing" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none">Ya, Batalkan Tagihan</button>
                </div>
            </div>
        </Modal>

        <!-- Modal Mark as Paid -->
        <Modal :show="showMarkPaidModal" @close="closeMarkPaidModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                    <CheckCircleIcon class="h-6 w-6 text-emerald-500 mr-2" /> Konfirmasi Pembayaran Manual
                </h2>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Tandai invoice ini sebagai <strong>Lunas</strong> secara manual. Jika invoice memiliki link Xendit aktif, link tersebut akan dibatalkan/kadaluarsa.
                </p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Bukti Transfer (Opsional)</label>
                    <input type="file" @change="handleFileChange" accept="image/*,.pdf" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300"/>
                    <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, atau PDF (Maks: 2MB)</p>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closeMarkPaidModal" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">Batal</button>
                    <button type="button" @click="executeMarkPaid" :disabled="markPaidForm.processing" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none">Ya, Tandai Lunas</button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    body * { visibility: hidden; }
    .print-container, .print-container * { visibility: visible; }
    .print-container { position: absolute; left: 0; top: 0; width: 100%; }
    .print-card { box-shadow: none !important; border: 1px solid #e5e7eb; }
    nav, header, button, a { display: none !important; }
}
</style>
