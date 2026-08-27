<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { TrashIcon, ArrowLeftIcon, ShoppingBagIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    cart: Object,
    siswas: Array,
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const items = computed(() => props.cart?.items || []);

const subtotal = computed(() => {
    return items.value.reduce((total, item) => total + (item.variant.price * item.quantity), 0);
});

const feeAmount = 4500; // Hardcoded for now, or get from config
const grandTotal = computed(() => subtotal.value > 0 ? subtotal.value + feeAmount : 0);

const updateQuantity = (item, newQuantity) => {
    if (newQuantity < 1) return;
    if (!item.product.is_preorder && newQuantity > item.variant.stock) return;

    router.put(route('siswa.store.cart.update', item.id), { quantity: newQuantity }, {
        preserveScroll: true,
    });
};

const removeItem = (item) => {
    router.delete(route('siswa.store.cart.remove', item.id), { preserveScroll: true });
};

const getMainImage = (product) => {
    if (product.images && product.images.length > 0) {
        const primary = product.images.find(img => img.is_primary);
        return primary ? primary.image_path : product.images[0].image_path;
    }
    return product.image_path;
};

const checkoutForm = useForm({
    siswa_id: props.siswas && props.siswas.length === 1 ? props.siswas[0].id_siswa : '',
    force: false,
});

const checkout = () => {
    checkoutForm.post(route('siswa.store.checkout'), {
        preserveScroll: true,
        onSuccess: () => {
            if (usePage().props.flash && usePage().props.flash.pending_order_conflict) {
                // Biarkan modal terbuka yang diatur oleh v-if="$page.props.flash.pending_order_conflict"
            }
        },
    });
};

const forceCheckout = () => {
    checkoutForm.force = true;
    checkoutForm.post(route('siswa.store.checkout'), {
        preserveScroll: true,
        onFinish: () => {
            checkoutForm.force = false;
        }
    });
};
</script>

<template>
    <Head title="Keranjang Belanja" />

    <SiswaLayout>
        <div class="pt-4 pb-8 md:py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="mb-6 md:mb-8">
                    <Link :href="route('siswa.store.index')" class="inline-flex items-center text-sm md:text-base font-bold text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                        <ArrowLeftIcon class="w-5 h-5 mr-2" />
                        Lanjut Belanja
                    </Link>
                </div>

                <div v-if="$page.props.flash && $page.props.flash.error && !$page.props.flash.pending_order_conflict" class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 dark:bg-red-900/30 dark:text-red-300 rounded-md shadow-sm">
                    <p class="font-bold">Gagal</p>
                    <p>{{ $page.props.flash.error }}</p>
                </div>

                <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                    <!-- Cart Items -->
                    <div class="lg:w-2/3">
                        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-3xl overflow-hidden border border-gray-100 dark:border-gray-700/60">
                            <div class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-700/60 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800">
                                <h1 class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white flex items-center">
                                    <ShoppingBagIcon class="w-7 h-7 md:w-8 md:h-8 mr-3 text-red-600 dark:text-red-400" />
                                    Keranjang Anda
                                </h1>
                            </div>

                            <div v-if="items.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                <div v-for="item in items" :key="item.id" class="p-4 sm:p-6 md:p-8 flex flex-row gap-4 sm:gap-6 hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                                    <div class="w-20 h-20 sm:w-32 sm:h-32 bg-gray-100 dark:bg-gray-900 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden shadow-inner p-2">
                                        <img v-if="getMainImage(item.product)" :src="'/storage/' + getMainImage(item.product)" :alt="item.product.name" class="w-full h-full object-contain drop-shadow-sm" />
                                        <ShoppingBagIcon v-else class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                                    </div>
                                    <div class="flex-grow flex flex-col justify-between">
                                        <div class="flex flex-row justify-between items-start gap-2 w-full">
                                            <div class="pr-2">
                                                <h3 class="font-bold text-sm sm:text-lg md:text-xl text-gray-900 dark:text-white leading-tight line-clamp-2">{{ item.product.name }}</h3>
                                                <p class="text-[10px] sm:text-sm text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 bg-gray-100 dark:bg-gray-700 inline-block px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full font-medium">Varian: <span class="text-gray-800 dark:text-gray-200">{{ item.variant.name }}</span></p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="font-extrabold text-sm sm:text-lg md:text-xl text-gray-900 dark:text-white">{{ formatRupiah(item.variant.price * item.quantity) }}</p>
                                                <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1 font-medium">{{ formatRupiah(item.variant.price) }} / item</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-wrap items-center justify-between gap-3 mt-4 sm:mt-6 pt-3 sm:pt-4 border-t border-gray-100 dark:border-gray-700/50">
                                            <div class="flex items-center bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-1">
                                                <button @click="updateQuantity(item, item.quantity - 1)" :disabled="item.quantity <= 1" class="p-1 w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-lg text-gray-600 hover:bg-white hover:shadow-sm dark:hover:bg-gray-800 disabled:opacity-50 font-bold transition-all">
                                                    -
                                                </button>
                                                <span class="w-8 sm:w-10 text-center text-xs sm:text-sm font-extrabold text-gray-900 dark:text-white">{{ item.quantity }}</span>
                                                <button @click="updateQuantity(item, item.quantity + 1)" :disabled="!item.product.is_preorder && item.quantity >= item.variant.stock" class="p-1 w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-lg text-gray-600 hover:bg-white hover:shadow-sm dark:hover:bg-gray-800 disabled:opacity-50 font-bold transition-all">
                                                    +
                                                </button>
                                            </div>
                                            
                                            <button @click="removeItem(item)" class="text-xs sm:text-sm font-bold text-red-500 hover:text-red-700 dark:text-red-400 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg flex items-center transition-colors">
                                                <TrashIcon class="w-4 h-4 sm:mr-1.5" /> <span class="hidden sm:inline">Hapus</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="p-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 mb-4">
                                    <ShoppingBagIcon class="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Keranjang Kosong</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-6">Anda belum menambahkan produk apapun ke keranjang.</p>
                                <Link :href="route('siswa.store.index')" class="text-red-600 font-bold hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    Mulai Belanja &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:w-1/3">
                        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-3xl p-6 md:p-8 border border-gray-100 dark:border-gray-700/60 sticky top-24">
                            <h2 class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white mb-6">Ringkasan Pesanan</h2>
                            
                            <div class="space-y-4 text-sm md:text-base">
                                <div class="flex justify-between text-gray-600 dark:text-gray-400 font-medium">
                                    <span>Subtotal ({{ items.length }} item)</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ formatRupiah(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-600 dark:text-gray-400 font-medium pb-4 border-b border-gray-100 dark:border-gray-700/60">
                                    <span>Biaya Layanan (Xendit)</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ subtotal > 0 ? formatRupiah(feeAmount) : formatRupiah(0) }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-3">
                                    <span class="font-bold text-gray-900 dark:text-white text-base md:text-lg">Total Tagihan</span>
                                    <span class="font-black text-red-600 dark:text-red-400 text-xl md:text-2xl">{{ formatRupiah(grandTotal) }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700/60">
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                    Pesanan ini untuk siapa?
                                </label>
                                <select v-model="checkoutForm.siswa_id" class="w-full text-base border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:text-gray-300 focus:border-red-500 focus:ring-red-500 rounded-xl shadow-inner px-4 py-3 font-medium transition-colors">
                                    <option value="" disabled>-- Pilih Anak --</option>
                                    <option v-for="siswa in siswas" :key="siswa.id_siswa" :value="siswa.id_siswa">
                                        {{ siswa.nama_siswa }}
                                    </option>
                                </select>
                                <InputError :message="checkoutForm.errors.siswa_id" class="mt-2" />
                            </div>
                            
                            <div class="mt-8">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 text-center">
                                    Dengan menekan tombol Checkout, Anda akan diarahkan ke halaman pembayaran aman Xendit.
                                </p>
                                <button 
                                    @click="checkout"
                                    :disabled="items.length === 0 || checkoutForm.processing"
                                    class="w-full flex justify-center items-center py-4 px-6 rounded-2xl text-base md:text-lg font-bold text-white shadow-lg disabled:bg-gray-400 dark:disabled:bg-gray-600 transition-all duration-300 ease-in-out transform hover:scale-[1.02] hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-red-500/50 bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-500 hover:to-rose-400"
                                >
                                    {{ checkoutForm.processing ? 'Memproses...' : 'Checkout & Bayar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Modal Konfirmasi Override Pesanan Pending -->
        <Modal :show="$page.props.flash && $page.props.flash.pending_order_conflict" maxWidth="md">
            <div class="p-6 md:p-8">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full mb-6">
                    <ShoppingBagIcon class="w-8 h-8 text-red-600 dark:text-red-400" />
                </div>
                
                <h2 class="text-xl md:text-2xl font-black text-center text-gray-900 dark:text-white mb-4">Terdapat Pesanan Belum Dibayar</h2>
                
                <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 text-center mb-8">
                    Anda masih memiliki pesanan toko yang berstatus <span class="font-bold text-yellow-600 dark:text-yellow-400">PENDING</span> (menunggu pembayaran). Untuk mencegah penumpukan pemesanan barang, Anda harus membatalkan pesanan sebelumnya jika ingin melanjutkan pesanan ini.
                </p>

                <div class="flex flex-col gap-3">
                    <DangerButton @click="forceCheckout" class="w-full justify-center py-4 text-sm md:text-base shadow-lg" :class="{ 'opacity-50': checkoutForm.processing }" :disabled="checkoutForm.processing">
                        {{ checkoutForm.processing ? 'Memproses...' : 'Batalkan Pesanan Lama & Lanjut' }}
                    </DangerButton>
                    
                    <Link :href="route('siswa.tagihan.index')" class="w-full">
                        <SecondaryButton type="button" class="w-full justify-center py-4 text-sm md:text-base">
                            Lihat & Bayar Pesanan Lama
                        </SecondaryButton>
                    </Link>

                    <button @click="$page.props.flash.pending_order_conflict = false" class="mt-4 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
    </SiswaLayout>
</template>
