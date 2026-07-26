<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    siswa: Object,
    message: String,
});
</script>

<template>
    <Head title="Pengunduran Diri Berhasil" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 text-center p-8">
                <CheckCircleIcon class="h-20 w-20 text-green-500 mx-auto mb-6" />
                
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Berhasil!</h2>
                
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    {{ message }}
                </p>

                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg text-sm text-gray-700 dark:text-gray-300 mb-8 border border-gray-200 dark:border-gray-700">
                    <p class="font-semibold mb-1">{{ siswa.nama_siswa }}</p>
                    <p class="text-gray-500">{{ siswa.nis || 'NIS Belum Ada' }}</p>
                </div>

                <Link :href="route('welcome')" class="w-full inline-flex justify-center items-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Kembali ke Beranda
                </Link>
            </div>
        </div>
    </div>
</template>
