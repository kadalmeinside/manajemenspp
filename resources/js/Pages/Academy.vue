<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    InformationCircleIcon,
    ClipboardDocumentListIcon,
    SparklesIcon,
    PhoneIcon,
    PencilSquareIcon,
    ShieldCheckIcon,
    AcademicCapIcon,
    UserGroupIcon,
    TrophyIcon,
    VideoCameraIcon,
    HeartIcon,
    UsersIcon,
    BuildingLibraryIcon,
    MapPinIcon,
    CurrencyDollarIcon,

} from '@heroicons/vue/24/outline';

// --- STICKY MENU LOGIC ---
const menuItems = ref([
    { id: 'tentang-kami', name: 'TENTANG', icon: InformationCircleIcon },
    { id: 'program-kegiatan', name: 'PROGRAM', icon: ClipboardDocumentListIcon },
    { id: 'fasilitas-kami', name: 'FASILITAS', icon: SparklesIcon },
    { id: 'info-kontak', name: 'KONTAK', icon: PhoneIcon },
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
// Data untuk fasilitas
const facilities = ref([
    { name: 'Lapangan Sintetis Standar FIFA', image: 'https://persijadevelopment.id/assets/academy/lapangan-academy.jpg' },
    { name: 'Lapangan Rumput Natural', image: 'https://persijadevelopment.id/assets/academy/lapangan_rumput.jpg' },
    { name: 'Gym & Fitness Center', image: 'https://persijadevelopment.id/assets/academy/gym2.jpg' },
]);
</script>

<template>
    <Head title="Persija Academy Boarding School" />
    <PublicLayout>

        <template #page-header>
            <section class="relative bg-gray-900 text-white text-center" style="background-image: url('https://images.unsplash.com/photo-1552318965-6e6be7484ada?q=80&w=2940&auto=format&fit=crop'); background-size: cover; background-position: center;">
                 <div class="absolute inset-0 bg-black/60"></div>
                 <div class="relative container-app z-10 pt-28 pb-32">
                    <h1 class="font-header text-5xl md:text-6xl font-bold uppercase">Persija Academy Boarding School</h1>
                    <p class="mt-2 text-lg text-gray-300">Pusat pelatihan terpadu untuk calon atlet profesional.</p>
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
            <section id="tentang-kami" class="py-20 md:py-28">
                 <div class="container-app text-center">
                    <h2 class="section-title">Tentang <span class="text-primary">Persija Academy</span></h2>
                    <p class="section-subtitle">
                        Persija Academy (Boarding School) merupakan pusat pelatihan sepakbola terpadu yang menyajikan perpaduan antara pendidikan sepakbola, pendidikan formal dan pengembangan kepribadian. Di Persija Academy, anak-anak usia SMP-SMA dapat merasakan pelatihan sepakbola standar internasional dengan lini kerja dan metodologi yang sama dengan Tim Utama Persija.
                    </p>
                    <p class="section-subtitle">
                        Kolaborasi pendidikan sepakbola top elite profesional dan pendidikan formal berstandar tinggi menjamin lulusan Persija Academy akan menjadi generasi muda yang mampu mewujudkan impian kehidupan dan sepakbolanya.
                    </p>
                </div>
            </section>

            <section id="program-kegiatan" class="py-20 md:py-28 bg-gray-900 text-white">
                <div class="container-app">
                     <div class="text-center">
                        <h2 class="section-title text-white">Program & Kegiatan</h2>
                        <p class="section-subtitle text-gray-400">Kurikulum lengkap untuk performa puncak di dalam dan luar lapangan.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8 text-center">
                        <div class="activity-item"><UsersIcon class="h-8 w-8 mx-auto text-primary"/> Latihan Tim</div>
                        <div class="activity-item"><UserGroupIcon class="h-8 w-8 mx-auto text-primary"/> Spesialis Individu</div>
                        <div class="activity-item"><VideoCameraIcon class="h-8 w-8 mx-auto text-primary"/> Analisis Video</div>
                        <div class="activity-item"><AcademicCapIcon class="h-8 w-8 mx-auto text-primary"/> Sesi Gym</div>
                        <div class="activity-item"><TrophyIcon class="h-8 w-8 mx-auto text-primary"/> Liga & Turnamen</div>
                        <div class="activity-item"><HeartIcon class="h-8 w-8 mx-auto text-primary"/> Pengembangan Kepribadian</div>
                    </div>
                </div>
            </section>
            
            <section id="fasilitas-kami" class="py-20 md:py-28">
                <div class="container-app">
                    <div class="text-center">
                        <h2 class="section-title">FASILITAS KAMI</h2>
                        <p class="section-subtitle">Didukung fasilitas modern untuk menunjang performa terbaik.</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="facility in facilities" :key="facility.name" class="branch-card group">
                            <img :src="facility.image" :alt="facility.name" class="branch-card-img">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                            <div class="absolute bottom-0 left-0 p-6 text-white">
                                <h3 class="font-bold text-xl">{{ facility.name }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="info-kontak" class="py-20 md:py-28 bg-white">
                <div class="container-app grid md:grid-cols-2 gap-16 items-center">
                    <div class="relative">
                         <img src="https://persijadevelopment.id/assets/field.jpg" class="rounded-xl w-full h-[500px] object-cover" alt="Lokasi Academy">
                    </div>
                     <div>
                         <h2 class="section-title text-left">Info & Kontak</h2>
                         <div class="mt-8 space-y-6">
                             <div class="flex items-start gap-4">
                                 <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-50 text-primary flex items-center justify-center">
                                     <MapPinIcon class="h-6 w-6"/>
                                 </div>
                                 <div>
                                     <h4 class="font-semibold text-lg text-gray-900">Lokasi</h4>
                                     <p class="text-gray-600 mt-1">Persija Development Center, Bojongsari Baru, Kec. Bojongsari, Kota Depok, Jawa Barat 16516</p>
                                     <a href="https://www.google.com/maps/place/Persija+Development+Center/@-6.3819863,106.7376897,17z/data=!3m1!4b1!4m5!3m4!1s0x2e69ef09a0bac013:0xf5ada56c7f922ad2!8m2!3d-6.3819867!4d106.7398407" target="_blank" class="text-primary font-semibold text-sm hover:underline">Lihat Peta →</a>
                                 </div>
                             </div>
                              <div class="flex items-start gap-4">
                                 <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-50 text-primary flex items-center justify-center">
                                     <PhoneIcon class="h-6 w-6"/>
                                 </div>
                                 <div>
                                     <h4 class="font-semibold text-lg text-gray-900">Narahubung (WA Only)</h4>
                                     <p class="text-gray-600 mt-1">Elpa Hadi (Dormitory Manager): +62 811-2626-322</p>
                                      <a href="https://wa.me/628112626322" target="_blank" class="text-primary font-semibold text-sm hover:underline">Hubungi via WhatsApp →</a>
                                 </div>
                             </div>
                         </div>
                    </div>
                </div>
            </section>

            <section id="syarat-pendaftaran" class="py-20 md:py-28 bg-gray-50">
                <div class="container-app">
                    <div class="text-center mb-16">
                        <h2 class="section-title">Syarat & Biaya Pendaftaran</h2>
                    </div>
                    <div class="grid md:grid-cols-2 gap-16 items-start">
                        <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                             <h3 class="font-header text-3xl font-bold text-zinc-900 uppercase">Biaya Pendaftaran</h3>
                             <p class="text-4xl font-bold text-primary mt-4">Rp 20 Juta</p>
                             <p class="text-gray-500 mt-1">(Diskon spesial dari harga normal)</p>
                             <p class="mt-6 font-semibold text-gray-800">Biaya sudah termasuk:</p>
                             <ul class="mt-2 space-y-2 text-gray-600 list-inside">
                                <li class="flex items-start gap-2"><ShieldCheckIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"/> <span>SPP bulan pertama (Rp 7.500.000,-)</span></li>
                                <li class="flex items-start gap-2"><BuildingLibraryIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"/> <span>Akomodasi di Asrama</span></li>
                                <li class="flex items-start gap-2"><HeartIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"/> <span>Makan 3x Sehari (Gizi Atlet)</span></li>
                                <li class="flex items-start gap-2"><UsersIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"/> <span>Biaya Pendidikan Sekolah Formal</span></li>
                                <li class="flex items-start gap-2"><AcademicCapIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"/> <span>Buku & Seragam Sekolah</span></li>
                                <li class="flex items-start gap-2"><TrophyIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5"/> <span>Full Set "Juara" Jersey & Equipment</span></li>
                             </ul>
                             <p class="mt-4 text-xs text-gray-500">* Syarat dan ketentuan berlaku.</p>
                        </div>
                        <div>
                             <h3 class="font-header text-3xl font-bold text-zinc-900 uppercase mb-8">Cara Pendaftaran</h3>
                             <ul class="timeline">
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
                                 <li>
                                     <h4 class="timeline-title">Pendaftaran Selesai</h4>
                                     <p class="timeline-desc">Anda telah resmi menjadi Siswa Persija Academy (Boarding School).</p>
                                 </li>
                             </ul>
                             <div class="mt-8 flex flex-col sm:flex-row gap-4">
                                <a href="https://persijadevelopment.id/file/Booklet-Persija-AC-2023.pdf" target="_blank" class="btn btn-secondary">Download Booklet</a>
                                <Link :href="route('register-academy.create')" class="btn btn-primary">Daftar Sekarang</Link>
                             </div>
                        </div>
                    </div>
                </div>
            </section>
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
