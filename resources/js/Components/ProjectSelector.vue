<script setup>
import { ref, computed } from 'vue';
import ResponsiveDialog from '@/Components/ResponsiveDialog.vue';
import { Search, ChevronDown, FolderOpen, Inbox } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    projects: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Seleccionar proyecto...'
    },
    showGlobalOption: {
        type: Boolean,
        default: true
    },
    showWithoutProjectOption: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');

const filteredProjects = computed(() => {
    if (!searchQuery.value) return props.projects;
    const query = searchQuery.value.toLowerCase();
    return props.projects.filter(p => p.name.toLowerCase().includes(query));
});

const selectedProject = computed(() => {
    if (props.modelValue === 'none') {
        return {
            id: 'none',
            name: 'Sin Proyecto',
            color: '#7B82A0',
            isSpecial: true,
        };
    }
    if (!props.modelValue) return null;
    return props.projects.find(p => p.id === props.modelValue || String(p.id) === String(props.modelValue)) || null;
});

const selectProject = (id) => {
    emit('update:modelValue', id);
    emit('change', id);
    isOpen.value = false;
};

const openDialog = () => {
    searchQuery.value = '';
    isOpen.value = true;
};
</script>

<template>
    <div>
        <!-- Trigger Button -->
        <button type="button" @click="openDialog"
                class="w-full sm:w-auto bg-[#1A1D27] border border-[#2E3347] hover:border-[#6C63FF] text-[#F0F2F8] rounded-[6px] px-3 py-2 sm:py-1.5 text-[14px] focus:ring-[#6C63FF] focus:border-[#6C63FF] transition-colors flex items-center justify-between gap-3 text-left">
            <div class="flex items-center gap-2 truncate">
                <Inbox v-if="selectedProject && selectedProject.id === 'none'" class="w-4 h-4 text-[#7B82A0]" />
                <FolderOpen v-else-if="selectedProject" class="w-4 h-4" :style="{ color: selectedProject.color }" />
                <FolderOpen v-else class="w-4 h-4 text-[#7B82A0]" />
                <span :class="selectedProject ? 'text-[#F0F2F8]' : 'text-[#7B82A0]'">
                    {{ selectedProject ? selectedProject.name : placeholder }}
                </span>
            </div>
            <ChevronDown class="w-4 h-4 text-[#7B82A0] flex-shrink-0" />
        </button>

        <!-- Selection Modal -->
        <ResponsiveDialog :show="isOpen" @close="isOpen = false" maxWidth="sm">
            <div class="p-6">
                <h3 class="text-[18px] font-semibold text-[#F0F2F8] mb-4 font-inter">
                    Seleccionar Proyecto
                </h3>

                <!-- Search -->
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-4 w-4 text-[#7B82A0]" />
                    </div>
                    <input type="text" v-model="searchQuery"
                           class="block w-full pl-10 bg-[#0F1117] border border-[#2E3347] text-[#F0F2F8] rounded-[6px] py-2 text-[15px] focus:ring-[#6C63FF] focus:border-[#6C63FF] placeholder-[#7B82A0]"
                           placeholder="Buscar proyecto...">
                </div>

                <!-- List -->
                <div class="max-h-[300px] overflow-y-auto pr-2 space-y-1 custom-scrollbar">
                    <!-- Global Option (All Projects) -->
                    <button v-if="showGlobalOption" @click="selectProject('')"
                            class="w-full text-left px-3 py-2.5 rounded-[6px] hover:bg-[#22263A] transition-colors flex items-center gap-3"
                            :class="!modelValue ? 'bg-[#6C63FF]/10 text-[#6C63FF]' : 'text-[#F0F2F8]'">
                        <div class="w-6 h-6 rounded-full bg-[#2E3347] flex items-center justify-center flex-shrink-0">
                            <FolderOpen class="w-3.5 h-3.5 text-[#7B82A0]" />
                        </div>
                        <span class="font-medium">Global (Todos los proyectos)</span>
                    </button>

                    <!-- Without Project Option (No project assigned) -->
                    <button v-if="showWithoutProjectOption" @click="selectProject('none')"
                            class="w-full text-left px-3 py-2.5 rounded-[6px] hover:bg-[#22263A] transition-colors flex items-center gap-3"
                            :class="modelValue === 'none' ? 'bg-[#6C63FF]/10 text-[#6C63FF]' : 'text-[#F0F2F8]'">
                        <div class="w-6 h-6 rounded-full bg-[#2E3347] flex items-center justify-center flex-shrink-0">
                            <Inbox class="w-3.5 h-3.5 text-[#7B82A0]" />
                        </div>
                        <span class="font-medium">Sin Proyecto (Tareas sin asignar)</span>
                    </button>

                    <!-- Projects List -->
                    <button v-for="project in filteredProjects" :key="project.id"
                            @click="selectProject(project.id)"
                            class="w-full text-left px-3 py-2.5 rounded-[6px] hover:bg-[#22263A] transition-colors flex items-center justify-between gap-3"
                            :class="modelValue === project.id ? 'bg-[#6C63FF]/10' : ''">
                        <div class="flex items-center gap-3 truncate">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
                                 :style="{ backgroundColor: `${project.color}26`, color: project.color }">
                                <FolderOpen class="w-3.5 h-3.5" />
                            </div>
                            <span class="font-medium truncate" :class="modelValue === project.id ? 'text-[#6C63FF]' : 'text-[#F0F2F8]'">
                                {{ project.name }}
                            </span>
                        </div>
                        <span v-if="project.base_score" class="text-[12px] font-mono text-[#38BDF8] shrink-0 font-medium">
                            +{{ project.base_score }} pts
                        </span>
                    </button>

                    <div v-if="filteredProjects.length === 0" class="text-center py-6 text-[#7B82A0] text-[14px]">
                        No se encontraron proyectos.
                    </div>
                </div>
            </div>
        </ResponsiveDialog>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #2E3347;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #7B82A0;
}
</style>
