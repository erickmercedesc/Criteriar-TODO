<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-[#6C63FF] border border-transparent rounded-[6px] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-85 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6C63FF] focus:ring-offset-2 focus:ring-offset-[#0F1117] transition ease-in-out duration-150">
        <slot />
    </button>
</template>
