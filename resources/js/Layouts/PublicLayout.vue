<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon, ArrowUpIcon } from '@heroicons/vue/24/solid';

const page = usePage();
const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Persija Development');
const appLogo = computed(() => appSettings.value.app_logo ? `/storage/${appSettings.value.app_logo}` : 'https://seeklogo.com/images/P/persija-jakarta-logo-62BFC506B5-seeklogo.com.png');

const navbar = ref(null);
const mobileMenuOpen = ref(false);
const showBackToTop = ref(false);

const handleScroll = () => {
    if (window.scrollY > 50) {
        navbar.value?.classList.add('bg-black/70', 'backdrop-blur-sm');
        showBackToTop.value = true;
    } else {
        navbar.value?.classList.remove('bg-black/70', 'backdrop-blur-sm');
        showBackToTop.value = false;
    }
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Smooth scroll untuk link anchor
const smoothScrollTo = (selector) => {
    mobileMenuOpen.value = false;
    document.querySelector(selector)?.scrollIntoView({
        behavior: 'smooth'
    });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="bg-white dark:bg-zinc-900 text-black/80 dark:text-white/80">
        <!-- Header / Navbar -->
        <header ref="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <Link href="/" class="flex-shrink-0 flex items-center space-x-2">
                        <img :src="appLogo" alt="App Logo" class="h-10 w-auto">
                        <span class="text-white font-teko text-2xl font-bold tracking-wider">{{ appName }}</span>
                    </Link>
                    
                    <nav class="hidden md:flex items-center space-x-8">
                        <button @click="smoothScrollTo('#program')" class="text-white/80 hover:text-white font-semibold transition-colors">Program</button>
                        <button @click="smoothScrollTo('#gallery')" class="text-white/80 hover:text-white font-semibold transition-colors">Galeri</button>
                        <button @click="smoothScrollTo('#berita')" class="text-white/80 hover:text-white font-semibold transition-colors">Berita</button>
                        <button @click="smoothScrollTo('#kontak')" class="text-white/80 hover:text-white font-semibold transition-colors">Kontak</button>
                    </nav>

                    <div class="hidden md:flex items-center space-x-2">
                        <Link :href="route('pendaftaran.create')" class="bg-white text-gray-900 font-bold py-2 px-5 rounded-md text-sm hover:bg-gray-200 transition-colors">DAFTAR</Link>
                        <Link :href="route('login')" class="bg-red-600 text-white font-bold py-2 px-5 rounded-md text-sm hover:bg-red-500 transition-colors">LOGIN</Link>
                    </div>

                    <div class="md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white p-2">
                            <Bars3Icon v-if="!mobileMenuOpen" class="h-8 w-8" />
                            <XMarkIcon v-else class="h-8 w-8" />
                        </button>
                    </div>
                </div>
            </div>
            
            <transition
                enter-active-class="transition ease-out duration-200" enter-from-class="transform opacity-0 -translate-y-4" enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 -translate-y-4"
            >
                <nav v-if="mobileMenuOpen" class="md:hidden bg-zinc-900/95 backdrop-blur-lg px-4 pt-2 pb-4 space-y-1">
                    <button @click="smoothScrollTo('#program')" class="block w-full text-left py-2 px-3 text-base font-medium text-white/80 rounded-md hover:bg-zinc-700">Program</button>
                    <button @click="smoothScrollTo('#gallery')" class="block w-full text-left py-2 px-3 text-base font-medium text-white/80 rounded-md hover:bg-zinc-700">Galeri</button>
                    <button @click="smoothScrollTo('#berita')" class="block w-full text-left py-2 px-3 text-base font-medium text-white/80 rounded-md hover:bg-zinc-700">Berita</button>
                    <button @click="smoothScrollTo('#kontak')" class="block w-full text-left py-2 px-3 text-base font-medium text-white/80 rounded-md hover:bg-zinc-700">Kontak</button>
                    <div class="border-t border-zinc-700 pt-4 mt-4 flex flex-col space-y-3">
                        <Link :href="route('pendaftaran.create')" class="w-full text-center bg-red-600 text-white font-bold py-2 px-5 rounded-md hover:bg-red-500 transition-colors">DAFTAR</Link>
                        <Link :href="route('login')" class="w-full text-center bg-gray-200 text-gray-900 font-bold py-2 px-5 rounded-md hover:bg-gray-300 transition-colors">LOGIN</Link>
                    </div>
                </nav>
            </transition>
        </header>

        <main>
            <slot />
        </main>

        <footer id="kontak" class="bg-zinc-800 text-gray-400">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="font-bold text-white">Persija Development</h3>
                    <p class="mt-2 text-sm">Pusat pelatihan sepakbola usia muda di bawah naungan klub Persija Jakarta.</p>
                </div>
                <div>
                    <h3 class="font-bold text-white">Program</h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        <li><a href="#academy-details" class="hover:text-white">Academy Boarding School</a></li>
                        <li><a href="#soccer-school-details" class="hover:text-white">Soccer School</a></li>
                    </ul>
                </div>
                 <div>
                    <h3 class="font-bold text-white">Kontak</h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        <li>Admin Academy: 0811-2626-322</li>
                        <li>Admin Soccer School: 0811-2626-323</li>
                        <li>info@persijadevelopment.id</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-white">Media Sosial</h3>
                     <div class="flex space-x-4 mt-2">
                         <a href="https://www.instagram.com/persija.ac/" target="_blank" rel="noopener" class="hover:text-white">Instagram</a>
                         <a href="https://www.youtube.com/channel/UCkG1xshm_v5NqJJ4AE7XsKA" target="_blank" rel="noopener" class="hover:text-white">YouTube</a>
                     </div>
                </div>
            </div>
            <div class="mt-8 border-t border-zinc-700 py-6 text-center text-sm">
                <p>&copy; {{ new Date().getFullYear() }} Persija Development. All Rights Reserved.</p>
            </div>
        </footer>

        <transition
            enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4"
        >
            <button v-if="showBackToTop" @click="scrollToTop" class="fixed bottom-6 right-6 bg-red-600 hover:bg-red-700 text-white h-12 w-12 rounded-full flex items-center justify-center shadow-lg transition-opacity">
                <ArrowUpIcon class="h-6 w-6"/>
            </button>
        </transition>
    </div>
</template>
