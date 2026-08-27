<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { TrashIcon, PlusIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const generateId = () => {
    return Date.now().toString(36) + Math.random().toString(36).substring(2);
};

const props = defineProps({
    product: {
        type: Object,
        default: null,
    }
});

const isEdit = !!props.product;

import { ref } from 'vue';

const form = useForm({
    name: props.product?.name || '',
    category: props.product?.category || 'merchandise',
    description: props.product?.description || '',
    is_active: props.product ? props.product.is_active : true,
    is_preorder: props.product ? props.product.is_preorder : false,
    images: [],
    variants: props.product?.variants?.length > 0 ? props.product.variants.map(v => ({...v, _ui_id: generateId()})) : [
        { _ui_id: generateId(), id: null, name: 'Default', sku: '', price: 0, stock: 0 }
    ],
});

const existingImages = ref(props.product?.images || []);
const previewUrls = ref([]);

const handleImageChange = (e) => {
    const newFiles = Array.from(e.target.files);
    if (newFiles.length === 0) return;
    
    form.images = [...form.images, ...newFiles];
    
    const newPreviewUrls = newFiles.map(file => URL.createObjectURL(file));
    previewUrls.value = [...previewUrls.value, ...newPreviewUrls];
    
    // Clear input to allow re-selecting same file
    e.target.value = null;
};

const removePreviewImage = (index) => {
    form.images.splice(index, 1);
    URL.revokeObjectURL(previewUrls.value[index]);
    previewUrls.value.splice(index, 1);
};

const deleteExistingImage = (imageId) => {
    if (confirm('Yakin ingin menghapus gambar ini?')) {
        router.delete(route('admin.products.images.destroy', { product: props.product.id, image: imageId }), {
            preserveScroll: true,
            onSuccess: () => {
                existingImages.value = existingImages.value.filter(img => img.id !== imageId);
            }
        });
    }
};

const addVariant = () => {
    form.variants.push({
        _ui_id: generateId(),
        id: null,
        name: '',
        sku: '',
        price: 0,
        stock: 0
    });
};

const removeVariant = (index) => {
    if (form.variants.length > 1) {
        form.variants.splice(index, 1);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        variants: JSON.stringify(data.variants),
        _method: isEdit ? 'PUT' : undefined
    })).post(
        isEdit ? route('admin.products.update', props.product.id) : route('admin.products.store'),
        {
            preserveScroll: true,
            forceFormData: true,
        }
    );
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Produk' : 'Tambah Produk'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.products.index')" class="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <ArrowLeftIcon class="h-6 w-6" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ isEdit ? 'Edit Produk: ' + product.name : 'Tambah Produk Baru' }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Informasi Dasar -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-2 dark:border-gray-700">Informasi Dasar</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Nama Produk *" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>
                                <div>
                                    <InputLabel for="category" value="Kategori *" />
                                    <select id="category" v-model="form.category" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        <option value="merchandise">Merchandise (Aksesoris)</option>
                                        <option value="jersey">Jersey (Set/Pakaian)</option>
                                        <option value="equipment">Peralatan Latihan</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.category" />
                                </div>
                            </div>
                            <div class="mt-4">
                                <InputLabel for="description" value="Deskripsi" />
                                <textarea id="description" v-model="form.description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>
                            <div class="mt-4">
                                <InputLabel value="Galeri Gambar Produk (Pilih Beberapa)" />
                                
                                <!-- Existing Images (if Edit Mode) -->
                                <div v-if="isEdit && existingImages.length > 0" class="mt-3 mb-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Gambar Saat Ini:</p>
                                    <div class="flex flex-wrap gap-4">
                                        <div v-for="img in existingImages" :key="img.id" class="relative group h-24 w-24">
                                            <img :src="img.url" alt="Product Image" class="h-24 w-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                            <div v-if="img.is_primary" class="absolute top-1 left-1 bg-indigo-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">Utama</div>
                                            <button type="button" @click="deleteExistingImage(img.id)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-md hover:bg-red-600">
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-4 items-start">
                                    <!-- New Images Previews -->
                                    <div v-for="(url, idx) in previewUrls" :key="'new-'+idx" class="relative group h-24 w-24">
                                        <img :src="url" alt="Preview Image" class="h-24 w-24 object-cover rounded-lg border border-indigo-200 dark:border-indigo-700 shadow-sm">
                                        <div v-if="idx === 0 && (!isEdit || existingImages.length === 0)" class="absolute top-1 left-1 bg-indigo-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">Utama</div>
                                        <button type="button" @click="removePreviewImage(idx)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-md hover:bg-red-600">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                    
                                    <!-- Add Button -->
                                    <label class="shrink-0 h-24 w-24 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg flex flex-col items-center justify-center border border-dashed border-gray-300 dark:border-gray-600 cursor-pointer transition-colors group">
                                        <PlusIcon class="w-6 h-6 text-gray-400 group-hover:text-indigo-500 mb-1" />
                                        <span class="text-[10px] text-gray-500 font-medium">Tambah Foto</span>
                                        <input type="file" multiple @change="handleImageChange" accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden" />
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Anda dapat memilih lebih dari satu file secara bertahap (Max 2MB per file).</p>
                                <InputError class="mt-2" :message="form.errors.image" />
                            </div>

                            <div class="mt-4 flex flex-col sm:flex-row gap-6">
                                <label class="flex items-center">
                                    <Checkbox name="is_active" v-model:checked="form.is_active" />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Produk Aktif (Tampil di Katalog Siswa)</span>
                                </label>
                                <label class="flex items-center">
                                    <Checkbox name="is_preorder" v-model:checked="form.is_preorder" />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400 font-semibold">Aktifkan Pre-Order (Abaikan Stok)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Manajemen Varian -->
                        <div class="pt-6">
                            <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Varian & Ukuran</h3>
                                <SecondaryButton type="button" @click="addVariant" size="sm" class="text-xs">
                                    <PlusIcon class="w-4 h-4 mr-1"/> Tambah Varian
                                </SecondaryButton>
                            </div>
                            
                            <div v-if="form.is_preorder" class="mb-4 p-3 bg-blue-50 text-blue-800 text-sm rounded border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                                ℹ️ Mode Pre-Order aktif. Kolom "Stok" akan diabaikan oleh sistem pemesanan.
                            </div>

                            <div class="space-y-4">
                                <div v-for="(variant, index) in form.variants" :key="variant._ui_id" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg relative bg-gray-50 dark:bg-gray-800/50">
                                    <div class="absolute top-2 right-2">
                                        <button v-if="form.variants.length > 1" type="button" @click="removeVariant(index)" class="text-red-500 hover:text-red-700 p-1" title="Hapus Varian">
                                            <TrashIcon class="w-5 h-5"/>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                        <div>
                                            <InputLabel :for="'v_name_'+index" value="Nama Varian/Ukuran *" />
                                            <TextInput :id="'v_name_'+index" type="text" class="mt-1 block w-full text-sm" v-model="variant.name" required placeholder="Ex: Size M" />
                                            <InputError class="mt-1" :message="form.errors[`variants.${index}.name`]" />
                                        </div>
                                        <div>
                                            <InputLabel :for="'v_sku_'+index" value="SKU (Opsional)" />
                                            <TextInput :id="'v_sku_'+index" type="text" class="mt-1 block w-full text-sm" v-model="variant.sku" placeholder="Ex: JRSY-M-01" />
                                        </div>
                                        <div>
                                            <InputLabel :for="'v_price_'+index" value="Harga (Rp) *" />
                                            <TextInput :id="'v_price_'+index" type="number" class="mt-1 block w-full text-sm" v-model="variant.price" required min="0" />
                                            <InputError class="mt-1" :message="form.errors[`variants.${index}.price`]" />
                                        </div>
                                        <div>
                                            <InputLabel :for="'v_stock_'+index" value="Stok Gudang *" />
                                            <TextInput :id="'v_stock_'+index" type="number" class="mt-1 block w-full text-sm" v-model="variant.stock" required min="0" :disabled="form.is_preorder" />
                                            <InputError class="mt-1" :message="form.errors[`variants.${index}.stock`]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.variants" />
                        </div>

                        <div class="flex items-center justify-end mt-8 border-t pt-4 dark:border-gray-700">
                            <Link :href="route('admin.products.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">
                                Batal
                            </Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Simpan Produk
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
