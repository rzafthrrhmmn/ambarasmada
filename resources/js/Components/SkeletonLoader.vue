<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'card',
    },
    lines: {
        type: Number,
        default: 3,
    },
    class: {
        type: String,
        default: '',
    },
});

const extraCardLines = computed(() => Math.max(0, props.lines - 3));
const textLineWidths = computed(() =>
    Array.from({ length: props.lines }, (_, i) => `${70 + ((i * 13) % 25)}%`),
);
</script>

<template>
    <div :class="['skeleton-loader', props.class]">
        <div v-if="variant === 'table'" class="animate-pulse">
            <div class="h-10 rounded-t bg-[#335233] dark:bg-[#446b44]"></div>
            <div v-for="i in 5" :key="i" class="h-12 border-t border-[#6F9435]/30">
                <div class="flex h-full items-center space-x-4 px-4">
                    <div class="h-5 w-1/4 rounded bg-[#335233] dark:bg-[#446b44]"></div>
                    <div class="h-4 w-1/3 rounded bg-[#335233] dark:bg-[#446b44]"></div>
                    <div class="h-4 w-1/4 rounded bg-[#335233] dark:bg-[#446b44]"></div>
                </div>
            </div>
        </div>

        <div v-else-if="variant === 'list'" class="animate-pulse space-y-4">
            <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                <div class="h-12 w-12 rounded-full bg-[#335233] dark:bg-[#446b44]"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-5 w-3/4 rounded bg-[#335233] dark:bg-[#446b44]"></div>
                    <div class="h-4 w-1/2 rounded bg-[#335233] dark:bg-[#446b44]"></div>
                </div>
            </div>
        </div>

        <div v-else-if="variant === 'chart'" class="h-64 animate-pulse rounded bg-[#335233] dark:bg-[#446b44]"></div>

        <div v-else-if="variant === 'text'" class="animate-pulse space-y-2">
            <div
                v-for="(width, i) in textLineWidths"
                :key="i"
                class="h-4 rounded bg-[#335233] dark:bg-[#446b44]"
                :style="{ width }"
            ></div>
        </div>

        <div v-else class="animate-pulse space-y-3">
            <div class="h-6 w-3/4 rounded bg-[#335233] dark:bg-[#446b44]"></div>
            <div class="h-4 w-1/2 rounded bg-[#335233] dark:bg-[#446b44]"></div>
            <div class="h-4 w-1/3 rounded bg-[#335233] dark:bg-[#446b44]"></div>
            <div
                v-for="i in extraCardLines"
                :key="i"
                class="h-4 w-full rounded bg-[#335233] dark:bg-[#446b44]"
            ></div>
        </div>
    </div>
</template>

<style scoped>
.skeleton-loader {
    border-radius: 0.5rem;
    background-color: #335233;
    padding: 1rem;
}
</style>
