<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    BookOpen,
    CalendarCheck,
    ClipboardList,
    HeartPulse,
    Medal,
    NotebookTabs,
    UserRound,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

const page = usePage();

const user = computed(
    () => page.props.auth.user as Record<string, unknown> | null,
);
const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-10 border-b bg-background/80 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3"
            >
                <div class="flex items-center gap-2 text-primary">
                    <HeartPulse class="size-6" />
                    <span class="text-lg font-semibold tracking-tight"
                        >Logbook Keperawatan</span
                    >
                </div>
                <nav class="flex items-center gap-2">
                    <template v-if="user">
                        <Button as-child>
                            <Link :href="dashboardUrl">Buka Dasbor</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child>
                            <Link :href="login().url">Masuk</Link>
                        </Button>
                        <Button variant="ghost" as-child>
                            <Link href="/panduan">Panduan</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 pt-14 pb-16">
            <section class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-secondary px-3 py-1 text-xs font-medium text-primary"
                    >
                        Sistem Informasi Keperawatan Rumah Sakit
                    </span>
                    <h1
                        class="mt-4 text-3xl font-semibold tracking-tight lg:text-4xl"
                    >
                        Catat logbook harian, pantau capaian, dan nilai kinerja
                        perawat dalam satu tempat.
                    </h1>
                    <p class="mt-3 text-muted-foreground">
                        Isi kinerja harian perawat, lihat rekap bulanan &amp;
                        tahunan otomatis, serta lakukan penilaian tahunan —
                        semua tercatat rapi dan siap dicetak.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Button size="lg" as-child>
                            <Link :href="user ? dashboardUrl : login().url">
                                {{ user ? 'Buka Dasbor' : 'Mulai Sekarang' }}
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link href="/panduan">
                                <BookOpen />
                                Panduan Pengisian
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-muted-foreground">
                        Alur penggunaan
                    </h2>
                    <ol class="mt-4 space-y-5">
                        <li class="flex gap-4">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                            >
                                1
                            </span>
                            <div>
                                <p class="font-medium">Atur master data</p>
                                <p class="text-sm text-muted-foreground">
                                    Unit, perawat, dan indikator kinerja
                                    disiapkan oleh admin / kepala ruang.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                            >
                                2
                            </span>
                            <div>
                                <p class="font-medium">Isi logbook harian</p>
                                <p class="text-sm text-muted-foreground">
                                    Perawat mengisi nilai per indikator pada
                                    kolom tanggal kerja.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                            >
                                3
                            </span>
                            <div>
                                <p class="font-medium">Pantau &amp; nilai</p>
                                <p class="text-sm text-muted-foreground">
                                    Rekap bulanan/tahunan dihitung otomatis,
                                    lalu dilakukan penilaian tahunan.
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-5">
                    <NotebookTabs class="size-5 text-primary" />
                    <h3 class="mt-3 font-medium">Input Harian</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Grid pengisian per tanggal dengan target tiap indikator.
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-5">
                    <CalendarCheck class="size-5 text-primary" />
                    <h3 class="mt-3 font-medium">Rekap Bulanan</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Capaian otomatis = jumlah nilai terisi dibagi hari
                        kerja.
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-5">
                    <ClipboardList class="size-5 text-primary" />
                    <h3 class="mt-3 font-medium">Rekap Tahunan</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Rata-rata 12 bulan per indikator, siap dinilai.
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-5">
                    <Medal class="size-5 text-primary" />
                    <h3 class="mt-3 font-medium">Penilaian &amp; Cetak</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Skor berbobot, finalisasi, dan dokumen siap cetak.
                    </p>
                </div>
            </section>
        </main>

        <footer class="border-t py-6">
            <div
                class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 text-sm text-muted-foreground"
            >
                <span class="flex items-center gap-1.5">
                    <UserRound class="size-4" />
                    Logbook Keperawatan — aplikasi RS
                </span>
                <Link href="/panduan" class="hover:text-foreground"
                    >Panduan Pengisian</Link
                >
            </div>
        </footer>
    </div>
</template>
