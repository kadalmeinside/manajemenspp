<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    pageTitle: String,
});

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo ? `/storage/${page.props.app_settings.app_logo}` : null);
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center p-4 sm:p-6">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-red-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
        </div>

        <main class="w-full max-w-4xl mx-auto z-10">
            <!-- Judul Utama -->
            <div class="text-center mb-10">
                <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight sm:text-5xl">Situs dalam Perbaikan</h1>
            </div>

            <!-- Konten Utama (Gambar Kiri, Tulisan Kanan) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">
                
                <!-- Sisi Kiri: Gambar -->
                <div class="order-1 md:order-2">
                    <img src="/images/under_construction.png" alt="Situs dalam perbaikan" class="mx-auto w-full max-w-sm h-auto">
                </div>

                <!-- Sisi Kanan: Teks -->
                <div class="order-2 md:order-1 text-center md:text-left">

                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Segera Kembali Online
                    </h2>
                    <p class="mt-4 text-gray-800 leading-relaxed">
                        Kami sedang melakukan pembaruan untuk meningkatkan performa dan keamanan. Mohon maaf atas ketidaknyamanannya, kami akan segera kembali.
                    </p>

                    <div class="mt-8">
                        <p class="text-sm text-gray-800">Butuh bantuan? Hubungi kami melalui:</p>
                        <a href="https://wa.me/62811386846" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-green-600 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-transform hover:scale-105">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                            </svg>
                            <span>WhatsApp Admin</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
.animation-delay-4000 {
  animation-delay: 4s;
}
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}
</style>
