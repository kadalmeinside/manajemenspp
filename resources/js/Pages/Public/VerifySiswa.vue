<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckBadgeIcon, ExclamationCircleIcon, UserCircleIcon, AcademicCapIcon, IdentificationIcon, HomeIcon } from '@heroicons/vue/24/outline';

defineProps({
    siswa: Object,
    pageTitle: String,
});
</script>

<template>
    <Head :title="pageTitle" />

    <PublicLayout>
        <div class="py-12 bg-gray-50 min-h-[70vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <!-- Status Icon -->
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-white shadow-lg border-4" :class="siswa.status_siswa === 'Aktif' ? 'border-green-100' : 'border-red-100'">
                    <CheckBadgeIcon v-if="siswa.status_siswa === 'Aktif'" class="h-16 w-16 text-green-500" />
                    <ExclamationCircleIcon v-else class="h-16 w-16 text-red-500" />
                </div>
                
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Verifikasi ID Card
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Status keanggotaan siswa di Persija Development.
                </p>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow-xl sm:rounded-3xl sm:px-10 border border-gray-100 relative overflow-hidden">
                    <!-- Decorative Background -->
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full opacity-10 blur-2xl" :class="siswa.status_siswa === 'Aktif' ? 'bg-green-500' : 'bg-red-500'"></div>
                    
                    <div class="relative z-10">
                        <div class="text-center mb-8">
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold shadow-sm" :class="siswa.status_siswa === 'Aktif' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'">
                                {{ siswa.status_siswa === 'Aktif' ? 'STATUS: AKTIF' : 'STATUS: TIDAK AKTIF' }}
                            </span>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <UserCircleIcon class="h-8 w-8 text-gray-400 mr-4" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Siswa</p>
                                    <p class="text-lg font-bold text-gray-900">{{ siswa.nama_siswa }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <IdentificationIcon class="h-8 w-8 text-gray-400 mr-4" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor Induk Siswa (NIS)</p>
                                    <p class="text-lg font-bold text-gray-900">{{ siswa.nis || '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <AcademicCapIcon class="h-8 w-8 text-gray-400 mr-4" />
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Kelas</p>
                                    <p class="text-lg font-bold text-gray-900">{{ siswa.kelas }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <Link :href="route('welcome')" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-red-600 transition-colors">
                        <HomeIcon class="h-4 w-4 mr-2" />
                        Kembali ke Beranda
                    </Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
