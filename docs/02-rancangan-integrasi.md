# EIP — Rancangan Integrasi Awal (Blueprint)

> Gambaran arsitektur integrasi Enterprise Integration Platform (EIP) beserta
> server WA blast yang sudah dirancang. Dokumen level konseptual → mengarah ke
> desain teknis implementasi.

Status: **Draft v0.2**

---

## 1. Visi

Satu platform (EIP) yang menjadi **pemilik data operasional dan satu kebenaran**
untuk proses bisnis kampus — kepegawaian, gaji, perencanaan, aset, pengadaan,
logistik BHP, dan nantinya akademik — dengan **WA blast sebagai layanan
notifikasi outbound** terpisah yang dipakai EIP untuk mengirim pesan WA yang
valid dan terlacak.

```
┌─────────────────────────────────────────────────────────────┐
│                      EIP  (monolith + modul)                 │
│                                                              │
│  ┌────────────┐ ┌────────┐ ┌──────────┐ ┌────────────┐       │
│  │Kepegawaian │ │  Gaji  │ │Perencanaan│ │  Aset      │       │
│  └─────┬──────┘ └───┬────┘ └────┬─────┘ └──────┬─────┘       │
│  ┌─────▼──────┐ ┌───▼────┐ ┌────▼─────┐ ┌──────▼─────┐       │
│  │ Pengadaan  │ │Logistik│ │  Akademik│ │  Master    │◄──────┘
│  └─────┬──────┘ │  BHP   │ │ (nanti)  │ │(pegawai,   │
│        └────────┴────────┘ └──────────┘ │ unit_kerja,│
│                        ┌──────────────┐ │ jabatan)   │
│  master data shared ──►│   DB shared  │ │            │
│                        └──────┬───────┘ └────────────┘
└───────────────────────────────┼────────────────────────────
                                │
                         event/queue notifikasi
                                ▼
              ┌─────────────────────────────────────┐
              │     Server WA Blast (terpisah)       │
              │  = sumber pengiriman WA yang valid    │
              │  status, histori, template pesan      │
              └─────────────────────────────────────┘
```

---

## 2. Dua komponen utama

### 2.1 EIP (dibangun sebagai modular monolith)
- Satu backend Laravel, satu database, satu frontend Vue.
- Modul-modul berbagi **data master** (`pegawai`, `unit_kerja`, `jabatan`) via
  foreign key dalam satu DB — tidak ada sinkronisasi antar modul EIP.
- **Tidak melakukan kirim WA sendiri.** Saat ada peristiwa butuh notifikasi,
  EIP **menyerahkan** ke server WA blast.

### 2.2 Server WA blast (sudah dirancang, terpisah)
- Komponen **independen** yang mengelola pengiriman pesan WA.
- Bertindak sebagai **sumber kebenaran status pengiriman**: valid/tidak,
  terkirim/gagal, histori, template.
- EIP memanggilnya via **HTTP API / queue**, bukan membangun gateway sendiri.
- Ke depannya juga melayani akademik & modul lain — satu gateway notifikasi.

---

## 3. Alur kerja integrasi (pola umum)

Karena WA blast **memisahkan `permintaan kirim` dari `status pengiriman`**,
pola yang dianjurkan adalah **asinkron via queue**:

```
1. Modul EIP memicu peristiwa   (mis. gaji terbit, pengadaan butuh approval)
2. EIP menulis "pesan akan dikirim" + memanggil API WA blast
3. WA blast menerima, mengantre, lalu mengirim via provider (Fonnte/Wablas/dll)
4. WA blast melaporkan status balik ke EIP (callback/status query)
5. EIP menyimpan hasil → terlihat di riwayat notifikasi tiap modul
```

**Rekomendasi titik integrasi:**
- Biarkan **server WA blast menjadi pemegang status final** pengiriman.
- EIP hanya menyimpan referensi status (ringkas) untuk kebutuhan UI; detail
  penuh ada di server WA blast. (Opsional: EIP ambil via API saat dibutuhkan.)

---

## 4. Master data & arah referensi

| Master | Pemilik | Siapa baca | Catatan |
|---|---|---|---|
| `pegawai` | Modul Kepegawaian (EIP) | semua modul + WA blast | NIP/NIK/ID_SIMPEG sbg referensi resmi |
| `unit_kerja` | Modul Kepegawaian/org | semua modul | hierarki → penentu alur approval |
| `jabatan` | Modul Kepegawaian | gaji, akademik | |
| `organisasi` | root EIP | semua | umumnya 1 root |
| nomor HP pegawai | Modul Kepegawaian | WA blast | WA blast butuh nomor + consent |

**Aturan:** EIP satu jalur tulis data pegawai; WA blast **baca** kontak dari EIP
atau terima kiriman saat notifikasi di-trigger.

---

## 5. Alur approval lintas modul (karena unit_kerja hierarkis)

`unit_kerja` berbentuk pohon memungkinkan **satu mekanisme approval bersama**:

```
Pengajuan (modul apa pun: gaji, pengadaan, aset)
  → dibuat oleh pegawai di unit_kerja X
  → jalur approval mengikuti hierarki unit X (atasan langsung, kepala unit, dst)
  → setiap langkah = transisi status (State Machine)
  → pada langkah perlu manusia: EIP kirim notifikasi WA via server WA blast
  → selesai/tertolak → update status ke modul asal
```

Ini mencegah approval dibuat ulang per modul — cukup satu mekanisme yang
berbagi data `unit_kerja` & `pegawai`.

---

## 6. Desain teknis tahap awal (rekomendasi)

| Lapisan | Pilihan |
|---|---|
| EIP backend | Laravel 11 + REST `/api/v1/`, Service layer per domain |
| DB | PostgreSQL satu instance |
| Queue | Laravel Queue + Redis |
| Frontend | Vue 3 + Vite + TS + Pinia, UI Element Plus |
| Auth | Laravel Sanctum |
| Integrasi WA | panggil HTTP API server WA blast; status via callback |

---

## 7. Urutan implementasi (fase)

1. **Fase 0 — Fondasi:** struktur master (`unit_kerja` → `pegawai` → `jabatan`,
   `organisasi`), auth, kerangka modul.
2. **Fase 1 — Kepegawaian:** kelola pegawai + penempatan.
3. **Fase 2 — Gaji:** baca pegawai; proses gaji; trigger notifikasi saat gaji terbit.
4. **Fase 3 — Aset + Logistik BHP:** PIC & pemakaian berbasis pegawai/unit.
5. **Fase 4 — Perencanaan & Pengadaan:** pengajuan + approval lintas unit
   (memakai mekanisme approval bersama + notifikasi WA).
6. **Fase 5 — WA blast:** sambungkan sebagai layanan notifikasi lintas modul.
7. **Fase 6 — Akademik (nanti):** sambung ke pola master & approval yang sama.

---

## 8. Keputusan terbuka (perlu dijawab sebelum teknis detail)

- [ ] Detail kontrak API **server WA blast** (format kirim, callback status,
  template, consent/permission pegawai).
- [ ] Nama/struktur unit_kerja nyata di kampus (untuk memastikan hierarki benar).
- [ ] Apakah approval berjenjang selalu mengikuti hierarki unit, atau ada alur
      khusus per modul.
- [ ] Sumber data kontak WA (nomor pegawai) — di-input di EIP atau ditarik dari
      sistem lain yang ada.
