<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, DocumentCheckIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

defineProps({
    pageTitle: String,
    invoice: Object,
});

// Tentukan tujuan tombol berdasarkan apakah user login atau tidak
const backUrl = computed(() => {
    if (user.value) {
        // Jika user adalah siswa, arahkan ke daftar tagihannya
        if (user.value.roles.includes('siswa')) {
            return route('siswa.tagihan.index');
        }
        // Jika admin, arahkan ke dashboard admin
        if (user.value.roles.includes('admin')) {
            return route('tagihan.spp.form');
        }
    }
    // Default untuk guest, arahkan ke form cek tagihan
    return route('tagihan.spp.form');
});

const showContent = ref(false);

onMounted(() => {
    setTimeout(() => {
        showContent.value = true;
    }, 100);
});
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col items-center justify-center p-4 sm:p-6 overflow-hidden">
        
        <!-- Abstract Background Ornaments -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-gradient-to-br from-green-400/20 to-teal-500/10 blur-3xl opacity-50 dark:opacity-20 animate-pulse-slow"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-gradient-to-tr from-emerald-400/20 to-cyan-500/10 blur-3xl opacity-50 dark:opacity-20 animate-pulse-slow" style="animation-delay: 1.5s;"></div>
        </div>

        <main class="w-full max-w-md mx-auto z-10 text-center transition-all duration-1000 ease-out transform" :class="showContent ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'">
            <!-- Success Card -->
            <div class="bg-white dark:bg-gray-800 shadow-2xl shadow-green-500/10 dark:shadow-green-900/20 rounded-3xl p-8 sm:p-12 border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                
                <!-- Decorative Top Bar -->
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-green-400 to-teal-500"></div>

                <!-- Icon Container with Animation -->
                <div class="relative mx-auto w-24 h-24 mb-8">
                    <div class="absolute inset-0 bg-green-100 dark:bg-green-900/30 rounded-full scale-150 animate-ping opacity-20"></div>
                    <div class="relative flex items-center justify-center w-full h-full bg-gradient-to-br from-green-400 to-teal-500 rounded-full shadow-lg shadow-green-500/30 transform transition-transform duration-700" :class="showContent ? 'scale-100 rotate-0' : 'scale-0 -rotate-180'">
                        <CheckCircleIcon class="w-12 h-12 text-white" />
                    </div>
                </div>
                
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-3">
                    <template v-if="invoice && invoice.status === 'PENDING'">
                        Menunggu Pembayaran
                    </template>
                    <template v-else>
                        Pembayaran Berhasil!
                    </template>
                </h1>
                
                <p class="text-gray-500 dark:text-gray-400 leading-relaxed mb-6">
                    <template v-if="invoice && invoice.status === 'PENDING'">
                        Tagihan Anda telah berhasil dibuat. Silakan selesaikan pembayaran sesuai instruksi.
                    </template>
                    <template v-else>
                        Terima kasih. Pembayaran Anda telah kami terima dan tercatat di sistem.
                    </template>
                </p>

                <!-- Receipt Details (If Invoice Exists) -->
                <div v-if="invoice" class="mb-6 text-left bg-gray-50 dark:bg-gray-700/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200 dark:border-gray-600 border-dashed">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tagihan</span>
                        <span class="text-lg font-extrabold text-teal-600 dark:text-teal-400">Rp {{ new Intl.NumberFormat('id-ID').format(invoice.total_amount) }}</span>
                    </div>
                    <div class="flex flex-col gap-1 mb-3 pb-3 border-b border-gray-200 dark:border-gray-600 border-dashed">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</span>
                        <span v-if="invoice.status === 'PENDING'" class="text-sm font-bold text-amber-600 dark:text-amber-400">Belum Dibayar (Menunggu Pembayaran)</span>
                        <span v-else class="text-sm font-bold text-green-600 dark:text-green-400">Lunas</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan Pembayaran</span>
                        <span class="text-sm text-gray-900 dark:text-white font-medium">{{ invoice.description }}</span>
                    </div>
                </div>

                <!-- Receipt-like Divider -->
                <div class="relative py-4 flex items-center justify-center">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center px-4 bg-white dark:bg-gray-800">
                        <DocumentCheckIcon class="w-6 h-6 text-gray-300 dark:text-gray-600" />
                    </div>
                </div>
                
                <div class="mt-4">
                    <Link :href="backUrl" class="group relative flex w-full justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-gray-900 transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                        Kembali ke Halaman Sebelumnya
                    </Link>
                </div>
            </div>
            
            <!-- Footer text -->
            <p class="mt-8 text-xs text-gray-400 dark:text-gray-500 font-medium tracking-wide">
                Secure Payment &copy; {{ new Date().getFullYear() }}
            </p>
        </main>
    </div>
</template>

<style scoped>
.animate-pulse-slow {
    animation: pulse 6s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.8; }
}
</style>
