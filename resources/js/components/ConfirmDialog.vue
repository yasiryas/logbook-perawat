<script setup lang="ts">
import { AlertTriangle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    title: string;
    description?: string;
    confirmText?: string;
    loading?: boolean;
}>();

defineEmits<{
    confirm: [];
}>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="size-5 text-destructive" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 sm:justify-end">
                <Button variant="outline" type="button" @click="open = false">Batal</Button>
                <Button variant="destructive" type="button" :disabled="loading" @click="$emit('confirm')">
                    {{ confirmText ?? 'Ya, lanjutkan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>