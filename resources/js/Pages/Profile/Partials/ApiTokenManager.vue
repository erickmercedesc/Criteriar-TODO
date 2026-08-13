<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Key } from 'lucide-vue-next';

defineProps({
    user: Object,
});

const confirmingRegeneration = ref(false);

const form = useForm({});

const confirmRegeneration = () => {
    confirmingRegeneration.value = true;
};

const regenerateToken = () => {
    form.post(route('user.api-token.store'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingRegeneration.value = false;
        },
    });
};

const copyToClipboard = (token) => {
    navigator.clipboard.writeText(token);
    alert('API Key copiada al portapapeles.');
};
</script>

<template>
    <ActionSection>
        <template #title>
            <div class="flex items-center gap-2">
                <Key class="w-5 h-5" />
                API Key
            </div>
        </template>

        <template #description>
            Administra tu clave de acceso a la API para integraciones de terceros.
        </template>

        <template #content>
            <h3 class="text-lg font-medium text-[var(--color-text-primary)]">
                Credenciales de acceso a la API
            </h3>

            <div class="mt-3 max-w-xl text-sm text-[var(--color-text-muted)]">
                <p>
                    Puedes usar tu API Key para acceder a tus tareas y criterios desde aplicaciones externas. Por razones de seguridad, si generas una nueva clave, la anterior dejará de funcionar inmediatamente.
                </p>
            </div>

            <div class="mt-5" v-if="user.api_token">
                <div class="flex items-center mt-2 p-3 bg-[var(--color-surface-2)] rounded-md border border-[var(--color-border)]">
                    <span class="font-mono text-sm text-[var(--color-text-primary)] break-all">{{ user.api_token }}</span>
                    <button @click="copyToClipboard(user.api_token)" class="ml-4 flex-shrink-0 text-[var(--color-primary)] hover:opacity-80 text-sm font-medium">
                        Copiar
                    </button>
                </div>
            </div>
            
            <div class="mt-5" v-else>
                <p class="text-sm text-[var(--color-text-muted)]">Aún no has generado una API Key.</p>
            </div>

            <div class="mt-5">
                <PrimaryButton @click="confirmRegeneration">
                    {{ user.api_token ? 'Regenerar API Key' : 'Generar API Key' }}
                </PrimaryButton>
            </div>

            <!-- Confirmation Modal -->
            <DialogModal :show="confirmingRegeneration" @close="confirmingRegeneration = false">
                <template #title>
                    {{ user.api_token ? 'Regenerar API Key' : 'Generar API Key' }}
                </template>

                <template #content>
                    <span v-if="user.api_token">
                        ¿Estás seguro de que deseas regenerar tu API Key? Tu clave actual dejará de funcionar inmediatamente y cualquier aplicación que la esté utilizando perderá el acceso hasta ser actualizada.
                    </span>
                    <span v-else>
                        ¿Estás seguro de que deseas generar tu primera API Key?
                    </span>
                </template>

                <template #footer>
                    <SecondaryButton @click="confirmingRegeneration = false">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="regenerateToken"
                    >
                        Confirmar
                    </PrimaryButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
