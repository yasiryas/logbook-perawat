<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { fmt } from '@/lib/format';
import { index as assessmentsIndex, show as assessmentsShow } from '@/routes/assessments';
import type { AnnualAssessmentRow } from '@/types';

const props = defineProps<{
    year: number;
    nurses: AnnualAssessmentRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Penilaian Tahunan', href: assessmentsIndex() }],
    },
});

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);
const selectedYear = ref(props.year);

function change() {
    router.get(assessmentsIndex().url, { year: selectedYear.value });
}
</script>

<template>
    <Head title="Penilaian Tahunan" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Penilaian Tahunan</h1>
                <p class="text-sm text-muted-foreground">Rekap capaian perawat per tahun</p>
            </div>

            <div class="grid gap-1.5">
                <Label for="year">Tahun</Label>
                <select
                    id="year"
                    v-model.number="selectedYear"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="change"
                >
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
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
                        <th class="px-4 py-2 text-center font-medium">Status</th>
                        <th class="px-4 py-2 text-right font-medium">Total Skor</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in nurses"
                        :key="row.id"
                        class="border-b last:border-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-2 font-medium">{{ row.name }}</td>
                        <td class="px-4 py-2 text-muted-foreground">{{ row.nip }}</td>
                        <td class="px-4 py-2">{{ row.pk }}</td>
                        <td class="px-4 py-2">{{ row.unit }}</td>
                        <td class="px-4 py-2 text-center">
                            <Badge :variant="row.status === 'final' ? 'default' : 'secondary'">
                                {{ row.status === 'final' ? 'Final' : 'Draft' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2 text-right font-semibold tabular-nums">
                            {{ fmt(row.total) }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <Link
                                :href="assessmentsShow(row.id).url + `?year=${selectedYear}`"
                                class="text-sm font-medium text-primary hover:underline"
                            >
                                Buka →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>