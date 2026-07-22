<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Sliders, LayoutDashboard, Timer, ArrowRight, Brain } from 'lucide-vue-next';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="SecondBrain - Sistema de Enfoque" />

    <div class="min-h-screen bg-[#0F1117] text-[#F0F2F8] font-inter selection:bg-[#6C63FF] selection:text-white">
        <!-- Header -->
        <header class="fixed top-0 inset-x-0 h-[80px] bg-[#0F1117]/80 backdrop-blur-md border-b border-[#2E3347] z-50 transition-all">
            <div class="max-w-7xl mx-auto px-6 h-full flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#6C63FF] rounded-[10px] flex items-center justify-center shadow-[0_0_15px_rgba(108,99,255,0.4)]">
                        <span class="text-white font-bold text-lg tracking-tight">SB</span>
                    </div>
                    <span class="font-bold text-[22px] tracking-tight">SecondBrain</span>
                </div>

                <!-- Navigation -->
                <nav v-if="canLogin" class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="text-[#F0F2F8] font-semibold hover:text-[#6C63FF] transition-colors"
                    >
                        Ir al Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-[#7B82A0] font-semibold hover:text-[#F0F2F8] transition-colors px-4 py-2"
                        >
                            Iniciar sesión
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="bg-[#6C63FF] hover:bg-[#5A51D1] text-white px-5 py-2.5 rounded-[8px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.2)] hover:shadow-[0_0_20px_rgba(108,99,255,0.4)] transform hover:-translate-y-0.5"
                        >
                            Comenzar Ahora
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="pt-[140px] pb-20 px-6 relative overflow-hidden">
            <!-- Background glow -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-[#6C63FF]/20 blur-[120px] rounded-full pointer-events-none"></div>

            <div class="max-w-4xl mx-auto text-center relative z-10">
                <div class="inline-flex items-center gap-2 bg-[#6C63FF]/10 text-[#6C63FF] border border-[#6C63FF]/20 px-4 py-1.5 rounded-full text-[13px] font-bold uppercase tracking-widest mb-8">
                    <Brain class="w-4 h-4" /> La revolución de la productividad
                </div>
                
                <h1 class="text-[48px] md:text-[72px] font-bold leading-[1.1] tracking-tight mb-8">
                    No es otra lista de tareas.<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#6C63FF] to-[#4facfe]">Es un sistema de enfoque.</span>
                </h1>
                
                <p class="text-[18px] md:text-[22px] text-[#7B82A0] mb-12 max-w-2xl mx-auto leading-relaxed">
                    Prioriza de forma inteligente mediante criterios configurables, trabaja en una sola cosa a la vez, y descansa automáticamente.
                </p>

                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-flex items-center justify-center gap-3 bg-[#6C63FF] text-white px-8 py-4 rounded-[12px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.3)] hover:shadow-[0_0_25px_rgba(108,99,255,0.5)] transform hover:-translate-y-1"
                >
                    Abrir mi SecondBrain <ArrowRight class="w-5 h-5" />
                </Link>
                <Link
                    v-else
                    :href="route('register')"
                    class="inline-flex items-center justify-center gap-3 bg-[#6C63FF] text-white px-8 py-4 rounded-[12px] text-[18px] font-bold transition-all shadow-[0_4px_12px_rgba(108,99,255,0.3)] hover:shadow-[0_0_25px_rgba(108,99,255,0.5)] transform hover:-translate-y-1"
                >
                    Comenzar Ahora - Es Gratis <ArrowRight class="w-5 h-5" />
                </Link>
            </div>
        </main>

        <!-- Features Section -->
        <section class="py-24 bg-[#1A1D27]/50 border-t border-[#2E3347] relative">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-20">
                    <h2 class="text-[32px] md:text-[42px] font-bold mb-4">¿Por qué SecondBrain?</h2>
                    <p class="text-[#7B82A0] text-[18px]">Diseñado para eliminar la parálisis por análisis y maximizar la ejecución.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] p-8 rounded-[24px] hover:border-[#6C63FF]/50 transition-colors group">
                        <div class="w-14 h-14 bg-[#6C63FF]/10 rounded-[16px] flex items-center justify-center mb-6 group-hover:bg-[#6C63FF] transition-colors">
                            <Sliders class="w-7 h-7 text-[#6C63FF] group-hover:text-white transition-colors" />
                        </div>
                        <h3 class="text-[22px] font-bold mb-3">Sistema de Criterios</h3>
                        <p class="text-[#7B82A0] leading-relaxed">
                            Olvida las prioridades "Alta/Media/Baja". Asigna un valor real a tus tareas basándote en criterios que tú defines (ej: Urgencia, Dinero, Esfuerzo).
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] p-8 rounded-[24px] hover:border-[#F59E0B]/50 transition-colors group">
                        <div class="w-14 h-14 bg-[#F59E0B]/10 rounded-[16px] flex items-center justify-center mb-6 group-hover:bg-[#F59E0B] transition-colors">
                            <LayoutDashboard class="w-7 h-7 text-[#F59E0B] group-hover:text-black transition-colors" />
                        </div>
                        <h3 class="text-[22px] font-bold mb-3">Dashboard de Enfoque</h3>
                        <p class="text-[#7B82A0] leading-relaxed">
                            Las listas largas causan ansiedad. Nuestro Dashboard te muestra <strong>solo una tarea a la vez</strong>. Mírala, ejecútala, y pasa a la siguiente.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] p-8 rounded-[24px] hover:border-[#EF4444]/50 transition-colors group">
                        <div class="w-14 h-14 bg-[#EF4444]/10 rounded-[16px] flex items-center justify-center mb-6 group-hover:bg-[#EF4444] transition-colors">
                            <Timer class="w-7 h-7 text-[#EF4444] group-hover:text-white transition-colors" />
                        </div>
                        <h3 class="text-[22px] font-bold mb-3">Pomodoro Integrado</h3>
                        <p class="text-[#7B82A0] leading-relaxed">
                            Mantén el ritmo con ciclos de trabajo automáticos. El sistema te obliga a tomar descansos cortos y largos después de 4 ciclos intensos.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 border-t border-[#2E3347] bg-[#0F1117] text-center">
            <div class="flex items-center justify-center gap-2 text-[#7B82A0] text-[14px]">
                <div class="w-6 h-6 bg-[#6C63FF] rounded-[6px] flex items-center justify-center opacity-80">
                    <span class="text-white font-bold text-[10px]">SB</span>
                </div>
                <span>SecondBrain &copy; {{ new Date().getFullYear() }}</span>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.font-inter {
    font-family: 'Inter', sans-serif;
}
</style>
