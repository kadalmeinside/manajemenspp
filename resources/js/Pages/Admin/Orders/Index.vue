<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { CheckBadgeIcon, ClockIcon, XCircleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    orders: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

watch([search, statusFilter], debounce(([newSearch, newStatus]) => {
    router.get(route('admin.orders.index'), { search: newSearch, status: newStatus }, { preserveState: true, replace: true });
}, 300));

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const formatDate = (dateString) => {
    return dayjs(dateString).format('D MMMM YYYY, HH:mm');
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'PAID':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800';
        case 'PENDING':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        case 'COMPLETED':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 border-gray-200 dark:border-gray-800';
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
            return 'Lunas (Siap Diambil)';
        case 'PENDING':
            return 'Menunggu Pembayaran';
        case 'COMPLETED':
            return 'Selesai (Sudah Diambil)';
        default:
            return status;
    }
};

const completeOrder = (order) => {
    if (confirm(`Apakah Anda yakin ingin menyelesaikan pesanan ${order.order_number}? Pastikan barang sudah diserahkan kepada siswa.`)) {
        router.patch(route('admin.orders.complete', order.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manajemen Pesanan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Pesanan Toko</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <div class="flex items-center gap-4 w-full sm:w-auto flex-grow">
                        <div class="relative w-full sm:w-96">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                            </div>
                            <input type="search" v-model="search" class="block w-full p-3 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 shadow-sm" placeholder="Cari nomor pesanan atau nama siswa...">
                        </div>
                        
                        <select v-model="statusFilter" class="block w-full sm:w-48 p-3 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 shadow-sm">
                            <option value="">Semua Status</option>
                            <option value="PENDING">Menunggu Pembayaran</option>
                            <option value="PAID">Lunas (Siap Diambil)</option>
                            <option value="COMPLETED">Selesai (Sudah Diambil)</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pesanan</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detail Item</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ order.order_number }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(order.created_at) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ order.siswa ? order.siswa.nama_siswa : 'Tidak diketahui' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Akun: {{ order.user.name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300">
                                            <li v-for="item in order.items" :key="item.id" class="mb-1">
                                                <span class="font-medium">{{ item.product.name }}</span> 
                                                <span class="text-gray-500 dark:text-gray-400">({{ item.variant.name }}) x {{ item.quantity }}</span>
                                            </li>
                                        </ul>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ formatRupiah(order.total_amount) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span :class="['px-2.5 py-1 rounded-full text-xs font-medium border inline-flex items-center', getStatusBadgeClass(order.status)]">
                                            <component :is="getStatusIcon(order.status)" class="w-4 h-4 mr-1.5" />
                                            {{ getStatusText(order.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <PrimaryButton 
                                            v-if="order.status === 'PAID'" 
                                            @click="completeOrder(order)" 
                                            class="bg-green-600 hover:bg-green-700 focus:ring-green-500"
                                        >
                                            Selesaikan
                                        </PrimaryButton>
                                        <a 
                                            v-else-if="order.status === 'PENDING' && order.payment_url"
                                            :href="order.payment_url"
                                            target="_blank"
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Bayar
                                        </a>
                                        <span v-else class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                    </td>
                                </tr>
                                <tr v-if="orders.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada data pesanan yang sesuai dengan filter Anda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-4" v-if="orders.links && orders.links.length > 3">
                    <div class="flex flex-wrap -mb-1">
                        <template v-for="(link, key) in orders.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded dark:border-gray-700 dark:text-gray-500" v-html="link.label" />
                            <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-900/50 dark:text-indigo-300 dark:border-indigo-700': link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
