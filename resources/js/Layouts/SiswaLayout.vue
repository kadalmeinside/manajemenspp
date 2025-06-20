<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

// Import ikon yang dibutuhkan
import {
    HomeIcon,
    UserCircleIcon,
    DocumentChartBarIcon,
    ArrowLeftStartOnRectangleIcon,
    BellIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

const page = usePage();

// State untuk modal logout
const showLogoutConfirm = ref(false);

// --- PERUBAHAN: Ambil Pengaturan Aplikasi Dinamis ---
const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Area Siswa');
const appLogo = computed(() => appSettings.value.app_logo || null);
// --- AKHIR PERUBAHAN ---

// Menu untuk Siswa
const siswaMenu = ref([
    { name: 'Dashboard', route: 'siswa.dashboard', icon: HomeIcon, current: 'siswa.dashboard', type: 'link' },
    { name: 'Tagihan', route: 'siswa.tagihan.index', icon: DocumentChartBarIcon, current: 'siswa.tagihan.*', type: 'link' },
    { name: 'Profil', route: 'siswa.profil.show', icon: UserCircleIcon, current: 'siswa.profil.*', type: 'link' },
    { name: 'Keluar', action: 'confirmLogout', icon: ArrowLeftStartOnRectangleIcon, type: 'button' },
]);

// Helper untuk mengecek state menu aktif
function isLinkActive(pattern) {
    if (!pattern) return false;
    const currentRoute = route().current();
    if (!currentRoute) return false;
    return route().current(pattern) || currentRoute.startsWith(pattern.replace('.*', '.'));
}

// Info Pengguna
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

// Fungsi Logout
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
</script>

<template>
    <Head :title="$page.props.pageTitle || 'Area Siswa'" />

    <div class="relative min-h-screen md:flex bg-gray-100 dark:bg-gray-900">
        <!-- Sidebar Kiri (Hanya untuk Desktop) -->
        <aside class="hidden md:sticky md:flex md:flex-col md:w-64 bg-gray-800 text-gray-300">
            <div class="h-16 flex items-center justify-center px-4 bg-gray-900 flex-shrink-0">
                <Link :href="route('dashboard')" class="flex items-center">
                    <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="block h-9 w-auto">
                    <ApplicationLogo v-else class="block h-9 w-auto fill-current text-white" />
                    <span class="ml-3 text-white text-lg font-semibold">{{ appName }}</span>
                </Link>
            </div>

            <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                <template v-for="(item, index) in siswaMenu" :key="'menu-' + index">
                    <Link v-if="item.type === 'link'" :href="route(item.route)"
                          :class="['flex items-center px-2 py-2 text-sm font-medium rounded-md group', isLinkActive(item.current) ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white']">
                        <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" />
                        <span>{{ item.name }}</span>
                    </Link>
                    <button v-else-if="item.type === 'button'" @click="confirmLogout" class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md group text-gray-300 hover:bg-gray-700 hover:text-white">
                        <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" />
                        <span>{{ item.name }}</span>
                    </button>
                </template>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white dark:bg-gray-700 shadow-sm sticky top-0 z-30 flex-shrink-0 border-b border-gray-200 dark:border-gray-600">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <!-- PERBAIKAN: Logo ditampilkan di header mobile -->
                        <div class="flex items-center md:hidden">
                            <Link :href="route('dashboard')" class="flex items-center">
                                <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="block h-8 w-auto">
                                <ApplicationLogo v-else class="block h-8 w-auto fill-current text-gray-800 dark:text-white" />
                            </Link>
                        </div>
                        
                         <div class="hidden md:block ml-4">
                            <slot name="header" />
                        </div>

                        <!-- Profil dropdown hanya tampil di desktop -->
                        <div class="hidden md:flex items-center space-x-3">
                           <button class="p-1 rounded-full text-gray-400 dark:text-gray-300 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">View notifications</span>
                                <BellIcon class="h-6 w-6" />
                            </button>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center text-sm font-medium text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100">
                                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/50 mr-2">
                                            <span class="text-sm font-medium leading-none text-red-700 dark:text-red-300">{{ userInitial }}</span>
                                        </span>
                                        <div>{{ userName }}</div>
                                        <div class="ml-1">
                                            <ChevronDownIcon class="h-4 w-4" />
                                        </div>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ userName }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Siswa / Wali Murid</p>
                                    </div>
                                    <DropdownLink :href="route('siswa.profil.show')">
                                        <UserCircleIcon class="mr-2 h-4 w-4 inline-block text-gray-400" />
                                        Profil Siswa Saya
                                    </DropdownLink>
                                    <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                    <DropdownLink @click="confirmLogout" as="button">
                                        <ArrowLeftStartOnRectangleIcon class="mr-2 h-4 w-4 inline-block text-gray-400" />
                                        Keluar
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                         <!-- Judul header untuk mobile -->
                        <div class="flex-1 text-center md:hidden">
                             <slot name="header" />
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="pb-16 md:pb-0">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Bottom Navbar untuk Mobile -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 flex justify-around items-center h-16 border-t border-gray-200 dark:border-gray-700 z-30">
             <template v-for="item in siswaMenu" :key="item.name + '-mobile'">
                <Link v-if="item.type === 'link'" :href="route(item.route)"
                      class="flex flex-col items-center justify-center text-xs w-full h-full pt-1 relative group">
                    <div class="relative flex items-center justify-center h-8 w-16 transition-all duration-300 ease-in-out" :class="isLinkActive(item.current) ? 'bg-red-600 rounded-full' : ''">
                        <component :is="item.icon" class="h-6 w-6" :class="isLinkActive(item.current) ? 'text-white' : 'text-gray-500 dark:text-gray-400 group-hover:text-red-500'"/>
                    </div>
                    <span :class="[isLinkActive(item.current) ? 'text-red-600 dark:text-red-500 font-bold' : 'text-gray-500 dark:text-gray-400', 'truncate mt-1 transition-all']">{{ item.name }}</span>
                </Link>
                 <button v-else-if="item.type === 'button'" @click="confirmLogout"
                      class="text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 flex flex-col items-center justify-center p-2 text-xs w-full h-full group">
                    <div class="relative flex items-center justify-center h-8 w-16">
                        <component :is="item.icon" class="h-6 w-6" />
                    </div>
                    <span class="truncate mt-1">{{ item.name }}</span>
                </button>
            </template>
        </nav>

        <!-- Modal Konfirmasi Logout -->
        <Modal :show="showLogoutConfirm" @close="showLogoutConfirm = false" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Keluar
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin keluar dari akun Anda?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showLogoutConfirm = false">Batal</SecondaryButton>
                    <DangerButton @click="logout">Ya, Keluar</DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
