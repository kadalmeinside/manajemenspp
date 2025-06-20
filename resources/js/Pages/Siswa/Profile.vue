<script setup>
import SiswaLayout from '@/Layouts/SiswaLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    siswa: Object,
    pageTitle: String,
    status: String,
});

const user = usePage().props.auth.user;
const flashMessage = computed(() => usePage().props.flash?.message);
const flashType = computed(() => usePage().props.flash?.type || 'info');

// Form untuk update informasi kontak
const infoForm = useForm({
    email_wali: props.siswa.email_wali,
    nomor_telepon_wali: props.siswa.nomor_telepon_wali,
});

const updateInfo = () => {
    infoForm.put(route('siswa.profil.update_info'), {
        preserveScroll: true,
    });
};

// Form untuk update password
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
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

const getStatusClass = (status) => {
    if (status === 'Aktif') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
};
</script>

<template>
    <Head :title="pageTitle" />

    <SiswaLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>
        <Toast :message="flashMessage" :type="flashType" />
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Biodata Siswa -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Biodata Siswa</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Informasi dasar mengenai siswa.</p>
                        </header>
                         <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.nama_siswa }}</dd>
                            </div>
                             <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 text-sm"><span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="getStatusClass(siswa.status_siswa)">{{ siswa.status_siswa }}</span></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Lahir</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.tanggal_lahir_formatted }}</dd>
                            </div>
                             <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Bergabung</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.tanggal_bergabung_formatted }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kelas/Cabang</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.kelas.nama_kelas }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <!-- Update Informasi Kontak -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Kontak & Login</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Perbarui email login dan nomor telepon Anda.</p>
                        </header>
                        <form @submit.prevent="updateInfo" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="email_wali" value="Email Wali (Login)" />
                                <TextInput id="email_wali" type="email" class="mt-1 block w-full" v-model="infoForm.email_wali" required />
                                <InputError class="mt-2" :message="infoForm.errors.email_wali" />
                            </div>
                             <div>
                                <InputLabel for="nomor_telepon_wali" value="Nomor Telepon Wali" />
                                <TextInput id="nomor_telepon_wali" type="text" class="mt-1 block w-full" v-model="infoForm.nomor_telepon_wali" required />
                                <InputError class="mt-2" :message="infoForm.errors.nomor_telepon_wali" />
                            </div>
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="infoForm.processing">Simpan</PrimaryButton>
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="infoForm.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Tersimpan.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Update Password -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ubah Password</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.</p>
                        </header>
                        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="current_password" value="Password Saat Ini" />
                                <TextInput id="current_password" type="password" class="mt-1 block w-full" v-model="passwordForm.current_password" />
                                <InputError class="mt-2" :message="passwordForm.errors.current_password" />
                            </div>
                             <div>
                                <InputLabel for="password" value="Password Baru" />
                                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="passwordForm.password" />
                                <InputError class="mt-2" :message="passwordForm.errors.password" />
                            </div>
                             <div>
                                <InputLabel for="password_confirmation" value="Konfirmasi Password Baru" />
                                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="passwordForm.password_confirmation" />
                                <InputError class="mt-2" :message="passwordForm.errors.password_confirmation" />
                            </div>
                             <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="passwordForm.processing">Simpan</PrimaryButton>
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="passwordForm.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Tersimpan.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </SiswaLayout>
</template>
