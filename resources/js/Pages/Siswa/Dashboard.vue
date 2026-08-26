<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BanknotesIcon, ClockIcon, CreditCardIcon, CheckBadgeIcon, ExclamationTriangleIcon, UserGroupIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    pageTitle: String,
    familySummary: Array,
    featuredProducts: {
        type: Array,
        default: () => []
    },
    grandTotal: Object,
    errorMessage: String,
});

// Fungsi untuk memotong deskripsi
const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};

const goToTagihan = (id_siswa) => {
    // Switch active siswa then go to tagihan
    router.post(route('siswa.switch-siswa', id_siswa), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route('siswa.tagihan.index'));
        }
    });
};

const goToProfil = (id_siswa) => {
    router.post(route('siswa.switch-siswa', id_siswa), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route('siswa.profil.show'));
        }
    });
};
</script>

<template>
    <Head :title="pageTitle" />
    <SiswaLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-extrabold text-xl md:text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center">
                    <UserGroupIcon class="h-6 w-6 md:h-8 md:w-8 mr-2 md:mr-3 text-red-600 dark:text-red-400" />
                    {{ pageTitle }}
                </h2>
            </div>
        </template>

        <div class="py-4 md:py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div v-if="errorMessage" class="mb-6 md:mb-8 p-4 md:p-5 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-lg shadow-sm" role="alert">
                    <div class="flex items-center">
                        <ExclamationTriangleIcon class="h-5 w-5 md:h-6 md:w-6 mr-3 text-red-500 shrink-0" />
                        <div>
                            <p class="font-bold text-base md:text-lg">Terjadi Kesalahan</p>
                            <p class="text-sm md:text-base">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-6 md:space-y-8">
                    
                    <!-- Grand Total Warning if any -->
                    <div v-if="grandTotal && grandTotal.count > 0" class="relative overflow-hidden bg-gradient-to-r from-red-600 to-rose-500 rounded-2xl shadow-lg p-5 md:p-8 text-white transition-transform transform hover:-translate-y-1 hover:shadow-xl duration-300">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 md:w-32 md:h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
                        
                        <div class="relative flex flex-row items-center justify-between z-10">
                            <div class="flex items-center">
                                <div class="bg-white/20 p-3 md:p-4 rounded-full mr-4 md:mr-6 backdrop-blur-sm shrink-0">
                                    <ExclamationTriangleIcon class="h-6 w-6 md:h-10 md:w-10 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-base md:text-2xl font-extrabold tracking-tight">Perhatian: Ada Tagihan Tertunggak</h3>
                                    <p class="text-red-100 mt-1 md:mt-2 text-xs md:text-lg leading-relaxed">Secara keseluruhan, ada <span class="font-bold text-white">{{ grandTotal.count }}</span> tagihan tertunggak sebesar <span class="font-bold text-white bg-red-800/40 px-1.5 py-0.5 rounded-md whitespace-nowrap">{{ grandTotal.formatted }}</span>.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome / Info Section (Optional: only if no grand total warning) -->
                    <div v-else-if="familySummary && familySummary.length > 0" class="relative overflow-hidden bg-gradient-to-r from-emerald-600 to-green-500 rounded-2xl shadow-lg p-5 md:p-8 text-white transition-transform transform hover:-translate-y-1 hover:shadow-xl duration-300">
                         <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 md:w-32 md:h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
                         <div class="relative flex items-center z-10">
                             <div class="bg-white/20 p-3 md:p-4 rounded-full mr-4 md:mr-6 backdrop-blur-sm shrink-0">
                                 <CheckBadgeIcon class="h-6 w-6 md:h-10 md:w-10 text-white" />
                             </div>
                             <div>
                                 <h3 class="text-base md:text-2xl font-extrabold tracking-tight">Semua Tagihan Lunas</h3>
                                 <p class="text-emerald-100 mt-1 md:mt-2 text-xs md:text-lg leading-relaxed">Terima kasih atas pembayaran yang tepat waktu untuk seluruh tagihan administrasi.</p>
                             </div>
                         </div>
                    </div>

                    <!-- Store Promo Banner & Featured Products -->
                    <div class="relative overflow-hidden rounded-3xl shadow-lg mt-6 md:mt-8 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 border border-gray-700">
                        <div class="absolute right-0 top-0 bottom-0 w-1/2 md:w-1/3 bg-gradient-to-l from-red-600/30 to-transparent pointer-events-none"></div>
                        
                        <div class="relative p-6 md:p-8 flex flex-col md:flex-row gap-8 items-center justify-between z-10">
                            <!-- Left: Promo Text -->
                            <div class="w-full md:w-1/3 mb-2 md:mb-0">
                                <span class="inline-block px-3 py-1 bg-red-600 text-white text-[10px] md:text-xs font-bold rounded-full mb-3 uppercase tracking-wider shadow-sm">Store</span>
                                <h3 class="text-xl md:text-3xl font-extrabold text-white mb-2 md:mb-3 tracking-tight leading-tight">Perlengkapan Resmi & Jersey Baru 🌟</h3>
                                <p class="text-gray-400 text-xs md:text-sm mb-5 leading-relaxed">Cek koleksi perlengkapan terbaru untuk putra Anda. Dapatkan penawaran spesial khusus pemesanan via aplikasi.</p>
                                <Link :href="route('siswa.store.index')" class="inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-red-600/30 hover:bg-red-500 transition-all transform hover:-translate-y-0.5">
                                    Kunjungi Toko
                                    <ArrowRightIcon class="h-4 w-4 ml-2" />
                                </Link>
                            </div>

                            <!-- Right: Product Highlights (Grid/Slider) -->
                            <div class="w-full md:w-2/3 flex overflow-x-auto pb-4 md:pb-0 gap-4 snap-x hide-scrollbar">
                                <Link 
                                    v-for="product in featuredProducts" 
                                    :key="product.id"
                                    :href="route('siswa.store.show', product.slug)"
                                    class="snap-start shrink-0 w-36 md:w-44 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 hover:bg-white/20 transition-all duration-300 overflow-hidden group"
                                >
                                    <div class="h-32 md:h-40 w-full overflow-hidden bg-gray-800 flex items-center justify-center">
                                        <img :src="product.image_url" :alt="product.name" class="h-full w-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                                    </div>
                                    <div class="p-3 md:p-4">
                                        <h4 class="text-white font-bold text-xs md:text-sm truncate mb-1">{{ product.name }}</h4>
                                        <p class="text-red-400 font-extrabold text-[11px] md:text-xs">{{ product.price_formatted }}</p>
                                    </div>
                                </Link>
                                
                                <!-- Empty State for Products if none exist -->
                                <div v-if="featuredProducts.length === 0" class="flex-1 min-h-[160px] flex items-center justify-center border border-dashed border-gray-600 rounded-2xl">
                                    <p class="text-gray-500 text-sm italic">Produk sedang diperbarui...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cards per Anak -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                        <div v-for="siswa in familySummary" :key="siswa.id_siswa" class="group bg-white dark:bg-gray-800 rounded-3xl shadow-md hover:shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col transition-all duration-300">
                            <!-- Header Anak -->
                            <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750 p-5 md:p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-start">
                                <div class="flex items-center space-x-3 md:space-x-4">
                                    <div class="h-12 w-12 md:h-16 md:w-16 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center border-2 border-red-200 dark:border-red-700 shadow-sm shrink-0">
                                        <span class="text-xl md:text-2xl font-bold text-red-700 dark:text-red-300">{{ siswa.nama_siswa.charAt(0).toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</h3>
                                        <div class="flex items-center mt-1 space-x-2 md:space-x-3 text-xs md:text-sm text-gray-500 dark:text-gray-400">
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 md:px-2.5 py-0.5 rounded-full font-medium">{{ siswa.kelas }}</span>
                                            <span class="flex items-center"><span class="w-1.5 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full mr-1.5 md:mr-2"></span>NIS: {{ siswa.nis || '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button @click="goToProfil(siswa.id_siswa)" class="p-1.5 md:p-2 rounded-full text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Lihat Profil">
                                    <ArrowRightIcon class="h-4 w-4 md:h-5 md:w-5" />
                                </button>
                            </div>

                            <!-- Body Tagihan -->
                            <div class="p-5 md:p-6 flex-grow flex flex-col space-y-4 md:space-y-5">
                                <!-- Tunggakan SPP -->
                                <div v-if="siswa.overdueTotal.count > 0" class="bg-red-50 dark:bg-red-900/10 rounded-2xl p-4 md:p-5 border border-red-100 dark:border-red-900/30">
                                    <div class="flex justify-between items-center mb-3 md:mb-4">
                                        <div class="flex items-center text-red-700 dark:text-red-400 font-bold text-sm md:text-base">
                                            <ExclamationTriangleIcon class="h-4 w-4 md:h-5 md:w-5 mr-1.5 md:mr-2" />
                                            Tunggakan SPP ({{ siswa.overdueTotal.count }})
                                        </div>
                                        <span class="text-red-700 dark:text-red-400 font-extrabold text-base md:text-lg">{{ siswa.overdueTotal.formatted }}</span>
                                    </div>
                                    <ul class="space-y-2 md:space-y-3">
                                        <!-- SPP Invoices -->
                                        <li v-for="inv in siswa.overdueInvoices" :key="inv.id" class="flex justify-between items-center p-2.5 md:p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-50 dark:border-red-900/20">
                                            <div class="flex flex-col">
                                                <span class="text-xs md:text-sm font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[120px] xs:max-w-[160px] sm:max-w-none">{{ getShortDescription(inv.description) }}</span>
                                                <span class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400">{{ inv.periode_formatted }}</span>
                                            </div>
                                            <span class="text-xs md:text-sm font-bold text-red-600 dark:text-red-400 ml-2 whitespace-nowrap">{{ inv.total_amount_formatted }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Pesanan Toko PENDING -->
                                <div v-if="siswa.pendingStoreOrders.length > 0" class="bg-yellow-50 dark:bg-yellow-900/10 rounded-2xl p-4 md:p-5 border border-yellow-200 dark:border-yellow-900/30">
                                    <div class="flex justify-between items-center mb-3 md:mb-4">
                                        <div class="flex items-center text-yellow-700 dark:text-yellow-500 font-bold text-sm md:text-base">
                                            <ExclamationTriangleIcon class="h-4 w-4 md:h-5 md:w-5 mr-1.5 md:mr-2" />
                                            Pesanan Toko (Menunggu Pembayaran)
                                        </div>
                                    </div>
                                    <ul class="space-y-2 md:space-y-3">
                                        <li v-for="order in siswa.pendingStoreOrders" :key="'store-'+order.id" class="flex justify-between items-center p-2.5 md:p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-yellow-100 dark:border-yellow-900/20">
                                            <div class="flex flex-col">
                                                <span class="text-xs md:text-sm font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[120px] xs:max-w-[160px] sm:max-w-none">{{ getShortDescription(order.description) }}</span>
                                                <span class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400">{{ order.periode_formatted }}</span>
                                            </div>
                                            <span class="text-xs md:text-sm font-bold text-yellow-600 dark:text-yellow-500 ml-2 whitespace-nowrap">{{ order.total_amount_formatted }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Bersih -->
                                <div v-if="siswa.overdueTotal.count === 0 && siswa.pendingStoreOrders.length === 0" class="flex items-center justify-center text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/10 p-4 md:p-5 rounded-2xl border border-green-100 dark:border-green-900/30 h-full min-h-[100px] md:min-h-[120px]">
                                    <div class="text-center">
                                        <CheckBadgeIcon class="h-8 w-8 md:h-10 md:w-10 mx-auto mb-1.5 md:mb-2 opacity-80" />
                                        <span class="text-xs md:text-sm font-bold">Hebat! Semua tagihan sampai bulan ini sudah lunas.</span>
                                    </div>
                                </div>

                                <!-- Ringkasan Pembayaran -->
                                <div class="mt-auto pt-4 md:pt-6 border-t border-gray-100 dark:border-gray-700/60">
                                    <div class="grid grid-cols-2 gap-4 items-center">
                                        <div>
                                            <p class="text-[10px] md:text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Sisa Tagihan (s/d Bulan Ini)</p>
                                            <p class="text-base md:text-xl font-extrabold text-red-600 dark:text-red-400 leading-none">
                                                {{ siswa.paymentSummary.total_unpaid_formatted }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] md:text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">Total Terbayar</p>
                                            <p class="text-base md:text-xl font-extrabold text-gray-900 dark:text-white leading-none">
                                                {{ siswa.paymentSummary.total_paid_formatted }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-gray-50 dark:border-gray-700/30 text-center">
                                        <button @click="goToTagihan(siswa.id_siswa)" class="w-full inline-flex items-center justify-center px-4 py-2.5 md:py-3 bg-red-600 dark:bg-red-500 text-white rounded-xl font-bold text-xs md:text-sm shadow-sm hover:bg-red-700 dark:hover:bg-red-400 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                                            Lihat Detail Tagihan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </SiswaLayout>
</template>