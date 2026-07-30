<script setup>
import { Link } from '@inertiajs/vue3';
import GlobalLoader from '@/Components/GlobalLoader.vue';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-red-600 via-red-800 to-gray-900 sm:pt-0">
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <!-- Goal Post 1: Kiri, Besar, Agak Blur (Background) -->
            <img src="/images/goal_post.svg" class="absolute -left-20 bottom-10 w-[30rem] sm:w-[40rem] opacity-30 blur-[3px] transform -rotate-3" alt="">

            <!-- Goal Post 2: Kanan, Kecil, Fokus -->
            <img src="/images/goal_post.svg" class="absolute right-10 bottom-40 w-48 sm:w-64 opacity-50 transform rotate-6" alt="">

            <!-- Ball 1: Kanan Bawah, Sangat Besar, Sangat Blur (Foreground DOF) -->
            <img src="/images/ball.svg" class="absolute -right-16 -bottom-16 w-64 sm:w-96 opacity-60 blur-md transform rotate-45" alt="">

            <!-- Ball 2: Kiri Tengah, Sedang, Fokus, Animasi Melayang (Float) -->
            <img src="/images/ball.svg" class="absolute left-10 sm:left-32 bottom-32 w-24 sm:w-32 opacity-80 shadow-2xl animate-float" alt="">

            <!-- Ball 3: Kiri Atas, Kecil, Blur -->
            <img src="/images/ball.svg" class="absolute left-1/4 top-20 w-16 opacity-40 blur-[2px] animate-pulse" alt="">
            
            <!-- Abstract light orbs -->
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-red-600 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-orange-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/2 w-80 h-80 bg-red-800 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
        </div>

        <!-- Content Container (Glassmorphism Light) -->
        <div class="relative z-10 w-full min-h-screen sm:min-h-0 flex flex-col justify-center sm:max-w-md px-6 py-8 sm:px-10 sm:py-10 mx-0 backdrop-blur-xl bg-white/70 sm:bg-white/80 border-0 sm:border border-white/50 shadow-2xl rounded-none sm:rounded-3xl">
            <slot />
        </div>
        
        <GlobalLoader />
    </div>
</template>

<style>
/* Float Animation */
@keyframes float {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Light Orbs Blob Animation */
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
