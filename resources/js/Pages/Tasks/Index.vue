<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Plus, Edit2, Trash2, Check, Flame, Square, CheckSquare } from 'lucide-vue-next';

const props = defineProps({
    tasks: Array,
    criteria: Array,
    filters: Object,
});

const isMobile = ref(false);

const checkScreen = () => {
    isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
    checkScreen();
    window.addEventListener('resize', checkScreen);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScreen);
});

// Filters
const isShowingCompleted = ref(props.filters.completed);

const toggleFilter = () => {
    isShowingCompleted.value = !isShowingCompleted.value;
    router.get(route('tasks.index'), { completed: isShowingCompleted.value ? 1 : 0 }, { preserveState: true });
};

// Form
const form = useForm({
    id: null,
    title: '',
    criteria_ids: [],
});

const isDialogOpen = ref(false);
const dialogMode = ref('create'); // 'create' or 'edit'

const openCreateDialog = () => {
    form.reset();
    form.clearErrors();
    dialogMode.value = 'create';
    isDialogOpen.value = true;
};

const openEditDialog = (task) => {
    form.reset();
    form.clearErrors();
    form.id = task.id;
    form.title = task.title;
    form.criteria_ids = task.criteria.map(c => c.id);
    dialogMode.value = 'edit';
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
    if (dialogMode.value === 'create') {
        form.post(route('tasks.store'), {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.put(route('tasks.update', form.id), {
            onSuccess: () => closeDialog(),
        });
    }
};

const toggleTaskCompletion = (task) => {
    router.patch(route('tasks.toggle', task.id), {}, { preserveScroll: true });
};

const deleteTask = (task) => {
    if (confirm(`¿Estás seguro de que quieres eliminar la tarea "${task.title}"?`)) {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Tareas">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                    Tareas
                </h2>
                <button @click="openCreateDialog" class="bg-[#6C63FF] hover:opacity-85 text-white px-[16px] md:px-[20px] py-[8px] md:py-[10px] rounded-[6px] text-[15px] font-medium transition flex items-center gap-2">
                    <Plus class="w-[18px] h-[18px] md:w-[20px] md:h-[20px]" />
                    <span>Nuevo</span>
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[1200px] mx-auto sm:px-6 lg:px-8">
                
                <!-- Filter Toggle -->
                <div class="mb-6 flex justify-end px-4 sm:px-0">
                    <button @click="toggleFilter" class="flex items-center gap-2 text-[14px] font-medium transition-colors"
                            :class="isShowingCompleted ? 'text-[#6C63FF]' : 'text-[#7B82A0] hover:text-[#F0F2F8]'">
                        <CheckSquare class="w-4 h-4" />
                        <span>{{ isShowingCompleted ? 'Viendo Completadas' : 'Viendo Activas' }}</span>
                    </button>
                </div>

                <!-- Desktop Table View -->
                <div v-if="!isMobile" class="bg-[#1A1D27] overflow-hidden rounded-[12px] shadow">
                    <table class="min-w-full divide-y divide-[#2E3347]">
                        <thead class="bg-[#22263A]">
                            <tr>
                                <th scope="col" class="w-16 px-6 py-3"></th>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Tarea</th>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Criterios</th>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Puntaje</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2E3347]">
                            <tr v-for="(task, index) in tasks" :key="task.id" class="group transition-colors"
                                :class="{'bg-[#6C63FF]/10': index === 0 && !isShowingCompleted, 'hover:bg-[#22263A]': index !== 0 || isShowingCompleted}">
                                
                                <!-- Checkbox -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button @click="toggleTaskCompletion(task)" class="text-[#7B82A0] hover:text-[#6C63FF] transition-colors focus:outline-none">
                                        <CheckSquare v-if="task.is_completed" class="w-6 h-6 text-[#22C55E]" />
                                        <Square v-else class="w-6 h-6" />
                                    </button>
                                </td>

                                <!-- Title & Highlight -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[15px] font-medium" :class="task.is_completed ? 'text-[#7B82A0] line-through' : 'text-[#F0F2F8]'">
                                            {{ task.title }}
                                        </span>
                                        <span v-if="index === 0 && !isShowingCompleted" class="inline-flex items-center gap-1 bg-[#F59E0B]/20 text-[#F59E0B] px-2 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider">
                                            <Flame class="w-3 h-3" /> ¿Qué hago ahora?
                                        </span>
                                    </div>
                                </td>

                                <!-- Criteria Tags -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="criterion in task.criteria" :key="criterion.id"
                                              class="inline-flex items-center px-2 py-0.5 rounded-[4px] text-[10px] font-semibold"
                                              :style="{ backgroundColor: `${criterion.color}26`, color: criterion.color }">
                                            {{ criterion.name }} ({{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }})
                                        </span>
                                    </div>
                                </td>

                                <!-- Score -->
                                <td class="px-6 py-4 whitespace-nowrap text-[16px] text-[#F0F2F8] font-mono font-medium">
                                    {{ task.criteria_sum_points ?? 0 }} pts
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditDialog(task)" class="text-[#38BDF8] hover:text-[#38BDF8]/80 mr-4">
                                        <Edit2 class="w-[18px] h-[18px]" />
                                    </button>
                                    <button @click="deleteTask(task)" class="text-[#EF4444] hover:text-[#EF4444]/80">
                                        <Trash2 class="w-[18px] h-[18px]" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="tasks.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-[#7B82A0] text-[15px]">
                                    {{ isShowingCompleted ? 'No tienes tareas completadas.' : 'No tienes tareas activas. ¡Crea una nueva!' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div v-else class="space-y-[12px] px-4">
                    <div v-for="(task, index) in tasks" :key="task.id" 
                         class="relative bg-[#1A1D27] border rounded-[12px] p-[16px] shadow-[0_2px_12px_rgba(0,0,0,0.3)] transition-colors overflow-hidden"
                         :class="index === 0 && !isShowingCompleted ? 'border-[#F59E0B] bg-[#F59E0B]/5' : 'border-[#2E3347]'">
                        
                        <!-- Highlight Badge -->
                        <div v-if="index === 0 && !isShowingCompleted" class="absolute top-0 right-0 bg-[#F59E0B] text-black px-3 py-1 rounded-bl-[12px] text-[10px] font-bold flex items-center gap-1 uppercase">
                            <Flame class="w-3 h-3" /> Prioridad
                        </div>

                        <div class="flex items-start gap-4">
                            <!-- Checkbox -->
                            <button @click="toggleTaskCompletion(task)" class="mt-1 flex-shrink-0 text-[#7B82A0] hover:text-[#6C63FF] focus:outline-none">
                                <CheckSquare v-if="task.is_completed" class="w-6 h-6 text-[#22C55E]" />
                                <Square v-else class="w-6 h-6" />
                            </button>

                            <!-- Content -->
                            <div class="flex-1">
                                <div class="text-[16px] font-medium leading-tight mb-2 pr-12" :class="task.is_completed ? 'text-[#7B82A0] line-through' : 'text-[#F0F2F8]'">
                                    {{ task.title }}
                                </div>

                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    <span v-for="criterion in task.criteria" :key="criterion.id"
                                          class="inline-flex items-center px-2 py-0.5 rounded-[4px] text-[10px] font-semibold"
                                          :style="{ backgroundColor: `${criterion.color}26`, color: criterion.color }">
                                        {{ criterion.name }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="text-[14px] font-mono font-medium text-[#F0F2F8]">
                                        Total: {{ task.criteria_sum_points ?? 0 }} pts
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button @click="openEditDialog(task)" class="text-[#38BDF8] hover:opacity-80">
                                            <Edit2 class="w-[18px] h-[18px]" />
                                        </button>
                                        <button @click="deleteTask(task)" class="text-[#EF4444] hover:opacity-80">
                                            <Trash2 class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="tasks.length === 0" class="text-center text-[#7B82A0] py-12 text-[15px]">
                        {{ isShowingCompleted ? 'No tienes tareas completadas.' : 'No tienes tareas activas.' }}
                    </div>
                </div>

            </div>
        </div>

        <!-- Form Dialog (Responsive) -->
        <ResponsiveDialog :show="isDialogOpen" @close="closeDialog" maxWidth="md">
            <div class="p-6">
                <h3 class="text-[18px] font-semibold text-[#F0F2F8] mb-6 font-inter">
                    {{ dialogMode === 'create' ? 'Nueva Tarea' : 'Editar Tarea' }}
                </h3>
                
                <form @submit.prevent="submitForm">
                    <!-- Título -->
                    <div class="mb-6">
                        <label for="title" class="block text-[13px] text-[#7B82A0] mb-1">Título de la tarea</label>
                        <input type="text" id="title" v-model="form.title" 
                               class="w-full bg-[#0F1117] border border-[#2E3347] text-[#F0F2F8] rounded-[6px] px-3 py-2 text-[15px] focus:ring-[#6C63FF] focus:border-[#6C63FF]"
                               placeholder="Ej: Terminar reporte mensual" required>
                        <div v-if="form.errors.title" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.title }}</div>
                    </div>

                    <!-- Criterios -->
                    <div class="mb-6">
                        <label class="block text-[13px] text-[#7B82A0] mb-3">Criterios que aplican</label>
                        
                        <div class="flex flex-wrap gap-2">
                            <button type="button" v-for="criterion in criteria" :key="criterion.id"
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
                        
                        <div v-if="criteria.length === 0" class="text-[13px] text-[#F59E0B] p-3 bg-[#F59E0B]/10 rounded-[6px]">
                            Aún no has creado ningún criterio. Ve a la sección "Criterios" para configurar cómo puntuarás tus tareas.
                        </div>
                        <div v-if="form.errors.criteria_ids" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.criteria_ids }}</div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex justify-end gap-3 mt-8">
                        <button type="button" @click="closeDialog" class="px-4 py-2 text-[15px] text-[#6C63FF] hover:bg-[#6C63FF26] rounded-[6px] transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-[#6C63FF] text-white text-[15px] rounded-[6px] hover:opacity-85 transition-opacity disabled:opacity-50 flex items-center gap-2">
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </ResponsiveDialog>
    </AppLayout>
</template>
