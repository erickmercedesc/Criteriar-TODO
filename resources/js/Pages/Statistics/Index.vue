<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BarChart3, Clock, CheckCircle2, TrendingUp } from 'lucide-vue-next';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    stats: Array,
    today: Object,
    yesterday: Object,
});

// Formatting functions
const formatTime = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (hours > 0) return `${hours}h ${minutes}m`;
    return `${minutes}m`;
};

// Chart Data Preparation
const chartData = computed(() => {
    const labels = props.stats.map(s => {
        const d = new Date(s.date + 'T00:00:00'); // Prevent timezone shift
        return d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
    });

    return {
        labels,
        datasets: [
            {
                label: 'Puntos Ganados',
                backgroundColor: '#6C63FF',
                borderRadius: 4,
                data: props.stats.map(s => s.points_earned),
                yAxisID: 'y',
            },
            {
                label: 'Minutos de Focus',
                backgroundColor: '#22C55E',
                borderRadius: 4,
                data: props.stats.map(s => Math.floor(s.pomodoro_seconds / 60)),
                yAxisID: 'y1',
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            labels: { color: '#F0F2F8' }
        },
        tooltip: {
            backgroundColor: '#1A1D27',
            titleColor: '#F0F2F8',
            bodyColor: '#7B82A0',
            borderColor: '#2E3347',
            borderWidth: 1,
        }
    },
    scales: {
        x: {
            ticks: { color: '#7B82A0' },
            grid: { display: false }
        },
        y: {
            type: 'linear',
            display: true,
            position: 'left',
            ticks: { color: '#7B82A0' },
            grid: { color: '#2E3347' },
            title: {
                display: true,
                text: 'Puntos',
                color: '#6C63FF'
            }
        },
        y1: {
            type: 'linear',
            display: true,
            position: 'right',
            ticks: { color: '#7B82A0' },
            grid: { drawOnChartArea: false },
            title: {
                display: true,
                text: 'Minutos',
                color: '#22C55E'
            }
        },
    }
};
</script>

<template>
    <Head title="Estadísticas" />

    <AppLayout title="Estadísticas">
        <template #header>
            <h2 class="font-semibold text-[22px] text-[#F0F2F8] leading-tight font-inter flex items-center gap-2">
                <BarChart3 class="w-6 h-6 text-[#6C63FF]" />
                Estadísticas Diarias
            </h2>
        </template>

        <div class="py-12 md:py-16">
            <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Resumen de Hoy -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Tiempo de Focus -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10">
                            <Clock class="w-24 h-24 text-[#22C55E]" />
                        </div>
                        <div class="text-[#7B82A0] text-[13px] font-bold uppercase tracking-wider mb-2">Tiempo de Focus (Hoy)</div>
                        <div class="text-[32px] font-bold text-[#F0F2F8]">{{ formatTime(today.pomodoro_seconds) }}</div>
                        <div class="text-[14px] mt-2" :class="today.pomodoro_seconds >= yesterday.pomodoro_seconds ? 'text-[#22C55E]' : 'text-[#EF4444]'">
                            Ayer: {{ formatTime(yesterday.pomodoro_seconds) }}
                        </div>
                    </div>

                    <!-- Tareas Completadas -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10">
                            <CheckCircle2 class="w-24 h-24 text-[#38BDF8]" />
                        </div>
                        <div class="text-[#7B82A0] text-[13px] font-bold uppercase tracking-wider mb-2">Tareas Completadas (Hoy)</div>
                        <div class="text-[32px] font-bold text-[#F0F2F8]">{{ today.tasks_completed }}</div>
                        <div class="text-[14px] mt-2" :class="today.tasks_completed >= yesterday.tasks_completed ? 'text-[#22C55E]' : 'text-[#EF4444]'">
                            Ayer: {{ yesterday.tasks_completed }}
                        </div>
                    </div>

                    <!-- Puntos Ganados -->
                    <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10">
                            <TrendingUp class="w-24 h-24 text-[#6C63FF]" />
                        </div>
                        <div class="text-[#7B82A0] text-[13px] font-bold uppercase tracking-wider mb-2">Puntos Ganados (Hoy)</div>
                        <div class="text-[32px] font-bold text-[#F0F2F8]">{{ today.points_earned }} pts</div>
                        <div class="text-[14px] mt-2" :class="today.points_earned >= yesterday.points_earned ? 'text-[#22C55E]' : 'text-[#EF4444]'">
                            Ayer: {{ yesterday.points_earned }} pts
                        </div>
                    </div>
                </div>

                <!-- Gráfica Histórica -->
                <div class="bg-[#1A1D27] border border-[#2E3347] rounded-[24px] p-6 shadow-sm">
                    <h3 class="text-[#F0F2F8] font-bold text-[18px] mb-6">Historial de Rendimiento (Últimos {{ stats.length }} días)</h3>
                    
                    <div v-if="stats.length > 0" class="h-[400px] w-full">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                    <div v-else class="h-[400px] flex items-center justify-center text-[#7B82A0]">
                        No hay suficientes datos todavía. ¡Completa una tarea o un pomodoro hoy!
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
