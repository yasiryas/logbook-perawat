<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import ImportExportButtons from '@/components/ImportExportButtons.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    destroy as unitsDestroy,
    exportMethod as unitsExport,
    importMethod as unitsImport,
    index as unitsIndex,
    store as unitsStore,
    update as unitsUpdate,
} from '@/routes/units';
import type { Unit } from '@/types';

defineProps<{
    units: Unit[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Master Unit', href: unitsIndex() }],
    },
});

const dialogOpen = ref(false);
const editing = ref<Unit | null>(null);
const deleteTarget = ref<Unit | null>(null);
const deleting = ref(false);

const form = useForm({ name: '' });

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.reset();
    dialogOpen.value = true;
}

function openEdit(unit: Unit) {
    editing.value = unit;
    form.clearErrors();
    form.name = unit.name;
    dialogOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.patch(unitsUpdate(editing.value.slug).url, { onSuccess: () => (dialogOpen.value = false) });
    } else {
        form.post(unitsStore().url, { onSuccess: () => (dialogOpen.value = false) });
    }
}

function confirmDelete() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    router.delete(unitsDestroy(deleteTarget.value.slug).url, {
        onSuccess: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
        onError: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Master Unit" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Master Unit</h1>
                <p class="text-sm text-muted-foreground">Manajemen ruang / unit kerja</p>
            </div>
            <ImportExportButtons :export-url="unitsExport().url" :import-url="unitsImport().url" />
            <Button @click="openCreate">Tambah Unit</Button>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-card">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-2 text-left font-medium">Nama</th>
                        <th class="px-4 py-2 text-center font-medium">Perawat</th>
                        <th class="px-4 py-2 text-center font-medium">Indikator</th>
                        <th class="px-4 py-2 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="unit in units"
                        :key="unit.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-2 font-medium">{{ unit.name }}</td>
                        <td class="px-4 py-2 text-center tabular-nums">{{ unit.nurses_count ?? 0 }}</td>
                        <td class="px-4 py-2 text-center tabular-nums">
                            {{ unit.indicators_count ?? 0 }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="ghost" size="sm" @click="openEdit(unit)">
                                    Ubah
                                </Button>
                                <Button variant="ghost" size="sm" class="text-destructive" @click="deleteTarget = unit">
                                    Hapus
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Ubah Unit' : 'Tambah Unit' }}</DialogTitle>
                    <DialogDescription>
                        {{ editing ? `Edit "${editing.name}"` : 'Nama ruang / unit kerja baru' }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="unit-name">Nama</Label>
                        <Input
                            id="unit-name"
                            v-model="form.name"
                            placeholder="Mis. Ruang Melati"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{ editing ? 'Simpan' : 'Tambah' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deleteTarget !== null"
            @update:open="(open: boolean) => !open && (deleteTarget = null)"
            title="Hapus Unit"
            :description="deleteTarget ? `Hapus unit ${deleteTarget.name}? Tindakan ini tidak bisa dibatalkan.` : ''"
            confirm-text="Ya, hapus"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>