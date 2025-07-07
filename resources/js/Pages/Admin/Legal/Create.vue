<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    document: { type: Object, default: () => ({}) },
    pageTitle: String,
});

const isEditMode = computed(() => !!props.document.id);

const form = useForm({
    name: props.document.name || '',
    type: props.document.type || 'terms_and_conditions',
    version: props.document.version || '',
    content: props.document.content || '',
    published_at: props.document.published_at ? props.document.published_at.slice(0, 10) : null,
});

// Ganti 'no-api-key' dengan API key gratis Anda dari tiny.cloud
const tinyMceApiKey = 'b1dftwwgalb2h6jhbgab8ii48rz5l9uwvcmwkr8l0zl1k603';

const initializeEditor = () => {
    if (window.tinymce) {
        tinymce.init({
            selector: 'textarea#content',
            plugins: 'lists link image table code help wordcount autoresize',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code',
            skin: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'oxide-dark' : 'oxide',
            content_css: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'default',
            height: 500,
            setup: (editor) => {
                editor.on('init', () => {
                    editor.setContent(form.content);
                });
                editor.on('change keyup', () => {
                    form.content = editor.getContent();
                });
            },
        });
    }
};

// PERBAIKAN: Fungsi untuk memuat script TinyMCE secara dinamis
const loadTinyMceScript = () => {
    return new Promise((resolve, reject) => {
        const scriptId = 'tinymce-script';
        if (document.getElementById(scriptId) || window.tinymce) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.id = scriptId;
        script.src = `https://cdn.tiny.cloud/1/${tinyMceApiKey}/tinymce/7/tinymce.min.js`;
        script.referrerpolicy = 'origin';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load TinyMCE script.'));
        document.head.appendChild(script);
    });
};


onMounted(async () => {
    try {
        await loadTinyMceScript(); // Tunggu script selesai dimuat
        initializeEditor();      // Baru inisialisasi editor
    } catch (error) {
        console.error(error);
    }
});

onUnmounted(() => {
    if (window.tinymce && tinymce.get('content')) {
        tinymce.get('content').destroy();
    }
});


const submit = () => {
    if (isEditMode.value) {
        form.put(route('admin.legal-documents.update', props.document.id));
    } else {
        form.post(route('admin.legal-documents.store'));
    }
};
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ pageTitle }}</h2>
        </template>
         <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Nama Unik Dokumen" required/>
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" placeholder="cth: pendaftaran-soccer-school"/>
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="type" value="Tipe Dokumen" required/>
                                <select id="type" v-model="form.type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                    <option value="terms_and_conditions">Syarat & Ketentuan</option>
                                    <option value="privacy_policy">Kebijakan Privasi</option>
                                    <option value="refund_policy">Kebijakan Pengembalian</option>
                                </select>
                                <InputError :message="form.errors.type" class="mt-2" />
                            </div>
                             <div>
                                <InputLabel for="version" value="Versi" required/>
                                <TextInput id="version" v-model="form.version" class="mt-1 block w-full" placeholder="cth: 1.0"/>
                                <InputError :message="form.errors.version" class="mt-2" />
                            </div>
                             <div>
                                <InputLabel for="published_at" value="Tanggal Publikasi (Opsional)"/>
                                <TextInput id="published_at" v-model="form.published_at" type="date" class="mt-1 block w-full"/>
                                <InputError :message="form.errors.published_at" class="mt-2" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="content" value="Isi Dokumen" required/>
                            <textarea id="content" v-model="form.content" class="hidden"></textarea>
                            <InputError :message="form.errors.content" class="mt-2" />
                        </div>
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                            <Link :href="route('admin.legal-documents.index')" class="text-sm text-gray-600 hover:underline dark:text-gray-400">Batal</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
