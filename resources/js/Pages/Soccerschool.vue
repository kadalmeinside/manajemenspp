<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    XMarkIcon,
    MapPinIcon,
    PhoneIcon,
    ShieldCheckIcon,
    AcademicCapIcon,
    UserGroupIcon,
    TrophyIcon,
    VideoCameraIcon,
    HeartIcon,
    UsersIcon,
    InformationCircleIcon,
    ClipboardDocumentListIcon,
    SparklesIcon,
    PencilSquareIcon,

} from '@heroicons/vue/24/outline';
import { FireIcon } from '@heroicons/vue/24/solid';

// --- DATA UNTUK MODAL DETAIL CABANG ---
const branches = ref([
    { 
        id: 'ciledug',
        name: 'Ciledug', 
        thumbnail: 'https://persijadevelopment.id/assets/web_ss_ciledug_thumb.jpg',
        image: 'https://persijadevelopment.id/assets/web_ss_ciledug.jpg',
        isNew: true,
        shortDesc: 'DM Sport - Jl. Raden Fatah No.88A, Parung Serab, Ciledug.',
        description: 'Terletak di Ciledug, fasilitas ini menawarkan lingkungan pelatihan yang nyaman dengan akses mudah dari berbagai area. Dilengkapi dengan lapangan berstandar internasional dan fasilitas modern lainnya untuk memastikan setiap pemain mendapatkan pengalaman terbaik.', 
        address: 'DM Sport - Jl. Raden Fatah No.88A, RT.003/RW.006, Parung Serab, Kec. Ciledug, Kota Tangerang, Banten 15153', 
        mapsUrl: 'https://maps.app.goo.gl/qBsfhJtNpsWpVZKC6',
        cost: 'Rp 2.500.000',
        originalCost: 'Rp 3.000.000',
        promo: 'Diskon s/d Desember 2024',
        contacts: ['+6285694181500', '+6281380567692'],
        registerId: 6 
    },
    { 
        id: 'bekasi',
        name: 'Bekasi', 
        thumbnail: 'https://persijadevelopment.id/assets/ss_bekasi_thumb.jpg',
        image: 'https://persijadevelopment.id/assets/ss_bekasi_modal.jpg',
        isNew: true,
        shortDesc: 'BISF - Jl. Cut Mutia, Margahayu, Kec. Bekasi Timur.',
        description: 'Terletak di jantung kota Bekasi, fasilitas ini menawarkan lingkungan pelatihan yang nyaman dengan akses mudah dari berbagai area. Dilengkapi dengan lapangan berstandar internasional dan fasilitas modern lainnya untuk memastikan setiap pemain mendapatkan pengalaman terbaik.', 
        address: 'BSIF - Jl. Cut Mutia, RT.003/RW.009, Margahayu, Kec. Bekasi Tim., Kota Bks, Jawa Barat 17113', 
        mapsUrl: 'https://maps.app.goo.gl/ktAKew3yV8XnqeqR9', 
        cost: 'Rp 2.500.000',
        originalCost: 'Rp 3.000.000',
        promo: 'Diskon s/d Desember 2024',
        contacts: ['+6285694181500', '+6281380567692'],
        registerId: 5 
    },
    { 
        id: 'kingkong',
        name: 'Kingkong', 
        thumbnail: 'https://persijadevelopment.id/assets/web_ss_kingkong1.jpg',
        image: 'https://persijadevelopment.id/assets/web_ss_kingkong1.jpg',
        isNew: false,
        shortDesc: 'Jl. Yonkav 7, Cijantung, Kec. Ps. Rebo, Jakarta Timur.',
        description: 'Berlokasi strategis di Jakarta Timur, Soccer School Kingkong menyediakan pelatihan intensif dengan fokus pada pengembangan keterampilan individu dan tim. Fasilitas ini juga menawarkan lingkungan yang aman dan mendukung untuk pemain muda.', 
        address: 'Jl. Yonkav 7 No.7, Cijantung, Kec. Ps. Rebo, Kota Jakarta Timur, DKI Jakarta 13780', 
        mapsUrl: 'https://maps.app.goo.gl/3YigKC7wkco59RGL9', 
        cost: 'Rp 2.500.000',
        contacts: ['+628112626324', '+6281285400641'],
        registerId: 2 
    },
    { 
        id: 'pulomas',
        name: 'Pulomas', 
        thumbnail: 'https://persijadevelopment.id/assets/web_ss_pulomas1.jpg',
        image: 'https://persijadevelopment.id/assets/web_ss_pulomas1.jpg',
        isNew: false,
        shortDesc: 'Jl. Pulomas I, Kec. Pulo Gadung, Jakarta Timur.',
        description: 'Persija Soccer School Pulomas menawarkan program latihan sepak bola profesional dengan fasilitas lengkap, termasuk lapangan berstandar internasional dan gym modern. Terletak strategis di Pulomas, sekolah ini menyediakan pelatihan untuk semua tingkat kemampuan.', 
        address: 'Jl. Pulomas I RT.8/RW.12, Kec. Pulo Gadung, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13210', 
        mapsUrl: 'https://maps.app.goo.gl/vb4pbP5uqSNXVcvQ9', 
        cost: 'Rp 2.500.000',
        contacts: ['+628112626325', '+6281285400651'],
        registerId: 3 
    },
     { 
        id: 'sawangan',
        name: 'Sawangan', 
        thumbnail: 'https://persijadevelopment.id/assets/web_ss_sawangan.jpg',
        image: 'https://persijadevelopment.id/assets/web_ss_sawangan.jpg',
        isNew: false,
        shortDesc: 'Jl. Rotan, Bojongsari Baru, Kec. Bojongsari, Depok.',
        description: 'Terletak di Depok, Sawangan Soccer School menawarkan lingkungan pelatihan yang tenang dan kondusif. Fasilitas ini dilengkapi dengan lapangan rumput berkualitas tinggi dan ruang latihan yang modern, ideal untuk mengembangkan bakat sepak bola pemain muda.', 
        address: 'Jl. Rotan, Bojongsari Baru, Kec. Bojongsari, Kota Depok, Jawa Barat 16516', 
        mapsUrl: 'https://maps.app.goo.gl/BvHxqC5dZvyUG8sg7', 
        cost: 'Rp 2.500.000',
        contacts: ['+628112626326', '+6281285400661'],
        registerId: 4 
    },
]);

const isModalOpen = ref(false);
const selectedBranch = ref(null);

const openModal = (branch) => {
    selectedBranch.value = branch;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};


// --- STICKY MENU LOGIC ---
const menuItems = ref([
    { id: 'tentang-kami', name: 'TENTANG', icon: InformationCircleIcon },
    { id: 'program-kegiatan', name: 'PROGRAM', icon: ClipboardDocumentListIcon },
    { id: 'fasilitas-kami', name: 'FASILITAS', icon: SparklesIcon },
    { id: 'info-kontak', name: 'KONTAK', icon: MapPinIcon },
    { id: 'syarat-pendaftaran', name: 'DAFTAR', icon: PencilSquareIcon }
]);

const isMenuSticky = ref(false);
const activeSection = ref('');
const navObserverRef = ref(null);

onMounted(() => {
    // Observer for sticky menu. We watch when the initial nav position exits the viewport.
    const stickyObserver = new IntersectionObserver(
        ([entry]) => {
            isMenuSticky.value = !entry.isIntersecting;
        },
        { threshold: 0, rootMargin: "-80px 0px 0px 0px" } // 80px is the main navbar height
    );
    if (navObserverRef.value) {
        stickyObserver.observe(navObserverRef.value);
    }

    // Observer for active section highlighting (scrollspy)
    const sectionObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                }
            });
        },
        { rootMargin: '-40% 0px -55% 0px' }
    );

    menuItems.value.forEach(item => {
        const el = document.getElementById(item.id);
        if (el) {
            sectionObserver.observe(el);
        }
    });

    onUnmounted(() => {
        if(navObserverRef.value) stickyObserver.unobserve(navObserverRef.value);
        sectionObserver.disconnect();
    });
});
</script>

<template>
    <Head title="Persija Soccer School" />
    <PublicLayout>

        <template #page-header>
            <section class="relative bg-gray-900 text-white text-center" style="background-image: url('https://images.unsplash.com/photo-1552318965-6e6be7484ada?q=80&w=2940&auto=format&fit=crop'); background-size: cover; background-position: center;">
                 <div class="absolute inset-0 bg-black/60"></div>
                 <div class="relative container-app z-10 pt-28 pb-32">
                    <h1 class="font-header text-5xl md:text-6xl font-bold uppercase">Persija Soccer School</h1>
                    <p class="mt-2 text-lg text-gray-300">Gerbang awal Anda menuju tim profesional.</p>
                 </div>
                 <!-- Placeholder for Intersection Observer -->
                 <div ref="navObserverRef" class="absolute bottom-14 w-full h-1"></div>
            </section>
        </template>
        
        <nav class="transition-all duration-300 z-40" :class="{'sticky top-20 shadow-lg': isMenuSticky, 'relative -top-12': !isMenuSticky}">
            <div class="absolute inset-x-0 top-0 flex justify-center">
                 <div class="flex items-center justify-center gap-2 bg-white/80 backdrop-blur-md p-2 rounded-full shadow-lg">
                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center justify-center p-1">
                        <a v-for="item in menuItems" :key="item.id + '-desktop'" :href="'#' + item.id"
                           class="roadmap-link"
                           :class="{'roadmap-link-active': activeSection === item.id}">
                            {{ item.name }}
                        </a>
                    </div>
                    <!-- Mobile Nav -->
                    <div class="flex gap-2 md:hidden items-center justify-center">
                        <a v-for="item in menuItems" :key="item.id + '-mobile'" :href="'#' + item.id"
                           class="floating-nav-item group"
                           :class="{'floating-nav-active': activeSection === item.id}">
                            <component :is="item.icon" class="h-6 w-6" />
                            <span class="floating-nav-tooltip group-hover:scale-100">{{ item.name }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="bg-slate-50 text-gray-700">
            <section id="tentang-kami" class="pt-20 pb-20 md:py-28">
                <div class="container-app text-center">
                    <h2 class="section-title">Tentang <span class="text-primary">Persija Soccer School</span></h2>
                    <p class="section-subtitle">
                        Persija Soccer School merupakan program pembinaan penunjang untuk mempersiapkan pemain muda U10-U16 untuk naik ke Persija Elite Pro Academy (EPA). Seluruh program terintegrasi sebagai jenjang untuk menuju ke Tim Profesional Persija Jakarta.
                    </p>
                </div>
            </section>

            <section id="program-kegiatan" class="py-20 md:py-28 bg-gray-900 text-white">
                <div class="container-app">
                     <div class="text-center">
                        <h2 class="section-title text-white">Program & Kegiatan</h2>
                        <p class="section-subtitle text-gray-400">Aktivitas lengkap untuk pengembangan skill dan mental.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8 text-center">
                        <div class="activity-item"><UsersIcon class="h-8 w-8 mx-auto text-primary"/> Latihan Tim</div>
                        <div class="activity-item"><UserGroupIcon class="h-8 w-8 mx-auto text-primary"/> Spesialis Individu</div>
                        <div class="activity-item"><VideoCameraIcon class="h-8 w-8 mx-auto text-primary"/> Analisis Video</div>
                        <div class="activity-item"><AcademicCapIcon class="h-8 w-8 mx-auto text-primary"/> Sesi Gym</div>
                        <div class="activity-item"><TrophyIcon class="h-8 w-8 mx-auto text-primary"/> Try In/Out & Festival</div>
                        <div class="activity-item"><HeartIcon class="h-8 w-8 mx-auto text-primary"/> Kelas Psikologi & Gizi</div>
                    </div>
                </div>
            </section>
            
            <section id="fasilitas-kami" class="py-20 md:py-28">
                <div class="container-app">
                    <div class="text-center">
                        <h2 class="section-title">FASILITAS KAMI</h2>
                        <p class="section-subtitle">Didukung fasilitas modern untuk menunjang performa terbaik.</p>
                    </div>
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                        <div class="feature-card">
                            <div class="feature-card-icon"><ShieldCheckIcon class="h-8 w-8"/></div>
                            <h4 class="feature-card-title">Lapangan Standar Internasional</h4>
                            <p class="feature-card-desc">Berlatih di lapangan berkualitas tinggi yang mendukung perkembangan teknik dan taktik permainan.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-card-icon"><AcademicCapIcon class="h-8 w-8"/></div>
                            <h4 class="feature-card-title">Peralatan Latihan Modern</h4>
                            <p class="feature-card-desc">Menggunakan peralatan terkini untuk memaksimalkan setiap sesi latihan dan analisis performa.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-card-icon"><UserGroupIcon class="h-8 w-8"/></div>
                            <h4 class="feature-card-title">Lingkungan Aman & Nyaman</h4>
                            <p class="feature-card-desc">Menyediakan lingkungan yang kondusif dan aman untuk fokus pada pengembangan diri.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="info-kontak" class="py-20 md:py-28 bg-white">
                <div class="container-app">
                    <div class="text-center">
                        <h2 class="section-title">INFO & KONTAK CABANG</h2>
                        <p class="section-subtitle">Pilih lokasi terdekat untuk memulai perjalanan sepak bola Anda bersama kami.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="branch in branches" :key="branch.id" class="branch-card group">
                            <img :src="branch.thumbnail" :alt="branch.name" class="branch-card-img">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent/20"></div>
                            <div class="absolute inset-0 p-6 flex flex-col justify-end text-white">
                                <h3 class="font-bold text-2xl">{{ branch.name }}</h3>
                                <p class="mt-1 text-sm text-white/80">{{ branch.shortDesc }}</p>
                                <button @click="openModal(branch)" class="btn btn-branch-detail mt-4 opacity-0 group-hover:opacity-100 translate-y-4 group-hover:translate-y-0 transition-all duration-300">
                                    Lihat Detail
                                </button>
                            </div>
                            <div v-if="branch.isNew" class="badge-fire">
                                <FireIcon class="h-4 w-4" />
                                <span>BARU</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="syarat-pendaftaran" class="py-20 md:py-28 bg-gray-50">
                <div class="container-app">
                    <!-- Desktop Layout -->
                    <div class="hidden md:grid md:grid-cols-2 gap-14 items-center">
                        <div>
                            <h2 class="font-header text-4xl font-bold text-zinc-900 uppercase">Syarat & Cara Pendaftaran</h2>
                            <ul class="timeline mt-6">
                                <li>
                                    <h4 class="timeline-title">Terbuka Untuk Umum</h4>
                                    <p class="timeline-desc">Pemain berusia 8 sampai 20 tahun.</p>
                                </li>
                                <li>
                                    <h4 class="timeline-title">Unduh Booklet</h4>
                                    <p class="timeline-desc">Dapatkan informasi lengkap melalui booklet resmi.</p>
                                </li>
                                <li>
                                    <h4 class="timeline-title">Lakukan Pembayaran</h4>
                                    <p class="timeline-desc">Transfer ke rekening resmi: BCA 4735499999 a/n PT PERSIJA JAKARTA HEBAT.</p>
                                </li>
                                <li>
                                    <h4 class="timeline-title">Daftar Online</h4>
                                    <p class="timeline-desc">Isi formulir pendaftaran online dan unggah bukti pembayaran.</p>
                                </li>
                            </ul>
                            <div class="mt-8">
                                <Link :href="route('pendaftaran.create')" class="btn btn-primary">Daftar Online Sekarang</Link>
                            </div>
                        </div>
                        <div class="relative">
                            <img src="https://persijadevelopment.id/assets/ferarri.png" class="rounded-xl w-full" alt="Pemain bersiap mendaftar">
                        </div>
                    </div>
                    <!-- Mobile Layout -->
                    <div class="md:hidden relative rounded-2xl overflow-hidden shadow-lg">
                        <img src="https://persijadevelopment.id/assets/ferarri.png" class="absolute top-0 left-0 w-full h-2/3 object-contain object-top" alt="Pemain bersiap mendaftar">
                        <div class="relative mt-96">
                            <div class="p-6 bg-gradient-to-t from-gray-50 via-gray-50 to-transparent">
                                 <div class="p-6 bg-gray-50 rounded-b-2xl -m-6">
                                    <h2 class="font-header text-2xl font-bold text-zinc-900 uppercase">Syarat & Cara Pendaftaran</h2>
                                    <ul class="timeline mt-6">
                                        <li>
                                            <h4 class="timeline-title">Terbuka Untuk Umum</h4>
                                            <p class="timeline-desc">Pemain berusia 8 sampai 20 tahun.</p>
                                        </li>
                                        <li>
                                            <h4 class="timeline-title">Unduh Booklet</h4>
                                            <p class="timeline-desc">Dapatkan informasi lengkap melalui booklet resmi.</p>
                                        </li>
                                        <li>
                                            <h4 class="timeline-title">Lakukan Pembayaran</h4>
                                            <p class="timeline-desc">Transfer ke rekening resmi: BCA 4735499999 a/n PT PERSIJA JAKARTA HEBAT.</p>
                                        </li>
                                        <li>
                                            <h4 class="timeline-title">Daftar Online</h4>
                                            <p class="timeline-desc">Isi formulir pendaftaran online dan unggah bukti pembayaran.</p>
                                        </li>
                                    </ul>
                                    <div class="mt-6">
                                         <Link :href="route('pendaftaran.create')" class="btn btn-primary w-full">Daftar Online Sekarang</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Modal Detail Cabang -->
        <div v-if="isModalOpen" @click="closeModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 transition-opacity duration-300" :class="isModalOpen ? 'opacity-100' : 'opacity-0'">
            <div @click.stop class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] flex flex-col transition-transform duration-300" :class="isModalOpen ? 'scale-100' : 'scale-95'">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Detail Cabang {{ selectedBranch.name }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-800"><XMarkIcon class="h-6 w-6" /></button>
                </div>
                <div class="overflow-y-auto p-6 space-y-6">
                    <img :src="selectedBranch.image" :alt="selectedBranch.name" class="w-full h-64 object-cover rounded-lg">
                    <p class="text-gray-600">{{ selectedBranch.description }}</p>
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800">Alamat</h4>
                            <p class="text-gray-600 mt-1">{{ selectedBranch.address }}</p>
                            <a :href="selectedBranch.mapsUrl" target="_blank" class="inline-flex items-center gap-2 mt-2 text-sm font-semibold text-primary hover:underline">
                                <MapPinIcon class="h-4 w-4"/> Lihat di Peta
                            </a>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800">Biaya Pendaftaran</h4>
                            <p class="text-2xl font-bold text-gray-900 mt-2">{{ selectedBranch.cost }}</p>
                            <p v-if="selectedBranch.originalCost" class="text-sm text-gray-500">
                                <span class="line-through">{{ selectedBranch.originalCost }}</span>
                                <span v-if="selectedBranch.promo" class="ml-2 font-semibold text-green-600">{{ selectedBranch.promo }}</span>
                            </p>
                            <ul class="text-sm text-gray-600 list-disc list-inside mt-2">
                                <li>Termasuk SPP bulan pertama</li>
                                <li>1 Set Training Jersey & Equipment</li>
                            </ul>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800">Kontak (WA Only)</h4>
                            <div class="mt-2 space-y-2">
                                <a v-for="contact in selectedBranch.contacts" :key="contact" :href="'https://wa.me/' + contact.replace(/\D/g, '')" target="_blank" class="flex items-center gap-2 text-sm text-gray-700 hover:text-primary">
                                    <PhoneIcon class="h-4 w-4"/> {{ contact }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                 <div class="p-4 bg-gray-50 border-t">
                    <Link :href="route('pendaftaran.create')" class="btn btn-primary w-full">Daftar di Cabang {{ selectedBranch.name }}</Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Oswald:wght@500;600;700&display=swap');

:root {
    --primary-color: #D9262E; /* Persija Red */
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 150px; 
}
body { 
    font-family: 'Inter', sans-serif; 
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.font-header { 
    font-family: 'Oswald', sans-serif; 
}

.text-primary { color: var(--primary-color); }

/* Global Container */
.container-app {
    @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
}

/* Button Styles */
.btn {
    @apply inline-block font-semibold text-center rounded-lg px-8 py-3 transition-all duration-300;
}
.btn-primary {
    background-color: var(--primary-color);
    @apply text-white shadow-md shadow-red-500/20 hover:bg-red-700 hover:shadow-lg hover:shadow-red-500/30 hover:-translate-y-0.5;
}
.btn-secondary {
    @apply bg-white border border-gray-300 text-gray-800 hover:bg-gray-100 hover:border-gray-400 hover:shadow-sm hover:-translate-y-0.5;
}
.btn-branch-detail {
    background-color: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    @apply text-white text-sm w-full py-2.5;
}
.btn-branch-detail:hover {
    background-color: rgba(255, 255, 255, 0.25);
}


/* Typography Styles */
.section-title {
    @apply font-header text-4xl font-bold text-zinc-900 md:text-5xl uppercase;
}
.section-title.text-white {
    color: white; 
}

.section-subtitle {
    @apply mt-2 max-w-3xl mx-auto text-base text-zinc-600 md:text-lg;
}

/* Roadmap Menu Styles */
.roadmap-link {
    @apply whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-500 border-b-2 border-transparent transition-colors duration-300;
}
.roadmap-link:hover {
    color: var(--primary-color);
}
.roadmap-link-active {
    color: var(--primary-color);
    border-color: var(--primary-color);
}
.roadmap-link-banner {
    @apply whitespace-nowrap px-4 py-3 text-sm font-semibold text-white/80 border-b-2 border-transparent transition-colors duration-300 hover:text-white;
}

/* Floating Nav (Mobile) */
.floating-nav-item {
    @apply relative flex items-center justify-center w-11 h-11 rounded-full text-gray-500 bg-white transition-all duration-300;
}
.floating-nav-active {
    background-color: var(--primary-color);
    @apply text-white scale-110;
}
.floating-nav-tooltip {
    @apply absolute bottom-full mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded-md whitespace-nowrap scale-0 origin-right transition-transform duration-200;
}


/* Branch Card */
.branch-card {
    @apply relative bg-gray-200 rounded-xl overflow-hidden transition-all duration-300 h-80;
}
.branch-card:hover {
    @apply shadow-xl shadow-gray-300/50 transform -translate-y-2;
}
.branch-card-img {
    @apply absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500;
}

/* Activity Item */
.activity-item {
    @apply text-sm font-medium space-y-2;
}

/* Timeline */
ul.timeline {
    @apply relative border-l-2 ml-4;
    border-color: rgba(217, 38, 46, 0.2);
}
ul.timeline > li {
    @apply mb-8 ml-8;
}
ul.timeline > li::before {
    content: ' ';
    border-color: var(--primary-color);
    @apply absolute -left-[9px] w-4 h-4 rounded-full bg-slate-50 border-2;
}
.timeline-title {
    @apply font-bold text-lg text-gray-900;
}
.timeline-desc {
    @apply mt-1 text-base text-gray-600;
}
.timeline-mobile {
    @apply space-y-3 list-disc list-inside text-gray-700;
}

/* Feature Card */
.feature-card {
    @apply bg-white p-8 rounded-xl border border-gray-100 transition-all duration-300 hover:border-red-500/50 hover:shadow-xl hover:-translate-y-1;
}
.feature-card-icon {
    @apply w-16 h-16 rounded-full bg-red-50 text-red-500 flex items-center justify-center mx-auto;
}
.feature-card-title {
    @apply mt-5 font-bold text-lg text-zinc-900;
}
.feature-card-desc {
    @apply mt-2 text-sm text-zinc-600;
}

/* Badge Fire */
.badge-fire {
    @apply absolute top-4 right-4 flex items-center gap-1 text-white text-xs font-bold px-3 py-1.5 rounded-full z-10;
    background: linear-gradient(to right top, #f97316, #ef4444); /* orange-500 to red-500 */
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
}
</style>