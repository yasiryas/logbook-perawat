# Backlog — Di Luar Scope MVP

Fitur di file ini **sengaja tidak dikerjakan** pada MVP. Kalau opencode (atau siapa pun)
mengusulkan salah satu dari ini di tengah pengerjaan fase MVP, tolak dan catat di sini
saja — jangan langsung masuk kode.

## Belum dikerjakan

- **Laporan pelatihan per unit** (sumber: sheet `3` di file Excel) — rekap jumlah
  pelatihan/seminar yang diikuti tiap petugas per unit per periode. Konteks datanya beda
  dari logbook individu (per unit, bukan per perawat per hari), sebaiknya jadi modul
  terpisah setelah MVP stabil.
- **Notifikasi/reminder pengisian harian** — pengingat ke perawat yang belum mengisi
  logbook hari itu.
- **Dashboard analitik lintas unit** — perbandingan capaian antar unit, tren bulanan,
  dsb. Butuh keputusan desain data terpisah (agregasi lintas `unit_id`).
- **Histori perubahan indikator antar tahun anggaran** — saat ini `indicators` dianggap
  tetap sepanjang tahun berjalan. Kalau rumah sakit mengubah target/indikator di tengah
  tahun, dibutuhkan versioning yang belum dirancang.
- **Import otomatis dari file Excel lama** — migrasi data historis dari file RANAP/RAJAL
  yang sudah ada ke database. Jika dibutuhkan, jadikan task terpisah setelah struktur
  data final, bukan bagian dari MVP.
- **Multi-tahun-anggaran** dalam satu tampilan (perbandingan tahun berjalan vs tahun lalu
  secara berdampingan).

## Catatan

Kalau salah satu item di atas ternyata dibutuhkan lebih cepat dari rencana, diskusikan
dan pindahkan ke `PRD.md` secara eksplisit (update Bagian 6 — Ruang Lingkup) sebelum
mulai dikerjakan. Jangan biarkan item backlog masuk kode tanpa keputusan sadar.
