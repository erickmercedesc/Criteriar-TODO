<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Key, Bot, Copy, Check } from 'lucide-vue-next';

const props = defineProps({
    user: Object,
});

const confirmingRegeneration = ref(false);
const copiedMcp = ref(false);
const copiedKey = ref(false);

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
    copiedKey.value = true;
    setTimeout(() => {
        copiedKey.value = false;
    }, 2000);
};

const mcpConfigSnippet = computed(() => {
    const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost:8000';
    return JSON.stringify({
        mcpServers: {
            secondbrain: {
                serverUrl: `${origin}/api/mcp/sse?api_token=${props.user?.api_token || 'TU_API_TOKEN'}`
            }
        }
    }, null, 2);
});

const copyMcpConfig = () => {
    navigator.clipboard.writeText(mcpConfigSnippet.value);
    copiedMcp.value = true;
    setTimeout(() => {
        copiedMcp.value = false;
    }, 2000);
};
</script>

<template>
    <ActionSection>
        <template #title>
            <div class="flex items-center gap-2">
                <Key class="w-5 h-5 text-[#6C63FF]" />
                API Key & Servidor MCP
            </div>
        </template>

        <template #description>
            Administra tu clave de acceso a la API y conecta agentes de IA (Antigravity, Cursor, Claude) vía MCP.
        </template>

        <template #content>
            <h3 class="text-lg font-medium text-[var(--color-text-primary)]">
                Credenciales de acceso a la API
            </h3>

            <div class="mt-3 max-w-xl text-sm text-[var(--color-text-muted)]">
                <p>
                    Puedes usar tu API Key para acceder a tus tareas y criterios desde aplicaciones externas o conectar tu asistente de IA personal.
                </p>
            </div>

            <div class="mt-5" v-if="user.api_token">
                <div class="flex items-center mt-2 p-3 bg-[var(--color-surface-2)] rounded-md border border-[var(--color-border)]">
                    <span class="font-mono text-sm text-[var(--color-text-primary)] break-all">{{ user.api_token }}</span>
                    <button @click="copyToClipboard(user.api_token)" class="ml-4 flex-shrink-0 text-[var(--color-primary)] hover:opacity-80 text-sm font-medium inline-flex items-center gap-1">
                        <Check v-if="copiedKey" class="w-4 h-4 text-[#22C55E]" />
                        <Copy v-else class="w-4 h-4" />
                        <span>{{ copiedKey ? '¡Copiado!' : 'Copiar' }}</span>
                    </button>
                </div>

                <!-- MCP Server Integration Card -->
                <div class="mt-6 p-4 rounded-xl bg-[#13151F] border border-[#2E3347]">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <Bot class="w-4 h-4 text-[#22C55E]" />
                            <h4 class="text-[14px] font-bold text-[#F0F2F8]">Conexión MCP para Agentes de IA</h4>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-[#22C55E]/10 text-[#22C55E] border border-[#22C55E]/20 font-semibold">
                            Nativo & Online
                        </span>
                    </div>
                    <p class="text-[13px] text-[#7B82A0] mb-3 leading-relaxed">
                        Conecta tu IA favorita (<strong class="text-[#F0F2F8]">Antigravity</strong>, <strong class="text-[#F0F2F8]">Cursor</strong>, <strong class="text-[#F0F2F8]">Claude Desktop</strong> o <strong class="text-[#F0F2F8]">Windsurf</strong>) directamente a tu cuenta pegando esta configuración:
                    </p>
                    
                    <div class="bg-[#1A1D27] p-3 rounded-lg border border-[#2E3347] font-mono text-[12px] text-[#F0F2F8] relative">
                        <pre class="overflow-x-auto leading-relaxed text-[#38BDF8]"><code>{{ mcpConfigSnippet }}</code></pre>
                        
                        <div class="mt-3 pt-2 border-t border-[#2E3347] flex items-center justify-between">
                            <span class="text-[11px] text-[#7B82A0]">Pega esto en tu <code>mcp_config.json</code></span>
                            <button @click="copyMcpConfig"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#6C63FF] hover:bg-[#6C63FF]/90 text-white text-xs font-semibold transition-all">
                                <Check v-if="copiedMcp" class="w-3.5 h-3.5" />
                                <Copy v-else class="w-3.5 h-3.5" />
                                <span>{{ copiedMcp ? '¡Configuración Copiada!' : 'Copiar Configuración MCP' }}</span>
                            </button>
                        </div>
                    </div>
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
                        ¿Estás seguro de que deseas regenerar tu API Key? Tu clave actual dejará de funcionar inmediatamente y cualquier aplicación o agente de IA que la esté utilizando perderá el acceso hasta ser actualizada.
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
