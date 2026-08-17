<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Plus, Edit2, Trash2, AlertCircle, ArrowLeft, Sliders, Flame } from 'lucide-vue-next';

const props = defineProps({
    project: Object,
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
    points: 10,
    color: '#6C63FF',
    is_complex_marker: false,
});

const isDialogOpen = ref(false);
const dialogMode = ref('create'); // 'create' or 'edit'

const openCreateDialog = () => {
    form.reset();
    form.clearErrors();
    form.color = props.project.color || '#6C63FF';
    dialogMode.value = 'create';
    isDialogOpen.value = true;
};

const openEditDialog = (criterion) => {
    form.reset();
    form.clearErrors();
    form.id = criterion.id;
    form.name = criterion.name;
    form.points = criterion.points;
    form.color = criterion.color;
    form.is_complex_marker = !!criterion.is_complex_marker;
    dialogMode.value = 'edit';
    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (dialogMode.value === 'create') {
        form.post(route('projects.scoring-criteria.store', props.project.id), {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.put(route('projects.scoring-criteria.update', [props.project.id, form.id]), {
            onSuccess: () => closeDialog(),
        });
    }
};

const deleteCriterion = (criterion) => {
    if (confirm(`¿Estás seguro de que quieres eliminar el criterio "${criterion.name}" del proyecto ${props.project.name}?`)) {
        router.delete(route('projects.scoring-criteria.destroy', [props.project.id, criterion.id]));
    }
};
</script>

<template>
    <AppLayout :title="`Criterios - ${project.name}`">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('projects.index')" 
                          class="p-2 text-[#7B82A0] hover:text-[#F0F2F8] hover:bg-[#1A1D27] rounded-lg transition-colors border border-[#2E3347]">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: project.color }"></span>
                            <h2 class="font-semibold text-[20px] md:text-[22px] text-[#F0F2F8] leading-tight font-inter">
                                Criterios: {{ project.name }}
                            </h2>
                        </div>
                        <p class="text-[13px] text-[#7B82A0] mt-0.5">
                            Puntuación Base del Proyecto: <strong class="text-[#F0F2F8]">{{ project.base_score }} pts</strong>
                        </p>
                    </div>
                </div>

                <button @click="openCreateDialog" 
                        class="bg-[#6C63FF] hover:bg-[#6C63FF]/90 text-white px-[16px] md:px-[20px] py-[8px] md:py-[10px] rounded-[8px] text-[15px] font-medium transition flex items-center justify-center gap-2 shadow-sm">
                    <Plus class="w-[18px] h-[18px]" />
                    Nuevo Criterio de Proyecto
                </button>
            </div>
        </template>

        <div class="py-6 md:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Context Info Card -->
            <div class="mb-6 bg-[#1A1D27] border border-[#2E3347] rounded-[16px] p-4 md:p-5 flex items-start gap-4">
                <div class="p-2.5 rounded-[10px] bg-[#6C63FF]/10 text-[#6C63FF] shrink-0 mt-0.5">
                    <Sliders class="w-5 h-5" />
                </div>
                <div class="text-[14px] leading-relaxed text-[#7B82A0]">
                    Estos criterios son exclusivos para las tareas asociadas al proyecto <strong class="text-[#F0F2F8]">{{ project.name }}</strong>.
                    Al crear una tarea en este proyecto, se sumarán sus puntos a la puntuación base del proyecto (<strong class="text-[#F0F2F8]">{{ project.base_score }} pts</strong>).
                </div>
            </div>

            <!-- Desktop View: Table -->
            <div v-if="!isMobile" class="bg-[#1A1D27] border border-[#2E3347] rounded-[16px] overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-[#2E3347]">
                    <thead class="bg-[#13151F]">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Color</th>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Puntos</th>
                            <th scope="col" class="px-6 py-4 text-left text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Complejidad</th>
                            <th scope="col" class="px-6 py-4 text-right text-[12px] font-bold text-[#7B82A0] uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2E3347]">
                        <tr v-for="criterion in criteria" :key="criterion.id" class="hover:bg-[#222634] transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block w-4 h-4 rounded-full" :style="{ backgroundColor: criterion.color }"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-[15px] font-medium text-[#F0F2F8]">
                                {{ criterion.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-[15px] font-mono" :class="criterion.points >= 0 ? 'text-[#22C55E]' : 'text-[#EF4444]'">
                                {{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }} pts
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="criterion.is_complex_marker" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[6px] text-[11px] font-bold bg-[#EF4444]/10 text-[#EF4444] border border-[#EF4444]/20">
                                    <Flame class="w-3.5 h-3.5" /> Marcador Complejo
                                </span>
                                <span v-else class="text-[#7B82A0] text-[13px]">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openEditDialog(criterion)" class="text-[#38BDF8] hover:text-[#38BDF8]/80 mr-4">
                                    <Edit2 class="w-[18px] h-[18px]" />
                                </button>
                                <button @click="deleteCriterion(criterion)" class="text-[#EF4444] hover:text-[#EF4444]/80">
                                    <Trash2 class="w-[18px] h-[18px]" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="criteria.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-[#7B82A0] text-[15px]">
                                No hay criterios específicos para este proyecto. ¡Crea el primero!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Cards -->
            <div v-else class="space-y-3">
                <div v-for="criterion in criteria" :key="criterion.id" class="bg-[#1A1D27] border border-[#2E3347] rounded-[16px] p-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-4 h-4 rounded-full flex-shrink-0" :style="{ backgroundColor: criterion.color }"></span>
                        <div>
                            <div class="text-[16px] font-medium text-[#F0F2F8]">
                                {{ criterion.name }}
                            </div>
                            <div class="text-[13px] font-mono mt-0.5 flex items-center gap-2" :class="criterion.points >= 0 ? 'text-[#22C55E]' : 'text-[#EF4444]'">
                                <span>{{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }} pts</span>
                                <span v-if="criterion.is_complex_marker" class="text-[10px] bg-[#EF4444]/15 text-[#EF4444] px-1.5 py-0.5 rounded font-sans font-bold">
                                    Complejo
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="openEditDialog(criterion)" class="text-[#38BDF8] p-2 hover:bg-[#222634] rounded-lg">
                            <Edit2 class="w-[18px] h-[18px]" />
                        </button>
                        <button @click="deleteCriterion(criterion)" class="text-[#EF4444] p-2 hover:bg-[#222634] rounded-lg">
                            <Trash2 class="w-[18px] h-[18px]" />
                        </button>
                    </div>
                </div>
                <div v-if="criteria.length === 0" class="text-center text-[#7B82A0] py-12 text-[15px]">
                    No hay criterios específicos para este proyecto. ¡Crea el primero!
                </div>
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <ResponsiveDialog :show="isDialogOpen" @close="closeDialog" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 rounded-[10px]" :style="{ backgroundColor: `${form.color}20`, color: form.color }">
                        <Sliders class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[18px] font-bold text-[#F0F2F8]">
                            {{ dialogMode === 'create' ? 'Nuevo Criterio de Proyecto' : 'Editar Criterio' }}
                        </h3>
                        <p class="text-[12px] text-[#7B82A0]">
                            Proyecto: {{ project.name }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label class="block text-[13px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2">
                            Nombre del Criterio
                        </label>
                        <input type="text" v-model="form.name" required placeholder="Ej. Stripe Integration, AWS Lab..." 
                               class="w-full bg-[#13151F] border border-[#2E3347] rounded-[10px] px-4 py-3 text-[#F0F2F8] focus:border-[#6C63FF] focus:ring-1 focus:ring-[#6C63FF] transition text-[15px]" />
                        <div v-if="form.errors.name" class="text-[#EF4444] text-[12px] mt-1.5 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Points -->
                    <div>
                        <label class="block text-[13px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2">
                            Puntos (-100 a 100)
                        </label>
                        <input type="number" v-model="form.points" min="-100" max="100" required 
                               class="w-full bg-[#13151F] border border-[#2E3347] rounded-[10px] px-4 py-3 text-[#F0F2F8] font-mono focus:border-[#6C63FF] focus:ring-1 focus:ring-[#6C63FF] transition text-[15px]" />
                        <p class="text-[12px] text-[#7B82A0] mt-1.5">
                            Valores positivos suman prioridad, valores negativos restan prioridad.
                        </p>
                        <div v-if="form.errors.points" class="text-[#EF4444] text-[12px] mt-1.5 flex items-center gap-1">
                            <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.points }}
                        </div>
                    </div>

                    <!-- Color Swatches -->
                    <div>
                        <label class="block text-[13px] font-bold text-[#7B82A0] uppercase tracking-wider mb-2">
                            Color Identificador
                        </label>
                        <div class="flex items-center gap-3 flex-wrap">
                            <button v-for="color in colorOptions" :key="color" type="button" @click="form.color = color" 
                                    class="w-8 h-8 rounded-full transition-transform flex items-center justify-center"
                                    :style="{ backgroundColor: color }"
                                    :class="form.color === color ? 'scale-125 ring-2 ring-white ring-offset-2 ring-offset-[#1A1D27]' : 'hover:scale-110'">
                            </button>
                        </div>
                    </div>

                    <!-- Complex Marker -->
                    <div class="bg-[#13151F] border border-[#2E3347] rounded-[12px] p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" v-model="form.is_complex_marker" 
                                   class="mt-1 rounded bg-[#1A1D27] border-[#2E3347] text-[#6C63FF] focus:ring-0 w-4 h-4" />
                            <div>
                                <div class="text-[14px] font-bold text-[#F0F2F8] flex items-center gap-1.5">
                                    <Flame class="w-4 h-4 text-[#F59E0B]" /> Marcar como Criterio Complejo
                                </div>
                                <p class="text-[12px] text-[#7B82A0] mt-0.5">
                                    Si una tarea tiene este criterio, al terminar un Pomodoro se consultará si deseas continuar o saltarla para evitar estancamientos.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Dialog Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#2E3347]">
                        <button type="button" @click="closeDialog" 
                                class="px-5 py-2.5 rounded-[8px] text-[14px] font-medium text-[#7B82A0] hover:text-[#F0F2F8] transition">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" 
                                class="bg-[#6C63FF] hover:bg-[#6C63FF]/90 disabled:opacity-50 text-white px-6 py-2.5 rounded-[8px] text-[14px] font-medium transition shadow-sm">
                            {{ dialogMode === 'create' ? 'Crear Criterio' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </ResponsiveDialog>
    </AppLayout>
</template>
