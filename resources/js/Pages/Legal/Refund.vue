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
                        <p>Kami berkomitmen untuk memberikan program pelatihan dengan kualitas terbaik. Mohon perhatikan kebijakan pengembalian dana (refund) kami sebagai berikut, yang berlaku untuk semua program di Persija Development, termasuk Soccer School dan Academy Boarding School.</p>
                        <br>
                        <h3 class="text-xl font-bold font-teko">1. Biaya Pendaftaran</h3>
                        <p><strong>Seluruh biaya pendaftaran</strong> yang telah dibayarkan pada saat registrasi awal bersifat **non-refundable** (tidak dapat dikembalikan). Biaya ini digunakan untuk administrasi, alokasi slot, dan persiapan awal keanggotaan siswa.</p>
                        <br>
                        <h3 class="text-xl font-bold ">2. Biaya SPP Bulanan</h3>
                        <p><strong>Biaya SPP</strong> yang sudah dibayarkan untuk satu bulan periode berjalan tidak dapat dikembalikan, baik sebagian maupun seluruhnya. Jika siswa memutuskan untuk berhenti di tengah periode berjalan, sisa waktu latihan pada bulan tersebut tidak dapat diuangkan kembali.</p>
                        <br>
                        <h3 class="text-xl font-bold font-teko">3. Perlengkapan dan Merchandise</h3>
                        <p><strong>Biaya untuk item fisik</strong> seperti seragam latihan, merchandise, atau perlengkapan lainnya yang sudah dibayarkan dan diterima oleh peserta tidak dapat dikembalikan atau ditukar, kecuali terdapat cacat produksi yang jelas saat barang diterima.</p>
                        <br>
                        <h3 class="text-xl font-bold font-teko">4. Pembatalan Program oleh Pihak Sekolah</h3>
                        <p><strong>Dalam kondisi luar biasa</strong> (force majeure) di mana pihak sekolah terpaksa membatalkan sebuah program secara permanen, maka akan ada kebijakan pengembalian dana prorata (dihitung berdasarkan sisa waktu program) yang akan diinformasikan secara resmi lebih lanjut kepada seluruh wali murid yang terdampak.</p>
                        <br>
                        <h3 class="text-xl font-bold font-teko">5. Proses Klaim (untuk Cacat Produksi)</h3>
                        <p>Klaim untuk perlengkapan yang cacat produksi harus diajukan maksimal 3x24 jam setelah barang diterima dengan menyertakan bukti foto atau video yang jelas ke administrasi kami.</p>

                        <p class="mt-8">Dengan melakukan pembayaran, Anda dianggap telah memahami dan menyetujui seluruh poin dalam kebijakan pengembalian dana ini.</p>
                        <p>Terakhir diperbarui: 24 Juni 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Menambahkan styling untuk list agar lebih rapi */
.prose ul > li {
    padding-left: 0;
}
.prose ul > li > p {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}
</style>
