<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

// State untuk menampilkan/menyembunyikan password
const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};


const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Atur Ulang Kata Sandi" />

        <div class="w-full max-w-md rounded-xl bg-white p-4 dark:bg-slate-800">
            <div class="mb-8 text-center">
                <div class="px-12">
                    <Link href="/">
                        <img class="h-auto w-auto dark:hidden" src="/images/logo-black.png" alt="Persija Development">
                        <img class="h-auto w-auto hidden dark:block" src="/images/logo-white.png" alt="Persija Development">
                    </Link>
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Silakan masukkan kata sandi baru Anda di bawah ini.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="email" value="Alamat Email" class="block text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 bg-gray-100 dark:bg-slate-900"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        readonly
                        disabled
                    />
                    <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Kata Sandi Baru" class="block text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <div class="relative mt-1">
                        <TextInput
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 pr-10 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <button type="button" @click="togglePasswordVisibility" class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-200">
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>
                    </div>
                    <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi Baru" class="block text-sm font-medium text-gray-700 dark:text-gray-300" />
                     <div class="relative mt-1">
                        <TextInput
                            id="password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 pr-10 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </div>
                    <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.password_confirmation" />
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
                        {{ form.processing ? 'Memproses...' : 'Atur Ulang Kata Sandi' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>