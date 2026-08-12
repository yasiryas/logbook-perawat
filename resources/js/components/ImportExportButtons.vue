<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    exportUrl: string;
    importUrl: string;
    importLabel?: string;
}>();

const fileInput = ref<HTMLInputElement | null>(null);

function exportData() {
    window.location.href = props.exportUrl;
}

function onFileChosen(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    router.post(props.importUrl, { file }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}
</script>

<template>
    <div class="flex items-center gap-2">
        <Button variant="outline" @click="exportData">Export</Button>
        <Button variant="outline" @click="fileInput?.click()">{{ importLabel ?? 'Import' }}</Button>
        <input
            ref="fileInput"
            type="file"
            accept=".xlsx,.xls,.csv"
            class="hidden"
            @change="onFileChosen"
        />
    </div>
</template>