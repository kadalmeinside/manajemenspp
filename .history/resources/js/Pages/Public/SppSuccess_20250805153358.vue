<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    pageTitle: String,
    siswaName: String,
});

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo ? `/storage/${page.props.app_settings.app_logo}` : null);
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center p-4 sm:p-6">
        <!-- Background Image -->
        <div class="fixed inset-0 z-0">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/images/bg_registration.webp');"></div>
            <div class="absolute inset-0 bg-black/60"></div>
        </div>

        <main class="w-full max-w-2xl mx-auto z-10 text-center">
            <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg shadow-2xl rounded-xl p-8 md:p-12">
                <CheckCircleIcon class="mx-auto h-16 w-16 text-green-500" />
                
                <h1 class="mt-4 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    Pembayaran Berhasil!
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-300">
                    Terima kasih, pembayaran untuk siswa atas nama <strong>{{ siswaName }}</strong> telah kami terima.
                </p>
                
                <div class="mt-8">
                    <Link :href="route('tagihan.spp.form')" class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                        &larr; Kembali ke Halaman Cek SPP
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
