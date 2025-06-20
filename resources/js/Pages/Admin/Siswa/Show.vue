<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ArrowLeftIcon, BanknotesIcon, EyeIcon } from '@heroicons/vue/20/solid';
import { debounce } from 'lodash';

const props = defineProps({
    siswa: Object,
    invoices: Array,
    pageTitle: String,
    filters: Object,
    availableYears: Array,
});

const selectedTahun = ref(props.filters.tahun);

watch(selectedTahun, debounce((value) => {
    router.get(route('admin.siswa.show', props.siswa.id_siswa), {
        tahun: value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['invoices', 'filters'],
    });
}, 300));


const getStatusClass = (status) => {
    if (status === 'PAID') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.siswa.index')" class="text-gray-400 hover:text-gray-600">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ pageTitle }}: {{ siswa.nama_siswa }}
                </h2>
            </div>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto space-y-8">
                
                <!-- Kartu Biodata Siswa -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Biodata Siswa</h3>
                    </div>
                    <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.nama_siswa }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Kelas</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.kelas_nama }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm"><span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="getStatusClass(siswa.status_siswa === 'Aktif' ? 'PAID' : 'FAILED')">{{ siswa.status_siswa }}</span></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.tanggal_lahir_formatted }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Bergabung</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.tanggal_bergabung_formatted }}</dd>
                        </div>
                         <div>
                            <dt class="text-sm font-medium text-gray-500">Email Wali</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.email_wali }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Riwayat Tagihan & Pembayaran -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                     <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Riwayat Invoice</h3>
                        <div class="w-full sm:w-auto max-w-xs">
                            <select v-model="selectedTahun" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                                <option v-for="year in availableYears" :key="year" :value="year"> {{ year }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Bayar</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="invoices.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Tidak ada riwayat tagihan untuk tahun {{ selectedTahun }}.</td>
                                </tr>
                                <tr v-else v-for="invoice in invoices" :key="invoice.id">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ invoice.description }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.total_amount_formatted }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.due_date_formatted }}</td>
                                    <td class="px-6 py-4 text-sm"><span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="getStatusClass(invoice.status)">{{ invoice.status }}</span></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ invoice.paid_at_formatted }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a v-if="invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-indigo-600 hover:text-indigo-900">Lihat</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
