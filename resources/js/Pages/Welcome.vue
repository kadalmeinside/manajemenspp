<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    ChevronLeftIcon, 
    ChevronRightIcon, 
    ShieldCheckIcon, 
    AcademicCapIcon, 
    UserGroupIcon,
    SparklesIcon,
    BuildingLibraryIcon,
    UsersIcon,
    TrophyIcon
} from '@heroicons/vue/24/outline';

// Data untuk 4 program utama
const programs = ref([
    {
        title: 'Elite Pro Academy (EPA)',
        description: 'Jalur profesional untuk atlet muda berbakat yang diseleksi secara ketat.',
        icon: SparklesIcon,
        href: '#', // Ganti dengan route ke halaman detail
        color: 'text-red-600',
        bgColor: 'bg-red-50'
    },
    {
        title: 'Academy Boarding School',
        description: 'Program terpadu yang menggabungkan sepak bola, akademis, dan asrama.',
        icon: BuildingLibraryIcon,
        href: '#',
        color: 'text-sky-600',
        bgColor: 'bg-sky-50'
    },
    {
        title: 'Soccer School',
        description: 'Pembinaan fundamental yang menyenangkan untuk semua level usia.',
        icon: UsersIcon,
        href: '#',
        color: 'text-amber-600',
        bgColor: 'bg-amber-50'
    },
    {
        title: 'Young Tiger League',
        description: 'Kompetisi internal untuk mengasah mental bertanding para siswa.',
        icon: TrophyIcon,
        href: '#',
        color: 'text-indigo-600',
        bgColor: 'bg-indigo-50'
    }
]);

// Data contoh untuk cabang Soccer School
const soccerSchoolBranches = ref([
    { id: 1, nama_kelas: 'Soccer School Ciledug', deskripsi: 'Program fundamental untuk usia dini.', gambar: 'https://persijadevelopment.id/assets/web_ss_ciledug.jpg', isNew: true },
    { id: 2, nama_kelas: 'Soccer School Pulomas', deskripsi: 'Pengembangan skill tingkat lanjut.', gambar: 'https://persijadevelopment.id/assets/web_ss_pulomas1.jpg', isNew: false },
    { id: 3, nama_kelas: 'Soccer School Sawangan', deskripsi: 'Fokus pada taktik dan kerjasama tim.', gambar: 'https://persijadevelopment.id/assets/web_ss_sawangan.jpg', isNew: false },
    { id: 4, nama_kelas: 'Soccer School Bekasi', deskripsi: 'Persiapan menuju jenjang kompetitif.', gambar: 'https://persijadevelopment.id/assets/ss_bekasi_modal.jpg', isNew: false },
]);


// --- HERO SLIDER ---
const heroSlides = ref([
    { title: 'SOCCER SCHOOL', subtitle: 'Cabang Ciledug', image: 'https://persijadevelopment.id/assets/web_ss_ciledug.jpg' },
    { title: 'SKILL DEVELOPMENT', subtitle: 'Latihan Intensif', image: 'https://images.unsplash.com/photo-1628029437115-a48bbb6e6ef8?q=80&w=2071&auto=format&fit=crop' },
]);
const currentHeroSlide = ref(0);
let heroSlideInterval = null;
const nextHeroSlide = () => { currentHeroSlide.value = (currentHeroSlide.value + 1) % heroSlides.value.length; };
const prevHeroSlide = () => { currentHeroSlide.value = (currentHeroSlide.value - 1 + heroSlides.value.length) % heroSlides.value.length; };

// --- SCHOOL BRANCH SLIDER ---
const schoolSliderContainer = ref(null);
const scrollSchoolSlider = (direction) => {
    if (schoolSliderContainer.value) {
        const scrollAmount = schoolSliderContainer.value.clientWidth;
        schoolSliderContainer.value.scrollBy({ left: direction === 'next' ? scrollAmount : -scrollAmount, behavior: 'smooth' });
    }
};

// --- DATA STATIS ---
const videoGalleries = ref([
    { title: 'BENCH CAM: PERSIJA VS MALUT UNITED FC', thumbnail: 'https://img.youtube.com/vi/IujYDOHSBMI/hqdefault.jpg', link: 'https://youtube.com/watch?v=IujYDOHSBMI' },
    { title: 'EXTRA TIME: PERSIJA VS MALUT UNITED FC', thumbnail: 'https://img.youtube.com/vi/IujYDOHSBMI/hqdefault.jpg', link: 'https://youtube.com/watch?v=IujYDOHSBMI' },
    { title: 'PESAN TERAKHIR MARKO SIMIC UNTUK PERSIJA', thumbnail: 'https://img.youtube.com/vi/IujYDOHSBMI/hqdefault.jpg', link: 'https://youtube.com/watch?v=IujYDOHSBMI' },
]);

const news = ref([]);
const newsLoading = ref(true);
async function fetchNews() {
    newsLoading.value = true;
    try {
        const response = await fetch('https://event.persijadevelopment.id/wp-json/wp/v2/posts?_embed&per_page=3&categories=1');
        if (!response.ok) throw new Error('Network response was not ok');
        const posts = await response.json();
        news.value = posts.map(post => ({
            link: post.link,
            image: post._embedded?.['wp:featuredmedia']?.[0]?.source_url || 'https://placehold.co/600x400/1f2937/ffffff?text=Berita',
            date: new Date(post.date_gmt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
            title: post.title.rendered,
        }));
    } catch (error) { console.error('Failed to fetch news:', error); }
    finally { newsLoading.value = false; }
}

onMounted(() => {
    heroSlideInterval = setInterval(nextHeroSlide, 7000);
    fetchNews();
});
onUnmounted(() => { clearInterval(heroSlideInterval); });
</script>

<template>
    <Head title="Selamat Datang di Persija Development" />
    <PublicLayout>
        <main class="bg-white text-zinc-800">
            <!-- Hero Section -->
            <section class="relative h-screen w-full flex items-center justify-center text-center overflow-hidden">
                <div class="absolute inset-0 h-full w-full transition-opacity duration-1000" v-for="(slide, index) in heroSlides" :key="index" :class="index === currentHeroSlide ? 'opacity-100' : 'opacity-0'">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent z-10"></div>
                    <img :src="slide.image" :alt="slide.title" class="h-full w-full object-cover">
                </div>
                <div class="relative z-20 px-4 flex flex-col items-center">
                    <h1 class="font-header text-5xl sm:text-6xl md:text-8xl font-bold text-white uppercase tracking-tight leading-none">
                        {{ heroSlides[currentHeroSlide].title }}
                    </h1>
                    <h2 class="font-header text-4xl sm:text-5xl md:text-7xl font-semibold text-red-500 uppercase -mt-2 md:-mt-4">
                        {{ heroSlides[currentHeroSlide].subtitle }}
                    </h2>
                    <p class="mt-4 max-w-xl text-base md:text-lg text-white/80">
                        Bergabunglah dengan program pembinaan elite Persija. Latih potensimu dengan metodologi standar tim utama untuk menjadi generasi juara.
                    </p>
                    <div class="mt-8">
                        <Link :href="route('pendaftaran.create')" class="inline-block bg-red-600 text-white font-bold py-3 px-8 rounded-lg text-base md:text-lg hover:bg-red-500 transition-all duration-300 transform hover:scale-105 shadow-lg shadow-red-600/30">
                            DAFTAR SEKARANG
                        </Link>
                    </div>
                </div>
                <button @click="prevHeroSlide" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-3 bg-white/10 hover:bg-white/20 rounded-full backdrop-blur-sm transition-colors text-white"><ChevronLeftIcon class="h-6 w-6"/></button>
                <button @click="nextHeroSlide" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-3 bg-white/10 hover:bg-white/20 rounded-full backdrop-blur-sm transition-colors text-white"><ChevronRightIcon class="h-6 w-6"/></button>
            </section>

            <!-- Pilihan Program -->
             <section id="program" class="py-16 md:py-24">
                <div class="container-app">
                    <div class="text-center">
                        <h2 class="section-title">PILIH PROGRAM ANDA</h2>
                        <p class="section-subtitle">Kami menyediakan empat jalur pembinaan profesional untuk setiap jenjang usia.</p>
                    </div>
                    <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div v-for="program in programs" :key="program.title" class="program-summary-card">
                            <div class="flex-shrink-0 w-14 h-14 rounded-lg flex items-center justify-center" :class="program.bgColor">
                                <component :is="program.icon" class="w-7 h-7" :class="program.color" />
                            </div>
                            <h3 class="mt-4 font-bold text-lg text-gray-900">{{ program.title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ program.description }}</p>
                            <Link :href="program.href" class="program-summary-link">
                                Lihat Detail <ChevronRightIcon class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Detail Soccer School -->
            <section id="soccer-school-details" class="py-16 md:py-24 bg-gray-50">
                <div class="container-app">
                    <div class="md:flex md:items-center md:justify-between border-b-2 border-gray-700 pb-2">
                        <div class="text-center md:text-left">
                            <h2 class="section-title">PERSIJA SOCCER SCHOOL</h2>
                            <p class="section-subtitle">Gerbang awal Anda menuju tim profesional. Temukan cabang terdekat.</p>
                        </div>
                        <div class="hidden md:flex items-center space-x-3 mt-4 md:mt-0">
                            <button @click="scrollSchoolSlider('prev')" class="slider-nav-button"><ChevronLeftIcon class="h-6 w-6"/></button>
                            <button @click="scrollSchoolSlider('next')" class="slider-nav-button"><ChevronRightIcon class="h-6 w-6"/></button>
                        </div>
                    </div>
                    <div class="mt-12">
                        <div ref="schoolSliderContainer" class="card-container-slider">
                            <div v-for="cabang in soccerSchoolBranches" :key="cabang.id" class="card">
                                <img :src="cabang.gambar" :alt="cabang.nama_kelas" class="card-background-image" onerror="this.onerror=null;this.src='https://placehold.co/400x600/1a1a1a/ffffff?text=Image+Not+Found';">
                                <div class="card-content">
                                    <div>
                                        <h3 class="card-title">{{ cabang.nama_kelas }}</h3>
                                        <p class="card-subtitle">{{ cabang.deskripsi }}</p>
                                        <Link :href="route('pendaftaran.create')" class="card-button mt-4">Daftar</Link>
                                    </div>
                                </div>
                                <div v-if="cabang.isNew" class="absolute top-4 right-4 bg-red-600 text-white text-xs font-bold px-3 py-1.5 rounded-full z-30">BARU</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Detail Academy -->
            <section id="academy-details" class="py-16 md:py-24">
                <div class="container-app">
                    <div class="text-center">
                        <h2 class="section-title">PERSIJA ACADEMY</h2>
                        <p class="section-subtitle">Kurikulum terpadu untuk performa puncak di dalam dan luar lapangan.</p>
                    </div>
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                        <div class="feature-card">
                            <div class="feature-card-icon"><ShieldCheckIcon class="h-8 w-8"/></div>
                            <h4 class="feature-card-title">Pelatihan Profesional</h4>
                            <p class="feature-card-desc">Latihan tim, spesialis individu, dan analisis video dengan standar tim utama Persija.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-card-icon"><AcademicCapIcon class="h-8 w-8"/></div>
                            <h4 class="feature-card-title">Pendidikan Formal</h4>
                            <p class="feature-card-desc">Kolaborasi dengan sekolah berstandar tinggi untuk menjamin masa depan akademis para siswa.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-card-icon"><UserGroupIcon class="h-8 w-8"/></div>
                            <h4 class="feature-card-title">Pengembangan Karakter</h4>
                            <p class="feature-card-desc">Kelas psikologi, gizi, dan public speaking untuk membentuk pribadi yang utuh dan percaya diri.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Berita Terkini -->
            <section id="berita" class="py-16 md:py-24 bg-gray-50">
                <div class="container-app">
                    <div class="flex items-end justify-between border-b-2 border-gray-700 pb-2">
                        <div>
                            <h3 class="font-teko text-xl font-bold text-gray-400 uppercase">News</h3>
                            <h2 class="section-title text-4xl sm:text-5xl font-bold uppercase -mt-2">Berita & Kegiatan</h2>
                        </div>
                        <a href="#" class="text-sm font-semibold hover:text-red-500 transition">Lihat Semua &rarr;</a>
                    </div>
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-if="newsLoading" class="text-center col-span-full py-12 text-zinc-500">Memuat berita...</div>
                        <a v-for="item in news" :key="item.link" :href="item.link" target="_blank" rel="noopener" class="news-card group">
                            <div class="overflow-hidden rounded-lg">
                                <img :src="item.image" :alt="item.title" class="news-card-img">
                            </div>
                            <div class="p-5">
                                <p class="text-xs text-gray-500 mb-2">{{ item.date }}</p>
                                <h4 class="news-card-title" v-html="item.title"></h4>
                            </div>
                        </a>
                        <div v-if="!newsLoading && news.length === 0" class="text-center col-span-full py-12 text-zinc-500">Gagal memuat berita.</div>
                    </div>
                </div>
            </section>

             <!-- Galeri Video -->
            <section id="gallery" class="py-16 md:py-24 bg-zinc-950 text-white">
                <div class="container-app">
                    <div class="flex items-end justify-between border-b-2 border-gray-700 pb-2">
                        <div>
                            <h3 class="font-teko text-xl font-bold text-gray-400 uppercase">Video</h3>
                            <h2 class="section-title text-white text-4xl sm:text-5xl font-bold uppercase -mt-2">Video Highlights</h2>
                        </div>
                        <a href="#" class="text-sm font-semibold hover:text-red-500 transition">Lihat Semua &rarr;</a>
                    </div>
                    <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        <a v-for="video in videoGalleries" :key="video.link" :href="video.link" target="_blank" rel="noopener" class="video-card group">
                            <img :src="video.thumbnail" :alt="video.title" class="video-card-img">
                            <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors duration-300"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="video-card-play-button">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8.118v3.764a1 1 0 001.555.832l3.197-1.882a1 1 0 000-1.664l-3.197-1.882z" clip-rule="evenodd" /></svg>
                                </div>
                            </div>
                            <p class="absolute bottom-0 left-0 p-4 text-white font-semibold text-sm drop-shadow-lg w-full bg-gradient-to-t from-black/70 to-transparent pt-10">{{ video.title }}</p>
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Oswald:wght@500;600;700&display=swap');

body { 
    font-family: 'Inter', sans-serif; 
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.font-header { 
    font-family: 'Oswald', sans-serif; 
}

/* Global Container */
.container-app {
    @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
}

/* Typography Styles */
.section-title {
    @apply font-header text-4xl font-bold text-zinc-900 md:text-5xl uppercase;
}
.section-title.text-white {
    color: white; 
}

.section-subtitle {
    @apply mt-2 max-w-2xl mx-auto text-base text-zinc-600 md:text-lg;
}

/* Program Summary Card */
.program-summary-card {
    @apply bg-white p-6 rounded-xl border border-gray-100 transition-all duration-300 hover:shadow-xl hover:border-gray-200 hover:-translate-y-1;
}
.program-summary-link {
    @apply inline-flex items-center gap-2 mt-4 text-sm font-semibold text-red-600 hover:underline;
}

/* Slider Navigation */
.slider-nav-button {
    @apply p-2 bg-white rounded-full shadow hover:bg-gray-100 text-zinc-600 hover:text-zinc-900 transition-all duration-200;
}

/* Horizontal Card Slider (Soccer School) */
.card-container-slider {
    @apply flex space-x-6 overflow-x-auto pb-4 scroll-smooth snap-x snap-mandatory;
    -ms-overflow-style: none; scrollbar-width: none;
}
.card-container-slider::-webkit-scrollbar { display: none; }

.card {
    @apply relative w-80 h-[450px] rounded-2xl overflow-hidden shadow-xl transition-transform duration-300 flex-shrink-0 snap-center;
}
.card:hover {
    @apply transform -translate-y-2;
}
.card-background-image {
    @apply absolute inset-0 w-full h-full object-cover z-0 transition-all duration-500 ease-in-out;
}
.card:hover .card-background-image {
    @apply filter blur-sm;
}
.card-content {
    @apply relative z-10 flex flex-col justify-end h-full p-6 bg-gradient-to-t from-black/90 via-black/50 to-transparent transition-all duration-500;
}
.card:hover .card-content {
     @apply from-black/90 via-black/30;
}
.card-title {
    @apply font-header font-bold text-2xl leading-tight text-white;
    text-shadow: 1px 1px 5px rgb(0 0 0 / 0.5);
}
.card-subtitle {
    @apply text-sm text-white/80 mt-1;
    text-shadow: 1px 1px 5px rgb(0 0 0 / 0.5);
}
.card-button {
    @apply block w-full text-center py-3 px-6 rounded-lg font-semibold bg-white/10 border border-white/20 backdrop-blur-sm text-white hover:bg-white/20 transition-colors duration-300;
}

/* Feature Card (Academy) */
.feature-card {
    @apply bg-white p-8 rounded-xl border border-gray-100 transition-all duration-300 hover:border-red-500/50 hover:shadow-xl hover:-translate-y-1;
}
.feature-card-icon {
    @apply w-16 h-16 rounded-full bg-red-600/10 text-red-500 flex items-center justify-center mx-auto;
}
.feature-card-title {
    @apply mt-5 font-bold text-lg text-zinc-900;
}
.feature-card-desc {
    @apply mt-2 text-sm text-zinc-600;
}

/* News Card */
.news-card {
    @apply block bg-white rounded-lg overflow-hidden border border-gray-100 hover:border-gray-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300;
}
.news-card-img {
    @apply h-56 w-full object-cover group-hover:scale-105 transition-transform duration-500;
}
.news-card-title {
    @apply font-bold text-lg text-zinc-900 group-hover:text-red-600 transition-colors duration-300 leading-tight;
}

/* Video Card (Dark Theme) */
.video-card {
    @apply block relative overflow-hidden rounded-lg shadow-lg;
}
.video-card-img {
    @apply w-full h-full object-cover transition-transform duration-500 group-hover:scale-105;
}
.video-card-play-button {
    @apply w-16 h-16 bg-red-600/80 rounded-full flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:bg-red-600;
}
</style>
