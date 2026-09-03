# eip — Enterprise Integration Platform (EIP)

Sistem terintegrasi kampus untuk menggabungkan modul **kepegawaian, gaji,
perencanaan, aset, pengadaan, logistik BHP, WA blast**, dan nantinya
**akademik** dalam satu aplikasi terpadu.

> File ini adalah **referensi global** (dibaca Claude Code dari root proyek).
> Detail teknis lengkap ada di folder `docs/`. Jangan duplikasi isi panjang di
> sini — cukup ringkas, akurat, dan menunjuk ke dokumen.

---

## 1. Visi & arsitektur

Satu platform (**modular monolith**) sebagai pemilik data operasional & satu
kebenaran proses bisnis kampus, dengan **server WA blast terpisah** sebagai
gateway notifikasi outbound yang dipanggil EIP.

- **Modular monolith**: satu backend, satu database, satu frontend. Tiap domain
  jadi modul dalam codebase yang sama dengan batas jelas (DILARANG microservice
  sejak awal — tim kecil).
- **EIP** memegang semua data operasional; modul berbagi master data via foreign
  key dalam satu DB (tanpa sinkronisasi antar-modul EIP).
- **Server WA blast**: komponen independen (sudah dirancang pengguna) = sumber
  pengiriman/status pesan WA yang valid. EIP TIDAK membangun gateway sendiri,
  hanya memanggilnya.

### Stack teknologi

| Lapisan | Pilihan |
|---|---|
| Backend | **Laravel 11** + REST API `/api/v1/`, pola Service layer per domain |
| Database | **PostgreSQL** (satu instance) |
| Auth | **Google Workspace sbg IdP via OIDC** — SSO lintas sistem |
| Frontend | **Vue 3 + Vite + TypeScript + Pinia + Vue Router** (SPA terpisah) |
| UI kit | **Element Plus** |
| Queue | Laravel Queue + **Redis** |
| WA Blast | panggil HTTP API server WA blast (pihak ketiga/terpisah); status via callback |

---

## 2. Keputusan arsitektur kunci (PASTIKAN ikut)

1. **Modular monolith**, bukan microservice.
2. **Tanpa Filament** — kendali penuh UI (workflow rumit di beberapa tempat).
3. **Master data pegawai dimiliki EIP** (Pola A/C pragmatis). Sistem eksternal
   (SIMPEG/akademik lama) akses terbatas & tidak andal → BUKAN sumber live.
   NIP/NIK/ID_SIMPEG disimpan sbg kolom referensi utk matching.
4. **Satu jalur tulis** data pegawai (hanya modul kepegawaian); modul lain baca.
5. **Master data** (`pegawai`, `unit_kerja` pohon, `jabatan`, `organisasi`)
   dirujuk lintas modul, tidak diduplikasi.
6. **SSO**: Google Workspace IdP (OIDC), tidak bangun SSO/password sendiri.
7. **Mode akses**: login terbuka utk semua akun domain kampus; **role manual per
   akun** menentukan modul (RBAC dikelola EIP).
8. **Workflow = State Machine terpusat di backend** (daftar status + transisi sah
   per modul); approval berjenjang mengikuti hierarki `unit_kerja`.

---

## 3. Master data inti

`organisasi` → `unit_kerja` (pohon) ↔ `jabatan` dihubungkan `pegawai` via pivot
`penempatan`. Detail tabel lengkap: `docs/01-skemadb-inti.md`.

Prioritas pembangunan master:
1. `unit_kerja` + `pegawai` (paling kritis, dirujuk semua modul) — desain bersama,
   perhatian tertinggi.
2. `jabatan`, `organisasi`.
3. Master khusus modul (barang/vendor/dll) dibuat saat modul terkait mulai.

---

## 4. Struktur dokumentasi

| File | Isi |
|---|---|
| `docs/01-skemadb-inti.md` | Desain skema DB inti (master data, kolom, relasi) |
| `docs/02-rancangan-integrasi.md` | Blueprint integrasi EIP + WA blast, alur notifikasi & approval, fase |
| `docs/03-autentikasi-sso.md` | Keputusan login/SSO Google OIDC, mode akses, RBAC |
| `PROGRESS.md` | Checklist & log progres per sesi |

---

## 5. Konvensi proyek

- Bahasa kode: **Inggris**; komentar & dokumen: **Indonesia**.
- Penamaan DB `snake_case`, tabel **jamak**, model **singular**.
- API **versioned** `/api/v1/`.
- Timeline (`created_at`/`updated_at`) + soft delete (`deleted_at`) utk audit.
- Laravel: Controller tipis → Service (logika bisnis/workflow) → Model/Repository.
  DILARANG controller gemuk.
- Auth: OIDC Google (email = kunci relasi ke pegawai); Sanctum utk
  service-to-service.

---

## 6. Konteks organisasi (penting utk desain)

- Institusi: **kampus/perguruan tinggi**. Tim pembangun kecil (1–2 orang).
- Membangun **in-house**.
- Ada Google Workspace (email kampus) — dipakai sbg SSO.
- Identitas resmi ASN yg wajib dipegang: **NIP, NIK, ID_SIMPEG**.
- Ada server WA blast tersendiri yg sudah dirancang → dipakai sbg gateway.

---

## 7. Urutan implementasi (fase)

1. **Fase 0 — Fondasi:** master (`unit_kerja` → `pegawai` → `jabatan`,
   `organisasi`), auth OIDC, kerangka modul.
2. **Fase 1 — Kepegawaian:** kelola pegawai + penempatan.
3. **Fase 2 — Gaji:** baca pegawai; proses; notifikasi saat terbit.
4. **Fase 3 — Aset + Logistik BHP:** PIC & pemakaian berbasis pegawai/unit.
5. **Fase 4 — Perencanaan & Pengadaan:** pengajuan + approval lintas unit.
6. **Fase 5 — WA blast:** sambungkan sbg notifikasi lintas modul.
7. **Fase 6 — Akademik (nanti):** sambung ke pola master & approval sama.

---

## 8. Status

Lihat `PROGRESS.md` utk progres detail & riwayat sesi.
