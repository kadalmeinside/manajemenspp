<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { CameraIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    currentPhotoUrl: {
        type: String,
        default: null,
    },
    initials: {
        type: String,
        default: '?',
    },
    uploadUrl: {
        type: String,
        required: true,
    }
});

const fileInput = ref(null);
const imageSrc = ref(null);
const cropperImage = ref(null);
const cropper = ref(null);
const showModal = ref(false);
const isUploading = ref(false);

const form = useForm({
    foto: null,
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (event) => {
            imageSrc.value = event.target.result;
            showModal.value = true;
            nextTick(() => {
                initializeCropper();
            });
        };
        reader.readAsDataURL(file);
    }
    // Reset file input so the same file can be selected again
    e.target.value = '';
};

const initializeCropper = () => {
    if (cropper.value) {
        cropper.value.destroy();
    }
    
    if (cropperImage.value) {
        cropper.value = new Cropper(cropperImage.value, {
            aspectRatio: 1, // 1:1 aspect ratio for profile picture
            viewMode: 1, // Restrict the crop box to not exceed the size of the canvas
            autoCropArea: 1,
            background: false,
            responsive: true,
        });
    }
};

const cancelCrop = () => {
    showModal.value = false;
    imageSrc.value = null;
    if (cropper.value) {
        cropper.value.destroy();
        cropper.value = null;
    }
};

const saveCrop = () => {
    if (!cropper.value) return;
    
    isUploading.value = true;
    
    // Get cropped canvas and compress to JPEG
    const canvas = cropper.value.getCroppedCanvas({
        width: 600,
        height: 600,
        fillColor: '#fff',
    });
    
    canvas.toBlob((blob) => {
        if (!blob) {
            alert('Gagal memproses gambar.');
            isUploading.value = false;
            return;
        }
        
        const file = new File([blob], 'profile.jpg', { type: 'image/jpeg' });
        form.foto = file;
        
        form.post(props.uploadUrl, {
            preserveScroll: true,
            onSuccess: () => {
                cancelCrop();
                isUploading.value = false;
            },
            onError: () => {
                isUploading.value = false;
                alert('Gagal mengunggah foto profil.');
            }
        });
        
    }, 'image/jpeg', 0.85); // 85% quality
};

onUnmounted(() => {
    if (cropper.value) {
        cropper.value.destroy();
    }
});
</script>

<template>
    <div class="relative group cursor-pointer inline-block" @click="triggerFileInput">
        <input 
            type="file" 
            ref="fileInput" 
            class="hidden" 
            accept="image/jpeg, image/png, image/webp" 
            @change="onFileChange"
        />
        
        <!-- Display Photo -->
        <div class="h-20 w-20 md:h-24 md:w-24 rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center border-4 border-white dark:border-gray-800 shadow-lg overflow-hidden relative transition-transform group-hover:scale-105">
            <img v-if="currentPhotoUrl" :src="currentPhotoUrl" alt="Foto Profil" class="w-full h-full object-cover" />
            <span v-else class="text-3xl font-black text-white">{{ initials }}</span>
            
            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <CameraIcon class="w-6 h-6 text-white" />
            </div>
        </div>

        <!-- Cropper Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm" @click.stop>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
                <div class="px-5 py-4 flex justify-between items-center border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white">Sesuaikan Foto Profil</h3>
                    <button @click="cancelCrop" class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-full hover:bg-red-50 dark:hover:bg-red-900/30">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="p-4 bg-gray-50 dark:bg-gray-900 flex-1 relative min-h-[300px] max-h-[60vh]">
                    <img ref="cropperImage" :src="imageSrc" class="max-w-full block" />
                </div>
                
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3 bg-white dark:bg-gray-800">
                    <button @click="cancelCrop" :disabled="isUploading" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
                        Batal
                    </button>
                    <button @click="saveCrop" :disabled="isUploading" class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 shadow-sm shadow-red-500/30 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all disabled:opacity-50 flex items-center">
                        <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isUploading ? 'Menyimpan...' : 'Simpan Foto' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
