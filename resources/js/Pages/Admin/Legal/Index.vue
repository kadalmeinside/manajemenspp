<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { PlusIcon, EyeIcon } from '@heroicons/vue/20/solid';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import axios from 'axios';

defineProps({
    documents: Object,
    pageTitle: String,
});

// State untuk modal view
const showViewModal = ref(false);
const isLoadingContent = ref(false);
const viewingDocument = ref(null);

const openViewModal = async (docId) => {
    viewingDocument.value = null; // Kosongkan konten lama
    isLoadingContent.value = true;
    showViewModal.value = true;

    try {
        // Panggil endpoint show untuk mengambil konten
        const response = await axios.get(route('admin.legal-documents.show', docId));
        viewingDocument.value = response.data;
    } catch (error) {
        console.error("Gagal memuat konten dokumen:", error);
        viewingDocument.value = { content: '<p class="text-red-500">Gagal memuat konten.</p>' };
    } finally {
        isLoadingContent.value = false;
    }
};

const closeViewModal = () => {
    showViewModal.value = false;
    viewingDocument.value = null; // Hapus konten dari memori saat modal ditutup
};
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Dok Legal</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />
        
        
        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            
                            <!-- <Link :href="route('admin.legal-documents.create')" v-if="can?.create_permission" > -->
                            <Link :href="route('admin.legal-documents.create')" >
                                <PrimaryButton><PlusIcon class="h-5 w-5 mr-2" />Tambah Dokumen</PrimaryButton>
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Dokumen</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Versi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Publikasi</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="documents.data.length === 0"><td colspan="5" class="text-center py-4">Tidak ada dokumen.</td></tr>
                                    <tr v-for="doc in documents.data" :key="doc.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ doc.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded-md text-xs">{{ doc.type }}</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doc.version }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doc.published_at ? new Date(doc.published_at).toLocaleDateString('id-ID') : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                            <button @click="openViewModal(doc.id)" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                                <EyeIcon class="h-4 w-4 mr-1"/> Lihat
                                            </button>
                                            <Link :href="route('admin.legal-documents.edit', doc.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <Modal :show="showViewModal" @close="closeViewModal" maxWidth="2xl">
            <div class="p-6">
                <div v-if="isLoadingContent" class="flex justify-center items-center h-64">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
                <div v-else-if="viewingDocument">
                     <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 border-b pb-3 dark:border-gray-700">
                        {{ viewingDocument.name }} (v{{ viewingDocument.version }})
                    </h2>
                    <div class="prose dark:prose-invert max-w-none" v-html="viewingDocument.content"></div>
                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeViewModal">Tutup</SecondaryButton>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style>
/* Styling untuk konten HTML di dalam modal */
.prose h3 { margin-top: 1.5em; margin-bottom: 0.5em; }
.prose ul > li { padding-left: 0; }
.prose ul > li > p { margin-top: 0.5em; margin-bottom: 0.5em; }
</style>
