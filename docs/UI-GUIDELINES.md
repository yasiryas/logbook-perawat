# UI Guidelines — Logbook Perawat

**Referensi gaya:** SB Admin (dashboard klasik: sidebar gelap fixed, topbar, layout berbasis card)
**Branding:** Hijau toska sebagai warna primer, menggantikan biru default SB Admin
**Implementasi:** Tailwind CSS (bawaan Laravel Breeze + Vue), bukan Bootstrap — tiru
strukturnya, bukan library-nya.

> Tujuan dokumen ini: supaya setiap layar yang dibuat opencode terlihat konsisten, tanpa
> perlu menjelaskan ulang gaya di tiap prompt. Rujuk file ini, jangan minta "buat
> tampilannya modern/bagus" secara vague.

---

## 1. Palet Warna — Hijau Toska

Tambahkan ke `tailwind.config.js` sebagai warna kustom `toska`:

| Token | Hex | Pemakaian |
|---|---|---|
| `toska-50` | `#E6F7F5` | Background hover ringan, badge subtle |
| `toska-100` | `#C0ECE7` | Background ikon di stat card |
| `toska-200` | `#96E0D8` | Border aksen ringan |
| `toska-300` | `#69D3C8` | — |
| `toska-400` | `#3FC7B8` | Hover state elemen sekunder |
| `toska-500` | `#16BBA9` | **Warna brand utama** — logo, active nav, link |
| `toska-600` | `#109C8D` | Tombol primary (default state) |
| `toska-700` | `#0B7A70` | Tombol primary (hover), heading aksen |
| `toska-800` | `#075C54` | Sidebar background (varian gelap) |
| `toska-900` | `#04413B` | Sidebar background (default, mirip navy SB Admin tapi toska) |

**Netral pendukung** (dari palet `slate` atau `gray` Tailwind bawaan):
- Background halaman: `slate-100` (`#F1F5F9`) — setara `#F8F9FC` di SB Admin asli
- Card: putih (`#FFFFFF`), border `slate-200`, `shadow-sm`
- Teks utama: `slate-800`, teks sekunder: `slate-500`

**Warna status** (dipakai konsisten untuk badge & indikator capaian):
- Sukses/capaian tercapai: `emerald-500` / `emerald-100` (bg)
- Peringatan/mendekati target: `amber-500` / `amber-100`
- Kurang/di bawah target: `rose-500` / `rose-100`
- Info/netral: `sky-500` / `sky-100`

Jangan pakai warna biru default SB Admin (`#4E73DF`) di elemen manapun — semua slot yang
di SB Admin asli berwarna biru primer diganti `toska-500`/`toska-600`.

## 2. Tipografi

- Font utama: **Nunito** (font default SB Admin) via Google Fonts, konsisten di seluruh
  aplikasi — jangan campur dengan font lain.
- Skala: heading halaman `text-2xl font-bold`, judul card `text-base font-semibold`,
  body `text-sm`, label form `text-xs font-medium uppercase tracking-wide text-slate-500`.
- Angka besar di stat card boleh `text-3xl font-bold` supaya menonjol dari label di
  bawahnya.

## 3. Struktur Layout

```
┌─────────────┬──────────────────────────────────────────┐
│             │  Topbar: breadcrumb kiri, user menu kanan │
│  Sidebar    ├──────────────────────────────────────────┤
│  (fixed,    │                                            │
│  toska-900) │  Konten halaman (card-based)               │
│             │                                            │
│  - Logo     │                                            │
│  - Menu     │                                            │
│    grup     │                                            │
│  - Nav item │                                            │
│             │                                            │
└─────────────┴──────────────────────────────────────────┘
```

**Sidebar**
- Background `toska-900`, teks putih/`slate-300`.
- Item aktif: latar `toska-800` + garis kiri 3px `toska-400`, teks putih penuh.
- Ikon di kiri tiap label (pakai set ikon konsisten, mis. Heroicons — jangan campur set ikon berbeda).
- Kelompokkan menu per peran: menu Perawat (Input Harian, Rekap Saya), menu Kepala Ruang (Master Data, Semua Perawat, Penilaian Tahunan) tampil sesuai role yang login.

**Topbar**
- Putih, `shadow-sm`, sticky di atas.
- Kiri: breadcrumb (nama halaman saat ini).
- Kanan: nama user + dropdown (profil, logout).

**Konten halaman**
- Semua blok konten dibungkus card putih (`rounded-lg border border-slate-200 shadow-sm p-6`) — jangan taruh tabel/form langsung di background halaman tanpa card.
- Halaman dashboard/ringkasan: baris stat card di atas (ikon bulat `bg-toska-100 text-toska-600`, angka besar, label kecil), lalu card tabel/grafik di bawahnya — pola klasik SB Admin.

## 4. Komponen

**Tombol**
- Primary: `bg-toska-600 hover:bg-toska-700 text-white rounded-md px-4 py-2 text-sm font-medium`
- Secondary/outline: `border border-slate-300 text-slate-700 hover:bg-slate-50`
- Destruktif (hapus): `bg-rose-600 hover:bg-rose-700 text-white` — dipakai hanya untuk aksi hapus data, bukan default.

**Tabel**
- Header: `bg-slate-50 text-xs uppercase tracking-wide text-slate-500`
- Baris: hover `bg-slate-50`, border bawah `slate-200` (bukan garis penuh tiap sisi).
- Kolom angka (capaian, target, nilai) rata kanan.

**Badge / status pill**
- Bentuk pill (`rounded-full px-2.5 py-0.5 text-xs font-medium`), warna sesuai Bagian 1
  (status capaian: sukses/warning/kurang).

**Form**
- Label di atas input (bukan di samping), `text-xs font-medium text-slate-600 mb-1`.
- Input: `border-slate-300 rounded-md focus:ring-toska-500 focus:border-toska-500`.

**Grid input harian (khusus halaman logbook)**
- Ini komponen paling custom di aplikasi — tabel lebar dengan kolom tanggal 1–31.
- Header kolom tanggal: sempit, angka saja, `text-center`.
- Sel input: kecil, `text-center`, tanpa border tebal antar sel (pakai `divide-x divide-slate-200` supaya tidak terlalu ramai secara visual).
- Baris kategori (A/B/C) sebagai pemisah visual: `bg-toska-50 font-semibold text-toska-800`, bukan warna netral — supaya section kategori langsung terbaca.

## 5. Yang Dihindari

- Jangan tambah animasi/transisi berlebihan (fade, slide, hover-scale) di luar transisi standar (`transition-colors duration-150`) — ini bukan landing page marketing, ini alat kerja harian.
- Jangan ganti warna brand di komponen tertentu secara ad-hoc (mis. tombol biru di satu halaman karena "kelihatan lebih bagus") — semua warna brand mengacu ke token `toska-*` di Bagian 1.
- Jangan bikin dark-mode di MVP kecuali diminta eksplisit — di luar scope, tambahkan ke `BACKLOG.md` kalau muncul kebutuhan.
