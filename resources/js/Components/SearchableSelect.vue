<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        default: () => [] // expected format: { value: '', label: '' }
    },
    placeholder: {
        type: String,
        default: 'Pilih...'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);
const searchInputRef = ref(null);

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => opt.label.toLowerCase().includes(query));
});

const selectedOption = computed(() => {
    return props.options.find(opt => opt.value === props.modelValue);
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        setTimeout(() => {
            if (searchInputRef.value) {
                searchInputRef.value.focus();
            }
        }, 50);
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option.value);
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <!-- Select Trigger -->
        <div 
            @click="toggleDropdown"
            class="mt-1 flex w-full items-center justify-between border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm px-3 py-2 cursor-pointer focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500"
        >
            <span class="block truncate" :class="{ 'text-gray-500 dark:text-gray-400': !selectedOption }">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </div>

        <!-- Dropdown Menu -->
        <div 
            v-if="isOpen" 
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-gray-900/5 overflow-auto focus:outline-none sm:text-sm border border-gray-200 dark:border-gray-700"
        >
            <div class="sticky top-0 z-10 px-2 py-1.5 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <input 
                    type="text" 
                    ref="searchInputRef"
                    v-model="searchQuery"
                    class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    placeholder="Cari..."
                    @click.stop
                >
            </div>
            
            <ul class="pt-1">
                <li 
                    v-if="filteredOptions.length === 0" 
                    class="text-gray-500 dark:text-gray-400 cursor-default select-none relative py-2 pl-3 pr-9"
                >
                    Tidak ditemukan.
                </li>
                <li 
                    v-for="option in filteredOptions" 
                    :key="option.value"
                    @click.stop="selectOption(option)"
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white dark:text-gray-200 group"
                    :class="{ 'bg-indigo-50 text-indigo-900 dark:bg-indigo-900/30': option.value === modelValue }"
                >
                    <span class="block truncate" :class="{ 'font-semibold': option.value === modelValue, 'font-normal': option.value !== modelValue }">
                        {{ option.label }}
                    </span>
                    <span v-if="option.value === modelValue" class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 group-hover:text-white">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
