<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import BankLogo from '@/Components/BankLogo.vue';
import { XMarkIcon, CreditCardIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    activeGateway: {
        type: String,
        default: 'midtrans_custom',
    },
    mdrConfig: {
        type: Object,
        default: () => ({}),
    },
    module: {
        type: String,
        default: 'spp', // 'spp' | 'pendaftaran' | 'store'
    },
    baseAmount: {
        type: Number,
        default: 0,
    },
    baseAmountLabel: {
        type: String,
        default: 'Total Tagihan',
    },
    paymentType: {
        type: String,
        default: 'VA',
    },
    bankCode: {
        type: String,
        default: 'BCA',
    },
    processing: {
        type: Boolean,
        default: false,
    },
    themeColor: {
        type: String,
        default: 'red', // 'red' | 'emerald'
    },
});

const emit = defineEmits([
    'update:show',
    'update:paymentType',
    'update:bankCode',
    'submit',
]);

const closeModal = () => {
    emit('update:show', false);
};

const selectMethod = (type, bank = '') => {
    emit('update:paymentType', type);
    emit('update:bankCode', bank);
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(val || 0);
};

const isVaEnabled = computed(() => props.mdrConfig?.enable_va !== false);

const isMdrEnabled = computed(() => {
    if (!props.mdrConfig?.enabled) return false;
    const isMdrGateway = ['midtrans', 'midtrans_custom', 'gapura'].includes(props.activeGateway);
    if (!isMdrGateway) return false;

    if (props.module === 'pendaftaran') {
        return !!props.mdrConfig?.enable_pendaftaran;
    }
    if (props.module === 'spp') {
        return !!props.mdrConfig?.enable_spp;
    }
    if (props.module === 'store') {
        return !!props.mdrConfig?.enable_store;
    }
    return false;
});

const calculatedAdminFee = computed(() => {
    if (!isMdrEnabled.value) return 0;
    if (props.paymentType === 'QRIS') {
        const pct = Number(props.mdrConfig?.qris_percent ?? 0.7);
        return Math.ceil(props.baseAmount * (pct / 100));
    }
    if (props.paymentType === 'VA') {
        return Math.round(Number(props.mdrConfig?.va_flat ?? 4440));
    }
    return 0;
});

const totalAmount = computed(() => props.baseAmount + calculatedAdminFee.value);

const isSelected = (type, bank = '') => {
    if (type === 'QRIS') {
        return props.paymentType === 'QRIS';
    }
    return props.paymentType === 'VA' && props.bankCode === bank;
};

const getButtonClass = (type, bank = '') => {
    const selected = isSelected(type, bank);
    if (selected) {
        if (props.themeColor === 'emerald') {
            return 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/20 shadow-sm dark:bg-emerald-900/30';
        }
        return 'border-red-500 bg-red-50 ring-2 ring-red-500/20 shadow-sm dark:bg-red-950/30';
    }
    return 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800';
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="md">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Pilih Metode Pembayaran</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pembayaran terverifikasi otomatis secara instan</p>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>
            
            <div v-if="['midtrans', 'midtrans_custom', 'gapura'].includes(activeGateway)" class="w-full">
                <!-- Grid Pilihan Bank & QRIS -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mb-5">
                    <template v-if="isVaEnabled">
                        <button
                            type="button"
                            @click="selectMethod('VA', 'BCA')"
                            :class="['p-3 rounded-xl border transition-all flex items-center justify-center cursor-pointer', getButtonClass('VA', 'BCA')]"
                        >
                            <BankLogo bank="BCA" class="h-6 w-auto max-w-[80px]" />
                        </button>
                        <button
                            type="button"
                            @click="selectMethod('VA', 'MANDIRI')"
                            :class="['p-3 rounded-xl border transition-all flex items-center justify-center cursor-pointer', getButtonClass('VA', 'MANDIRI')]"
                        >
                            <BankLogo bank="MANDIRI" class="h-6 w-auto max-w-[80px]" />
                        </button>
                        <button
                            type="button"
                            @click="selectMethod('VA', 'BNI')"
                            :class="['p-3 rounded-xl border transition-all flex items-center justify-center cursor-pointer', getButtonClass('VA', 'BNI')]"
                        >
                            <BankLogo bank="BNI" class="h-6 w-auto max-w-[80px]" />
                        </button>
                        <button
                            v-if="['midtrans', 'midtrans_custom'].includes(activeGateway)"
                            type="button"
                            @click="selectMethod('VA', 'BRI')"
                            :class="['p-3 rounded-xl border transition-all flex items-center justify-center cursor-pointer', getButtonClass('VA', 'BRI')]"
                        >
                            <BankLogo bank="BRI" class="h-6 w-auto max-w-[80px]" />
                        </button>
                        <button
                            v-if="['midtrans', 'midtrans_custom'].includes(activeGateway)"
                            type="button"
                            @click="selectMethod('VA', 'PERMATA')"
                            :class="['p-3 rounded-xl border transition-all flex items-center justify-center cursor-pointer', getButtonClass('VA', 'PERMATA')]"
                        >
                            <BankLogo bank="PERMATA" class="h-6 w-auto max-w-[80px]" />
                        </button>
                    </template>
                    <button
                        type="button"
                        @click="selectMethod('QRIS', '')"
                        :class="['p-3 rounded-xl border transition-all flex items-center justify-center cursor-pointer', getButtonClass('QRIS')]"
                    >
                        <BankLogo bank="QRIS" class="h-6 w-auto max-w-[80px]" />
                    </button>
                </div>

                <!-- Rincian Tagihan & Biaya Layanan (MDR) -->
                <div class="mb-5 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>{{ baseAmountLabel }}</span>
                        <span class="font-medium text-gray-900 dark:text-gray-200">{{ formatCurrency(baseAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>
                            Biaya Layanan
                            <template v-if="isMdrEnabled">
                                ({{ paymentType === 'QRIS' ? `QRIS ${mdrConfig.qris_percent}%` : `VA ${bankCode}` }})
                            </template>
                        </span>
                        <span class="font-medium" :class="calculatedAdminFee === 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-900 dark:text-gray-200'">
                            {{ calculatedAdminFee === 0 ? 'Gratis (Rp 0)' : formatCurrency(calculatedAdminFee) }}
                        </span>
                    </div>
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center font-bold text-base text-gray-900 dark:text-white">
                        <span>Total Bayar</span>
                        <span :class="themeColor === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                            {{ formatCurrency(totalAmount) }}
                        </span>
                    </div>
                </div>

                <!-- Tombol Lanjutkan Pembayaran -->
                <button
                    type="button"
                    @click="emit('submit')"
                    :disabled="processing"
                    class="w-full relative group overflow-hidden rounded-2xl p-[2px] transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span
                        class="absolute inset-0 rounded-2xl opacity-75 group-hover:opacity-100 blur transition-opacity duration-300"
                        :class="themeColor === 'emerald' ? 'bg-gradient-to-r from-green-400 via-emerald-400 to-teal-400' : 'bg-gradient-to-r from-red-500 via-rose-500 to-orange-500'"
                    ></span>
                    <div
                        class="relative text-white font-bold px-8 py-3.5 rounded-[14px] flex items-center justify-center transition-all shadow-inner border border-white/20"
                        :class="themeColor === 'emerald' ? 'bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400' : 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500'"
                    >
                        <CreditCardIcon v-if="!processing" class="h-5 w-5 mr-2 group-hover:animate-pulse" />
                        <svg v-else class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="tracking-wide">{{ processing ? 'Memproses...' : 'Lanjutkan Pembayaran' }}</span>
                    </div>
                </button>
            </div>
            <div v-else class="text-center py-6 text-gray-500">
                Sistem pembayaran sedang tidak tersedia.
            </div>
        </div>
    </Modal>
</template>

