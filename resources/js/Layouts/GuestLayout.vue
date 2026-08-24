<script setup>
import { Link } from '@inertiajs/vue3';
import GlobalLoader from '@/Components/GlobalLoader.vue';
</script>

<template>
    <div class="flex flex-col lg:flex-row min-h-screen bg-gray-50 dark:bg-gray-900 lg:bg-white">
        <!-- Top on Mobile / Left on Desktop: Branding & Visuals -->
        <div class="relative w-full h-[25vh] min-h-[220px] lg:h-auto lg:min-h-screen lg:w-1/2 flex-shrink-0 bg-gradient-to-br from-red-600 via-red-800 to-gray-900 overflow-hidden flex items-center justify-center">
            
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <!-- Goal Posts -->
                <img src="/images/goal_post.svg" class="absolute -left-10 lg:-left-20 bottom-0 lg:bottom-10 w-[20rem] lg:w-[40rem] opacity-30 blur-[2px] transform -rotate-3" alt="">
                <img src="/images/goal_post.svg" class="absolute right-0 lg:right-10 bottom-20 lg:bottom-40 w-32 lg:w-64 opacity-50 transform rotate-6" alt="">

                <!-- Floating Balls -->
                <img src="/images/ball.svg" class="absolute -right-10 lg:-right-16 -bottom-10 lg:-bottom-16 w-48 lg:w-96 opacity-40 blur-md transform rotate-45" alt="">
                <img src="/images/ball.svg" class="absolute left-10 lg:left-32 bottom-20 lg:bottom-32 w-16 lg:w-32 opacity-80 drop-shadow-2xl animate-float" alt="">
                <img src="/images/ball.svg" class="absolute left-1/4 top-10 lg:top-20 w-10 lg:w-16 opacity-40 blur-[2px] animate-pulse" alt="">
                
                <!-- Abstract light orbs -->
                <div class="absolute top-1/4 left-1/4 w-32 lg:w-64 h-32 lg:h-64 bg-red-500 rounded-full mix-blend-screen filter blur-[50px] lg:blur-[100px] opacity-40 animate-blob"></div>
                <div class="absolute bottom-1/4 right-1/4 w-40 lg:w-80 h-40 lg:h-80 bg-orange-500 rounded-full mix-blend-screen filter blur-[60px] lg:blur-[120px] opacity-30 animate-blob animation-delay-2000"></div>
            </div>

            <!-- Foreground Branding Text -->
            <div class="relative z-10 flex flex-col items-center justify-center text-center p-6 lg:p-12 text-white -mt-10 lg:mt-0">
                <Link href="/">
                    <img src="/images/logo-white.png" class="h-20 lg:h-32 mb-4 lg:mb-8 drop-shadow-2xl hover:scale-105 transition-transform duration-500" alt="Persija Development Logo">
                </Link>
                <h1 class="hidden lg:block text-4xl xl:text-5xl font-black mb-4 tracking-tight drop-shadow-lg">Persija Development</h1>
                <p class="hidden lg:block text-red-100 text-lg xl:text-xl font-medium max-w-md drop-shadow-md leading-relaxed">
                    Sistem Manajemen Soccer School Terpadu.<br>Membangun Juara Masa Depan.
                </p>
            </div>
        </div>

        <!-- Bottom Sheet on Mobile / Right Side on Desktop: Auth Form Container -->
        <div class="w-full lg:w-1/2 flex-grow flex flex-col justify-center bg-white dark:bg-gray-900 relative z-20 rounded-t-[2.5rem] lg:rounded-none -mt-10 lg:mt-0 pb-8 pt-6 lg:pt-0 lg:px-24 shadow-[0_-15px_40px_-15px_rgba(0,0,0,0.3)] lg:shadow-none">
            
            <div class="relative z-10 w-full max-w-md mx-auto px-6 sm:px-12 lg:px-0">
                <!-- Mobile drag indicator pill -->
                <div class="w-14 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full mx-auto mb-6 lg:hidden"></div>

                <slot />
            </div>
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
