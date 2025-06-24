<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    pageTitle: String,
});
</script>

<template>
    <Head :title="pageTitle" />
    <PublicLayout>
        <div class="pt-24 pb-12 bg-gray-100 dark:bg-zinc-900">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-zinc-800 shadow-xl rounded-lg p-8 md:p-10">
                    <h1 class="text-3xl font-bold font-teko tracking-wider text-gray-900 dark:text-white mb-6 border-b pb-4 border-gray-200 dark:border-gray-700">
                        {{ pageTitle }}
                    </h1>
                    <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-300">
                        <p>Kebijakan Privasi ini menjelaskan bagaimana Persija Development ("kami") mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda saat Anda menggunakan layanan kami.</p>
                        
                        <h3>1. Informasi yang Kami Kumpulkan</h3>
                        <p><strong>Informasi yang Anda berikan langsung.</strong> Ini termasuk data pribadi yang Anda masukkan saat pendaftaran, seperti nama siswa, tanggal lahir, nama wali, alamat email, dan nomor telepon.</p>
                        <p><strong>Informasi Pembayaran.</strong> Saat Anda melakukan pembayaran, kami memproses transaksi melalui penyedia layanan pihak ketiga (Xendit). Kami tidak menyimpan detail kartu kredit atau rekening bank Anda secara langsung di server kami.</p>

                        <h3>2. Bagaimana Kami Menggunakan Informasi Anda</h3>
                        <p><strong>Untuk menyediakan layanan.</strong> Data Anda kami gunakan untuk mengelola keanggotaan, mengorganisir jadwal latihan, dan mengirimkan informasi terkait program.</p>
                        <p><strong>Untuk Komunikasi.</strong> Kami menggunakan email dan nomor WhatsApp Anda untuk mengirimkan tagihan, pengumuman penting, dan informasi mengenai kemajuan siswa.</p>
                        <p><strong>Untuk Keamanan.</strong> Informasi seperti alamat IP digunakan untuk memverifikasi identitas dan menjaga keamanan akun Anda.</p>

                        <h3>3. Keamanan Data</h3>
                        <p><strong>Kami berkomitmen untuk melindungi data Anda.</strong> Kami menerapkan langkah-langkah keamanan teknis dan organisasi yang wajar untuk mencegah akses, pengungkapan, atau penghapusan data Anda yang tidak sah.</p>

                        <h3>4. Hak Anda</h3>
                        <p><strong>Akses dan Koreksi.</strong> Anda berhak untuk mengakses dan memperbarui data pribadi yang kami simpan. Anda dapat melakukannya melalui halaman profil di area siswa/wali.</p>

                        <p class="mt-8">Kebijakan ini dapat berubah sewaktu-waktu. Kami akan memberitahukan perubahan signifikan melalui email atau pengumuman di situs kami.</p>
                        <p>Terakhir diperbarui: 24 Juni 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Styling umum untuk halaman legal, bisa dipindahkan ke file CSS global jika perlu */
.prose h3 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}
</style>
