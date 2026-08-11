<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, Gauge, RefreshCw, TrendingUp } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { stats as dashboardStats } from '@/routes/dashboard';
import { fmt } from '@/lib/format';
import type { CategoryGroup, DashboardInvitation, Nurse, Team } from '@/types';

type DashboardIndicator = CategoryGroup['indicators'][number] & {
    sum: number;
    filled_days: number;
    achievement: number;
};

type DashboardCategory = CategoryGroup & {
    achievement: number;
    indicators: DashboardIndicator[];
};

const props = defineProps<{
    pendingInvitations?: DashboardInvitation[];
    nurse?: Nurse | null;
    nurses?: { id: number; name: string }[];
    month: number;
    year: number;
    workingDays?: number;
    daysInMonth?: number;
    categories?: DashboardCategory[];
    total?: number;
    annual?: number;
    filledCells?: number;
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);

const selectedNurse = ref(props.nurse?.id ?? '');
const selectedMonth = ref(props.month);
const selectedYear = ref(props.year);
const autoRefresh = ref(true);
const refreshInterval = 15000;
const lastUpdated = ref<Date | null>(null);
const refreshing = ref(false);

const state = reactive({
    nurse: props.nurse ?? null,
    workingDays: props.workingDays ?? 0,
    daysInMonth: props.daysInMonth ?? 0,
    categories: (props.categories ?? []) as DashboardCategory[],
    total: props.total ?? 0,
    annual: props.annual ?? 0,
    filledCells: props.filledCells ?? 0,
});

let timer: ReturnType<typeof setInterval> | null = null;

const totalCells = computed(() => {
    if (!state.daysInMonth || state.categories.length === 0) {
        return 0;
    }

    return (
        state.categories.reduce((sum, category) => sum + category.indicators.length, 0) *
        state.daysInMonth
    );
});

const monthYear = computed(() => {
    const date = new Date(state.nurse ? selectedYear.value : props.year, selectedMonth.value - 1);

    return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
});

const monthName = computed(() =>
    new Date(selectedYear.value, selectedMonth.value - 1).toLocaleDateString('id-ID', {
        month: 'long',
    }),
);

function params() {
    return {
        ...(selectedNurse.value ? { nurse: String(selectedNurse.value) } : {}),
        month: selectedMonth.value,
        year: selectedYear.value,
    };
}

async function refresh() {
    if (refreshing.value) {
        return;
    }

    refreshing.value = true;

    try {
        const response = await fetch(dashboardStats(params()).url, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        state.nurse = data.nurse ?? null;
        state.workingDays = data.workingDays ?? 0;
        state.daysInMonth = data.daysInMonth ?? 0;
        state.categories = (data.categories ?? []) as DashboardCategory[];
        state.total = data.total ?? 0;
        state.annual = data.annual ?? 0;
        state.filledCells = data.filledCells ?? 0;
        lastUpdated.value = new Date();
    } catch (error) {
        console.error('Gagal memuat data dashboard:', error);
    } finally {
        refreshing.value = false;
    }
}

function changePeriod() {
    if (teamSlug.value) {
        router.get(dashboard(teamSlug.value).url, params());
    } else {
        refresh();
    }
}

onMounted(() => {
    timer = setInterval(() => {
        if (autoRefresh.value) {
            refresh();
        }
    }, refreshInterval);
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Dashboard Rekap</h1>
                <p class="text-sm text-muted-foreground">
                    {{
                        state.nurse
                            ? `${state.nurse.name} · ${state.nurse.unit?.name ?? '-'}`
                            : 'Pilih perawat untuk melihat rekap'
                    }}
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div v-if="nurses && nurses.length > 0" class="grid gap-1.5">
                    <Label for="dash-nurse">Perawat</Label>
                    <select
                        id="dash-nurse"
                        v-model="selectedNurse"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="changePeriod"
                    >
                        <option v-for="option in nurses" :key="option.id" :value="option.id">
                            {{ option.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-1.5">
                    <Label for="dash-month">Bulan</Label>
                    <select
                        id="dash-month"
                        v-model.number="selectedMonth"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="changePeriod"
                    >
                        <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>

                <div class="grid gap-1.5">
                    <Label for="dash-year">Tahun</Label>
                    <select
                        id="dash-year"
                        v-model.number="selectedYear"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="changePeriod"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <label class="flex h-9 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 text-sm">
                        <input v-model="autoRefresh" type="checkbox" class="size-3.5" />
                        Auto
                    </label>
                    <Button size="sm" variant="outline" :disabled="refreshing" @click="refresh">
                        <RefreshCw :class="refreshing ? 'animate-spin' : ''" class="size-4" />
                        Segarkan
                    </Button>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
                <span
                    class="size-2 rounded-full"
                    :class="autoRefresh ? 'animate-pulse bg-emerald-500' : 'bg-muted-foreground'"
                />
                {{ autoRefresh ? `Pembaruan otomatis tiap ${refreshInterval / 1000} detik` : 'Pembaruan otomatis nonaktif' }}
            </span>
            <span v-if="lastUpdated" class="tabular-nums">
                Terakhir diperbarui {{ lastUpdated.toLocaleTimeString('id-ID') }}
            </span>
        </div>

        <template v-if="state.nurse">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Hari Kerja</span>
                        <CalendarDays class="size-4 text-primary" />
                    </div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">
                        {{ state.workingDays }}
                        <span class="text-sm font-normal text-muted-foreground">hari</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Sel Terisi</span>
                        <CheckCircle2 class="size-4 text-primary" />
                    </div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">
                        {{ state.filledCells }}
                        <span class="text-sm font-normal text-muted-foreground">/ {{ totalCells }}</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: totalCells > 0 ? `${(100 * state.filledCells) / totalCells}%` : '0%' }"
                        />
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Capaian {{ monthName }}</span>
                        <Gauge class="size-4 text-primary" />
                    </div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">{{ fmt(state.total) }}</div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${Math.min(100, state.total)}%` }"
                        />
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Nilai Tahunan</span>
                        <TrendingUp class="size-4 text-primary" />
                    </div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">{{ fmt(state.annual) }}</div>
                </div>
            </div>

            <div class="rounded-xl border bg-card shadow-sm">
                <div class="border-b px-4 py-3">
                    <h2 class="text-sm font-semibold">Rekap per Indikator</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-muted-foreground">
                                <th class="px-4 py-2 text-left font-medium">Indikator</th>
                                <th class="px-4 py-2 text-right font-medium">Jumlah</th>
                                <th class="px-4 py-2 text-center font-medium">Hari Terisi</th>
                                <th class="px-4 py-2 text-center font-medium">Capaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="category in state.categories" :key="category.id">
                                <tr class="border-b bg-secondary/40">
                                    <td colspan="4" class="px-4 py-1.5 text-xs font-semibold">
                                        {{ category.code }} — {{ category.name }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="indicator in category.indicators"
                                    :key="indicator.id"
                                    class="border-b last:border-0 hover:bg-muted/40"
                                >
                                    <td class="px-4 py-2">
                                        <div class="text-xs font-medium">{{ indicator.sort }}. {{ indicator.name }}</div>
                                        <div class="text-xs text-muted-foreground">Target: {{ indicator.target }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ fmt(indicator.sum) }}</td>
                                    <td class="px-4 py-2 text-center tabular-nums">{{ indicator.filled_days }}</td>
                                    <td class="px-4 py-2 text-center tabular-nums">
                                        {{ fmt(indicator.achievement) }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border bg-card shadow-sm">
                <div class="border-b px-4 py-3">
                    <h2 class="text-sm font-semibold">Isian Harian {{ monthYear }}</h2>
                    <p class="text-xs text-muted-foreground">
                        Angka sama seperti lembar input logbook (hanya tampilan).
                    </p>
                </div>

                <div class="max-h-[28rem] overflow-auto">
                    <table class="w-full min-w-[900px] border-collapse text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b bg-muted/95">
                                <th class="sticky left-0 bg-muted/95 px-4 py-2 text-left font-medium">
                                    Indikator
                                </th>
                                <th
                                    v-for="day in state.daysInMonth"
                                    :key="day"
                                    class="px-1 py-2 text-center font-medium"
                                    :class="{ 'border-l': day > 1 }"
                                >
                                    {{ day }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="category in state.categories" :key="category.id">
                                <tr class="border-b bg-secondary/40">
                                    <td
                                        colspan="1"
                                        class="sticky left-0 bg-secondary px-4 py-1.5 text-xs font-semibold"
                                    >
                                        {{ category.code }} — {{ category.name }}
                                    </td>
                                    <td colspan="31" class="px-2 py-1.5"></td>
                                </tr>
                                <tr
                                    v-for="indicator in category.indicators"
                                    :key="indicator.id"
                                    class="border-b last:border-0"
                                >
                                    <td
                                        class="sticky left-0 bg-card px-4 py-1 text-xs align-top"
                                    >
                                        <div class="font-medium">{{ indicator.name }}</div>
                                        <div class="text-muted-foreground">Target: {{ indicator.target }}</div>
                                    </td>
                                    <td
                                        v-for="day in state.daysInMonth"
                                        :key="day"
                                        class="border-l border-muted px-0.5 py-0.5 text-center"
                                    >
                                        <span
                                            v-if="indicator.values?.[day] !== undefined"
                                            class="inline-flex h-7 w-full min-w-10 items-center justify-center rounded border border-primary/20 bg-primary/5 text-xs font-medium tabular-nums text-primary"
                                        >
                                            {{ indicator.values[day] }}
                                        </span>
                                        <span v-else class="inline-flex h-7 w-full min-w-10 items-center justify-center" />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <div v-else class="rounded-xl border border-dashed bg-card p-10 text-center text-sm text-muted-foreground">
            Belum ada profil perawat untuk akun ini. Hubungi administrator.
        </div>
    </div>
</template>