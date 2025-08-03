<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FormPageLayout from '@/Layouts/FormPageLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';

const props = defineProps({
    pageTitle: String,
    allKelas: Array, 
    termsDocument: Object,
    errors: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash);

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
    kode_promo: '',
});

// --- State untuk Harga & Promo ---
const biayaNormal = ref(0);
const biayaSetelahPromoOtomatis = ref(0);
const biayaSaatIni = ref(0);
const inputKodePromo = ref('');
const promoLoading = ref(false);
const promoMessage = ref({ type: '', text: '' });
const appliedPromoCodeData = ref(null);

// --- State untuk modal Syarat & Ketentuan ---
const showTermsModal = ref(false);

const selectedKelas = computed(() => {
    return props.allKelas.find(k => k.id_kelas === form.id_kelas);
});

watch(selectedKelas, (newKelas) => {
    if (newKelas) {
        biayaNormal.value = newKelas.biaya_pendaftaran_normal;
        biayaSetelahPromoOtomatis.value = newKelas.biaya_pendaftaran_saat_ini;
        biayaSaatIni.value = newKelas.biaya_pendaftaran_saat_ini;
        
        inputKodePromo.value = '';
        form.kode_promo = '';
        promoMessage.value = { type: '', text: '' };
        appliedPromoCodeData.value = null;
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const automaticDiscountAmount = computed(() => biayaNormal.value - biayaSetelahPromoOtomatis.value);
const promoCodeDiscountAmount = computed(() => {
    if (!appliedPromoCodeData.value) return 0;
    return biayaSetelahPromoOtomatis.value - biayaSaatIni.value;
});

const applyPromoCode = async () => {
    if (!inputKodePromo.value || !form.id_kelas) return;
    promoLoading.value = true;
    promoMessage.value = { type: '', text: '' };
    form.clearErrors('kode_promo');

    try {
        const response = await axios.post(route('promo.validate'), {
            id_kelas: form.id_kelas,
            kode_promo: inputKodePromo.value,
        });
        
        biayaSaatIni.value = response.data.new_price;
        form.kode_promo = inputKodePromo.value;
        appliedPromoCodeData.value = { name: `Diskon Kode '${inputKodePromo.value.toUpperCase()}'` };
        promoMessage.value = { type: 'success', text: response.data.message };
    } catch (error) {
        biayaSaatIni.value = biayaSetelahPromoOtomatis.value;
        form.kode_promo = '';
        appliedPromoCodeData.value = null;
        promoMessage.value = { type: 'error', text: error.response?.data?.message || 'Terjadi kesalahan.' };
    } finally {
        promoLoading.value = false;
    }
};

const removePromoCode = () => {
    biayaSaatIni.value = biayaSetelahPromoOtomatis.value;
    inputKodePromo.value = '';
    form.kode_promo = '';
    promoMessage.value = { type: '', text: '' };
    appliedPromoCodeData.value = null;
};

const handleTermsClick = (event) => {
    if (form.terms && event.target.tagName.toLowerCase() !== 'button') return;
    showTermsModal.value = true;
};

const acceptTerms = () => {
    form.terms = true;
    showTermsModal.value = false;
};

const submit = () => {
    form.post(route('pendaftaran.store'), {
        onError: () => form.reset('user_password', 'user_password_confirmation'),
    });
};
</script>

<template>
    <Head :title="pageTitle" />
    <FormPageLayout>
        <template #header>{{ pageTitle }}</template>

        <div v-if="form.processing" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 z-50 flex flex-col items-center justify-center">
            <svg class="animate-spin h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memproses Pendaftaran...</p>
        </div>

        <form v-if="!flash?.success" @submit.prevent="submit" novalidate class="space-y-8">
            <div v-if="errors.general" class="p-4 bg-red-100 text-red-700 rounded-md">{{ errors.general }}</div>
            
            <fieldset>
                <legend class="text-lg font-medium text-gray-900 dark:text-white">1. Data Diri Siswa</legend>
                <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                    <div class="sm:col-span-2">
                        <InputLabel for="nama_siswa" value="Nama Lengkap Siswa" required/>
                        <TextInput id="nama_siswa" v-model="form.nama_siswa" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.nama_siswa" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="tanggal_lahir" value="Tanggal Lahir" required/>
                        <TextInput id="tanggal_lahir" v-model="form.tanggal_lahir" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.tanggal_lahir" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="id_kelas" value="Pilih Cabang Soccer School" required/>
                        <select id="id_kelas" v-model="form.id_kelas" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 rounded-md shadow-sm" required>
                            <option value="" disabled>-- Pilih Cabang --</option>
                            <option v-for="kelas in allKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                        </select>
                        <InputError :message="form.errors.id_kelas" class="mt-2" />
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-lg font-medium text-gray-900 dark:text-white">2. Data Wali & Akun Login</legend>
                <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                     <div>
                        <InputLabel for="user_name" value="Nama Lengkap Wali" required/>
                        <TextInput id="user_name" v-model="form.user_name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.user_name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="nomor_telepon_wali" value="Nomor WhatsApp Wali" required/>
                        <TextInput id="nomor_telepon_wali" v-model="form.nomor_telepon_wali" type="tel" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.nomor_telepon_wali" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="email_wali" value="Alamat Email Wali (untuk login)" required/>
                        <TextInput id="email_wali" v-model="form.email_wali" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.email_wali" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="user_password" value="Buat Password" required/>
                        <TextInput id="user_password" v-model="form.user_password" type="password" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.user_password" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="user_password_confirmation" value="Konfirmasi Password" required/>
                        <TextInput id="user_password_confirmation" v-model="form.user_password_confirmation" type="password" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.user_password_confirmation" class="mt-2" />
                    </div>
                </div>
            </fieldset>
            
            <fieldset v-if="form.id_kelas">
                <legend class="text-lg font-medium text-gray-900 dark:text-white">3. Biaya Pendaftaran</legend>
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg space-y-3">
                    <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                        <span>Biaya Pendaftaran Normal</span>
                        <span :class="{ 'line-through text-gray-500': automaticDiscountAmount > 0 || promoCodeDiscountAmount > 0 }">{{ formatCurrency(biayaNormal) }}</span>
                    </div>
                    <div v-if="automaticDiscountAmount > 0" class="flex justify-between items-center text-green-600 dark:text-green-400">
                        <span>Diskon Pendaftaran</span>
                        <span>- {{ formatCurrency(automaticDiscountAmount) }}</span>
                    </div>
                    <div v-if="promoCodeDiscountAmount > 0" class="flex justify-between items-center text-green-600 dark:text-green-400">
                        <span>{{ appliedPromoCodeData.name }}</span>
                        <span>- {{ formatCurrency(promoCodeDiscountAmount) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xl font-bold text-gray-900 dark:text-white pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span>Total Biaya</span>
                        <span class="text-red-600">{{ formatCurrency(biayaSaatIni) }}</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <InputLabel for="kode_promo" value="Punya Kode Promo?" />
                        <div class="mt-1 flex gap-2">
                            <TextInput id="kode_promo" v-model="inputKodePromo" type="text" class="block w-full" placeholder="Masukkan kode di sini" :disabled="!!appliedPromoCodeData" />
                            <SecondaryButton v-if="!appliedPromoCodeData" @click="applyPromoCode" :disabled="promoLoading" type="button">
                                <span v-if="!promoLoading">Terapkan</span><span v-else>...</span>
                            </SecondaryButton>
                            <button v-else @click="removePromoCode" type="button" class="text-sm font-medium text-red-600 hover:text-red-500">Hapus</button>
                        </div>
                        <p v-if="promoMessage.text" class="mt-2 text-sm" :class="promoMessage.type === 'success' ? 'text-green-600' : 'text-red-600'">{{ promoMessage.text }}</p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-lg font-medium text-gray-900 dark:text-white">4. Persetujuan</legend>
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
                <PrimaryButton class="w-full justify-center text-lg" :disabled="form.processing || !form.terms" :class="{ 'opacity-50 cursor-not-allowed': !form.terms }">
                    <span v-if="form.processing">Menyimpan...</span><span v-else>Daftar & Lanjutkan Pembayaran</span>
                </PrimaryButton>
            </div>
        </form>
    </FormPageLayout>

    <Modal :show="showTermsModal" @close="showTermsModal = false" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Syarat dan Ketentuan</h2>
            <div v-if="termsDocument" class="text-xs">{{ termsDocument.name }} (v{{ termsDocument.version }})</div>
            <div v-if="termsDocument" class="mt-4 prose prose-sm max-w-none text-gray-600 dark:text-gray-300 overflow-y-auto max-h-[60vh]" v-html="termsDocument.content"></div>
            <div v-else class="mt-4 text-red-500">Gagal memuat Syarat & Ketentuan.</div>
            <div class="mt-6 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <SecondaryButton @click="showTermsModal = false">Tidak Setuju</SecondaryButton>
                <PrimaryButton @click="acceptTerms" class="bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="!termsDocument">Setuju</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
