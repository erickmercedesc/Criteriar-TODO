<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#0F1117]">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-[#1A1D27] border border-[#2E3347] shadow-[0_2px_12px_rgba(0,0,0,0.3)] overflow-hidden sm:rounded-[12px]">
            <slot />
        </div>
    </div>
</template>
