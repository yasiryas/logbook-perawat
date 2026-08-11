<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Calculator,
    CheckCircle2,
    ClipboardCheck,
    FileDown,
    HeartPulse,
    MessageCircleQuestion,
    Settings2,
    Target,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';

const sections = [
    { id: 'penting', label: '1. Konsep Utama', icon: BookOpen },
    { id: 'akun', label: '2. Akun & Login', icon: Settings2 },
    { id: 'logbook', label: '3. Isi Logbook Harian', icon: Target },
    { id: 'rekap-bulanan', label: '4. Rekap Bulanan', icon: Calculator },
    { id: 'rekap-tahunan', label: '5. Rekap Tahunan', icon: Calculator },
    { id: 'penilaian', label: '6. Penilaian Tahunan', icon: ClipboardCheck },
    { id: 'print', label: '7. Cetak Dokumen', icon: FileDown },
    { id: 'hitung', label: '8. Cara Menghitung', icon: Calculator },
    { id: 'tanya', label: '9. Pertanyaan Umum', icon: MessageCircleQuestion },
] as const;
</script>

<template>
    <Head title="Panduan Pengisian" />

    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-10 border-b bg-background/80 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3"
            >
                <Link href="/" class="flex items-center gap-2 text-primary">
                    <HeartPulse class="size-6" />
                    <span class="text-lg font-semibold tracking-tight"
                        >Panduan Pengisian</span
                    >
                </Link>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link href="/">Kembali</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="login().url">Masuk</Link>
                    </Button>
                </div>
            </div>
        </header>

        <div
            class="mx-auto max-w-6xl gap-8 px-4 py-10 lg:grid lg:grid-cols-[220px_1fr]"
        >
            <aside class="hidden lg:block">
                <nav class="sticky top-20 space-y-1">
                    <a
                        v-for="s in sections"
                        :key="s.id"
                        :href="`#${s.id}`"
                        class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <component :is="s.icon" class="size-4 shrink-0" />
                        {{ s.label }}
                    </a>
                </nav>
            </aside>

            <main class="max-w-3xl min-w-0 space-y-10">
                <section>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Panduan Pengisian Logbook Keperawatan
                    </h1>
                    <p class="mt-2 text-muted-foreground">
                        Dokumen ini menjelaskan seluruh alur penggunaan
                        aplikasi: dari pengaturan master data, pengisian logbook
                        harian, hingga penilaian tahunan dan cetak.
                    </p>
                </section>

                <section id="penting" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <BookOpen class="size-5 text-primary" /> 1. Konsep Utama
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Logbook mencatat kinerja perawat setiap hari kerja
                        berdasarkan
                        <strong class="text-foreground"
                            >indikator kinerja</strong
                        >. Indikator dikelompokkan ke 3 kategori: A (Perilaku,
                        bobot 40%), B (Pengembangan Profesi, bobot 10%), dan C
                        (Kinerja Klinis, bobot 50%). Setiap hari kerja, perawat
                        mengisi nilai pada tiap indikator.
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start gap-2">
                            <CheckCircle2
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            <span
                                ><b>Indikator</b> — aspek kinerja yang dinilai,
                                mis. "Kepatuhan identifikasi pasien" atau
                                "Pengkajian Pasien".</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <CheckCircle2
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            <span
                                ><b>Target</b> — keterangan sasaran indikator,
                                mis. "100", ">85", atau "&lt;5 mnt".</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <CheckCircle2
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            <span
                                ><b>Angka</b> — setiap indikator diisi dengan
                                angka sesuai kondisi nyata hari itu. Untuk
                                indikator kepatuhan isi 1 (dilakukan) atau 0
                                (tidak dilakukan); untuk indikator kuantitatif
                                isi jumlah sebenarnya (mis. berapa pasien
                                dikaji, berapa tindakan dilakukan).</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <CheckCircle2
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            <span
                                ><b>Hari kerja</b> — jumlah hari kerja pada
                                bulan berjalan (default: jumlah hari kalender,
                                bisa disesuaikan bila ada libur/cuti).</span
                            >
                        </li>
                    </ul>
                    <div
                        class="rounded-lg border border-primary/20 bg-secondary/50 p-3 text-sm"
                    >
                        <b>Rumus capaian bulanan:</b> total nilai terisi ÷ hari
                        kerja. Contoh: <i>Kepatuhan kebersihan tangan</i> dicentang
                        20 dari 22 hari kerja → 20 ÷ 22 = 91%. Contoh angka:
                        total 45 pasien dikaji selama 22 hari kerja → 45 ÷ 22 =
                        2,05.
                    </div>
                </section>

                <section id="akun" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <Settings2 class="size-5 text-primary" /> 2. Akun &amp;
                        Login
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Setiap pegawai mendapat akun dari administrator. Gunakan
                        email dan kata sandi yang diberikan lalu masuk. Akses
                        menyesuaikan peran: perawat hanya melihat data
                        dirinya, kepala ruang melihat unitnya, admin melihat
                        semua.
                    </p>
                    <table
                        class="w-full overflow-hidden rounded-lg border text-sm"
                    >
                        <thead class="bg-muted text-left">
                            <tr>
                                <th class="px-3 py-2 font-medium">Peran</th>
                                <th class="px-3 py-2 font-medium">
                                    Kewenangan
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr>
                                <td class="px-3 py-2"><b>Admin</b></td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    Mengelola unit, data perawat, indikator, dan
                                    semua rekap.
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2"><b>Kepala Ruang</b></td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    Menutup rekap bulanan dan melakukan
                                    penilaian perawat unitnya.
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2"><b>Perawat</b></td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    Mengisi logbook harian untuk dirinya
                                    sendiri.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section id="logbook" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <Target class="size-5 text-primary" /> 3. Isi Logbook
                        Harian
                    </h2>
                    <ol class="space-y-3 text-sm">
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">1.</span>
                            <span
                                >Buka menu <b>Logbook Harian</b> di
                                sidebar.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">2.</span>
                            <span>
                                Pilih <b>perawat</b> (kepala ruang/admin),
                                <b>bulan</b>, dan <b>tahun</b> (menu di atas
                                tabel).
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">3.</span>
                            <span>
                                Tabel berbentuk matriks: <b>baris</b> adalah
                                indikator (dikelompokkan per kategori A/B/C),
                                <b>kolom</b> adalah tanggal 1 s/d jumlah hari
                                bulan itu.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">4.</span>
                            <span>
                                Isi tiap sel dengan <b>angka</b>: untuk indikator
                                kepatuhan isi 1 jika dilakukan hari itu atau 0
                                jika tidak; untuk indikator kuantitatif isi jumlah
                                sebenarnya.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">5.</span>
                            <span>
                                Isi jumlah <b>hari kerja</b> pada bulan itu bila
                                ada libur/cuti, agar pembagian rata-rata tetap
                                akurat.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">6.</span>
                            <span>
                                Tekan tombol <b>Simpan Logbook</b> agar
                                perubahan tersimpan — jangan tinggal halaman
                                tanpa menyimpan.
                            </span>
                        </li>
                    </ol>
                </section>

                <section id="rekap-bulanan" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <Calculator class="size-5 text-primary" /> 4. Rekap
                        Bulanan
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Menu <b>Rekap Bulanan</b> menampilkan capaian per
                        indikator dan per kategori (A/B/C) untuk perawat yang
                        dipilih pada bulan tertentu. Tampil juga jumlah nilai
                        terisi, hari kerja, dan total capaian bulan itu.
                    </p>
                </section>

                <section id="rekap-tahunan" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <Calculator class="size-5 text-primary" /> 5. Rekap
                        Tahunan
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Menu <b>Rekap Tahunan</b> menggabungkan 12 rekap bulanan
                        menjadi satu angka rata-rata per indikator. Inilah dasar
                        penilaian kinerja tahunan perawat.
                    </p>
                </section>

                <section id="penilaian" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <ClipboardCheck class="size-5 text-primary" /> 6.
                        Penilaian Tahunan
                    </h2>
                    <ol class="space-y-3 text-sm">
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">1.</span
                            ><span
                                >Buka <b>Penilaian</b>. Pilih perawat yang
                                dinilai.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">2.</span
                            ><span
                                >Capaian tahunan per indikator terisi otomatis
                                dari rekap. Nilai capaian masih bisa disesuaikan
                                pada tahap draf.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">3.</span
                            ><span
                                >Skor berbobot dihitung otomatis dari capaian ×
                                bobot kategori (A 40%, B 10%, C 50%).</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span class="font-semibold text-primary">4.</span
                            ><span
                                >Klik <b>Finalisasi</b> untuk mengunci
                                penilaian. Data yang sudah final tidak bisa
                                diubah.</span
                            >
                        </li>
                    </ol>
                </section>

                <section id="print" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <FileDown class="size-5 text-primary" /> 7. Cetak
                        Dokumen
                    </h2>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <b>Logbook</b> — rekap isian harian perawat per
                            bulan (format siap arsip).
                        </li>
                        <li>
                            <b>Rekap Tahunan</b> — ringkasan capaian tahunan
                            perawat.
                        </li>
                        <li>
                            <b>Penilaian</b> — lembar penilaian tahunan final
                            untuk tandatangan.
                        </li>
                    </ul>
                    <p
                        class="rounded-lg border border-dashed p-3 text-sm text-muted-foreground"
                    >
                        Fungsi ini memanggil tampilan cetak khusus — gunakan
                        <code class="rounded bg-muted px-1">Ctrl+P</code> untuk
                        menyimpan PDF atau mencetak.
                    </p>
                </section>

                <section id="hitung" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <Calculator class="size-5 text-primary" /> 8. Cara
                        Menghitung
                    </h2>
                    <div class="space-y-2 text-sm">
                        <p>
                            <b>Capaian indikator (1 bulan)</b> = total nilai
                            terisi ÷ hari kerja bulan itu.
                        </p>
                        <p>
                            <b>Capaian kategori (1 bulan)</b> = rata-rata
                            capaian seluruh indikator dalam kategori tersebut.
                        </p>
                        <p>
                            <b>Rekap bulanan</b> = jumlah capaian kategori
                            (skala maksimal 300 = 100%×3 kategori).
                        </p>
                        <p>
                            <b>Capaian indikator (1 tahun)</b> = rata-rata
                            capaian 12 bulan (bulan kosong dianggap 0).
                        </p>
                        <p>
                            <b>Skor akhir penilaian</b> = Σ (capaian × bobot
                            kategori). Bobot: A = 40%, B = 10%, C = 50%.
                        </p>
                    </div>
                </section>

                <section id="tanya" class="space-y-3 border-t pt-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <MessageCircleQuestion class="size-5 text-primary" /> 9.
                        Pertanyaan Umum
                    </h2>
                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="font-medium">Lupa kata sandi?</p>
                            <p class="text-muted-foreground">
                                Hubungi admin untuk reset. Perubahan kata sandi
                                sendiri tersedia di menu Pengaturan → Keamanan.
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">
                                Terlanjur salah mengisi satu tanggal?
                            </p>
                            <p class="text-muted-foreground">
                                Klik selnya dan ganti nilainya (centang ulang
                                atau ganti angkanya), lalu tekan Simpan
                                Logbook.
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">
                                Kenapa ada kolom yang tidak bisa diisi?
                            </p>
                            <p class="text-muted-foreground">
                                Anda tidak berwenang mengisi untuk perawat
                                tersebut — perawat hanya bisa mengisi untuk
                                dirinya sendiri, kepala ruang untuk unitnya.
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">
                                Checklist dan angka itu berbeda apa?
                            </p>
                            <p class="text-muted-foreground">
                                Tidak ada — semua indikator diisi dengan angka.
                                Untuk indikator kepatuhan isi 1 jika dilakukan
                                hari itu, 0 jika tidak. Untuk indikator
                                kuantitatif isi jumlah sebenarnya.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="border-t pt-8">
                    <div class="rounded-xl border bg-card p-6 text-center">
                        <p class="font-medium">Siap memulai?</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Masuk menggunakan akun yang diberikan
                            atasan/administrator Anda.
                        </p>
                        <Button class="mt-4" as-child>
                            <Link :href="login().url">Masuk ke Aplikasi</Link>
                        </Button>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
