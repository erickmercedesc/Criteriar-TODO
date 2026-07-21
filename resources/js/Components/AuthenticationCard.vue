<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 bg-[#0F1117] relative overflow-hidden">
        <!-- Ambient glow background -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-[#6C63FF] rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-[#F59E0B] rounded-full mix-blend-screen filter blur-[120px] opacity-10"></div>
        
        <div class="relative z-10">
            <slot name="logo" />
        </div>

        <div class="w-[calc(100%-2rem)] mx-4 sm:mx-0 sm:max-w-md mt-8 px-8 py-10 bg-[#1A1D27]/80 backdrop-blur-xl border border-[#2E3347] shadow-[0_8px_32px_rgba(0,0,0,0.4)] overflow-hidden rounded-[24px] relative z-10">
            <slot />
        </div>
    </div>
</template>
