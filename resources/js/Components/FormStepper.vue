<script setup>
import { computed } from 'vue';

const props = defineProps({
    steps: {
        type: Array,
        required: true
    },
    currentStep: {
        type: Number,
        required: true
    }
});

const progressWidth = computed(() => {
    return ((props.currentStep - 1) / (props.steps.length - 1)) * 100;
});
</script>

<template>
    <div class="mb-12 mt-4 px-4 sm:px-8">
        <div class="relative flex justify-between items-center">
            <!-- Background Line -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200 dark:bg-gray-700 z-0 rounded"></div>
            <!-- Progress Line -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-red-600 z-0 rounded transition-all duration-500 ease-in-out" :style="{ width: progressWidth + '%' }"></div>
            
            <div v-for="step in steps" :key="step.number" class="relative z-10 flex flex-col items-center">
                <!-- Icon/Circle -->
                <div 
                    class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all duration-300 shadow-sm"
                    :class="[
                        currentStep > step.number ? 'bg-red-600 border-red-600 text-white' :
                        currentStep === step.number ? 'bg-white dark:bg-gray-900 border-red-600 text-red-600 scale-110' : 
                        'bg-white dark:bg-gray-800 border-gray-300 text-gray-400 dark:border-gray-600'
                    ]"
                >
                    <svg v-if="currentStep > step.number" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span v-else class="text-base">{{ step.number }}</span>
                </div>
                
                <!-- Text -->
                <div class="absolute top-12 w-32 text-center text-xs sm:text-sm font-semibold transition-colors duration-300"
                    :class="currentStep >= step.number ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
                >
                    {{ step.title }}
                </div>
            </div>
        </div>
    </div>
</template>
