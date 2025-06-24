<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue'; // Menggunakan layout publik yang konsisten

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
                        <p>Dengan melakukan pendaftaran dan pembayaran di Persija Development, Anda sebagai orang tua/wali dan calon siswa ("Peserta") dianggap telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang berlaku di bawah ini:</p>
                        <br>
                        <h3 class="text-xl font-bold font-teko">1. Pendaftaran dan Keanggotaan</h3>
                        <ul>
                            <li><p><strong>Validitas Data.</strong> Peserta menjamin bahwa semua data yang diisikan saat pendaftaran adalah benar, akurat, dan dapat dipertanggungjawabkan. Pemalsuan data dapat mengakibatkan pembatalan pendaftaran tanpa pengembalian dana.</p></li>
                            <li><p><strong>Status Keanggotaan.</strong> Status keanggotaan siswa akan resmi aktif setelah seluruh biaya pendaftaran lunas dan telah diverifikasi oleh administrasi Persija Development.</p></li>
                            <li><p><strong>Biaya Pendaftaran.</strong> Biaya pendaftaran yang telah dibayarkan bersifat final dan hangus (tidak dapat dikembalikan) dengan alasan apapun.</p></li>
                        </ul>
                        <br>
                        <h3 class="text-xl font-bold font-teko">2. Pembayaran dan Biaya</h3>
                        <ul>
                            <li><p><strong>SPP Bulanan.</strong> Biaya SPP wajib dibayarkan setiap bulan sesuai dengan jadwal yang telah ditentukan. Keterlambatan pembayaran dapat mengakibatkan sanksi administratif.</p></li>
                            <li><p><strong>Biaya Lain-lain.</strong> Biaya tambahan untuk kegiatan seperti turnamen, event khusus, atau pembelian perlengkapan (seragam) akan diinformasikan secara terpisah dan tidak termasuk dalam biaya SPP bulanan.</p></li>
                        </ul>
                        <br>
                        <h3 class="text-xl font-bold font-teko">3. Program dan Latihan</h3>
                        <ul>
                            <li><p><strong>Metodologi.</strong> Persija Development menerapkan metodologi latihan yang terintegrasi dan terstruktur sebagai jenjang menuju tim profesional. Peserta diwajibkan mengikuti seluruh program dengan disiplin.</p></li>
                            <li><p><strong>Jadwal.</strong> Jadwal latihan dapat berubah sewaktu-waktu dengan pemberitahuan sebelumnya dari pihak manajemen untuk menyesuaikan dengan kondisi atau agenda klub.</p></li>
                            <li><p><strong>Fasilitas.</strong> Peserta berhak menggunakan semua fasilitas latihan yang telah disediakan, termasuk lapangan, gym, dan fasilitas pendukung lainnya, sesuai dengan aturan yang berlaku.</p></li>
                        </ul>
                        <br>
                        <h3 class="text-xl font-bold font-teko">4. Kesehatan dan Keselamatan</h3>
                        <ul>
                             <li><p><strong>Tanggung Jawab Kesehatan.</strong> Wali murid bertanggung jawab penuh atas kondisi kesehatan siswa dan wajib menginformasikan riwayat medis yang relevan. Persija Development tidak bertanggung jawab atas cedera yang terjadi selama aktivitas di luar kelalaian yang terbukti dari pihak sekolah.</p></li>
                             <li><p><strong>Asuransi.</strong> Biaya program tidak termasuk asuransi cedera. Wali murid dianjurkan untuk memiliki asuransi kesehatan pribadi untuk menanggung risiko yang mungkin terjadi.</p></li>
                        </ul>
                        <br>
                        <h3 class="text-xl font-bold font-teko">5. Peraturan dan Disiplin</h3>
                        <ul>
                            <li><p><strong>Etika dan Perilaku.</strong> Seluruh siswa diwajibkan menjaga nama baik Persija Development dengan menunjukkan sikap sportif, disiplin, dan saling menghormati, baik di dalam maupun di luar lapangan.</p></li>
                            <li><p><strong>Sanksi.</strong> Pelanggaran terhadap peraturan dan kedisiplinan akan dikenakan sanksi sesuai dengan tingkat pelanggaran, mulai dari teguran hingga pemberhentian dari program.</p></li>
                        </ul>
                        <br>
                        <p class="mt-8">Syarat dan ketentuan ini dapat diperbarui sewaktu-waktu oleh manajemen Persija Development tanpa pemberitahuan terlebih dahulu. Dengan melanjutkan proses pendaftaran, Anda dianggap menyetujui versi terbaru dari syarat dan ketentuan ini.</p>
                        <p>Terakhir diperbarui: 24 Juni 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Menambahkan styling untuk list agar lebih rapi */
h3 {
    font-weight: bold;
  }
.prose ul > li {
    padding-left: 0;
}
.prose ul > li > p {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}
</style>
