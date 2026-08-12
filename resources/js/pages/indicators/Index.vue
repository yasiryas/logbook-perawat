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
import { fmt } from '@/lib/format';
import {
    destroy as indicatorsDestroy,
    exportMethod as indicatorsExport,
    importMethod as indicatorsImport,
    index as indicatorsIndex,
    store as indicatorsStore,
    update as indicatorsUpdate,
} from '@/routes/indicators';
import type { Unit } from '@/types';

type IndicatorModel = {
    id: number;
    name: string;
    target: string;
    sort: number;
    unit_id: number | null;
    category_id: number;
};

type CategoryModel = {
    id: number;
    code: string;
    name: string;
    weight_percent: number;
    indicators: IndicatorModel[];
};

const props = defineProps<{
    categories: CategoryModel[];
    units: Pick<Unit, 'id' | 'name'>[];
    selectedUnit: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Master Indikator', href: indicatorsIndex() }],
    },
});

const currentYear = new Date().getFullYear();

const filterUnit = ref(props.selectedUnit ?? '');

function filter() {
    router.get(
        indicatorsIndex().url,
        filterUnit.value ? { unit: filterUnit.value } : {},
    );
}

const dialogOpen = ref(false);
const editing = ref<IndicatorModel | null>(null);
const deleteTarget = ref<IndicatorModel | null>(null);
const deleting = ref(false);

const form = useForm({
    category_id: props.categories[0]?.id ?? null,
    unit_id: null as number | null,
    name: '',
    target: '',
    sort: '' as number | '',
});

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.reset();
    dialogOpen.value = true;
}

function openEdit(indicator: IndicatorModel) {
    editing.value = indicator;
    form.clearErrors();
    form.category_id = indicator.category_id;
    form.unit_id = indicator.unit_id;
    form.name = indicator.name;
    form.target = indicator.target;
    form.sort = indicator.sort;
    dialogOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.patch(indicatorsUpdate(editing.value.id).url, { onSuccess: () => (dialogOpen.value = false) });
    } else {
        form.post(indicatorsStore().url, { onSuccess: () => (dialogOpen.value = false) });
    }
}

function confirmDelete() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    router.delete(indicatorsDestroy(deleteTarget.value.id).url, {
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
    <Head title="Master Indikator" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Master Indikator</h1>
                <p class="text-sm text-muted-foreground">Daftar indikator kinerja per kategori</p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="grid gap-1.5">
                    <Label for="unit-filter">Unit</Label>
                    <select
                        id="unit-filter"
                        v-model="filterUnit"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="filter"
                    >
                        <option value="">Semua unit</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                            {{ unit.name }}
                        </option>
                    </select>
                </div>
                <ImportExportButtons :export-url="indicatorsExport().url" :import-url="indicatorsImport().url" />
                <Button @click="openCreate">Tambah Indikator</Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div v-for="category in categories" :key="category.id" class="rounded-xl border bg-card">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <div>
                        <div class="text-sm font-semibold">{{ category.code }} — {{ category.name }}</div>
                        <div class="text-xs text-muted-foreground">
                            Bobot {{ fmt(category.weight_percent, 0) }}%
                        </div>
                    </div>
                    <Button variant="outline" size="sm" @click="openCreate()">+</Button>
                </div>

                <ul class="divide-y">
                    <li
                        v-for="indicator in category.indicators"
                        :key="indicator.id"
                        class="flex items-start justify-between gap-2 px-4 py-2.5"
                    >
                        <div>
                            <div class="text-xs font-medium">{{ indicator.sort }}. {{ indicator.name }}</div>
                            <div class="text-xs text-muted-foreground">Target: {{ indicator.target }}</div>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <Button variant="ghost" size="sm" @click="openEdit(indicator)">Ubah</Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive"
                                @click="deleteTarget = indicator"
                            >
                                Hapus
                            </Button>
                        </div>
                    </li>
                    <li v-if="category.indicators.length === 0" class="px-4 py-6 text-center text-xs text-muted-foreground">
                        Tidak ada indikator untuk unit terpilih.
                    </li>
                </ul>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editing ? 'Ubah Indikator' : 'Tambah Indikator' }}</DialogTitle>
                    <DialogDescription>Indikator kinerja tahun {{ currentYear }}</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="ind-category">Kategori</Label>
                        <select
                            id="ind-category"
                            v-model="form.category_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.code }} — {{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ind-unit">Unit (opsional)</Label>
                        <select
                            id="ind-unit"
                            v-model="form.unit_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option :value="null">Semua unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.unit_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ind-name">Nama indikator</Label>
                        <Input id="ind-name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ind-target">Target</Label>
                        <Input id="ind-target" v-model="form.target" placeholder="Mis. 1 kali/hari" required />
                        <InputError :message="form.errors.target" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ind-sort">Urutan</Label>
                        <Input id="ind-sort" v-model="form.sort" type="number" min="1" />
                        <InputError :message="form.errors.sort" />
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
            title="Hapus Indikator"
            :description="deleteTarget ? `Hapus indikator ${deleteTarget.name}? Tindakan ini tidak bisa dibatalkan.` : ''"
            confirm-text="Ya, hapus"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>