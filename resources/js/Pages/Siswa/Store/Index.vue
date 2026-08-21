<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { ShoppingBagIcon, MagnifyingGlassIcon, ShoppingCartIcon, CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('siswa.store.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const getLowestPrice = (variants) => {
    if (!variants || variants.length === 0) return 0;
    return Math.min(...variants.map(v => parseFloat(v.price)));
};

const form = useForm({
    product_id: null,
    product_variant_id: null,
    quantity: 1,
});

const showVariantModal = ref(false);
const selectedProduct = ref(null);
const selectedVariant = ref(null);
const showSuccessAnim = ref(false);

const openVariantModal = (product) => {
    selectedProduct.value = product;
    selectedVariant.value = null;
    showVariantModal.value = true;
};

const closeVariantModal = () => {
    showVariantModal.value = false;
    setTimeout(() => {
        selectedProduct.value = null;
        selectedVariant.value = null;
    }, 300);
};

const submitAddToCart = (productId, variantId) => {
    form.product_id = productId;
    form.product_variant_id = variantId;
    form.quantity = 1;
    
    form.post(route('siswa.store.cart.add'), {
        preserveScroll: true,
        onSuccess: () => {
            if (showVariantModal.value) closeVariantModal();
            showSuccessAnim.value = true;
            setTimeout(() => { showSuccessAnim.value = false; }, 3000);
        }
    });
};

const handleAddToCartClick = (product) => {
    if (product.variants && product.variants.length === 1) {
        submitAddToCart(product.id, product.variants[0].id);
    } else if (product.variants && product.variants.length > 1) {
        openVariantModal(product);
    }
};

const isVariantDisabled = (product, variant) => {
    if (product.is_preorder) return false;
    return variant.stock < 1;
};
</script>

<template>
    <Head title="Toko & Merchandise" />

    <SiswaLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-extrabold text-xl md:text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center">
                    <ShoppingBagIcon class="h-6 w-6 md:h-8 md:w-8 mr-2 md:mr-3 text-indigo-600 dark:text-indigo-400" />
                    Toko Merchandise
                </h2>
            </div>
        </template>

        <div class="py-8 md:py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                
                <!-- Success Toast -->
                <transition 
                    enter-active-class="transition ease-out duration-300 transform"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 sm:scale-100"
                    leave-to-class="opacity-0 sm:scale-95"
                >
                    <div v-if="showSuccessAnim" class="fixed bottom-24 left-1/2 transform -translate-x-1/2 z-50 md:bottom-auto md:top-24 flex items-center bg-gray-900/90 backdrop-blur-md text-white px-6 py-4 rounded-2xl shadow-2xl border border-white/10">
                        <div class="bg-green-500 rounded-full p-1 mr-3">
                            <CheckCircleIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="font-bold">Berhasil Ditambahkan</p>
                            <p class="text-sm text-gray-300">Cek keranjang belanja Anda.</p>
                        </div>
                    </div>
                </transition>
                
                <!-- Search Bar -->
                <div class="mb-10 relative max-w-2xl mx-auto">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-6 w-6 text-gray-400" />
                    </div>
                    <input type="text" v-model="search" placeholder="Cari seragam, buku, alat tulis..." 
                        class="block w-full pl-12 pr-4 py-4 border border-gray-200 dark:border-gray-700 rounded-2xl leading-5 bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl text-gray-900 dark:text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg shadow-sm hover:shadow-md transition duration-300 ease-in-out">
                </div>

                <!-- Product Grid -->
                <div v-if="products.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-8">
                    <Link 
                        v-for="product in products.data" 
                        :key="product.id"
                        :href="route('siswa.store.show', product.slug)"
                        class="group bg-white dark:bg-gray-800 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden border border-gray-100 dark:border-gray-700/60 flex flex-col relative"
                    >
                        <!-- Product Image Box -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 flex items-center justify-center relative w-full pt-[100%] overflow-hidden">
                            <span v-if="product.is_preorder" class="absolute top-3 left-3 bg-gradient-to-r from-amber-500 to-orange-400 text-white text-[10px] md:text-xs font-bold px-2 md:px-3 py-1 rounded-full shadow-md z-10">Pre-Order</span>
                            <div class="absolute inset-0 p-4 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                                <img v-if="product.image_path" :src="'/storage/' + product.image_path" :alt="product.name" class="w-full h-full object-contain drop-shadow-md" />
                                <ShoppingBagIcon v-else class="w-16 h-16 text-gray-300 dark:text-gray-600 drop-shadow-sm" />
                            </div>
                        </div>
                        
                        <!-- Product Details -->
                        <div class="p-4 md:p-5 flex-grow flex flex-col">
                            <span class="text-[10px] md:text-xs font-bold text-indigo-500 dark:text-indigo-400 mb-1 uppercase tracking-widest">{{ product.category }}</span>
                            <h3 class="text-sm md:text-lg font-bold text-gray-900 dark:text-white mb-3 line-clamp-2 leading-snug group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ product.name }}</h3>
                            
                            <div class="mt-auto pt-3 flex items-end justify-between border-t border-gray-100 dark:border-gray-700/50">
                                <div>
                                    <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 mb-0.5 font-medium">Mulai dari</p>
                                    <p class="text-base md:text-xl font-extrabold text-gray-900 dark:text-white">{{ formatRupiah(getLowestPrice(product.variants)) }}</p>
                                </div>
                                <button 
                                    @click.prevent="handleAddToCartClick(product)"
                                    class="p-2 md:p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 transition-all shadow-sm transform hover:scale-110 active:scale-95"
                                    title="Tambah ke Keranjang"
                                >
                                    <ShoppingCartIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <ShoppingBagIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Belum ada produk</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada produk yang cocok dengan pencarian Anda atau toko sedang kosong.</p>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex justify-center" v-if="products.links.length > 3">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, key) in products.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md cursor-not-allowed" v-html="link.label" />
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" :class="{ 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-900/50 dark:text-indigo-300 dark:border-indigo-700': link.active, 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700': !link.active }" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>
        </div>

        <Modal :show="showVariantModal" @close="closeVariantModal" maxWidth="md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Pilih Varian</h3>
                    <button @click="closeVariantModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>
                
                <div v-if="selectedProduct">
                    <div class="flex items-center gap-4 mb-6">
                        <img v-if="selectedProduct.image_path" :src="'/storage/' + selectedProduct.image_path" class="w-16 h-16 rounded-xl object-contain bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700" />
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white leading-tight line-clamp-2">{{ selectedProduct.name }}</p>
                            <p class="text-sm text-indigo-600 dark:text-indigo-400 font-bold mt-1">{{ formatRupiah(selectedVariant ? selectedVariant.price : getLowestPrice(selectedProduct.variants)) }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-8">
                        <button 
                            v-for="variant in selectedProduct.variants" 
                            :key="variant.id"
                            @click="!isVariantDisabled(selectedProduct, variant) ? selectedVariant = variant : null"
                            :class="[
                                'px-3 py-2.5 rounded-xl border-2 text-sm font-bold transition-all duration-200 ease-in-out',
                                selectedVariant?.id === variant.id 
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-md transform scale-[1.02]' 
                                    : 'bg-white border-gray-200 text-gray-700 hover:border-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:border-white',
                                isVariantDisabled(selectedProduct, variant) ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                        >
                            {{ variant.name }} 
                        </button>
                    </div>
                    
                    <PrimaryButton 
                        @click="submitAddToCart(selectedProduct.id, selectedVariant.id)"
                        class="w-full justify-center py-4 text-base"
                        :disabled="!selectedVariant || form.processing"
                    >
                        Masukkan Keranjang
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </SiswaLayout>
</template>
