import { ref, readonly } from 'vue';

const STORAGE_KEY = 'working-project';

// Module-level reactive ref so all consumers share the same state
const workingProjectId = ref(initWorkingProject());

function initWorkingProject() {
    if (typeof window === 'undefined' || !window.localStorage) {
        return '';
    }
    const val = localStorage.getItem(STORAGE_KEY);
    if (!val || val === 'null' || val === 'undefined') {
        return '';
    }
    // Return parsed number if numeric, otherwise string
    const num = Number(val);
    return isNaN(num) ? val : num;
}

// Listen to storage events to sync across tabs
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY) {
            workingProjectId.value = initWorkingProject();
        }
    });
}

export function useWorkingProject() {
    const getWorkingProject = () => {
        return workingProjectId.value;
    };

    const setWorkingProject = (id) => {
        if (!id || id === 'null' || id === 'undefined') {
            workingProjectId.value = '';
            if (typeof window !== 'undefined' && window.localStorage) {
                localStorage.removeItem(STORAGE_KEY);
            }
        } else {
            const parsed = isNaN(Number(id)) ? id : Number(id);
            workingProjectId.value = parsed;
            if (typeof window !== 'undefined' && window.localStorage) {
                localStorage.setItem(STORAGE_KEY, String(parsed));
            }
        }
    };

    const syncWithProjects = (availableProjects) => {
        if (!workingProjectId.value || !Array.isArray(availableProjects) || availableProjects.length === 0) {
            return;
        }
        if (workingProjectId.value === 'none') {
            return;
        }
        const exists = availableProjects.some(p => p.id === workingProjectId.value || String(p.id) === String(workingProjectId.value));
        if (!exists) {
            setWorkingProject('');
        }
    };

    return {
        workingProjectId,
        getWorkingProject,
        setWorkingProject,
        syncWithProjects,
    };
}
