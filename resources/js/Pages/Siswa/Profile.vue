<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import Toast from '@/Components/Toast.vue';
import {
    UserCircleIcon,
    EnvelopeIcon,
    PhoneIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    IdentificationIcon,
    CalendarDaysIcon,
    AcademicCapIcon,
    CheckBadgeIcon
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    siswa: Object,
    pageTitle: String,
    status: String,
    mutasiSiswas: { type: Array, default: () => [] },
});

const user = usePage().props.auth.user;
const flashMessage = computed(() => usePage().props.flash?.message);
const flashType = computed(() => usePage().props.flash?.type || 'info');

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const infoForm = useForm({
    email_wali: props.siswa.email_wali,
    nomor_telepon_wali: props.siswa.nomor_telepon_wali,
});

const updateInfo = () => {
    infoForm.put(route('siswa.profil.update_info'), { preserveScroll: true });
};

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('siswa.profil.update_password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) passwordForm.reset('password', 'password_confirmation');
            if (passwordForm.errors.current_password) passwordForm.reset('current_password');
        },
    });
};

const getStatusClass = (status) => {
    if (status === 'Aktif') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800';
};

const initials = computed(() => {
    return props.siswa?.nama_siswa?.charAt(0).toUpperCase() || '?';
});
</script>

<template>
    <Head :title="pageTitle" />

    <SiswaLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-extrabold text-xl md:text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center">
                    <UserCircleIcon class="h-6 w-6 md:h-8 md:w-8 mr-2 md:mr-3 text-red-600 dark:text-red-400" />
                    {{ pageTitle }}
                </h2>
            </div>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="py-4 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-5">

                <!-- Hero Card: Avatar + Biodata -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Red Gradient Banner -->
                    <div class="relative bg-gradient-to-r from-red-700 to-rose-600 h-24 overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                        <div class="absolute top-4 -left-6 w-24 h-24 bg-orange-400 opacity-10 rounded-full blur-xl"></div>
                    </div>

                    <!-- Avatar + Info -->
                    <div class="px-6 pb-6">
                        <!-- Avatar overlapping banner -->
                        <div class="-mt-10 mb-4 flex items-end justify-between">
                            <div class="relative">
                                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center border-4 border-white dark:border-gray-800 shadow-lg">
                                    <span class="text-3xl font-black text-white">{{ initials }}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold" :class="getStatusClass(siswa.status_siswa)">
                                <CheckBadgeIcon v-if="siswa.status_siswa === 'Aktif'" class="w-3.5 h-3.5 mr-1" />
                                {{ siswa.status_siswa }}
                            </span>
                        </div>

                        <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Orang Tua / Wali: {{ user?.name }}</p>

                        <!-- Info Grid -->
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-3 flex items-start space-x-2.5">
                                <div class="p-1.5 bg-red-100 dark:bg-red-900/30 rounded-lg shrink-0">
                                    <AcademicCapIcon class="h-4 w-4 text-red-600 dark:text-red-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kelas</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate" :title="siswa.kelas?.nama_kelas || '-'">{{ siswa.kelas?.nama_kelas || '-' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-3 flex items-start space-x-2.5">
                                <div class="p-1.5 bg-red-100 dark:bg-red-900/30 rounded-lg shrink-0">
                                    <IdentificationIcon class="h-4 w-4 text-red-600 dark:text-red-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">NIS</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate" :title="siswa.nis || '-'">{{ siswa.nis || '-' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-3 flex items-start space-x-2.5">
                                <div class="p-1.5 bg-red-100 dark:bg-red-900/30 rounded-lg shrink-0">
                                    <CalendarDaysIcon class="h-4 w-4 text-red-600 dark:text-red-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tgl Lahir</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate" :title="siswa.tanggal_lahir_formatted">{{ siswa.tanggal_lahir_formatted }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-3 flex items-start space-x-2.5">
                                <div class="p-1.5 bg-red-100 dark:bg-red-900/30 rounded-lg shrink-0">
                                    <CalendarDaysIcon class="h-4 w-4 text-red-600 dark:text-red-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Bergabung</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate" :title="siswa.tanggal_bergabung_formatted">{{ siswa.tanggal_bergabung_formatted }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Kontak -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Informasi Kontak & Login</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Perbarui email login dan nomor telepon Anda.</p>
                    </div>
                    <form @submit.prevent="updateInfo" class="px-6 py-5 space-y-4">
                        <!-- Email -->
                        <div>
                            <label for="email_wali" class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">Email Wali (Login)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <EnvelopeIcon class="h-4 w-4 text-gray-400" />
                                </div>
                                <input
                                    id="email_wali" type="email" v-model="infoForm.email_wali" required
                                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                                />
                            </div>
                            <InputError class="mt-1.5" :message="infoForm.errors.email_wali" />
                        </div>
                        <!-- Telepon -->
                        <div>
                            <label for="nomor_telepon_wali" class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nomor Telepon Wali</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <PhoneIcon class="h-4 w-4 text-gray-400" />
                                </div>
                                <input
                                    id="nomor_telepon_wali" type="text" v-model="infoForm.nomor_telepon_wali" required
                                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                                />
                            </div>
                            <InputError class="mt-1.5" :message="infoForm.errors.nomor_telepon_wali" />
                        </div>
                        <div class="flex items-center gap-4 pt-1">
                            <button type="submit" :disabled="infoForm.processing"
                                class="px-6 py-2.5 bg-red-600 text-white text-sm font-bold rounded-xl shadow-sm shadow-red-500/30 hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all disabled:opacity-60">
                                Simpan Perubahan
                            </button>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="infoForm.recentlySuccessful" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Tersimpan!</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Update Password -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Ubah Password</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gunakan password yang kuat dan unik untuk keamanan akun Anda.</p>
                    </div>
                    <form @submit.prevent="updatePassword" class="px-6 py-5 space-y-4">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">Password Saat Ini</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-4 w-4 text-gray-400" />
                                </div>
                                <input
                                    id="current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    v-model="passwordForm.current_password"
                                    class="block w-full pl-10 pr-10 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                                />
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-red-500">
                                    <EyeSlashIcon v-if="showCurrentPassword" class="h-4 w-4" />
                                    <EyeIcon v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError class="mt-1.5" :message="passwordForm.errors.current_password" />
                        </div>
                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">Password Baru</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-4 w-4 text-gray-400" />
                                </div>
                                <input
                                    id="password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    v-model="passwordForm.password"
                                    class="block w-full pl-10 pr-10 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                                />
                                <button type="button" @click="showNewPassword = !showNewPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-red-500">
                                    <EyeSlashIcon v-if="showNewPassword" class="h-4 w-4" />
                                    <EyeIcon v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError class="mt-1.5" :message="passwordForm.errors.password" />
                        </div>
                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-4 w-4 text-gray-400" />
                                </div>
                                <input
                                    id="password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    v-model="passwordForm.password_confirmation"
                                    class="block w-full pl-10 pr-10 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                                />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-red-500">
                                    <EyeSlashIcon v-if="showConfirmPassword" class="h-4 w-4" />
                                    <EyeIcon v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError class="mt-1.5" :message="passwordForm.errors.password_confirmation" />
                        </div>
                        <div class="flex items-center gap-4 pt-1">
                            <button type="submit" :disabled="passwordForm.processing"
                                class="px-6 py-2.5 bg-red-600 text-white text-sm font-bold rounded-xl shadow-sm shadow-red-500/30 hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all disabled:opacity-60">
                                Ubah Password
                            </button>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="passwordForm.recentlySuccessful" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Password diperbarui!</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Spacer untuk bottom nav mobile -->
                <div class="pb-4"></div>

            </div>

            <!-- Riwayat Mutasi (Full Width) -->
            <div v-if="mutasiSiswas && mutasiSiswas.length > 0" class="mt-8 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        Riwayat Pindah Cabang
                    </h3>
                </div>
                <div class="p-0 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Dibuat</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cabang Lama</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cabang Baru</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="mutasi in mutasiSiswas" :key="mutasi.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ mutasi.created_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ mutasi.from_kelas }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ mutasi.to_kelas }}<br>
                                    <span class="text-xs text-gray-500">Mulai: {{ mutasi.start_month }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="mutasi.status === 'APPROVED'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Disetujui</span>
                                    <span v-else-if="mutasi.status === 'PENDING'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Proses</span>
                                    <span v-else-if="mutasi.status === 'EXPIRED'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Kedaluwarsa</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dibatalkan</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>
