<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import ProjectSelector from '@/Components/ProjectSelector.vue';
import { useWorkingProject } from '@/Composables/useWorkingProject';
import { Check, Flame, Trophy, Plus, PartyPopper, FastForward, RotateCcw, Folder, Globe } from 'lucide-vue-next';

const props = defineProps({
    pendingTasks: Array,
    stats: Object,
    criteria: Array,
    projects: Array,
    filters: Object,
});

const { workingProjectId, setWorkingProject, syncWithProjects } = useWorkingProject();

const currentContext = ref(
    props.filters?.project_id 
        ? (Number(props.filters.project_id) || props.filters.project_id) 
        : (workingProjectId.value || '')
);

onMounted(() => {
    syncWithProjects(props.projects);

    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('project_id') && workingProjectId.value) {
        currentContext.value = workingProjectId.value;
        router.get(route('dashboard'), { project_id: workingProjectId.value }, { preserveState: true, replace: true });
    } else if (props.filters?.project_id) {
        setWorkingProject(props.filters.project_id);
        currentContext.value = workingProjectId.value;
    }
});

watch(workingProjectId, (newVal) => {
    currentContext.value = newVal || '';
});

const changeContext = () => {
    setWorkingProject(currentContext.value);
    router.get(route('dashboard'), { project_id: currentContext.value || undefined }, { preserveState: true });
};

const topTask = computed(() => {
    return props.pendingTasks[0];
});

// Complete Task from Dashboard
const completeTask = (task) => {
    router.patch(route('tasks.toggle', task.id), {}, { preserveScroll: true });
};

const skipTask = (task) => {
    router.patch(route('dashboard.skip', task.id), {}, { preserveScroll: true });
};

const resetSkipped = () => {
    router.post(route('dashboard.reset'), {}, { preserveScroll: true });
};

// Form for creating tasks from Dashboard
const form = useForm({
    title: '',
    project_id: workingProjectId.value || '',
    criteria_ids: [],
});

const selectedProject = computed(() => {
    if (!form.project_id) return null;
    return props.projects.find(p => p.id == form.project_id);
});

const availableCriteria = computed(() => {
    const selectedProjectId = form.project_id ? Number(form.project_id) : null;
    return props.criteria.filter(c => !c.project_id || c.project_id === selectedProjectId);
});

watch(() => form.project_id, (newVal) => {
    const allowedIds = new Set(availableCriteria.value.map(c => c.id));
    form.criteria_ids = form.criteria_ids.filter(id => allowedIds.has(id));
});

const isDialogOpen = ref(false);

const openCreateDialog = () => {
    form.reset();
    form.clearErrors();
    form.project_id = workingProjectId.value || '';
    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    form.reset();
};

const toggleCriterion = (criterionId) => {
    const index = form.criteria_ids.indexOf(criterionId);
    if (index === -1) {
        form.criteria_ids.push(criterionId);
    } else {
        form.criteria_ids.splice(index, 1);
    }
};

const submitForm = () => {
    form.post(route('tasks.store'), {
        onSuccess: () => closeDialog(),
    });
};
</script>

<template>
    <AppLayout title="Dashboard" show-mobile-header>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                    Command Center
                </h2>
                <div class="flex items-center gap-2">
                    <ProjectSelector 
                        v-model="currentContext" 
                        :projects="projects"
                        @change="changeContext"
                        class="w-[150px] sm:w-[200px]"
                    />
                </div>
            </div>
        </template>

        <div class="py-8 md:py-12">
            <div class="max-w-[800px] mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Stats Row -->
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[12px] p-4 flex flex-col items-center justify-center shadow-sm">
                        <span class="text-[#7B82A0] text-[12px] font-semibold uppercase tracking-wider mb-1">Pendientes</span>
                        <span class="text-[32px] font-bold text-[#F0F2F8] leading-none">{{ stats.pending }}</span>
                    </div>
                    <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[12px] p-4 flex flex-col items-center justify-center shadow-sm">
                        <span class="text-[#7B82A0] text-[12px] font-semibold uppercase tracking-wider mb-1">Completadas Hoy</span>
                        <div class="flex items-center gap-2">
                            <Trophy class="w-5 h-5 text-[#F59E0B]" v-if="stats.completedToday > 0" />
                            <span class="text-[32px] font-bold text-[#F0F2F8] leading-none">{{ stats.completedToday }}</span>
                        </div>
                    </div>
                </div>

                <!-- Active Task State -->
                <div v-if="topTask" class="relative bg-[#1A1D27] border border-[#F59E0B] rounded-[24px] shadow-[0_8px_32px_rgba(245,158,11,0.15)] overflow-hidden transition-all duration-300">
                    <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-[#F59E0B] to-[#EF4444]"></div>
                    
                    <div class="p-8 md:p-12 flex flex-col items-center text-center">
                        <!-- Top Badges: Status & Project Distinction -->
                        <div class="flex flex-wrap items-center justify-center gap-3 mb-6">
                            <div class="inline-flex items-center gap-1.5 bg-[#F59E0B]/10 text-[#F59E0B] border border-[#F59E0B]/20 px-3.5 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider">
                                <Flame class="w-4 h-4" /> ¿Qué hago ahora?
                            </div>

                            <!-- Distinct Project Pill -->
                            <div v-if="topTask.project" 
                                 class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[12px] font-semibold border shadow-sm"
                                 :style="{ backgroundColor: `${topTask.project.color}20`, color: topTask.project.color, borderColor: `${topTask.project.color}40` }">
                                <Folder class="w-3.5 h-3.5" />
                                <span>{{ topTask.project.name }}</span>
                                <span v-if="(topTask.project.base_score || topTask.project_score) > 0" class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-white/10">
                                    +{{ topTask.project.base_score || topTask.project_score }} pts base
                                </span>
                            </div>
                            <div v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[12px] text-[#7B82A0] bg-[#13151F] border border-[#2E3347]">
                                <Globe class="w-3.5 h-3.5" />
                                <span>Tarea Global</span>
                            </div>
                        </div>
                        
                        <h1 class="text-[32px] md:text-[42px] font-bold text-[#F0F2F8] leading-tight mb-6 font-inter">
                            {{ topTask.title }}
                        </h1>

                        <p v-if="topTask.notes" class="text-[#7B82A0] text-[15px] max-w-[500px] mb-8 leading-relaxed whitespace-pre-line">
                            {{ topTask.notes }}
                        </p>
                        
                        <!-- Criteria Section (Separated from Project) -->
                        <div v-if="topTask.criteria && topTask.criteria.length > 0" class="w-full max-w-[500px] mb-8">
                            <div class="text-[11px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2.5">
                                Criterios Asignados
                            </div>
                            <div class="flex flex-wrap justify-center gap-2">
                                <span v-for="criterion in topTask.criteria" :key="criterion.id"
                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[8px] text-[12px] font-semibold border"
                                      :style="{ backgroundColor: `${criterion.color}15`, color: criterion.color, borderColor: `${criterion.color}30` }">
                                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: criterion.color }"></span>
                                    {{ criterion.name }}
                                    <span class="font-mono font-bold opacity-80">({{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }})</span>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex flex-col md:flex-row items-center justify-center gap-3 md:gap-4 mt-4">
                            <button @click="completeTask(topTask)" 
                                    class="w-full md:w-auto bg-[#6C63FF] hover:bg-[#6C63FF]/90 text-white px-10 py-5 rounded-[16px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.2)] hover:shadow-[0_0_20px_rgba(108,99,255,0.4)] flex items-center justify-center gap-3 transform hover:-translate-y-1">
                                <Check class="w-6 h-6" />
                                ¡Completar Tarea!
                            </button>
                            
                            <button @click="skipTask(topTask)" 
                                    class="w-full md:w-auto bg-transparent border border-[#2E3347] hover:border-[#7B82A0] text-[#7B82A0] hover:text-[#F0F2F8] px-6 py-5 rounded-[16px] text-[16px] font-semibold transition-all flex items-center justify-center gap-2">
                                <FastForward class="w-5 h-5" />
                                Saltar por ahora
                            </button>
                        </div>
                        
                        <!-- Score Breakdown Footer -->
                        <div class="mt-10 pt-6 border-t border-[#2E3347]/60 w-full max-w-[500px] flex items-center justify-around text-[13px] font-mono text-[#7B82A0]">
                            <div v-if="topTask.project" class="flex flex-col items-center">
                                <span class="text-[10px] text-[#7B82A0] uppercase tracking-wider mb-0.5">Base Proyecto</span>
                                <span class="text-[#38BDF8] font-bold text-[15px]">+{{ topTask.project.base_score || topTask.project_score || 0 }} pts</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <span class="text-[10px] text-[#7B82A0] uppercase tracking-wider mb-0.5">Criterios</span>
                                <span class="text-[#22C55E] font-bold text-[15px]">+{{ topTask.criteria_sum_points || 0 }} pts</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <span class="text-[10px] text-[#F59E0B] uppercase tracking-wider font-bold mb-0.5">Puntaje Total</span>
                                <span class="text-[#F0F2F8] font-bold text-[16px]">{{ topTask.total_score ?? ((topTask.criteria_sum_points || 0) + (topTask.project_score || 0)) }} pts</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State (No tasks at all) -->
                <div v-else-if="pendingTasks.length === 0" class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-12 flex flex-col items-center text-center shadow-sm">
                    <div class="w-20 h-20 bg-[#22C55E]/10 rounded-full flex items-center justify-center mb-6">
                        <PartyPopper class="w-10 h-10 text-[#22C55E]" />
                    </div>
                    
                    <h2 class="text-[28px] font-bold text-[#F0F2F8] mb-3">¡Todo al día!</h2>
                    <p class="text-[#7B82A0] text-[16px] mb-10 max-w-[400px]">
                        Has completado todas tus tareas pendientes. Tómate un respiro o planea tu próximo movimiento.
                    </p>
                    
                    <button @click="openCreateDialog" 
                            class="bg-[#2E3347] hover:bg-[#2E3347]/80 text-[#F0F2F8] px-8 py-4 rounded-[12px] text-[16px] font-semibold transition-colors flex items-center gap-2 border border-[#7B82A0]/30">
                        <Plus class="w-5 h-5" />
                        Agregar nueva tarea
                    </button>
                </div>

                <!-- Empty State (All tasks skipped) -->
                <div v-else-if="stats.skipped > 0" class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-12 flex flex-col items-center text-center shadow-sm">
                    <div class="w-20 h-20 bg-[#F59E0B]/10 rounded-full flex items-center justify-center mb-6">
                        <RotateCcw class="w-10 h-10 text-[#F59E0B]" />
                    </div>
                    
                    <h2 class="text-[28px] font-bold text-[#F0F2F8] mb-3">Has saltado todas las tareas</h2>
                    <p class="text-[#7B82A0] text-[16px] mb-10 max-w-[400px]">
                        Has pospuesto todas tus tareas pendientes por ahora. ¿Listo para volver a la carga?
                    </p>
                    
                    <button @click="resetSkipped" 
                            class="bg-[#F59E0B] hover:bg-[#F59E0B]/90 text-black px-8 py-4 rounded-[12px] text-[16px] font-bold transition-colors flex items-center gap-2 shadow-[0_4px_12px_rgba(245,158,11,0.2)]">
                        <RotateCcw class="w-5 h-5" />
                        Reiniciar lista de tareas ({{ stats.skipped }})
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Dialog for Creating Task -->
        <ResponsiveDialog :show="isDialogOpen" @close="closeDialog" maxWidth="md">
            <div class="p-6">
                <h3 class="text-[18px] font-semibold text-[#F0F2F8] mb-6 font-inter">
                    Nueva Tarea
                </h3>
                
                <form @submit.prevent="submitForm">
                    <div class="mb-6">
                        <label for="title" class="block text-[13px] text-[#7B82A0] mb-1">Título de la tarea</label>
                        <input type="text" id="title" v-model="form.title" 
                               class="w-full bg-[#0F1117] border border-[#2E3347] text-[#F0F2F8] rounded-[6px] px-3 py-2 text-[15px] focus:ring-[#6C63FF] focus:border-[#6C63FF]"
                               placeholder="Ej: Terminar reporte mensual" required>
                        <div v-if="form.errors.title" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-[13px] text-[#7B82A0] mb-1">Proyecto</label>
                        <ProjectSelector 
                            v-model="form.project_id" 
                            :projects="projects"
                            placeholder="(Ninguno - Global)"
                        />
                        <div v-if="selectedProject && selectedProject.base_score > 0" class="mt-2 text-[12px] text-[#38BDF8] flex items-center gap-1.5 font-medium">
                            <span>★ Este proyecto añade automáticamente <strong>+{{ selectedProject.base_score }} pts</strong> de base a la tarea.</span>
                        </div>
                        <div v-if="form.errors.project_id" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.project_id }}</div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-[13px] text-[#7B82A0]">Criterios que aplican</label>
                            <span class="text-[11px] text-[#7B82A0]">
                                {{ selectedProject ? `Globales + ${selectedProject.name}` : 'Solo Globales' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" v-for="criterion in availableCriteria" :key="criterion.id"
                                    @click="toggleCriterion(criterion.id)"
                                    class="px-3 py-1.5 rounded-[6px] text-[13px] font-medium transition-all border"
                                    :class="form.criteria_ids.includes(criterion.id) 
                                        ? 'border-transparent shadow-md transform scale-[1.02]' 
                                        : 'bg-transparent border-[#2E3347] hover:border-[#7B82A0] opacity-60 hover:opacity-100'"
                                    :style="form.criteria_ids.includes(criterion.id) ? { backgroundColor: criterion.color, color: '#fff' } : { color: criterion.color }">
                                <div class="flex items-center gap-1.5">
                                    <Check v-if="form.criteria_ids.includes(criterion.id)" class="w-3.5 h-3.5" />
                                    <span>{{ criterion.name }} ({{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }})</span>
                                </div>
                            </button>
                        </div>
                        <div v-if="availableCriteria.length === 0" class="text-[13px] text-[#F59E0B] p-3 bg-[#F59E0B]/10 rounded-[6px]">
                            No hay criterios disponibles para esta selección.
                        </div>
                        <div v-if="form.errors.criteria_ids" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.criteria_ids }}</div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <button type="button" @click="closeDialog" class="px-4 py-2 text-[15px] text-[#6C63FF] hover:bg-[#6C63FF26] rounded-[6px] transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-[#6C63FF] text-white text-[15px] rounded-[6px] hover:opacity-85 transition-opacity flex items-center gap-2">
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </ResponsiveDialog>

    </AppLayout>
</template>
