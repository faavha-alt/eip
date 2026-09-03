# eip — Enterprise Integration Platform (EIP)

Platform pusat baru untuk kampus yang **menyatukan data & alur lintas sistem**.
EIP menjadi **master data pegawai (satu rujukan)** yang dibaca sistem-sistem
berjalan, dan menjadi rumah bagi modul-modul yang belum ada.

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

**EIP adalah aplikasi BARU** (terpisah dari aplikasi di atas) yang berperan:
1. Pemegang **master data pegawai** (satu-satunya rujukan) — sistem gaji/aset/
   logistik **membaca data pegawai dari EIP**.
2. Rumah bagi modul yang **belum ada**: **kepegawaian**, **perencanaan**,
   **pengadaan**, dan nantinya **akademik**.
3. Pemanggil **server WA blast** (layanan terpisah, sudah dirancang) utk
   notifikasi outbound.

**Kesimpulan arsitektur:** EIP **bukan** "satu monolith yang menyerap semua
modul lama". EIP = **aplikasi pusat + sumber master pegawai** yang berintegrasi
dengan sistem-sistem lama yang tetap berjalan.

```
┌────────────────────────── EIP (aplikasi baru) ──────────────────────────┐
│  Master pegawai (satu rujukan)   +   modul baru:                       │
│  kepegawaian, perencanaan, pengadaan, (akademik nanti)                 │
└───────────▲────────────▲────────────▲──────────────────────────────────┘
            │ baca       │ baca       │ baca (API/DB)
            │ pegawai    │            │
   ┌────────┴─────┐ ┌────┴─────┐ ┌────┴────────┐   ┌───────────────┐
   │ Gaji (lama)  │ │ Aset(lama)│ │Logistik(lama)│ │ WA blast       │
   │ Laravel+MySQL │ │ L+MySQL  │ │  L+MySQL    │   │ (terpisah)     │
   └──────────────┘ └──────────┘ └─────────────┘   └───────────────┘
```

---

## 2. Stack teknologi EIP

| Lapisan | Pilihan |
|---|---|
| Backend | **Laravel 13** (versi terbaru) + REST API `/api/v1/`, pola Service layer per domain |
| Database | **MySQL** (seragam dgn sistem lama) |
| Auth | **Google Workspace sbg IdP via OIDC** — SSO lintas sistem |
| Frontend | **Vue 3 + Vite + TypeScript + Pinia + Vue Router** (SPA terpisah) |
| UI kit | **Element Plus** |
| Queue | Laravel Queue + **Redis** |
| WA Blast | panggil HTTP API server WA blast; status via callback |

> Catatan: DB MySQL dipilih utk seragam dengan sistem-sistem lama (Laravel+MySQL)
> agar integrasi/pertukaran data lebih mudah. (Sebelumnya sempat dirancang
> PostgreSQL, namun dikoreksi utk konsistensi.)

---

## 3. Keputusan arsitektur kunci (PASTIKAN ikut)

1. **EIP = aplikasi pusat baru**, TIDAK menyerap sistem gaji/aset/logistik lama
   (tetap berdiri sendiri).
2. **Master data pegawai dimiliki EIP** — sistem-sistem lain baca dari EIP.
   Arah referensi: sistem lama → baca pegawai dari EIP.
3. **Satu jalur tulis** data pegawai (hanya modul kepegawaian EIP); yang lain
   baca.
4. **Modular monolith DALAM lingkup EIP** (utk modul-modul barunya): satu
   backend, satu DB, satu frontend; tiap domain jadi modul dgn batas jelas.
   DILARANG microservice di dalam EIP (tim kecil).
5. **Tanpa Filament** — kendali penuh UI (workflow rumit di beberapa tempat).
6. Identitas resmi ASN (**NIP, NIK, ID_SIMPEG**) disimpan sbg kolom referensi
   utk matching ke sistem SIMPEG (akses terbatas, bukan sumber live).
7. **SSO**: Google Workspace IdP (OIDC); login terbuka utk akun domain kampus;
   **role manual per akun** (RBAC dikelola EIP).
8. **Workflow = State Machine terpusat di backend**; approval mengikuti hierarki
   `unit_kerja`.

---

## 4. Master data inti (di EIP)

`organisasi` → `unit_kerja` (pohon) ↔ `jabatan` dihubungkan `pegawai` via pivot
`penempatan`. Detail tabel: `docs/01-skemadb-inti.md`.

Prioritas: `unit_kerja` + `pegawai` (paling kritis) → `jabatan`, `organisasi` →
master khusus modul saat modul tsb mulai.

---

## 5. Struktur dokumentasi

| File | Isi |
|---|---|
| `docs/01-skemadb-inti.md` | Desain skema DB inti (master data EIP) |
| `docs/02-rancangan-integrasi.md` | Blueprint integrasi EIP + sistem lama + WA blast |
| `docs/03-autentikasi-sso.md` | Keputusan login/SSO Google OIDC, mode akses, RBAC |
| `PROGRESS.md` | Checklist & log progres per sesi |

---

## 6. Konvensi proyek

- Bahasa kode: **Inggris**; komentar & dokumen: **Indonesia**.
- Penamaan DB `snake_case`, tabel **jamak**, model **singular**.
- API **versioned** `/api/v1/`.
- Timeline + soft delete utk audit.
- Laravel: Controller tipis → Service → Model/Repository. DILARANG controller
  gemuk.
- Auth: OIDC Google (email = kunci relasi ke pegawai); Sanctum utk
  service-to-service (EIP ↔ sistem lama ↔ WA blast).

---

## 7. Konteks organisasi

- Institusi: **kampus/perguruan tinggi**. Tim kecil (1–2 orang), in-house.
- Sistem lama: **gaji, aset, logistik** = Laravel + MySQL, beroperasi & tetap
  dipakai.
- Ada **Google Workspace** (SSO). Identitas resmi ASN: **NIP, NIK, ID_SIMPEG**.
- Ada **server WA blast** terpisah yg sudah dirancang.

---

## 8. Urutan implementasi (fase)

1. **Fase 0 — Fondasi EIP:** master (`unit_kerja` → `pegawai` → `jabatan`,
   `organisasi`), auth OIDC, kerangka modular.
2. **Fase 1 — Kepegawaian:** kelola pegawai + penempatan (sumber master).
3. **Fase 2 — Integrasi data:** buka API master pegawai utk dibaca sistem gaji/
   aset/logistik; sambungkan WA blast sbg notifikasi.
4. **Fase 3 — Perencanaan:** modul baru.
5. **Fase 4 — Pengadaan:** pengajuan + approval lintas unit.
6. **Fase 5 — Akademik (nanti):** sambung ke pola master & approval sama.

---

## 9. Status

Lihat `PROGRESS.md` utk progres detail & riwayat sesi.
