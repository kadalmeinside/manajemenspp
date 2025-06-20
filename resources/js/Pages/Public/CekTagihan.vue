<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowPathIcon, BanknotesIcon, EyeIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    pageTitle: String,
    errors: Object,
    siswa: Object,
    tagihanList: Array,
});

const form = useForm({
    id_siswa: '',
    tanggal_lahir: '',
});

const submit = () => {
    form.post(route('tagihan.check_status'), {
        preserveState: true,
        onError: () => {
            form.reset('id_siswa', 'tanggal_lahir');
        }
    });
};

const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="pageTitle" />
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-slate-800 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="p-4 text-center bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm shadow-sm sticky top-0 z-10">
            <Link href="/">
                <h1 class="text-2xl font-teko font-bold text-gray-800 dark:text-white tracking-wider">PERSIJA DEVELOPMENT</h1>
            </Link>
        </header>

        <main class="flex-grow flex items-center justify-center p-4">
            <div class="w-full max-w-2xl mx-auto">
                <!-- Tampilkan Form jika tidak ada data siswa -->
                <div v-if="!siswa" class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white">{{ pageTitle }}</h2>
                    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">Masukkan ID Siswa dan Tanggal Lahir untuk melihat status pembayaran.</p>

                    <form @submit.prevent="submit" class="mt-8 space-y-6">
                        <div v-if="errors.lookup" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ errors.lookup }}</span>
                        </div>

                        <div>
                            <label for="id_siswa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID Siswa</label>
                            <input v-model="form.id_siswa" id="id_siswa" type="text" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <div v-if="errors.id_siswa" class="text-red-500 text-xs mt-1">{{ errors.id_siswa }}</div>
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Lahir</label>
                            <input v-model="form.tanggal_lahir" id="tanggal_lahir" type="date" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                             <div v-if="errors.tanggal_lahir" class="text-red-500 text-xs mt-1">{{ errors.tanggal_lahir }}</div>
                        </div>
                        <div>
                            <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                <span v-if="!form.processing">Cek Tagihan</span>
                                <span v-else>Mencari...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tampilkan Hasil jika data siswa ditemukan -->
                <div v-else class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                    <div class="flex justify-end mb-4">
                        <Link :href="route('tagihan.check_form')" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center">
                           <ArrowPathIcon class="h-4 w-4 mr-1"/>
                            Cari Siswa Lain
                        </Link>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Status Tagihan</h2>
                        <p class="mt-1 text-lg text-gray-700 dark:text-gray-300">{{ siswa.nama_siswa }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ID: {{ siswa.id_siswa }}</p>
                    </div>

                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-if="tagihanList.length === 0" class="py-4 text-center text-gray-500">
                                Tidak ada data tagihan untuk siswa ini.
                            </li>
                            <li v-else v-for="tagihan in tagihanList" :key="tagihan.id_tagihan" class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <p class="text-md font-semibold text-gray-800 dark:text-gray-100">{{ tagihan.periode_tagihan }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ tagihan.total_tagihan_formatted }}</p>
                                </div>
                                <div class="flex items-center gap-4 mt-2 sm:mt-0">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(tagihan.status_pembayaran_xendit)">
                                        {{ tagihan.status_pembayaran_xendit }}
                                    </span>
                                    <a v-if="tagihan.can_pay && tagihan.xendit_payment_url" :href="tagihan.xendit_payment_url" target="_blank" class="inline-flex items-center text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2 ml-auto md:ml-0">
                                        <BanknotesIcon class="h-5 w-5 mr-2" />
                                        Bayar
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
