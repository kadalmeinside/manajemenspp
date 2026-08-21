<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBagIcon, CheckBadgeIcon, ClockIcon, XCircleIcon, CheckCircleIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    orders: Object,
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
    <Head title="Riwayat Pesanan Toko" />

    <SiswaLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-extrabold text-xl md:text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center">
                    <ClipboardDocumentListIcon class="h-6 w-6 md:h-8 md:w-8 mr-2 md:mr-3 text-indigo-600 dark:text-indigo-400" />
                    Riwayat Pesanan Merchandise
                </h2>
            </div>
        </template>

        <div class="py-8 md:py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                
                <div v-if="orders.data.length > 0" class="space-y-6 md:space-y-8">
                    <div v-for="order in orders.data" :key="order.id" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700/60 transform transition-all duration-300 hover:shadow-2xl">
                        
                        <!-- Order Header -->
                        <div class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-700/60 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-2">
                                    <span class="font-extrabold text-lg text-gray-900 dark:text-white">{{ order.order_number }}</span>
                                    <span :class="['px-3 py-1.5 rounded-full text-xs font-bold flex items-center w-max', getStatusBadgeClass(order.status)]">
                                        <component :is="getStatusIcon(order.status)" class="w-4 h-4 mr-1.5" />
                                        {{ getStatusText(order.status) }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dipesan pada <span class="text-gray-700 dark:text-gray-300">{{ formatDate(order.created_at) }}</span></p>
                            </div>
                            
                            <div class="text-left sm:text-right shrink-0">
                                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Total Belanja</p>
                                <p class="font-black text-indigo-600 dark:text-indigo-400 text-xl md:text-2xl">{{ formatRupiah(order.total_amount) }}</p>
                            </div>
                        </div>

                        <!-- Order Items Summary -->
                        <div class="p-6 md:p-8 cursor-pointer group" @click="$inertia.visit(route('siswa.store.orders.show', order.id))">
                            <div class="flex flex-row gap-4 sm:gap-6 group-hover:bg-gray-50/50 dark:group-hover:bg-gray-700/20 transition-colors rounded-xl px-2 -mx-2 py-2">
                                <div class="w-20 h-20 sm:w-28 sm:h-28 bg-gray-100 dark:bg-gray-900 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden shadow-inner p-2">
                                    <img v-if="order.items[0].product.image_path" :src="'/storage/' + order.items[0].product.image_path" :alt="order.items[0].product.name" class="w-full h-full object-contain drop-shadow-sm" />
                                    <ShoppingBagIcon v-else class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                                </div>
                                <div class="flex-grow flex flex-col justify-center">
                                    <div class="flex flex-col sm:flex-row justify-between items-start gap-2">
                                        <div class="pr-2">
                                            <h4 class="font-bold text-sm sm:text-lg text-gray-900 dark:text-white leading-tight line-clamp-2">{{ order.items[0].product.name }}</h4>
                                            <p class="text-[10px] sm:text-sm text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 bg-gray-100 dark:bg-gray-700 inline-block px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full font-medium">Varian: <span class="text-gray-800 dark:text-gray-200">{{ order.items[0].variant.name }}</span></p>
                                        </div>
                                        <div class="text-left sm:text-right shrink-0 mt-2 sm:mt-0">
                                            <p class="font-extrabold text-sm sm:text-lg text-gray-900 dark:text-white">{{ formatRupiah(order.items[0].subtotal) }}</p>
                                            <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1 font-medium">{{ order.items[0].quantity }} x {{ formatRupiah(order.items[0].unit_price) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="order.items.length > 1" class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 text-xs sm:text-sm font-bold text-indigo-500 dark:text-indigo-400">
                                        + {{ order.items.length - 1 }} produk lainnya
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Footer / Actions -->
                        <div class="bg-gray-50/80 dark:bg-gray-900/50 p-6 md:p-8 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row justify-between items-center gap-6">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 text-center sm:text-left flex items-center bg-white dark:bg-gray-800 px-4 py-3 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 w-full sm:w-auto">
                                <span v-if="order.status === 'PAID'">
                                    <CheckBadgeIcon class="w-5 h-5 text-green-500 inline mr-2" />
                                    Tunjukkan pesanan ini ke petugas untuk mengambil barang.
                                </span>
                                <span v-else-if="order.status === 'PENDING'">
                                    <ClockIcon class="w-5 h-5 text-amber-500 inline mr-2" />
                                    Menunggu pembayaran melalui Xendit.
                                </span>
                                <span v-else-if="order.status === 'COMPLETED'">
                                    <CheckCircleIcon class="w-5 h-5 text-blue-500 inline mr-2" />
                                    Pesanan telah selesai.
                                </span>
                            </div>
                            
                            <Link :href="route('siswa.store.orders.show', order.id)" class="w-full sm:w-auto flex justify-center items-center py-3.5 px-8 rounded-2xl text-base font-bold text-gray-700 dark:text-white shadow-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-md focus:outline-none">
                                Lihat Detail &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-24 px-4 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700/60 flex flex-col items-center justify-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-900 mb-6 shadow-inner">
                        <ShoppingBagIcon class="h-10 w-10 text-gray-400" />
                    </div>
                    <h3 class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white mb-2">Belum Ada Pesanan</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm text-sm md:text-base">Anda belum pernah melakukan pembelian merchandise. Yuk intip katalog kami sekarang!</p>
                    <Link :href="route('siswa.store.index')" class="inline-flex justify-center items-center py-3.5 px-8 rounded-2xl text-base font-bold text-white shadow-lg bg-gradient-to-r from-indigo-600 to-blue-500 hover:from-indigo-500 hover:to-blue-400 transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-xl focus:outline-none">
                        Mulai Belanja &rarr;
                    </Link>
                </div>

                <!-- Pagination -->
                <div class="mt-10 flex justify-center" v-if="orders.links && orders.links.length > 3">
                    <div class="flex flex-wrap gap-2">
                        <template v-for="(link, key) in orders.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2.5 text-sm font-bold text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl cursor-not-allowed shadow-sm" v-html="link.label" />
                            <Link v-else :href="link.url" class="px-4 py-2.5 text-sm font-bold border rounded-xl hover:shadow-md transition-all duration-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 shadow-md': link.active, 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </SiswaLayout>
</template>
