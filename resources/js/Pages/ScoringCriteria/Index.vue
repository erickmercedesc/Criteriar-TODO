<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Plus, Edit2, Trash2 } from 'lucide-vue-next';

const props = defineProps({
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
});

const isDialogOpen = ref(false);
const dialogMode = ref('create'); // 'create' or 'edit'

const openCreateDialog = () => {
    form.reset();
    form.clearErrors();
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
    dialogMode.value = 'edit';
    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (dialogMode.value === 'create') {
        form.post(route('scoring-criteria.store'), {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.put(route('scoring-criteria.update', form.id), {
            onSuccess: () => closeDialog(),
        });
    }
};

const deleteCriterion = (criterion) => {
    if (confirm(`¿Estás seguro de que quieres eliminar el criterio "${criterion.name}"?`)) {
        router.delete(route('scoring-criteria.destroy', criterion.id));
    }
};
</script>

<template>
    <AppLayout title="Criterios">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter">
                    Criterios de Scoring
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
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Puntos</th>
                                <th scope="col" class="px-6 py-3 text-left text-[11px] font-medium text-[#7B82A0] uppercase tracking-[0.08em]">Color</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2E3347]">
                            <tr v-for="criterion in criteria" :key="criterion.id" class="group hover:bg-[#22263A] transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-[15px] text-[#F0F2F8] font-medium">
                                    {{ criterion.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-[16px] text-[#F0F2F8] font-mono font-medium">
                                    {{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }} pts
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[11px] font-semibold"
                                         :style="{ backgroundColor: `${criterion.color}33`, color: criterion.color }">
                                        Muestra
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditDialog(criterion)" class="text-[#38BDF8] hover:text-[#38BDF8]/80 mr-4">
                                        <Edit2 class="w-[18px] h-[18px]" />
                                    </button>
                                    <button @click="deleteCriterion(criterion)" class="text-[#EF4444] hover:text-[#EF4444]/80">
                                        <Trash2 class="w-[18px] h-[18px]" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="criteria.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-[#7B82A0] text-[15px]">
                                    No hay criterios configurados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div v-else class="space-y-[8px] px-4">
                    <div v-for="criterion in criteria" :key="criterion.id" class="bg-[#1A1D27] border border-[#2E3347] rounded-[12px] p-[16px] flex justify-between items-center shadow-[0_2px_12px_rgba(0,0,0,0.3)]">
                        <div>
                            <div class="inline-flex items-center px-2 py-0.5 rounded-[6px] text-[11px] font-semibold mb-2"
                                 :style="{ backgroundColor: `${criterion.color}33`, color: criterion.color }">
                                {{ criterion.points > 0 ? '+' : '' }}{{ criterion.points }} pts
                            </div>
                            <div class="text-[15px] text-[#F0F2F8] font-medium">{{ criterion.name }}</div>
                        </div>
                        <div class="flex items-center gap-4">
                            <button @click="openEditDialog(criterion)" class="text-[#38BDF8] hover:opacity-80">
                                <Edit2 class="w-[20px] h-[20px]" />
                            </button>
                            <button @click="deleteCriterion(criterion)" class="text-[#EF4444] hover:opacity-80">
                                <Trash2 class="w-[20px] h-[20px]" />
                            </button>
                        </div>
                    </div>
                    <div v-if="criteria.length === 0" class="text-center text-[#7B82A0] py-8 text-[15px]">
                        No hay criterios configurados.
                    </div>
                </div>

            </div>
        </div>

        <!-- Form Dialog (Responsive) -->
        <ResponsiveDialog :show="isDialogOpen" @close="closeDialog" maxWidth="md">
            <div class="p-6">
                <h3 class="text-[18px] font-semibold text-[#F0F2F8] mb-6 font-inter">
                    {{ dialogMode === 'create' ? 'Nuevo Criterio' : 'Editar Criterio' }}
                </h3>
                
                <form @submit.prevent="submitForm">
                    <!-- Nombre -->
                    <div class="mb-4">
                        <label for="name" class="block text-[13px] text-[#7B82A0] mb-1">Nombre</label>
                        <input type="text" id="name" v-model="form.name" 
                               class="w-full bg-[#0F1117] border border-[#2E3347] text-[#F0F2F8] rounded-[6px] px-3 py-2 text-[15px] focus:ring-[#6C63FF] focus:border-[#6C63FF]"
                               placeholder="Ej: Genera dinero" required>
                        <div v-if="form.errors.name" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Puntos -->
                    <div class="mb-4">
                        <label for="points" class="block text-[13px] text-[#7B82A0] mb-1">Puntos (-100 a 100)</label>
                        <input type="number" id="points" v-model="form.points" min="-100" max="100"
                               class="w-full bg-[#0F1117] border border-[#2E3347] text-[#F0F2F8] rounded-[6px] px-3 py-2 text-[16px] font-mono focus:ring-[#6C63FF] focus:border-[#6C63FF]"
                               required>
                        <div v-if="form.errors.points" class="text-[#EF4444] text-[11px] mt-1">{{ form.errors.points }}</div>
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
