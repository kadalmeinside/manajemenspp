<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Kata Sandi" />

        <div class="w-full max-w-md rounded-xl bg-white p-4 dark:bg-slate-800">
            <div class="mb-8 text-center">
                <div class="px-12">
                    <Link href="/">
                        <img class="h-auto w-auto dark:hidden" src="/images/logo-black.png" alt="Persija Development">
                        <img class="h-auto w-auto hidden dark:block" src="/images/logo-white.png" alt="Persija Development">
                    </Link>
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Lupa kata sandi? Tidak masalah. Beri tahu kami alamat email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
                </p>
                <div v-if="status" class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-700 dark:text-green-50">
                    {{ status }}
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="email" value="Alamat Email" class="block text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@contoh.com"
                    />
                    <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.email" />
                </div>

                <div>
                    <PrimaryButton
                        class="group relative flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-3 px-4 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150 ease-in-out dark:bg-indigo-500 dark:hover:bg-indigo-600 dark:focus:ring-offset-slate-800"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Memproses...' : 'Kirim Tautan Atur Ulang Kata Sandi' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>