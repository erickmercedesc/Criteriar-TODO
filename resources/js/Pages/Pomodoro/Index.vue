<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Play, Pause, Square, Coffee, Brain, ArrowRight, Flame, SkipForward } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    topTask: Object,
    initialState: Object,
});

const state = ref(props.initialState);
const currentRemaining = ref(props.initialState.remaining_seconds || 0);

// Timer and Polling references
let localTimer = null;
let pollTimer = null;

// Audio context for "Ding" sound
const playDing = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        
        const playNote = (freq, startTime, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + startTime);
            
            gain.gain.setValueAtTime(0, ctx.currentTime + startTime);
            gain.gain.linearRampToValueAtTime(0.5, ctx.currentTime + startTime + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + startTime + duration);
            
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + startTime);
            osc.stop(ctx.currentTime + startTime + duration);
        };

        playNote(880, 0, 0.5); // A5
        playNote(1108.73, 0.2, 0.6); // C#6
    } catch (e) {
        console.error("Audio not supported or blocked");
    }
};

const updateLocalTime = () => {
    if (state.value.status === 'idle') {
        currentRemaining.value = 25 * 60; // default visual for idle
        return;
    }

    if (state.value.is_paused) {
        currentRemaining.value = state.value.remaining_seconds;
        return;
    }

    const now = Math.floor(Date.now() / 1000);
    const diff = state.value.ends_at - now;

    if (diff <= 0) {
        if (currentRemaining.value > 0) {
            // Just hit zero
            playDing();
            fetchState(); // Re-sync to confirm it's actually over
        }
        currentRemaining.value = 0;
    } else {
        currentRemaining.value = diff;
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

const sendAction = async (action, data = {}) => {
    try {
        const response = await axios.post(route(`pomodoro.${action}`), data);
        state.value = response.data;
        updateLocalTime();
    } catch (error) {
        console.error(`Failed to ${action} pomodoro`, error);
    }
};

onMounted(() => {
    // Run local countdown every second
    localTimer = setInterval(updateLocalTime, 1000);
    updateLocalTime();

    // Silent poll every 15 seconds to sync state from backend
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
const isFocus = computed(() => state.value.status === 'focus');
const isShortBreak = computed(() => state.value.status === 'short_break');
const isLongBreak = computed(() => state.value.status === 'long_break');
const isBreak = computed(() => isShortBreak.value || isLongBreak.value);
const isIdle = computed(() => state.value.status === 'idle');
const isRunning = computed(() => !isIdle.value && !state.value.is_paused && currentRemaining.value > 0);
const isPaused = computed(() => state.value.is_paused);
const isFinished = computed(() => !isIdle.value && currentRemaining.value === 0);

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
                        {{ isIdle ? '25:00' : formattedTime }}
                    </div>

                    <!-- Controls -->
                    <div class="flex flex-wrap items-center justify-center gap-4 z-10 w-full">
                        
                        <!-- Idle State: Start Focus -->
                        <button v-if="isIdle || isFinished" @click="sendAction('start', { phase: 'focus' })" 
                                class="w-full sm:w-auto bg-[#6C63FF] hover:bg-[#5A51E6] text-white px-8 py-4 rounded-[16px] text-[16px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.3)] flex items-center justify-center gap-2">
                            <Brain class="w-5 h-5" />
                            Empezar Focus
                        </button>
                        
                        <!-- Idle State: Start Break -->
                        <button v-if="isIdle || isFinished" @click="sendAction('start', { phase: 'break' })" 
                                class="w-full sm:w-auto bg-[#2E3347] hover:bg-[#3E445B] text-[#F0F2F8] px-8 py-4 rounded-[16px] text-[16px] font-bold transition-all flex items-center justify-center gap-2 border border-[#7B82A0]/20">
                            <Coffee class="w-5 h-5" />
                            Empezar Descanso
                        </button>

                        <!-- Running State: Pause -->
                        <button v-if="isRunning" @click="sendAction('pause')" 
                                class="w-full sm:w-auto bg-[#F59E0B] hover:bg-[#D97706] text-black px-10 py-5 rounded-[16px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(245,158,11,0.3)] flex items-center justify-center gap-2 transform hover:-translate-y-1">
                            <Pause class="w-6 h-6" />
                            Pausar
                        </button>

                        <!-- Paused State: Resume -->
                        <button v-if="isPaused && !isFinished" @click="sendAction('resume')" 
                                class="w-full sm:w-auto bg-[#22C55E] hover:bg-[#16A34A] text-white px-10 py-5 rounded-[16px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(34,197,94,0.3)] flex items-center justify-center gap-2 transform hover:-translate-y-1">
                            <Play class="w-6 h-6" />
                            Reanudar
                        </button>

                        <!-- Skip Stage (Only visible when active or paused, but not finished/idle) -->
                        <button v-if="!isIdle && !isFinished" @click="sendAction('start', { phase: isFocus ? 'break' : 'focus' })" 
                                class="w-full sm:w-auto bg-transparent border border-[#7B82A0]/30 hover:bg-[#7B82A0]/10 text-[#7B82A0] hover:text-[#F0F2F8] px-6 py-4 rounded-[16px] text-[16px] font-bold transition-all flex items-center justify-center gap-2">
                            <SkipForward class="w-5 h-5" />
                            Omitir
                        </button>

                        <!-- Always show stop if not idle -->
                        <button v-if="!isIdle" @click="sendAction('stop')" 
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
    </AppLayout>
</template>
