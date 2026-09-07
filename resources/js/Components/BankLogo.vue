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
    <!-- Fallback if no logo available -->
    <div v-else :class="[props.class, 'flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded']">
        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ props.bank }}</span>
    </div>
</template>
