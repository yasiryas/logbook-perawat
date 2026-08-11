<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as nursesIndex, store as nursesStore, update as nursesUpdate } from '@/routes/nurses';
import type { Nurse, Unit } from '@/types';

type UserOption = { id: number; name: string; email: string };

const props = defineProps<{
    nurse?: Nurse;
    units: Pick<Unit, 'id' | 'name'>[];
    users: UserOption[];
}>();

defineOptions({
    layout: (props: { nurse?: Nurse }) => ({
        breadcrumbs: [
            {
                title: props.nurse ? `Edit ${props.nurse.name}` : 'Tambah Perawat',
                href: nursesIndex(),
            },
        ],
    }),
});

const form = useForm({
    name: props.nurse?.name ?? '',
    nip: props.nurse?.nip ?? '',
    pk: props.nurse?.pk ?? '',
    unit_id: props.nurse?.unit_id ?? (props.units[0]?.id ?? null),
    user_id: props.nurse?.user_id ?? null,
});

function submit() {
    if (props.nurse) {
        form.patch(nursesUpdate(props.nurse.id).url);
    } else {
        form.post(nursesStore().url);
    }
}
</script>

<template>
    <Head :title="nurse ? 'Edit Perawat' : 'Tambah Perawat'" />

    <div class="mx-auto flex w-full max-w-xl flex-col space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ nurse ? 'Edit Perawat' : 'Tambah Perawat' }}
            </h1>
            <p class="text-sm text-muted-foreground">{{ nurse ? nurse.name : 'Data baru' }}</p>
        </div>

        <form class="space-y-6 rounded-xl border bg-card p-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="name">Nama lengkap</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="nip">NIP</Label>
                <Input id="nip" v-model="form.nip" required />
                <InputError :message="form.errors.nip" />
            </div>

            <div class="grid gap-2">
                <Label for="pk">Pangkat / Golongan</Label>
                <Input id="pk" v-model="form.pk" placeholder="Mis. Penata Muda - III/a" />
                <InputError :message="form.errors.pk" />
            </div>

            <div class="grid gap-2">
                <Label for="unit">Unit</Label>
                <select
                    id="unit"
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
                <Label for="user">Akun pengguna (opsional)</Label>
                <select
                    id="user"
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

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" as-child>
                    <Link :href="nursesIndex().url">Batal</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ nurse ? 'Simpan Perubahan' : 'Tambah Perawat' }}
                </Button>
            </div>
        </form>
    </div>
</template>