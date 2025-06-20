<script setup>
import { ref } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    showText: {
        type: Boolean,
        default: true,
    }
});

const sliderContainer = ref(null);

const scrollSlider = (direction) => {
    if (sliderContainer.value) {
        const scrollAmount = sliderContainer.value.offsetWidth * 0.8;
        sliderContainer.value.scrollBy({
            left: direction === 'next' ? scrollAmount : -scrollAmount,
            behavior: 'smooth',
        });
    }
};
</script>

<template>
    <div class="relative">
        <div ref="sliderContainer" class="flex space-x-6 overflow-x-auto pb-4 slider-container scroll-smooth snap-x snap-mandatory">
            <div v-for="(item, index) in items" :key="index" class="flex-shrink-0 w-72 snap-start">
                <div class="group relative overflow-hidden rounded-lg shadow-lg">
                    <img :src="item.gambar" :alt="item.nama_kelas || 'Galeri'" class="h-96 w-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <div v-if="showText" class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                    <div v-if="showText" class="absolute bottom-0 left-0 p-4 text-white">
                        <h3 class="text-lg font-bold">{{ item.nama_kelas }}</h3>
                        <p class="mt-1 text-sm opacity-90">{{ item.deskripsi }}</p>
                    </div>
                    <div v-if="item.isNew" class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">BARU</div>
                </div>
            </div>
        </div>
         <div class="hidden sm:flex items-center justify-end space-x-3 absolute -top-16 right-0">
            <button @click="scrollSlider('prev')" class="p-2 bg-gray-200 dark:bg-zinc-800 rounded-full hover:bg-gray-300 dark:hover:bg-zinc-700 transition"><ChevronLeftIcon class="h-6 w-6 text-gray-700 dark:text-gray-200"/></button>
            <button @click="scrollSlider('next')" class="p-2 bg-gray-200 dark:bg-zinc-800 rounded-full hover:bg-gray-300 dark:hover:bg-zinc-700 transition"><ChevronRightIcon class="h-6 w-6 text-gray-700 dark:text-gray-200"/></button>
        </div>
    </div>
</template>

<style scoped>
.slider-container::-webkit-scrollbar { display: none; }
.slider-container { -ms-overflow-style: none; scrollbar-width: none; }
</style>
