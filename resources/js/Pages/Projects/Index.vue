<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Plus, Edit2, Trash2, Folder } from 'lucide-vue-next';

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
                                    {{ project.tasks_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditDialog(project)" class="text-[#38BDF8] hover:text-[#38BDF8]/80 mr-4">
                                        <Edit2 class="w-[18px] h-[18px]" />
                                    </button>
                                    <button @click="deleteProject(project)" class="text-[#EF4444] hover:text-[#EF4444]/80">
                                        <Trash2 class="w-[18px] h-[18px]" />
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
                                    {{ project.tasks_count || 0 }} tareas pendientes
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <button @click="openEditDialog(project)" class="text-[#38BDF8] hover:opacity-80">
                                <Edit2 class="w-[20px] h-[20px]" />
                            </button>
                            <button @click="deleteProject(project)" class="text-[#EF4444] hover:opacity-80">
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
