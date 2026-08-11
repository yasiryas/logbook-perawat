<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as logbookEdit, update as logbookUpdate } from '@/routes/logbook';
import { nurse as logbookEditNurse } from '@/routes/logbook/edit';
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
                <Input
                    id="working_days"
                    v-model="workingDays"
                    type="number"
                    min="1"
                    :max="daysInMonth"
                    class="w-32"
                    @change="debouncedSave"
                />
            </div>
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
                            <td colspan="31" class="px-2 py-1.5"></td>
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
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>