<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { inject, onMounted } from 'vue';
import { CreditCardIcon, UserGroupIcon, ShieldCheckIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const hideLoader = inject('hideLoader', () => console.error('Inject hideLoader gagal!'));

onMounted(() => {
    hideLoader();
});
</script>

<template>
    <Head title="Portal Layanan Persija Development" />
    <PublicLayout>
        <main class="bg-zinc-950">
            <!-- Hero Section (Full Screen) -->
            <section class="relative h-screen w-full flex flex-col items-center justify-center text-center overflow-hidden">
                <!-- Background Image -->
                <div class="absolute inset-0 z-0">
                    <img src="https://persijadevelopment.id/storage/uploads/2025/10/Ts6lgT4mbotPRQBmHNWwynPVWDmKNGBppgmU8ySz.webp" alt="Persija Development Background" class="w-full h-full object-cover opacity-40">
                    <!-- Gradient overlay for readability -->
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/60 to-zinc-950/80"></div>
                </div>

                <!-- Hero Content -->
                <div class="relative z-10 w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-16">
                    <img src="/images/logo-white.png" alt="Persija Logo" class="mx-auto h-24 sm:h-32 mb-6 drop-shadow-2xl">
                    
                    <h1 class="font-teko text-5xl sm:text-7xl font-bold text-white uppercase tracking-wider mb-2 text-shadow-lg">
                        PERSIJA DEVELOPMENT
                    </h1>
                    <h2 class="text-xl sm:text-2xl text-red-500 font-semibold mb-6 tracking-wide">
                        PORTAL LAYANAN & ADMINISTRASI
                    </h2>
                    <p class="text-zinc-300 text-base sm:text-lg max-w-2xl mx-auto mb-10 leading-relaxed">
                        Akses mudah ke seluruh layanan administrasi Persija Academy dan Soccer School. Pantau riwayat tagihan dan lakukan pembayaran SPP Anda dengan aman, cepat, dan transparan.
                    </p>

                    <!-- CTA Button -->
                    <div class="flex justify-center">
                        <Link :href="route('tagihan.spp.form')" class="group relative inline-flex items-center justify-center gap-3 bg-red-600 text-white font-bold text-lg sm:text-xl py-4 px-10 rounded-full overflow-hidden transition-all duration-300 hover:bg-red-500 hover:scale-105 hover:shadow-[0_0_40px_rgba(220,38,38,0.6)] shadow-[0_0_20px_rgba(220,38,38,0.3)]">
                            <CreditCardIcon class="w-7 h-7" />
                            <span>CEK & BAYAR SPP</span>
                            <div class="absolute inset-0 h-full w-full bg-white/20 scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300 ease-out"></div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="relative z-10 py-16 sm:py-24 bg-zinc-950 border-t border-zinc-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 lg:gap-8">
                        <div class="bg-zinc-900/60 p-8 rounded-2xl border border-zinc-800 hover:border-red-500/50 transition-colors group">
                            <div class="w-14 h-14 bg-red-600/10 rounded-full flex items-center justify-center mx-auto mb-5 group-hover:bg-red-600/20 transition-colors">
                                <ShieldCheckIcon class="w-7 h-7 text-red-500" />
                            </div>
                            <h3 class="text-white text-center font-bold text-lg mb-2">Pembayaran Aman</h3>
                            <p class="text-zinc-400 text-center text-sm leading-relaxed">Terintegrasi dengan payment gateway resmi (Xendit) untuk keamanan penuh pada setiap transaksi Anda.</p>
                        </div>
                        
                        <div class="bg-zinc-900/60 p-8 rounded-2xl border border-zinc-800 hover:border-red-500/50 transition-colors group">
                            <div class="w-14 h-14 bg-red-600/10 rounded-full flex items-center justify-center mx-auto mb-5 group-hover:bg-red-600/20 transition-colors">
                                <DocumentTextIcon class="w-7 h-7 text-red-500" />
                            </div>
                            <h3 class="text-white text-center font-bold text-lg mb-2">Pengecekan Instan</h3>
                            <p class="text-zinc-400 text-center text-sm leading-relaxed">Cek status tagihan SPP bulanan siswa secara langsung menggunakan nomor telepon yang terdaftar.</p>
                        </div>
                        
                        <div class="bg-zinc-900/60 p-8 rounded-2xl border border-zinc-800 hover:border-red-500/50 transition-colors group">
                            <div class="w-14 h-14 bg-red-600/10 rounded-full flex items-center justify-center mx-auto mb-5 group-hover:bg-red-600/20 transition-colors">
                                <UserGroupIcon class="w-7 h-7 text-red-500" />
                            </div>
                            <h3 class="text-white text-center font-bold text-lg mb-2">Portal Terpadu</h3>
                            <p class="text-zinc-400 text-center text-sm leading-relaxed">Satu gerbang akses terpusat untuk seluruh program pembinaan, baik Academy maupun Soccer School Persija.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Teko:wght@500;600;700&display=swap');
body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
.font-teko { font-family: 'Teko', sans-serif; }
.text-shadow-lg { text-shadow: 0 10px 15px rgba(0, 0, 0, 0.5); }
</style>