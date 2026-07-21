<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>


<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded bg-[#0F1117] border-[#2E3347] text-[#6C63FF] shadow-sm focus:ring-[#6C63FF] focus:border-[#6C63FF]"
    >
</template>
