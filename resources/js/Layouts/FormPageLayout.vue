<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const appLogo = computed(() => page.props.app_settings?.app_logo ? `/storage/${page.props.app_settings.app_logo}` : null);
</script>

<template>
    <div class="relative min-h-screen overflow-hidden flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-red-100 via-orange-50 to-orange-100">
        
        <main class="w-full max-w-4xl space-y-8 z-10">
             <header class="text-center mb-8">
                <!-- <Link href="/" class="inline-block">
                    <img v-if="appLogo" :src="appLogo" alt="App Logo" class="h-12 w-auto mx-auto">
                    <ApplicationLogo v-else class="h-12 w-auto mx-auto text-gray-800 dark:text-white" />
                </Link> -->
                <div class="flex justify-center">
                    <Link href="/">
                        <img class="h-12 w-auto dark:hidden" src="/images/logo-black.png" alt="Persija Development">
                        <img class="h-12 w-auto hidden dark:block" src="/images/logo-white.png" alt="Persija Development">
                    </Link>
                </div>
                <h1 class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white">
                    <slot name="header" />
                </h1>
            </header>

            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-8">
                 <slot />
            </div>

            <footer class="w-full max-w-4xl mx-auto mt-8 text-center text-xs text-gray-600 dark:text-gray-400">
                <p>Copyright &copy; 2025 Persija Development</p>
                <p class="mt-1">Contact Center: 0811-2626-323</p>
            </footer>
        </main>
    </div>
</template>
