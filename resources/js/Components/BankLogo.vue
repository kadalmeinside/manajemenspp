<script setup>
import { computed } from 'vue';

const props = defineProps({
    bank: {
        type: String,
        required: true
    },
    class: {
        type: String,
        default: 'w-16 h-8'
    }
});

const imageUrl = computed(() => {
    const code = props.bank.toUpperCase();
    
    // Map to specific file names to respect exact casing uploaded by user
    const fileMap = {
        'BCA': 'BCA.svg',
        'BNI': 'BNI.svg',
        'BRI': 'BRI.svg',
        'MANDIRI': 'MANDIRI.svg',
        'PERMATA': 'permata.svg',
        'QRIS': 'QRIS.svg'
    };
    
    if (fileMap[code]) {
        return `/images/banks/${fileMap[code]}`;
    }
    
    // Fallback if not found, though we should only get mapped ones
    return null;
});
</script>

<template>
    <img v-if="imageUrl" :src="imageUrl" :class="[props.class, 'object-contain']" :alt="props.bank" />
    <!-- Styled SVG Logo for CIMB Niaga -->
    <svg v-else-if="props.bank.toUpperCase() === 'CIMB'" :class="[props.class]" viewBox="0 0 110 30" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="0" y="3" width="24" height="24" rx="5" fill="#DC2626"/>
        <path d="M7 15L12 10L17 15L12 20L7 15Z" fill="white"/>
        <text x="30" y="21" fill="#DC2626" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-weight="900" font-size="18" letter-spacing="0.5">CIMB</text>
    </svg>
    <!-- Fallback if no logo available -->
    <div v-else :class="[props.class, 'flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded']">
        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ props.bank }}</span>
    </div>
</template>
