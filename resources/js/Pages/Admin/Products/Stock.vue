<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    product: Object,
    movements: Object,
});

const form = useForm({
    variant_id: props.product.variants.length > 0 ? props.product.variants[0].id : '',
    type: 'restock',
    quantity: 1,
    reference_id: '',
});

const submit = () => {
    form.post(route('admin.products.stock.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('quantity', 'reference_id');
        }
    });
};

const formatDate = (dateString) => {
    return dayjs(dateString).format('D MMM YYYY, HH:mm');
};

const getTypeBadge = (type) => {
    switch(type) {
        case 'restock': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'sale': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'returned': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
        case 'adjustment': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getTypeText = (type) => {
    switch(type) {
        case 'restock': return 'Barang Masuk';
        case 'sale': return 'Penjualan';
        case 'returned': return 'Batal/Retur';
        case 'adjustment': return 'Penyesuaian';
        default: return type;
    }
};

const getQuantityClass = (qty) => {
    return qty > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <Head :title="`Kartu Stok: ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Kartu Stok: {{ product.name }}
                </h2>
                <Link :href="route('admin.products.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    &larr; Kembali ke Daftar Produk
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Panel Form Tambah/Ubah Stok -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">Input Perubahan Stok</h3>
                    
                    <form @submit.prevent="submit" class="flex flex-col md:flex-row gap-3 items-start md:items-end">
                        <div class="w-full md:w-1/4">
                            <InputLabel for="variant" value="Varian (Ukuran) *" class="text-xs" />
                            <select id="variant" v-model="form.variant_id" required class="mt-1 block w-full text-sm py-1.5 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option v-for="variant in product.variants" :key="variant.id" :value="variant.id">
                                    {{ variant.name }} (Stok: {{ variant.stock }})
                                </option>
                            </select>
                            <InputError :message="form.errors.variant_id" class="mt-1 text-xs" />
                        </div>
                        
                        <div class="w-full md:w-1/4">
                            <InputLabel for="type" value="Jenis *" class="text-xs" />
                            <select id="type" v-model="form.type" required class="mt-1 block w-full text-sm py-1.5 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="restock">Masuk (Restock)</option>
                                <option value="adjustment">Penyesuaian (+/-)</option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-1 text-xs" />
                        </div>
                        
                        <div class="w-full md:w-32">
                            <InputLabel for="quantity" value="Qty (+/-) *" class="text-xs" />
                            <TextInput id="quantity" type="number" v-model="form.quantity" required class="mt-1 block w-full text-sm py-1.5" placeholder="10 / -5" />
                            <InputError :message="form.errors.quantity" class="mt-1 text-xs" />
                        </div>
                        
                        <div class="w-full md:flex-1">
                            <InputLabel for="reference" value="Catatan (Opsional)" class="text-xs" />
                            <TextInput id="reference" type="text" v-model="form.reference_id" class="mt-1 block w-full text-sm py-1.5" placeholder="Nomor PO / Catatan" />
                            <InputError :message="form.errors.reference_id" class="mt-1 text-xs" />
                        </div>
                        
                        <div class="w-full md:w-auto">
                            <PrimaryButton :class="['bg-indigo-600 hover:bg-indigo-700 w-full md:w-auto justify-center', { 'opacity-25': form.processing }]" :disabled="form.processing || product.variants.length === 0" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                                Simpan
                            </PrimaryButton>
                        </div>
                    </form>
                    
                    <div v-if="product.variants.length === 0" class="mt-3 p-3 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-md text-xs border border-red-100 dark:border-red-800">
                        Produk belum memiliki varian/ukuran. Tambahkan varian dari menu Edit Produk.
                    </div>
                </div>

                <!-- Panel Tabel Riwayat -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Mutasi Stok (Kartu Stok)</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Varian</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jenis</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mutasi</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sisa Stok</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Oleh / Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="log in movements.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ log.variant.name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getTypeBadge(log.type)]">
                                            {{ getTypeText(log.type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center font-bold text-sm">
                                        <span :class="getQuantityClass(log.quantity)">
                                            {{ log.quantity > 0 ? '+' : '' }}{{ log.quantity }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-900 dark:text-white">
                                        {{ log.new_stock }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div>{{ log.user ? log.user.name : 'Sistem' }}</div>
                                        <div v-if="log.reference_id" class="text-xs italic mt-0.5">Ref: {{ log.reference_id }}</div>
                                    </td>
                                </tr>
                                <tr v-if="movements.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada riwayat mutasi stok untuk produk ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700" v-if="movements.links && movements.links.length > 3">
                        <div class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in movements.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm text-gray-400 border rounded dark:border-gray-700" v-html="link.label" />
                                <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300" :class="{ 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-900/50 dark:text-indigo-300': link.active }" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
