# EIP — Autentikasi & SSO

> Keputusan identitas/login untuk Enterprise Integration Platform dan seluruh
> sistem terkait (EIP, server WA blast, akademik, dll).

Status: **Draft v0.1**

---

## 1. Keputusan

- **TIDAK membangun sistem SSO/password sendiri.**
- **Google Workspace menjadi penyedia identitas (IdP) login utama** via
  standar **OIDC** (OpenID Connect).
- Berlaku sebagai **SSO lintas semua sistem**: EIP, server WA blast, akademik,
  dan sistem lain memakai Google yang sama → satu akun kampus untuk semuanya.
- Mayoritas pengguna = pegawai yang memiliki email Google Workspace kampus.
- Opsional: akun lokal EIP untuk kasus khusus (opsi di bawah).

---

## 2. Alur login (Google sebagai IdP)

```
Pegawai buka EIP
  → klik "Masuk dengan akun Google kampus"
  → dialihkan ke Google (login sekali; sesi Google dipakai semua sistem)
  → Google kembalikan identitas (klaim: sub, email, nama, unit?) via OIDC
  → EIP mencocokkan email ke tabel `pegawai`
  → jika cocok → otorisasi berdasarkan peran; jika belum ada → alur registrasi
```

### Alasan tidak membangun SSO sendiri
- Bagian terberat & paling rawan (hash password, reset, keamanan, penyimpanan)
  dibebankan ke Google — mengurangi beban tim kecil.
- Siklus hidup akun otomatis mengikuti Google Workspace admin
  (aktif/nonaktif pegawai).
- Standar OIDC didukung penuh Laravel (Socialite / Laravel OIDC) tanpa bangun
  sendiri.

---

## 3. Pencocokan identitas → data pegawai

Kunci relasi: **email Google Workspace = kolom `email` pada `pegawai`**.

```
pegawai.email (unique)  ←→  Google account kampus
```

- Saat login, EIP mencari `pegawai` by email.
- Jika email dikenali → lanjut ke otorisasi peran.
- Jika login Google sukses tapi email belum terdaftar sebagai pegawai →
  tampilkan halaman "akun belum terdaftar" atau alur undangan admin
  (mencegah sembarang masuk).

---

## 4. Peran & otorisasi (RBAC)

Login Google menentukan **siapa**; **peran** tetap dikelola di EIP (RBAC):

| Konsep | Dimana |
|---|---|
| Identitas (siapa, via Google OIDC) | Google |
| Peran & izin (modul apa yg boleh diakses) | EIP (tabel role/permission) |
| Data pegawai (unit, jabatan) | EIP |

- Peran default per pegawai (mis. pegawai biasa). Admin menetapkan peran
  khusus (mis. admin gaji, approver pengadaan).
- Server WA blast & sistem lain bisa memakai token/klaim Google yang sama,
  atau memercayakan otorisasi ke EIP.

---

## 5. Kasus khusus & akun lokal (opsional)

Google sebagai IdP utama, namun untuk pengguna yang **tidak punya akun Google
kampus** (mis. operator eksternal, sistem layanan):

- **Opsi A (dianjurkan):** buat akun Google Workspace tamu/fungsional bagi
  mereka → tetap satu jalur login.
- **Opsi B:** sediakan sedikit **akun lokal EIP** khusus pengguna tersebut
  (cadangan, tanpa membangun sistem SSO penuh).

Untuk mayoritas, opsi A dipakai; opsi B hanya sebagai pelengkap bila perlu.

---

## 6. Implikasi untuk server WA blast & sistem lain

- WA blast, akademik, dan sistem lain **tidak perlu membangun login sendiri**.
  Bila memakai Google: sama seperti EIP. Bila antar-sistem dalam kendali Anda,
  pertimbangkan **token layanan (service-to-service)** untuk komunikasi mesin
  (bukan login pengguna) — mis. Laravel Sanctum token / JWT yang ditandatangani.
- Konsistensi satu akun kampus = satu identitas pegawai di semua sistem.

---

## 7. Keputusan terbuka

- [ ] Daftar **domain email** yang diizinkan login (pastikan hanya
      @kampus.ac.id, cegah Google pribadi masuk).
- [ ] Nama penyedia/klien OIDC resmi (harus dibuat di Google Cloud Console).
- [ ] Skema **peran** awal yang dibutuhkan (admin, approver, pegawai, dst).
- [ ] Apakah WA blast & akademik memakai Google langsung atau memercayai EIP.
