<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBagIcon, CheckBadgeIcon, ClockIcon, XCircleIcon, CheckCircleIcon, ArrowLeftIcon, DocumentTextIcon, CreditCardIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    order: Object,
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const formatDate = (dateString) => {
    return dayjs(dateString).format('D MMMM YYYY, HH:mm');
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'PAID':
            return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 border border-green-200 dark:border-green-800 shadow-sm';
        case 'PENDING':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800 shadow-sm';
        case 'COMPLETED':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800 shadow-sm';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-900/40 dark:text-gray-400 border border-gray-200 dark:border-gray-800 shadow-sm';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'PAID':
            return CheckBadgeIcon;
        case 'PENDING':
            return ClockIcon;
        case 'COMPLETED':
            return CheckCircleIcon;
        default:
            return XCircleIcon;
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'PAID':
            return 'Lunas (Menunggu Diambil)';
        case 'PENDING':
            return 'Menunggu Pembayaran';
        case 'COMPLETED':
            return 'Selesai (Sudah Diambil)';
        default:
            return status;
    }
};
</script>

<template>
    <Head :title="'Detail Pesanan ' + order.order_number" />

    <SiswaLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-extrabold text-xl md:text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center">
                    <DocumentTextIcon class="h-6 w-6 md:h-8 md:w-8 mr-2 md:mr-3 text-red-600 dark:text-red-400" />
                    Detail Pesanan
                </h2>
            </div>
        </template>

        <div class="py-4 md:py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                
                <div class="mb-6 md:mb-8">
                    <Link :href="route('siswa.store.orders.index')" class="inline-flex items-center text-sm md:text-base font-bold text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                        <ArrowLeftIcon class="w-5 h-5 mr-2" />
                        Kembali ke Riwayat
                    </Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700/60">
                    
                    <!-- Order Header -->
                    <div class="p-6 md:p-10 border-b border-gray-100 dark:border-gray-700/60 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800 flex flex-col sm:flex-row justify-between items-start gap-6">
                        <div>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">No. Pesanan</p>
                            <h3 class="font-black text-2xl md:text-3xl text-gray-900 dark:text-white mb-3">{{ order.order_number }}</h3>
                            <span :class="['px-3 py-1.5 rounded-full text-xs font-bold flex items-center w-max', getStatusBadgeClass(order.status)]">
                                <component :is="getStatusIcon(order.status)" class="w-4 h-4 mr-1.5" />
                                {{ getStatusText(order.status) }}
                            </span>
                        </div>
                        <div class="text-left sm:text-right w-full sm:w-auto">
                            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Tanggal Pembelian</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200">{{ formatDate(order.created_at) }}</p>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div class="p-6 md:p-10 border-b border-gray-100 dark:border-gray-700/60">
                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white mb-6 flex items-center">
                            <ShoppingBagIcon class="w-5 h-5 mr-2 text-red-500" /> Daftar Produk
                        </h3>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <div v-for="item in order.items" :key="item.id" class="py-6 first:pt-0 last:pb-0 flex flex-row gap-4 sm:gap-6 hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors rounded-xl px-2 -mx-2">
                                <div class="w-20 h-20 sm:w-28 sm:h-28 bg-gray-100 dark:bg-gray-900 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden shadow-inner p-2">
                                    <img v-if="item.product.image_path" :src="'/storage/' + item.product.image_path" :alt="item.product.name" class="w-full h-full object-contain drop-shadow-sm" />
                                    <ShoppingBagIcon v-else class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                                </div>
                                <div class="flex-grow flex flex-col justify-center">
                                    <div class="flex flex-col sm:flex-row justify-between items-start gap-2">
                                        <div class="pr-2">
                                            <h4 class="font-bold text-sm sm:text-lg text-gray-900 dark:text-white leading-tight line-clamp-2">{{ item.product.name }}</h4>
                                            <p class="text-[10px] sm:text-sm text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 bg-gray-100 dark:bg-gray-700 inline-block px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full font-medium">Varian: <span class="text-gray-800 dark:text-gray-200">{{ item.variant.name }}</span></p>
                                        </div>
                                        <div class="text-left sm:text-right shrink-0 mt-2 sm:mt-0">
                                            <p class="font-extrabold text-sm sm:text-lg text-gray-900 dark:text-white">{{ formatRupiah(item.subtotal) }}</p>
                                            <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1 font-medium">{{ item.quantity }} x {{ formatRupiah(item.unit_price) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary & Actions -->
                    <div class="p-6 md:p-10 bg-gray-50/50 dark:bg-gray-900/30 flex flex-col md:flex-row justify-between gap-8 md:gap-12">
                        <!-- Summary -->
                        <div class="flex-grow order-2 md:order-1">
                            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white mb-6 flex items-center">
                                <CreditCardIcon class="w-5 h-5 mr-2 text-red-500" /> Rincian Pembayaran
                            </h3>
                            <div class="space-y-3 text-sm md:text-base">
                                <div class="flex justify-between text-gray-600 dark:text-gray-400 font-medium">
                                    <span>Subtotal Produk</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ formatRupiah(order.total_amount - (order.fee_amount || 0)) }}</span>
                                </div>
                                <div v-if="order.fee_amount" class="flex justify-between text-gray-600 dark:text-gray-400 font-medium">
                                    <span>Biaya Layanan</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ formatRupiah(order.fee_amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-4 mt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span class="font-bold text-gray-900 dark:text-white text-base md:text-lg">Total Belanja</span>
                                    <span class="font-black text-red-600 dark:text-red-400 text-xl md:text-2xl">{{ formatRupiah(order.total_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Box -->
                        <div class="w-full md:w-72 flex-shrink-0 order-1 md:order-2 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 flex flex-col justify-center h-full">
                            <template v-if="order.status === 'PENDING'">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4 text-center">Segera selesaikan pembayaran Anda untuk memproses pesanan.</p>
                                <a v-if="order.payment_url" :href="order.payment_url" target="_blank" class="w-full flex justify-center items-center py-3.5 px-6 rounded-2xl text-base font-bold text-white shadow-lg bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-500 hover:to-rose-400 transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-xl focus:outline-none text-center">
                                    Bayar Sekarang &rarr;
                                </a>
                            </template>
                            <template v-else-if="order.status === 'PAID'">
                                <div class="text-center">
                                    <CheckBadgeIcon class="w-12 h-12 text-green-500 mx-auto mb-3" />
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Pembayaran Berhasil</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Silakan tunjukkan nomor pesanan ini ke petugas di tempat latihan untuk mengambil barang.</p>
                                </div>
                            </template>
                            <template v-else-if="order.status === 'COMPLETED'">
                                <div class="text-center">
                                    <CheckCircleIcon class="w-12 h-12 text-blue-500 mx-auto mb-3" />
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Pesanan Selesai</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Terima kasih telah berbelanja merchandise kami.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </SiswaLayout>
</template>
