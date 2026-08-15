<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Plus, Edit2, Trash2, Folder, ListTodo, Check } from 'lucide-vue-next';

const props = defineProps({
    projects: Array,
    criteria: Array,
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

// Curated colors for the color picker
const colorOptions = [
    '#6C63FF', // Primary
    '#22C55E', // Success (Green)
    '#F59E0B', // Warning (Amber)
    '#EF4444', // Danger (Red)
    '#38BDF8', // Info (Light Blue)
    '#A855F7', // Purple
    '#EC4899', // Pink
    '#14B8A6', // Teal
];

const form = useForm({
    id: null,
    name: '',
    color: '#6C63FF',
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

const openEditDialog = (project) => {
    form.reset();
    form.clearErrors();
    form.id = project.id;
    form.name = project.name;
    form.color = project.color || '#6C63FF';
    form.criteria_ids = project.criteria ? project.criteria.map(c => c.id) : [];
    dialogMode.value = 'edit';
    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (dialogMode.value === 'create') {
        form.post(route('projects.store'), {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.put(route('projects.update', form.id), {
            onSuccess: () => closeDialog(),
        });
    }
};

const deleteProject = (project) => {
    if (confirm(`¿Estás seguro de que quieres eliminar el proyecto "${project.name}"? Sus tareas quedarán sin proyecto asignado.`)) {
        router.delete(route('projects.destroy', project.id));
    }
};

const toggleCriterion = (criterionId) => {
    const index = form.criteria_ids.indexOf(criterionId);
    if (index === -1) {
        form.criteria_ids.push(criterionId);
    } else {
        form.criteria_ids.splice(index, 1);
    }
};
</script>

<template>
    <AppLayout title="Proyectos">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                    Proyectos
                </h2>
                <button @click="openCreateDialog" class="bg-[#6C63FF] hover:opacity-85 text-white px-[16px] md:px-[20px] py-[8px] md:py-[10px] rounded-[6px] text-[15px] font-medium transition flex items-center gap-2">
                    <Plus class="w-[18px] h-[18px] md:w-[20px] md:h-[20px]" />
                    <span>Nuevo</span>
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[1200px] mx-auto sm:px-6 lg:px-8">
                
                <!-- Desktop Table View -->
                <div v-if="!isMobile" class="bg-[#1A1D27] overflow-hidden rounded-[12px] shadow">
                    <table class="min-w-full divide-y divide-[#2E3347]">
                        <thead class="bg-[#22263A]">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Nombre</th>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Score Base</th>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Tareas Pendientes</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2E3347]">
                            <tr v-for="project in projects" :key="project.id" class="group hover:bg-[#22263A] transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-[15px] text-[#F0F2F8] font-medium flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :style="{ backgroundColor: `${project.color}33`, color: project.color }">
                                        <Folder class="w-4 h-4" />
                                    </div>
                                    {{ project.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-[15px] text-[#F0F2F8]">
                                    {{ project.criteria ? project.criteria.reduce((sum, c) => sum + c.points, 0) : 0 }} pts
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-[15px] text-[#F0F2F8]">
                                    {{ project.tasks_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('tasks.index', { project_id: project.id })" class="inline-block text-[#A78BFA] hover:text-[#A78BFA]/80 mr-4" title="Ver tareas">
                                        <ListTodo class="w-[18px] h-[18px]" />
                                    </Link>
                                    <button @click="openEditDialog(project)" class="text-[#38BDF8] hover:text-[#38BDF8]/80 mr-4" title="Editar">
                                        <Edit2 class="w-[18px] h-[18px] inline-block" />
                                    </button>
                                    <button @click="deleteProject(project)" class="text-[#EF4444] hover:text-[#EF4444]/80" title="Eliminar">
                                        <Trash2 class="w-[18px] h-[18px] inline-block" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="projects.length === 0">
                                <td colspan="3" class="px-6 py-8 text-center text-[#7B82A0] text-[15px]">
                                    No tienes proyectos creados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div v-else class="space-y-[8px] px-4">
                    <div v-for="project in projects" :key="project.id" class="bg-[#1A1D27] border border-[#2E3347] rounded-[12px] p-[16px] flex justify-between items-center shadow-[0_2px_12px_rgba(0,0,0,0.3)]">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :style="{ backgroundColor: `${project.color}33`, color: project.color }">
                                <Folder class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-[15px] text-[#F0F2F8] font-medium">
                                    {{ project.name }}
                                </div>
                                <div class="text-[12px] text-[#7B82A0]">
                                    Score Base: {{ project.criteria ? project.criteria.reduce((sum, c) => sum + c.points, 0) : 0 }} pts • {{ project.tasks_count || 0 }} tareas pendientes
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <Link :href="route('tasks.index', { project_id: project.id })" class="text-[#A78BFA] hover:opacity-80" title="Ver tareas">
                                <ListTodo class="w-[20px] h-[20px]" />
                            </Link>
                            <button @click="openEditDialog(project)" class="text-[#38BDF8] hover:opacity-80" title="Editar">
                                <Edit2 class="w-[20px] h-[20px]" />
                            </button>
                            <button @click="deleteProject(project)" class="text-[#EF4444] hover:opacity-80" title="Eliminar">
                                <Trash2 class="w-[20px] h-[20px]" />
                            </button>
                        </div>
                    </div>
                    <div v-if="projects.length === 0" class="text-center text-[#7B82A0] py-8 text-[15px]">
                        No tienes proyectos creados.
                    </div>
                </div>

            </div>
        </div>

        <!-- Form Dialog (Responsive) -->
        <ResponsiveDialog :show="isDialogOpen" @close="closeDialog" maxWidth="md">
            <div class="p-6">
                <h3 class="text-[18px] font-semibold text-[#F0F2F8] mb-6 font-inter">
                    {{ dialogMode === 'create' ? 'Nuevo Proyecto' : 'Editar Proyecto' }}
                </h3>
                
                <form @submit.prevent="submitForm">
                    <!-- Nombre -->
                    <div class="mb-4">
                        <label for="name" class="block text-[13px] text-[#7B82A0] mb-1">Nombre</label>
                        <input type="text" id="name" v-model="form.name" 
                               class="w-full bg-[#0F1117] border border-[#2E3347] text-[#F0F2F8] rounded-[6px] px-3 py-2 text-[15px] focus:ring-[#6C63FF] focus:border-[#6C63FF]"
                               placeholder="Ej: Trabajo, Personal" required>
                        <div v-if="form.errors.name" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Color -->
                    <div class="mb-6">
                        <label class="block text-[13px] text-[#7B82A0] mb-2">Color</label>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" v-for="color in colorOptions" :key="color"
                                    @click="form.color = color"
                                    class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
                                    :class="form.color === color ? 'border-white scale-110 shadow-lg' : 'border-transparent'"
                                    :style="{ backgroundColor: color }">
                            </button>
                        </div>
                        <div v-if="form.errors.color" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.color }}</div>
                    </div>

                    <!-- Criterios -->
                    <div class="mb-6">
                        <label class="block text-[13px] text-[#7B82A0] mb-3">Criterios del Proyecto (Score Base)</label>
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
                            Aún no has creado ningún criterio. Ve a la sección "Criterios".
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
