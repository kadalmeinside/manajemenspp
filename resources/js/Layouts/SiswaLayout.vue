<script setup>
import { ref, computed, watchEffect, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

import {
    HomeIcon,
    UserCircleIcon,
    DocumentChartBarIcon,
    ArrowLeftStartOnRectangleIcon,
    BellIcon,
    ChevronDownIcon,
    ShoppingBagIcon,
    ClipboardDocumentListIcon,
    UsersIcon
} from '@heroicons/vue/24/outline';

import {
    HomeIcon as HomeIconSolid,
    UserCircleIcon as UserCircleIconSolid,
    DocumentChartBarIcon as DocumentChartBarIconSolid,
    ShoppingBagIcon as ShoppingBagIconSolid,
    ClipboardDocumentListIcon as ClipboardDocumentListIconSolid
} from '@heroicons/vue/24/solid';

const page = usePage();

const showLogoutConfirm = ref(false);

const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Area Siswa');
const appLogo = computed(() => appSettings.value.app_logo || null);

const siswaMenu = ref([
    { name: 'Beranda', route: 'siswa.dashboard', icon: HomeIcon, activeIcon: HomeIconSolid, current: 'siswa.dashboard', type: 'link' },
    { name: 'Tagihan', route: 'siswa.tagihan.index', icon: DocumentChartBarIcon, activeIcon: DocumentChartBarIconSolid, current: 'siswa.tagihan.*', type: 'link' },
    { name: 'Toko', route: 'siswa.store.index', icon: ShoppingBagIcon, activeIcon: ShoppingBagIconSolid, current: 'siswa.store.index|siswa.store.show', type: 'link' },
    { name: 'Pesanan', route: 'siswa.store.orders.index', icon: ClipboardDocumentListIcon, activeIcon: ClipboardDocumentListIconSolid, current: 'siswa.store.orders.*', type: 'link' },
    { name: 'Profil', route: 'siswa.profil.show', icon: UserCircleIcon, activeIcon: UserCircleIconSolid, current: 'siswa.profil.*', type: 'link' },
]);

function isLinkActive(pattern) {
    if (!pattern) return false;
    const currentRoute = route().current();
    if (!currentRoute) return false;
    const patterns = pattern.split('|');
    for (let p of patterns) {
        if (route().current(p) || currentRoute.startsWith(p.replace('.*', '.'))) {
            return true;
        }
    }
    return false;
}

const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

const confirmLogout = () => {
    showLogoutConfirm.value = true;
};
const logout = () => {
    router.post(route('logout'), {
        onFinish: () => {
            showLogoutConfirm.value = false;
        }
    });
};

const userSiswas = computed(() => page.props.auth?.user?.siswas || []);
const activeSiswaId = computed(() => page.props.auth?.active_siswa_id || null);
const activeSiswa = computed(() => userSiswas.value.find(s => s.id_siswa === activeSiswaId.value) || userSiswas.value[0]);

const switchSiswa = (id_siswa) => {
    router.post(route('siswa.switch-siswa', id_siswa), {}, {
        preserveScroll: true
    });
};

const missingAgreements = computed(() => page.props.missing_agreements);
const isMissingAgreement = computed(() => !!missingAgreements.value);

const agreementForm = useForm({
    legal_document_id: null,
    id_siswa: [],
    terms_accepted: false,
});

watchEffect(() => {
    if (missingAgreements.value) {
        agreementForm.legal_document_id = missingAgreements.value.document?.id;
        agreementForm.id_siswa = missingAgreements.value.siswa.map(s => s.id_siswa);
    }
});

const submitAgreement = () => {
    if (!agreementForm.terms_accepted) return;
    agreementForm.post(route('siswa.agreements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            agreementForm.reset();
        }
    });
};

const showBottomNav = ref(true);
let lastScrollY = 0;
let scrollTimeout = null;

const handleScroll = () => {
    const currentScrollY = window.scrollY;
    
    // Hide when scrolling down, show when scrolling up
    if (currentScrollY > lastScrollY && currentScrollY > 50) {
        showBottomNav.value = false;
    } else {
        showBottomNav.value = true;
    }
    
    lastScrollY = currentScrollY;
    
    // Also show when scroll stops
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
        showBottomNav.value = true;
    }, 600);
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearTimeout(scrollTimeout);
});
</script>

<template>
    <Head :title="$page.props.pageTitle || 'Area Siswa'" />

    <div class="relative min-h-screen bg-gray-50 dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 antialiased selection:bg-indigo-500 selection:text-white pb-20 md:pb-0">
        <!-- Desktop Sidebar -->
        <aside class="hidden md:flex md:flex-col md:w-72 fixed inset-y-0 left-0 bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 shadow-sm z-40">
            <div class="h-20 flex items-center px-8 border-b border-gray-100 dark:border-gray-700/50">
                <Link :href="route('dashboard')" class="flex items-center space-x-3 group">
                    <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="h-10 w-auto transform transition-transform group-hover:scale-105">
                    <ApplicationLogo v-else class="h-10 w-auto fill-current text-indigo-600 dark:text-indigo-400 transform transition-transform group-hover:scale-105" />
                    <span class="text-xl font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300">{{ appName }}</span>
                </Link>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">
                <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4 px-4">Menu Utama</div>
                
                <template v-for="(item, index) in siswaMenu" :key="'menu-' + index">
                    <Link v-if="item.type === 'link'" :href="route(item.route)"
                          :class="['flex items-center px-4 py-3 text-sm font-semibold rounded-2xl transition-all duration-200 group relative', isLinkActive(item.current) ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white']">
                        
                        <div v-if="isLinkActive(item.current)" class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-8 bg-red-600 dark:bg-red-400 rounded-r-full"></div>
                        
                        <div :class="['p-2 rounded-xl mr-3 shadow-sm transition-all duration-200', isLinkActive(item.current) ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 group-hover:bg-white dark:group-hover:bg-gray-600 group-hover:text-red-600 dark:group-hover:text-red-300']">
                            <component :is="isLinkActive(item.current) && item.activeIcon ? item.activeIcon : item.icon" class="h-5 w-5" />
                        </div>
                        {{ item.name }}
                    </Link>
                </template>
            </nav>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                 <button @click="confirmLogout" class="w-full flex items-center px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-2xl hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors duration-200 group">
                    <div class="p-2 rounded-xl bg-white dark:bg-red-900/50 mr-3 shadow-sm text-red-500 group-hover:text-red-600 transition-colors">
                        <ArrowLeftStartOnRectangleIcon class="h-5 w-5" />
                    </div>
                    Keluar Akun
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="md:pl-72 flex flex-col min-h-screen transition-all duration-300">
            <!-- Glassmorphism Header (Desktop Only) -->
            <header class="hidden md:block sticky top-0 z-30 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800 shadow-sm transition-all duration-300">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-20">
                        
                        <!-- Page Title Header Slot -->
                        <div class="flex flex-1 items-center">
                            <slot name="header" />
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center space-x-2 sm:space-x-4 ml-auto">
                            <!-- Cart Icon -->
                            <Link :href="route('siswa.store.cart')" class="relative p-2.5 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition-colors group">
                                <span class="sr-only">Keranjang Belanja</span>
                                <ShoppingBagIcon class="h-6 w-6 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors" />
                                <span v-if="$page.props.cart_count > 0" class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900 shadow-sm transform group-hover:scale-110 transition-transform">
                                    {{ $page.props.cart_count }}
                                </span>
                            </Link>

                            <!-- Notification Icon -->
                            <button class="relative p-2.5 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition-colors group">
                                <span class="sr-only">Notifikasi</span>
                                <BellIcon class="h-6 w-6 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors" />
                            </button>

                            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>

                            <!-- Siswa Selector -->
                            <template v-if="userSiswas.length > 0">
                                <Dropdown v-if="userSiswas.length > 1" align="right" width="48">
                                    <template #trigger>
                                        <button class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 px-4 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-200">
                                            <span class="mr-2 text-gray-400">Anak:</span>
                                            <span class="text-red-600 dark:text-red-400 truncate max-w-[120px]" :title="activeSiswa?.nama_siswa">{{ activeSiswa?.nama_siswa || 'Pilih Siswa' }}</span>
                                            <ChevronDownIcon class="ml-2 h-4 w-4 text-gray-400" />
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                                            Pilih Anak
                                        </div>
                                        <div class="p-1">
                                            <DropdownLink v-for="siswa in userSiswas" :key="siswa.id_siswa" as="button" @click="switchSiswa(siswa.id_siswa)" class="rounded-lg">
                                                <div class="flex items-center justify-between w-full">
                                                    <span :class="{'font-bold text-red-600 dark:text-red-400': activeSiswaId === siswa.id_siswa}">{{ siswa.nama_siswa }}</span>
                                                    <svg v-if="activeSiswaId === siswa.id_siswa" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                </div>
                                            </DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                                <div v-else class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm cursor-default">
                                    <span class="mr-2 text-gray-400">Anak:</span>
                                    <span class="text-red-600 dark:text-red-400 truncate max-w-[120px]" :title="activeSiswa?.nama_siswa">{{ activeSiswa?.nama_siswa || 'Siswa' }}</span>
                                </div>
                            </template>

                            <!-- User Profile -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center p-1 border-2 border-transparent rounded-full focus:outline-none focus:border-red-300 transition duration-150 ease-in-out hover:ring-2 hover:ring-red-100 dark:hover:ring-red-900/50">
                                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-md">
                                            <span class="text-sm font-bold leading-none">{{ userInitial }}</span>
                                        </span>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate" :title="userName">{{ userName }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Orang Tua / Wali</p>
                                    </div>
                                    <div class="p-1 space-y-1">
                                        <DropdownLink :href="route('siswa.profil.show')" class="rounded-lg flex items-center">
                                            <UserCircleIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-red-500" />
                                            Pengaturan Profil
                                        </DropdownLink>
                                        <div class="border-t border-gray-100 dark:border-gray-700/50 my-1"></div>
                                        <DropdownLink @click="confirmLogout" as="button" class="rounded-lg flex items-center text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <ArrowLeftStartOnRectangleIcon class="mr-3 h-5 w-5 text-red-400 group-hover:text-red-500" />
                                            Keluar
                                        </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Non-Fixed Mobile Header (Scrolls Away) -->
            <div class="md:hidden pt-4 px-4 pb-2 flex items-center space-x-3">
                <!-- Left: Logo Box -->
                <div class="bg-gray-900/70 backdrop-blur-lg border border-gray-700/50 shadow-md rounded-xl h-[52px] px-3 flex items-center shrink-0">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="h-8 w-auto">
                        <ApplicationLogo v-else class="h-8 w-auto fill-current text-white" />
                    </Link>
                </div>

                <!-- Center: Student Selector -->
                <div v-if="userSiswas.length > 0" class="flex-1 flex items-center h-[48px]">
                    <Dropdown v-if="userSiswas.length > 1" align="left" width="48">
                        <template #trigger>
                            <button class="flex items-center px-1 py-1 focus:outline-none group">
                                <span class="text-gray-900 dark:text-white text-base font-extrabold tracking-wide truncate max-w-[120px] group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors" :title="activeSiswa?.nama_siswa">{{ activeSiswa?.nama_siswa || 'Pilih Siswa' }}</span>
                                <ChevronDownIcon class="ml-1 h-5 w-5 text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors" />
                            </button>
                        </template>
                        <template #content>
                            <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                                Pilih Anak
                            </div>
                            <div class="p-1">
                                <DropdownLink v-for="siswa in userSiswas" :key="siswa.id_siswa" as="button" @click="switchSiswa(siswa.id_siswa)" class="rounded-lg">
                                    <div class="flex items-center justify-between w-full">
                                        <span :class="{'font-bold text-red-600 dark:text-red-400': activeSiswaId === siswa.id_siswa}">{{ siswa.nama_siswa }}</span>
                                        <svg v-if="activeSiswaId === siswa.id_siswa" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </DropdownLink>
                            </div>
                        </template>
                    </Dropdown>
                    <div v-else class="flex items-center px-1 py-1 cursor-default">
                        <span class="text-gray-900 dark:text-white text-base font-extrabold tracking-wide truncate max-w-[120px]" :title="activeSiswa?.nama_siswa">{{ activeSiswa?.nama_siswa || 'Siswa' }}</span>
                    </div>
                </div>
            </div>

            <!-- Fixed Mobile Actions Box -->
            <div class="md:hidden fixed top-0 right-0 z-40 p-4 pointer-events-none">
                <div class="pointer-events-auto bg-gray-900/70 backdrop-blur-lg border border-gray-700/50 shadow-md rounded-2xl py-1.5 px-1.5 flex items-center space-x-1">
                    <!-- Cart Icon -->
                    <Link :href="route('siswa.store.cart')" class="relative p-2 rounded-xl text-gray-300 hover:bg-gray-800 hover:text-white focus:outline-none transition-colors group">
                        <span class="sr-only">Keranjang Belanja</span>
                        <ShoppingBagIcon class="h-6 w-6 transition-colors" />
                        <span v-if="$page.props.cart_count > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white ring-2 ring-gray-900 shadow-sm transform group-hover:scale-110 transition-transform">
                            {{ $page.props.cart_count }}
                        </span>
                    </Link>

                    <!-- Notification Icon -->
                    <button class="relative p-2 rounded-xl text-gray-300 hover:bg-gray-800 hover:text-white focus:outline-none transition-colors group">
                        <span class="sr-only">Notifikasi</span>
                        <BellIcon class="h-6 w-6 transition-colors" />
                    </button>
                </div>
            </div>

            <!-- Mobile Page Title Slot -->
            <div v-if="$slots.header" class="md:hidden px-4 pt-6 pb-2">
                <slot name="header" />
            </div>

            <!-- Page Content -->
            <main class="flex-1 w-full pb-28 md:pb-0">
                <slot />
            </main>
            
            <footer class="hidden md:block py-8 px-6 text-center text-sm text-gray-400 dark:text-gray-500 mt-auto">
                <p class="font-medium text-gray-500 dark:text-gray-400">Copyright &copy; 2026 {{ appName }}</p>
                <div class="mt-3 flex items-center justify-center space-x-4">
                    <Link :href="route('legal.terms')" class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline transition-colors">Syarat & Ketentuan</Link>
                    <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <Link :href="route('legal.refund')" class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline transition-colors">Ketentuan Pengembalian</Link>
                </div>
            </footer>
        </div>

        <!-- Sleek Mobile Bottom Navigation -->
        <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 px-5 pb-6 pt-2 pointer-events-none transition-transform duration-300 ease-in-out" :class="showBottomNav ? 'translate-y-0' : 'translate-y-[150%]'">
            <nav class="bg-gradient-to-r from-rose-500 to-orange-400 shadow-[0_10px_40px_rgba(244,63,94,0.6)] border border-white/30 backdrop-blur-xl rounded-full flex justify-around items-center h-16 pointer-events-auto">
                 <template v-for="item in siswaMenu" :key="item.name + '-mobile'">
                    <Link v-if="item.type === 'link'" :href="route(item.route)"
                          class="relative flex flex-col items-center justify-center w-full h-full group">
                        
                        <!-- Active Indicator Bubble -->
                        <div v-if="isLinkActive(item.current)" class="absolute inset-0 flex justify-center items-center pointer-events-none">
                            <div class="w-14 h-14 bg-white/30 backdrop-blur-md border-[1.5px] border-white/60 shadow-[inset_0_3px_8px_rgba(255,255,255,0.7),0_4px_10px_rgba(0,0,0,0.15)] rounded-2xl transform transition-transform"></div>
                        </div>

                        <component :is="isLinkActive(item.current) && item.activeIcon ? item.activeIcon : item.icon" 
                            class="h-6 w-6 z-10 transition-all duration-300 drop-shadow-sm" 
                            :class="isLinkActive(item.current) ? 'text-white transform -translate-y-0.5' : 'text-white/80 group-hover:text-white'"/>
                        
                        <span class="text-[10px] z-10 transition-all duration-300 mt-1 font-bold tracking-wide drop-shadow-sm"
                            :class="isLinkActive(item.current) ? 'text-white' : 'text-white/80 group-hover:text-white'">
                            {{ item.name }}
                        </span>
                    </Link>
                </template>
            </nav>
        </div>

        <Modal :show="showLogoutConfirm" @close="showLogoutConfirm = false" maxWidth="sm">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4 mx-auto">
                    <ArrowLeftStartOnRectangleIcon class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white text-center">
                    Konfirmasi Keluar
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                    Apakah Anda yakin ingin keluar dari aplikasi?
                </p>
                <div class="mt-8 flex justify-center space-x-3 w-full">
                    <SecondaryButton @click="showLogoutConfirm = false" class="flex-1 justify-center py-2.5">Batal</SecondaryButton>
                    <DangerButton @click="logout" class="flex-1 justify-center py-2.5">Ya, Keluar</DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Missing Agreement Intercept Modal -->
        <Modal :show="isMissingAgreement" maxWidth="2xl" :closeable="false">
            <div class="p-6 sm:p-8">
                <div class="flex items-center space-x-3 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <DocumentChartBarIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Pembaruan Syarat & Ketentuan
                    </h2>
                </div>
                
                <div class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                    <p class="mb-4 text-base">
                        Halo <strong class="text-gray-900 dark:text-white">{{ userName }}</strong>, kami mendeteksi bahwa data pendaftaran untuk anak Anda berikut ini belum dilengkapi dengan persetujuan Syarat & Ketentuan elektronik terbaru:
                    </p>
                    
                    <ul class="list-disc pl-5 mb-6 font-semibold text-gray-800 dark:text-gray-200">
                        <li v-for="siswa in missingAgreements?.siswa" :key="siswa.id_siswa" class="my-1">
                            {{ siswa.nama_siswa }}
                        </li>
                    </ul>

                    <p class="mb-3 text-sm font-medium">Untuk melanjutkan akses layanan, silakan baca dan setujui dokumen berikut:</p>
                </div>

                <div class="mt-2 p-5 bg-gray-50 dark:bg-gray-800 rounded-xl max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 prose prose-sm dark:prose-invert custom-scrollbar shadow-inner">
                    <div v-html="missingAgreements?.document?.content"></div>
                </div>

                <form @submit.prevent="submitAgreement" class="mt-8">
                    <div class="flex items-start bg-blue-50 dark:bg-blue-900/10 p-4 rounded-xl border border-blue-100 dark:border-blue-900/30">
                        <div class="flex items-center h-5 mt-0.5">
                            <input id="terms_accepted" type="checkbox" v-model="agreementForm.terms_accepted" required
                                class="w-5 h-5 text-indigo-600 bg-white border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                        </div>
                        <label for="terms_accepted" class="ml-3 text-sm font-medium text-blue-900 dark:text-blue-300 cursor-pointer">
                            Saya telah membaca, memahami, dan menyetujui seluruh Syarat dan Ketentuan di atas untuk pendaftaran anak saya.
                        </label>
                    </div>

                    <div class="flex justify-end pt-6 mt-6 border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" 
                                :disabled="agreementForm.processing || !agreementForm.terms_accepted"
                                :class="{'opacity-50 cursor-not-allowed': agreementForm.processing || !agreementForm.terms_accepted}"
                                class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-xl font-bold text-sm text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 shadow-md">
                            Saya Setuju & Lanjutkan
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1; 
  border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background: #94a3b8; 
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #475569;
}
:global(.dark) .custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background: #64748b;
}
</style>
