# EIP — Rancangan Integrasi

> Blueprint integrasi Enterprise Integration Platform (EIP) dengan sistem-sistem
> kampus yang sudah ada dan server WA blast. Level konseptual → desain teknis.

Status: **Draft v0.3** (dikoreksi sesuai realita: sistem lama tetap berdiri)

---

## 1. Visi (terkoreksi)

**EIP = aplikasi pusat baru** yang menjadi **sumber master data pegawai** dan
rumah bagi modul-modul yang belum ada. Sistem-sistem lama (**gaji, aset,
logistik** — Laravel+MySQL) **tetap berjalan sendiri** dan **membaca data
pegawai dari EIP**. Server **WA blast** (terpisah, sudah dirancang) dipakai sbg
gateway notifikasi.

Jadi EIP BUKAN menggantikan/menyerap sistem lama; EIP **menyatukan referensi
(pegawai) & menyediakan modul yang belum ada**.

```
┌────────────────────────── EIP (aplikasi baru) ──────────────────────────┐
│  Master pegawai (satu rujukan)  +  modul baru:                          │
│  kepegawaian, perencanaan, pengadaan, (akademik nanti)                  │
└───────────▲────────────▲────────────▲──────────────────────────────────┘
            │ baca       │ baca       │ baca (API/DB)                     │
   ┌────────┴─────┐ ┌────┴─────┐ ┌────┴────────┐   ┌───────────────┐       │
   │ Gaji (lama)  │ │ Aset(lama)│ │Logistik(lama)│  │ WA blast       │      │
   │ Laravel+MySQL │ │ L+MySQL  │ │  L+MySQL    │  │ (terpisah)     │      │
   └──────────────┘ └──────────┘ └─────────────┘   └───────────────┘      │
```

---

## 2. Komponen

### 2.1 EIP (aplikasi baru)
- Laravel 13 + MySQL, modular monolith **dalam lingkup EIP**.
- Memegang **master pegawai**; modul baru (kepegawaian, perencanaan, pengadaan,
  akademik) berbagi master via FK satu DB.
- Menyediakan **API master pegawai** utk dibaca sistem lama.
- Tidak mengirim WA sendiri — menyerahkan ke server WA blast.

### 2.2 Sistem lama (gaji, aset, logistik) — tetap berdiri
- Laravel + MySQL, tetap beroperasi.
- Berintegrasi dgn EIP utk **membaca master pegawai** (API EIP atau shared DB
  read). Tidak dipaksa menyerap ke EIP.

### 2.3 Server WA blast (terpisah, sudah dirancang)
- Gateway & pemegang status pengiriman WA yang valid.
- EIP & modul lain memanggilnya via API/queue.

---

## 3. Integrasi sistem lama ↔ EIP (arah data)

| Sistem | Arah data dgn EIP | Mekanisme (rekomendasi) |
|---|---|---|
| Gaji | baca pegawai dari EIP | API EIP `/api/v1/pegawai` (atau read-only DB) |
| Aset | baca pegawai & unit dari EIP | API EIP |
| Logistik | baca pegawai & unit dari EIP | API EIP |
| (semua) | tulis perubahan data pegawai | HANYA via modul kepegawaian EIP |

**Keputusan penting yang harus dijawab:** apakah sistem lama **menyimpan salinan
pegawai lokal** (perlu sinkronisasi) atau **selalu query live ke EIP**. Untuk
sistem yg sudah jalan, umumnya memakai salinan lokal + sinkronisasi berkala via
API EIP. Ini harus dikonfirmasi per sistem.

---

## 4. Master data & pemilik

| Master | Pemilik | Siapa baca |
|---|---|---|
| `pegawai` | EIP (kepegawaian) | semua modul EIP + sistem gaji/aset/logistik |
| `unit_kerja` | EIP | semua |
| `jabatan` | EIP | gaji, akademik |
| `organisasi` | EIP | semua |

**Aturan:** satu jalur tulis data pegawai di EIP. Sistem lama read dari EIP.
NIP/NIK/ID_SIMPEG sbg referensi resmi utk matching SIMPEG.

---

## 5. Alur approval lintas modul EIP (modul baru)

`unit_kerja` pohon → **satu mekanisme approval bersama** di antara modul baru
EIP (perencanaan, pengadaan):

```
Pengajuan di modul EIP (mis. pengadaan)
  → dibuat pegawai di unit_kerja X
  → approval mengikuti hierarki unit X (State Machine)
  → langkah butuh manusia → notifikasi WA via server WA blast
  → selesai/tolak → update status modul asal
```

---

## 6. Desain teknis EIP

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel 13 + REST `/api/v1/`, Service layer per domain |
| DB | MySQL (seragam sistem lama) |
| Queue | Laravel Queue + Redis |
| Frontend | Vue 3 + Vite + TS + Pinia, UI Element Plus |
| Auth | OIDC Google (SSO lintas sistem) |
| Integrasi lama | API EIP utk master pegawai; read-only atau sync berkala |
| Integrasi WA | panggil HTTP API server WA blast; status via callback |

---

## 7. Urutan implementasi (fase)

1. **Fase 0 — Fondasi EIP:** master (`unit_kerja` → `pegawai` → `jabatan`,
   `organisasi`), auth OIDC, kerangka modular.
2. **Fase 1 — Kepegawaian:** kelola pegawai + penempatan.
3. **Fase 2 — Integrasi:** API master pegawai utk sistem gaji/aset/logistik;
   sambungkan WA blast sbg notifikasi.
4. **Fase 3 — Perencanaan:** modul baru.
5. **Fase 4 — Pengadaan:** pengajuan + approval lintas unit.
6. **Fase 5 — Akademik (nanti):** sambung ke pola master & approval sama.

---

## 8. Keputusan terbuka (perlu dijawab sblm teknis detail)

- [ ] Kontrak API **server WA blast** (format kirim, callback, template).
- [ ] Struktur **unit_kerja** nyata kampus.
- [ ] **Mekanisme integrasi** tiap sistem lama: salinan lokal+sync vs query live.
      Siapa baca-tulis apa di masing-masing sistem (gaji/aset/logistik).
- [ ] Skema **role** awal (RBAC).
- [ ] Source kontak WA (nomor pegawai).
- [ ] Versi/akses DB & API nyata sistem lama (gaji/aset/logistik).
