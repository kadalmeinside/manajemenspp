<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    ChevronDownIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';

const contactPoints = ref([
    {
        name: 'Customer Support',
        description: 'Tim support kami siap membantu Anda menjawab pertanyaan seputar program dan pendaftaran.',
    },
    {
        name: 'Feedback and Suggestions',
        description: 'Kami sangat menghargai masukan Anda untuk terus meningkatkan kualitas layanan kami.',
    },
    {
        name: 'Media Inquiries',
        description: 'Untuk pertanyaan terkait media dan pers, silakan hubungi kami melalui email media@persijadevelopment.id.',
    },
]);

const faqs = ref([
    {
        question: 'Bagaimana cara mendaftar di Persija Development?',
        answer: 'Pendaftaran dilakukan secara online melalui tombol "Daftar Online" di halaman program masing-masing. Anda akan diminta untuk mengisi formulir dan mengunggah bukti pembayaran.',
        open: false
    },
    {
        question: 'Berapa biaya pendaftaran dan SPP bulanan?',
        answer: 'Biaya pendaftaran dan SPP bervariasi tergantung program yang dipilih. Rincian biaya dapat ditemukan di halaman detail setiap program atau dengan mengunduh booklet resmi kami.',
        open: false
    },
    {
        question: 'Apakah ada seleksi untuk masuk?',
        answer: 'Untuk program Elite Pro Academy (EPA) dan Academy Boarding School, terdapat proses seleksi yang ketat. Sementara itu, Persija Soccer School terbuka untuk umum sesuai dengan kelompok usia yang tersedia.',
        open: false
    },
    {
        question: 'Apa saja fasilitas yang didapatkan siswa?',
        answer: 'Siswa mendapatkan akses ke fasilitas latihan berstandar tinggi, termasuk lapangan, gym, ruang kelas untuk analisis video, serta perlengkapan latihan resmi dari Persija.',
        open: false
    }
]);

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open;
};

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    message: '',
});

const formSubmitted = ref(false);

const submitForm = () => {
    console.log('Form submitted:', form);
    formSubmitted.value = true;
    form.reset();
    
    setTimeout(() => {
        formSubmitted.value = false;
    }, 5000);
};

</script>

<template>
    <Head title="Hubungi Kami" />
    <PublicLayout>
        <div class="pt-20"> <!-- Offset for fixed main navbar -->
            <main class="bg-white text-gray-700">

                <!-- Contact Section -->
                <section class="py-20 md:py-28 bg-slate-50">
                    <div class="container-app">
                        <div class="grid lg:grid-cols-2 gap-16 items-start">
                            
                            <!-- Kolom Kiri: Info Kontak -->
                            <div class="space-y-12">
                                <div class="space-y-4">
                                    <h1 class="section-title text-left">Hubungi Kami</h1>
                                    <p class="text-lg text-gray-600">
                                        Punya pertanyaan atau butuh informasi lebih lanjut? Tim kami siap membantu Anda. Hubungi kami melalui detail di bawah atau isi formulir di samping.
                                    </p>
                                    <p class="text-lg font-semibold text-gray-800">
                                        info@persijadevelopment.id
                                    </p>
                                    <p class="text-lg font-semibold text-gray-800">
                                        +62 811-2626-322
                                    </p>
                                </div>
                                <div class="space-y-8 pt-10 border-t border-gray-200">
                                    <div v-for="contact in contactPoints" :key="contact.name" class="flex items-start gap-4">
                                        <div>
                                            <a href="#" class="font-bold text-lg text-gray-900 hover:text-primary transition-colors">{{ contact.name }}</a>
                                            <p class="mt-1 text-base text-gray-500">{{ contact.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Formulir Kontak -->
                            <div>
                                <form @submit.prevent="submitForm" class="space-y-6 bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                                     <h3 class="font-header text-2xl font-bold text-gray-800">Get in Touch</h3>
                                     <p class="text-gray-600">Anda dapat menghubungi kami kapan saja.</p>
                                     <div class="grid sm:grid-cols-2 gap-6">
                                        <div>
                                            <label for="first_name" class="form-label">Nama Depan</label>
                                            <input type="text" id="first_name" v-model="form.first_name" class="form-input" required>
                                        </div>
                                        <div>
                                            <label for="last_name" class="form-label">Nama Belakang</label>
                                            <input type="text" id="last_name" v-model="form.last_name" class="form-input" required>
                                        </div>
                                     </div>
                                    <div>
                                        <label for="email" class="form-label">Alamat Email</label>
                                        <input type="email" id="email" v-model="form.email" class="form-input" required>
                                    </div>
                                    <div>
                                        <label for="phone" class="form-label">Nomor Telepon</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">+62</div>
                                            <input type="tel" id="phone" v-model="form.phone" class="form-input pl-12" placeholder="812-3456-7890" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="message" class="form-label">Bagaimana kami bisa membantu?</label>
                                        <textarea id="message" v-model="form.message" rows="5" class="form-input" required></textarea>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary w-full">Kirim Pesan</button>
                                    </div>
                                    <transition
                                        enter-active-class="transition ease-out duration-300"
                                        enter-from-class="opacity-0 translate-y-2"
                                        enter-to-class="opacity-100 translate-y-0"
                                    >
                                        <div v-if="formSubmitted" class="p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm">
                                            Terima kasih! Pesan Anda telah terkirim. Kami akan segera menghubungi Anda.
                                        </div>
                                    </transition>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Map Section -->
                <section class="py-20 md:py-28 bg-slate-50">
                    <div class="container-app">
                        <div class="grid md:grid-cols-2 gap-16 items-center">
                            <div class="h-80 md:h-[500px] w-full rounded-2xl overflow-hidden shadow-lg">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3964.904899143896!2d106.7376897153676!3d-6.381986364197998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ef09a0bac013%3A0xf5ada56c7f922ad2!2sPersija%20Development%20Center!5e0!3m2!1sen!2sid!4v1687769950000!5m2!1sen!2sid" 
                                    width="100%" 
                                    height="100%" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                            <div>
                                <h2 class="section-title text-left">Lokasi Kami</h2>
                                <p class="section-subtitle !mx-0 !max-w-none text-left mt-4">
                                    Pusat pelatihan utama kami berlokasi di Depok, dilengkapi dengan fasilitas modern untuk mendukung pengembangan atlet secara maksimal.
                                </p>
                                <div class="mt-6">
                                    <h4 class="font-bold text-lg text-gray-900">Headquarters</h4>
                                    <p class="mt-1 text-gray-600">Persija Development Center</p>
                                    <p class="text-gray-600">Bojongsari Baru, Kec. Bojongsari, Kota Depok, Jawa Barat 16516</p>
                                    <a href="https://maps.app.goo.gl/qBsfhJtNpsWpVZKC6" target="_blank" class="inline-flex items-center gap-2 mt-4 font-semibold text-primary hover:underline">
                                        Buka di Google Maps <ArrowRightIcon class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FAQ Section -->
                 <section class="py-20 md:py-28 bg-white">
                    <div class="container-app">
                        <div class="grid md:grid-cols-3 gap-8">
                            <div>
                                <h2 class="section-title text-left">Frequently Asked Questions</h2>
                                <p class="section-subtitle !mx-0 !max-w-none text-left mt-4">Beberapa pertanyaan yang sering diajukan kepada kami.</p>
                            </div>
                            <div class="md:col-span-2 space-y-4">
                                <div v-for="(faq, index) in faqs" :key="index" class="border border-gray-200 rounded-lg">
                                    <button @click="toggleFaq(index)" class="w-full flex justify-between items-center p-5 text-left font-semibold text-gray-800">
                                        <span>{{ faq.question }}</span>
                                        <ChevronDownIcon class="h-5 w-5 transition-transform duration-300" :class="{'rotate-180': faq.open}" />
                                    </button>
                                    <div v-if="faq.open" class="px-5 pb-5 text-gray-600">
                                        {{ faq.answer }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </main>
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

/* Form Styles */
.form-label {
    @apply block text-sm font-medium text-gray-700 mb-1;
}
.form-input {
    @apply block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none transition-all;
}
.form-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(217, 38, 46, 0.2);
}
</style>
