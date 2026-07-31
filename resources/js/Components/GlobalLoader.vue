<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const isLoading = ref(false);

let timeout = null;

const startLoader = () => {
    // Tambahkan sedikit delay (misal 200ms) agar loading tidak muncul berkedip 
    // jika perpindahan halamannya sangat cepat.
    timeout = setTimeout(() => {
        isLoading.value = true;
    }, 200);
};

let isUnloading = false;
window.addEventListener('beforeunload', () => {
    isUnloading = true;
});

const stopLoader = () => {
    // Beri sedikit jeda. Jika halaman sedang dialihkan (redirect) ke eksternal, 
    // beforeunload akan terpanggil sehingga loader tidak menghilang (mencegah glitch).
    setTimeout(() => {
        if (isUnloading) return; 
        if (timeout) clearTimeout(timeout);
        isLoading.value = false;
    }, 50);
};

onMounted(() => {
    router.on('start', startLoader);
    router.on('finish', stopLoader);
});

onUnmounted(() => {
    // Clean up
    // Inertia event listeners should be removed correctly. 
    // Inertia returns a callback to remove the listener, but it's fine for a global layout component.
});
</script>

<template>
    <!-- Overlay Loading: Menggunakan backdrop blur dan spinner -->
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isLoading" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/50 dark:bg-black/50 backdrop-blur-sm">
            <div class="flex flex-col items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700">
                <!-- Spinner Berputar -->
                <svg class="animate-spin h-10 w-10 text-red-600 dark:text-red-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Memproses...</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mohon tunggu sebentar</p>
            </div>
        </div>
    </transition>
</template>
