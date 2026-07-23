<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Toast from '@/Components/Toast.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    leaves: Object,
    filters: Object,
    pageTitle: String,
    siswaOptions: Array,
    flash: Object,
});

const showAddModal = ref(false);
const currentYear = new Date().getFullYear();

const addForm = useForm({
    id_siswa: '',
    months: [],
    year: currentYear,
    reason: '',
});

const submitAdd = () => {
    addForm.post(route('admin.leaves.storeAdmin'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        },
    });
};

const flashMessage = computed(() => props.flash?.message);
const flashType = computed(() => props.flash?.type || 'info');

const approve = (id) => {
    if (confirm('Setujui pengajuan cuti ini? Jika invoice PENDING sudah ada, nominal akan diupdate menjadi 250.000.')) {
        router.post(route('admin.leaves.approve', id), {}, { preserveScroll: true });
    }
};

const reject = (id) => {
    if (confirm('Tolak pengajuan cuti ini?')) {
        router.post(route('admin.leaves.reject', id), {}, { preserveScroll: true });
    }
};

const cancel = (id) => {
    if (confirm('Batalkan cuti ini? Tagihan SPP akan dikembalikan ke nominal normal.')) {
        router.post(route('admin.leaves.cancel', id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg md:text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ pageTitle || 'Pengajuan Cuti Siswa' }}</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        
                        <!-- Filters & Actions -->
                        <div class="mb-6 flex flex-col sm:flex-row justify-between gap-4">
                            <div class="flex flex-wrap gap-2">
                                <Link :href="route('admin.leaves.index', { status: 'pending' })" class="px-4 py-1.5 text-sm font-medium rounded-full transition-colors" :class="filters.status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'">Pending</Link>
                                <Link :href="route('admin.leaves.index', { status: 'approved' })" class="px-4 py-1.5 text-sm font-medium rounded-full transition-colors" :class="filters.status === 'approved' ? 'bg-green-500 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100'">Disetujui</Link>
                                <Link :href="route('admin.leaves.index', { status: 'rejected' })" class="px-4 py-1.5 text-sm font-medium rounded-full transition-colors" :class="filters.status === 'rejected' ? 'bg-red-500 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'">Ditolak</Link>
                                <Link :href="route('admin.leaves.index', { status: 'cancelled' })" class="px-4 py-1.5 text-sm font-medium rounded-full transition-colors" :class="filters.status === 'cancelled' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">Dibatalkan</Link>
                                <Link :href="route('admin.leaves.index')" class="px-4 py-1.5 text-sm font-medium rounded-full transition-colors" :class="!filters.status ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'">Semua</Link>
                            </div>
                            <div>
                                <PrimaryButton @click="showAddModal = true">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Tambah Cuti
                                </PrimaryButton>
                            </div>
                        </div>

                        <div class="bg-transparent sm:bg-white sm:dark:bg-gray-800 sm:rounded-xl sm:border sm:border-gray-200 sm:dark:border-gray-700">
                            <!-- Desktop View -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa</th>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Periode</th>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alasan</th>
                                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Cuti</th>
                                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Tagihan</th>
                                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-if="leaves.data.length === 0"><td colspan="6" class="text-center py-8 text-gray-500">Tidak ada data pengajuan.</td></tr>
                                        <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ leave.siswa_nama }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ leave.kelas_nama }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                                                    {{ leave.month_year }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate" :title="leave.reason">{{ leave.reason }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span v-if="leave.status === 'pending'" class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800/50">Pending</span>
                                                <span v-else-if="leave.status === 'approved'" class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800/50">Disetujui</span>
                                                <span v-else-if="leave.status === 'rejected'" class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800/50">Ditolak</span>
                                                <span v-else class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">Dibatalkan</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span v-if="!leave.invoice_status" class="text-xs text-gray-400 italic">Tidak ada</span>
                                                <span v-else-if="['PAID','SETTLED'].includes(leave.invoice_status.toUpperCase())" class="px-2.5 py-1 inline-flex text-xs font-bold rounded bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">LUNAS</span>
                                                <span v-else class="px-2.5 py-1 inline-flex text-xs font-bold rounded bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400 border border-orange-200 dark:border-orange-800/50">{{ leave.invoice_status }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div v-if="leave.status === 'pending'" class="flex justify-end gap-2">
                                                    <button @click="reject(leave.id)" class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-100">Tolak</button>
                                                    <button @click="approve(leave.id)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm">Setujui</button>
                                                </div>
                                                <div v-else-if="leave.status === 'approved'" class="flex justify-end flex-col items-end gap-1">
                                                    <button 
                                                        @click="!['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase()) && cancel(leave.id)" 
                                                        :disabled="['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase())"
                                                        :class="['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase()) ? 'opacity-50 cursor-not-allowed text-gray-500 bg-gray-100' : 'text-orange-600 bg-orange-50 hover:bg-orange-100 border-orange-100'"
                                                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors border"
                                                        title="Batalkan Cuti">
                                                        Batalkan
                                                    </button>
                                                    <span class="text-[10px] text-gray-400 block" v-if="['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase())">Tagihan lunas</span>
                                                </div>
                                                <div v-else class="text-xs text-gray-400">
                                                    Oleh: {{ leave.approved_by || '-' }}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile View (Cards) -->
                            <div class="block md:hidden space-y-4">
                                <div v-if="leaves.data.length === 0" class="text-center py-8 text-gray-500 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                    Tidak ada data pengajuan.
                                </div>
                                <div v-else v-for="leave in leaves.data" :key="'mob-'+leave.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col gap-3">
                                    <div class="flex justify-between items-start border-b border-gray-100 dark:border-gray-700 pb-2">
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ leave.siswa_nama }}</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ leave.kelas_nama }}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                                            {{ leave.month_year }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium mb-1">Alasan Cuti:</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">{{ leave.reason }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 mt-1">
                                        <div>
                                            <p class="text-[10px] text-gray-400 mb-1">Status Cuti</p>
                                            <span v-if="leave.status === 'pending'" class="px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800/50">Pending</span>
                                            <span v-else-if="leave.status === 'approved'" class="px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800/50">Disetujui</span>
                                            <span v-else-if="leave.status === 'rejected'" class="px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800/50">Ditolak</span>
                                            <span v-else class="px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">Dibatalkan</span>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 mb-1">Status Tagihan</p>
                                            <span v-if="!leave.invoice_status" class="text-[10px] text-gray-400 italic">Tidak ada</span>
                                            <span v-else-if="['PAID','SETTLED'].includes(leave.invoice_status.toUpperCase())" class="px-2.5 py-0.5 inline-flex text-[10px] font-bold rounded bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">LUNAS</span>
                                            <span v-else class="px-2.5 py-0.5 inline-flex text-[10px] font-bold rounded bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400 border border-orange-200 dark:border-orange-800/50">{{ leave.invoice_status }}</span>
                                        </div>
                                    </div>
                                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2 mt-1">
                                        <template v-if="leave.status === 'pending'">
                                            <button @click="reject(leave.id)" class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-100 flex-1">Tolak</button>
                                            <button @click="approve(leave.id)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm flex-1">Setujui</button>
                                        </template>
                                        <template v-else-if="leave.status === 'approved'">
                                            <div class="flex items-center gap-3 w-full justify-end">
                                                <span class="text-[10px] text-gray-400" v-if="['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase())">Tagihan lunas</span>
                                                <button 
                                                    @click="!['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase()) && cancel(leave.id)" 
                                                    :disabled="['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase())"
                                                    :class="['PAID','SETTLED'].includes((leave.invoice_status || '').toUpperCase()) ? 'opacity-50 cursor-not-allowed text-gray-500 bg-gray-100' : 'text-orange-600 bg-orange-50 hover:bg-orange-100 border-orange-100'"
                                                    class="px-4 py-1.5 text-xs font-bold rounded-lg transition-colors border">
                                                    Batalkan
                                                </button>
                                            </div>
                                        </template>
                                        <div v-else class="text-xs text-gray-400 w-full text-right">
                                            Oleh: {{ leave.approved_by || '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                         <div class="mt-4" v-if="leaves.links && leaves.links.length > 3">
                            <!-- Simple Previous/Next or full pagination component if available -->
                        </div>

                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Modal -->
        <Modal :show="showAddModal" @close="showAddModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tambah Cuti Baru</h2>
                <form @submit.prevent="submitAdd">
                    <div class="mb-4">
                        <InputLabel for="id_siswa" value="Siswa" />
                        <SearchableSelect
                            v-model="addForm.id_siswa"
                            :options="siswaOptions.map(s => ({ value: s.id_siswa, label: s.nama_siswa + ' (' + s.nis + ')' }))"
                            placeholder="Ketik untuk mencari Siswa..."
                        />
                        <InputError :message="addForm.errors.id_siswa" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Bulan (Bisa pilih lebih dari satu)" />
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
                            <label v-for="m in 12" :key="'m'+m" class="inline-flex items-center">
                                <input
                                    type="checkbox"
                                    :value="m"
                                    v-model="addForm.months"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                >
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ new Date(2000, m - 1, 1).toLocaleString('id-ID', { month: 'long' }) }}
                                </span>
                            </label>
                        </div>
                        <InputError :message="addForm.errors.months" class="mt-2" />
                        <InputError v-if="addForm.errors['months.0']" :message="addForm.errors['months.0']" class="mt-2" />
                        <InputError v-if="addForm.errors.error" :message="addForm.errors.error" class="mt-2 text-red-600" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="year" value="Tahun" />
                        <TextInput
                            id="year"
                            type="number"
                            v-model="addForm.year"
                            class="mt-1 block w-full"
                            required
                            :min="currentYear"
                        />
                        <InputError :message="addForm.errors.year" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="reason" value="Alasan" />
                        <textarea
                            id="reason"
                            v-model="addForm.reason"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            rows="3"
                            required
                        ></textarea>
                        <InputError :message="addForm.errors.reason" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="showAddModal = false"> Batal </SecondaryButton>
                        <PrimaryButton
                            class="ml-3"
                            :class="{ 'opacity-25': addForm.processing }"
                            :disabled="addForm.processing"
                        >
                            Simpan & Setujui
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
