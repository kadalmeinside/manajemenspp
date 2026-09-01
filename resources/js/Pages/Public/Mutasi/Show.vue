<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    ArrowsRightLeftIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    mutasi: Object,
    document: Object,
    flash: Object,
});

const form = useForm({
    agreed_by: '',
    agree_terms: false,
    legal_document_id: props.document?.id || '',
});

const sppLamaFormatted = computed(() => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(props.mutasi.siswa.jumlah_spp_custom);
});

const sppBaruFormatted = computed(() => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(props.mutasi.spp_baru);
});

const submit = () => {
    form.post(route('mutasi.approve', props.mutasi.token), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Persetujuan Pindah Cabang" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            
            <div class="text-center mb-8">
                <ArrowsRightLeftIcon class="mx-auto h-12 w-12 text-indigo-600" />
                <h2 class="mt-4 text-3xl font-extrabold text-gray-900 dark:text-white">Persetujuan Pindah Cabang / Kelas</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Mohon tinjau kembali rincian pemindahan untuk siswa <strong>{{ mutasi.siswa.nama_siswa }}</strong>
                </p>
            </div>

            <!-- Pesan Sukses -->
            <div v-if="flash?.success || mutasi.status === 'APPROVED'" class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-400 p-4 mb-8 rounded-r-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckBadgeIcon class="h-6 w-6 text-green-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-300">Berhasil Disetujui</h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-400">
                            <p>Terima kasih. Permohonan pindah cabang/kelas telah berhasil diproses.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pesan Error / Expired -->
            <div v-else-if="flash?.error || mutasi.status === 'EXPIRED' || mutasi.status === 'CANCELLED'" class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-400 p-4 mb-8 rounded-r-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon class="h-6 w-6 text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Tidak Dapat Diproses</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-400">
                            <p>{{ flash?.error || 'Link persetujuan ini sudah tidak berlaku atau dibatalkan.' }}</p>
                            <p class="mt-1">Silakan hubungi admin untuk mendapatkan link baru jika diperlukan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="mutasi.status === 'PENDING' && !mutasi.is_expired">
                <!-- Rincian Mutasi -->
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-8">
                    <div class="px-4 py-5 sm:px-6 bg-gray-50 dark:bg-gray-700/50">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Rincian Kepindahan</h3>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
                        <dl class="sm:divide-y sm:divide-gray-200 dark:sm:divide-gray-700">
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Siswa</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold sm:mt-0 sm:col-span-2">{{ mutasi.siswa.nama_siswa }}</dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cabang/Kelas Lama</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ mutasi.from_kelas?.nama_kelas || '-' }}</dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cabang/Kelas Baru</dt>
                                <dd class="mt-1 text-sm font-bold text-indigo-600 dark:text-indigo-400 sm:mt-0 sm:col-span-2">{{ mutasi.to_kelas?.nama_kelas || '-' }}</dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SPP Lama</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ sppLamaFormatted }}</dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SPP Baru</dt>
                                <dd class="mt-1 text-sm font-bold text-indigo-600 dark:text-indigo-400 sm:mt-0 sm:col-span-2">{{ sppBaruFormatted }}</dd>
                            </div>
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Berlaku Mulai Bulan</dt>
                                <dd class="mt-1 text-sm font-bold text-green-600 dark:text-green-400 sm:mt-0 sm:col-span-2">{{ mutasi.start_month }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div v-if="document" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-8">
                    <div class="px-4 py-5 sm:px-6 bg-gray-50 dark:bg-gray-700/50 flex items-center">
                        <DocumentTextIcon class="h-5 w-5 text-gray-500 mr-2" />
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Syarat & Ketentuan</h3>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5">
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300" v-html="document?.content"></div>
                    </div>
                </div>

                <div v-else class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-400 p-4 mb-8 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" aria-hidden="true" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Dokumen Persetujuan Belum Diatur</h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                <p>Admin belum mengonfigurasi Dokumen Legal untuk Syarat dan Ketentuan Pindah Cabang. Anda tidak dapat menyetujui permohonan ini sebelum admin mengaturnya.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Persetujuan -->
                <div v-if="document" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="agreed_by" value="Nama Terang (Orang Tua / Wali)" />
                                <TextInput
                                    id="agreed_by"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.agreed_by"
                                    placeholder="Masukkan nama lengkap Anda"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.agreed_by" />
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        id="agree_terms"
                                        type="checkbox"
                                        v-model="form.agree_terms"
                                        class="w-5 h-5 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        required
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="agree_terms" class="font-medium text-gray-700 dark:text-gray-300">
                                        Saya telah membaca dan menyetujui rincian kepindahan beserta syarat & ketentuan di atas.
                                    </label>
                                    <InputError class="mt-2" :message="form.errors.agree_terms" />
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing || !form.agree_terms" class="w-full justify-center text-base py-3">
                                    Setuju & Konfirmasi Pindah Cabang
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</template>
