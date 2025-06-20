<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    activities: Object,
    pageTitle: String,
});

const activitiesData = computed(() => props.activities.data || []);
const activitiesLinks = computed(() => props.activities.links || []);
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ pageTitle }}</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                             <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Objek</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pelaku</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="activitiesData.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada aktivitas yang tercatat.</td>
                                </tr>
                                <tr v-for="log in activitiesData" :key="log.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ log.description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded-md text-xs">{{ log.subject_type }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ log.causer_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ log.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Pagination -->
                    <div v-if="activitiesLinks.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap -mb-1 justify-center">
                            <template v-for="(link, key) in activitiesLinks" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                <Link v-else class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded hover:bg-white" :class="{ 'bg-indigo-500 text-white': link.active }" :href="link.url" v-html="link.label" preserve-scroll />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
