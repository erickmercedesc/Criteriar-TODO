<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Save, Timer, Settings2 } from 'lucide-vue-next';
import ActionMessage from '@/Components/ActionMessage.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    pomo_time: props.settings.pomo_time,
    short_break_time: props.settings.short_break_time,
    long_break_time: props.settings.long_break_time,
    pomodoro_webhook: props.settings.pomodoro_webhook,
});

const submit = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Configuración" />

    <AppLayout title="Configuración">
        <template #header>
            <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter flex items-center gap-2">
                <Settings2 class="w-6 h-6 text-[#6C63FF]" />
                Configuración del Sistema
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Pomodoro Settings Card -->
                <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] overflow-hidden shadow-[0_8px_32px_rgba(0,0,0,0.3)]">
                    <div class="p-8 border-b border-[#2E3347] bg-[#1A1D27]/50">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-[#6C63FF]/10 rounded-[12px] flex items-center justify-center">
                                <Timer class="w-5 h-5 text-[#6C63FF]" />
                            </div>
                            <h3 class="text-[18px] font-bold text-[#F0F2F8]">Ajustes de Pomodoro</h3>
                        </div>
                        <p class="text-[14px] text-[#7B82A0] ml-13">
                            Personaliza la duración de tus ciclos de concentración y descanso.
                        </p>
                    </div>

                    <div class="p-8">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <!-- Focus Time -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <div class="md:col-span-1">
                                    <label class="block text-[14px] font-bold text-[#F0F2F8] mb-1">Focus Time (min)</label>
                                    <p class="text-[12px] text-[#7B82A0]">Tiempo de concentración.</p>
                                </div>
                                <div class="md:col-span-2">
                                    <input type="number" v-model="form.pomo_time" min="1" max="120"
                                        class="w-full bg-[#0F1117] border-[#2E3347] text-[#F0F2F8] focus:border-[#6C63FF] focus:ring-[#6C63FF] rounded-[12px] px-4 py-3 font-mono text-[16px]" />
                                    <div v-if="form.errors.pomo_time" class="text-red-500 text-[12px] mt-1">{{ form.errors.pomo_time }}</div>
                                </div>
                            </div>

                            <!-- Short Break -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <div class="md:col-span-1">
                                    <label class="block text-[14px] font-bold text-[#F0F2F8] mb-1">Short Break (min)</label>
                                    <p class="text-[12px] text-[#7B82A0]">Descanso entre ciclos.</p>
                                </div>
                                <div class="md:col-span-2">
                                    <input type="number" v-model="form.short_break_time" min="1" max="60"
                                        class="w-full bg-[#0F1117] border-[#2E3347] text-[#F0F2F8] focus:border-[#22C55E] focus:ring-[#22C55E] rounded-[12px] px-4 py-3 font-mono text-[16px]" />
                                    <div v-if="form.errors.short_break_time" class="text-red-500 text-[12px] mt-1">{{ form.errors.short_break_time }}</div>
                                </div>
                            </div>

                            <!-- Long Break -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <div class="md:col-span-1">
                                    <label class="block text-[14px] font-bold text-[#F0F2F8] mb-1">Long Break (min)</label>
                                    <p class="text-[12px] text-[#7B82A0]">Cada 4 ciclos de focus.</p>
                                </div>
                                <div class="md:col-span-2">
                                    <input type="number" v-model="form.long_break_time" min="1" max="60"
                                        class="w-full bg-[#0F1117] border-[#2E3347] text-[#F0F2F8] focus:border-[#3B82F6] focus:ring-[#3B82F6] rounded-[12px] px-4 py-3 font-mono text-[16px]" />
                                    <div v-if="form.errors.long_break_time" class="text-red-500 text-[12px] mt-1">{{ form.errors.long_break_time }}</div>
                                </div>
                            </div>

                            <!-- Webhook URL -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <div class="md:col-span-1">
                                    <label class="block text-[14px] font-bold text-[#F0F2F8] mb-1">Webhook URL</label>
                                    <p class="text-[12px] text-[#7B82A0]">Se ejecutará al terminar el temporizador.</p>
                                </div>
                                <div class="md:col-span-2">
                                    <input type="url" v-model="form.pomodoro_webhook" placeholder="https://..."
                                        class="w-full bg-[#0F1117] border-[#2E3347] text-[#F0F2F8] focus:border-[#6C63FF] focus:ring-[#6C63FF] rounded-[12px] px-4 py-3 font-mono text-[14px]" />
                                    <div v-if="form.errors.pomodoro_webhook" class="text-red-500 text-[12px] mt-1">{{ form.errors.pomodoro_webhook }}</div>
                                </div>
                            </div>

                            <div class="pt-6 mt-6 border-t border-[#2E3347] flex items-center justify-end gap-4">
                                <ActionMessage :on="form.recentlySuccessful" class="mr-3">
                                    Guardado.
                                </ActionMessage>
                                
                                <button type="submit" :disabled="form.processing"
                                        class="bg-[#6C63FF] hover:bg-[#5A51E6] text-white px-6 py-3 rounded-[12px] text-[14px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.3)] flex items-center justify-center gap-2 disabled:opacity-50">
                                    <Save class="w-4 h-4" />
                                    Guardar Cambios
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
