<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ArrowLeftIcon, PencilIcon, LinkIcon, BanknotesIcon, UserIcon, BookOpenIcon, CheckBadgeIcon, ExclamationTriangleIcon, DocumentArrowDownIcon, CalendarDaysIcon, CurrencyDollarIcon, EnvelopeIcon, PhoneIcon, ChevronDownIcon, CalendarIcon, PlusIcon, ChatBubbleLeftEllipsisIcon, ArrowDownTrayIcon, ShareIcon, QrCodeIcon, ArrowRightOnRectangleIcon, ClipboardDocumentIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { debounce } from 'lodash';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Toast from '@/Components/Toast.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import html2pdf from 'html2pdf.js';
const page = usePage();

const props = defineProps({
    siswa: Object,
    pendingInvoices: { type: Array, default: () => [] },
    paidInvoices: { type: Array, default: () => [] },
    expiredInvoices: { type: Array, default: () => [] },
    pageTitle: String,
    filters: Object,
    availableYears: { type: Array, default: () => [] },
    allKelas: { type: Array, default: () => [] },
    statusSiswaOptions: { type: Array, default: () => [] },
    resignationUrl: String,
    legalAgreements: { type: Array, default: () => [] },
    mutasiSiswas: { type: Array, default: () => [] },
});

const downloadIdCard = async () => {
    const element = document.getElementById('student-id-card');
    const width  = element.offsetWidth;
    const height = element.offsetHeight;

    // Pre-convert SVG ball ornaments ke base64 img agar html2canvas bisa merendernya
    const ballSvgs = Array.from(element.querySelectorAll('svg[data-ball]'));
    const replacements = [];

    for (const svg of ballSvgs) {
        const svgStr = new XMLSerializer().serializeToString(svg);
        const b64 = btoa(unescape(encodeURIComponent(svgStr)));
        const img = document.createElement('img');
        img.src = `data:image/svg+xml;base64,${b64}`;
        img.style.cssText = svg.style.cssText;
        // Tunggu image selesai load sebelum capture
        await new Promise(r => { img.onload = r; img.onerror = r; if (img.complete) r(); });
        svg.parentNode.insertBefore(img, svg);
        svg.style.display = 'none';
        replacements.push({ svg, img });
    }

    const opt = {
        margin:      0,
        filename:    `ID_Card_${props.siswa.nama_siswa.replace(/\s+/g, '_')}.pdf`,
        image:       { type: 'jpeg', quality: 1.0 },
        html2canvas: { scale: 3, useCORS: true, allowTaint: true, logging: false },
        jsPDF:       { unit: 'px', format: [width, height], orientation: width > height ? 'landscape' : 'portrait' }
    };

    await html2pdf().set(opt).from(element).save();

    // Restore SVG elemen setelah PDF selesai
    for (const { svg, img } of replacements) {
        svg.style.display = '';
        img.remove();
    }
};

// --- State untuk Notifikasi Flash ---
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// --- State untuk Filter & Tab ---
const selectedTahun = ref(props.filters.tahun);
const activeTab = ref('pending');

// --- State & Logika untuk Modal Edit ---
const isEditPribadiOpen = ref(false);
const isEditWaliOpen = ref(false);
const isEditAkademikOpen = ref(false);
const isEditKeuanganOpen = ref(false);
const isPreviewIdCardOpen = ref(false);
const form = useForm({
    nama_siswa: '',
    user_name: '',
    email_wali: '',
    nomor_telepon_wali: '',
    user_password: '',
    user_password_confirmation: '',
    id_kelas: '',
    status_siswa: '',
    tanggal_lahir: '',
    tanggal_bergabung: '',
    jumlah_spp_custom: null,
    admin_fee_custom: null,
});

// ### FUNGSI YANG DIPERBARUI & DISEDERHANAKAN ###
const createCurrencyFormatter = (formField) => {
    return computed({
        get() {
            const numberValue = parseFloat(form[formField]);
            if (isNaN(numberValue)) return '';
            return new Intl.NumberFormat('id-ID').format(numberValue);
        },
        set(newValue) {
            // Hanya bersihkan input dan simpan sebagai angka
            const numericString = newValue.replace(/[^0-9]/g, '');
            form[formField] = numericString ? parseInt(numericString, 10) : null;
        }
    });
};

const sppCustomFormatted = createCurrencyFormatter('jumlah_spp_custom');
const adminFeeCustomFormatted = createCurrencyFormatter('admin_fee_custom');

const prepForm = () => {
    form.nama_siswa = props.siswa.nama_siswa;
    form.user_name = props.siswa.user_name;
    form.email_wali = props.siswa.email_wali;
    form.nomor_telepon_wali = props.siswa.nomor_telepon_wali;
    form.id_kelas = props.siswa.id_kelas;
    form.status_siswa = props.siswa.status_siswa;
    form.tanggal_lahir = props.siswa.tanggal_lahir;
    form.tanggal_bergabung = props.siswa.tanggal_bergabung;
    form.jumlah_spp_custom = parseFloat(props.siswa.jumlah_spp_custom) || null;
    form.admin_fee_custom = parseFloat(props.siswa.admin_fee_custom) || null;
};

const openEditPribadi = () => { prepForm(); isEditPribadiOpen.value = true; };
const openEditWali = () => { prepForm(); isEditWaliOpen.value = true; };
const openEditAkademik = () => { prepForm(); isEditAkademikOpen.value = true; };
const openEditKeuangan = () => { prepForm(); isEditKeuanganOpen.value = true; };

const closeModal = () => {
    isEditPribadiOpen.value = false;
    isEditWaliOpen.value = false;
    isEditAkademikOpen.value = false;
    isEditKeuanganOpen.value = false;
    isPreviewIdCardOpen.value = false;
    isMutasiModalOpen.value = false;
    isCopyModalOpen.value = false;
    form.reset();
    form.clearErrors();
    mutasiForm.reset();
    mutasiForm.clearErrors();
};

const hasPendingMutasi = computed(() => {
    return props.mutasiSiswas.some(m => m.status === 'PENDING' && !m.is_expired);
});

const isMutasiModalOpen = ref(false);
const mutasiForm = useForm({
    to_kelas_id: '',
    spp_baru: null,
    start_month: '',
});

const availableKelasMutasi = computed(() => {
    return props.allKelas.filter(k => k.id_kelas !== props.siswa.id_kelas);
});

const mutasiSppFormatted = computed({
    get() {
        const numberValue = parseFloat(mutasiForm.spp_baru);
        if (isNaN(numberValue)) return '';
        return new Intl.NumberFormat('id-ID').format(numberValue);
    },
    set(newValue) {
        const numericString = newValue.replace(/[^0-9]/g, '');
        mutasiForm.spp_baru = numericString ? parseInt(numericString, 10) : null;
    }
});

const openMutasiModal = () => {
    mutasiForm.reset();
    const currentMonth = new Date().getMonth() + 1;
    const currentYear = new Date().getFullYear();
    mutasiForm.start_month = `${currentYear}-${currentMonth.toString().padStart(2, '0')}`;
    isMutasiModalOpen.value = true;
};

const handleKelasMutasiChange = () => {
    if (!mutasiForm.to_kelas_id) {
        mutasiForm.spp_baru = null;
        return;
    }
    const selectedKelas = props.allKelas.find(k => k.id_kelas === mutasiForm.to_kelas_id);
    if (selectedKelas && selectedKelas.biaya_spp_default) {
        mutasiForm.spp_baru = parseInt(selectedKelas.biaya_spp_default, 10);
    }
};

const submitMutasi = () => {
    mutasiForm.post(route('admin.mutasi.store', props.siswa.id_siswa), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const isCopyModalOpen = ref(false);
const currentMutasiLink = ref('');
const hasCopiedLink = ref(false);

const openCopyLinkModal = (text) => {
    currentMutasiLink.value = text;
    hasCopiedLink.value = false;
    isCopyModalOpen.value = true;
    
    // Auto-copy
    navigator.clipboard.writeText(text).then(() => {
        hasCopiedLink.value = true;
        setTimeout(() => {
            hasCopiedLink.value = false;
        }, 3000);
    });
};

const manualCopyLink = () => {
    navigator.clipboard.writeText(currentMutasiLink.value).then(() => {
        hasCopiedLink.value = true;
        setTimeout(() => {
            hasCopiedLink.value = false;
        }, 3000);
    });
};

const regenerateMutasi = (mutasiId) => {
    if (confirm('Anda yakin ingin memperbarui link mutasi ini?')) {
        router.post(route('admin.mutasi.regenerate', mutasiId), {}, {
            preserveScroll: true,
        });
    }
};

const cancelMutasi = (mutasiId) => {
    if (confirm('Anda yakin ingin membatalkan permohonan mutasi ini?')) {
        router.post(route('admin.mutasi.cancel', mutasiId), {}, {
            preserveScroll: true,
        });
    }
};

const submitUpdate = () => {
    form.put(route('admin.siswa.update', props.siswa.id_siswa), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

watch(selectedTahun, debounce((value) => {
    router.get(route('admin.siswa.show', props.siswa.id_siswa), {
        tahun: value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['pendingInvoices', 'paidInvoices', 'expiredInvoices', 'filters'],
    });
}, 300));

// --- Logika untuk Pembayaran Manual ---
const showManualPayConfirmModal = ref(false);
const invoiceToMarkAsPaid = ref(null);

const manualPayForm = useForm({
    bukti_pembayaran: null,
});

const confirmMarkAsPaid = (invoice) => {
    invoiceToMarkAsPaid.value = invoice;
    manualPayForm.reset();
    showManualPayConfirmModal.value = true;
};

const markAsPaid = () => {
    if (invoiceToMarkAsPaid.value) {
        manualPayForm.transform((data) => ({
            ...data,
            _method: 'patch',
        })).post(route('admin.invoices.mark_as_paid', invoiceToMarkAsPaid.value.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                showManualPayConfirmModal.value = false;
                invoiceToMarkAsPaid.value = null;
                manualPayForm.reset();
                router.reload({ only: ['pendingInvoices', 'paidInvoices', 'expiredInvoices'] });
            }
        });
    }
};

// --- Logika untuk Resign Siswa ---
const showResignConfirmModal = ref(false);
const resignForm = useForm({
    status_siswa: 'Non-Aktif',
    // Kita butuh kirim data wajib lainnya agar validasi UpdateSiswaRequest lolos
    nama_siswa: props.siswa.nama_siswa,
    user_name: props.siswa.user_name,
    email_wali: props.siswa.email_wali,
    id_kelas: props.siswa.id_kelas,
    tanggal_lahir: props.siswa.tanggal_lahir,
    tanggal_bergabung: props.siswa.tanggal_bergabung,
    jumlah_spp_custom: props.siswa.jumlah_spp_custom,
});

const confirmResign = () => {
    showResignConfirmModal.value = true;
};

const submitResign = () => {
    resignForm.put(route('admin.siswa.update', props.siswa.id_siswa), {
        preserveScroll: true,
        onSuccess: () => {
            showResignConfirmModal.value = false;
        },
    });
};

// --- Logika untuk Proses Keluar (Signed URL) ---
const showProsesKeluarModal = ref(false);
const resignDate = ref('');
const generatedResignationUrl = ref('');
const isGeneratingUrl = ref(false);

const openProsesKeluarModal = () => {
    showProsesKeluarModal.value = true;
    resignDate.value = '';
    generatedResignationUrl.value = '';
};

const generateResignationLink = async () => {
    if (!resignDate.value) {
        alert('Silakan pilih tanggal resign terlebih dahulu.');
        return;
    }
    
    isGeneratingUrl.value = true;
    try {
        const response = await axios.post(route('admin.siswa.generate_resignation_url', props.siswa.id_siswa), {
            tanggal_resign: resignDate.value
        });
        generatedResignationUrl.value = response.data.url;
    } catch (error) {
        console.error(error);
        alert('Gagal membuat link pengunduran diri.');
    } finally {
        isGeneratingUrl.value = false;
    }
};

const copyResignationUrl = async () => {
    if (!generatedResignationUrl.value) return;
    
    try {
        await navigator.clipboard.writeText(generatedResignationUrl.value);
        alert('Link berhasil disalin ke clipboard!');
    } catch (error) {
        // Fallback for non-secure contexts (HTTP instead of HTTPS)
        const textArea = document.createElement("textarea");
        textArea.value = generatedResignationUrl.value;
        
        // Prevent scrolling to bottom of page in MS Edge.
        textArea.style.position = "fixed";
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.opacity = "0";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                alert('Link berhasil disalin ke clipboard!');
            } else {
                alert('Gagal menyalin link. Silakan copy secara manual.');
            }
        } catch (err) {
            alert('Gagal menyalin link. Silakan copy secara manual.');
        }
        document.body.removeChild(textArea);
    }
};

const sendResignationWa = () => {
    if (!props.siswa.nomor_telepon_wali) {
        alert('Nomor WhatsApp wali belum diisi.');
        return;
    }
    if (!generatedResignationUrl.value) {
        alert('Silakan buat tautan terlebih dahulu.');
        return;
    }
    
    let phone = props.siswa.nomor_telepon_wali.replace(/\D/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    
    const message = `Halo Bapak/Ibu Wali dari ${props.siswa.nama_siswa},\n\nBerikut adalah tautan (link) untuk mengisi form pengunduran diri secara resmi. Mohon untuk membukanya dan melengkapi data yang diperlukan:\n\n${generatedResignationUrl.value}\n\nTerima kasih.`;
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
};

// --- Helper Functions ---
const getStatusClass = (status) => {
    if (status === 'PAID' || status === 'Aktif') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    if (status === 'PENDING') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    if (status === 'EXPIRED' || status === 'FAILED') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
};

const getShortDescription = (description) => {
    if (!description) return '';
    return description.split('-')[0].trim();
};

const openWhatsApp = () => {
    if (!props.siswa.nomor_telepon_wali) {
        alert('Nomor WhatsApp wali belum diisi.');
        return;
    }
    let phone = props.siswa.nomor_telepon_wali.replace(/\D/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    window.open(`https://wa.me/${phone}`, '_blank');
};

const underDevelopmentAlert = () => {
    alert('Fitur ini sedang dalam pengembangan.');
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
                <h2 class="font-semibold text-base sm:text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ pageTitle }}: {{ siswa.nama_siswa }}
                </h2>
            </div>
        </template>

         <Toast :message="flashMessage" :type="flashType" />

        <div class="pt-4 space-y-6">
            
            <!-- Modern Header Profile -->
            <div class="relative z-20 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl shadow-sm rounded-2xl overflow-visible border border-gray-200/50 dark:border-gray-700/50">
                <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-4 sm:gap-6 w-full">
                        <div class="h-24 w-24 sm:h-20 sm:w-20 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center flex-shrink-0 border-4 border-white dark:border-gray-800 shadow-sm mx-auto sm:mx-0">
                            <span class="text-4xl sm:text-3xl font-bold text-indigo-700 dark:text-indigo-300">{{ siswa.nama_siswa.charAt(0) }}</span>
                        </div>
                        <div class="flex flex-col items-center sm:items-start w-full">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
                                {{ siswa.nama_siswa }}
                                <span class="px-3 py-1 rounded-full text-xs font-semibold shadow-sm" :class="getStatusClass(siswa.status_siswa)">{{ siswa.status_siswa }}</span>
                            </h3>
                            <div class="mt-3 sm:mt-2 text-sm text-gray-500 dark:text-gray-400 flex flex-col sm:flex-row sm:flex-wrap items-center sm:items-start gap-2 sm:gap-4">
                                <span class="flex items-center gap-1"><UserIcon class="w-4 h-4 text-gray-400" /> NIS: {{ siswa.nis ?? 'Dalam Proses' }}</span>
                                <span class="flex items-center gap-1"><BookOpenIcon class="w-4 h-4 text-gray-400" /> Kelas: {{ siswa.kelas_nama ?? 'Belum ada kelas' }}</span>
                                <span class="flex items-center gap-1"><CalendarDaysIcon class="w-4 h-4 text-gray-400" /> Bergabung: {{ siswa.tanggal_bergabung_formatted ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 shrink-0 w-full md:w-auto justify-end">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150 shadow-sm">
                                        Aksi
                                        <ChevronDownIcon class="ml-2 -mr-0.5 h-4 w-4" aria-hidden="true" />
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <!-- Shortcut: Buat Tagihan Baru -->
                                <DropdownLink v-if="!['Keluar', 'pending_payment'].includes(siswa.status_siswa)" :href="route('admin.invoices.index') + '?siswa=' + siswa.id_siswa" class="flex items-center text-gray-700 dark:text-gray-300">
                                    <PlusIcon class="h-4 w-4 mr-2 text-indigo-500" /> Buat Tagihan SPP
                                </DropdownLink>

                                <!-- Shortcut: Chat WhatsApp -->
                                <button type="button" @click="openWhatsApp" class="w-full text-left block w-full px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out flex items-center">
                                    <ChatBubbleLeftEllipsisIcon class="h-4 w-4 mr-2 text-green-500" /> Hubungi Wali (WA)
                                </button>

                                <!-- Shortcut: Ajukan Cuti -->
                                <DropdownLink v-if="!['Keluar', 'pending_payment'].includes(siswa.status_siswa)" :href="route('admin.leaves.index') + '?create_for=' + siswa.id_siswa" class="flex items-center text-gray-700 dark:text-gray-300">
                                    <CalendarIcon class="h-4 w-4 mr-2 text-indigo-500" /> Ajukan Cuti
                                </DropdownLink>
                                <!-- Shortcut: Pindah Cabang / Kelas -->
                                <button v-if="siswa.status_siswa === 'Aktif'" @click="openMutasiModal" :disabled="hasPendingMutasi" :class="{'opacity-50 cursor-not-allowed': hasPendingMutasi}" class="w-full text-left block w-full px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 mr-2 text-blue-500">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                    {{ hasPendingMutasi ? 'Mutasi Pending...' : 'Pindah Cabang/Kelas' }}
                                </button>
                                
                                <div class="border-t border-gray-100 dark:border-gray-700"></div>

                                <!-- Shortcut: Nonaktifkan Siswa -->
                                <button v-if="siswa.status_siswa === 'Aktif'" @click="confirmResign" class="w-full text-left block w-full px-4 py-2 text-sm leading-5 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/50 focus:outline-none focus:bg-yellow-50 transition duration-150 ease-in-out flex items-center">
                                    <ExclamationTriangleIcon class="h-4 w-4 mr-2" /> Nonaktifkan Siswa
                                </button>
                                
                                <!-- Shortcut: Proses Keluar -->
                                <button v-if="!['Keluar', 'pending_payment'].includes(siswa.status_siswa)" @click="openProsesKeluarModal" class="w-full text-left block w-full px-4 py-2 text-sm leading-5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/50 focus:outline-none focus:bg-red-50 transition duration-150 ease-in-out flex items-center">
                                    <ArrowRightOnRectangleIcon class="h-4 w-4 mr-2" /> Proses Keluar (Resign)
                                </button>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kolom Kiri: Biodata -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl shadow-sm rounded-2xl overflow-hidden border border-gray-200/50 dark:border-gray-700/50">
                        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <UserIcon class="w-5 h-5 mr-2 text-indigo-500" /> Informasi Detail
                            </h3>
                        </div>
                        <div class="p-4 sm:p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                                <div class="col-span-2 sm:col-span-1">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Info Pribadi</h4>
                                        <button @click="openEditPribadi" class="text-gray-400 hover:text-indigo-600 transition" title="Edit Info Pribadi"><PencilIcon class="w-4 h-4"/></button>
                                    </div>
                                    <dl class="space-y-3">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ siswa.nama_siswa }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                {{ siswa.tanggal_lahir_formatted ?? '-' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Info Wali</h4>
                                        <button @click="openEditWali" class="text-gray-400 hover:text-indigo-600 transition" title="Edit Info Wali"><PencilIcon class="w-4 h-4"/></button>
                                    </div>
                                    <dl class="space-y-3">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Nama Wali (Tampilan)</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                {{ siswa.user_name ?? '-' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Email Wali (Login)</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                <EnvelopeIcon class="w-4 h-4 text-gray-400" /> {{ siswa.email_wali ?? '-' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">No. Telepon Wali</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                <PhoneIcon class="w-4 h-4 text-gray-400" /> {{ siswa.nomor_telepon_wali ?? '-' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="col-span-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Info Akademik</h4>
                                        <button @click="openEditAkademik" class="text-gray-400 hover:text-indigo-600 transition" title="Edit Info Akademik"><PencilIcon class="w-4 h-4"/></button>
                                    </div>
                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Kelas</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ siswa.kelas_nama ?? '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="getStatusClass(siswa.status_siswa)">{{ siswa.status_siswa }}</span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Pengaturan Keuangan -->
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl shadow-sm rounded-2xl overflow-hidden border border-gray-200/50 dark:border-gray-700/50">
                        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <BanknotesIcon class="w-5 h-5 mr-2 text-green-500" /> Pengaturan Spp
                            </h3>
                             <button @click="openEditKeuangan" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition shadow-sm">
                                <PencilIcon class="w-3.5 h-3.5 mr-1.5 text-gray-400" /> Edit
                            </button>
                        </div>
                        <div class="p-4 sm:p-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Pengaturan ini akan menimpa standar tarif bulanan pada kelas/program siswa. Kosongkan (atau set ke 0) jika siswa ini mengikuti tarif standar.</p>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SPP Bulanan Khusus</dt>
                                    <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100 font-bold flex items-center gap-2">
                                        <CurrencyDollarIcon class="w-5 h-5 text-green-500" /> 
                                        <span v-if="siswa.jumlah_spp_custom">{{ siswa.jumlah_spp_custom_formatted }}</span>
                                        <span v-else class="text-gray-400 italic text-sm font-normal">Mengikuti Standar Kelas</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Admin Fee Khusus</dt>
                                    <dd class="mt-1 text-lg text-gray-900 dark:text-gray-100 font-bold flex items-center gap-2">
                                        <CurrencyDollarIcon class="w-5 h-5 text-indigo-500" /> 
                                        <span v-if="siswa.admin_fee_custom">{{ siswa.admin_fee_custom_formatted }}</span>
                                        <span v-else class="text-gray-400 italic text-sm font-normal">Mengikuti Standar Kelas</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Status Legal & Lainnya -->
                <div class="space-y-6">
                    
                    <!-- Kartu Pelajar (ID Card Preview) -->
                    <div v-if="['pending_payment', 'Keluar', 'Non-Aktif'].includes(siswa.status_siswa)" class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <h4 class="text-gray-900 dark:text-gray-100 font-bold mb-1">ID Card Dikunci</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            Kartu pelajar tidak dapat diakses karena status siswa saat ini adalah <span class="font-semibold text-gray-700 dark:text-gray-300">{{ siswa.status_siswa }}</span>.
                        </p>
                    </div>
                    <div v-else class="relative">
                        <div class="bg-black rounded-2xl shadow-xl text-white relative overflow-hidden group w-full" style="background: linear-gradient(135deg, #1a0000 0%, #660000 50%, #000000 100%); aspect-ratio: 1.586 / 1;">
                            
                            <!-- Soccer Ball -->
                            <svg class="absolute -top-10 -left-10 w-32 h-32 text-red-600 opacity-10 transform rotate-12" viewBox="0 0 72.371 72.372" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                <path d="M22.57,2.648c-4.489,1.82-8.517,4.496-11.971,7.949C7.144,14.051,4.471,18.08,2.65,22.568C0.892,26.904,0,31.486,0,36.186 c0,4.699,0.892,9.281,2.65,13.615c1.821,4.489,4.495,8.518,7.949,11.971c3.454,3.455,7.481,6.129,11.971,7.949 c4.336,1.76,8.917,2.649,13.617,2.649c4.7,0,9.28-0.892,13.616-2.649c4.488-1.82,8.518-4.494,11.971-7.949 c3.455-3.453,6.129-7.48,7.949-11.971c1.758-4.334,2.648-8.916,2.648-13.615c0-4.7-0.891-9.282-2.648-13.618 c-1.82-4.488-4.496-8.518-7.949-11.971s-7.479-6.129-11.971-7.949C45.467,0.891,40.887,0,36.187,0 C31.487,0,26.906,0.891,22.57,2.648z M9.044,51.419c-1.743-1.094-3.349-2.354-4.771-3.838c-2.172-6.112-2.54-12.729-1.101-19.01 c0.677-1.335,1.447-2.617,2.318-3.845c0.269-0.379,0.518-0.774,0.806-1.142l8.166,4.832c0,0.064,0,0.134,0,0.205 c-0.021,4.392,0.425,8.752,1.313,13.049c0.003,0.02,0.006,0.031,0.01,0.049l-6.333,9.93C9.314,51.579,9.177,51.503,9.044,51.419z M33.324,68.206c1.409,0.719,2.858,1.326,4.347,1.82c-6.325,0.275-12.713-1.207-18.36-4.447L33,68.018 C33.105,68.085,33.212,68.149,33.324,68.206z M33.274,65.735L17.12,62.856c-1.89-2.295-3.59-4.723-5.051-7.318 c-0.372-0.66-0.787-1.301-1.102-1.99l6.327-9.92c0.14,0.035,0.296,0.072,0.473,0.119c3.958,1.059,7.986,1.812,12.042,2.402 c0.237,0.033,0.435,0.062,0.604,0.08l7.584,13.113c-1.316,1.85-2.647,3.69-4.007,5.51C33.764,65.155,33.524,65.446,33.274,65.735z M60.15,60.149c-1.286,1.287-2.651,2.447-4.08,3.481c-0.237-1.894-0.646-3.75-1.223-5.563l8.092-15.096 c2.229-1.015,4.379-2.166,6.375-3.593c0.261-0.185,0.478-0.392,0.646-0.618C69.374,46.561,66.104,54.196,60.15,60.149z M59.791,40.571c0.301,0.574,0.598,1.154,0.896,1.742l-7.816,14.58c-0.045,0.01-0.088,0.02-0.133,0.026 c-4.225,0.789-8.484,1.209-12.779,1.229l-7.8-13.487c1.214-2.254,2.417-4.517,3.61-6.781c0.81-1.536,1.606-3.082,2.401-4.627 l16.143-1.658C56.29,34.495,58.163,37.457,59.791,40.571z M56.516,23.277c-0.766,2.023-1.586,4.025-2.401,6.031l-15.726,1.615 c-0.188-0.248-0.383-0.492-0.588-0.725c-1.857-2.103-3.726-4.193-5.592-6.289c0.017-0.021,0.034-0.037,0.051-0.056 c-0.753-0.752-1.508-1.504-2.261-2.258l4.378-13.181c0.302-0.08,0.606-0.147,0.913-0.18c2.38-0.242,4.763-0.516,7.149-0.654 c1.461-0.082,2.93-0.129,4.416-0.024l10.832,12.209C57.314,20.943,56.95,22.124,56.516,23.277z M60.15,12.221 c2.988,2.99,5.302,6.402,6.938,10.047c-2.024-1.393-4.188-2.539-6.463-3.473c-0.354-0.146-0.717-0.275-1.086-0.402L48.877,6.376 c0.074-0.519,0.113-1.039,0.129-1.563C53.062,6.464,56.864,8.936,60.15,12.221z M25.334,4.182c0.042,0.031,0.062,0.057,0.086,0.064 c2.437,0.842,4.654,2.082,6.744,3.553l-4.09,12.317c-0.021,0.006-0.041,0.012-0.061,0.021c-0.837,0.346-1.69,0.656-2.514,1.031 c-3.395,1.543-6.705,3.252-9.823,5.301l-8.071-4.775c0.012-0.252,0.055-0.508,0.141-0.736c0.542-1.444,1.075-2.896,1.688-4.311 c0.472-1.09,1.01-2.143,1.597-3.172c0.384-0.424,0.782-0.844,1.192-1.254c3.833-3.832,8.363-6.553,13.186-8.162 C25.384,4.098,25.358,4.139,25.334,4.182z"/>
                            </svg>

                            <!-- Focused Soccer Ball -->
                            <svg class="absolute -bottom-8 -right-8 w-40 h-40 text-red-500 opacity-10 transform -rotate-12" viewBox="0 0 72.371 72.372" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                <path d="M22.57,2.648c-4.489,1.82-8.517,4.496-11.971,7.949C7.144,14.051,4.471,18.08,2.65,22.568C0.892,26.904,0,31.486,0,36.186 c0,4.699,0.892,9.281,2.65,13.615c1.821,4.489,4.495,8.518,7.949,11.971c3.454,3.455,7.481,6.129,11.971,7.949 c4.336,1.76,8.917,2.649,13.617,2.649c4.7,0,9.28-0.892,13.616-2.649c4.488-1.82,8.518-4.494,11.971-7.949 c3.455-3.453,6.129-7.48,7.949-11.971c1.758-4.334,2.648-8.916,2.648-13.615c0-4.7-0.891-9.282-2.648-13.618 c-1.82-4.488-4.496-8.518-7.949-11.971s-7.479-6.129-11.971-7.949C45.467,0.891,40.887,0,36.187,0 C31.487,0,26.906,0.891,22.57,2.648z M9.044,51.419c-1.743-1.094-3.349-2.354-4.771-3.838c-2.172-6.112-2.54-12.729-1.101-19.01 c0.677-1.335,1.447-2.617,2.318-3.845c0.269-0.379,0.518-0.774,0.806-1.142l8.166,4.832c0,0.064,0,0.134,0,0.205 c-0.021,4.392,0.425,8.752,1.313,13.049c0.003,0.02,0.006,0.031,0.01,0.049l-6.333,9.93C9.314,51.579,9.177,51.503,9.044,51.419z M33.324,68.206c1.409,0.719,2.858,1.326,4.347,1.82c-6.325,0.275-12.713-1.207-18.36-4.447L33,68.018 C33.105,68.085,33.212,68.149,33.324,68.206z M33.274,65.735L17.12,62.856c-1.89-2.295-3.59-4.723-5.051-7.318 c-0.372-0.66-0.787-1.301-1.102-1.99l6.327-9.92c0.14,0.035,0.296,0.072,0.473,0.119c3.958,1.059,7.986,1.812,12.042,2.402 c0.237,0.033,0.435,0.062,0.604,0.08l7.584,13.113c-1.316,1.85-2.647,3.69-4.007,5.51C33.764,65.155,33.524,65.446,33.274,65.735z M60.15,60.149c-1.286,1.287-2.651,2.447-4.08,3.481c-0.237-1.894-0.646-3.75-1.223-5.563l8.092-15.096 c2.229-1.015,4.379-2.166,6.375-3.593c0.261-0.185,0.478-0.392,0.646-0.618C69.374,46.561,66.104,54.196,60.15,60.149z M59.791,40.571c0.301,0.574,0.598,1.154,0.896,1.742l-7.816,14.58c-0.045,0.01-0.088,0.02-0.133,0.026 c-4.225,0.789-8.484,1.209-12.779,1.229l-7.8-13.487c1.214-2.254,2.417-4.517,3.61-6.781c0.81-1.536,1.606-3.082,2.401-4.627 l16.143-1.658C56.29,34.495,58.163,37.457,59.791,40.571z M56.516,23.277c-0.766,2.023-1.586,4.025-2.401,6.031l-15.726,1.615 c-0.188-0.248-0.383-0.492-0.588-0.725c-1.857-2.103-3.726-4.193-5.592-6.289c0.017-0.021,0.034-0.037,0.051-0.056 c-0.753-0.752-1.508-1.504-2.261-2.258l4.378-13.181c0.302-0.08,0.606-0.147,0.913-0.18c2.38-0.242,4.763-0.516,7.149-0.654 c1.461-0.082,2.93-0.129,4.416-0.024l10.832,12.209C57.314,20.943,56.95,22.124,56.516,23.277z M60.15,12.221 c2.988,2.99,5.302,6.402,6.938,10.047c-2.024-1.393-4.188-2.539-6.463-3.473c-0.354-0.146-0.717-0.275-1.086-0.402L48.877,6.376 c0.074-0.519,0.113-1.039,0.129-1.563C53.062,6.464,56.864,8.936,60.15,12.221z M25.334,4.182c0.042,0.031,0.062,0.057,0.086,0.064 c2.437,0.842,4.654,2.082,6.744,3.553l-4.09,12.317c-0.021,0.006-0.041,0.012-0.061,0.021c-0.837,0.346-1.69,0.656-2.514,1.031 c-3.395,1.543-6.705,3.252-9.823,5.301l-8.071-4.775c0.012-0.252,0.055-0.508,0.141-0.736c0.542-1.444,1.075-2.896,1.688-4.311 c0.472-1.09,1.01-2.143,1.597-3.172c0.384-0.424,0.782-0.844,1.192-1.254c3.833-3.832,8.363-6.553,13.186-8.162 C25.384,4.098,25.358,4.139,25.334,4.182z"/>
                            </svg>

                            <!-- Content Wrapper -->
                            <div class="relative z-10 p-5 flex flex-col h-full justify-between">
                                <!-- Header ID Card -->
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-black text-lg sm:text-xl tracking-widest text-red-500">KARTU PELAJAR</h4>
                                        <p class="text-[8px] sm:text-[9px] text-red-300 uppercase tracking-[0.2em] font-semibold mt-1">Sistem Informasi Akademik</p>
                                    </div>
                                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white/10 rounded-lg flex items-center justify-center border border-white/10 shadow-lg">
                                        <QrCodeIcon class="w-5 h-5 sm:w-6 sm:h-6 text-white opacity-80" />
                                    </div>
                                </div>
                                
                                <!-- Body ID Card (Photo & Details) -->
                                <div class="flex items-center gap-3 sm:gap-4 mt-auto">
                                    <!-- Photo Placeholder -->
                                    <div class="w-16 h-20 sm:w-20 sm:h-28 bg-gradient-to-br from-gray-800 to-black rounded-lg border border-red-500/30 overflow-hidden shadow-2xl flex-shrink-0 relative">
                                        <div class="absolute inset-0 flex items-center justify-center text-gray-500">
                                            <UserIcon class="w-8 h-8 sm:w-10 sm:h-10" />
                                        </div>
                                    </div>
                                    
                                    <!-- Details -->
                                    <div class="flex-1 min-w-0 flex flex-col justify-end">
                                        <h3 class="font-bold text-base sm:text-lg leading-normal mb-1 text-white drop-shadow-md m-0">{{ siswa.nama_siswa }}</h3>
                                        <div class="text-xs sm:text-sm text-red-200 font-mono tracking-widest mb-2 drop-shadow-md opacity-80 m-0">{{ siswa.nis || 'NIS-PENDING' }}</div>
                                        <div class="flex">
                                            <div class="flex items-center justify-center px-3 py-1.5 bg-red-600/20 rounded border border-red-500/30 text-[10px] sm:text-xs font-semibold shadow-sm text-red-100 text-center leading-tight">
                                                {{ siswa.kelas_nama || 'Belum Ada Kelas' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Hover -->
                        <div class="mt-4 flex gap-2 justify-end relative z-10">
                            <button @click="isPreviewIdCardOpen = true" class="text-xs bg-red-600 text-white hover:bg-red-700 px-3 py-1.5 rounded-lg font-bold transition-colors flex items-center gap-1.5 shadow-md w-full justify-center">
                                <QrCodeIcon class="w-4 h-4" /> Preview ID Card
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700">
                        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <DocumentArrowDownIcon class="w-5 h-5 mr-2 text-indigo-500" /> Legalitas & Persetujuan
                            </h3>
                        </div>
                        <div class="p-4 sm:p-6">
                            <div v-if="legalAgreements.length > 0" class="space-y-4">
                                <div v-for="agreement in legalAgreements" :key="agreement.id" class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 border border-green-200 dark:border-green-800/50">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <CheckBadgeIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-green-800 dark:text-green-300">
                                                Disetujui: {{ agreement.document_name }} 
                                                <span v-if="agreement.type === 'resignation'" class="ml-2 inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Resign</span>
                                            </h3>
                                            <div class="mt-2 text-sm text-green-700 dark:text-green-400 space-y-1">
                                                <p><strong>Waktu:</strong> {{ agreement.agreed_at }}</p>
                                                <p><strong>Versi Dokumen:</strong> {{ agreement.version }}</p>
                                            </div>
                                            <div class="mt-4">
                                                <a :href="route('admin.siswa.legal_pdf', [siswa.id_siswa, agreement.id])" target="_blank" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                                    Unduh Dokumen (PDF)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 border border-yellow-200 dark:border-yellow-800/50">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" aria-hidden="true" />
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Belum Ada Persetujuan</h3>
                                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                            <p>Tidak ada catatan persetujuan dokumen legal saat siswa ini didaftarkan. Dokumen PDF persetujuan tidak dapat diunduh.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Mutasi Cabang -->
            <div v-if="mutasiSiswas && mutasiSiswas.length > 0" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700 mb-6">
                <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-blue-500">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                        Riwayat Pindah Cabang / Kelas
                    </h3>
                </div>
                <div class="px-4 py-5 sm:p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Dibuat</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kelas Lama</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kelas Baru</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="mutasi in mutasiSiswas" :key="mutasi.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ mutasi.created_at }}<br>
                                    <span class="text-xs">oleh {{ mutasi.created_by_name }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ mutasi.from_kelas }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ mutasi.to_kelas }}<br>
                                    <span class="text-xs text-gray-500">Mulai: {{ mutasi.start_month }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <span v-if="mutasi.status === 'APPROVED'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Disetujui</span>
                                    <span v-else-if="mutasi.status === 'PENDING' && !mutasi.is_expired" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>
                                    <span v-else-if="mutasi.status === 'PENDING' && mutasi.is_expired" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Kedaluwarsa</span>
                                    <span v-else-if="mutasi.status === 'EXPIRED'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Kedaluwarsa</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dibatalkan</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <div v-if="mutasi.status === 'PENDING' && !mutasi.is_expired" class="flex items-center space-x-2">
                                        <button @click="openCopyLinkModal(route('mutasi.show', mutasi.token))" class="text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 p-1.5 rounded-md transition-colors" title="Copy Link Persetujuan">
                                            <ClipboardDocumentIcon class="h-4 w-4" />
                                        </button>
                                        <button @click="cancelMutasi(mutasi.id)" class="text-red-600 hover:bg-red-50 dark:hover:bg-red-900/50 p-1.5 rounded-md transition-colors" title="Batalkan Permohonan">
                                            <XMarkIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div v-else-if="mutasi.is_expired || mutasi.status === 'EXPIRED'">
                                        <button @click="regenerateMutasi(mutasi.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-xs" title="Regenerate Link">
                                            Regenerate Link
                                        </button>
                                    </div>
                                    <div v-else-if="mutasi.status === 'APPROVED'">
                                        <span class="text-xs text-green-600">Disetujui oleh: {{ mutasi.agreed_by }}</span><br>
                                        <span class="text-xs text-gray-500">{{ mutasi.agreed_at }}</span>
                                    </div>
                                    <div v-else>
                                        -
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Riwayat Invoice -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700">
                 <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                        <BanknotesIcon class="w-5 h-5 mr-2 text-indigo-500" /> Riwayat Keuangan
                    </h3>
                    <select v-model="selectedTahun" class="w-full sm:w-48 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                        <option v-for="year in availableYears" :key="year" :value="year">Tahun {{ year }}</option>
                    </select>
                </div>
                
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-6 px-4 sm:px-6 overflow-x-auto scrollbar-hide" aria-label="Tabs">
                        <button @click="activeTab = 'pending'" :class="[activeTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center']">
                            Tertunda <span class="ml-2 inline-flex items-center justify-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">{{ pendingInvoices.length }}</span>
                        </button>
                        <button @click="activeTab = 'paid'" :class="[activeTab === 'paid' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center']">
                            Lunas <span class="ml-2 inline-flex items-center justify-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ paidInvoices.length }}</span>
                        </button>
                        <button @click="activeTab = 'expired'" :class="[activeTab === 'expired' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center']">
                            Batal/Expired <span class="ml-2 inline-flex items-center justify-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ expiredInvoices.length }}</span>
                        </button>
                    </nav>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-900/20">
                    <!-- Template Pending -->
                    <template v-if="activeTab === 'pending'">
                        <div v-if="pendingInvoices.length === 0" class="text-center py-12">
                            <BanknotesIcon class="mx-auto h-12 w-12 text-gray-300" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-200">Tidak ada tagihan tertunda</h3>
                            <p class="mt-1 text-sm text-gray-500">Semua tagihan untuk tahun ini telah dibayar.</p>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="invoice in pendingInvoices" :key="invoice.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-yellow-200 dark:border-yellow-900/50 p-5 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-yellow-400"></div>
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate" :title="invoice.description">{{ getShortDescription(invoice.description) }}</h4>
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">PENDING</span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ invoice.total_amount_formatted }}</div>
                                <div class="flex items-center justify-between text-sm mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">Jatuh Tempo: {{ invoice.due_date_formatted }}</span>
                                    <div class="flex gap-2">
                                        <button @click="confirmMarkAsPaid(invoice)" class="text-green-600 hover:text-green-900 bg-green-50 p-1.5 rounded" title="Tandai Sudah Bayar (Manual)">
                                            <BanknotesIcon class="h-4 w-4" />
                                        </button>
                                        <a v-if="invoice.xendit_payment_url" :href="invoice.xendit_payment_url" target="_blank" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1.5 rounded font-medium">Bayar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Template Paid -->
                    <template v-if="activeTab === 'paid'">
                        <div v-if="paidInvoices.length === 0" class="text-center py-12">
                            <BanknotesIcon class="mx-auto h-12 w-12 text-gray-300" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-200">Belum ada riwayat pembayaran</h3>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="invoice in paidInvoices" :key="invoice.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-green-500"></div>
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate" :title="invoice.description">{{ getShortDescription(invoice.description) }}</h4>
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">LUNAS</span>
                                </div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ invoice.total_amount_formatted }}</div>
                                <div v-if="invoice.paymentParent" class="text-xs text-gray-500 flex items-center mb-4 bg-gray-50 dark:bg-gray-900/50 p-2 rounded">
                                    <LinkIcon class="h-3 w-3 mr-1 shrink-0" />
                                    <span class="truncate">Via: {{ getShortDescription(invoice.paymentParent.description) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-gray-500">
                                    <span>Dibayar: {{ invoice.paid_at_formatted }}</span>
                                    <a v-if="invoice.paymentParent?.xendit_payment_url || invoice.xendit_payment_url" :href="invoice.paymentParent?.xendit_payment_url || invoice.xendit_payment_url" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium">Nota/Resi &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Template Expired -->
                    <template v-if="activeTab === 'expired'">
                        <div v-if="expiredInvoices.length === 0" class="text-center py-12">
                            <BanknotesIcon class="mx-auto h-12 w-12 text-gray-300" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-200">Tidak ada tagihan kadaluarsa</h3>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="invoice in expiredInvoices" :key="invoice.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 relative overflow-hidden opacity-75">
                                <div class="absolute top-0 left-0 w-1 h-full bg-red-400"></div>
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate" :title="invoice.description">{{ getShortDescription(invoice.description) }}</h4>
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">{{ invoice.status }}</span>
                                </div>
                                <div class="text-xl font-bold text-gray-500 dark:text-gray-400 mb-4 line-through">{{ invoice.total_amount_formatted }}</div>
                                <div class="flex items-center justify-between text-xs mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-gray-500">
                                    <span>Tgl: {{ invoice.due_date_formatted }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal Edit Info Pribadi -->
        <Modal :show="isEditPribadiOpen" @close="closeModal" :maxWidth="'md'">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Edit Info Pribadi</h2>
                <form @submit.prevent="submitUpdate" class="space-y-6" novalidate>
                    <div>
                        <InputLabel for="nama_siswa_modal" value="Nama Lengkap Siswa" required />
                        <TextInput id="nama_siswa_modal" type="text" class="mt-1 block w-full" v-model="form.nama_siswa" required />
                        <InputError class="mt-2" :message="form.errors.nama_siswa" />
                    </div>
                    <div>
                        <InputLabel for="tanggal_lahir_modal" value="Tanggal Lahir" required />
                        <TextInput id="tanggal_lahir_modal" type="date" class="mt-1 block w-full" v-model="form.tanggal_lahir" required />
                        <InputError class="mt-2" :message="form.errors.tanggal_lahir" />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button" :disabled="form.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing"> Simpan </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Edit Info Wali -->
        <Modal :show="isEditWaliOpen" @close="closeModal" :maxWidth="'md'">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Edit Info Wali & Akun Login</h2>
                <form @submit.prevent="submitUpdate" class="space-y-6" novalidate>
                    <div>
                        <InputLabel for="user_name_modal" value="Nama Wali (Untuk tampilan)" required />
                        <TextInput id="user_name_modal" type="text" class="mt-1 block w-full" v-model="form.user_name" required />
                        <InputError class="mt-2" :message="form.errors.user_name" />
                    </div>
                    <div>
                        <InputLabel for="email_wali_modal" value="Email Wali (Untuk Login)" required />
                        <TextInput id="email_wali_modal" type="email" class="mt-1 block w-full" v-model="form.email_wali" required />
                        <InputError class="mt-2" :message="form.errors.email_wali" />
                    </div>
                    <div>
                        <InputLabel for="nomor_telepon_wali_modal" value="No. Telepon Wali (WhatsApp)" />
                        <TextInput id="nomor_telepon_wali_modal" type="text" class="mt-1 block w-full" v-model="form.nomor_telepon_wali" />
                        <InputError class="mt-2" :message="form.errors.nomor_telepon_wali" />
                    </div>
                    <div>
                        <InputLabel for="password_edit_modal" value="Password Baru Akun (Opsional)" />
                        <TextInput id="password_edit_modal" type="password" class="mt-1 block w-full" v-model="form.user_password" placeholder="Isi jika ingin ganti password"/>
                        <InputError class="mt-2" :message="form.errors.user_password" />
                    </div>
                    <div v-if="form.user_password">
                        <InputLabel for="password_confirmation_edit_modal" value="Konfirmasi Password Baru" />
                        <TextInput id="password_confirmation_edit_modal" type="password" class="mt-1 block w-full" v-model="form.user_password_confirmation" />
                        <InputError class="mt-2" :message="form.errors.user_password_confirmation" />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button" :disabled="form.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing"> Simpan </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Edit Akademik -->
        <Modal :show="isEditAkademikOpen" @close="closeModal" :maxWidth="'md'">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Edit Data Akademik</h2>
                <form @submit.prevent="submitUpdate" class="space-y-6" novalidate>
                    <div>
                        <InputLabel for="nis_modal" value="Nomor Induk Siswa (NIS)" />
                        <input id="nis_modal" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100 text-gray-500" :value="siswa.nis || 'Dalam Proses / Belum Dibuat'" readonly />
                    </div>
                    <div>
                        <InputLabel for="id_kelas_modal" value="Kelas" required />
                        <select id="id_kelas_modal" v-model="form.id_kelas" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" disabled>Pilih Kelas</option>
                            <option v-for="k in allKelas" :key="k.id_kelas" :value="k.id_kelas">{{ k.nama_kelas }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.id_kelas" />
                    </div>
                    <div>
                        <InputLabel for="status_siswa_modal" value="Status Siswa" required />
                        <select id="status_siswa_modal" v-model="form.status_siswa" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="status in statusSiswaOptions" :key="status" :value="status">{{ status }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status_siswa" />
                    </div>
                    <div>
                        <InputLabel for="tanggal_bergabung_modal" value="Tanggal Bergabung" required />
                        <TextInput id="tanggal_bergabung_modal" type="date" class="mt-1 block w-full" v-model="form.tanggal_bergabung" required />
                        <InputError class="mt-2" :message="form.errors.tanggal_bergabung" />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button" :disabled="form.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing"> Simpan </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Edit Keuangan -->
        <Modal :show="isEditKeuanganOpen" @close="closeModal" :maxWidth="'md'">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700">Edit Pengaturan Keuangan</h2>
                <form @submit.prevent="submitUpdate" class="space-y-6" novalidate>
                    <div>
                        <InputLabel for="jumlah_spp_custom_modal" value="SPP Bulanan Custom (Kosongkan jika ikut standar kelas)" />
                        <div class="relative mt-1 rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <TextInput id="jumlah_spp_custom_modal" type="text" class="block w-full pl-10" v-model="sppCustomFormatted" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.jumlah_spp_custom" />
                    </div>
                    <div>
                        <InputLabel for="admin_fee_custom_modal" value="Admin Fee Custom (Kosongkan jika ikut standar kelas)" />
                         <div class="relative mt-1 rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <TextInput id="admin_fee_custom_modal" type="text" class="block w-full pl-10" v-model="adminFeeCustomFormatted" />
                        </div>
                        <InputError class="mt-2" :message="form.errors.admin_fee_custom" />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button" :disabled="form.processing"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing"> Simpan </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Pindah Cabang / Kelas (Mutasi) -->
        <Modal :show="isMutasiModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-3 dark:border-gray-700 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2 text-blue-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                    Pindah Cabang / Kelas
                </h2>
                
                <form @submit.prevent="submitMutasi">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Fitur ini akan menghasilkan <strong>Link Persetujuan</strong> yang bisa dibagikan ke orang tua/wali siswa. Kepindahan siswa baru akan diproses setelah wali menyetujuinya.
                        </p>
                        
                        <div>
                            <InputLabel for="mutasi_to_kelas" value="Cabang / Kelas Baru" />
                            <select
                                id="mutasi_to_kelas"
                                v-model="mutasiForm.to_kelas_id"
                                @change="handleKelasMutasiChange"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required
                            >
                                <option value="" disabled>-- Pilih Kelas Baru --</option>
                                <option v-for="kelas in availableKelasMutasi" :key="kelas.id_kelas" :value="kelas.id_kelas">
                                    {{ kelas.nama_kelas }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="mutasiForm.errors.to_kelas_id" />
                        </div>

                        <div>
                            <InputLabel for="mutasi_spp_baru" value="Nominal SPP Baru" />
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">Rp</span>
                                </div>
                                <TextInput
                                    id="mutasi_spp_baru"
                                    type="text"
                                    class="mt-1 block w-full pl-10"
                                    v-model="mutasiSppFormatted"
                                    placeholder="Contoh: 350.000"
                                />
                            </div>
                            <InputError class="mt-2" :message="mutasiForm.errors.spp_baru" />
                            <p class="text-xs text-gray-500 mt-1">Otomatis terisi dari kelas terpilih. Bisa diubah (Custom).</p>
                        </div>

                        <div>
                            <InputLabel for="mutasi_start_month" value="Mulai Berlaku SPP Baru (Bulan)" />
                            <TextInput
                                id="mutasi_start_month"
                                type="month"
                                class="mt-1 block w-full"
                                v-model="mutasiForm.start_month"
                                required
                            />
                            <InputError class="mt-2" :message="mutasiForm.errors.start_month" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="closeModal" type="button"> Batal </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': mutasiForm.processing }" :disabled="mutasiForm.processing">
                            Generate Link Mutasi
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Konfirmasi Pembayaran Manual -->
        <Modal :show="showManualPayConfirmModal" @close="showManualPayConfirmModal = false" maxWidth="md">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <CurrencyDollarIcon class="h-6 w-6 mr-2 text-green-500" />
                    Konfirmasi Pembayaran Manual
                </h2>
                <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-md border border-yellow-200 dark:border-yellow-700">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 font-semibold mb-2">
                        Anda akan menandai tagihan bulan <span class="font-bold underline">{{ invoiceToMarkAsPaid?.bulan }}</span> sebagai LUNAS.
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside space-y-1 mt-2">
                        <li>Total yang dibayar: <span class="font-bold font-mono bg-yellow-100 dark:bg-yellow-800 px-1 rounded">{{ invoiceToMarkAsPaid?.total_tagihan_formatted }}</span></li>
                        <li v-if="invoiceToMarkAsPaid?.spp_tagihan">Tagihan SPP: {{ invoiceToMarkAsPaid?.spp_tagihan_formatted }}</li>
                        <li v-if="invoiceToMarkAsPaid?.admin_fee_tagihan">Admin Fee: {{ invoiceToMarkAsPaid?.admin_fee_tagihan_formatted }}</li>
                    </ul>
                </div>
                
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Aksi ini akan mencatat pembayaran sebagai transaksi manual (misal: tunai/transfer langsung) dan tidak dapat dibatalkan.
                </p>

                <div class="mt-4">
                    <InputLabel for="bukti_pembayaran" value="Bukti Pembayaran (Opsional)" />
                    <input 
                        id="bukti_pembayaran" 
                        type="file" 
                        @change="e => manualPayForm.bukti_pembayaran = e.target.files[0]"
                        class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" 
                        accept="image/*,.pdf"
                    />
                    <InputError :message="manualPayForm.errors.bukti_pembayaran" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showManualPayConfirmModal = false" type="button"> Batal </SecondaryButton>
                    <PrimaryButton @click="markAsPaid" :class="{ 'opacity-25': manualPayForm.processing }" :disabled="manualPayForm.processing" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                        Ya, Tandai Lunas
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal Copy Link Mutasi -->
        <Modal :show="isCopyModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <LinkIcon class="h-5 w-5 mr-2 text-indigo-500" />
                    Link Persetujuan Mutasi
                </h2>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Kirimkan link ini ke orang tua/wali murid agar mereka dapat meninjau dan menyetujui mutasi cabang/kelas.
                </p>

                <div class="flex items-center space-x-2">
                    <TextInput 
                        readonly 
                        :value="currentMutasiLink" 
                        class="block w-full text-sm text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400"
                        @focus="$event.target.select()"
                    />
                    <PrimaryButton @click="manualCopyLink" type="button" class="flex-shrink-0" :disabled="hasCopiedLink">
                        <ClipboardDocumentIcon v-if="!hasCopiedLink" class="h-4 w-4 mr-2" />
                        <CheckBadgeIcon v-else class="h-4 w-4 mr-2" />
                        {{ hasCopiedLink ? 'Tersalin!' : 'Copy' }}
                    </PrimaryButton>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" type="button"> Tutup </SecondaryButton>
                </div>
            </div>
        </Modal>
        <!-- Modal Konfirmasi Resign -->
        <Modal :show="showResignConfirmModal" @close="showResignConfirmModal = false" maxWidth="md">
            <div class="p-4 sm:p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Nonaktifkan / Resign Siswa
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menonaktifkan siswa <span class="font-semibold">{{ siswa.nama_siswa }}</span>?
                    <br><br>
                    Aksi ini akan merubah status siswa menjadi <strong>Non-Aktif</strong> dan secara otomatis <strong>membatalkan seluruh tagihan yang masih PENDING</strong> (belum dibayar) untuk siswa ini. Riwayat transaksi yang sudah lunas akan tetap tersimpan.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showResignConfirmModal = false" type="button"> Batal </SecondaryButton>
                    <PrimaryButton @click="submitResign" class="bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="resignForm.processing">
                        Ya, Nonaktifkan
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal Proses Keluar (Resign) -->
        <Modal :show="showProsesKeluarModal" @close="showProsesKeluarModal = false" maxWidth="md">
            <div class="p-4 sm:p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <ArrowRightOnRectangleIcon class="h-6 w-6 mr-2 text-red-500" />
                    Proses Keluar (Resign)
                </h2>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-md border border-gray-200 dark:border-gray-700 mb-4">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        Silakan tentukan tanggal pengunduran diri. Seluruh tagihan berstatus <strong>PENDING</strong> yang periodenya setelah tanggal tersebut akan dibatalkan/dihapus ketika wali menyetujui pengunduran diri.
                    </p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Keluar (Resign)</label>
                        <div class="flex gap-2">
                            <input type="date" v-model="resignDate" class="block w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <PrimaryButton @click="generateResignationLink" :disabled="isGeneratingUrl || !resignDate" type="button" class="whitespace-nowrap">
                                <span v-if="isGeneratingUrl">Loading...</span>
                                <span v-else>Generate Link</span>
                            </PrimaryButton>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2" v-if="generatedResignationUrl">
                        <input type="text" readonly :value="generatedResignationUrl" class="block w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <button @click="copyResignationUrl" type="button" class="inline-flex items-center p-2 border border-transparent rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Salin Tautan">
                            <ClipboardDocumentIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showProsesKeluarModal = false" type="button"> Tutup </SecondaryButton>
                    <PrimaryButton @click="sendResignationWa" :disabled="!generatedResignationUrl" class="bg-green-600 hover:bg-green-700 focus:ring-green-500 flex items-center disabled:opacity-50">
                        <ChatBubbleLeftEllipsisIcon class="h-4 w-4 mr-2" />
                        Kirim via WhatsApp
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Fullscreen ID Card Preview -->
        <div v-if="isPreviewIdCardOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4 transition-all">
            <div class="max-w-2xl w-full flex flex-col items-center gap-6 relative">
                <!-- Close Button on top right -->
                <button @click="isPreviewIdCardOpen = false" class="absolute -top-10 right-0 text-white hover:text-red-400 bg-black/40 hover:bg-black/60 rounded-full p-2 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- The Actual Printable ID Card -->
                <div id="student-id-card" class="bg-black rounded-2xl shadow-2xl text-white relative overflow-hidden group shrink-0" style="background: linear-gradient(135deg, #1a0000 0%, #660000 50%, #000000 100%); width: 500px; height: 315px;">

                    <!-- Soccer Ball top-left: viewBox shows bottom-right quarter → corner accent, data-ball attr for PDF pre-conversion -->
                    <svg data-ball="top-left" style="position: absolute; top: 0; left: 0; width: 110px; height: 110px; opacity: 0.25; pointer-events: none;" viewBox="36 36 37 37" xmlns="http://www.w3.org/2000/svg" fill="#dc2626">
                        <path d="M22.57,2.648c-4.489,1.82-8.517,4.496-11.971,7.949C7.144,14.051,4.471,18.08,2.65,22.568C0.892,26.904,0,31.486,0,36.186 c0,4.699,0.892,9.281,2.65,13.615c1.821,4.489,4.495,8.518,7.949,11.971c3.454,3.455,7.481,6.129,11.971,7.949 c4.336,1.76,8.917,2.649,13.617,2.649c4.7,0,9.28-0.892,13.616-2.649c4.488-1.82,8.518-4.494,11.971-7.949 c3.455-3.453,6.129-7.48,7.949-11.971c1.758-4.334,2.648-8.916,2.648-13.615c0-4.7-0.891-9.282-2.648-13.618 c-1.82-4.488-4.496-8.518-7.949-11.971s-7.479-6.129-11.971-7.949C45.467,0.891,40.887,0,36.187,0 C31.487,0,26.906,0.891,22.57,2.648z M9.044,51.419c-1.743-1.094-3.349-2.354-4.771-3.838c-2.172-6.112-2.54-12.729-1.101-19.01 c0.677-1.335,1.447-2.617,2.318-3.845c0.269-0.379,0.518-0.774,0.806-1.142l8.166,4.832c0,0.064,0,0.134,0,0.205 c-0.021,4.392,0.425,8.752,1.313,13.049c0.003,0.02,0.006,0.031,0.01,0.049l-6.333,9.93C9.314,51.579,9.177,51.503,9.044,51.419z M33.324,68.206c1.409,0.719,2.858,1.326,4.347,1.82c-6.325,0.275-12.713-1.207-18.36-4.447L33,68.018 C33.105,68.085,33.212,68.149,33.324,68.206z M33.274,65.735L17.12,62.856c-1.89-2.295-3.59-4.723-5.051-7.318 c-0.372-0.66-0.787-1.301-1.102-1.99l6.327-9.92c0.14,0.035,0.296,0.072,0.473,0.119c3.958,1.059,7.986,1.812,12.042,2.402 c0.237,0.033,0.435,0.062,0.604,0.08l7.584,13.113c-1.316,1.85-2.647,3.69-4.007,5.51C33.764,65.155,33.524,65.446,33.274,65.735z M60.15,60.149c-1.286,1.287-2.651,2.447-4.08,3.481c-0.237-1.894-0.646-3.75-1.223-5.563l8.092-15.096 c2.229-1.015,4.379-2.166,6.375-3.593c0.261-0.185,0.478-0.392,0.646-0.618C69.374,46.561,66.104,54.196,60.15,60.149z M59.791,40.571c0.301,0.574,0.598,1.154,0.896,1.742l-7.816,14.58c-0.045,0.01-0.088,0.02-0.133,0.026 c-4.225,0.789-8.484,1.209-12.779,1.229l-7.8-13.487c1.214-2.254,2.417-4.517,3.61-6.781c0.81-1.536,1.606-3.082,2.401-4.627 l16.143-1.658C56.29,34.495,58.163,37.457,59.791,40.571z M56.516,23.277c-0.766,2.023-1.586,4.025-2.401,6.031l-15.726,1.615 c-0.188-0.248-0.383-0.492-0.588-0.725c-1.857-2.103-3.726-4.193-5.592-6.289c0.017-0.021,0.034-0.037,0.051-0.056 c-0.753-0.752-1.508-1.504-2.261-2.258l4.378-13.181c0.302-0.08,0.606-0.147,0.913-0.18c2.38-0.242,4.763-0.516,7.149-0.654 c1.461-0.082,2.93-0.129,4.416-0.024l10.832,12.209C57.314,20.943,56.95,22.124,56.516,23.277z M60.15,12.221 c2.988,2.99,5.302,6.402,6.938,10.047c-2.024-1.393-4.188-2.539-6.463-3.473c-0.354-0.146-0.717-0.275-1.086-0.402L48.877,6.376 c0.074-0.519,0.113-1.039,0.129-1.563C53.062,6.464,56.864,8.936,60.15,12.221z M25.334,4.182c0.042,0.031,0.062,0.057,0.086,0.064 c2.437,0.842,4.654,2.082,6.744,3.553l-4.09,12.317c-0.021,0.006-0.041,0.012-0.061,0.021c-0.837,0.346-1.69,0.656-2.514,1.031 c-3.395,1.543-6.705,3.252-9.823,5.301l-8.071-4.775c0.012-0.252,0.055-0.508,0.141-0.736c0.542-1.444,1.075-2.896,1.688-4.311 c0.472-1.09,1.01-2.143,1.597-3.172c0.384-0.424,0.782-0.844,1.192-1.254c3.833-3.832,8.363-6.553,13.186-8.162 C25.384,4.098,25.358,4.139,25.334,4.182z"/>
                    </svg>

                    <!-- Soccer Ball bottom-right: viewBox shows top-left quarter → corner accent -->
                    <svg data-ball="bottom-right" style="position: absolute; bottom: 0; right: 0; width: 110px; height: 110px; opacity: 0.25; pointer-events: none;" viewBox="0 0 37 37" xmlns="http://www.w3.org/2000/svg" fill="#ef4444">
                        <path d="M22.57,2.648c-4.489,1.82-8.517,4.496-11.971,7.949C7.144,14.051,4.471,18.08,2.65,22.568C0.892,26.904,0,31.486,0,36.186 c0,4.699,0.892,9.281,2.65,13.615c1.821,4.489,4.495,8.518,7.949,11.971c3.454,3.455,7.481,6.129,11.971,7.949 c4.336,1.76,8.917,2.649,13.617,2.649c4.7,0,9.28-0.892,13.616-2.649c4.488-1.82,8.518-4.494,11.971-7.949 c3.455-3.453,6.129-7.48,7.949-11.971c1.758-4.334,2.648-8.916,2.648-13.615c0-4.7-0.891-9.282-2.648-13.618 c-1.82-4.488-4.496-8.518-7.949-11.971s-7.479-6.129-11.971-7.949C45.467,0.891,40.887,0,36.187,0 C31.487,0,26.906,0.891,22.57,2.648z M9.044,51.419c-1.743-1.094-3.349-2.354-4.771-3.838c-2.172-6.112-2.54-12.729-1.101-19.01 c0.677-1.335,1.447-2.617,2.318-3.845c0.269-0.379,0.518-0.774,0.806-1.142l8.166,4.832c0,0.064,0,0.134,0,0.205 c-0.021,4.392,0.425,8.752,1.313,13.049c0.003,0.02,0.006,0.031,0.01,0.049l-6.333,9.93C9.314,51.579,9.177,51.503,9.044,51.419z M33.324,68.206c1.409,0.719,2.858,1.326,4.347,1.82c-6.325,0.275-12.713-1.207-18.36-4.447L33,68.018 C33.105,68.085,33.212,68.149,33.324,68.206z M33.274,65.735L17.12,62.856c-1.89-2.295-3.59-4.723-5.051-7.318 c-0.372-0.66-0.787-1.301-1.102-1.99l6.327-9.92c0.14,0.035,0.296,0.072,0.473,0.119c3.958,1.059,7.986,1.812,12.042,2.402 c0.237,0.033,0.435,0.062,0.604,0.08l7.584,13.113c-1.316,1.85-2.647,3.69-4.007,5.51C33.764,65.155,33.524,65.446,33.274,65.735z M60.15,60.149c-1.286,1.287-2.651,2.447-4.08,3.481c-0.237-1.894-0.646-3.75-1.223-5.563l8.092-15.096 c2.229-1.015,4.379-2.166,6.375-3.593c0.261-0.185,0.478-0.392,0.646-0.618C69.374,46.561,66.104,54.196,60.15,60.149z M59.791,40.571c0.301,0.574,0.598,1.154,0.896,1.742l-7.816,14.58c-0.045,0.01-0.088,0.02-0.133,0.026 c-4.225,0.789-8.484,1.209-12.779,1.229l-7.8-13.487c1.214-2.254,2.417-4.517,3.61-6.781c0.81-1.536,1.606-3.082,2.401-4.627 l16.143-1.658C56.29,34.495,58.163,37.457,59.791,40.571z M56.516,23.277c-0.766,2.023-1.586,4.025-2.401,6.031l-15.726,1.615 c-0.188-0.248-0.383-0.492-0.588-0.725c-1.857-2.103-3.726-4.193-5.592-6.289c0.017-0.021,0.034-0.037,0.051-0.056 c-0.753-0.752-1.508-1.504-2.261-2.258l4.378-13.181c0.302-0.08,0.606-0.147,0.913-0.18c2.38-0.242,4.763-0.516,7.149-0.654 c1.461-0.082,2.93-0.129,4.416-0.024l10.832,12.209C57.314,20.943,56.95,22.124,56.516,23.277z M60.15,12.221 c2.988,2.99,5.302,6.402,6.938,10.047c-2.024-1.393-4.188-2.539-6.463-3.473c-0.354-0.146-0.717-0.275-1.086-0.402L48.877,6.376 c0.074-0.519,0.113-1.039,0.129-1.563C53.062,6.464,56.864,8.936,60.15,12.221z M25.334,4.182c0.042,0.031,0.062,0.057,0.086,0.064 c2.437,0.842,4.654,2.082,6.744,3.553l-4.09,12.317c-0.021,0.006-0.041,0.012-0.061,0.021c-0.837,0.346-1.69,0.656-2.514,1.031 c-3.395,1.543-6.705,3.252-9.823,5.301l-8.071-4.775c0.012-0.252,0.055-0.508,0.141-0.736c0.542-1.444,1.075-2.896,1.688-4.311 c0.472-1.09,1.01-2.143,1.597-3.172c0.384-0.424,0.782-0.844,1.192-1.254c3.833-3.832,8.363-6.553,13.186-8.162 C25.384,4.098,25.358,4.139,25.334,4.182z"/>
                    </svg>

                    <!-- Content Wrapper -->
                    <div style="position: relative; z-index: 10; padding: 20px 24px; display: flex; flex-direction: column; height: 100%; box-sizing: border-box;">
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4 style="font-weight: 900; font-size: 22px; letter-spacing: 0.2em; color: #ef4444; margin: 0; line-height: 1.2;">KARTU PELAJAR</h4>
                                <p style="font-size: 10px; color: #fca5a5; text-transform: uppercase; letter-spacing: 0.2em; font-weight: 600; margin: 4px 0 0 0;">Sistem Informasi Akademik</p>
                            </div>
                            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1);">
                                <QrCodeIcon class="w-7 h-7 text-white opacity-80" />
                            </div>
                        </div>

                        <!-- Spacer -->
                        <div style="flex: 1;"></div>

                        <!-- Body: Photo + Name / NIS / Badge -->
                        <div style="display: flex; align-items: flex-end; gap: 20px; padding-bottom: 8px;">
                            <!-- Photo -->
                            <div style="width: 80px; height: 100px; background: linear-gradient(135deg, #1f2937, #000000); border-radius: 10px; border: 1px solid rgba(239,68,68,0.3); overflow: hidden; flex-shrink: 0; position: relative;">
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                                    <UserIcon class="w-10 h-10" />
                                </div>
                            </div>

                            <!-- Name, NIS, Badge -->
                            <div style="flex: 1; min-width: 0;">
                                <h3 style="font-weight: 700; font-size: 20px; color: #ffffff; margin: 0 0 5px 0; line-height: 1.3; word-break: break-word;">{{ siswa.nama_siswa }}</h3>
                                <div style="font-size: 13px; color: #fecaca; font-family: monospace; letter-spacing: 0.15em; opacity: 0.8; margin-bottom: 10px;">{{ siswa.nis || 'NIS-PENDING' }}</div>
                                <!-- Badge: full-width, perfectly centered for html2canvas using table-cell -->
                                <div style="width: 100%; height: 32px; border-radius: 8px; background-color: rgba(220,38,38,0.25); border: 1px solid rgba(239,68,68,0.5); box-sizing: border-box; display: table;">
                                    <div style="display: table-cell; vertical-align: middle; text-align: center; color: #fee2e2; font-size: 13px; font-weight: 600;">
                                        {{ siswa.kelas_nama || 'Belum Ada Kelas' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4 w-full max-w-[500px] justify-center mt-4">
                    <button @click="isPreviewIdCardOpen = false" class="px-6 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium border border-white/20 backdrop-blur-sm transition">
                        Tutup
                    </button>
                    <button @click="downloadIdCard" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold shadow-lg shadow-red-600/30 flex items-center gap-2 transition">
                        <ArrowDownTrayIcon class="w-5 h-5" /> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
