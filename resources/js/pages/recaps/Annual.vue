<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { fmt } from '@/lib/format';
import { annual as recapsAnnual } from '@/routes/recaps';
import { nurse as recapsAnnualNurse } from '@/routes/recaps/annual';
import { annual as printAnnual } from '@/routes/print';
import type { CategoryGroup, Nurse } from '@/types';

type NurseOption = { id: number; name: string };

const props = defineProps<{
    nurse: Nurse;
    nurses: NurseOption[];
    year: number;
    categories: CategoryGroup[];
    total: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Rekap Tahunan', href: recapsAnnual() }],
    },
});

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);
const selectedYear = ref(props.year);

function change(id?: number) {
    const url = id ? recapsAnnualNurse(id).url : recapsAnnual().url;
    router.get(url, { year: selectedYear.value });
}

function openPrint() {
    window.open(printAnnual(props.nurse.id).url, '_blank');
}
</script>

<template>
    <Head title="Rekap Tahunan" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Rekap Tahunan</h1>
                <p class="text-sm text-muted-foreground">
                    {{ nurse.name }} · {{ nurse.unit?.name }} · Tahun {{ selectedYear }}
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

                <Button variant="outline" @click="openPrint">Cetak</Button>
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
                    <table class="w-full min-w-[820px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="sticky left-0 bg-muted/95 px-4 py-2 text-left font-medium">
                                    Indikator
                                </th>
                                <th
                                    v-for="m in 12"
                                    :key="m"
                                    class="px-2 py-2 text-center text-xs font-medium"
                                >
                                    {{ m }}
                                </th>
                                <th class="px-3 py-2 text-center font-medium">Rata-rata</th>
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
                                    v-for="m in 12"
                                    :key="m"
                                    class="border-l border-muted px-2 py-1.5 text-center text-xs tabular-nums"
                                >
                                    {{ indicator.monthly?.[m - 1] ?? 0 }}
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

        <div class="rounded-xl border bg-card p-4 text-right">
            <span class="text-sm text-muted-foreground">Total capaian tahunan: </span>
            <span class="text-2xl font-semibold text-primary">{{ fmt(total) }}%</span>
        </div>
    </div>
</template>