<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh items-center justify-center overflow-hidden bg-linear-to-br from-[hsl(180_42%_30%)] via-[hsl(180_42%_47%)] to-[hsl(173_63%_75%)] p-6 md:p-10"
    >
        <div
            class="pointer-events-none absolute -top-32 -left-32 size-96 rounded-full bg-[hsl(172_63%_80%)]/30 blur-3xl"
        />
        <div
            class="pointer-events-none absolute -right-32 -bottom-32 size-96 rounded-full bg-[hsl(180_45%_35%)]/30 blur-3xl"
        />
        <div
            class="pointer-events-none absolute top-1/3 right-1/4 size-64 rounded-full bg-white/10 blur-3xl"
        />

        <div class="relative flex w-full max-w-md flex-col gap-6">
            <Transition name="page" mode="out-in">
                <div :key="$page.url">
                    <Card
                        class="rounded-2xl border-white/40 bg-card/95 shadow-2xl backdrop-blur"
                    >
                        <CardHeader class="px-10 pt-8 pb-0 text-center">
                            <CardTitle class="text-xl">{{ title }}</CardTitle>
                            <CardDescription>{{ description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="px-10 py-8">
                            <slot />
                        </CardContent>
                    </Card>
                </div>
            </Transition>
        </div>
    </div>
</template>