<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, usePage, router, Head } from '@inertiajs/vue3';
import JobStatusToast from '@/Components/JobStatusToast.vue';
import GlobalLoader from '@/Components/GlobalLoader.vue';
import {
    HomeIcon, UsersIcon, UserCircleIcon, ShieldCheckIcon, Cog6ToothIcon, ArrowLeftStartOnRectangleIcon,
    XMarkIcon, ChevronDownIcon, BellIcon, BuildingOfficeIcon, UserGroupIcon, DocumentChartBarIcon, ChartBarIcon,
    ChevronRightIcon, CurrencyDollarIcon, CalendarDaysIcon, QueueListIcon, ArrowDownTrayIcon, CheckBadgeIcon,
    BellSlashIcon, ClockIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const showLogoutConfirmModal = ref(false);

const deferredPrompt = ref(null);
const installPWA = async () => {
    if (!deferredPrompt.value) return;
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
        deferredPrompt.value = null;
    }
};

const confirmLogout = () => {
    nextTick(() => {
        showLogoutConfirmModal.value = true;
    });
};

const logout = () => {
    router.post(route('logout'), {
        onSuccess: () => {
            showLogoutConfirmModal.value = false;
        }
    });
};

const showJobToast = ref(false);
const jobStatus = ref('');
const jobMessage = ref('');
const jobProgress = ref(0);

const unreadNotifications = ref([]);
const unreadCount = computed(() => unreadNotifications.value.length);
const showNotificationsModal = ref(false);

const isWebPushEnabled = ref(false);

const fetchNotifications = async () => {
    if (!user.value || !userRoles.value.some(role => ['super_admin', 'admin', 'admin_kelas', 'staff_akademik'].includes(role))) return;
    try {
        const response = await axios.get(route('admin.notifications.unread'));
        unreadNotifications.value = response.data.notifications;
    } catch (e) {
        console.error('Failed to fetch notifications', e);
    }
};

const markAsRead = async (notification) => {
    try {
        await axios.post(route('admin.notifications.read', notification.id));
        unreadNotifications.value = unreadNotifications.value.filter(n => n.id !== notification.id);
        
        // Cek apakah tidak ada lagi notifikasi yang belum dibaca
        if (unreadNotifications.value.length === 0) {
            showNotificationsModal.value = false;
        }

        if (notification.data && notification.data.url) {
            showNotificationsModal.value = false;
            router.visit(notification.data.url);
        }
    } catch (e) {
        console.error('Failed to mark as read', e);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post(route('admin.notifications.read_all'));
        unreadNotifications.value = [];
    } catch (e) {
        console.error(e);
    }
};

// --- WEB PUSH NOTIFICATIONS ---
const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
};

const subscribeToWebPush = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    
    try {
        const registration = await navigator.serviceWorker.ready;
        const vapidPublicKey = page.props.auth?.vapid_public_key;
        
        if (!vapidPublicKey) {
            console.warn('⚠️ VAPID Public Key tidak ditemukan. Web Push tidak bisa diaktifkan.');
            return;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');

        await axios.post(route('admin.push-subscriptions.store'), {
            endpoint: subscription.endpoint,
            keys: {
                p256dh: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
                auth: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
            }
        });
        
        isWebPushEnabled.value = true;
        console.log('✅ Web Push (Native) berhasil diaktifkan dan didaftarkan di server!');
        
    } catch (e) {
        console.error('❌ Gagal mengaktifkan Web Push:', e);
    }
};

const askForNotificationPermission = async () => {
    if (!('Notification' in window)) return;
    
    if (Notification.permission === 'default') {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            subscribeToWebPush();
        }
    } else if (Notification.permission === 'granted') {
        subscribeToWebPush();
    }
};

onMounted(() => {
        window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
    });
    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
    });
    
    if ('Notification' in window) {
        isWebPushEnabled.value = Notification.permission === 'granted';
    }

    if (window.Echo && user.value) {
        window.Echo.private(`App.Models.User.${user.value.id}`)
            .listen('.mass-invoice.status', (e) => {
                jobStatus.value = e.status;
                jobMessage.value = e.message;
                jobProgress.value = e.progress;
                showJobToast.value = true;
                if (e.status === 'finished' || e.status === 'failed') {
                    setTimeout(() => { showJobToast.value = false; }, 8000);
                }
            })
            .notification((notification) => {
                // Notifikasi baru masuk via Pusher!
                console.log('🔔 [PUSHER] Notifikasi baru diterima:', notification);
                
                // Laravel Echo's .notification() automatically unwraps the event data.
                // It gives us the exact payload we sent in toBroadcast().
                const notifData = notification;
                
                unreadNotifications.value.unshift({
                    id: notifData.id || Date.now(),
                    data: notifData.data || notifData,
                    created_at: notifData.created_at || new Date().toISOString(),
                });
            });
            
        console.log(`✅ Pusher (Laravel Echo) berhasil terkoneksi! Mendengarkan channel: App.Models.User.${user.value.id}`);
            
        fetchNotifications();
        
        if (userRoles.value.some(role => ['super_admin', 'admin', 'admin_kelas', 'staff_akademik'].includes(role))) {
            setTimeout(() => {
                askForNotificationPermission();
            }, 3000);
        }
    }
});

const desktopSidebarOpen = ref(true);
const mobileSidebarOpen = ref(false);
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Manajemen SPP');
const appLogo = computed(() => appSettings.value.app_logo || null);
const pendingLeavesCount = computed(() => page.props.pending_leaves_count || 0);
const pendingAktivasiSppCount = computed(() => page.props.pending_aktivasi_spp_count || 0);

const getBadgeCount = (item) => {
    if (item.badgeType === 'cuti') return pendingLeavesCount.value;
    if (item.badgeType === 'aktivasi_spp') return pendingAktivasiSppCount.value;
    return 0;
};

const openMenus = ref({});
function toggleMenu(menuKey) {
    openMenus.value[menuKey] = !openMenus.value[menuKey];
}

const themes = {
    gray: { 50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 300: '#d1d5db', 400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 700: '#374151', 800: '#1f2937', 900: '#111827' },
    maroon: { 50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d' },
    indigo: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' },
    blue: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
    teal: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a' },
    rose: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337' },
};
const currentTheme = ref(localStorage.getItem('app-theme') || 'indigo');
const applyTheme = (themeName) => {
    const themeColors = themes[themeName];
    if (!themeColors) { applyTheme('indigo'); return; }
    const root = document.documentElement;
    for (const [shade, color] of Object.entries(themeColors)) {
        root.style.setProperty(`--color-primary-${shade}`, color);
    }
    localStorage.setItem('app-theme', themeName);
    currentTheme.value = themeName;
};
watch(currentTheme, (newTheme) => applyTheme(newTheme));
onMounted(() => applyTheme(currentTheme.value));


// --- HELPERS ---
const hasRole = (roleName) => userRoles.value.includes(roleName);
const hasPermission = (permissionName) => userPermissions.value.includes(permissionName);
function isLinkActive(pattern) {
    if (!pattern) return false;
    const currentRoute = route().current();
    if (!currentRoute) return false;
    return route().current(pattern) || currentRoute.startsWith(pattern.replace('.*', '.'));
}
function isMenuActive(menuItem) {
    if (isLinkActive(menuItem.current)) return true;
    if (menuItem.children && menuItem.children.length > 0) {
        return menuItem.children.some(child => isLinkActive(child.current));
    }
    return false;
}


const mainMenu = computed(() => {
    const dashboardItem = {
        name: 'Dashboard', icon: HomeIcon,
        route: hasRole('admin') || hasRole('staff_akademik') ? 'admin.dashboard' : 'dashboard',
        current: hasRole('admin') || hasRole('staff_akademik') ? 'admin.dashboard' : 'dashboard',
    };
    const siswaMenuItems = [
        { name: 'Profil Saya', route: 'siswa.profil.show', icon: UserCircleIcon, current: 'siswa.profil.*', requiredRole: 'siswa' },
        { name: 'Tagihan Saya', route: 'siswa.tagihan.index', icon: DocumentChartBarIcon, current: 'siswa.tagihan.*', requiredRole: 'siswa' },
    ];
    return [dashboardItem, ...siswaMenuItems.filter(item => hasRole(item.requiredRole))];
});

const adminMenu = [
    { name: 'Statistik & Tren', route: 'admin.analytics.index', icon: ChartBarIcon, current: 'admin.analytics.index', requiredPermission: 'manage_all_tagihan' },
    { name: 'Laporan Pembayaran', route: 'admin.laporan.pembayaran_bulanan', icon: DocumentChartBarIcon, current: 'admin.laporan.pembayaran_bulanan', requiredPermission: 'manage_all_tagihan' },
    { name: 'Riwayat Aktivitas', route: 'admin.laporan.aktivitas', icon: QueueListIcon, current: 'admin.laporan.aktivitas', requiredPermission: 'manage_all_tagihan' },
    { name: 'Manajemen Invoice', route: 'admin.invoices.index', icon: DocumentChartBarIcon, current: 'admin.invoices.*', requiredPermission: 'manage_all_tagihan' },
    { name: 'Aktivasi SPP', route: 'admin.siswa.pendaftar_lunas', icon: CheckBadgeIcon, current: 'admin.siswa.pendaftar_lunas', requiredPermission: 'view_siswa', badgeType: 'aktivasi_spp' },
    { name: 'Manajemen Siswa', route: 'admin.siswa.index', icon: UserGroupIcon, current: 'admin.siswa.index', requiredPermission: 'view_siswa' },
    { name: 'Manajemen Kelas', route: 'admin.kelas.index', icon: BuildingOfficeIcon, current: 'admin.kelas.*', requiredPermission: 'manage_kelas' },
    { name: 'Manajemen Promo', route: 'admin.promos.index', icon: CurrencyDollarIcon, current: 'admin.promos.*', requiredPermission: 'manage_kelas' },
    { name: 'Pengajuan Cuti', route: 'admin.leaves.index', icon: CalendarDaysIcon, current: 'admin.leaves.*', requiredPermission: 'manage_all_tagihan', badgeType: 'cuti' },
];

const systemMenu = {
    name: 'Pengaturan Sistem',
    icon: Cog6ToothIcon,
    current: 'admin.users.*,admin.roles.*,admin.permissions.*,admin.settings.*,admin.jobs.*,admin.activity.index',
    requiredPermission: 'manage users',
    children: [
        { name: 'Dokumen Legal', route: 'admin.legal-documents.index', icon: DocumentChartBarIcon, current: 'admin.legal-documents.*', requiredPermission: 'manage application settings' },
        { name: 'Users', route: 'admin.users.index', icon: UsersIcon, current: 'admin.users.*', requiredPermission: 'manage users' },
        { name: 'Roles', route: 'admin.roles.index', icon: UserCircleIcon, current: 'admin.roles.*', requiredPermission: 'manage roles' },
        { name: 'Permissions', route: 'admin.permissions.index', icon: ShieldCheckIcon, current: 'admin.permissions.*', requiredPermission: 'manage permissions' },
        { name: 'Pengaturan Aplikasi', route: 'admin.settings.index', icon: Cog6ToothIcon, current: 'admin.settings.*', requiredPermission: 'manage application settings' },
        { name: 'Riwayat Proses', route: 'admin.jobs.index', icon: ChartBarIcon, current: 'admin.jobs.*', requiredPermission: 'manage application settings' },
        { name: 'Log Aktivitas', route: 'admin.activity.index', icon: DocumentChartBarIcon, current: 'admin.activity.index', requiredPermission: 'manage users' },
    ]
};

const canViewAdminMenu = computed(() => adminMenu.some(item => hasPermission(item.requiredPermission)));
const canViewSystemMenu = computed(() => hasPermission(systemMenu.requiredPermission));

const activeMenuName = computed(() => {
    const allMenus = [
        ...mainMenu.value,
        ...adminMenu,
        ...(systemMenu.children || [])
    ];
    const active = allMenus.find(item => isMenuActive(item));
    return active ? active.name : 'Dashboard';
});

</script>

<template>
    <Head :title="$page.props.pageTitle || 'Admin Area'">
        <link rel="icon" type="image/x-icon" :href="$page.props.app_settings?.app_logo ? `/storage/${$page.props.app_settings.app_logo}` : '/favicon.ico'">
    </Head>

    <div class="relative h-screen flex overflow-hidden bg-gray-100 dark:bg-gray-900">
        <div v-if="mobileSidebarOpen" @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity md:hidden" aria-hidden="true"></div>

        <aside :class="[
                    'fixed inset-y-0 left-0 z-50 bg-gray-900 text-gray-300 transform transition-transform duration-300 ease-in-out md:sticky md:translate-x-0 md:flex md:flex-col',
                    mobileSidebarOpen ? 'translate-x-0 w-64 sm:w-72' : '-translate-x-full w-64 sm:w-72',
                    desktopSidebarOpen ? 'md:w-64' : 'md:w-20'
                ]">
            <div class="h-16 flex items-center justify-between px-4 bg-black/20 flex-shrink-0">
                <Link :href="hasRole('admin') ? route('admin.dashboard') : route('dashboard')" @click="mobileSidebarOpen = false" class="flex items-center overflow-hidden">
                    <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="block h-9 w-auto">
                    <ApplicationLogo v-else class="block h-9 w-auto fill-current text-white" />
                    
                    <span v-show="desktopSidebarOpen || mobileSidebarOpen" class="ml-3 text-white text-lg font-semibold truncate">{{ appName }}</span>
                </Link>
                <button @click="mobileSidebarOpen = false" class="text-gray-400 hover:text-white md:hidden">
                    <span class="sr-only">Close sidebar</span>
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                <h3 v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Utama</h3>
                <template v-for="item in mainMenu" :key="item.name">
                    <div v-if="!item.requiredRole || hasRole(item.requiredRole)">
                        <Link :href="item.route ? route(item.route) : '#'"
                              @click="mobileSidebarOpen = false"
                              :class="['flex items-center px-2 py-2 text-sm font-medium rounded-md group', isMenuActive(item) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white']">
                            <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                            <span v-show="desktopSidebarOpen || mobileSidebarOpen">{{ item.name }}</span>
                        </Link>
                    </div>
                </template>

                <template v-if="canViewAdminMenu">
                    <h3 v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Manajemen</h3>
                    <template v-for="item in adminMenu" :key="item.name">
                         <Link v-if="hasPermission(item.requiredPermission)"
                               :href="item.route ? route(item.route) : '#'"
                               @click="mobileSidebarOpen = false"
                               :class="['flex items-center px-2 py-2 text-sm font-medium rounded-md group', isMenuActive(item) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white']">
                            <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                            <span v-show="desktopSidebarOpen || mobileSidebarOpen" class="flex-1">{{ item.name }}</span>
                            <!-- Badge notifikasi -->
                            <span
                                v-if="item.badgeType && getBadgeCount(item) > 0 && (desktopSidebarOpen || mobileSidebarOpen)"
                                class="ml-auto inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 text-xs font-bold rounded-full bg-red-500 text-white"
                            >{{ getBadgeCount(item) > 99 ? '99+' : getBadgeCount(item) }}</span>
                        </Link>
                    </template>
                </template>

                <!-- PERBAIKAN: Tampilkan menu sistem sebagai dropdown -->
                <template v-if="canViewSystemMenu">
                    <h3 v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistem</h3>
                     <div>
                        <button @click="toggleMenu('system')" :class="['w-full flex items-center px-2 py-2 text-sm font-medium rounded-md group', isMenuActive(systemMenu) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white']">
                            <component :is="systemMenu.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                            <span class="flex-1 text-left" v-show="desktopSidebarOpen || mobileSidebarOpen">{{ systemMenu.name }}</span>
                            <component :is="openMenus['system'] ? ChevronDownIcon : ChevronRightIcon" v-show="desktopSidebarOpen || mobileSidebarOpen" class="ml-1 flex-shrink-0 h-4 w-4 transform transition-transform duration-150" />
                        </button>
                        <div v-show="openMenus['system']" class="mt-1 ml-4 pl-1 space-y-1 border-l border-gray-800">
                             <template v-for="child in systemMenu.children" :key="child.name">
                                <Link v-if="hasPermission(child.requiredPermission)"
                                      :href="child.route ? route(child.route) : '#'"
                                      @click="mobileSidebarOpen = false"
                                      :class="['block px-2 py-1.5 text-sm font-medium rounded-md', isLinkActive(child.current) ? 'text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800']">
                                    <span v-show="desktopSidebarOpen || mobileSidebarOpen">{{ child.name }}</span>
                                </Link>
                             </template>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Bagian Akun (Hanya Mobile) -->
            <div class="md:hidden border-t border-gray-800 pt-4 pb-2">
                <h3 v-show="mobileSidebarOpen" class="px-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Akun ({{ userName }})</h3>
                <div class="space-y-1">
                    <Link :href="route('profile.edit')" @click="mobileSidebarOpen = false" class="flex items-center px-2 py-2 text-sm font-medium rounded-md group text-gray-300 hover:bg-gray-800 hover:text-white">
                        <Cog6ToothIcon class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                        <span v-show="mobileSidebarOpen">Profil</span>
                    </Link>
                    <button @click="confirmLogout" class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md group text-gray-300 hover:bg-gray-800 hover:text-white text-left">
                        <ArrowLeftStartOnRectangleIcon class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                        <span v-show="mobileSidebarOpen">Keluar</span>
                    </button>
                </div>
            </div>

            <div v-if="deferredPrompt" v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 mt-auto pb-4">
                <button @click="installPWA" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <ArrowDownTrayIcon class="mr-2 h-4 w-4" />
                    Install Aplikasi
                </button>
            </div>
            <div v-if="!isWebPushEnabled && userRoles.some(role => ['super_admin', 'admin', 'admin_kelas', 'staff_akademik'].includes(role))" v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 mt-auto pb-4">
                <button @click="askForNotificationPermission" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition">
                    <BellIcon class="mr-2 h-4 w-4" />
                    Aktifkan Notifikasi
                </button>
            </div>
            <div v-show="desktopSidebarOpen || mobileSidebarOpen" class="flex-shrink-0 p-4 bg-black/10 border-t border-gray-800 text-center text-xs text-gray-400">
                <span v-if="appSettings.app_version || appSettings.app_build">
                    {{ appSettings.app_version ? `v${appSettings.app_version}` : '' }} 
                    {{ appSettings.app_build ? `(${appSettings.app_build})` : '' }}
                </span>
                <span v-else>v1.0.0 (Local)</span>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-gray-800 shadow-sm sticky top-0 z-30 flex-shrink-0 border-b border-gray-700">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16 relative">
                        <div class="flex items-center z-10">
                            <button @click="desktopSidebarOpen = !desktopSidebarOpen" class="hidden md:inline-flex items-center justify-center rounded-md p-2 text-gray-300 hover:text-gray-200 hover:bg-gray-700 focus:outline-none focus:bg-gray-700">
                                <span class="sr-only">Toggle desktop sidebar</span>
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            </button>
                            <!-- Mobile toggle button with rounded full background -->
                            <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="md:hidden inline-flex items-center justify-center rounded-full p-2 bg-gray-700 text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <span class="sr-only">Open sidebar</span>
                                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>

                        <!-- Title centered on mobile -->
                        <div class="absolute inset-0 flex justify-center items-center pointer-events-none md:hidden">
                            <span class="text-sm font-semibold text-white">{{ activeMenuName }}</span>
                        </div>

                        <div class="flex items-center space-x-3 z-10">
                            <button v-if="userRoles.some(role => ['super_admin', 'admin', 'admin_kelas', 'staff_akademik'].includes(role))" @click="showNotificationsModal = true" class="relative p-1 rounded-full text-gray-300 hover:text-white focus:outline-none transition-colors">
                                <span class="sr-only">Lihat Notifikasi</span>
                                <BellIcon class="h-6 w-6" aria-hidden="true" />
                                <span v-if="unreadCount > 0" class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-gray-800"></span>
                            </button>

                            <div class="relative hidden md:block">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="flex items-center text-sm font-medium text-gray-300 hover:text-white focus:outline-none transition duration-150 ease-in-out">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-[--color-primary-900] md:mr-2">
                                                <span class="text-sm font-medium leading-none text-[--color-primary-300]">{{ userInitial }}</span>
                                            </span>
                                            <div class="hidden md:block">{{ userName }}</div>
                                            <div class="ml-1 md:ml-1">
                                                <ChevronDownIcon class="h-4 w-4" />
                                            </div>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ userName }}</p>
                                        </div>
                                        <DropdownLink :href="route('profile.edit')">
                                            <Cog6ToothIcon class="mr-2 h-4 w-4 inline-block text-gray-400" /> Profil
                                        </DropdownLink>
                                        <!-- Theme chooser removed -->
                                        
                                        <button
                                            type="button"
                                            @click.stop.prevent="confirmLogout"
                                            class="w-full flex items-center px-4 py-2 text-sm text-left text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
                                        >
                                            <ArrowLeftStartOnRectangleIcon class="mr-2 h-4 w-4 inline-block text-gray-400" />
                                            Keluar
                                        </button>
                                        
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto px-4 md:px-6 py-4 md:py-6">
                <div class="pb-12 max-w-7xl mx-auto">
                    <!-- Memindahkan slot header ke dalam area konten dengan gaya lebih ringkas di mobile -->
                    <div v-if="$slots.header" class="mb-4 md:mb-6">
                        <slot name="header" />
                    </div>
                    
                    <slot />
                </div>
            </main>
        </div>
        
        <JobStatusToast 
            :show="showJobToast"
            :status="jobStatus"
            :message="jobMessage"
            :progress="jobProgress"
            @close="showJobToast = false"
        />

        <Modal :show="showLogoutConfirmModal" @close="showLogoutConfirmModal = false" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Keluar
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin keluar dari akun Anda?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showLogoutConfirmModal = false">Batal</SecondaryButton>
                    <DangerButton @click="logout" :class="{ 'opacity-25': router.processing }" :disabled="router.processing">
                        Ya, Keluar
                    </DangerButton>
                </div>
            </div>
        </Modal>
        
        <!-- Glassmorphism Notification Modal -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div v-if="showNotificationsModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 sm:p-6 pb-20 sm:pb-6">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showNotificationsModal = false"></div>

                <!-- Modal Content -->
                <div class="relative w-full sm:max-w-md bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/40 dark:border-gray-700/50 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] transform transition-all">
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-gray-200/50 dark:border-gray-700/50 flex justify-between items-center bg-white/40 dark:bg-gray-800/40">
                        <div class="flex items-center space-x-2">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
                                <BellIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Notifikasi</h3>
                            <span v-if="unreadCount > 0" class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ unreadCount }}</span>
                        </div>
                        <button @click="showNotificationsModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none transition bg-gray-100/50 dark:bg-gray-700/50 hover:bg-gray-200/50 rounded-full p-2 shadow-sm">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                    
                    <!-- Body -->
                    <div class="overflow-y-auto flex-1 p-3 sm:p-4 bg-gray-50/30 dark:bg-gray-900/30">
                        <div v-if="unreadCount === 0" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400 flex flex-col items-center justify-center h-full">
                            <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                                <BellSlashIcon class="h-10 w-10 text-gray-400 opacity-60" />
                            </div>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">Tidak ada notifikasi</p>
                            <p class="text-sm mt-1 text-gray-500">Anda sudah membaca semuanya!</p>
                            <button @click="showNotificationsModal = false" class="mt-6 px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-full shadow hover:bg-indigo-700 transition">Tutup</button>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="flex justify-end px-1 pb-1">
                                <button @click="markAllAsRead" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium transition flex items-center">
                                    <CheckBadgeIcon class="h-4 w-4 mr-1" />
                                    Tandai semua dibaca
                                </button>
                            </div>
                            <button v-for="notif in unreadNotifications" :key="notif.id" @click="markAsRead(notif)" class="w-full text-left px-5 py-4 bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-700 border border-gray-100 dark:border-gray-600/50 rounded-2xl transition shadow-sm hover:shadow-md block group relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                                <div class="flex items-start justify-between">
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition pr-2">{{ notif.data.title || 'Informasi' }}</p>
                                    <span class="inline-block h-2 w-2 rounded-full bg-red-500 mt-1.5 flex-shrink-0 animate-pulse"></span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1.5 leading-relaxed">{{ notif.data.message || '' }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3 font-medium flex items-center">
                                    <ClockIcon class="h-3 w-3 mr-1" />
                                    {{ new Date(notif.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) }}
                                </p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <GlobalLoader />
    </div>
</template>

<style scoped>
aside::-webkit-scrollbar { width: 6px; }
aside::-webkit-scrollbar-thumb { background-color: #4a5568; border-radius: 3px; }
aside::-webkit-scrollbar-track { background-color: #2d3748; }

main::-webkit-scrollbar { width: 8px; }
main::-webkit-scrollbar-thumb { background-color: #a0aec0; border-radius: 4px; }
main::-webkit-scrollbar-track { background-color: #edf2f7; }

.dark main::-webkit-scrollbar-thumb { background-color: #4a5568; }
.dark main::-webkit-scrollbar-track { background-color: #1a202c; }
</style>
