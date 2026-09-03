<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { BanknotesIcon, CreditCardIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    products: Array,
    siswas: Array,
});

const page = usePage();

const cart = ref([]);
const selectedSiswa = ref('');
const paymentMethod = ref('CASH');
const searchQuery = ref(''); // untuk siswa

const errorSiswa = ref(false);
const errorCart = ref(false);
const isCashModalOpen = ref(false);
const uangDiterimaStr = ref('');

const uangDiterima = computed(() => {
    return uangDiterimaStr.value ? parseInt(uangDiterimaStr.value.replace(/\D/g, ''), 10) : 0;
});

const formatUangInput = (e) => {
    let rawValue = e.target.value.replace(/\D/g, '');
    if (rawValue) {
        uangDiterimaStr.value = parseInt(rawValue, 10).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    } else {
        uangDiterimaStr.value = '';
    }
};

const cashKembalian = computed(() => {
    return Math.max(0, uangDiterima.value - cartTotal.value);
});

// Search dan Filter Produk
const productSearchQuery = ref('');
const selectedCategory = ref('Semua');

// Kategori unik dari produk yang ada
const uniqueCategories = computed(() => {
    const categories = props.products.map(p => p.category).filter(c => c);
    return ['Semua', ...new Set(categories)];
});

const filteredProducts = computed(() => {
    return props.products.filter(product => {
        const matchCategory = selectedCategory.value === 'Semua' || product.category === selectedCategory.value;
        const matchSearch = product.name.toLowerCase().includes(productSearchQuery.value.toLowerCase());
        return matchCategory && matchSearch;
    });
});

// Filter siswas untuk dropdown search
const filteredSiswas = computed(() => {
    if (searchQuery.value === '') {
        return props.siswas; 
    }
    const query = searchQuery.value.toLowerCase();
    return props.siswas.filter(s => 
        s.name.toLowerCase().includes(query) || 
        s.nis?.toLowerCase().includes(query) ||
        s.kelas.toLowerCase().includes(query)
    );
});

const dropdownOpen = ref(false);

const selectSiswa = (siswa) => {
    errorSiswa.value = false;
    selectedSiswa.value = siswa.id;
    searchQuery.value = `${siswa.name} (${siswa.kelas})`;
    dropdownOpen.value = false;
};

// Modal Varian
const isVariantModalOpen = ref(false);
const selectedProductForVariant = ref(null);

const handleProductClick = (product) => {
    if (product.variants.length === 1) {
        // Langsung tambah jika cuma 1 varian
        addToCart(product, product.variants[0]);
    } else if (product.variants.length > 1) {
        // Buka modal jika > 1 varian
        selectedProductForVariant.value = product;
        isVariantModalOpen.value = true;
    }
};

const closeVariantModal = () => {
    isVariantModalOpen.value = false;
    selectedProductForVariant.value = null;
};

const selectVariantAndAddToCart = (variant) => {
    addToCart(selectedProductForVariant.value, variant);
    closeVariantModal();
};

const addToCart = (product, variant) => {
    errorCart.value = false;
    const existingItem = cart.value.find(item => item.variant_id === variant.id);
    if (existingItem) {
        if (existingItem.quantity < variant.stock || product.is_preorder) {
            existingItem.quantity++;
        }
    } else {
        cart.value.push({
            product_id: product.id,
            variant_id: variant.id,
            name: product.name,
            variant_name: variant.name,
            price: variant.price,
            quantity: 1,
            max_stock: product.is_preorder ? 9999 : variant.stock,
        });
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const updateQuantity = (index, change) => {
    const item = cart.value[index];
    const newQty = item.quantity + change;
    if (newQty > 0 && newQty <= item.max_stock) {
        item.quantity = newQty;
    } else if (newQty === 0) {
        removeFromCart(index);
    }
};

const cartTotal = computed(() => {
    return cart.value.reduce((total, item) => total + (item.price * item.quantity), 0);
});

const form = useForm({
    siswa_id: '',
    payment_method: 'CASH',
    items: [],
});

const submitCheckout = () => {
    errorSiswa.value = !selectedSiswa.value;
    errorCart.value = cart.value.length === 0;

    if (errorSiswa.value || errorCart.value) {
        return;
    }

    if (paymentMethod.value === 'CASH') {
        isCashModalOpen.value = true;
        uangDiterimaStr.value = cartTotal.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return;
    }
    
    executeCheckout();
};

const confirmCashCheckout = () => {
    executeCheckout(uangDiterima.value);
};

const executeCheckout = (uang_diterima = null) => {
    form.siswa_id = selectedSiswa.value;
    form.payment_method = paymentMethod.value;
    form.items = cart.value.map(item => ({
        variant_id: item.variant_id,
        quantity: item.quantity,
    }));
    
    let payload = { preserveScroll: true, onSuccess: handleSuccess };
    
    // Add uang_diterima via transform since it's not in the base form
    form.transform((data) => ({
        ...data,
        uang_diterima: uang_diterima
    })).post(route('admin.pos.store'), payload);
};

const handleSuccess = (page) => {
    cart.value = [];
    selectedSiswa.value = '';
    searchQuery.value = '';
    paymentMethod.value = 'CASH';
    isCashModalOpen.value = false;
    // Note: Kita tidak menggunakan window.open() otomatis di sini
    // karena browser (terutama Safari/Chrome) sering memblokirnya (Popup Blocker).
    // Tombol link akan muncul di Flash Message (atas) agar admin bisa mengkliknya secara manual.
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

// Close dropdown if clicking outside
import { onMounted, onBeforeUnmount } from 'vue';
const closeDropdown = (e) => {
    if (!e.target.closest('.searchable-dropdown')) {
        dropdownOpen.value = false;
    }
};
onMounted(() => document.addEventListener('click', closeDropdown));
onBeforeUnmount(() => document.removeEventListener('click', closeDropdown));

</script>

<template>
    <Head title="Kasir Toko (POS)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Kasir Toko (POS)</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Flash Messages -->
                <div v-if="page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
                    <div class="flex items-center">
                        <CheckCircleIcon class="h-5 w-5 mr-2" />
                        <span class="block sm:inline font-bold">{{ page.props.flash.success }}</span>
                    </div>
                    <div v-if="page.props.flash?.payment_url" class="mt-3 ml-7">
                        <a :href="page.props.flash.payment_url" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md">
                            Buka Layar Pembayaran (Online) &rarr;
                        </a>
                        <p class="mt-1 text-xs text-green-800">Silakan klik tombol di atas untuk membuka pembayaran, atau arahkan siswa mengecek tagihan di portal/email mereka.</p>
                    </div>
                </div>
                <div v-if="page.props.flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative flex items-center shadow-sm" role="alert">
                    <XCircleIcon class="h-5 w-5 mr-2" />
                    <span class="block sm:inline">{{ page.props.flash.error }}</span>
                </div>


                <div class="flex flex-col lg:flex-row gap-6">
                    
                    <!-- Kiri: Katalog Produk -->
                    <div class="w-full lg:w-2/3">
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white shrink-0">Pilih Produk</h3>
                                <div class="w-full md:w-64">
                                    <input 
                                        type="text" 
                                        v-model="productSearchQuery" 
                                        placeholder="Cari produk..."
                                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    >
                                </div>
                            </div>
                            
                            <!-- Kategori Filter -->
                            <div class="flex overflow-x-auto pb-3 mb-3 gap-2 no-scrollbar border-b border-gray-100 dark:border-gray-700">
                                <button 
                                    v-for="cat in uniqueCategories" 
                                    :key="cat"
                                    @click="selectedCategory = cat"
                                    :class="selectedCategory === cat ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="px-3 py-1.5 rounded-full text-xs font-medium border whitespace-nowrap transition-colors"
                                >
                                    {{ cat }}
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-4 max-h-[calc(100vh-16rem)] overflow-y-auto pr-1">
                                <div 
                                    v-for="product in filteredProducts" 
                                    :key="product.id" 
                                    @click="handleProductClick(product)"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md transition-shadow dark:bg-gray-900 cursor-pointer hover:border-indigo-500 group relative"
                                >
                                    <div class="h-28 w-full bg-gray-100 dark:bg-gray-800 rounded-md mb-2 overflow-hidden flex items-center justify-center">
                                        <img v-if="product.image_path" :src="`/storage/${product.image_path}`" class="object-cover w-full h-full group-hover:scale-105 transition-transform" alt="Product">
                                        <span v-else class="text-gray-400 text-xs">No Image</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-xs leading-snug line-clamp-2">{{ product.name }}</h4>
                                    
                                    <div class="mt-1 flex justify-between items-end">
                                        <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatRupiah(product.variants[0]?.price || 0) }}
                                        </div>
                                    </div>
                                    
                                    <div v-if="product.is_preorder" class="absolute top-2 left-2 bg-yellow-400/90 text-yellow-900 text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm backdrop-blur-sm">
                                        PRE-ORDER
                                    </div>

                                    <div v-if="product.variants.length > 1" class="absolute top-2 right-2 bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded backdrop-blur-sm">
                                        {{ product.variants.length }} Varian
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="filteredProducts.length === 0" class="text-center py-10 text-gray-500 dark:text-gray-400">
                                Belum ada produk aktif yang memiliki stok, atau tidak ada produk yang cocok dengan pencarian.
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Keranjang & Checkout -->
                    <div class="w-full lg:w-1/3">
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6 flex flex-col max-h-[calc(100vh-2rem)]">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-t-lg">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Keranjang Pesanan
                                </h3>
                                <div v-if="errorCart" class="mt-3 text-xs text-red-700 bg-red-100 border border-red-400 p-2 rounded flex items-center">
                                    <XCircleIcon class="h-4 w-4 mr-1 flex-shrink-0" />
                                    Keranjang belanja masih kosong. Tambahkan minimal 1 produk.
                                </div>
                            </div>
                            
                            <!-- Isi Keranjang -->
                            <div class="p-4 flex-1 overflow-y-auto min-h-[150px]">
                                <div v-if="cart.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm italic">
                                    Keranjang masih kosong.<br>Silakan pilih produk di sebelah kiri.
                                </div>
                                
                                <ul v-else class="space-y-4">
                                    <li v-for="(item, index) in cart" :key="index" class="flex justify-between items-start pb-4 border-b border-gray-100 dark:border-gray-700 last:border-0 last:pb-0">
                                        <div class="flex-1 pr-4">
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{{ item.name }}</h4>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Var: {{ item.variant_name }}</div>
                                            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mt-0.5">{{ formatRupiah(item.price) }}</div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800">
                                                <button type="button" @click="updateQuantity(index, -1)" class="px-2 py-1 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-l-md">-</button>
                                                <span class="px-2 py-1 text-sm font-semibold text-gray-900 dark:text-white w-8 text-center">{{ item.quantity }}</span>
                                                <button type="button" @click="updateQuantity(index, 1)" class="px-2 py-1 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-r-md" :disabled="item.quantity >= item.max_stock">+</button>
                                            </div>
                                            <button @click="removeFromCart(index)" class="text-xs text-red-500 hover:text-red-700 mt-2 hover:underline">Hapus</button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Panel Checkout Bawah -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg space-y-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Total Belanja</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ formatRupiah(cartTotal) }}</span>
                                </div>
                                
                                <form @submit.prevent="submitCheckout" class="space-y-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <div class="relative searchable-dropdown">
                                        <InputLabel value="Cari Nama Siswa" />
                                        <input 
                                            type="text" 
                                            v-model="searchQuery" 
                                            @focus="dropdownOpen = true"
                                            @input="dropdownOpen = true; selectedSiswa = ''"
                                            placeholder="Ketik nama atau NIS..."
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                        />
                                        <!-- Dropdown List -->
                                        <div v-if="dropdownOpen && filteredSiswas.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                                <li 
                                                    v-for="siswa in filteredSiswas" 
                                                    :key="siswa.id"
                                                    @click="selectSiswa(siswa)"
                                                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-900 dark:text-gray-200"
                                                >
                                                    <span class="block font-medium truncate">{{ siswa.name }}</span>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Kelas: {{ siswa.kelas }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div v-if="dropdownOpen && filteredSiswas.length === 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 shadow-lg rounded-md py-3 px-3 text-sm text-gray-500">
                                            Tidak ditemukan siswa dengan nama tersebut.
                                        </div>
                                        <InputError :message="form.errors.siswa_id" class="mt-1" />
                                        <div v-if="errorSiswa" class="mt-1 text-xs text-red-700 bg-red-100 border border-red-400 p-2 rounded flex items-center">
                                            <XCircleIcon class="h-4 w-4 mr-1 flex-shrink-0" />
                                            Silakan pilih siswa terlebih dahulu.
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <InputLabel value="Metode Pembayaran" />
                                        <div class="grid grid-cols-2 gap-2 mt-1">
                                            <label class="cursor-pointer">
                                                <input type="radio" v-model="paymentMethod" value="CASH" class="peer sr-only" />
                                                <div class="p-2 text-center border rounded-md peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:text-green-700 dark:border-gray-600 dark:peer-checked:bg-green-900/30 dark:peer-checked:text-green-400 dark:text-gray-300 transition-colors h-full flex flex-col items-center justify-center">
                                                    <BanknotesIcon class="w-6 h-6 mb-1 text-green-600 dark:text-green-400" />
                                                    <span class="block font-bold text-sm">Tunai</span>
                                                    <span class="text-[10px]">Langsung Lunas</span>
                                                </div>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" v-model="paymentMethod" value="ONLINE" class="peer sr-only" />
                                                <div class="p-2 text-center border rounded-md peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:text-indigo-700 dark:border-gray-600 dark:peer-checked:bg-indigo-900/30 dark:peer-checked:text-indigo-400 dark:text-gray-300 transition-colors h-full flex flex-col items-center justify-center">
                                                    <CreditCardIcon class="w-6 h-6 mb-1 text-indigo-600 dark:text-indigo-400" />
                                                    <span class="block font-bold text-sm">Transfer</span>
                                                    <span class="text-[10px]">Via Tagihan Online</span>
                                                </div>
                                            </label>
                                        </div>
                                        <InputError :message="form.errors.payment_method" class="mt-1" />
                                    </div>
                                    
                                    <div class="pt-2">
                                        <PrimaryButton 
                                            class="w-full justify-center h-12 text-sm" 
                                            :class="paymentMethod === 'CASH' ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500' : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'"
                                            :disabled="form.processing"
                                        >
                                            <span v-if="form.processing">Memproses...</span>
                                            <span v-else-if="paymentMethod === 'CASH'">Proses Pembayaran Tunai</span>
                                            <span v-else>Buat Tagihan (Xendit)</span>
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Modal Pilih Varian -->
        <Modal :show="isVariantModalOpen" @close="closeVariantModal" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    Pilih Varian
                </h2>
                <div v-if="selectedProductForVariant" class="flex items-center mb-4">
                    <img v-if="selectedProductForVariant.image_path" :src="`/storage/${selectedProductForVariant.image_path}`" class="w-12 h-12 object-cover rounded-md mr-3" />
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ selectedProductForVariant.name }}</div>
                    </div>
                </div>

                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <button 
                        v-for="variant in selectedProductForVariant?.variants" 
                        :key="variant.id"
                        @click="selectVariantAndAddToCart(variant)"
                        class="w-full flex justify-between items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors text-left"
                    >
                        <div>
                            <span class="block font-medium text-gray-900 dark:text-gray-200 text-sm">{{ variant.name }}</span>
                            <span class="block text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">{{ formatRupiah(variant.price) }}</span>
                        </div>
                        <div class="text-xs text-gray-500 text-right">
                            Stok:<br><span class="font-bold">{{ selectedProductForVariant.is_preorder ? '∞' : variant.stock }}</span>
                        </div>
                    </button>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeVariantModal">Batal</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal Pembayaran Tunai (Checkout Kasir) -->
        <Modal :show="isCashModalOpen" @close="isCashModalOpen = false" maxWidth="sm">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                    <BanknotesIcon class="w-6 h-6 text-green-600" />
                </div>
                <h2 class="text-xl font-bold text-center text-gray-900 dark:text-white mb-6">
                    Pembayaran Tunai
                </h2>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg flex justify-between items-center border border-gray-200 dark:border-gray-600">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Tagihan</span>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ formatRupiah(cartTotal) }}</span>
                    </div>

                    <div>
                        <InputLabel value="Uang Diterima (Rp)" />
                        <input
                            type="text"
                            v-model="uangDiterimaStr"
                            @input="formatUangInput"
                            class="mt-1 block w-full text-lg font-bold text-right tracking-wider border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            placeholder="0"
                            autofocus
                        />
                    </div>

                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg flex justify-between items-center border border-indigo-100 dark:border-indigo-800">
                        <span class="text-sm font-medium text-indigo-800 dark:text-indigo-300">Kembalian</span>
                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ formatRupiah(cashKembalian) }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="isCashModalOpen = false">Batal</SecondaryButton>
                    <PrimaryButton 
                        @click="confirmCashCheckout" 
                        class="bg-green-600 hover:bg-green-700 focus:bg-green-700 focus:ring-green-500 active:bg-green-800"
                        :disabled="uangDiterima < cartTotal || form.processing"
                    >
                        {{ form.processing ? 'Memproses...' : 'Selesaikan Pembayaran' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
