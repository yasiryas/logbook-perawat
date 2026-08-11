<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
    destroy as nursesDestroy,
    index as nursesIndex,
    store as nursesStore,
    update as nursesUpdate,
} from '@/routes/nurses';
import type { Nurse } from '@/types';

type UnitOption = { id: number; name: string; slug?: string };
type UserOption = { id: number; name: string; email: string };

const props = defineProps<{
    nurses: (Nurse & { unit: { name: string } })[];
    units: UnitOption[];
    users: UserOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Master Perawat', href: nursesIndex() }],
    },
});

const selectedUnit = ref('');

function filter() {
    router.get(nursesIndex().url, selectedUnit.value ? { unit: selectedUnit.value } : {});
}

const dialogOpen = ref(false);
const editing = ref<Nurse | null>(null);
const deleteTarget = ref<Nurse | null>(null);
const deleting = ref(false);

const form = useForm({
    name: '',
    nip: '',
    pk: '',
    unit_id: props.units[0]?.id ?? null,
    user_id: null as number | null,
});

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.reset();
    form.unit_id = props.units[0]?.id ?? null;
    dialogOpen.value = true;
}

function openEdit(nurse: Nurse) {
    editing.value = nurse;
    form.clearErrors();
    form.name = nurse.name;
    form.nip = nurse.nip;
    form.pk = nurse.pk;
    form.unit_id = nurse.unit_id;
    form.user_id = nurse.user_id ?? null;
    dialogOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.patch(nursesUpdate(editing.value.id).url, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post(nursesStore().url, {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

function confirmDelete() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    router.delete(nursesDestroy(deleteTarget.value.id).url, {
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
    <Head title="Master Perawat" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Master Perawat</h1>
                <p class="text-sm text-muted-foreground">Data tenaga keperawatan</p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="grid gap-1.5">
                    <Label for="unit-filter">Unit</Label>
                    <select
                        id="unit-filter"
                        v-model="selectedUnit"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="filter"
                    >
                        <option value="">Semua unit</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.slug ?? unit.name">
                            {{ unit.name }}
                        </option>
                    </select>
                </div>
                <Button @click="openCreate">Tambah Perawat</Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-card">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-2 text-left font-medium">Nama</th>
                        <th class="px-4 py-2 text-left font-medium">NIP</th>
                        <th class="px-4 py-2 text-left font-medium">Pangkat</th>
                        <th class="px-4 py-2 text-left font-medium">Unit</th>
                        <th class="px-4 py-2 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="nurse in nurses"
                        :key="nurse.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-2 font-medium">{{ nurse.name }}</td>
                        <td class="px-4 py-2 text-muted-foreground">{{ nurse.nip }}</td>
                        <td class="px-4 py-2">{{ nurse.pk }}</td>
                        <td class="px-4 py-2">{{ nurse.unit.name }}</td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="ghost" size="sm" @click="openEdit(nurse)">
                                    Ubah
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                    @click="deleteTarget = nurse"
                                >
                                    Hapus
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Ubah Perawat' : 'Tambah Perawat' }}</DialogTitle>
                    <DialogDescription>
                        {{ editing ? `Edit data "${editing.name}"` : 'Data tenaga keperawatan baru' }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="nurse-name">Nama lengkap</Label>
                        <Input id="nurse-name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="nurse-nip">NIP</Label>
                            <Input id="nurse-nip" v-model="form.nip" required />
                            <InputError :message="form.errors.nip" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="nurse-pk">Pangkat / Golongan</Label>
                            <Input id="nurse-pk" v-model="form.pk" placeholder="Mis. Penata Muda - III/a" required />
                            <InputError :message="form.errors.pk" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="nurse-unit">Unit</Label>
                            <select
                                id="nurse-unit"
                                v-model="form.unit_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                required
                            >
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.unit_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="nurse-user">Akun pengguna (opsional)</Label>
                            <select
                                id="nurse-user"
                                v-model="form.user_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option :value="null">— Tanpa akun login —</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <InputError :message="form.errors.user_id" />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{ editing ? 'Simpan Perubahan' : 'Tambah Perawat' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deleteTarget !== null"
            @update:open="(open: boolean) => !open && (deleteTarget = null)"
            title="Hapus Perawat"
            :description="deleteTarget ? `Hapus perawat ${deleteTarget.name}? Tindakan ini tidak bisa dibatalkan.` : ''"
            confirm-text="Ya, hapus"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>