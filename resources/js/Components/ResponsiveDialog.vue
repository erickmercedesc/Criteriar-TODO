<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

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

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = null;
        }
    }
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 z-50 overflow-y-auto flex" :class="{ 'p-4 sm:p-6': !isMobile }" scroll-region>
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
                    </div>
                </transition>

                <!-- Desktop Modal -->
                <transition v-if="!isMobile"
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-show="show" class="m-auto rounded-[12px] overflow-hidden shadow-xl transform transition-all w-full border border-[#2E3347]" :class="[maxWidthClass]" style="background-color: var(--color-surface, #1A1D27)">
                        <slot />
                    </div>
                </transition>
                
                <!-- Mobile Bottom Sheet -->
                <transition v-else
                    enter-active-class="ease-[cubic-bezier(0.32,0.72,0,1)] duration-300"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="ease-[cubic-bezier(0.32,0.72,0,1)] duration-300"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div v-show="show" class="mt-auto w-full max-h-[85vh] overflow-y-auto rounded-t-[20px] shadow-xl transform transition-all border border-b-0 border-[#2E3347] flex flex-col" style="background-color: var(--color-surface, #1A1D27)">
                        <div class="w-full flex justify-center pt-3 pb-2 sticky top-0 z-10" style="background-color: var(--color-surface, #1A1D27)" @click="close">
                            <div class="w-10 h-1 rounded-full bg-[#2E3347]"></div>
                        </div>
                        <div class="pb-safe">
                            <slot />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
