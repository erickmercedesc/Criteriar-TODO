<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import ProjectSelector from '@/Components/ProjectSelector.vue';
import { useWorkingProject } from '@/Composables/useWorkingProject';
import { Play, Pause, Square, Coffee, Brain, ArrowRight, Flame, SkipForward, AlertCircle, PartyPopper, Volume2, VolumeX } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    topTask: Object,
    initialState: Object,
    projects: Array,
    filters: Object,
});

const { workingProjectId, setWorkingProject, syncWithProjects } = useWorkingProject();

const currentContext = ref(
    props.filters?.project_id 
        ? (Number(props.filters.project_id) || props.filters.project_id) 
        : (workingProjectId.value || '')
);

const changeContext = () => {
    setWorkingProject(currentContext.value);
    router.get(route('pomodoro.index'), { project_id: currentContext.value || undefined }, { preserveState: true });
};

watch(workingProjectId, (newVal) => {
    currentContext.value = newVal || '';
});

const state = ref(props.initialState);
const currentRemaining = ref(props.initialState.remaining_seconds || 0);

const showCompletionModal = ref(false);
const completedPhase = ref('focus'); // 'focus' or 'break'
const isComplexTaskAfterFocus = ref(false);

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
 * Plays the alarm.wav sound from the public directory with optional looping.
 * @param {boolean} loop - Whether to loop the alarm continuously until stopped.
 * @returns {void}
 */
const playDing = (loop = true) => {
    try {
        if (!alarmAudio) initAudio();
        
        alarmAudio.loop = loop;
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

/**
 * Stops and resets the alarm sound.
 */
const stopAlarm = () => {
    if (alarmAudio) {
        alarmAudio.pause();
        alarmAudio.currentTime = 0;
        alarmAudio.loop = false;
    }
    showCompletionModal.value = false;
};

const dismissAndStartNext = () => {
    stopAlarm();
    sendAction('start');
};

const dismissAndSkipComplex = () => {
    stopAlarm();
    skipComplexTask();
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
                const wasFocus = state.value.phase === 'focus';
                const isComplex = wasFocus && props.topTask && props.topTask.criteria && props.topTask.criteria.some(c => c.is_complex_marker);
                
                completedPhase.value = wasFocus ? 'focus' : 'break';
                isComplexTaskAfterFocus.value = isComplex;
                
                playDing(true);
                showCompletionModal.value = true;
                
                fetchState(); // Force a sync so the backend intercepts expiration
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
            alarmAudio.loop = false;
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
    syncWithProjects(props.projects);

    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('project_id') && workingProjectId.value) {
        currentContext.value = workingProjectId.value;
        router.get(route('pomodoro.index'), { project_id: workingProjectId.value }, { preserveState: true, replace: true });
    } else if (props.filters?.project_id) {
        setWorkingProject(props.filters.project_id);
        currentContext.value = workingProjectId.value;
    }

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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                    Pomodoro Focus
                </h2>
                <div class="flex items-center gap-3">
                    <ProjectSelector 
                        v-if="projects"
                        v-model="currentContext" 
                        :projects="projects"
                        @change="changeContext"
                        class="w-[150px] sm:w-[200px]"
                    />
                    <!-- Cycle Indicator -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] px-4 py-2 rounded-[12px] text-[13px] font-bold text-[#7B82A0]">
                        Ciclo <span class="text-[#F0F2F8]">{{ currentCycle }}</span> <span class="opacity-50">/ 4</span>
                    </div>
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
                        <div class="text-[12px] text-[#7B82A0] font-bold uppercase tracking-wider mb-1 flex items-center gap-2 flex-wrap">
                            <span>Trabajando en</span>
                            <span v-if="topTask.project" class="inline-flex items-center px-2.5 py-0.5 rounded-[6px] text-[11px] font-semibold border"
                                  :style="{ backgroundColor: `${topTask.project.color}15`, color: topTask.project.color, borderColor: `${topTask.project.color}30` }">
                                {{ topTask.project.name }}
                                <span class="ml-1 opacity-70" v-if="topTask.project_score > 0">({{ topTask.project_score }} pts)</span>
                            </span>
                        </div>
                        <h3 class="text-[20px] font-bold text-[#F0F2F8]">{{ topTask.title }}</h3>
                        <p v-if="topTask.notes" class="mt-2 text-[14px] text-[#7B82A0] max-w-[600px] leading-relaxed whitespace-pre-line">
                            {{ topTask.notes }}
                        </p>
                        <div class="mt-3 text-[14px] text-[#7B82A0]">
                            Valor: {{ topTask.total_score ?? ((topTask.criteria_sum_points || 0) + (topTask.project_score || 0)) }} pts
                        </div>
                    </div>

                    <Link :href="route('dashboard')" class="bg-[#2E3347] hover:bg-[#3E445B] text-[#F0F2F8] p-4 rounded-full transition-colors shrink-0">
                        <ArrowRight class="w-5 h-5" />
                    </Link>
                </div>
                
                <div v-else class="mt-8 text-center text-[#7B82A0] text-[14px] p-8 bg-[#1A1D27]/50 rounded-[20px] border border-[#2E3347]/50">
                    <span v-if="currentContext">No tienes tareas pendientes en este proyecto. ¡Tómate un descanso o crea nuevas tareas!</span>
                    <span v-else>No tienes tareas pendientes. ¡Tómate el día libre!</span>
                </div>

            </div>
        </div>

        <!-- Completion Modal (Pomodoro / Break Terminado) -->
        <ResponsiveDialog :show="showCompletionModal" @close="stopAlarm" maxWidth="sm">
            <div class="p-6 md:p-8 text-center relative overflow-hidden">
                <!-- Ambient Glow Background -->
                <div v-if="completedPhase === 'focus'" 
                     class="absolute top-[-50%] left-[-50%] w-[200%] h-[200%] bg-[#22C55E] opacity-10 mix-blend-screen filter blur-[80px] pointer-events-none"></div>
                <div v-else 
                     class="absolute top-[-50%] left-[-50%] w-[200%] h-[200%] bg-[#6C63FF] opacity-10 mix-blend-screen filter blur-[80px] pointer-events-none"></div>

                <!-- Icon Badge -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-5 relative z-10 border"
                     :class="completedPhase === 'focus' 
                         ? 'bg-[#22C55E]/15 border-[#22C55E]/30 text-[#22C55E]' 
                         : 'bg-[#6C63FF]/15 border-[#6C63FF]/30 text-[#6C63FF]'">
                    <PartyPopper v-if="completedPhase === 'focus'" class="h-8 w-8" />
                    <Coffee v-else class="h-8 w-8" />
                </div>

                <!-- Ringing / Sound Status Pill -->
                <div class="inline-flex items-center gap-2 bg-[#EF4444]/15 border border-[#EF4444]/30 text-[#EF4444] px-3 py-1 rounded-full text-[12px] font-semibold mb-4 animate-pulse relative z-10">
                    <Volume2 class="w-3.5 h-3.5" />
                    <span>Alarma sonando</span>
                </div>

                <!-- Title -->
                <h3 class="text-[24px] font-bold text-[#F0F2F8] mb-2 font-inter relative z-10">
                    {{ completedPhase === 'focus' ? '¡Pomodoro Terminado!' : '¡Descanso Terminado!' }}
                </h3>

                <!-- Subtitle / Message -->
                <p class="text-[15px] text-[#7B82A0] mb-6 relative z-10 leading-relaxed">
                    <template v-if="completedPhase === 'focus'">
                        ¡Excelente sesión de concentración! Es momento de tomar un respiro y recargar energía.
                    </template>
                    <template v-else>
                        El descanso ha concluido. ¿Listo para el siguiente bloque de productividad?
                    </template>
                </p>

                <!-- Complex Task Warning / Choice if applicable -->
                <div v-if="isComplexTaskAfterFocus" class="mb-6 p-4 rounded-[12px] bg-[#F59E0B]/10 border border-[#F59E0B]/20 text-left relative z-10">
                    <div class="flex items-center gap-2 text-[#F59E0B] font-semibold text-[13px] mb-1">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>Tarea Compleja Detectada</span>
                    </div>
                    <p class="text-[12px] text-[#7B82A0]">
                        Esta tarea está marcada como compleja. Puedes continuar con ella o saltarla para el próximo ciclo.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-3 justify-center relative z-10">
                    <!-- Start Next Phase directly (stops alarm & starts) -->
                    <button @click="dismissAndStartNext" 
                            class="w-full px-4 py-3.5 text-white text-[15px] font-bold rounded-[12px] transition-all shadow-[0_4px_12px_rgba(0,0,0,0.3)] flex items-center justify-center gap-2"
                            :class="completedPhase === 'focus' 
                                ? 'bg-[#22C55E] hover:bg-[#16A34A]' 
                                : 'bg-[#6C63FF] hover:bg-[#5A51E6]'">
                        <Play class="w-5 h-5" />
                        Detener alarma y empezar {{ completedPhase === 'focus' ? (state.phase === 'long_break' ? 'Descanso Largo' : 'Descanso Corto') : 'Focus' }}
                    </button>

                    <!-- Skip task button for complex task -->
                    <button v-if="isComplexTaskAfterFocus" @click="dismissAndSkipComplex" 
                            class="w-full px-4 py-3 bg-transparent border border-[#F59E0B]/40 text-[#F59E0B] hover:bg-[#F59E0B]/10 text-[14px] font-semibold rounded-[12px] transition-colors flex items-center justify-center gap-2">
                        <SkipForward class="w-4 h-4" />
                        Pasar a otra tarea
                    </button>

                    <!-- Just Stop Alarm -->
                    <button @click="stopAlarm" 
                            class="w-full px-4 py-3 bg-[#1A1D27] hover:bg-[#22263A] border border-[#2E3347] text-[#F0F2F8] text-[14px] font-medium rounded-[12px] transition-colors flex items-center justify-center gap-2">
                        <VolumeX class="w-4 h-4 text-[#7B82A0]" />
                        Detener alarma
                    </button>
                </div>
            </div>
        </ResponsiveDialog>
    </AppLayout>
</template>
