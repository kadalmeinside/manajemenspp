<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { ArrowPathIcon } from '@heroicons/vue/20/solid';

const page = usePage();

const props = defineProps({
    jobBatches: Object,
    pageTitle: String,
});

const user = computed(() => page.props.auth.user);

const jobBatchesData = computed(() => props.jobBatches || { data: [], links: [] });

const getStatusClass = (status) => {
    if (status === 'finished') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    if (status === 'processing') return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    if (status === 'failed') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return '-';
    return new Date(dateTimeString).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    if (window.Echo && user.value) {
        window.Echo.private(`App.Models.User.${user.value.id}`)
            .listen('.mass-invoice.status', (e) => {
                console.log('Job status event received on Jobs/Index page:', e);
                
                router.reload({ 
                    preserveScroll: true,
                    only: ['jobBatches'],
                });
            });
    }
});

</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ pageTitle }}</h2>
                <button @click="router.reload({ only: ['jobBatches'] })" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <ArrowPathIcon class="h-5 w-5 text-gray-500 dark:text-gray-400"/>
                </button>
            </div>
        </template>
        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                             <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Proses</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progress</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Output / Hasil</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dimulai Oleh</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu Selesai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!jobBatchesData.data || jobBatchesData.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada riwayat proses yang tercatat.</td>
                                </tr>
                                <tr v-for="batch in jobBatchesData.data" :key="batch.id">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ batch.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getStatusClass(batch.status)" class="px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                            <ArrowPathIcon v-if="batch.status === 'processing'" class="h-4 w-4 inline-block mr-1 animate-spin" />
                                            {{ batch.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mr-3">
                                                <div class="h-2.5 rounded-full" :class="{'bg-blue-600': batch.status === 'processing', 'bg-green-600': batch.status === 'finished', 'bg-red-600': batch.status === 'failed'}" :style="{ width: batch.progress + '%' }"></div>
                                            </div>
                                            <span>{{ batch.progress }}%</span>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ batch.processed_items }} / {{ batch.total_items }} item</span>
                                    </td>
                                    <td class="px-6 py-4 text-xs max-w-sm text-gray-500 dark:text-gray-400 break-words">{{ batch.output }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ batch.user_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(batch.finished_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Pagination -->
                     <div v-if="jobBatchesData.links && jobBatchesData.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap -mb-1 justify-center">
                            <template v-for="(link, key) in jobBatchesData.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600 select-none" v-html="link.label" />
                                <Link v-else
                                      class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300"
                                      :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600 dark:text-white dark:border-indigo-700': link.active }"
                                      :href="link.url"
                                      v-html="link.label"
                                      preserve-scroll />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

