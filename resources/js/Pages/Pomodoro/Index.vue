<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Play, Pause, Square, Coffee, Brain, ArrowRight, Flame, SkipForward, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    topTask: Object,
    initialState: Object,
});

const state = ref(props.initialState);
const currentRemaining = ref(props.initialState.remaining_seconds || 0);

const showComplexTaskModal = ref(false);

// Timer and Polling references
let localTimer = null;
let pollTimer = null;

// Audio instance for the alarm sound
let alarmAudio = null;

/**
 * Initializes the audio element on user interaction to prepare for playback.
 * @returns {void}
 */
const initAudio = () => {
    if (!alarmAudio) {
        alarmAudio = new Audio('/alarm.wav');
        // Preload to ensure it is ready when the timer finishes
        alarmAudio.load();
    }
};

/**
 * Plays the alarm.wav sound from the public directory.
 * @returns {void}
 */
const playDing = () => {
    try {
        if (!alarmAudio) initAudio();
        
        // Reset time in case it is already playing
        alarmAudio.currentTime = 0;
        const playPromise = alarmAudio.play();
        
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.error("Audio playback failed or blocked", error);
            });
        }
    } catch (e) {
        console.error("Audio playback failed", e);
    }
};

const updateLocalTime = () => {
    if (state.value.status === 'waiting') {
        currentRemaining.value = state.value.remaining_seconds || (state.value.phase === 'focus' ? 25 * 60 : (state.value.phase === 'long_break' ? 15 * 60 : 5 * 60)); // fallback visual
        return;
    }

    if (state.value.status === 'paused') {
        currentRemaining.value = state.value.remaining_seconds;
        return;
    }

    if (state.value.status === 'running') {
        const now = Math.floor(Date.now() / 1000);
        const diff = state.value.ends_at - now;

        if (diff <= 0) {
            if (currentRemaining.value > 0) {
                // Just hit zero
                playDing();
                
                const wasFocus = state.value.phase === 'focus';
                const isComplex = props.topTask && props.topTask.criteria && props.topTask.criteria.some(c => c.is_complex_marker);
                
                fetchState().then(() => {
                    if (wasFocus && isComplex) {
                        showComplexTaskModal.value = true;
                    }
                }); // Force a sync so the backend intercepts expiration
            }
            currentRemaining.value = 0;
        } else {
            currentRemaining.value = diff;
        }
    }
};

const fetchState = async () => {
    try {
        const response = await axios.get(route('pomodoro.state'));
        state.value = response.data;
        updateLocalTime();
    } catch (error) {
        console.error("Failed to sync pomodoro state", error);
    }
};

import { useWebPush } from '@/Composables/useWebPush';

const { initWebPush } = useWebPush();

/**
 * Sends a control action to the Pomodoro backend (start, pause, resume, skip, stop).
 * @param {string} action - The action to perform.
 * @param {Object} data - Additional data for the request.
 * @returns {Promise<void>}
 */
const sendAction = async (action, data = {}) => {
    try {
        if (action === 'start') {
            await initWebPush();
        }

        // Initialize audio on first user gesture
        initAudio();

        // Stop the alarm if it's currently ringing
        if (alarmAudio) {
            alarmAudio.pause();
            alarmAudio.currentTime = 0;
        }

        // If action includes a dash, we must use route helpers carefully or just let it map via route name
        // However, route name 'pomodoro.nextPhase' maps to '/api/pomodoro/next-phase'
        const routeName = action === 'next-phase' ? 'pomodoro.nextPhase' : `pomodoro.${action}`;
        const response = await axios.post(route(routeName), data);
        state.value = response.data;
        updateLocalTime();
    } catch (error) {
        console.error(`Failed to ${action} pomodoro`, error);
    }
};

const skipComplexTask = async () => {
    if (!props.topTask) {
        showComplexTaskModal.value = false;
        return;
    }
    
    try {
        await axios.post(route('pomodoro.skipTask'), {
            task_id: props.topTask.id
        });
        
        // Reload to get the new topTask from backend
        router.reload({ only: ['topTask', 'initialState'] });
        showComplexTaskModal.value = false;
    } catch (error) {
        console.error("Failed to skip complex task", error);
    }
};

onMounted(() => {
    localTimer = setInterval(updateLocalTime, 1000);
    updateLocalTime();
    pollTimer = setInterval(fetchState, 15000);
});

onUnmounted(() => {
    clearInterval(localTimer);
    clearInterval(pollTimer);
});

// Format MM:SS
const formattedTime = computed(() => {
    const m = Math.floor(currentRemaining.value / 60).toString().padStart(2, '0');
    const s = (currentRemaining.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

// Computed properties for UI
const isFocus = computed(() => state.value.phase === 'focus');
const isShortBreak = computed(() => state.value.phase === 'short_break');
const isLongBreak = computed(() => state.value.phase === 'long_break');
const isBreak = computed(() => isShortBreak.value || isLongBreak.value);

const isWaiting = computed(() => state.value.status === 'waiting');
const isRunning = computed(() => state.value.status === 'running');
const isPaused = computed(() => state.value.status === 'paused');

// Focus cycles
const currentCycle = computed(() => (state.value.focus_cycles % 4) + 1);
</script>

<template>
    <Head title="Pomodoro" />

    <AppLayout title="Pomodoro">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                    Pomodoro Focus
                </h2>
                <!-- Cycle Indicator -->
                <div class="bg-[#1A1D27] border border-[#2E3347] px-4 py-2 rounded-[12px] text-[13px] font-bold text-[#7B82A0]">
                    Ciclo <span class="text-[#F0F2F8]">{{ currentCycle }}</span> <span class="opacity-50">/ 4</span>
                </div>
            </div>
        </template>

        <div class="py-12 md:py-16">
            <div class="max-w-[800px] mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Timer Card -->
                <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[32px] p-10 md:p-16 flex flex-col items-center justify-center text-center shadow-[0_8px_32px_rgba(0,0,0,0.3)] relative overflow-hidden transition-all duration-500"
                     :class="{
                         'border-[#6C63FF]/50 shadow-[0_0_40px_rgba(108,99,255,0.15)]': isFocus && isRunning,
                         'border-[#22C55E]/50 shadow-[0_0_40px_rgba(34,197,94,0.15)]': isShortBreak && isRunning,
                         'border-[#3B82F6]/50 shadow-[0_0_40px_rgba(59,130,246,0.15)]': isLongBreak && isRunning,
                         'border-[#F59E0B]/50 shadow-[0_0_40px_rgba(245,158,11,0.15)]': isFinished,
                     }">
                    
                    <!-- Ambient glows based on state -->
                    <div v-if="isFocus && isRunning" class="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] bg-[#6C63FF] rounded-full mix-blend-screen filter blur-[150px] opacity-20"></div>
                    <div v-if="isShortBreak && isRunning" class="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] bg-[#22C55E] rounded-full mix-blend-screen filter blur-[150px] opacity-20"></div>
                    <div v-if="isLongBreak && isRunning" class="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] bg-[#3B82F6] rounded-full mix-blend-screen filter blur-[150px] opacity-20"></div>

                    <!-- Status Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[13px] font-bold uppercase tracking-widest mb-10 transition-colors z-10"
                         :class="{
                             'bg-[#6C63FF]/10 text-[#6C63FF]': isFocus || isIdle,
                             'bg-[#22C55E]/10 text-[#22C55E]': isShortBreak,
                             'bg-[#3B82F6]/10 text-[#3B82F6]': isLongBreak,
                         }">
                        <Brain v-if="isFocus || isIdle" class="w-4 h-4" />
                        <Coffee v-if="isBreak" class="w-4 h-4" />
                        <span>
                            <template v-if="isFocus || isIdle">Focus Time</template>
                            <template v-else-if="isShortBreak">Descanso Corto</template>
                            <template v-else-if="isLongBreak">Descanso Largo</template>
                        </span>
                    </div>

                    <!-- Time Display -->
                    <div class="text-[80px] md:text-[120px] font-bold text-[#F0F2F8] leading-none tracking-tighter mb-12 font-mono z-10">
                        {{ formattedTime }}
                    </div>

                    <!-- Controls -->
                    <div class="flex flex-wrap items-center justify-center gap-4 z-10 w-full">
                        
                        <!-- Waiting State: Start Phase -->
                        <button v-if="isWaiting" @click="sendAction('start')" 
                                class="w-full sm:w-auto bg-[#6C63FF] hover:bg-[#5A51E6] text-white px-8 py-4 rounded-[16px] text-[16px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.3)] flex items-center justify-center gap-2">
                            <Brain v-if="isFocus" class="w-5 h-5" />
                            <Coffee v-if="isBreak" class="w-5 h-5" />
                            Empezar {{ isFocus ? 'Focus' : (isShortBreak ? 'Descanso Corto' : 'Descanso Largo') }}
                        </button>
                        
                        <!-- Skip State (Omitir) -->
                        <button v-if="isWaiting" @click="sendAction('skip')" 
                                class="w-full sm:w-auto bg-transparent border border-[#7B82A0]/30 hover:bg-[#7B82A0]/10 text-[#7B82A0] hover:text-[#F0F2F8] px-8 py-4 rounded-[16px] text-[16px] font-bold transition-all flex items-center justify-center gap-2">
                            <SkipForward class="w-5 h-5" />
                            Omitir
                        </button>

                        <!-- Running State: Pause -->
                        <button v-if="isRunning" @click="sendAction('pause')" 
                                class="w-full sm:w-auto bg-[#F59E0B] hover:bg-[#D97706] text-black px-10 py-5 rounded-[16px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(245,158,11,0.3)] flex items-center justify-center gap-2 transform hover:-translate-y-1">
                            <Pause class="w-6 h-6" />
                            Pausar
                        </button>

                        <!-- Paused State: Resume -->
                        <button v-if="isPaused" @click="sendAction('resume')" 
                                class="w-full sm:w-auto bg-[#22C55E] hover:bg-[#16A34A] text-white px-10 py-5 rounded-[16px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(34,197,94,0.3)] flex items-center justify-center gap-2 transform hover:-translate-y-1">
                            <Play class="w-6 h-6" />
                            Reanudar
                        </button>

                        <!-- Skip Stage while running/paused -->
                        <button v-if="!isWaiting" @click="sendAction('skip')" 
                                class="w-full sm:w-auto bg-transparent border border-[#7B82A0]/30 hover:bg-[#7B82A0]/10 text-[#7B82A0] hover:text-[#F0F2F8] px-6 py-4 rounded-[16px] text-[16px] font-bold transition-all flex items-center justify-center gap-2">
                            <SkipForward class="w-5 h-5" />
                            Omitir
                        </button>

                        <!-- Always show stop if not waiting -->
                        <button v-if="!isWaiting" @click="sendAction('stop')" 
                                class="w-full sm:w-auto bg-transparent border border-[#EF4444]/50 hover:bg-[#EF4444]/10 text-[#EF4444] px-6 py-4 rounded-[16px] text-[16px] font-bold transition-all flex items-center justify-center gap-2">
                            <Square class="w-5 h-5" />
                            Detener
                        </button>

                    </div>

                    <div v-if="!isIdle" class="mt-8 text-[13px] text-[#7B82A0] max-w-[300px]">
                        Sincronizado en todos tus dispositivos.
                    </div>
                </div>

                <!-- Top Task Context -->
                <div v-if="topTask" class="mt-8 bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-6 md:p-8 flex items-center gap-6 shadow-sm">
                    <div class="hidden md:flex w-14 h-14 bg-[#F59E0B]/10 rounded-full items-center justify-center shrink-0">
                        <Flame class="w-7 h-7 text-[#F59E0B]" />
                    </div>
                    
                    <div class="flex-1">
                        <div class="text-[12px] text-[#7B82A0] font-bold uppercase tracking-wider mb-1">Trabajando en</div>
                        <h3 class="text-[20px] font-bold text-[#F0F2F8]">{{ topTask.title }}</h3>
                        <div class="mt-2 text-[14px] text-[#7B82A0]">
                            Valor: {{ topTask.criteria_sum_points ?? 0 }} pts
                        </div>
                    </div>

                    <Link :href="route('dashboard')" class="bg-[#2E3347] hover:bg-[#3E445B] text-[#F0F2F8] p-4 rounded-full transition-colors shrink-0">
                        <ArrowRight class="w-5 h-5" />
                    </Link>
                </div>
                
                <div v-else class="mt-8 text-center text-[#7B82A0] text-[14px]">
                    No tienes tareas pendientes. ¡Tómate el día libre!
                </div>

            </div>
        </div>

        <!-- Complex Task Interruption Modal -->
        <ResponsiveDialog :show="showComplexTaskModal" @close="() => {}" maxWidth="sm">
            <div class="p-6 md:p-8 text-center relative overflow-hidden">
                <div class="absolute top-[-50%] left-[-50%] w-[200%] h-[200%] bg-[#F59E0B] opacity-5 mix-blend-screen filter blur-[80px] pointer-events-none"></div>
                
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-[#F59E0B]/10 mb-6 relative z-10 border border-[#F59E0B]/20">
                    <AlertCircle class="h-8 w-8 text-[#F59E0B]" />
                </div>
                
                <h3 class="text-[22px] font-bold text-[#F0F2F8] mb-3 font-inter relative z-10">
                    Ciclo Completado
                </h3>
                
                <p class="text-[15px] text-[#7B82A0] mb-8 relative z-10 leading-relaxed">
                    Estás trabajando en una tarea marcada como compleja. ¿Deseas continuar con ella en el siguiente ciclo o prefieres pasar a otra cosa?
                </p>
                
                <div class="flex flex-col gap-3 justify-center relative z-10">
                    <button @click="showComplexTaskModal = false" class="w-full px-4 py-3.5 bg-[#6C63FF] text-white text-[15px] font-bold rounded-[12px] hover:bg-[#5A51E6] transition-all shadow-[0_4px_12px_rgba(108,99,255,0.25)] flex items-center justify-center gap-2">
                        <Brain class="w-5 h-5" />
                        Continuar con esta tarea
                    </button>
                    <button @click="skipComplexTask" class="w-full px-4 py-3.5 bg-transparent border border-[#2E3347] text-[#F0F2F8] text-[15px] font-bold rounded-[12px] hover:bg-[#2E3347]/50 transition-colors flex items-center justify-center gap-2">
                        <SkipForward class="w-5 h-5" />
                        Pasar a la siguiente tarea
                    </button>
                </div>
            </div>
        </ResponsiveDialog>
    </AppLayout>
</template>
