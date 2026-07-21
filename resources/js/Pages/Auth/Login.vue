<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="text-center mb-2">
                <h1 class="text-[28px] font-bold text-[#F0F2F8] font-inter">Bienvenido</h1>
                <p class="text-[#7B82A0] text-[15px] mt-2">Accede a tu SecondBrain</p>
            </div>

            <div>
                <InputLabel for="email" value="Correo electrónico" class="text-[#7B82A0] mb-2 block" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full bg-[#0F1117] border-[#2E3347] text-[#F0F2F8] focus:border-[#6C63FF] focus:ring-[#6C63FF] rounded-[10px] px-4 py-3"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="tu@email.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <InputLabel for="password" value="Contraseña" class="text-[#7B82A0] mb-0" />
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-[13px] text-[#6C63FF] hover:text-[#837BFF] transition-colors font-medium">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="block w-full bg-[#0F1117] border-[#2E3347] text-[#F0F2F8] focus:border-[#6C63FF] focus:ring-[#6C63FF] rounded-[10px] px-4 py-3"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block">
                <label class="flex items-center cursor-pointer group">
                    <Checkbox v-model:checked="form.remember" name="remember" class="border-[#2E3347] bg-[#0F1117] text-[#6C63FF] focus:ring-[#6C63FF]" />
                    <span class="ms-3 text-[14px] text-[#7B82A0] group-hover:text-[#F0F2F8] transition-colors">Mantener sesión iniciada</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-[#6C63FF] hover:bg-[#5A51E6] text-white font-bold text-[16px] py-4 rounded-[12px] shadow-[0_4px_14px_rgba(108,99,255,0.4)] transition-all transform hover:-translate-y-0.5 disabled:opacity-50 mt-2" :disabled="form.processing">
                <span v-if="form.processing">Iniciando sesión...</span>
                <span v-else>Iniciar Sesión</span>
            </button>
        </form>
    </AuthenticationCard>
</template>
