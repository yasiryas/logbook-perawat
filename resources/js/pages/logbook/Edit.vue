<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
import { edit as logbookEdit, update as logbookUpdate } from '@/routes/logbook';
import { nurse as logbookEditNurse } from '@/routes/logbook/edit';
import { destroy as indicatorsDestroy, store as indicatorsStore } from '@/routes/indicators';
import type { CategoryGroup, Nurse } from '@/types';

type NurseOption = { id: number; name: string };

const props = defineProps<{
    nurse: Nurse;
    nurses: NurseOption[];
    month: number;
    year: number;
    workingDays: number;
    daysInMonth: number;
    categories: CategoryGroup[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Input Logbook', href: logbookEdit() }],
    },
});

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);
const selectedMonth = ref(props.month);
const selectedYear = ref(props.year);
const workingDays = ref(props.workingDays);

const matrix = reactive<Record<number, Record<number, number | ''>>>({});

for (const category of props.categories) {
    for (const indicator of category.indicators) {
        matrix[indicator.id] = {};

        for (let day = 1; day <= props.daysInMonth; day++) {
            matrix[indicator.id][day] = indicator.values?.[day] ?? '';
        }
    }
}

function switchNurse(id: number) {
    router.get(logbookEditNurse(id).url, {
        month: selectedMonth.value,
        year: selectedYear.value,
    });
}

function changePeriod() {
    router.get(logbookEdit().url, {
        month: selectedMonth.value,
        year: selectedYear.value,
    });
}

function rowSum(indicatorId: number): number {
    let sum = 0;

    for (const day in matrix[indicatorId]) {
        const value = matrix[indicatorId][day];

        if (value !== '' && value !== null && value !== undefined) {
            sum += Number(value);
        }
    }

    return sum;
}

function rowAchievement(indicatorId: number): number {
    if (!workingDays.value) {
        return 0;
    }

    return (rowSum(indicatorId) / workingDays.value) * 100;
}

function autoWorkingDays() {
    const daysInSelectedMonth = new Date(selectedYear.value, selectedMonth.value, 0).getDate();
    let count = 0;

    for (let day = 1; day <= daysInSelectedMonth; day++) {
        const weekday = new Date(selectedYear.value, selectedMonth.value - 1, day).getDay();

        if (weekday > 0 && weekday < 6) {
            count++;
        }
    }

    workingDays.value = count;
    debouncedSave();
}

let saveTimer: ReturnType<typeof setTimeout> | null = null;

function debouncedSave() {
    if (saveTimer) {
        clearTimeout(saveTimer);
    }

    saveTimer = setTimeout(save, 800);
}

function save() {
    const values: Record<number, Record<number, number>> = {};

    for (const category of props.categories) {
        for (const indicator of category.indicators) {
            for (let day = 1; day <= props.daysInMonth; day++) {
                const value = matrix[indicator.id]?.[day];

                if (value !== '' && value !== null && value !== undefined) {
                    values[indicator.id] ??= {};
                    values[indicator.id][day] = value;
                }
            }
        }
    }

    router.put(
        logbookUpdate(props.nurse.id).url,
        {
            month: selectedMonth.value,
            year: selectedYear.value,
            working_days: workingDays.value,
            values,
        },
        { preserveScroll: true },
    );
}

const indicatorDialogOpen = ref(false);
const deletingIndicator = ref<{ id: number; name: string } | null>(null);

const indicatorForm = reactive({
    category_id: props.categories[0]?.id ?? null,
    name: '',
    target: '100',
});

function openAddIndicator() {
    indicatorForm.category_id = props.categories[0]?.id ?? null;
    indicatorForm.name = '';
    indicatorForm.target = '100';
    indicatorDialogOpen.value = true;
}

function openAddIndicatorFor(categoryId: number) {
    indicatorForm.category_id = categoryId;
    indicatorForm.name = '';
    indicatorForm.target = '100';
    indicatorDialogOpen.value = true;
}

function addIndicator() {
    if (!indicatorForm.category_id || !indicatorForm.name.trim()) {
        return;
    }

    router.post(
        indicatorsStore().url,
        {
            category_id: indicatorForm.category_id,
            unit_id: props.nurse.unit_id,
            name: indicatorForm.name.trim(),
            target: indicatorForm.target.trim(),
        },
        { onSuccess: () => (indicatorDialogOpen.value = false) },
    );
}

function removeIndicator() {
    if (!deletingIndicator.value) {
        return;
    }

    router.delete(indicatorsDestroy(deletingIndicator.value.id).url, {
        onSuccess: () => (deletingIndicator.value = null),
        onError: () => (deletingIndicator.value = null),
    });
}
</script>

<template>
    <Head title="Input Logbook" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Input Logbook Harian</h1>
                <p class="text-sm text-muted-foreground">
                    {{ nurse.name }} · {{ nurse.unit?.name }} · {{ selectedMonth }}/{{ selectedYear }}
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div v-if="nurses.length > 0" class="grid gap-1.5">
                    <Label for="nurse">Perawat</Label>
                    <select
                        id="nurse"
                        :value="nurse.id"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="switchNurse(Number(($event.target as HTMLSelectElement).value))"
                    >
                        <option v-for="option in nurses" :key="option.id" :value="option.id">
                            {{ option.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-1.5">
                    <Label for="month">Bulan</Label>
                    <select
                        id="month"
                        v-model.number="selectedMonth"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="changePeriod"
                    >
                        <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>

                <div class="grid gap-1.5">
                    <Label for="year">Tahun</Label>
                    <select
                        id="year"
                        v-model.number="selectedYear"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="changePeriod"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="grid gap-1.5">
                <Label for="working_days">Hari kerja (bulan ini)</Label>
                <div class="flex gap-2">
                    <Input
                        id="working_days"
                        v-model="workingDays"
                        type="number"
                        min="1"
                        :max="daysInMonth"
                        class="w-28"
                        @change="debouncedSave"
                    />
                    <Button variant="outline" @click="autoWorkingDays">Hitung otomatis</Button>
                </div>
            </div>
            <Button variant="outline" @click="openAddIndicator">
                <Plus class="mr-2 h-4 w-4" /> Tambah Indikator
            </Button>
            <Button @click="save">Simpan Logbook</Button>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-card">
            <table class="w-full min-w-[900px] border-collapse text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="sticky left-0 bg-muted/95 px-4 py-2 text-left font-medium">
                            Indikator
                        </th>
                        <th
                            v-for="day in daysInMonth"
                            :key="day"
                            class="px-1 py-2 text-center font-medium"
                            :class="{ 'border-l': day > 1 }"
                        >
                            {{ day }}
                        </th>
                        <th class="px-3 py-2 text-center font-medium">Σ</th>
                        <th class="px-3 py-2 text-center font-medium">Capaian</th>
                        <th class="w-10 px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="category in categories" :key="category.id">
                        <tr class="border-b bg-secondary/40">
                            <td
                                colspan="1"
                                class="px-4 py-1.5 text-xs font-semibold text-secondary-foreground"
                            >
                                {{ category.code }} — {{ category.name }}
                            </td>
                            <td :colspan="daysInMonth" class="px-2 py-1.5"></td>
                            <td colspan="3" class="px-2 py-1.5 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                    @click="openAddIndicatorFor(category.id)"
                                >
                                    <Plus class="size-3.5" /> tambah
                                </button>
                            </td>
                        </tr>
                        <tr
                            v-for="indicator in category.indicators"
                            :key="indicator.id"
                            class="border-b last:border-0 hover:bg-muted/40"
                        >
                            <td
                                class="sticky left-0 bg-card px-4 py-1 text-xs align-top"
                            >
                                <div class="font-medium">{{ indicator.name }}</div>
                                <div class="text-muted-foreground">Target: {{ indicator.target }}</div>
                            </td>
                            <td
                                v-for="day in daysInMonth"
                                :key="day"
                                class="border-l border-muted px-0.5 py-0.5"
                            >
                                <input
                                    v-model.number="matrix[indicator.id][day]"
                                    type="number"
                                    step="any"
                                    class="h-7 w-full min-w-10 rounded border border-input bg-background px-1 text-center text-xs"
                                    @change="debouncedSave"
                                />
                            </td>
                            <td class="px-3 py-1.5 text-center text-xs font-semibold tabular-nums">
                                {{ fmt(rowSum(indicator.id)) }}
                            </td>
                            <td class="px-3 py-1.5 text-center text-xs font-semibold tabular-nums">
                                {{ fmt(rowAchievement(indicator.id)) }}%
                            </td>
                            <td class="px-2 py-1.5">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 text-destructive"
                                    @click="deletingIndicator = { id: indicator.id, name: indicator.name }"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <Button variant="ghost" size="sm" class="self-start text-primary" @click="openAddIndicator">
            <Plus class="mr-2 h-4 w-4" /> Tambah baris indikator
        </Button>

        <Dialog v-model:open="indicatorDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Tambah Indikator</DialogTitle>
                    <DialogDescription>
                        Indikator baru untuk unit {{ nurse.unit?.name }}. Langsung muncul di tabel setelah disimpan.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="addIndicator">
                    <div class="grid gap-2">
                        <Label for="ind-category">Kategori</Label>
                        <select
                            id="ind-category"
                            v-model="indicatorForm.category_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.code }} — {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="ind-name">Nama indikator</Label>
                        <Input id="ind-name" v-model="indicatorForm.name" required />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ind-target">Target</Label>
                        <Input id="ind-target" v-model="indicatorForm.target" placeholder="Mis. 1 kali/hari" required />
                    </div>

                    <DialogFooter>
                        <Button type="submit">Tambah Indikator</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="deletingIndicator !== null"
            @update:open="(open: boolean) => !open && (deletingIndicator = null)"
            title="Hapus Indikator"
            :description="deletingIndicator ? `Hapus indikator &quot;${deletingIndicator.name}&quot;? Bacaan logbook ikut terhapus.` : ''"
            confirm-text="Ya, hapus"
            @confirm="removeIndicator"
        />
    </div>
</template>