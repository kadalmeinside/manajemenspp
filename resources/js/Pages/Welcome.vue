<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/solid'
import Carousel from '@/Components/Carousel.vue';

const props = defineProps({
    allKelas: Array,
});

// --- HERO SLIDER ---
const heroSlides = ref([
    {
        title: 'SOCCER SCHOOL',
        subtitle: 'Cabang Ciledug',
        image: 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2835&auto=format&fit=crop',
    },
    {
        title: 'SKILL DEVELOPMENT',
        subtitle: 'Latihan Intensif',
        image: 'https://images.unsplash.com/photo-1628029437115-a48bbb6e6ef8?q=80&w=2071&auto=format&fit=crop',
    },
    {
        title: 'TEAMWORK & FUN',
        subtitle: 'Untuk Semua Usia',
        image: 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop',
    }
]);

const currentHeroSlide = ref(0);
let heroSlideInterval = null;
const nextHeroSlide = () => { currentHeroSlide.value = (currentHeroSlide.value + 1) % heroSlides.value.length; };
const prevHeroSlide = () => { currentHeroSlide.value = (currentHeroSlide.value - 1 + heroSlides.value.length) % heroSlides.value.length; };


// --- SCHOOL BRANCH SLIDER ---
const schoolSliderContainer = ref(null);
const scrollSchoolSlider = (direction) => {
    if (schoolSliderContainer.value) {
        const scrollAmount = schoolSliderContainer.value.offsetWidth * 0.8;
        schoolSliderContainer.value.scrollBy({ left: direction === 'next' ? scrollAmount : -scrollAmount, behavior: 'smooth' });
    }
};

// --- DATA GALERI (STATIS) ---
// const photoGalleries = ref([
//     {
//         title: 'Pertandingan Persahabatan U-16',
//         images: [
//             'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2835&auto=format&fit=crop',
//             'https://images.unsplash.com/photo-1628029437115-a48bbb6e6ef8?q=80&w=2071&auto=format&fit=crop',
//             'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop',
//             'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop'
//         ]
//     },
// ]);

const photoGalleries = ref([
    { title: 'Match Gallery', images: [ 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2835&auto=format&fit=crop', 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop', 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop' ] },
    { title: 'Program Gallery', images: [ 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2835&auto=format&fit=crop', 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop', 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop' ] },
    { title: 'Persija CSR', images: [ 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2835&auto=format&fit=crop', 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop', 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop' ] },
]);

const videoGalleries = ref([
    { title: 'BENCH CAM: PERSIJA VS MALUT UNITED FC', thumbnail: 'https://img.youtube.com/vi/IujYDOHSBMI/hqdefault.jpg', link: 'https://youtube.com/watch?v=IujYDOHSBMI' },
    { title: 'EXTRA TIME: PERSIJA VS MALUT UNITED FC', thumbnail: 'https://img.youtube.com/vi/IujYDOHSBMI/hqdefault.jpg', link: 'https://youtube.com/watch?v=IujYDOHSBMI' },
    { title: 'PESAN TERAKHIR MARKO SIMIC UNTUK PERSIJA', thumbnail: 'https://img.youtube.com/vi/IujYDOHSBMI/hqdefault.jpg', link: 'https://youtube.com/watch?v=IujYDOHSBMI' },
]);
// --- NEWS FETCH ---
const news = ref([]);
const newsLoading = ref(true);
async function fetchNews() {
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
    <Head title="Selamat Datang" />
    <PublicLayout>
        <main>
            <!-- Hero Section dengan Video -->
            <section class="relative h-screen w-full flex items-center justify-center text-center overflow-hidden">
                 <div class="absolute inset-0 h-full w-full transition-all duration-1000 ease-in-out" v-for="(slide, index) in heroSlides" :key="index" :class="index === currentHeroSlide ? 'opacity-100' : 'opacity-0'">
                    <div class="absolute inset-0 bg-black/60 z-10"></div>
                    <img :src="slide.image" :alt="slide.title" class="h-full w-full object-cover">
                </div>
                <div class="relative z-10 px-4">
                    <h1 class="font-teko text-5xl sm:text-7xl md:text-8xl font-bold text-white uppercase tracking-wide leading-tight">
                        {{ heroSlides[currentHeroSlide].title }}
                    </h1>
                    <h2 class="font-teko text-4xl sm:text-6xl md:text-7xl font-semibold text-red-500 uppercase -mt-2 md:-mt-4">
                        {{ heroSlides[currentHeroSlide].subtitle }}
                    </h2>
                    <p class="mt-4 max-w-2xl mx-auto text-lg text-white/90">
                        Bergabunglah dengan program pembinaan elite Persija. Latih potensimu dengan metodologi standar tim utama untuk menjadi generasi juara.
                    </p>
                    <div class="mt-8">
                        <Link :href="route('pendaftaran.create')" class="bg-red-600 text-white font-bold py-4 px-10 rounded-md text-lg hover:bg-red-500 transition-transform hover:scale-105 transform inline-block">
                            DAFTAR SEKARANG
                        </Link>
                    </div>
                </div>
                <!-- Kontrol Slider Hero -->
                <button @click="prevHeroSlide" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-3 bg-white/20 hover:bg-white/40 rounded-full text-white transition-colors">
                    <ChevronLeftIcon class="h-6 w-6"/>
                </button>
                <button @click="nextHeroSlide" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-3 bg-white/20 hover:bg-white/40 rounded-full text-white transition-colors">
                    <ChevronRightIcon class="h-6 w-6"/>
                </button>
            </section>

            <!-- Pilihan Program -->
            <section id="program" class="py-20 bg-gray-100 dark:bg-zinc-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="font-teko text-5xl font-bold text-gray-900 dark:text-white">PILIH PROGRAM ANDA</h2>
                        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Kami menyediakan dua jalur pembinaan profesional untuk setiap jenjang usia.</p>
                    </div>
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden group">
                            <div class="h-56 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1598448375978-88b8f2d50228?q=80&w=2938&auto=format&fit=crop')"></div>
                            <div class="p-6">
                                <h3 class="font-teko text-3xl font-bold text-gray-900 dark:text-white">SOCCER SCHOOL</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Program pembinaan penunjang untuk usia U10-U16 sebagai pintu masuk menuju jenjang elite sepak bola Indonesia.</p>
                                <a href="#soccer-school-details" class="mt-6 inline-block font-semibold text-red-600 dark:text-red-500 group-hover:text-red-700 dark:group-hover:text-red-400">Lihat Detail &rarr;</a>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden group">
                            <div class="h-56 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?q=80&w=2807&auto=format&fit=crop')"></div>
                            <div class="p-6">
                                <h3 class="font-teko text-3xl font-bold text-gray-900 dark:text-white">ACADEMY BOARDING SCHOOL</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Pusat pelatihan terpadu yang memadukan pendidikan sepak bola profesional, pendidikan formal, dan pengembangan kepribadian.</p>
                                 <a href="#academy-details" class="mt-6 inline-block font-semibold text-red-600 dark:text-red-500 group-hover:text-red-700 dark:group-hover:text-red-400">Lihat Detail &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Detail Soccer School -->
            <section id="soccer-school-details" class="py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="font-teko text-5xl font-bold text-gray-900 dark:text-white">PERSIJA SOCCER SCHOOL</h2>
                        <p class="mt-2 max-w-3xl mx-auto text-lg text-gray-600 dark:text-gray-400">Gerbang awal Anda menuju tim profesional. Menerapkan metodologi latihan yang sama dengan Persija Elite Pro Academy.</p>
                    </div>
                    <div class="mt-12">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Temukan Cabang Terdekat</h3>
                            <div class="hidden sm:flex items-center space-x-3">
                                <button @click="scrollSchoolSlider('prev')" class="p-2 bg-gray-200 dark:bg-zinc-800 rounded-full hover:bg-gray-300 dark:hover:bg-zinc-700 transition"><ChevronLeftIcon class="h-6 w-6 text-gray-700 dark:text-gray-200"/></button>
                                <button @click="scrollSchoolSlider('next')" class="p-2 bg-gray-200 dark:bg-zinc-800 rounded-full hover:bg-gray-300 dark:hover:bg-zinc-700 transition"><ChevronRightIcon class="h-6 w-6 text-gray-700 dark:text-gray-200"/></button>
                            </div>
                        </div>
                        <div class="relative">
                            <div ref="schoolSliderContainer" class="flex space-x-6 overflow-x-auto pb-4 slider-container scroll-smooth snap-x snap-mandatory">
                               <div v-for="kelas in allKelas" :key="kelas.id" class="flex-shrink-0 w-72 snap-start">
                                    <div class="group relative overflow-hidden rounded-lg shadow-lg">
                                        <img :src="kelas.gambar" :alt="kelas.nama_kelas" class="h-96 w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                                        <div class="absolute bottom-0 left-0 p-4 text-white">
                                            <h3 class="text-lg font-bold">{{ kelas.nama_kelas }}</h3>
                                            <p class="mt-1 text-sm opacity-90">{{ kelas.deskripsi }}</p>
                                        </div>
                                        <div v-if="kelas.isNew" class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">BARU</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Detail Academy -->
            <section id="academy-details" class="py-20 bg-gray-100 dark:bg-zinc-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="font-teko text-5xl font-bold text-gray-900 dark:text-white">PERSIJA ACADEMY</h2>
                        <p class="mt-2 max-w-3xl mx-auto text-lg text-gray-600 dark:text-gray-400">Program pelatihan sepak bola terpadu dengan pendidikan formal dan pengembangan kepribadian untuk usia SMP-SMA.</p>
                    </div>
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl shadow-lg">
                            <p class="text-5xl">⚽</p>
                            <h4 class="mt-4 font-bold text-lg text-gray-900 dark:text-white">Pelatihan Profesional</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Latihan tim, spesialis individu, dan analisis video dengan standar tim utama Persija.</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl shadow-lg">
                            <p class="text-5xl">🎓</p>
                            <h4 class="mt-4 font-bold text-lg text-gray-900 dark:text-white">Pendidikan Formal</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kolaborasi dengan sekolah berstandar tinggi untuk menjamin masa depan akademis para siswa.</p>
                        </div>
                        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl shadow-lg">
                            <p class="text-5xl">🧠</p>
                            <h4 class="mt-4 font-bold text-lg text-gray-900 dark:text-white">Pengembangan Karakter</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelas psikologi, gizi, bahasa Inggris, dan agama untuk membentuk pribadi yang utuh.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Berita Terkini -->
            <section id="berita" class="py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="font-teko text-5xl font-bold text-gray-900 dark:text-white">BERITA TERKINI</h2>
                        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Ikuti perkembangan terbaru dari semua program Persija Development.</p>
                    </div>
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-if="newsLoading" class="text-center col-span-full py-12 text-gray-500">Memuat berita...</div>
                        <a v-for="item in news" :key="item.link" :href="item.link" target="_blank" rel="noopener" class="block bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden group">
                            <img :src="item.image" :alt="item.title" class="h-56 w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="p-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.date }}</p>
                                <h4 class="mt-2 font-bold text-lg text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-500 transition-colors" v-html="item.title"></h4>
                            </div>
                        </a>
                        <div v-if="!newsLoading && news.length === 0" class="text-center col-span-full py-12 text-gray-500">Gagal memuat berita.</div>
                    </div>
                </div>
            </section>

            <!-- Galeri Foto & Video BARU -->
            <section id="gallery" class="py-16 md:py-20 bg-black text-white">
                 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Navigasi Tab Galeri -->
                    <div class="mb-12">
                        <div class="flex items-end justify-between border-b-2 border-gray-700 pb-2">
                           <div>
                                <h3 class="font-teko text-xl font-bold text-gray-400 uppercase">Foto</h3>
                                <h2 class="font-teko text-4xl sm:text-5xl font-bold uppercase -mt-2">Match Gallery</h2>
                           </div>
                           <a href="#" class="text-sm font-semibold hover:text-red-500 transition">Lihat Semua &rarr;</a>
                        </div>
                    </div>
                    <!-- Konten Galeri Foto -->
                    <Carousel :items="photoGalleries[0].images.map(img => ({ gambar: img }))" :show-text="false" />

                    <!-- Galeri Video -->
                    <div class="mt-20">
                        <div class="flex items-end justify-between border-b-2 border-gray-700 pb-2">
                           <div>
                                <h3 class="font-teko text-xl font-bold text-gray-400 uppercase">Video</h3>
                                <h2 class="font-teko text-4xl sm:text-5xl font-bold uppercase -mt-2">Match Highlights</h2>
                           </div>
                           <a href="#" class="text-sm font-semibold hover:text-red-500 transition">Lihat Semua &rarr;</a>
                        </div>
                        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            <a v-for="video in videoGalleries" :key="video.videoId" :href="video.link" target="_blank" rel="noopener" class="block group relative overflow-hidden rounded-lg shadow-lg">
                                <img :src="video.thumbnail" :alt="video.title" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-16 h-16 bg-red-600/80 rounded-full flex items-center justify-center transition-transform group-hover:scale-110">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8.118v3.764a1 1 0 001.555.832l3.197-1.882a1 1 0 000-1.664l-3.197-1.882z" clip-rule="evenodd" /></svg>
                                    </div>
                                </div>
                                <p class="absolute bottom-0 left-0 p-3 text-white font-semibold text-sm drop-shadow-md">{{ video.title }}</p>
                            </a>
                        </div>
                    </div>
                 </div>
            </section>
        </main>
    </PublicLayout>
</template>

<style>
body { font-family: 'Inter', sans-serif; }
.font-teko { font-family: 'Teko', sans-serif; }
</style>
