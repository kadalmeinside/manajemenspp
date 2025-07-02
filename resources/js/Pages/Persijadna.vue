<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    BookOpenIcon,
    PuzzlePieceIcon,
    BoltIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';

// --- STICKY MENU LOGIC ---
const menuItems = ref([
    { id: 'filsafah-dna', name: 'FILSAFAT', icon: PuzzlePieceIcon },
    { id: 'smart-training', name: 'SMART TRAINING', icon: BoltIcon },
    { id: 'e-book', name: 'E-BOOK', icon: BookOpenIcon }
]);

const activeSection = ref('');

onMounted(() => {
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
        sectionObserver.disconnect();
    });
});
</script>

<template>
    <Head title="Persija DNA" />
    <PublicLayout>

        <template #page-header>
            <section class="relative bg-gray-900 text-white text-center" style="background-image: url('https://images.unsplash.com/photo-1552318965-6e6be7484ada?q=80&w=2940&auto=format&fit=crop'); background-size: cover; background-position: center;">
                 <div class="absolute inset-0 bg-black/60"></div>
                 <div class="relative container-app z-10 py-32 md:py-48">
                    <!-- Konten di dalam banner bisa ditambahkan di sini jika perlu -->
                 </div>
            </section>

             <nav class="sticky top-20 bg-white/95 backdrop-blur-lg border-b border-gray-200 z-40 shadow-sm">
                 <div class="container-app">
                    <div class="flex items-center justify-center overflow-x-auto">
                        <a v-for="item in menuItems" :key="item.id" :href="'#' + item.id"
                           class="roadmap-link"
                           :class="{'roadmap-link-active': activeSection === item.id}">
                            {{ item.name }}
                        </a>
                    </div>
                </div>
            </nav>
        </template>
        
        <div class="bg-white text-gray-700">
            <section class="py-20 md:py-28 bg-gray-800 text-white">
                <div class="container-app text-center">
                    <p class="text-2xl md:text-3xl font-light italic leading-relaxed max-w-4xl mx-auto">
                       “ Sekumpulan ide tentang visi pemain masa depan, falsafah bermain dan metode latihan yang diyakini Persija Jakarta akan mencetak pemain level dunia di masa depan ”
                    </p>
                    <p class="mt-4 font-semibold text-primary">— Persija DNA</p>
                </div>
            </section>

            <section id="filsafah-dna" class="py-20 md:py-28">
                <div class="container-app">
                    <div class="text-center">
                        <h2 class="section-title">Filsafah Persija <span class="text-primary">DNA</span></h2>
                        <p class="section-subtitle mt-8 text-3xl md:text-4xl font-header">Apa itu <span class="text-primary">EN-SI-FLEK</span> ?</p>
                    </div>
                    <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                        <div class="text-center">
                            <h3 class="font-header text-3xl font-bold"><span class="text-primary">EN</span>ERJIK</h3>
                            <p class="mt-2 text-gray-600">
                                Persija Jakarta menginginkan permainan enerjik dengan intensitas tinggi. Dimana dalam permainan enerjik terdapat unsur tidak takut salah. Berani ambil resiko dengan suatu perhitungan matang.
                            </p>
                        </div>
                         <div class="text-center">
                            <h3 class="font-header text-3xl font-bold">INI<span class="text-primary">SI</span>ATIF</h3>
                            <p class="mt-2 text-gray-600">
                                Persija Jakarta tak ingin menunggu dan bereaksi. Persija selalu ambil inisiatif saat menyerang-bertahan-transisi. Inisiatif ini berarti suatu upaya selalu mendominasi permainan sepanjang laga.
                            </p>
                        </div>
                         <div class="text-center">
                            <h3 class="font-header text-3xl font-bold"><span class="text-primary">FLEK</span>SIBEL</h3>
                            <p class="mt-2 text-gray-600">
                                Sepakbola yang dinamis kompleks menuntut fleksibilitas. Kontrol terhadap ruang jadi kunci untuk memenangkan permainan. Untuk itu, Persija Jakarta harus selalu fleksibel menyesuaikan ketersediaan ruang yang selalu berubah.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="smart-training" class="py-20 md:py-28 bg-slate-50">
                 <div class="container-app">
                    <div class="text-center mb-16">
                        <h2 class="section-title">Smart <span class="text-primary">Training</span></h2>
                    </div>
                    <div class="grid md:grid-cols-2 gap-16 items-center">
                        <div class="relative hidden md:block">
                            <img src="https://persijadevelopment.id/assets/tomas_doll.png" alt="Thomas Doll" class="w-full h-auto">
                        </div>
                        <div class="space-y-8">
                            <div class="flex items-start gap-6">
                                <h3 class="font-header text-5xl font-bold text-primary">S</h3>
                                <div>
                                    <h4 class="font-bold text-xl text-gray-900"><span class="text-primary">Situation</span> Football</h4>
                                    <p class="mt-1 text-gray-600">Latihan harus mensimulasikan situasi sepakbola. Di dalamnya terdapat menyerang-bertahan-transisi. terdapat kawan-lawan yang merangsang komunikasi-keputusan-eksekusi dalam situasi sepakbola riil.</p>
                                </div>
                            </div>
                             <div class="flex items-start gap-6">
                                <h3 class="font-header text-5xl font-bold text-primary">M</h3>
                                <div>
                                    <h4 class="font-bold text-xl text-gray-900"><span class="text-primary">Maximum</span> Intensity</h4>
                                    <p class="mt-1 text-gray-600">Setiap latihan harus berlangsung dalam intensitas maksimal. Dari mulai latihan passing sederhana hingga game, semua harus dilakukan dengan usaha maksimal seperti dalam pertandingan.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-6">
                                <h3 class="font-header text-5xl font-bold text-primary">A</h3>
                                <div>
                                    <h4 class="font-bold text-xl text-gray-900"><span class="text-primary">Active</span> Involvement</h4>
                                    <p class="mt-1 text-gray-600">Semua bentuk latihan harus libatkan seluruh pemain, termasuk kiper. Menunggu dalam antrian merupakan sesuatu yang sebisa mungkin dihindari!</p>
                                </div>
                            </div>
                             <div class="flex items-start gap-6">
                                <h3 class="font-header text-5xl font-bold text-primary">R</h3>
                                <div>
                                    <h4 class="font-bold text-xl text-gray-900"><span class="text-primary">Rest</span> Coaching</h4>
                                    <p class="mt-1 text-gray-600">Sebisa mungkin pelatih tidak hentikan latihan untuk memberi masukan pada pemain. Intensitas latihan harus terjaga dan pemain harus mencari solusi mandiri. Pemberian masukan disarankan dilakukan saat interval rest latihan.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-6">
                                <h3 class="font-header text-5xl font-bold text-primary">T</h3>
                                <div>
                                    <h4 class="font-bold text-xl text-gray-900"><span class="text-primary">Task</span> Oriented</h4>
                                    <p class="mt-1 text-gray-600">Setiap bentuk latihan selalu sajikan permasalahan yang perlu dicari solusinya oleh pemain. Permasalahan ini adalah indikator kesuksesan latihan. Semakin pemain mampu menyelesaikan permasalahan, semakin sukses latihan!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="e-book" class="py-20 md:py-28 bg-gray-800 text-white">
                 <div class="container-app text-center">
                    <h2 class="section-title text-white">Tunggu <span class="text-primary">Apa Lagi?</span></h2>
                    <div class="mt-8 flex justify-center">
                        <img src="https://persijadevelopment.id/assets/book-mockup.png" class="w-full max-w-sm" alt="E-Book Persija DNA">
                    </div>
                    <p class="section-subtitle text-gray-300 mt-8">Dapatkan Sekarang juga E-Book Persija DNA secara gratis dengan dengan menekan tombol dibawah.</p>
                    <div class="mt-8">
                        <a href="https://drive.google.com/drive/u/1/folders/1wElmAnlBCiwOnUtYucLErxPsud3bgLbS" target="_blank" class="btn btn-primary">
                           <ArrowDownTrayIcon class="inline-block h-5 w-5 mr-2"/> Download E-Book
                        </a>
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
    @apply inline-flex items-center justify-center font-semibold text-center rounded-lg px-8 py-3 transition-all duration-300;
}
.btn-primary {
    background-color: var(--primary-color);
    @apply text-white shadow-md shadow-red-500/20 hover:bg-red-700 hover:shadow-lg hover:shadow-red-500/30 hover:-translate-y-0.5;
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
</style>
