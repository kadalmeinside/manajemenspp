<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    image: null,
    variants: props.product?.variants?.length > 0 ? props.product.variants.map(v => ({...v, _ui_id: generateId()})) : [
        { _ui_id: generateId(), id: null, name: 'Default', sku: '', price: 0, stock: 0 }
    ],
});

const previewUrl = ref(props.product?.image_url || null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    form.image = file;
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = props.product?.image_url || null;
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
                                <InputLabel value="Gambar Produk" />
                                <div class="mt-2 flex items-center space-x-6">
                                    <div class="shrink-0">
                                        <img v-if="previewUrl" :src="previewUrl" alt="Product Image" class="h-24 w-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div v-else class="h-24 w-24 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700">
                                            <span class="text-xs text-gray-400">Tidak ada gambar</span>
                                        </div>
                                    </div>
                                    <label class="block">
                                        <span class="sr-only">Pilih gambar produk</span>
                                        <input type="file" @change="handleImageChange" accept="image/png, image/jpeg, image/jpg, image/webp" class="block w-full text-sm text-gray-500 dark:text-gray-400
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-indigo-50 file:text-indigo-700
                                            hover:file:bg-indigo-100
                                            dark:file:bg-gray-700 dark:file:text-gray-300
                                        "/>
                                    </label>
                                </div>
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
