<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import FormStepper from '@/Components/FormStepper.vue';
import AnimatedBackground from '@/Components/AnimatedBackground.vue';

const props = defineProps({
    pageTitle: String,
    academyClass: Object,
    termsDocument: Object,
    flash: Object,
    errors: Object,
});

const form = useForm({
    nama_siswa: '',
    tanggal_lahir: '',
    id_kelas: props.academyClass?.id_kelas || null,
    user_name: '', 
    email_wali: '',
    nomor_telepon_wali: '',
    user_password: '',
    user_password_confirmation: '',
    terms: false,
    legal_document_id: props.termsDocument?.id || null,
});

// State untuk modal Syarat & Ketentuan
const showTermsModal = ref(false);

const handleTermsClick = () => {
    if (form.terms) {
        form.terms = false;
    } else {
        showTermsModal.value = true;
    }
};

const acceptTerms = () => {
    form.terms = true;
    showTermsModal.value = false;
};


const steps = [
    { number: 1, title: 'Data Siswa' },
    { number: 2, title: 'Data Ortu/Wali' },
    { number: 3, title: 'Konfirmasi' }
];

const currentStep = ref(1);

const nextStep = () => {
    form.clearErrors();
    let hasError = false;

    if (currentStep.value === 1) {
        if (!form.nama_siswa) { form.setError('nama_siswa', 'Nama Lengkap Siswa wajib diisi.'); hasError = true; }
        if (!form.tanggal_lahir) { form.setError('tanggal_lahir', 'Tanggal Lahir wajib diisi.'); hasError = true; }
        if (!form.id_kelas) { form.setError('id_kelas', 'Silakan pilih cabang/kelas.'); hasError = true; }
    } else if (currentStep.value === 2) {
        if (!form.user_name) { form.setError('user_name', 'Nama Lengkap Wali wajib diisi.'); hasError = true; }
        if (!form.nomor_telepon_wali) { form.setError('nomor_telepon_wali', 'Nomor WhatsApp Wali wajib diisi.'); hasError = true; }
        if (!form.email_wali) { form.setError('email_wali', 'Alamat Email Wali wajib diisi.'); hasError = true; }
        if (!form.user_password) { form.setError('user_password', 'Password wajib diisi.'); hasError = true; }
        if (form.user_password && form.user_password !== form.user_password_confirmation) {
            form.setError('user_password_confirmation', 'Konfirmasi password tidak cocok.');
            hasError = true;
        }
    }

    if (!hasError) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};
const submit = () => {
    let hasClientErrors = false;
    form.clearErrors();

    if (!form.terms) { form.setError('terms', 'Anda harus menyetujui syarat dan ketentuan.'); hasClientErrors = true; }

    if (hasClientErrors) {
        return; // Hentikan proses jika ada error di frontend
    }

    form.post(route('re-register.store'), {
        onError: () => {
            form.reset('user_password', 'user_password_confirmation');
        },
    });
};

</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen overflow-hidden flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        
        <!-- Latar Belakang Gradasi Animasi -->
        <div class="animated-gradient absolute inset-0 -z-20"></div>
        <AnimatedBackground />
        
        <div class="w-full max-w-4xl space-y-8 z-10 relative">
            <div class="text-center">
                <div class="flex justify-center items-center gap-4">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ pageTitle }}</h2>
                </div>
                <p class="mt-2 text-md text-gray-600 dark:text-gray-400">
                    Lengkapi data pendaftaran ulang Academy untuk mendapatkan akses ke sistem terbaru kami.
                </p>
            </div>

            <!-- Peringatan jika dokumen legal belum diatur -->
        <div v-if="!termsDocument" class="mt-8 bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-400 p-4 rounded-r-md z-10 relative">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Form Pendaftaran Ulang Belum Dapat Diakses</h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                        <p>Mohon maaf, sistem mendeteksi bahwa Admin belum mengonfigurasi dokumen Syarat dan Ketentuan untuk pendaftaran ulang. Silakan hubungi Admin untuk mengatur dokumen persetujuan di Pengaturan Aplikasi.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="props.flash?.success" class="mt-8 bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl shadow-2xl rounded-2xl p-8 z-10 relative text-center">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.flash.message }}</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Email konfirmasi berisi detail pendaftaran telah dikirim ke <strong>{{ props.flash.completed_data?.email_wali }}</strong>.
            </p>
            
            <div class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-900/50 text-left">
                <h4 class="font-medium text-gray-800 dark:text-gray-200">Detail Pendaftaran:</h4>
                <ul class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li><strong>NIS Baru:</strong> {{ props.flash.completed_data?.nis }}</li>
                    <li><strong>Nama Siswa:</strong> {{ props.flash.completed_data?.nama_siswa }}</li>
                    <li><strong>Nama Wali:</strong> {{ props.flash.completed_data?.nama_wali }}</li>
                </ul>
            </div>

            <div class="mt-8">
                <Link :href="route('login')" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-red-700">
                    Lanjut ke Halaman Login
                </Link>
            </div>
        </div>

        <form v-else-if="termsDocument" @submit.prevent="submit" novalidate class="mt-8 bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl shadow-2xl rounded-2xl p-4 sm:p-8 space-y-6">
                <div v-if="errors.general" class="p-4 bg-red-100 text-red-700 rounded-md">
                    {{ errors.general }}
                </div>
                
                <FormStepper :steps="steps" :currentStep="currentStep" />

                <!-- Data Siswa -->
                <div v-show="currentStep === 1" class="pt-2">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Data Diri Siswa</h3>
                    <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        <div class="sm:col-span-2">
                            <InputLabel for="nama_siswa" value="Nama Lengkap Siswa" required/>
                            <TextInput id="nama_siswa" v-model="form.nama_siswa" @input="form.clearErrors('nama_siswa')" type="text" class="mt-1 block w-full" placeholder="Cth: Budi Santoso" required />
                            <InputError :message="form.errors.nama_siswa" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_lahir" value="Tanggal Lahir" required/>
                            <TextInput id="tanggal_lahir" v-model="form.tanggal_lahir" @input="form.clearErrors('tanggal_lahir')" type="date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.tanggal_lahir" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="id_kelas" value="Pilih Cabang/Kelas" required/>
                            <TextInput id="id_kelas" type="text" :value="academyClass?.nama_kelas" disabled class="mt-1 block w-full bg-gray-100 dark:bg-gray-800 text-gray-500 cursor-not-allowed" />
                             <InputError :message="form.errors.id_kelas" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-12 mb-8 flex justify-end">
                        <PrimaryButton @click="nextStep" type="button" class="w-full sm:w-auto px-8 bg-red-600 hover:bg-red-700 justify-center">Selanjutnya</PrimaryButton>
                    </div>
                </div>

                <!-- Data Ortu/Wali -->
                <div v-show="currentStep === 2" class="pt-2">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Data Ortu/Wali</h3>
                    <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        <div>
                            <InputLabel for="user_name" value="Nama Lengkap Wali" required/>
                            <TextInput id="user_name" v-model="form.user_name" @input="form.clearErrors('user_name')" type="text" class="mt-1 block w-full" placeholder="Cth: Ayah Budi" required />
                            <InputError :message="form.errors.user_name" class="mt-2" />
                        </div>
                         <div>
                            <InputLabel for="nomor_telepon_wali" value="Nomor WhatsApp Wali" required/>
                            <TextInput id="nomor_telepon_wali" v-model="form.nomor_telepon_wali" @input="form.nomor_telepon_wali = form.nomor_telepon_wali.replace(/\D/g, ''); form.clearErrors('nomor_telepon_wali')" type="tel" inputmode="numeric" class="mt-1 block w-full" placeholder="Cth: 081234567890" required />
                            <InputError :message="form.errors.nomor_telepon_wali" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="email_wali" value="Alamat Email Wali (untuk login)" required/>
                            <div class="relative">
                                <TextInput id="email_wali" v-model="form.email_wali" @input="form.clearErrors('email_wali');"  type="email" class="mt-1 block w-full" placeholder="Cth: wali.budi@email.com" required />
                            </div>
                            <InputError :message="form.errors.email_wali" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="user_password" value="Buat Password" required/>
                            <TextInput id="user_password" v-model="form.user_password" @input="form.clearErrors('user_password')" type="password" class="mt-1 block w-full" placeholder="••••••••" required />
                            <InputError :message="form.errors.user_password" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="user_password_confirmation" value="Konfirmasi Password" required/>
                            <TextInput id="user_password_confirmation" v-model="form.user_password_confirmation" @input="form.clearErrors('user_password_confirmation')" type="password" class="mt-1 block w-full" placeholder="••••••••" required />
                            <InputError :message="form.errors.user_password_confirmation" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-12 mb-8 flex flex-col-reverse sm:flex-row sm:justify-between gap-4">
                        <SecondaryButton @click="prevStep" type="button" class="w-full sm:w-auto px-8 justify-center">Kembali</SecondaryButton>
                        <PrimaryButton @click="nextStep" type="button" class="w-full sm:w-auto px-8 bg-red-600 hover:bg-red-700 justify-center">Selanjutnya</PrimaryButton>
                    </div>
                </div>
                
                <!-- Konfirmasi & Pembayaran -->
                <div v-show="currentStep === 3" class="pt-2">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Konfirmasi</h3>
                     <div class="p-4 bg-gray-100 dark:bg-gray-700/50 rounded-lg">
                        <!-- Tidak ada biaya untuk daftar ulang -->
                        <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-900 border-2 rounded-lg transition-all duration-700" :class="!form.terms ? 'border-red-300 dark:border-red-800 shadow-[0_0_15px_rgba(239,68,68,0.15)]' : 'border-gray-200 dark:border-gray-700'">
                            <div class="mb-3">
                                <span class="text-xs font-bold uppercase tracking-wider" :class="!form.terms ? 'text-red-500' : 'text-green-600'">
                                    <span v-if="!form.terms">* Wajib dibaca & disetujui</span>
                                    <span v-else>✓ Persetujuan Diterima</span>
                                </span>
                            </div>
                            <label class="flex items-start gap-3 cursor-pointer group" @click.prevent="handleTermsClick">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input type="checkbox" :checked="form.terms" class="w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 pointer-events-none transition-colors">
                                </div>
                                <div class="text-sm">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 block mb-1">Syarat dan Ketentuan Pendaftaran</span>
                                    <span class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                        Saya menyatakan bahwa data yang diisi adalah benar, dan saya telah membaca serta menyetujui <span @click.stop="showTermsModal = true" class="text-red-600 hover:underline font-medium">Syarat dan Ketentuan</span> yang berlaku.
                                    </span>
                                </div>
                            </label>
                            <InputError :message="form.errors.terms" class="mt-2" />
                        </div>
                     </div>

                        <div class="mt-12 mb-8 flex flex-col-reverse sm:flex-row sm:justify-between gap-4 items-center sm:items-stretch">
                            <SecondaryButton @click="prevStep" type="button" class="w-full sm:w-auto px-6 justify-center">Kembali</SecondaryButton>
                            <PrimaryButton class="w-full sm:w-auto px-8 bg-red-600 hover:bg-red-700 focus:ring-red-500 justify-center" :disabled="form.processing || !form.terms" :class="{ 'opacity-50 cursor-not-allowed': !form.terms }">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Data Siswa</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
        </div>

        <!-- Modal untuk Syarat & Ketentuan -->
        <Modal :show="showTermsModal" @close="showTermsModal = false" maxWidth="2xl">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Syarat dan Ketentuan</h2>
                <div v-if="termsDocument" class="text-xs mb-2">{{ termsDocument.name }} (v{{ termsDocument.version }})</div>
                <div v-if="termsDocument" class="mt-4 prose prose-sm max-w-none text-gray-600 dark:text-gray-300 overflow-y-auto max-h-[60vh]" v-html="termsDocument.content">
                </div>

                <div class="mt-6 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <SecondaryButton @click="showTermsModal = false">Tidak Setuju</SecondaryButton>
                    <PrimaryButton @click="acceptTerms" class="bg-red-600 hover:bg-red-700 focus:ring-red-500">Setuju</PrimaryButton>
                </div>
            </div>
        </Modal>
        
        <!-- Loading Overlay Layar Penuh -->
        <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="form.processing" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm">
                <div class="w-20 h-20 loading-ball">
                   <img src="/images/soccer-ball.png" alt="Loading..." class="w-full h-full" onerror="this.style.display='none'">
                </div>
                <p class="mt-4 text-white text-lg font-semibold">Menyimpan data pendaftaran ulang...</p>
            </div>
        </transition>

    </div>
</template>

<style scoped>
@keyframes gradient-animation {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
.animated-gradient {
  background: linear-gradient(-45deg, #f9fafb, #eef2ff, #f0f9ff);
  background-size: 400% 400%;
  animation: gradient-animation 25s ease infinite;
}
.dark .animated-gradient {
  background: linear-gradient(-45deg, #1e293b, #111827, #0c4a6e);
  background-size: 400% 400%;
  animation: gradient-animation 25s ease infinite;
}

@keyframes bounce-spin {
  0%, 100% {
    transform: translateY(-25%) rotate(0deg);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0) rotate(180deg);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}
.loading-ball {
    animation: bounce-spin 1.5s infinite;
}
</style>
