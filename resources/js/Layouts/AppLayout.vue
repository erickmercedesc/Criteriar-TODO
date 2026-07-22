<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckSquare, Sliders, Timer, LogOut, Settings, LayoutDashboard } from 'lucide-vue-next';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: String,
});

const logout = () => {
    router.post(route('logout'));
};

const navigation = [
    { name: 'Dashboard', route: 'dashboard', icon: LayoutDashboard },
    { name: 'Pomodoro', route: 'pomodoro.index', icon: Timer },
    { name: 'Tareas', route: 'tasks.index', icon: CheckSquare },
    { name: 'Criterios', route: 'scoring-criteria.index', icon: Sliders },
    { name: 'Configuración', route: 'settings.index', icon: Settings },
];
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-[#0F1117] flex">
            <!-- DESKTOP SIDEBAR (≥ 768px) -->
            <aside class="hidden md:flex flex-col w-[240px] bg-[#1A1D27] border-r border-[#2E3347] fixed inset-y-0 z-10">
                <!-- Logo -->
                <div class="h-16 flex items-center px-6 border-b border-[#2E3347]">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#6C63FF] rounded-[8px] flex items-center justify-center">
                            <span class="text-white font-inter font-bold text-sm tracking-tight">SB</span>
                        </div>
                        <span class="text-[#F0F2F8] font-bold text-[18px]">SecondBrain</span>
                    </Link>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-[8px] transition-colors group"
                        :class="[
                            route().current(item.route) 
                                ? 'bg-[#6C63FF26] text-[#6C63FF] border-l-4 border-[#6C63FF]' 
                                : 'text-[#7B82A0] hover:bg-[#22263A] hover:text-[#F0F2F8] border-l-4 border-transparent'
                        ]"
                    >
                        <component :is="item.icon" class="w-[20px] h-[20px]" />
                        <span class="font-medium text-[15px]">{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- User Profile / Settings -->
                <div class="p-4 border-t border-[#2E3347]">
                    <Dropdown align="top" width="48">
                        <template #trigger>
                            <button class="w-full flex items-center gap-3 p-2 rounded-[8px] hover:bg-[#22263A] transition-colors text-left">
                                <div class="w-10 h-10 rounded-full bg-[#2E3347] flex items-center justify-center overflow-hidden">
                                    <img v-if="$page.props.jetstream.managesProfilePhotos" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover" />
                                    <span v-else class="text-[#F0F2F8] font-bold">{{ $page.props.auth.user.name.charAt(0) }}</span>
                                </div>
                                <div class="flex-1 truncate">
                                    <div class="text-[#F0F2F8] text-[14px] font-medium truncate">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-[#7B82A0] text-[12px] truncate">{{ $page.props.auth.user.email }}</div>
                                </div>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.show')" class="!text-[#F0F2F8] hover:!bg-[#22263A]">
                                <div class="flex items-center gap-2">
                                    <Settings class="w-4 h-4" /> Perfil
                                </div>
                            </DropdownLink>
                            <div class="border-t border-[#2E3347]" />
                            <form @submit.prevent="logout">
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm leading-5 !text-[#EF4444] hover:!bg-[#22263A] transition">
                                    <div class="flex items-center gap-2">
                                        <LogOut class="w-4 h-4" /> Cerrar sesión
                                    </div>
                                </button>
                            </form>
                        </template>
                    </Dropdown>
                </div>
            </aside>

            <!-- MOBILE TOP BAR (< 768px) -->
            <header class="md:hidden fixed top-0 inset-x-0 h-[60px] bg-[#1A1D27] border-b border-[#2E3347] z-20 flex items-center justify-between px-4">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#6C63FF] rounded-[8px] flex items-center justify-center">
                        <span class="text-white font-inter font-bold text-sm tracking-tight">SB</span>
                    </div>
                    <span class="text-[#F0F2F8] font-bold text-[18px]">SecondBrain</span>
                </Link>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="w-8 h-8 rounded-full bg-[#2E3347] flex items-center justify-center overflow-hidden border border-[#7B82A0]">
                            <img v-if="$page.props.jetstream.managesProfilePhotos" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover" />
                            <span v-else class="text-[#F0F2F8] font-bold text-xs">{{ $page.props.auth.user.name.charAt(0) }}</span>
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.show')" class="!text-[#F0F2F8] hover:!bg-[#22263A]">Perfil</DropdownLink>
                        <div class="border-t border-[#2E3347]" />
                        <form @submit.prevent="logout">
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm !text-[#EF4444] hover:!bg-[#22263A]">Cerrar sesión</button>
                        </form>
                    </template>
                </Dropdown>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 md:pl-[240px] pt-[60px] md:pt-0 pb-[90px] md:pb-0">
                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-[#1A1D27] border-b border-[#2E3347] shadow-sm">
                    <div class="max-w-[1200px] mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <slot />
            </main>

            <!-- MOBILE BOTTOM NAVIGATION (< 768px) -->
            <nav class="md:hidden fixed bottom-0 inset-x-0 h-[72px] bg-[#1A1D27] border-t border-[#2E3347] z-20 flex pb-safe">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex-1 flex flex-col items-center justify-center gap-1.5 transition-colors"
                    :class="route().current(item.route) ? 'text-[#6C63FF]' : 'text-[#7B82A0] hover:text-[#F0F2F8]'"
                >
                    <component :is="item.icon" class="w-[22px] h-[22px]" />
                    <span class="text-[11px] font-medium">{{ item.name }}</span>
                </Link>
            </nav>
        </div>
    </div>
</template>

<style>
/* Safe area padding for mobile (notches, home bars) */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .pb-safe {
        padding-bottom: env(safe-area-inset-bottom);
    }
}
</style>
