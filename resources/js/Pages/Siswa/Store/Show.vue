<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ShoppingBagIcon, ArrowLeftIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    product: Object,
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const mainImage = ref(null);
if (props.product.images && props.product.images.length > 0) {
    const primary = props.product.images.find(img => img.is_primary);
    mainImage.value = primary ? primary.image_path : props.product.images[0].image_path;
} else if (props.product.image_path) {
    mainImage.value = props.product.image_path;
}

const selectedVariant = ref(props.product.variants.length === 1 ? props.product.variants[0] : null);
const quantity = ref(1);

const form = useForm({
    product_id: props.product.id,
    product_variant_id: selectedVariant.value?.id || null,
    quantity: 1,
});

const isOutOfStock = computed(() => {
    if (props.product.is_preorder) return false;
    if (!selectedVariant.value) return false; // Not out of stock, just not selected yet
    return selectedVariant.value.stock < 1;
});

const maxQuantity = computed(() => {
    if (props.product.is_preorder) return 99;
    return selectedVariant.value ? selectedVariant.value.stock : 0;
});

const selectVariant = (variant) => {
    selectedVariant.value = variant;
    form.product_variant_id = variant.id;
    showVariantAlert.value = false;
    
    if (quantity.value > variant.stock && !props.product.is_preorder) {
        quantity.value = variant.stock;
    }
    if (quantity.value < 1 && variant.stock > 0) {
        quantity.value = 1;
    }
};

const incrementQuantity = () => {
    if (quantity.value < maxQuantity.value) {
        quantity.value++;
    }
};

const decrementQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const showSuccessAnim = ref(false);
const showVariantAlert = ref(false);

const addToCart = () => {
    if (!selectedVariant.value && props.product.variants.length > 1) {
        showVariantAlert.value = true;
        return;
    }
    
    form.quantity = quantity.value;
    form.post(route('siswa.store.cart.add'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessAnim.value = true;
            setTimeout(() => { showSuccessAnim.value = false; }, 3000);
        }
    });
};
</script>

<template>
    <Head :title="product.name" />

    <SiswaLayout>
        <div class="pt-4 pb-8 md:py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
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

                <div class="mb-6 md:mb-8">
                    <Link :href="route('siswa.store.index')" class="inline-flex items-center text-sm md:text-base font-bold text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                        <ArrowLeftIcon class="w-5 h-5 mr-2" />
                        Kembali ke Katalog
                    </Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700/60">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                        
                        <!-- Product Image Box -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 flex flex-col items-center justify-between relative min-h-[400px] md:min-h-full overflow-hidden p-6 md:p-8">
                            <span v-if="product.is_preorder" class="absolute top-6 left-6 bg-gradient-to-r from-amber-500 to-orange-400 text-white text-xs md:text-sm font-bold px-4 py-1.5 rounded-full shadow-lg z-10">
                                Pre-Order Aktif
                            </span>
                            
                            <div class="flex-grow flex items-center justify-center w-full relative mb-6 min-h-[300px] md:min-h-[450px]">
                                <transition name="fade" mode="out-in">
                                    <img :key="mainImage" v-if="mainImage" :src="'/storage/' + mainImage" :alt="product.name" class="w-full h-full max-h-[400px] md:max-h-[550px] object-contain drop-shadow-2xl" />
                                    <ShoppingBagIcon v-else class="w-40 h-40 text-gray-300 dark:text-gray-700 drop-shadow-sm" />
                                </transition>
                            </div>

                            <!-- Thumbnails -->
                            <div v-if="product.images && product.images.length > 1" class="flex gap-3 overflow-x-auto pb-2 w-full max-w-full justify-center scrollbar-hide snap-x">
                                <button v-for="img in product.images" :key="img.id" @click="mainImage = img.image_path"
                                    class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border-2 transition-all duration-200 snap-center focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                    :class="mainImage === img.image_path ? 'border-red-500 shadow-md scale-105 opacity-100' : 'border-gray-200 dark:border-gray-700 hover:border-red-300 opacity-60 hover:opacity-100'">
                                    <img :src="'/storage/' + img.image_path" class="w-full h-full object-cover bg-white" />
                                </button>
                            </div>
                        </div>

                        <!-- Product Info Box -->
                        <div class="p-6 md:p-12 lg:p-16 flex flex-col">
                            <span class="text-xs md:text-sm font-bold text-red-500 dark:text-red-400 mb-2 uppercase tracking-widest">{{ product.category }}</span>
                            <h1 class="text-2xl md:text-4xl font-extrabold text-gray-900 dark:text-white mb-4 leading-tight">{{ product.name }}</h1>
                            
                            <div class="mb-6">
                                <span v-if="selectedVariant" class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">
                                    {{ formatRupiah(selectedVariant.price) }}
                                </span>
                                <span v-else class="text-lg font-bold text-gray-500 dark:text-gray-400">
                                    Mulai dari <span class="text-xl text-gray-800 dark:text-gray-200">{{ formatRupiah(Math.min(...product.variants.map(v => v.price))) }}</span>
                                </span>
                            </div>

                            <div v-if="product.description" class="prose prose-sm dark:prose-invert text-gray-600 dark:text-gray-400 mb-8">
                                <p>{{ product.description }}</p>
                            </div>

                            <div class="mb-8 md:mb-10">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-xs md:text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Varian / Ukuran</h3>
                                    <span v-if="showVariantAlert" class="text-xs font-bold text-red-500 dark:text-red-400 animate-pulse bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-md">Pilih varian wajib diisi!</span>
                                </div>
                                <div class="flex flex-wrap gap-3 p-1" :class="{'ring-2 ring-red-500 rounded-2xl ring-offset-2 dark:ring-offset-gray-800 transition-all duration-300': showVariantAlert}">
                                    <button 
                                        v-for="variant in product.variants" 
                                        :key="variant.id"
                                        @click="selectVariant(variant)"
                                        :class="[
                                            'px-5 py-2.5 rounded-xl border text-sm font-bold transition-all duration-300 ease-in-out',
                                            selectedVariant?.id === variant.id 
                                                ? 'bg-gray-900 border-gray-900 text-white dark:bg-white dark:border-white dark:text-gray-900 shadow-md transform scale-105' 
                                                : 'bg-white border-gray-200 text-gray-700 hover:border-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:border-white',
                                            (!product.is_preorder && variant.stock < 1) ? 'opacity-40 cursor-not-allowed' : ''
                                        ]"
                                    >
                                        {{ variant.name }} 
                                        <span v-if="variant.sku" class="text-xs opacity-70 ml-1">({{ variant.sku }})</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Availability & Quantity -->
                            <div class="mt-auto pt-8 border-t border-gray-100 dark:border-gray-700/60">
                                
                                <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                                    <div>
                                        <h3 class="text-xs md:text-sm font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">Jumlah</h3>
                                        <div class="flex items-center bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl w-fit p-1">
                                            <button @click="decrementQuantity" :disabled="quantity <= 1 || isOutOfStock" class="p-2 w-10 h-10 flex items-center justify-center rounded-lg text-gray-600 hover:bg-white hover:shadow-sm dark:hover:bg-gray-800 disabled:opacity-50 transition-all font-bold text-xl">
                                                -
                                            </button>
                                            <input type="number" v-model="quantity" readonly class="w-12 text-center bg-transparent border-none text-base font-extrabold focus:ring-0 text-gray-900 dark:text-white p-0">
                                            <button @click="incrementQuantity" :disabled="quantity >= maxQuantity || isOutOfStock" class="p-2 w-10 h-10 flex items-center justify-center rounded-lg text-gray-600 hover:bg-white hover:shadow-sm dark:hover:bg-gray-800 disabled:opacity-50 transition-all font-bold text-xl">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div v-if="!selectedVariant && product.variants.length > 1" class="flex items-center text-sm font-bold text-gray-500 dark:text-gray-400">
                                            Pilih varian untuk melihat stok
                                        </div>
                                        <div v-else-if="product.is_preorder" class="flex items-center text-sm font-bold text-amber-600 dark:text-amber-500">
                                            <CheckCircleIcon class="w-5 h-5 mr-1.5" />
                                            Pre-Order Tersedia
                                        </div>
                                        <div v-else-if="!isOutOfStock" class="flex items-center text-sm font-bold text-green-600 dark:text-green-500">
                                            <CheckCircleIcon class="w-5 h-5 mr-1.5" />
                                            Sisa Stok: {{ selectedVariant?.stock }}
                                        </div>
                                        <div v-else class="flex items-center text-sm font-bold text-red-600 dark:text-red-500">
                                            <XCircleIcon class="w-5 h-5 mr-1.5" />
                                            Stok Habis
                                        </div>
                                    </div>
                                </div>

                                <button 
                                    @click="addToCart"
                                    :disabled="isOutOfStock || form.processing" 
                                    class="w-full flex justify-center items-center py-4 px-6 rounded-2xl text-base md:text-lg font-bold text-white shadow-lg disabled:bg-gray-400 dark:disabled:bg-gray-600 transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-red-500/50"
                                    :class="[isOutOfStock ? 'bg-gray-400' : 'bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-500 hover:to-rose-400']"
                                >
                                    <ShoppingBagIcon class="w-6 h-6 mr-2" />
                                    {{ isOutOfStock ? 'Stok Habis' : 'Masukkan ke Keranjang' }}
                                </button>
                                
                                <div v-if="$page.props.flash?.error" class="mt-4 text-sm text-red-600 bg-red-50 p-3 rounded-md">
                                    {{ $page.props.flash.error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </SiswaLayout>
</template>
