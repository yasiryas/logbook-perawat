<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fmt } from '@/lib/format';
import {
    finalize as assessmentsFinalize,
    show as assessmentsShow,
    update as assessmentsUpdate,
} from '@/routes/assessments';
import { assessment as printAssessment } from '@/routes/print';
import type { AssessmentCategory, Nurse } from '@/types';

type AssessmentMeta = {
    id: number;
    status: 'draft' | 'final';
    assessor_name: string | null;
    assessor_nip: string | null;
    assessor_position: string | null;
    assessor_unit: string | null;
    superior_name: string | null;
    superior_nip: string | null;
    superior_position: string | null;
    superior_unit: string | null;
};

const props = defineProps<{
    nurse: Nurse;
    year: number;
    assessment: AssessmentMeta;
    categories: AssessmentCategory[];
}>();

defineOptions({
    layout: (props: { nurse: Nurse }) => ({
        breadcrumbs: [
            {
                title: `Penilaian ${props.nurse.name}`,
                href: assessmentsShow(props.nurse.id),
            },
        ],
    }),
});

const isFinal = ref(props.assessment.status === 'final');

const form = reactive({
    assessor_name: props.assessment.assessor_name ?? '',
    assessor_nip: props.assessment.assessor_nip ?? '',
    assessor_position: props.assessment.assessor_position ?? '',
    assessor_unit: props.assessment.assessor_unit ?? '',
    superior_name: props.assessment.superior_name ?? '',
    superior_nip: props.assessment.superior_nip ?? '',
    superior_position: props.assessment.superior_position ?? '',
    superior_unit: props.assessment.superior_unit ?? '',
});

const achievements = reactive<Record<number, number>>({});

for (const category of props.categories) {
    for (const row of category.rows) {
        achievements[row.id] = row.achievement;
    }
}

function rowScore(row: (typeof props.categories)[0]['rows'][number]): number {
    const a = achievements[row.id] ?? 0;

    return a * row.weight;
}

function categoryScore(category: AssessmentCategory): number {
    return category.rows.reduce((sum, row) => sum + rowScore(row), 0);
}

function grandTotal(): number {
    return props.categories.reduce((sum, category) => sum + categoryScore(category), 0);
}

function save() {
    const items = props.categories.flatMap((category) =>
        category.rows.map((row) => ({ id: row.id, achievement: achievements[row.id] ?? 0 })),
    );

    router.put(assessmentsUpdate(props.nurse.id).url + `?year=${props.year}`, {
        year: props.year,
        ...form,
        items,
    });
}

function finalize() {
    router.post(assessmentsFinalize(props.nurse.id).url + `?year=${props.year}`, {});
}

function openPrint() {
    window.open(printAssessment(props.assessment.id).url, '_blank');
}
</script>

<template>
    <Head title="Penilaian Tahunan" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Penilaian Tahunan</h1>
                <p class="text-sm text-muted-foreground">
                    {{ nurse.name }} · {{ nurse.unit?.name }} · Tahun {{ year }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Badge :variant="isFinal ? 'default' : 'secondary'">
                    {{ isFinal ? 'Final' : 'Draft' }}
                </Badge>
                <Button v-if="!isFinal" variant="outline" @click="save">Simpan Draf</Button>
                <Button v-if="!isFinal" @click="finalize">Finalisasi</Button>
                <Button variant="outline" @click="openPrint" :disabled="!isFinal">Cetak</Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Penilai</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Nama</Label>
                        <Input v-model="form.assessor_name" :disabled="isFinal" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>NIP</Label>
                        <Input v-model="form.assessor_nip" :disabled="isFinal" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Pangkat / Jabatan</Label>
                        <Input v-model="form.assessor_position" :disabled="isFinal" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Unit Kerja</Label>
                        <Input v-model="form.assessor_unit" :disabled="isFinal" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Atasan Penilai</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Nama</Label>
                        <Input v-model="form.superior_name" :disabled="isFinal" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>NIP</Label>
                        <Input v-model="form.superior_nip" :disabled="isFinal" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Pangkat / Jabatan</Label>
                        <Input v-model="form.superior_position" :disabled="isFinal" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Unit Kerja</Label>
                        <Input v-model="form.superior_unit" :disabled="isFinal" />
                    </div>
                </CardContent>
            </Card>
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
                        Skor kategori: {{ fmt(categoryScore(category)) }}
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border bg-card">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium">Indikator</th>
                                <th class="px-4 py-2 text-left font-medium">Target</th>
                                <th class="px-4 py-2 text-center font-medium">Capaian (%)</th>
                                <th class="px-4 py-2 text-center font-medium">Bobot</th>
                                <th class="px-4 py-2 text-right font-medium">Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in category.rows"
                                :key="row.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td class="px-4 py-1.5 text-xs font-medium">{{ row.name }}</td>
                                <td class="px-4 py-1.5 text-xs text-muted-foreground">{{ row.target }}</td>
                                <td class="px-4 py-1 text-center">
                                    <Input
                                        v-if="!isFinal"
                                        v-model.number="achievements[row.id]"
                                        type="number"
                                        step="any"
                                        min="0"
                                        class="mx-auto h-8 w-28 text-center"
                                    />
                                    <span v-else class="text-sm tabular-nums">{{ fmt(row.achievement) }}</span>
                                </td>
                                <td class="px-4 py-1.5 text-center text-xs tabular-nums">
                                    {{ fmt(row.weight, 4) }}
                                </td>
                                <td class="px-4 py-1.5 text-right text-xs font-semibold tabular-nums">
                                    {{ fmt(rowScore(row)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-xl border bg-card p-4 text-right">
            <span class="text-sm text-muted-foreground">Total skor: </span>
            <span class="text-2xl font-semibold text-primary">{{ fmt(grandTotal()) }}</span>
        </div>
    </div>
</template>