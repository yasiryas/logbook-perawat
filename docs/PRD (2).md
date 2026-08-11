# PRD — Aplikasi Logbook Perawat RSU Sakina Idaman

**Status:** Draft v1.0
**Stack:** Laravel + Inertia + Vue 3
**Tooling pengembangan:** opencode

> Dokumen ini adalah single source of truth. File pendukung: `BACKLOG.md` (fitur di luar
> scope), `UI-GUIDELINES.md` (aturan tampilan & branding). Tempel ketiganya ke context
> opencode di awal tiap sesi.

---

## 1. Latar Belakang

Perawat RSU Sakina Idaman saat ini mengisi logbook kinerja harian di file Excel per unit
(RANAP, RAJAL, dst). Kepala ruang merekap manual untuk penilaian tahunan. Masalah yang
ditemukan pada file sumber:

- Kalkulasi rata-rata/capaian bergantung pada formula Excel yang rapuh — ditemukan galat
  `#REF!` dan `#DIV/0!` pada beberapa sel di file yang sudah berjalan.
- Indikator kategori **Kinerja Klinis** berbeda antar unit, tapi dikelola sebagai file
  Excel terpisah tanpa struktur data terpusat.
- Penilaian tahunan disusun dengan menyalin manual angka capaian dari 12 sheet bulanan.

## 2. Tujuan

1. Satu sumber input harian per perawat, dengan kalkulasi capaian yang dihitung otomatis
   dan konsisten di backend (bukan di Excel/frontend).
2. Form penilaian tahunan yang capaiannya ditarik otomatis dari data harian sepanjang
   tahun, bisa disesuaikan manual sebelum difinalisasi.
3. Output PDF (logbook bulanan, rekap tahunan, penilaian tahunan) yang formatnya dikenali
   pengguna yang terbiasa dengan format Excel lama.

## 3. Pengguna & Peran

| Peran | Kebutuhan utama | Akses |
|---|---|---|
| Perawat | Isi nilai indikator harian, lihat rekap capaian miliknya sendiri | Input harian, lihat rekap sendiri |
| Kepala Ruang | Pantau seluruh perawat di unitnya, isi & finalisasi penilaian tahunan, kelola master indikator unit | Kelola unit, finalisasi penilaian, cetak PDF |
| Kabid Keperawatan | Muncul sebagai atasan penilai di dokumen resmi — peran administratif | Data identitas saja (default terkonfigurasi) |

**User story inti:**
- Sebagai **perawat**, saya ingin mengisi nilai indikator hari ini dalam satu grid, tanpa pindah sheet.
- Sebagai **kepala ruang**, saya ingin capaian tahunan seorang perawat terisi otomatis saat membuka form penilaian, dan bisa saya sesuaikan sebelum difinalisasi.
- Sebagai **kepala ruang**, saya ingin mencetak logbook bulanan & form penilaian dalam format siap tanda tangan dan arsip.

## 4. Analisa Struktur Data Sumber

| Sheet Excel | Fungsi | Jadi apa di aplikasi |
|---|---|---|
| `Data Perawat` | Identitas perawat | Master `nurses` |
| `JAN`–`DES` | Input harian, 31 kolom hari, 3 kategori indikator | `logbook_entries` + service kalkulasi |
| `TAHUNAN` | Rekap capaian 12 bulan per indikator | View agregasi (tanpa tabel baru) |
| `PENILAIAN TAHUNAN` | Form resmi: data perawat, penilai, atasan, target/capaian/bobot/nilai | `annual_assessments` — output PDF utama |
| `3` | Rekap jumlah pelatihan per petugas per unit | Di luar scope — lihat `BACKLOG.md` |

**Temuan kunci:**
- Kategori **A. Perilaku (40%)** dan **B. Pengembangan Profesi (10%)** — indikator sama di semua unit.
- Kategori **C. Kinerja Klinis (50%)** — indikator berbeda per unit, wajib dimodelkan terikat ke `unit_id`.
- Kolom **"Jumlah Hari"** per bulan adalah input manual (bukan otomatis dari kalender), dipakai sebagai penyebut kalkulasi capaian.

## 5. Skema Database (MVP)

```
units                     id, nama
nurses                    id, user_id, nama, nip, pk, unit_id
indicator_categories      id, kode (A/B/C), nama, bobot_persen
indicators                id, category_id, unit_id (nullable), nomor_urut, nama, target_persen
logbook_entries           id, nurse_id, indicator_id, tanggal, nilai
                           unique(nurse_id, indicator_id, tanggal)
monthly_configs           id, nurse_id, bulan, tahun, jumlah_hari, status
annual_assessments        id, nurse_id, tahun, data_penilai, data_atasan, status
annual_assessment_items   id, assessment_id, indicator_id, target, capaian, bobot, nilai_hasil
```

Nilai hasil hitung (rata-rata, capaian) **tidak disimpan permanen** — selalu dihitung ulang
dari `logbook_entries`, kecuali di `annual_assessment_items` yang menyimpan snapshot saat
status penilaian menjadi "final" (dokumen resmi tidak boleh berubah setelah ditandatangani).

## 6. Ruang Lingkup

**Masuk MVP:**
- Login & role (perawat, kepala ruang/admin)
- CRUD master: unit, perawat, indikator per unit
- Input harian ala-grid per perawat per bulan
- Rekap bulanan & tahunan otomatis
- Form penilaian tahunan + finalisasi
- Export PDF: logbook bulanan, rekap tahunan, penilaian tahunan

**Di luar MVP:** lihat `BACKLOG.md`.

## 7. Keputusan Teknis

| Area | Keputusan | Alasan |
|---|---|---|
| Backend | Laravel, API via Inertia | — |
| Frontend | Vue 3 + Inertia.js | Aplikasi form/CRUD-heavy, bukan SPA kompleks — mengurangi boilerplate endpoint |
| Kalkulasi | Satu class `LogbookCalculationService` di backend | Frontend tidak pernah menghitung ulang — satu sumber kebenaran |
| PDF | spatie/browsershot (Blade → headless Chrome → PDF) | Presisi lebih tinggi dari dompdf untuk grid 39 kolom |
| UI/UX | Lihat `UI-GUIDELINES.md` | Gaya SB Admin, branding hijau toska |

## 8. Fase Pengerjaan

1. **Fase 0** — Spec & fondasi: setup Laravel + Breeze (Inertia+Vue), simpan dokumen ini di repo.
2. **Fase 1** — Skema database: migration sesuai Bagian 5, seeder kategori & indikator global (A/B).
3. **Fase 2** — Master data: CRUD unit, perawat, indikator per unit.
4. **Fase 3** — Input harian: grid indikator × 31 hari.
5. **Fase 4** — Kalkulasi & rekap bulanan: service + unit test memakai angka nyata dari Excel.
6. **Fase 5** — Rekap tahunan: agregasi 12 bulan.
7. **Fase 6** — Penilaian tahunan: form + finalisasi (snapshot).
8. **Fase 7** — Export PDF: 3 Blade view cetak, dicocokkan ke format Excel asli.
9. **Fase 8** (pasca-MVP) — Modul laporan pelatihan per unit.

Satu fase = satu sesi opencode. Commit git sebelum lanjut ke fase berikutnya.

## 9. Kriteria Sukses

- Angka capaian dari sistem **cocok** dengan hasil manual di Excel untuk data historis yang sama.
- Kepala ruang menyelesaikan satu siklus penilaian tahunan tanpa menyalin angka manual.
- PDF hasil cetak bisa langsung dipakai untuk arsip/tanda tangan tanpa edit ulang.

## 10. Risiko & Asumsi

- **Asumsi:** struktur indikator kategori C per unit tidak berubah drastis di tengah tahun berjalan. Kalau berubah tiap periode, dibutuhkan fitur versioning indikator — saat ini di luar scope.
- **Risiko:** kolom "jumlah hari" manual bisa jadi sumber salah input — pertimbangkan default otomatis dengan opsi override di Fase 3.
- **Risiko:** kecocokan visual PDF ke Excel asli butuh iterasi — validasi berdampingan dengan file sumber, jangan anggap selesai dari tampilan saja.
