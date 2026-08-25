<script setup>
import { ref } from 'vue'; // Ditambahkan untuk reaktivitas showPassword
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue'; // Pastikan path ini benar
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// State untuk menampilkan/menyembunyikan password
const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            showPassword.value = false;
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Akun" />

        <div class="w-full flex flex-col justify-center py-4 lg:py-0">
             <div class="mb-8 lg:mb-10 text-center lg:text-left">
                <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Selamat Datang!
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Silakan masukkan email dan kata sandi Anda untuk mengakses Portal Soccer School.
                </p>
                <p v-if="status" class="mt-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm font-medium text-green-800">
                    {{ status }}
                </p>
            </div>

            <!-- Form Login -->
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <!-- Input Email -->
                <div>
                    <InputLabel for="email" value="Alamat Email" class="block text-xs lg:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" />
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <EnvelopeIcon class="h-4 w-4 lg:h-5 lg:w-5 text-gray-400" aria-hidden="true" />
                        </div>
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-xl border-gray-300 bg-white pl-10 lg:pl-11 pr-4 py-2.5 lg:py-3.5 text-gray-900 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm transition-all"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="contoh: nama@email.com"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600" :message="form.errors.email" />
                </div>

                <!-- Input Password dengan Tombol Show/Hide -->
                <div>
                    <InputLabel for="password" value="Kata Sandi" class="block text-xs lg:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" />
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <LockClosedIcon class="h-4 w-4 lg:h-5 lg:w-5 text-gray-400" aria-hidden="true" />
                        </div>
                        <TextInput
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full rounded-xl border-gray-300 bg-white pl-10 lg:pl-11 pr-10 py-2.5 lg:py-3.5 text-gray-900 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm transition-all"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-red-500 transition-colors focus:outline-none"
                            aria-label="Toggle password visibility"
                        >
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600" :message="form.errors.password" />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between mt-1.5">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" class="text-red-600 focus:ring-red-500 rounded border-gray-300 w-4 h-4" />
                        <span class="ml-2 text-xs lg:text-sm text-gray-600 dark:text-gray-400">Ingat Saya</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs lg:text-sm font-semibold text-red-600 hover:text-red-500 hover:underline transition-all"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <!-- Action Button -->
                <div class="pt-1.5">
                    <PrimaryButton 
                        class="w-full flex justify-center items-center py-2.5 lg:py-3.5 px-4 rounded-xl text-sm lg:text-base font-bold text-white bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 focus:ring-4 focus:ring-red-500/30 shadow-lg shadow-red-500/40 transform transition-all duration-200" 
                        :class="{ 'opacity-75 cursor-not-allowed scale-95': form.processing }" 
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 lg:h-5 lg:w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Memproses...</span>
                        <span v-else>Masuk ke Akun</span>
                    </PrimaryButton>
                </div>

                <!-- Separator -->
                <div class="mt-5 lg:mt-8 flex items-center justify-center space-x-4">
                    <span class="h-px w-full bg-gray-200 dark:bg-gray-700"></span>
                    <span class="text-[11px] lg:text-sm font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Atau masuk dengan</span>
                    <span class="h-px w-full bg-gray-200 dark:bg-gray-700"></span>
                </div>

                <!-- Google Login Button -->
                <div class="mt-4 lg:mt-6">
                    <a 
                        href="/auth/google"
                        class="w-full flex items-center justify-center py-2.5 lg:py-3.5 px-4 rounded-xl text-sm lg:text-base font-bold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <svg class="h-4 w-4 lg:h-5 lg:w-5 mr-2 lg:mr-3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.15v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.15C1.43 8.55 1 10.22 1 12s.43 3.45 1.15 4.93l3.69-2.84z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.15 7.07l3.69 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Masuk dengan Google
                    </a>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
