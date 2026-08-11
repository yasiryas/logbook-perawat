<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Label } from '@/components/ui/label';
import { fmt } from '@/lib/format';
import { monthly as recapsMonthly } from '@/routes/recaps';
import { nurse as recapsMonthlyNurse } from '@/routes/recaps/monthly';
import type { CategoryGroup, Nurse } from '@/types';

type NurseOption = { id: number; name: string };

const props = defineProps<{
    nurse: Nurse;
    nurses: NurseOption[];
    month: number;
    year: number;
    workingDays: number;
    categories: CategoryGroup[];
    total: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Rekap Bulanan', href: recapsMonthly() }],
    },
});

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);
const selectedMonth = ref(props.month);
const selectedYear = ref(props.year);

function change(id?: number) {
    const url = id ? recapsMonthlyNurse(id).url : recapsMonthly().url;
    router.get(url, { month: selectedMonth.value, year: selectedYear.value });
}
</script>

<template>
    <Head title="Rekap Bulanan" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Rekap Bulanan</h1>
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
                        @change="change(Number(($event.target as HTMLSelectElement).value))"
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
                        @change="change()"
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
                        @change="change()"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-4">
                <div class="text-xs text-muted-foreground">Hari kerja</div>
                <div class="mt-1 text-2xl font-semibold">{{ workingDays }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="text-xs text-muted-foreground">Rata-rata capaian kategori</div>
                <div class="mt-1 text-2xl font-semibold">{{ fmt(total) }}%</div>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="text-xs text-muted-foreground">Status</div>
                <div class="mt-1 text-2xl font-semibold text-primary">
                    {{ total >= 100 ? 'Tercapai' : 'Belum' }}
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div v-for="category in categories" :key="category.id">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">
                        {{ category.code }} — {{ category.name }}
                        <span class="ml-2 text-xs font-normal text-muted-foreground">
                            bobot {{ fmt(category.weight_percent, 0) }}%
                        </span>
                    </h2>
                    <span class="text-xs text-muted-foreground">
                        Capaian kategori: {{ fmt(category.achievement) }}%
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border bg-card">
                    <table class="w-full min-w-[900px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="sticky left-0 bg-muted/95 px-4 py-2 text-left font-medium">
                                    Indikator
                                </th>
                                <th
                                    v-for="day in workingDays"
                                    :key="day"
                                    class="px-1 py-2 text-center text-xs font-medium"
                                >
                                    {{ day }}
                                </th>
                                <th class="px-3 py-2 text-center font-medium">Σ</th>
                                <th class="px-3 py-2 text-center font-medium">Capaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="indicator in category.indicators"
                                :key="indicator.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td class="sticky left-0 bg-card px-4 py-1.5 text-xs">
                                    <div class="font-medium">{{ indicator.name }}</div>
                                    <div class="text-muted-foreground">
                                        Target: {{ indicator.target }}
                                    </div>
                                </td>
                                <td
                                    v-for="day in workingDays"
                                    :key="day"
                                    class="border-l border-muted px-1 py-1.5 text-center text-xs tabular-nums"
                                >
                                    {{ indicator.values?.[day] ?? '' }}
                                </td>
                                <td class="px-3 py-1.5 text-center text-xs font-semibold tabular-nums">
                                    {{ fmt(indicator.sum) }}
                                </td>
                                <td class="px-3 py-1.5 text-center text-xs font-semibold tabular-nums">
                                    {{ fmt(indicator.achievement) }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>