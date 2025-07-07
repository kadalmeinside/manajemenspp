<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FormPageLayout from '@/Layouts/FormPageLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    pageTitle: String,
    allKelas: Array,
    termsDocument: Object,
    errors: Object,
    flash: Object,
});

const form = useForm({
    nama_siswa: '',
    tanggal_lahir: '',
    id_kelas: '',
    user_name: '',
    email_wali: '',
    nomor_telepon_wali: '',
    user_password: '',
    user_password_confirmation: '',
    terms: false,
    legal_document_id: props.termsDocument?.id || null,
});

const showTermsModal = ref(false);

const handleTermsClick = (event) => {
    if (form.terms && event.target.tagName.toLowerCase() !== 'button') return;
    showTermsModal.value = true;
};

const acceptTerms = () => {
    form.terms = true;
    showTermsModal.value = false;
};

const submit = () => {
    form.clearErrors();
    let hasClientErrors = false;

    if (!form.nama_siswa) { form.setError('nama_siswa', 'Nama Lengkap Siswa wajib diisi.'); hasClientErrors = true; }
    if (!form.tanggal_lahir) { form.setError('tanggal_lahir', 'Tanggal Lahir wajib diisi.'); hasClientErrors = true; }
    if (!form.id_kelas) { form.setError('id_kelas', 'Silakan pilih cabang/kelas.'); hasClientErrors = true; }
    if (!form.user_name) { form.setError('user_name', 'Nama Lengkap Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.email_wali) { form.setError('email_wali', 'Alamat Email Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.nomor_telepon_wali) { form.setError('nomor_telepon_wali', 'Nomor WhatsApp Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.user_password) { form.setError('user_password', 'Password wajib diisi.'); hasClientErrors = true; }
    if (form.user_password && form.user_password !== form.user_password_confirmation) {
        form.setError('user_password_confirmation', 'Konfirmasi password tidak cocok.');
        hasClientErrors = true;
    }
    if (!form.terms) { form.setError('terms', 'Anda harus menyetujui syarat dan ketentuan.'); hasClientErrors = true; }

    if (hasClientErrors) {
        return;
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
    <FormPageLayout>
        <template #header>{{ pageTitle }}</template>

        <div v-if="form.processing" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 z-50 flex flex-col items-center justify-center">
            <svg class="animate-spin h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memproses Pendaftaran...</p>
        </div>

        <div v-if="props.flash?.success" class="text-center">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.flash.message }}</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Email konfirmasi berisi detail pendaftaran telah dikirim ke <strong>{{ props.flash.completed_data.email_wali }}</strong>.
            </p>
            
            <div class="mt-6 p-4 border rounded-lg bg-gray-50 dark:bg-gray-900 text-left">
                <h4 class="font-medium text-gray-800 dark:text-gray-200">Detail Pendaftaran:</h4>
                <ul class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li><strong>NIS Baru:</strong> {{ props.flash.completed_data.nis }}</li>
                    <li><strong>Nama Siswa:</strong> {{ props.flash.completed_data.nama_siswa }}</li>
                    <li><strong>Nama Wali:</strong> {{ props.flash.completed_data.nama_wali }}</li>
                </ul>
            </div>

            <div class="mt-8">
                <Link :href="route('login')" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-red-700">
                    Lanjut ke Halaman Login
                </Link>
            </div>
        </div>

        <form v-else @submit.prevent="submit" novalidate class="space-y-6">
            <div v-if="errors.general" class="p-4 bg-red-100 text-red-700 rounded-md">
                {{ errors.general }}
            </div>
            
            <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <legend class="text-lg font-medium text-gray-900 dark:text-white">1. Data Diri Siswa</legend>
                <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
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
                        <select id="id_kelas" v-model="form.id_kelas" @change="form.clearErrors('id_kelas')" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="" disabled>-- Pilih Cabang --</option>
                            <option v-for="kelas in allKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                        </select>
                        <InputError :message="form.errors.id_kelas" class="mt-2" />
                    </div>
                </div>
            </fieldset>

            <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <legend class="text-lg font-medium text-gray-900 dark:text-white">2. Data Wali & Akun Login</legend>
                <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                     <div>
                        <InputLabel for="user_name" value="Nama Lengkap Wali" required/>
                        <TextInput id="user_name" v-model="form.user_name" @input="form.clearErrors('user_name')" type="text" class="mt-1 block w-full" placeholder="Cth: Ayah Budi" required />
                        <InputError :message="form.errors.user_name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="nomor_telepon_wali" value="Nomor WhatsApp Wali" required/>
                        <TextInput id="nomor_telepon_wali" v-model="form.nomor_telepon_wali" @input="form.clearErrors('nomor_telepon_wali')" type="tel" class="mt-1 block w-full" placeholder="Cth: 081234567890" required />
                        <InputError :message="form.errors.nomor_telepon_wali" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="email_wali" value="Alamat Email Wali (untuk login)" required/>
                        <TextInput id="email_wali" v-model="form.email_wali" @input="form.clearErrors('email_wali')" type="email" class="mt-1 block w-full" placeholder="Cth: wali.budi@email.com" required />
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
            </fieldset>
            
            <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <legend class="text-lg font-medium text-gray-900 dark:text-white">3. Persetujuan</legend>
                <div class="mt-6">
                    <div @click="handleTermsClick" class="flex items-center cursor-pointer group">
                        <div class="h-4 w-4 rounded border flex items-center justify-center transition-all" :class="form.terms ? 'bg-red-600 border-red-700' : 'bg-gray-200 dark:bg-gray-900 border-gray-300 dark:border-gray-600 group-hover:border-red-500'">
                            <svg v-if="form.terms" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </div>
                        <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">
                            Saya menyetujui <button type="button" class="font-medium text-red-600 hover:underline">syarat dan ketentuan</button> yang berlaku.
                        </span>
                    </div>
                    <InputError :message="form.errors.terms || form.errors.legal_document_id" class="mt-2" />
                </div>
            </fieldset>

            <div class="pt-5 flex justify-end">
                <PrimaryButton class="w-full justify-center text-lg bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="form.processing || !form.terms" :class="{ 'opacity-50 cursor-not-allowed': !form.terms }">
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan Data Siswa</span>
                </PrimaryButton>
            </div>
        </form>
    </FormPageLayout>

    <Modal :show="showTermsModal" @close="showTermsModal = false" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Syarat dan Ketentuan</h2>
            <div v-if="termsDocument" class="text-xs">{{ termsDocument.name }} (v{{ termsDocument.version }})</div>
            
            <div v-if="termsDocument" class="mt-4 prose prose-sm max-w-none text-gray-600 dark:text-gray-300 overflow-y-auto max-h-[60vh]" v-html="termsDocument.content">
            </div>
            <div v-else class="mt-4 text-red-500">Gagal memuat Syarat & Ketentuan. Silakan coba lagi nanti.</div>

            <div class="mt-6 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <SecondaryButton @click="showTermsModal = false">Tidak Setuju</SecondaryButton>
                <PrimaryButton @click="acceptTerms" class="bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="!termsDocument">Setuju</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>