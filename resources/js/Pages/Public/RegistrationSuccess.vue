<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { CheckCircleIcon, DocumentTextIcon, PhoneIcon } from '@heroicons/vue/24/outline';
import AnimatedBackground from '@/Components/AnimatedBackground.vue';

const props = defineProps({
    pageTitle: String,
    siswaName: String,
    cabangName: String,
    adminContact: String,
    instagramUrl: String,
});

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo ? `/storage/${page.props.app_settings.app_logo}` : null);

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
            <div class="absolute -top-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-gradient-to-br from-green-400/20 to-emerald-500/5 blur-3xl opacity-50 dark:opacity-20 animate-pulse-slow"></div>
            <div class="absolute -bottom-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-gradient-to-tr from-blue-400/20 to-indigo-500/5 blur-3xl opacity-50 dark:opacity-20 animate-pulse-slow" style="animation-delay: 2s;"></div>
        </div>
        
        <AnimatedBackground />

        <main class="w-full max-w-lg mx-auto z-10 text-center transition-all duration-1000 ease-out transform" :class="showContent ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'">
            <!-- Success Card -->
            <div class="bg-white dark:bg-gray-800 shadow-2xl shadow-green-500/10 dark:shadow-green-900/20 rounded-3xl p-8 sm:p-12 border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                
                <!-- Decorative Top Bar -->
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-green-400 to-emerald-500"></div>

                <!-- Icon Container with Animation -->
                <div class="relative mx-auto w-24 h-24 mb-8">
                    <div class="absolute inset-0 bg-green-100 dark:bg-green-900/30 rounded-full scale-150 animate-ping opacity-20"></div>
                    <div class="relative flex items-center justify-center w-full h-full bg-gradient-to-br from-green-400 to-emerald-500 rounded-full shadow-lg shadow-green-500/30 transform transition-transform duration-700" :class="showContent ? 'scale-100 rotate-0' : 'scale-0 -rotate-180'">
                        <CheckCircleIcon class="w-12 h-12 text-white" />
                    </div>
                </div>
                
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-3">
                    Pendaftaran Berhasil!
                </h1>
                
                <p class="text-gray-500 dark:text-gray-400 leading-relaxed mb-6">
                    Selamat, <strong class="text-gray-900 dark:text-white font-semibold">{{ siswaName }}</strong>!<br class="hidden sm:block">
                    Pembayaran Anda telah kami terima dan pendaftaran untuk cabang <strong class="text-gray-900 dark:text-white font-semibold">{{ cabangName }}</strong> berhasil tercatat di sistem.
                </p>

                <!-- Next Steps Info -->
                <div class="mb-6 text-left bg-gray-50 dark:bg-gray-700/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col gap-1 mb-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Langkah Selanjutnya</span>
                        <span class="text-sm text-gray-900 dark:text-white font-medium">Untuk informasi mengenai jadwal latihan dan proses lanjutan, Anda dapat menghubungi admin kami:</span>
                    </div>
                    
                    <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-600 border-dashed">
                        <a :href="`tel:${adminContact}`" class="flex items-center text-sm font-semibold text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                            <PhoneIcon class="h-5 w-5 mr-2" />
                            {{ adminContact }}
                        </a>
                        <a :href="instagramUrl" target="_blank" class="flex items-center text-sm font-semibold text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 3.808s-.012 2.74-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-3.808.06s-2.74-.012-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.067-.06-1.407-.06-3.808s.012-2.74.06-3.808c.049-1.064.218 1.791.465 2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.343 2.522c.636-.247 1.363.416 2.427.465C9.833 2.013 10.173 2 12.315 2zm0 1.623c-2.403 0-2.729.01-3.686.06-1.003.046-1.605.2-2.073.387a3.272 3.272 0 00-1.15 1.15c-.187.468-.341 1.07-.387 2.073-.05 1.002-.06 1.283-.06 3.686s.01 2.684.06 3.686c.046 1.003.2 1.605.387 2.073a3.272 3.272 0 001.15 1.15c.468.187 1.07.341 2.073.387 1.002.05 1.283.06 3.686.06s2.684-.01 3.686-.06c1.003-.046 1.605-.2 2.073-.387a3.272 3.272 0 001.15-1.15c.187-.468.341-1.07.387-2.073.05-1.002.06-1.283.06-3.686s-.01-2.684-.06-3.686c-.046-1.003-.2-1.605-.387-2.073a3.272 3.272 0 00-1.15-1.15c-.468-.187-1.07-.341-2.073-.387-1.002-.05-1.283-.06-3.686-.06zm0 4.498a4.928 4.928 0 100 9.856 4.928 4.928 0 000-9.856zm0 8.229a3.301 3.301 0 110-6.602 3.301 3.301 0 010 6.602zm5.45-8.835a1.238 1.238 0 100 2.476 1.238 1.238 0 000-2.476z" clip-rule="evenodd" /></svg>
                            Instagram Kami
                        </a>
                    </div>
                </div>

                <!-- Receipt-like Divider -->
                <div class="relative py-4 flex items-center justify-center">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center px-4 bg-white dark:bg-gray-800">
                        <DocumentTextIcon class="w-6 h-6 text-gray-300 dark:text-gray-600" />
                    </div>
                </div>
                
                <div class="mt-4 space-y-4">
                    <Link :href="route('login')" class="group relative flex w-full justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:focus:ring-white transition-all shadow-md hover:shadow-lg">
                        Ke Halaman Login
                    </Link>
                    
                    <Link href="/" class="inline-block text-sm font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                        Ke Beranda Utama
                    </Link>
                </div>
            </div>
            
            <!-- Footer text -->
            <p class="mt-8 text-xs text-gray-400 dark:text-gray-500 font-medium tracking-wide">
                Sistem Informasi SPP &copy; {{ new Date().getFullYear() }}
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
