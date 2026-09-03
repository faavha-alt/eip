# EIP — Rancangan Integrasi

> Blueprint integrasi Enterprise Integration Platform (EIP) dengan sistem-sistem
> kampus yang sudah ada dan server WA blast. Level konseptual → desain teknis.

Status: **Draft v0.4** (arsitektur final: **aplikasi terpisah per domain** —
lihat `docs/04`). EIP = **EIP Core** (master + RBAC + API + portal) + **app
domain terpisah** (kepegawaian, perencanaan, pengadaan, akademik). App domain
baru berintegrasi ke EIP Core dgn pola **sama persis** dgn sistem lama.

---

## 1. Visi (terkoreksi)

**EIP Core = aplikasi pusat baru** yang menjadi **sumber master data pegawai**,
RBAC terpusat, API, dan portal SSO. Domain yang belum ada dibangun sbg
**aplikasi terpisah masing-masing** (kepegawaian, perencanaan, pengadaan,
akademik) — semuanya membaca master dari EIP Core API. Sistem lama (**gaji,
aset, logistik** — Laravel+MySQL) **tetap berjalan sendiri** dgn pola integrasi
yang sama. Server **WA blast** (terpisah, sudah dirancang) sbg gateway notifikasi.

Jadi EIP BUKAN monolith dan BUKAN penyerap sistem lama; EIP = **EIP Core +
kumpulan app yang saling terhubung lewat API**, dengan pegawai sbg referensi
tunggal.

```
                 ┌───────────── EIP Core (app) ─────────────┐
                 │ master pegawai/unit/jabatan/org · RBAC    │
                 │ API /api/v1/ · portal SSO · audit_log     │
                 └──▲───▲───▲───▲──────────────────▲─────────┘
       tulis master │   │   │   │ baca (API/sync)  │ baca (API/sync)
        via API ────┘   │   │   │                  │
   ┌────────────┐  ┌────┴┐ ┌┴───┐ ┌┴─────┐   ┌─────┴───┐┌────────┐┌──────────┐
   │Kepegawaian │  │Peren│ │Peng│ │Akade │   │  Gaji   ││  Aset  ││ Logistik │
   │(app+DB+SPA)│  │(app)│ │adaan│ │mik  │   │ (lama)  ││ (lama) ││  (lama)  │
   └────────────┘  └─────┘ │(app)│ │nanti│   └─────────┘└────────┘└──────────┘
                           └─────┘ └─────┘
                                              WA blast (terpisah) ← dipanggil app
```

---

## 2. Komponen

### 2.1 EIP Core (app baru)
- Laravel 13 + MySQL. **Satu-satunya pemilik DB master** (pegawai, unit_kerja,
  jabatan, organisasi, penempatan).
- RBAC terpusat; portal/launcher SSO; `audit_log`.
- Menyediakan **API `/api/v1/`** utk semua konsumen (app domain + sistem lama).
- Tidak mengirim WA sendiri — diserahkan ke server WA blast.

### 2.2 App domain baru (kepegawaian, perencanaan, pengadaan, akademik) — terpisah
- Masing-masing Laravel 13 + MySQL + SPA Vue sendiri, deploy sendiri.
- **Tidak punya tabel master**; baca master via EIP Core API (+ cache).
- **App kepegawaian = penulis tunggal** pegawai/penempatan (via EIP Core API).
- OIDC client Google sendiri; pakai **shared library `eip/client`**.
- Alur lintas-app (mis. perencanaan → pengadaan) via **event/webhook + Redis**.

### 2.3 Sistem lama (gaji, aset, logistik) — tetap berdiri
- Laravel + MySQL, tetap beroperasi.
- Integrasi dgn EIP Core utk **membaca master pegawai** — pola **sama** dgn app
  domain baru (sync 1 arah → panggil API). Tidak dipaksa menyerap ke EIP.

### 2.4 Server WA blast (terpisah, sudah dirancang)
- Gateway & pemegang status pengiriman WA yang valid.
- EIP Core & app lain memanggilnya via API/queue.

---

## 3. Integrasi app konsumen ↔ EIP Core (arah data)

Berlaku sama untuk **app domain baru** maupun **sistem lama**:

| App | Arah data dgn EIP Core | Mekanisme (rekomendasi) |
|---|---|---|
| Kepegawaian | **tulis + baca** pegawai/penempatan | EIP Core API (service token khusus) |
| Perencanaan / Pengadaan / Akademik | baca pegawai & unit | EIP Core API + cache |
| Gaji | baca pegawai | pola A: sync 1 arah → `pegawai_ref`; lalu pola B: API |
| Aset | baca pegawai & unit | idem |
| Logistik | baca pegawai & unit | idem (kandidat pilot Fase 2) |
| (semua kecuali kepegawaian) | tulis data pegawai | TIDAK — hanya via app kepegawaian |

**Keputusan penting yang harus dijawab:** apakah tiap konsumen **menyimpan
salinan pegawai lokal** (perlu sinkronisasi) atau **query live ke EIP Core**.
Untuk sistem yg sudah jalan: umumnya salinan lokal + sync berkala (pola A).
Dikonfirmasi per sistem (lihat `docs/04` §7 blocker #2–3).

---

## 4. Master data & pemilik

| Master | DB pemilik | Penulis | Siapa baca |
|---|---|---|---|
| `pegawai`, `penempatan` | EIP Core | app kepegawaian (via API) | semua app + sistem lama |
| `unit_kerja` | EIP Core | EIP Core | semua |
| `jabatan` | EIP Core | EIP Core | semua (gaji, akademik dsb) |
| `organisasi` | EIP Core | EIP Core | semua |

**Aturan:** satu jalur tulis data pegawai = app kepegawaian → EIP Core API.
Semua app lain + sistem lama read dari EIP Core. **Data pegawai paling valid =
EIP Core (SSOT).** NIP/NIK/ID_SIMPEG = referensi; sumber resmi SIMPEG/Dukcapil,
EIP hanya matching.

---

## 5. Alur approval lintas app (app domain baru)

`unit_kerja` pohon → **pola approval yang sama** dipakai app perencanaan &
pengadaan. Karena app terpisah, langkah lintas-app lewat **event/webhook + Redis**
(bukan transaksi terdistribusi):

```
Pengajuan di app pengadaan
  → dibuat pegawai di unit_kerja X (id + atribut di-resolve dari EIP Core API)
  → approval mengikuti hierarki unit X (State Machine di backend pengadaan)
  → langkah butuh manusia → notifikasi WA via server WA blast
  → selesai/tolak → app pengadaan kirim event → app asal update status
```

State machine hidup di backend app pemilik proses; hierarki approval selalu
dibaca dari EIP Core (`unit_kerja`).

---

## 6. Desain teknis (tiap app)

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel 13 + REST `/api/v1/`, Service layer per domain |
| DB | MySQL per app (master hanya di EIP Core) |
| Queue | Laravel Queue + Redis |
| Frontend | Vue 3 + Vite + TS + Pinia, Element Plus — 1 SPA per app + portal SSO di Core |
| Auth | tiap app OIDC client Google sendiri; Sanctum/JWT service-to-service |
| Reuse | shared library composer internal `eip/client` (klien Core, DTO, middleware) |
| Integrasi konsumen | EIP Core API utk master; sync berkala (pola A) atau live (pola B) |
| Integrasi WA | panggil HTTP API server WA blast; status via callback |

---

## 7. Urutan implementasi (fase)

Sinkron dgn `CLAUDE.md §8` & langkah teknis `docs/04 §6`.

1. **Fase 0 — EIP Core:** master (`unit_kerja` → `pegawai` → `jabatan`,
   `organisasi`), RBAC, OIDC, `audit_log`, API `/api/v1/`, portal, shared library.
2. **Fase 1 — App Kepegawaian:** kelola pegawai + penempatan + direktori (tulis via Core API).
3. **Fase 2 — Integrasi pilot:** sync master ke 1 sistem lama (mis. logistik) + sambung WA blast.
4. **Fase 3 — App Pengadaan:** pengajuan + approval berjenjang + notifikasi WA.
5. **Fase 4 — App Perencanaan:** kaitkan ke pengadaan.
6. **Fase 5 — Sisa sistem lama** (gaji, aset) baca dari EIP Core.
7. **Fase 6 — App Akademik (nanti).**

---

## 8. Keputusan terbuka (perlu dijawab sblm teknis detail)

Daftar lengkap + prioritas: `docs/04 §7`. Ringkas:

- [ ] Ruang lingkup: 1 fakultas atau 1 universitas.
- [ ] Kontrak API **server WA blast** (format kirim, callback, template); status siap?
- [ ] Struktur **unit_kerja** nyata; hierarki approval = hierarki unit?
- [ ] **Mekanisme integrasi** tiap sistem lama: akses DB/kode, kolom kunci pegawai.
- [ ] Skema **role** awal (RBAC) + siapa menetapkan.
- [ ] Source kontak WA (nomor pegawai).
- [ ] Kontrak **event/webhook** antar-app (format, retry, idempotency).
