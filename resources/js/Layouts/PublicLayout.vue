<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon, ArrowUpIcon } from '@heroicons/vue/24/solid';

const page = usePage();
const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Persija Development');
const appLogo = computed(() => appSettings.value.app_logo ? `/storage/${appSettings.value.app_logo}` : 'https://seeklogo.com/images/P/persija-jakarta-logo-62BFC506B5-seeklogo.com.png');

const mobileMenuOpen = ref(false);
const showBackToTop = ref(false);
const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 50;
    showBackToTop.value = window.scrollY > 300;
};

// Logika untuk menentukan kelas header secara dinamis
const headerClass = computed(() => {
    const baseClasses = 'fixed top-0 left-0 right-0 z-50 transition-all duration-300';
    const solidBgClasses = 'bg-black/70 backdrop-blur-sm shadow-lg';
    
    // Daftar halaman yang diizinkan memiliki header transparan di awal
    const transparentHeaderPages = ['Welcome', 'Soccerschool', 'Academy', 'Persijadna'];

    // Periksa apakah halaman saat ini ada dalam daftar
    const isTransparentPage = transparentHeaderPages.includes(page.component);

    // Jika BUKAN halaman yang diizinkan, selalu gunakan background solid.
    if (!isTransparentPage) {
        return `${baseClasses} ${solidBgClasses}`;
    }
    
    // Jika IYA, gunakan background solid HANYA jika di-scroll.
    return isScrolled.value ? `${baseClasses} ${solidBgClasses}` : baseClasses;
});


const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Fungsi smooth scroll ini sekarang hanya untuk footer
const smoothScrollTo = (selector) => {
    mobileMenuOpen.value = false;
    const element = document.querySelector(selector);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
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
        <header :class="headerClass">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <Link href="/" class="flex-shrink-0 flex items-center space-x-2">
                        <img :src="appLogo" alt="App Logo" class="h-10 w-auto">
                        <span class="text-white font-teko text-2xl font-bold tracking-wider">{{ appName }}</span>
                    </Link>
                    
                    <!-- Navigasi Utama -->
                    <nav class="hidden md:flex items-center space-x-2">
                        <Link :href="route('welcome')" :class="['px-4 py-2 rounded-full transition-colors font-semibold', page.component === 'Welcome' ? 'bg-red-600 text-white' : 'text-white/80 hover:text-white']">Beranda</Link>
                        <Link :href="route('soccer-school')" :class="['px-4 py-2 rounded-full transition-colors font-semibold', page.component === 'Soccerschool' ? 'bg-red-600 text-white' : 'text-white/80 hover:text-white']">Soccer School</Link>
                        <Link :href="route('academy')" :class="['px-4 py-2 rounded-full transition-colors font-semibold', page.component === 'Academy' ? 'bg-red-600 text-white' : 'text-white/80 hover:text-white']">Academy</Link>
                        <Link :href="route('persija-dna')" :class="['px-4 py-2 rounded-full transition-colors font-semibold', page.component === 'Persijadna' ? 'bg-red-600 text-white' : 'text-white/80 hover:text-white']">Persija DNA</Link>
                    </nav>

                    <div class="hidden md:flex items-center space-x-2">
                        <Link :href="route('tagihan.check_form')" class="bg-white text-gray-900 font-bold py-2 px-5 rounded-md text-sm hover:bg-gray-200 transition-colors">Bayar SPP</Link>
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
                    <Link :href="route('welcome')" :class="['block w-full text-left py-2 px-3 text-base font-medium rounded-md', page.component === 'Welcome' ? 'bg-red-600 text-white' : 'text-white/80 hover:bg-zinc-700']">Beranda</Link>
                    <Link :href="route('soccer-school')" :class="['block w-full text-left py-2 px-3 text-base font-medium rounded-md', page.component === 'Soccerschool' ? 'bg-red-600 text-white' : 'text-white/80 hover:bg-zinc-700']">Soccer School</Link>
                    <Link :href="route('academy')" :class="['block w-full text-left py-2 px-3 text-base font-medium rounded-md', page.component === 'Academy' ? 'bg-red-600 text-white' : 'text-white/80 hover:bg-zinc-700']">Academy</Link>
                    <Link :href="route('persija-dna')" :class="['block w-full text-left py-2 px-3 text-base font-medium rounded-md', page.component === 'PersijaDna' ? 'bg-red-600 text-white' : 'text-white/80 hover:bg-zinc-700']">Persija DNA</Link>
                    
                    <div class="border-t border-zinc-700 pt-4 mt-4 flex flex-col space-y-3">
                         <Link :href="route('tagihan.check_form')" class="w-full text-center bg-white text-gray-900 font-bold py-2 px-5 rounded-md hover:bg-gray-200 transition-colors">Bayar SPP</Link>
                    </div>
                </nav>
            </transition>
        </header>

        <!-- PERUBAHAN UTAMA DI SINI -->
        <div> <!-- Memberi ruang untuk header utama yang fixed -->
            <!-- Slot untuk Header/Banner Halaman -->
            <slot name="page-header" />
            
            <!-- Slot untuk Konten Utama Halaman -->
            <main>
                <slot />
            </main>
        </div>


        <footer id="kontak" class="bg-zinc-800 text-gray-400">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="font-bold text-white">{{ appName }}</h3>
                    <p class="mt-2 text-sm">Pusat pelatihan sepakbola usia muda di bawah naungan klub Persija Jakarta.</p>
                </div>
                <div>
                    <h3 class="font-bold text-white">Program</h3>
                     <ul class="mt-2 space-y-1 text-sm">
                        <li><Link :href="route('academy')" class="hover:text-white cursor-pointer">Academy Boarding School</Link></li>
                        <li><Link :href="route('soccer-school')" class="hover:text-white cursor-pointer">Soccer School</Link></li>
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
                     <div class="mt-4 flex flex-col space-y-2 items-start">
                         <a href="https://www.instagram.com/persija.ac/" target="_blank" rel="noopener" class="flex items-center hover:text-white transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 3.808s-.012 2.74-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-3.808.06s-2.74-.012-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.067-.06-1.407-.06-3.808s.012-2.74.06-3.808c.049 1.064.218 1.791.465 2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.343 2.522c.636-.247 1.363-.416 2.427-.465C9.833 2.013 10.173 2 12.315 2zm0 1.623c-2.403 0-2.729.01-3.686.06-1.003.046-1.605.2-2.073.387a3.272 3.272 0 00-1.15 1.15c-.187.468-.341 1.07-.387 2.073-.05 1.002-.06 1.283-.06 3.686s.01 2.684.06 3.686c.046 1.003.2 1.605.387 2.073a3.272 3.272 0 001.15 1.15c.468.187 1.07.341 2.073.387 1.002.05 1.283.06 3.686.06s2.684-.01 3.686-.06c1.003-.046 1.605-.2 2.073-.387a3.272 3.272 0 001.15-1.15c.187-.468.341-1.07.387-2.073.05-1.002.06-1.283.06-3.686s-.01-2.684-.06-3.686c-.046-1.003-.2-1.605-.387-2.073a3.272 3.272 0 00-1.15-1.15c-.468-.187-1.07-.341-2.073-.387-1.002-.05-1.283-.06-3.686-.06zm0 4.498a4.928 4.928 0 100 9.856 4.928 4.928 0 000-9.856zm0 8.229a3.301 3.301 0 110-6.602 3.301 3.301 0 010 6.602zm5.45-8.835a1.238 1.238 0 100 2.476 1.238 1.238 0 000-2.476z" clip-rule="evenodd" /></svg>
                            <span>Instagram</span>
                         </a>
                         <a href="https://www.youtube.com/channel/UCkG1xshm_v5NqJJ4AE7XsKA" target="_blank" rel="noopener" class="flex items-center hover:text-white transition-colors">
                             <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.78 22 12 22 12s0 3.22-.42 4.814a2.506 2.506 0 0 1-1.768 1.768c-1.594.42-7.394.42-7.394.42s-5.8 0-7.394-.42a2.506 2.506 0 0 1-1.768-1.768C2 15.22 2 12 2 12s0-3.22.42-4.814a2.506 2.506 0 0 1 1.768-1.768C5.794 5.002 11.594 5 11.594 5s5.8 0 7.394.418zM9.545 15.568V8.432L15.479 12 9.545 15.568z" clip-rule="evenodd" /></svg>
                             <span>YouTube</span>
                         </a>
                     </div>
                </div>
            </div>
            <div class="mt-8 border-t border-zinc-700 py-6 text-center text-sm">
                <p>&copy; {{ new Date().getFullYear() }} {{ appName }}. All Rights Reserved.</p>
                <div class="mt-2 space-x-4">
                    <Link :href="route('legal.terms')" class="hover:underline">Syarat & Ketentuan</Link>
                    <span class="text-gray-500">&middot;</span>
                    <Link :href="route('legal.refund')" class="hover:underline">Kebijakan Pengembalian</Link>
                    <span class="text-gray-500">&middot;</span>
                    <Link :href="route('legal.privacy')" class="hover:underline">Kebijakan Privasi</Link>
                </div>
            </div>
        </footer>

        <transition
            enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4"
        >
            <button v-if="showBackToTop" @click="scrollToTop" class="fixed bottom-6 right-6 bg-red-600 hover:bg-red-700 text-white h-12 w-12 rounded-full flex items-center justify-center shadow-lg transition-opacity z-40">
                <ArrowUpIcon class="h-6 w-6"/>
            </button>
        </transition>
    </div>
</template>
