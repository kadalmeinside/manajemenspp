<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    settings: Object,
    legalDocuments: Array,
    pageTitle: String,
    can: Object,
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const registrationDocs = computed(() => props.legalDocuments.filter(doc => doc.type === 'terms_and_conditions'));
const resignationDocs = computed(() => props.legalDocuments.filter(doc => doc.type === 'resignation'));
const mutasiDocs = computed(() => props.legalDocuments.filter(doc => doc.type === 'mutasi'));

const form = useForm({
    app_name: props.settings.app_name || '',
    app_version: props.settings.app_version || '',
    app_build: props.settings.app_build || '',
    app_logo: null,
    app_logo_cek_spp: null,
    kop_surat_nama: props.settings.kop_surat_nama || '',
    kop_surat_alamat: props.settings.kop_surat_alamat || '',
    kop_surat_kontak: props.settings.kop_surat_kontak || '',
    legal_doc_registration_public: props.settings.legal_doc_registration_public || '',
    legal_doc_registration_academy: props.settings.legal_doc_registration_academy || '',
    legal_doc_registration_ss: props.settings.legal_doc_registration_ss || '',
    legal_doc_re_registration: props.settings.legal_doc_re_registration || '',
    legal_doc_resignation: props.settings.legal_doc_resignation || '',
    legal_doc_mutasi: props.settings.legal_doc_mutasi || '',
});

const logoPreview = ref(props.settings.app_logo ? `/storage/${props.settings.app_logo}` : null);
const logoCekSppPreview = ref(props.settings.app_logo_cek_spp ? `/storage/${props.settings.app_logo_cek_spp}` : null);

function onLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.app_logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function onLogoCekSppChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.app_logo_cek_spp = file;
    logoCekSppPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('admin.settings.update'), {
        forceFormData: true, // Penting untuk upload file
        onSuccess: () => {
            // Reset input file setelah sukses
            document.getElementById('app_logo_input').value = '';
            form.app_logo = null;
            document.getElementById('app_logo_cek_spp_input').value = '';
            form.app_logo_cek_spp = null;
        }
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="py-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div class="border-b pb-4 mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Umum</h3>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="app_name" value="Nama Aplikasi" />
                                    <TextInput
                                        id="app_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.app_name"
                                    />
                                    <InputError class="mt-2" :message="form.errors.app_name" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="app_version" value="Versi Aplikasi" />
                                        <TextInput
                                            id="app_version"
                                            type="text"
                                            class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                                            placeholder="Contoh: 1.0.0"
                                            v-model="form.app_version"
                                            disabled
                                        />
                                        <InputError class="mt-2" :message="form.errors.app_version" />
                                    </div>
                                    <div>
                                        <InputLabel for="app_build" value="Build / Environment" />
                                        <TextInput
                                            id="app_build"
                                            type="text"
                                            class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                                            placeholder="Contoh: Local / Production"
                                            v-model="form.app_build"
                                            disabled
                                        />
                                        <InputError class="mt-2" :message="form.errors.app_build" />
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 italic mt-1 mb-4">
                                    Catatan: Versi dan Build diisi secara otomatis dari sistem (composer.json dan Git commit hash).
                                </p>
                                
                                <div>
                                    <InputLabel for="app_logo" value="Logo Aplikasi (Panel Admin)" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Logo Preview" class="h-16 w-16 object-contain rounded-md bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-16 w-16 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-md text-gray-400">
                                            No Logo
                                        </div>
                                        <input id="app_logo_input" type="file" @input="onLogoChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_logo" />
                                </div>

                                <div>
                                    <InputLabel for="app_logo_cek_spp" value="Logo Cek SPP (Halaman Publik)" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="logoCekSppPreview" :src="logoCekSppPreview" alt="Logo Cek SPP Preview" class="h-16 w-16 object-contain rounded-md bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-16 w-16 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-md text-gray-400">
                                            No Logo
                                        </div>
                                        <input id="app_logo_cek_spp_input" type="file" @input="onLogoCekSppChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_logo_cek_spp" />
                                </div>
                            </div>
                        </div>

                        <div class="border-b pb-4 mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Kop Surat (Cetak PDF)</h3>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="kop_surat_nama" value="Nama Institusi / Sekolah (Misal: PERSIJA DEVELOPMENT)" />
                                    <TextInput
                                        id="kop_surat_nama"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.kop_surat_nama"
                                    />
                                    <InputError class="mt-2" :message="form.errors.kop_surat_nama" />
                                </div>
                                <div>
                                    <InputLabel for="kop_surat_alamat" value="Alamat Lengkap" />
                                    <TextInput
                                        id="kop_surat_alamat"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.kop_surat_alamat"
                                    />
                                    <InputError class="mt-2" :message="form.errors.kop_surat_alamat" />
                                </div>
                                <div>
                                    <InputLabel for="kop_surat_kontak" value="Kontak (Telepon / Email / Website)" />
                                    <TextInput
                                        id="kop_surat_kontak"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.kop_surat_kontak"
                                    />
                                    <InputError class="mt-2" :message="form.errors.kop_surat_kontak" />
                                </div>
                                <p class="text-sm text-gray-500 italic mt-2">
                                    Catatan: Logo pada kop surat akan secara otomatis menggunakan "Logo Aplikasi" yang Anda unggah di bagian Umum.
                                </p>
                            </div>
                        </div>

                        <div class="border-b pb-4 mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Dokumen Legal</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Pendaftaran Reguler / Umum" />
                                    <select v-model="form.legal_doc_registration_public" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Pilih Dokumen --</option>
                                        <option v-for="doc in registrationDocs" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Pendaftaran Academy" />
                                    <select v-model="form.legal_doc_registration_academy" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Pilih Dokumen --</option>
                                        <option v-for="doc in registrationDocs" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Pendaftaran Soccer School" />
                                    <select v-model="form.legal_doc_registration_ss" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Pilih Dokumen --</option>
                                        <option v-for="doc in registrationDocs" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Daftar Ulang" />
                                    <select v-model="form.legal_doc_re_registration" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Pilih Dokumen --</option>
                                        <option v-for="doc in registrationDocs" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Pengunduran Diri (Resign)" />
                                    <select v-model="form.legal_doc_resignation" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Pilih Dokumen --</option>
                                        <option v-for="doc in resignationDocs" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Pindah Cabang / Kelas" />
                                    <select v-model="form.legal_doc_mutasi" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Pilih Dokumen --</option>
                                        <option v-for="doc in mutasiDocs" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Tersimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
