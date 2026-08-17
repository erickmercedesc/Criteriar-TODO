<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Plus, Edit2, Trash2, Folder, ListTodo, Sliders, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    projects: Array,
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
    base_score: 0,
});

const isDialogOpen = ref(false);
const dialogMode = ref('create'); // 'create' or 'edit'

const openCreateDialog = () => {
    form.reset();
    form.clearErrors();
    form.color = '#6C63FF';
    form.base_score = 0;
    dialogMode.value = 'create';
    isDialogOpen.value = true;
};

const openEditDialog = (project) => {
    form.reset();
    form.clearErrors();
    form.id = project.id;
    form.name = project.name;
    form.color = project.color || '#6C63FF';
    form.base_score = project.base_score || 0;
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
</script>

<template>
    <AppLayout title="Proyectos">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                        Proyectos
                    </h2>
                    <p class="text-[13px] text-[#7B82A0] mt-0.5">
                        Organiza tus objetivos, asigna puntuación base y define criterios específicos.
                    </p>
                </div>
                <button @click="openCreateDialog" class="bg-[#6C63FF] hover:bg-[#6C63FF]/90 text-white px-[16px] md:px-[20px] py-[8px] md:py-[10px] rounded-[8px] text-[15px] font-medium transition flex items-center gap-2 shadow-sm">
                    <Plus class="w-[18px] h-[18px] md:w-[20px] md:h-[20px]" />
                    <span>Nuevo Proyecto</span>
                </button>
            </div>
        </template>

        <div class="py-6 md:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Desktop Table View -->
            <div v-if="!isMobile" class="bg-[#1A1D27] overflow-hidden rounded-[16px] border border-[#2E3347] shadow-sm">
                <table class="min-w-full divide-y divide-[#2E3347]">
                    <thead class="bg-[#13151F]">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Proyecto</th>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Score Base</th>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Criterios Específicos</th>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Tareas Pendientes</th>
                            <th scope="col" class="relative px-6 py-4"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2E3347]">
                        <tr v-for="project in projects" :key="project.id" class="group hover:bg-[#222634] transition-colors">
                            <!-- Name & Color -->
                            <td class="px-6 py-4 whitespace-nowrap text-[15px] text-[#F0F2F8] font-medium flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :style="{ backgroundColor: `${project.color}25`, color: project.color }">
                                    <Folder class="w-4 h-4" />
                                </div>
                                {{ project.name }}
                            </td>

                            <!-- Base Score -->
                            <td class="px-6 py-4 whitespace-nowrap text-[15px] text-[#F0F2F8] font-mono font-medium">
                                <span class="text-[#38BDF8]">{{ project.base_score || 0 }} pts</span>
                            </td>

                            <!-- Specific Criteria -->
                            <td class="px-6 py-4 whitespace-nowrap text-[14px]">
                                <Link :href="route('projects.scoring-criteria.index', project.id)" 
                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[6px] text-[12px] font-semibold bg-[#2E3347]/60 hover:bg-[#2E3347] text-[#F0F2F8] transition-colors border border-[#2E3347]">
                                    <Sliders class="w-3.5 h-3.5 text-[#A78BFA]" />
                                    <span>{{ project.criteria?.length || 0 }} criterios</span>
                                </Link>
                            </td>

                            <!-- Tasks Count -->
                            <td class="px-6 py-4 whitespace-nowrap text-[14px] text-[#7B82A0]">
                                {{ project.tasks_count || 0 }} tareas
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                <Link :href="route('projects.scoring-criteria.index', project.id)" 
                                      class="inline-block text-[#A78BFA] hover:text-[#A78BFA]/80 mr-4" 
                                      title="Gestionar Criterios del Proyecto">
                                    <Sliders class="w-[18px] h-[18px]" />
                                </Link>
                                <Link :href="route('tasks.index', { project_id: project.id })" 
                                      class="inline-block text-[#38BDF8] hover:text-[#38BDF8]/80 mr-4" 
                                      title="Ver tareas">
                                    <ListTodo class="w-[18px] h-[18px]" />
                                </Link>
                                <button @click="openEditDialog(project)" class="text-[#7B82A0] hover:text-[#F0F2F8] mr-4" title="Editar">
                                    <Edit2 class="w-[18px] h-[18px] inline-block" />
                                </button>
                                <button @click="deleteProject(project)" class="text-[#EF4444] hover:text-[#EF4444]/80" title="Eliminar">
                                    <Trash2 class="w-[18px] h-[18px] inline-block" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="projects.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-[#7B82A0] text-[15px]">
                                No tienes proyectos creados. ¡Crea el primero para organizar tus tareas!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div v-else class="space-y-3">
                <div v-for="project in projects" :key="project.id" class="bg-[#1A1D27] border border-[#2E3347] rounded-[16px] p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :style="{ backgroundColor: `${project.color}25`, color: project.color }">
                                <Folder class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-[16px] text-[#F0F2F8] font-bold">
                                    {{ project.name }}
                                </div>
                                <div class="text-[12px] text-[#7B82A0] font-mono mt-0.5">
                                    Base: <span class="text-[#38BDF8] font-bold">{{ project.base_score || 0 }} pts</span> • {{ project.tasks_count || 0 }} tareas pendientes
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-[#2E3347]">
                        <Link :href="route('projects.scoring-criteria.index', project.id)" 
                              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[6px] text-[12px] font-semibold bg-[#2E3347] text-[#F0F2F8]">
                            <Sliders class="w-3.5 h-3.5 text-[#A78BFA]" />
                            <span>{{ project.criteria?.length || 0 }} criterios</span>
                        </Link>
                        
                        <div class="flex items-center gap-3">
                            <Link :href="route('tasks.index', { project_id: project.id })" class="text-[#38BDF8] p-1.5" title="Ver tareas">
                                <ListTodo class="w-5 h-5" />
                            </Link>
                            <button @click="openEditDialog(project)" class="text-[#7B82A0] p-1.5" title="Editar">
                                <Edit2 class="w-5 h-5" />
                            </button>
                            <button @click="deleteProject(project)" class="text-[#EF4444] p-1.5" title="Eliminar">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="projects.length === 0" class="text-center text-[#7B82A0] py-12 text-[15px]">
                    No tienes proyectos creados.
                </div>
            </div>

        </div>

        <!-- Form Dialog (Responsive) -->
        <ResponsiveDialog :show="isDialogOpen" @close="closeDialog" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 rounded-[10px]" :style="{ backgroundColor: `${form.color}20`, color: form.color }">
                        <Folder class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[18px] font-bold text-[#F0F2F8]">
                            {{ dialogMode === 'create' ? 'Nuevo Proyecto' : 'Editar Proyecto' }}
                        </h3>
                        <p class="text-[12px] text-[#7B82A0]">
                            Configura los datos principales del proyecto
                        </p>
                    </div>
                </div>
                
                <form @submit.prevent="submitForm" class="space-y-5">
                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-[13px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2">Nombre del Proyecto</label>
                        <input type="text" id="name" v-model="form.name" 
                               class="w-full bg-[#13151F] border border-[#2E3347] text-[#F0F2F8] rounded-[10px] px-4 py-3 text-[15px] focus:border-[#6C63FF] focus:ring-1 focus:ring-[#6C63FF] transition"
                               placeholder="Ej: Trabajo, Startup, Salud" required>
                        <div v-if="form.errors.name" class="text-[#EF4444] text-[12px] mt-1.5 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Puntuación Base -->
                    <div>
                        <label for="base_score" class="block text-[13px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2">Puntuación Base (pts)</label>
                        <input type="number" id="base_score" v-model="form.base_score" min="0" max="1000"
                               class="w-full bg-[#13151F] border border-[#2E3347] text-[#F0F2F8] font-mono rounded-[10px] px-4 py-3 text-[15px] focus:border-[#6C63FF] focus:ring-1 focus:ring-[#6C63FF] transition"
                               placeholder="0">
                        <p class="text-[12px] text-[#7B82A0] mt-1.5">
                            Puntos por defecto que heredará automáticamente cualquier tarea asignada a este proyecto.
                        </p>
                        <div v-if="form.errors.base_score" class="text-[#EF4444] text-[12px] mt-1.5 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.base_score }}
                        </div>
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-[13px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2">Color Identificador</label>
                        <div class="flex items-center gap-3 flex-wrap">
                            <button type="button" v-for="color in colorOptions" :key="color"
                                    @click="form.color = color"
                                    class="w-8 h-8 rounded-full transition-transform flex items-center justify-center"
                                    :style="{ backgroundColor: color }"
                                    :class="form.color === color ? 'scale-125 ring-2 ring-white ring-offset-2 ring-offset-[#1A1D27]' : 'hover:scale-110'">
                            </button>
                        </div>
                        <div v-if="form.errors.color" class="text-[#EF4444] text-[12px] mt-1.5 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.color }}
                        </div>
                    </div>

                    <!-- Notice about specific criteria -->
                    <div class="bg-[#13151F] border border-[#2E3347] rounded-[12px] p-4 text-[13px] text-[#7B82A0] leading-relaxed flex items-start gap-3">
                        <Sliders class="w-5 h-5 text-[#A78BFA] shrink-0 mt-0.5" />
                        <div>
                            Los criterios específicos de este proyecto se gestionan en <strong class="text-[#F0F2F8]">/projects/{id}/scoring-criteria</strong> mediante el botón <strong class="text-[#A78BFA]">Criterios</strong> en la tabla.
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#2E3347]">
                        <button type="button" @click="closeDialog" 
                                class="px-5 py-2.5 rounded-[8px] text-[14px] font-medium text-[#7B82A0] hover:text-[#F0F2F8] transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" 
                                class="bg-[#6C63FF] hover:bg-[#6C63FF]/90 disabled:opacity-50 text-white px-6 py-2.5 rounded-[8px] text-[14px] font-medium transition shadow-sm">
                            {{ dialogMode === 'create' ? 'Crear Proyecto' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </ResponsiveDialog>
    </AppLayout>
</template>
