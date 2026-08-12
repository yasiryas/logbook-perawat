# 📋 Logbook Perawat — RSU Sakina Idaman

Aplikasi **logbook kinerja harian perawat** berbasis web untuk menggantikan pengisian
manual di file Excel. Input harian per perawat → rekap bulanan & tahunan → penilaian
tahunan → cetak PDF siap tanda tangan, semuanya dalam satu sistem.

- **Backend:** Laravel 13 + Inertia.js
- **Frontend:** Vue 3 + Tailwind CSS 4 + shadcn-vue (reka-ui)
- **Database:** SQLite (default) / MySQL
- **Kalkulasi:** satu sumber kebenaran di backend (`LogbookCalculationService`)

> Dokumen pendukung: [`docs/PRD (2).md`](docs/PRD%20(2).md) · [`docs/BACKLOG.md`](docs/BACKLOG.md) · [`docs/UI-GUIDELINES.md`](docs/UI-GUIDELINES.md)

---

## ✨ Fitur

- ✅ Login & 3 peran: **Perawat**, **Kepala Ruang**, **Administrator**
- ✅ Input logbook harian — grid indikator × tanggal dalam satu tampilan
- ✅ Rekap bulanan & tahunan otomatis (bukan disalin manual dari Excel)
- ✅ Form penilaian tahunan, capaian terisi otomatis, bisa disesuaikan & **difinalisasi**
- ✅ Cetak PDF: logbook bulanan, rekap tahunan, penilaian tahunan
- ✅ Master data: unit, perawat, indikator (global + spesifik per unit)
- ✅ Export/import master data via Excel (`.xlsx`)
- ✅ Auto-save input harian

---

## 🧑‍💻 Kebutuhan Sistem

| Alat | Versi |
|---|---|
| PHP | ^8.3 |
| Composer | 2.x |
| Node.js | ^20 atau ^22 |
| npm / pnpm | npm 10+ |
| Database | SQLite (bawaan) atau MySQL 8 |

---

## 🚀 Instalasi

### 1. Ambil kode & pasang dependensi

```bash
git clone <url-repo> logbook-rs
cd logbook-rs
composer install
npm install
```

### 2. Konfigurasi environment

```bash
copy .env.example .env        # Windows (cmd)
# cp .env.example .env        # Linux / macOS / Git Bash

php artisan key:generate
```

Sesuaikan `.env` bila perlu:

```dotenv
APP_NAME="Logbook Perawat"
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite          # default — tidak perlu setup tambahan
# DB_CONNECTION=mysql         # atau pakai MySQL
# DB_DATABASE=logbook
# DB_USERNAME=root
# DB_PASSWORD=
```

> **SQLite:** database dibuat otomatis saat migrate. Bila mau manual:
> `touch database/database.sqlite` (Linux/macOS) atau `type nul > database\database.sqlite` (Windows).

### 3. Migrasi + seed data contoh

```bash
php artisan migrate --seed
```

### 4. Jalankan aplikasi

```bash
npm run dev                   # dev server aset (Vite)
composer run dev              # Laravel dev server + asset (chisel)
```

Buka **http://localhost:8000** (lihat nilai `APP_URL`).

### ⚙️ Install cepat (opsional)

Script `composer run setup` menjalankan semua langkah 1–3 sekaligus:

```bash
composer run setup
```

### 🧹 Reset database (kalau mau mulai bersih)

```bash
php artisan migrate:fresh --seed
```

---

## 👤 Akun Demo

Semua akun demo memakai password **`123456`**:

| Peran | Email |
|---|---|
| Administrator | `admin@mail.com` |
| Kepala Ruang | `kepala_ruang@mail.com` |
| Perawat | `perawat@mail.com` |
| Perawat (seeder NursesSeeder) | `perawat01@mail.com` … `perawatNN@mail.com` |

> Akun dibuat oleh `LogbookSeeder` & `NursesSeeder` saat `php artisan migrate --seed`.

---

## 🧮 Rumus & Perhitungan

Semua angka dihitung **di backend** oleh `app/Services/LogbookCalculationService.php`,
tidak pernah dihitung ulang di frontend/Excel. Nilai capaian tidak disimpan permanen —
selalu dihitung ulang dari `logbook_entries`, kecuali snapshot penilaian final.

### Notasi

| Simbol | Arti |
|---|---|
| `vᵢ,ₖ` | Nilai indikator `i` pada hari `k` |
| `Hₘ` | Jumlah hari kerja bulan `m` (kolom manual "Jumlah Hari", default = panjang kalender) |
| `Σᵢ` | Jumlah seluruh nilai harian indikator `i` dalam satu bulan |
| `n` | Jumlah indikator dalam satu kategori |

### 1. Capaian Indikator Bulanan

> Rasio total nilai harian terhadap jumlah hari kerja.

```
Capaian(i, bulan) = Σᵢ / Hₘ
```

### 2. Capaian Kategori Bulanan

> Rata-rata capaian seluruh indikator di kategori tersebut.

```
Capaian(kategori, bulan) = ( Σₙ Capaian(i) ) / n
```

### 3. Total Rekap Bulanan

> **Jumlah** capaian kategori (bukan rata-rata berbobot) — mengikuti format kertas lama.

```
Total(bulan) = Capaian(A) + Capaian(B) + Capaian(C)
```

### 4. Capaian Indikator Tahunan

> Rata-rata dari 12 capaian bulanan.

```
Capaian_tahunan(i) = ( Σₘ₌₁..₁₂ Capaian(i, bulan m) ) / 12
```

### 5. Skor & Total Penilaian Tahunan (berbobot)

Bobot kategori: **A. Perilaku = 40%**, **B. Pengembangan Profesi = 10%**,
**C. Kinerja Klinis = 50%** (lihat `indicator_categories.weight_percent`).

```
Score(i) = Capaian_tahunan(i) × bobot_kategori

Total = Σ Score(i)  →  maksimum 100
```

### 6. Target & Ketercapaian

- Tiap indikator punya `target` (mis. `100`, `<5 mnt`) — dibandingkan dengan capaian
  pada rekap untuk menandai status tercapai / mendekati / kurang.
- Kategori **A & B** memakai indikator **global** (sama di semua unit).
- Kategori **C** memakai indikator **spesifik per unit** (`indicators.unit_id`).

> Implementasi: `LogbookCalculationService::monthlyRecap()`, `annualPerIndicator()`,
> `annualScoresByNurses()`, `assessmentRows()`.

---

## 📂 Struktur Folder

```
app/
├─ Http/Controllers/        # Dashboard, logbook, recaps, assessments, master data, print
├─ Models/                  # Nurse, Indicator, IndicatorCategory, LogbookEntry, MonthlyConfig, AnnualAssessment, Unit
├─ Services/
│  ├─ LogbookCalculationService.php   # Semua rumus (lihat di atas)
│  └─ MasterDataExcelService.php      # Export/import master via Excel
database/
├─ migrations/              # Skema: units, nurses, indicators, logbook_entries, monthly_configs, annual_assessments
└─ seeders/                 # LogbookSeeder, NursesSeeder, ExampleLogbookSeeder
resources/
├─ js/pages/                # Halaman Vue: logbook, recaps, assessments, nurses, units, indicators
└─ views/print/             # Template PDF (Blade): logbook, annual, assessment
routes/web.php              # Daftar route utama
docs/                       # PRD, backlog, UI guidelines, file Excel sumber
```

---

## 🧪 Testing & Quality

```bash
npm run types:check     # vue-tsc — cek tipe TypeScript
npm run lint:check      # eslint + prettier
php artisan pint --test # Laravel Pint (format PHP)
php artisan test        # PHPUnit
composer run test       # semua: lint + phpstan + test
```

---

## ❓ FAQ / Catatan

<details>
<summary><b>Kenapa angka rekap tidak sama dengan Excel?</b></summary>

Formula Excel lama rapuh (`#REF!`, `#DIV/0!`). Aplikasi menghitung ulang konsisten dari
data harian. Pastikan kolom "Jumlah Hari" (`Hₘ`) sudah benar — ini penyebut semua
kalkulasi capaian.
</details>

<details>
<summary><b>Dark mode?</b></summary>

Sengaja **tidak ada** — di luar scope MVP. Aplikasi light-only.
</details>

<details>
<summary><b>Penilaian sudah difinalisasi, angkanya berubah?</b></summary>

Tidak. `annual_assessment_items` menyimpan snapshot saat finalisasi — dokumen resmi tidak
berubah setelah ditandatangani.
</details>

---

## 📄 Lisensi

MIT.