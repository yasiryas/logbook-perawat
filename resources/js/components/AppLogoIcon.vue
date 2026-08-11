<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M11 2h10a5 5 0 0 1 5 5v18a5 5 0 0 1-5 5H11a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5ZM6.9 8a1.2 1.2 0 1 0 0 2.4a1.2 1.2 0 1 0 0-2.4Zm0 5.8a1.2 1.2 0 1 0 0 2.4a1.2 1.2 0 1 0 0-2.4Zm0 5.8a1.2 1.2 0 1 0 0 2.4a1.2 1.2 0 1 0 0-2.4Zm0 5.8a1.2 1.2 0 1 0 0 2.4a1.2 1.2 0 1 0 0-2.4Zm6.1-15.6h10a1 1 0 0 1 0 2H13a1 1 0 0 1 0-2Zm0 5.6h10a1 1 0 0 1 0 2H13a1 1 0 0 1 0-2Zm0 5.6h7a1 1 0 0 1 0 2h-7a1 1 0 0 1 0-2Z"
        />
    </svg>
</template>