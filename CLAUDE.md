# eip — Enterprise Integration Platform (EIP)

Platform pusat baru untuk kampus yang **menyatukan data & alur lintas sistem**.
EIP menjadi **master data pegawai (satu rujukan)** yang dibaca sistem-sistem
berjalan, dan menjadi payung bagi **aplikasi-aplikasi domain baru** yang berdiri
sendiri (kepegawaian, perencanaan, pengadaan, akademik).

> File ini adalah **referensi global** (dibaca Claude Code dari root proyek).
> Detail teknis lengkap ada di folder `docs/`. Ringkas, akurat, menunjuk ke
> dokumen — jangan duplikasi isi panjang di sini.

---

## 1. Gambaran nyata sistem (realita di kampus)

Terdapat **aplikasi yang SUDAH BEROPERASI & TETAP BERDIRI SENDIRI** (tidak
diserap EIP), semuanya **Laravel + MySQL**:
- Aplikasi **Gaji**
- Aplikasi **Aset**
- Aplikasi **Logistik BHP**

**EIP adalah platform BARU** (terpisah dari aplikasi di atas) yang terdiri dari:

- **EIP Core** — aplikasi pusat: pemegang **master data pegawai** (satu-satunya
  rujukan & satu-satunya pemilik DB master), **RBAC terpusat**, **API `/api/v1/`**
  utk semua konsumen, dan **portal/launcher SSO**.
- **Aplikasi domain** — domain baru dibangun sbg **aplikasi yang berdiri
  sendiri** (Laravel 13 + MySQL sendiri + SPA Vue sendiri, deploy sendiri):
  **perencanaan**, **pengadaan**, dan nantinya **akademik**. Semuanya membaca
  master dari EIP Core API.
- **Kepegawaian = MODUL DI DALAM EIP Core** (keputusan revisi 2026-09-05,
  lihat bawah), BUKAN aplikasi terpisah — beda dari domain lain krn tidak
  py data sendiri (seluruh isinya sudah di DB EIP Core: pegawai, penempatan,
  riwayat_pendidikan, riwayat_pangkat_golongan, keluarga_pegawai,
  dokumen_pegawai). CRUD langsung via Eloquent, tanpa panggil API HTTP
  internal. Ditulis via Blade (gaya AeroDeck, sama dgn dashboard EIP Core),
  bukan Vue SPA. Akses tulis dibatasi role `admin-kepegawaian` (atau `admin`).
- **Server WA blast** (layanan terpisah, sudah dirancang) dipanggil aplikasi
  yang butuh notifikasi outbound.

**Kesimpulan arsitektur:** EIP **bukan** monolith. EIP = **EIP Core (sumber
master + RBAC + API)** ditambah **kumpulan aplikasi domain yang berdiri sendiri**,
diintegrasikan lewat API. Sistem lama (gaji/aset/logistik) menjadi konsumen API
EIP Core dengan pola yang **sama persis** dengan aplikasi domain baru.

### Klarifikasi penting: keputusan "aplikasi terpisah per domain"

Keputusan final (2026-09-03): **tiap domain baru = aplikasi terpisah sejak
awal**, BUKAN modul dalam satu monolith. Rasional: batas domain dipaksakan
secara fisik (proses + DB terpisah), rilis/deploy independen per domain,
kesiapan diserahkan ke tim lain kelak. Rancangan "modular monolith dalam EIP"
sebelumnya — **dibatalkan**.

Konsekuensi yang diterima sadar (tim 1–2 orang): tiap aplikasi punya pipeline
CI/CD, monitoring, konfigurasi OIDC, backup, dan DB sendiri → beban operasional
beberapa kali lipat; alur lintas-app (mis. approval) jadi komunikasi antar-
proses. Mitigasi: **shared library (composer package internal)** utk klien
EIP Core, DTO, dan middleware auth dipakai semua app; alur lintas-app via
event/webhook, bukan transaksi terdistribusi. Analisis lengkap + keberatan
yang tercatat: `docs/04-analisis-dan-rencana-eksekusi.md`.

**Revisi 2026-09-05 (Kepegawaian saja):** setelah benar-benar menyiapkan
hosting EIP Core (SSH, DB, Node tanpa root, deploy script) dirasakan beban
ops-nya, DAN krn tabel Kepegawaian (pegawai, penempatan, riwayat_*,
keluarga_pegawai, dokumen_pegawai) sudah dari awal disimpan di DB EIP Core
sendiri (bukan DB terpisah) — Kepegawaian **dipindah jadi modul di dalam EIP
Core**, bukan app terpisah. Perencanaan/Pengadaan/Akademik TETAP direncanakan
terpisah krn py data sendiri yang genuinely beda (rencana, pengajuan).

```
   ┌─────────────────────────────────────────────────┐
   │                  EIP Core                        │
   │  master pegawai/unit/jabatan/org (satu2nya DB)   │
   │  + MODUL Kepegawaian (Blade, tulis via Eloquent)  │
   │  RBAC terpusat · portal SSO · API /api/v1/        │
   └───┬─────────┬─────────┬────────────────┬─────────┘
       │ baca     │ baca     │ baca (API)     │ baca (API)
  ┌────┴───┐ ┌────┴────┐ ┌───┴──────┐  ┌──────┴───┐┌─────────┐┌───────────┐
  │Perenc. │ │Pengadaan│ │ Akademik │  │  Gaji    ││  Aset   ││ Logistik  │
  │ (app)  │ │ (app)   │ │ (nanti)  │  │ (lama)   ││ (lama)  ││  (lama)   │
  └────────┘ └─────────┘ └──────────┘  └──────────┘└─────────┘└───────────┘
                                    ┌────────────────┐
                                    │  WA blast       │
                                    │  (terpisah)     │
                                    └────────────────┘
```

---

## 2. Stack teknologi EIP

| Lapisan | Pilihan |
|---|---|
| Backend | **Laravel 13** (versi terbaru) + REST API `/api/v1/`, pola Service layer per domain |
| Database | **MySQL** (seragam dgn sistem lama) |
| Auth | **Google Workspace sbg IdP via OIDC** — SSO lintas sistem |
| Frontend | **Vue 3 + Vite + TypeScript + Pinia + Vue Router** — **satu SPA per app** + portal/launcher SSO di EIP Core |
| UI kit | **Element Plus** |
| Queue | Laravel Queue + **Redis** |
| WA Blast | panggil HTTP API server WA blast; status via callback |

> Catatan: DB MySQL dipilih utk seragam dengan sistem-sistem lama (Laravel+MySQL)
> agar integrasi/pertukaran data lebih mudah. (Sebelumnya sempat dirancang
> PostgreSQL, namun dikoreksi utk konsistensi.)

---

## 3. Keputusan arsitektur kunci (PASTIKAN ikut)

1. **EIP = platform baru** = **EIP Core** + **aplikasi domain terpisah**. TIDAK
   menyerap sistem gaji/aset/logistik lama (tetap berdiri sendiri).
2. **Master data pegawai dimiliki EIP Core** (satu-satunya pemilik DB master).
   Semua konsumen — app domain baru + sistem lama — **baca dari EIP Core API**.
3. **Satu jalur tulis** data pegawai: **hanya app kepegawaian**, menulis ke
   EIP Core lewat API (service token). App & sistem lain hanya baca.
4. **Aplikasi terpisah per domain** (keputusan 2026-09-03, final; revisi
   2026-09-05 utk Kepegawaian): EIP Core, perencanaan, pengadaan, (akademik
   nanti) — masing-masing **Laravel 13 + MySQL + SPA sendiri, deploy sendiri**.
   **Kepegawaian = modul DI DALAM EIP Core** (bukan app terpisah), krn semua
   datanya sudah di DB EIP Core sejak awal. Integrasi app lain HANYA lewat
   EIP Core API (tanpa FK lintas-DB). **Shared library** (composer package
   internal) utk klien EIP Core, DTO, middleware auth — dipakai app yg
   genuinely terpisah. Konsekuensi ops (CI/CD, monitoring, OIDC client,
   backup per app) diterima sadar; detail & analisis di
   `docs/04-analisis-dan-rencana-eksekusi.md`.
5. **Tanpa Filament** — kendali penuh UI (workflow rumit di beberapa tempat).
6. Identitas resmi ASN (**NIP, NIK, ID_SIMPEG**) disimpan sbg kolom referensi
   di EIP Core utk matching ke SIMPEG (sumber resmi = SIMPEG/Dukcapil, EIP
   hanya menyalin & mencocokkan — bukan penerbit, bukan sumber live).
7. **SSO**: tiap app = **OIDC client sendiri** ke Google Workspace; login
   terbuka utk akun domain kampus. **RBAC terpusat di EIP Core**; app baca
   peran user via API / klaim token. Role manual per akun.
8. **Workflow = State Machine di backend** app pemilik proses; approval
   mengikuti hierarki `unit_kerja` (dibaca dari EIP Core).

---

## 4. Master data inti (di EIP Core)

`organisasi` → `unit_kerja` (pohon) ↔ `jabatan` dihubungkan `pegawai` via pivot
`penempatan`. Semua tinggal di DB EIP Core; app lain akses via API.
Detail tabel: `docs/01-skemadb-inti.md`.

Prioritas: `unit_kerja` + `pegawai` (paling kritis) → `jabatan`, `organisasi` →
master khusus modul saat modul tsb mulai.

---

## 5. Struktur dokumentasi

| File | Isi |
|---|---|
| `docs/01-skemadb-inti.md` | Desain skema DB inti (master data EIP) |
| `docs/02-rancangan-integrasi.md` | Blueprint integrasi EIP + sistem lama + WA blast |
| `docs/03-autentikasi-sso.md` | Keputusan login/SSO Google OIDC, mode akses, RBAC |
| `docs/04-analisis-dan-rencana-eksekusi.md` | Analisis sistem existing, keputusan "aplikasi terpisah per domain", pola integrasi, blocker, langkah eksekusi |
| `PROGRESS.md` | Checklist & log progres per sesi |

---

## 6. Konvensi proyek

- Bahasa kode: **Inggris**; komentar & dokumen: **Indonesia**.
- Penamaan DB `snake_case`, tabel **jamak**, model **singular**.
- API **versioned** `/api/v1/`.
- Timeline + soft delete utk audit.
- Laravel: Controller tipis → Service → Model/Repository. DILARANG controller
  gemuk.
- Auth: tiap app OIDC client Google sendiri (email = kunci relasi ke pegawai);
  Sanctum / signed JWT utk service-to-service (app ↔ EIP Core ↔ sistem lama ↔
  WA blast).
- Reuse: **shared library composer internal** (klien EIP Core, DTO pegawai/unit,
  middleware auth) wajib dipakai tiap app — jangan duplikasi kode integrasi.

---

## 7. Konteks organisasi

- Institusi: **Fakultas MIPA, Universitas Sebelas Maret (UNS)** — domain
  `eip.mipa.uns.ac.id` (hosting disiapkan pengguna). Tim kecil (1–2 orang), in-house.
- Sistem lama: **gaji, aset, logistik** = Laravel + MySQL, beroperasi & tetap
  dipakai.
- Ada **Google Workspace** (SSO). Identitas resmi ASN: **NIP, NIK, ID_SIMPEG**.
- Ada **server WA blast** terpisah yg sudah dirancang.

---

## 8. Urutan implementasi (fase)

Urutan diselaraskan ke **nilai & risiko** (bukan sekadar urutan modul).
Rincian langkah teknis per fase: `docs/04-analisis-dan-rencana-eksekusi.md`.

1. **Fase 0 — EIP Core:** master (`unit_kerja` → `pegawai` → `jabatan`,
   `organisasi`), RBAC, OIDC, `audit_log`, API `/api/v1/`, portal SSO. ✅
2. **Fase 1 — Modul Kepegawaian (di EIP Core):** CRUD pegawai + penempatan +
   direktori; penulis tunggal master (langsung via Eloquent, satu app).
3. **Fase 2 — Integrasi pilot:** sync master ke **1 sistem lama** (mis.
   logistik) sbg pembuktian pola; sambungkan **WA blast**. (Naik dari fase
   akhir → di sini, karena risiko integrasi terbesar.)
4. **Fase 3 — App Pengadaan:** pengajuan + approval berjenjang + notifikasi WA.
5. **Fase 4 — App Perencanaan:** modul baru; kaitkan output ke pengadaan.
6. **Fase 5 — Sisa sistem lama** (gaji, aset) baca dari EIP Core.
7. **Fase 6 — App Akademik (nanti):** pola master & approval sama.

---

## 9. Status

Kode mulai berjalan: **`eip-core/`** (Laravel 13) — skema master + audit_log
(Langkah 1–2) selesai. Lihat `PROGRESS.md` utk progres detail & riwayat sesi.
