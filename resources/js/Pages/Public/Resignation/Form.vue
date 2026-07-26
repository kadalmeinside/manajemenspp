<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';

const hasScrolledToBottom = ref(false);
const documentContainer = ref(null);

const handleScroll = () => {
    if (!documentContainer.value) return;
    const { scrollTop, scrollHeight, clientHeight } = documentContainer.value;
    // Memberikan toleransi 5px
    if (scrollTop + clientHeight >= scrollHeight - 5) {
        hasScrolledToBottom.value = true;
    }
};

onMounted(() => {
    nextTick(() => {
        if (documentContainer.value) {
            // Jika dokumen pendek dan tidak perlu scroll, langsung set true
            const { scrollHeight, clientHeight } = documentContainer.value;
            if (scrollHeight <= clientHeight + 5) {
                hasScrolledToBottom.value = true;
            }
        } else {
            // Jika tidak ada dokumen (fallback)
            hasScrolledToBottom.value = true;
        }
    });
});

const props = defineProps({
    siswa: Object,
    legalDocument: Object,
    submitUrl: String,
});

const form = useForm({
    parent_name: props.siswa.user?.name || '', // Pre-filled with actual parent's name
    student_name: props.siswa.nama_siswa || '', // Pre-filled
    reason: '',
    agreement_accepted: false,
    legal_document_id: props.legalDocument?.id || '',
});

const submit = () => {
    form.post(props.submitUrl);
};
</script>

<template>
    <Head title="Form Pengunduran Diri" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="px-6 py-8 border-b border-gray-200 dark:border-gray-700 bg-red-50/50 dark:bg-red-900/10 text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">
                        Formulir Pengunduran Diri Siswa
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Harap baca dokumen secara saksama sebelum menyetujui.
                    </p>
                </div>

                <div class="p-6 sm:p-8 space-y-8">
                    <!-- Info Siswa (View Only) -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 dark:border-gray-700">Informasi Siswa</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ siswa.nama_siswa }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">NIS</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ siswa.nis || 'Belum Ada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Kelas Saat Ini</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ siswa.kelas?.nama_kelas || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Nama Wali</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ siswa.user?.name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Email Wali</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ siswa.email_wali || '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Dokumen Legal -->
                    <div v-if="legalDocument" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ legalDocument.name }}</h3>
                            <span class="text-xs text-gray-500">Versi {{ legalDocument.version }}</span>
                        </div>
                        <div 
                            ref="documentContainer" 
                            @scroll="handleScroll" 
                            class="p-5 max-h-64 overflow-y-auto prose prose-sm dark:prose-invert prose-indigo relative shadow-inner"
                        >
                            <!-- Render HTML konten -->
                            <div v-html="legalDocument.content" class="text-sm text-gray-700 dark:text-gray-300"></div>
                        </div>
                        <div v-if="!hasScrolledToBottom" class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-4 py-2 text-xs text-center border-t border-indigo-100 dark:border-indigo-800 font-medium">
                            👇 Silakan scroll dokumen ke paling bawah untuk melanjutkan
                        </div>
                    </div>
                    <div v-else class="rounded-md bg-yellow-50 p-4 border border-yellow-200">
                        <p class="text-sm text-yellow-700">Peringatan: Dokumen legal untuk pengunduran diri belum diatur oleh admin.</p>
                    </div>

                    <!-- Form Pengisian -->
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Pengunduran Diri <span class="text-red-500">*</span></label>
                            <textarea id="reason" v-model="form.reason" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Sebutkan alasan detail mengapa siswa mengundurkan diri" required></textarea>
                            <div v-if="form.errors.reason" class="mt-1 text-sm text-red-600">{{ form.errors.reason }}</div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Tanda Tangan Elektronik</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Sebagai bentuk persetujuan, silakan konfirmasi atau ketik ulang nama Anda sebagai Wali dan nama Siswa di bawah ini.
                            </p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="parent_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Wali (Pihak Penyetuju) <span class="text-red-500">*</span></label>
                                    <input type="text" id="parent_name" v-model="form.parent_name" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                    <div v-if="form.errors.parent_name" class="mt-1 text-sm text-red-600">{{ form.errors.parent_name }}</div>
                                </div>
                                <div>
                                    <label for="student_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Siswa <span class="text-red-500">*</span></label>
                                    <input type="text" id="student_name" v-model="form.student_name" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                    <div v-if="form.errors.student_name" class="mt-1 text-sm text-red-600">{{ form.errors.student_name }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700" :class="{ 'opacity-50 cursor-not-allowed': !hasScrolledToBottom }">
                            <div class="flex items-center h-5">
                                <input id="agreement" type="checkbox" v-model="form.agreement_accepted" :disabled="!hasScrolledToBottom" class="focus:ring-indigo-500 h-5 w-5 text-indigo-600 border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed" required>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="agreement" class="font-medium text-gray-700 dark:text-gray-300" :class="{ 'cursor-not-allowed': !hasScrolledToBottom }">Saya Menyetujui</label>
                                <p class="text-gray-500 dark:text-gray-400">Dengan mencentang kotak ini, saya sebagai orang tua/wali siswa secara sadar menyetujui seluruh ketentuan pengunduran diri yang tertera pada dokumen legal di atas.</p>
                            </div>
                        </div>
                        <div v-if="!hasScrolledToBottom && legalDocument" class="text-sm text-yellow-600 dark:text-yellow-500">Anda harus membaca/menggulir dokumen legal hingga selesai sebelum bisa menyetujui.</div>
                        <div v-if="form.errors.agreement_accepted" class="text-sm text-red-600">{{ form.errors.agreement_accepted }}</div>
                        <div v-if="form.errors.legal_document_id" class="text-sm text-red-600">{{ form.errors.legal_document_id }}</div>

                        <div class="pt-4 flex justify-end">
                            <button type="submit" :disabled="form.processing || !legalDocument || !hasScrolledToBottom || !form.agreement_accepted" class="w-full sm:w-auto inline-flex justify-center items-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-xl text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Proses Pengunduran Diri
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
