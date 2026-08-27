<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PencilSquareIcon, TrashIcon, PlusIcon, TagIcon, CheckCircleIcon, XCircleIcon, InformationCircleIcon, ShoppingBagIcon, Squares2X2Icon, ListBulletIcon } from '@heroicons/vue/24/outline';

const viewMode = ref('list');

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.products.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showConfirmDelete = ref(false);
const productToDelete = ref(null);

const confirmDelete = (product) => {
    productToDelete.value = product;
    showConfirmDelete.value = true;
};

const deleteProduct = () => {
    router.delete(route('admin.products.destroy', productToDelete.value.id), {
        onSuccess: () => {
            showConfirmDelete.value = false;
        }
    });
};

const getTotalStock = (variants) => {
    if (!variants || variants.length === 0) return 0;
    return variants.reduce((sum, v) => sum + parseInt(v.stock), 0);
};

const getMainImage = (product) => {
    if (product.images && product.images.length > 0) {
        const primary = product.images.find(img => img.is_primary);
        return primary ? primary.image_path : product.images[0].image_path;
    }
    return product.image_path;
};
</script>

<template>
    <Head title="Manajemen Produk" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Produk / Merchandise</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto flex-grow">
                        <div class="relative w-full sm:w-80">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="search" v-model="search" class="block w-full p-3 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500 shadow-sm" placeholder="Cari nama produk...">
                        </div>
                        <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm border border-gray-200 dark:border-gray-700 shrink-0">
                            <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="p-2 rounded-md transition-colors" title="List View">
                                <ListBulletIcon class="w-5 h-5" />
                            </button>
                            <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="p-2 rounded-md transition-colors" title="Grid View">
                                <Squares2X2Icon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                    <Link :href="route('admin.products.create')" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm shrink-0">
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Tambah Produk
                    </Link>
                </div>

                <div v-if="viewMode === 'list'" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stok</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Varian</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="product in products.data" :key="product.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img v-if="getMainImage(product)" :src="'/storage/' + getMainImage(product)" alt="" class="h-10 w-10 rounded-md object-cover" />
                                                <div v-else class="h-10 w-10 rounded-md bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                    <ShoppingBagIcon class="h-6 w-6 text-gray-400" />
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ product.name }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    <span v-if="product.is_preorder" class="text-amber-600 dark:text-amber-400 font-semibold">Pre-Order Aktif</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                            {{ product.category.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold">
                                        {{ product.is_preorder ? '∞' : getTotalStock(product.variants) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <ul v-if="product.variants?.length" class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                            <li v-for="v in product.variants" :key="v.id" class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded">
                                                <span class="truncate pr-2 max-w-[100px]" :title="v.name">{{ v.name }}</span>
                                                <span class="font-bold shrink-0" :class="{'text-red-500': v.stock === 0 && !product.is_preorder}">
                                                    {{ product.is_preorder ? '∞' : v.stock }}
                                                </span>
                                            </li>
                                        </ul>
                                        <span v-else class="text-xs text-gray-400 italic">Tidak ada varian</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <CheckCircleIcon v-if="product.is_active" class="w-6 h-6 text-green-500 mx-auto" />
                                        <XCircleIcon v-else class="w-6 h-6 text-red-500 mx-auto" />
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <Link :href="route('admin.products.stock.index', product.id)" class="inline-flex items-center p-2 text-white bg-indigo-600 hover:bg-indigo-700 rounded-md focus:ring-4 focus:outline-none focus:ring-indigo-300 dark:focus:ring-indigo-800 transition-colors" title="Kelola Stok">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                            </svg>
                                        </Link>
                                        <Link :href="route('admin.products.edit', product.id)" class="inline-flex items-center p-2 text-white bg-amber-500 hover:bg-amber-600 rounded-md focus:ring-4 focus:outline-none focus:ring-amber-300 dark:focus:ring-amber-800 transition-colors" title="Edit Produk">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </Link>
                                        <button @click="confirmDelete(product)" class="inline-flex items-center p-2 text-white bg-red-600 hover:bg-red-700 rounded-md focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 transition-colors" title="Hapus Produk">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="products.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada data produk.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="product in products.data" :key="'grid-'+product.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col group relative">
                        <!-- Image -->
                        <div class="h-48 w-full bg-gray-50 dark:bg-gray-900 relative flex items-center justify-center p-4">
                            <span v-if="product.is_preorder" class="absolute top-2 left-2 bg-gradient-to-r from-amber-500 to-orange-400 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow z-10">Pre-Order</span>
                            <span :class="product.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="absolute top-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded shadow z-10">{{ product.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                            <img v-if="getMainImage(product)" :src="'/storage/' + getMainImage(product)" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300" />
                            <ShoppingBagIcon v-else class="w-16 h-16 text-gray-300 dark:text-gray-700" />
                        </div>
                        
                        <!-- Content -->
                        <div class="p-4 flex-grow flex flex-col">
                            <span class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-1">{{ product.category }}</span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-2 mb-2" :title="product.name">{{ product.name }}</h3>
                            
                            <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700/50">
                                <div class="flex justify-between items-end mb-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Stok: <span class="font-bold text-gray-900 dark:text-white">{{ product.is_preorder ? '∞' : getTotalStock(product.variants) }}</span></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Varian: <span class="font-bold text-gray-900 dark:text-white">{{ product.variants?.length || 0 }}</span></div>
                                </div>
                                
                                <div class="flex items-center space-x-2 mt-3">
                                    <Link :href="route('admin.products.stock.index', product.id)" class="flex-1 flex justify-center items-center py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white dark:bg-indigo-900/50 dark:text-indigo-300 dark:hover:bg-indigo-600 dark:hover:text-white rounded transition-colors text-xs font-semibold border border-transparent shadow-sm">
                                        Kelola Stok
                                    </Link>
                                    <Link :href="route('admin.products.edit', product.id)" class="p-1.5 text-gray-600 bg-gray-100 hover:bg-amber-500 hover:text-white dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-amber-600 rounded transition-colors border border-transparent shadow-sm" title="Edit">
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </Link>
                                    <button @click="confirmDelete(product)" class="p-1.5 text-gray-600 bg-gray-100 hover:bg-red-500 hover:text-white dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-red-600 rounded transition-colors border border-transparent shadow-sm" title="Hapus">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="viewMode === 'grid' && products.data.length === 0" class="text-center py-12 text-gray-500 bg-white dark:bg-gray-800 sm:rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                    Belum ada data produk.
                </div>

                <!-- Pagination -->
                <div class="mt-4" v-if="products.links.length > 3">
                    <div class="flex flex-wrap -mb-1">
                        <template v-for="(link, key) in products.links" :key="key">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded dark:border-gray-700 dark:text-gray-500" v-html="link.label" />
                            <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-900/50 dark:text-indigo-300 dark:border-indigo-700': link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showConfirmDelete" @close="showConfirmDelete = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Hapus Produk
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus produk <b>{{ productToDelete?.name }}</b>? Produk ini akan masuk ke Trash (Soft Delete).
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showConfirmDelete = false">Batal</SecondaryButton>
                    <DangerButton class="ml-3" @click="deleteProduct">Hapus Produk</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
