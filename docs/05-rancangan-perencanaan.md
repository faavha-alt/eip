# EIP — Rancangan App Perencanaan (STRAP)

> **Nama produk: STRAP** — *Strategic Resource & Allocation Planning*.
> Kode/folder/repo tetap `perencanaan` (istilah domain/roadmap).
>
> Dokumen desain app **Perencanaan** — aplikasi terpisah pertama yang dibangun
> setelah EIP Core + Kepegawaian (lihat keputusan arsitektur `CLAUDE.md` §1/§3).
> Status: **v1.0 — desain final sebelum mulai coding.** Semua pertanyaan
> terbuka dari draft v0.1 sudah diputuskan (lihat §8). Prinsip desain:
> **sesederhana mungkin** — skema pengadaan barang & pemeliharaan DISATUKAN
> (§4), bukan dua struktur paralel yg mirip-mirip (lihat §2 alasannya).

---

## 1. Posisi dalam arsitektur

**App terpisah** (Laravel 13 + MySQL + SPA sendiri, deploy sendiri), BUKAN
modul EIP Core — dipertimbangkan eksplisit & ditolak. Beda dgn Kepegawaian:
data Perencanaan genuinely belum ada di mana pun, dan menggabungkan berisiko
bikin EIP Core jatuh ke masalah yg sama spt dikritik dari sistem Aset lama:
"jadi satu semua, terlalu besar, tidak fokus". Baca master pegawai/unit_kerja
dari EIP Core API (lewat shared library `eip/client`, belum dibangun).

**Lintas-domain** — BUKAN khusus aset. Menangani kebutuhan Aset (barang
modal) maupun Persediaan/BHP (barang habis pakai), plus **Pemeliharaan &
Perbaikan** (§4.4). Beda dari sistem Aset lama (`iams-fmipa-uns`) yang
menyatukan perencanaan+pengadaan+pencatatan jadi satu app — di arsitektur
baru, Perencanaan & Pengadaan lintas-domain, sementara Aset & Persediaan
(Logistik BHP) diperamping jadi HANYA pencatatan (lihat CLAUDE.md §1).

**Sumber requirement**: dibaca langsung kode nyata sistem Aset lama
(`/ai/projects/iams-fmipa-uns`) & Persediaan (`/ai/projects/logistik`) —
aturan bisnisnya sudah teruji pemakaian nyata & diambil sbg referensi
(BUKAN kodenya yg dipindah — lihat §7 alasan build-baru).

---

## 2. Prinsip desain: satu skema utk semua jenis kebutuhan

Requirement gathering awal (lihat `PROGRESS.md` 2026-09-05) menghasilkan
dua alur yg TERLIHAT beda — "Pengajuan Barang" dan "Permintaan Perbaikan"
— tapi keduanya ternyata **bentuknya identik**:

1. Diajukan bebas oleh unit (tanpa approval gate)
2. Dibatasi pagu (hard block, tidak boleh melebihi sisa pagu)
3. Diproses/dieksekusi (Pengadaan, bisa via vendor)
4. Harga riil bisa beda dari estimasi → disesuaikan oleh unit pengaju
5. Direalisasikan

Daripada bikin dua tabel & dua alur kode yg mirip-mirip (rawan duplikasi
& tambal-sulam kalau salah satu berubah, yg lain ketinggalan), **desain
ini SENGAJA menyatukan keduanya** jadi satu entitas `permintaan` dengan
kolom `jenis` (`pengadaan_barang` | `pemeliharaan`) sbg pembeda. Begitu
juga pagu: satu tabel `pagu` dengan kolom `jenis` yg sama, bukan dua
tabel `pagu_anggaran`+`pagu_pemeliharaan` terpisah.

**Keuntungan**: satu form, satu validasi, satu status machine, satu
halaman daftar (bisa difilter per jenis) — sesuai permintaan pengguna
"desain selengkap mungkin dan segampang mungkin". Kalau nanti muncul
jenis kebutuhan ketiga (misal "Jasa" atau "Sewa"), tinggal tambah value
enum `jenis`, TIDAK perlu tabel/kode baru.

---

## 3. Alur bisnis end-to-end (final, unified)

```
Unit ajukan kebutuhan (Permintaan, jenis: pengadaan_barang ATAU pemeliharaan)
        │  bebas, TANPA approval gate
        │  TAPI: hard block thd sisa pagu jenis yg sesuai (§5)
        ▼
[PERENCANAAN]  Permintaan terkumpul per unit/periode/kategori/jenis
        │  dikelompokkan per kategori → tentukan vendor yg relevan
        ▼
[PENGADAAN]  Eksekusi (wizard, draft per langkah — keputusan sblmnya)
        │  - pengadaan_barang: pilih vendor, beli
        │  - pemeliharaan: pilih vendor ATAU swakelola (bebas, tanpa
        │    ambang nilai — keputusan simplifikasi, lihat §8)
        │  harga/biaya riil didapat
        ▼
   Beda dari estimasi? ──ya──▶ status "Perlu Penyesuaian"
        │ tidak                     │
        │              Unit pengaju SENDIRI yang menyesuaikan
        │              jumlah/harga (tetap tunduk kontrol pagu §5)
        ▼                           │
   Direalisasikan ◀──────────────────┘
        │
        ▼
[ASET / PERSEDIAAN]  pengadaan_barang → dicatat sbg barang baru (registry/stok)
                      pemeliharaan → dicatat sbg riwayat perbaikan aset terkait
```

**Tidak ada approval gate** di tahap pengajuan — unit bebas mengajukan
apa saja. Satu-satunya kontrol adalah pagu (§5), bukan konten permintaan.

---

## 4. Skema data (final, siap-migrasi)

### 4.1 `kategori_kebutuhan` (master, admin-manageable)

Pola sama seperti `golongan_ruang`/`pendidikan` di EIP Core — tabel
kode+nama, bukan enum hardcode, supaya admin bisa tambah tanpa deploy.
**Milik Perencanaan** (keputusan final §8 — tidak perlu naik jadi master
lintas-domain EIP Core kecuali nanti terbukti dipakai modul lain).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `kode` | string, unique | |
| `nama` | string | Contoh awal: Alat Lab, Komputer, Mebeler (extensible) |
| `is_active` | boolean, default true | |
| timestamps + soft deletes | | |

Dipakai utk: (1) klasifikasi tiap `permintaan`, (2) routing vendor yg
relevan saat Pengadaan (satu taksonomi dipakai kedua jenis kebutuhan —
kategori "Alat Lab" bisa relevan baik utk beli baru maupun servis).

### 4.2 `periode_anggaran`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `nama` | string | Mis. "Tahun Anggaran 2027" |
| `tanggal_mulai`, `tanggal_selesai` | date | |
| `is_active` | boolean | Hanya 1 periode aktif pada satu waktu |
| timestamps + soft deletes | | |

### 4.3 Anggaran: global → alokasi prodi → sisa Fakultas (revisi 2026-09-06)

Model anggaran (menggantikan "pagu per unit termasuk Fakultas" di draft
awal): admin **tetapkan Anggaran Global lebih dulu** per (periode,
jenis) → **bagi ke tiap prodi** → **pagu Fakultas = Global − Σ alokasi
prodi**, DIHITUNG (tidak diinput). Alokasi prodi tidak boleh melebihi
global (hard block). Semua ledger — tiap penetapan/revisi = baris baru,
bisa direvisi tengah periode, "nilai saat ini" = baris terbaru by
`berlaku_sejak`.

**`anggaran_global`** — plafon total per (periode, jenis):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `periode_anggaran_id` | bigint FK | |
| `jenis` | enum: `pengadaan_barang`, `pemeliharaan` | Dua pool independen |
| `nominal` | decimal(15,2) | Global berlaku sejak baris ini |
| `berlaku_sejak` | date | |
| `ditetapkan_oleh` | bigint (user id) | Admin, tanpa alur approval |
| `keterangan` | text, nullable | Alasan revisi |
| timestamps + soft deletes | | |

**`pagu`** — alokasi ke unit NON-Fakultas (prodi, bagian):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `unit_kerja_id` | bigint | Referensi EIP Core (id disalin lokal). **BUKAN** unit Fakultas (id 1 = FMIPA, `config strap.unit_fakultas_id`) — itu dihitung |
| `periode_anggaran_id` | bigint FK | |
| `jenis` | enum | Dua pool independen |
| `nominal` | decimal(15,2) | Alokasi berlaku sejak baris ini |
| `berlaku_sejak` | date | |
| `ditetapkan_oleh` | bigint (user id) | |
| `keterangan` | text, nullable | |
| timestamps + soft deletes | | |

`PaguService::plafon($unit, $periode, $jenis)` = angka batas atas efektif:
Fakultas → `globalTerkini − Σ alokasi prodi`; unit lain → nominal alokasi
terkini. `sisa()` = `plafon − terpakai`.

Index: `(unit_kerja_id, periode_anggaran_id, jenis, berlaku_sejak)` —
"pagu terkini" = `ORDER BY berlaku_sejak DESC LIMIT 1` per kombinasi.

### 4.4 `permintaan` (UNIFIED — pengganti "Pengajuan Barang" + "Laporan Kerusakan/Permintaan Perbaikan")

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `unit_kerja_id` | bigint | Unit pengaju |
| `periode_anggaran_id` | bigint FK | |
| `jenis` | enum: `pengadaan_barang`, `pemeliharaan` | Menentukan pool pagu mana yg dicek (§5) |
| `kategori_kebutuhan_id` | bigint FK | |
| `diajukan_oleh` | bigint (pegawai id, dari EIP Core) | **Siapa saja pegawai di unit boleh mengajukan** (dikonfirmasi, khusus jenis pemeliharaan — berlaku sama utk konsistensi) |
| `nama_kebutuhan` | string | Nama barang, ATAU ringkasan kerusakan/kebutuhan servis |
| `deskripsi` | text, nullable | Detail tambahan/deskripsi kerusakan |
| `aset_terkait_id` | bigint, nullable | Hanya relevan `jenis=pemeliharaan` — referensi ke Aset yg mau diperbaiki (via API ke sistem Aset, nullable krn aset kadang belum tercatat resmi) |
| `foto` | string (path), nullable | Bukti kondisi/kerusakan, opsional |
| `jumlah` | integer, default 1 | |
| `estimasi_harga_satuan` | decimal(15,2) | |
| `estimasi_total` | decimal(15,2) | `jumlah × estimasi_harga_satuan`, disimpan (bukan dihitung on-the-fly) supaya histori "rencana awal" tidak berubah kalau harga satuan diedit |
| `status` | enum (lihat §6) | |
| `vendor_id` | bigint, nullable | Diisi di tahap Pengadaan (nullable krn Perencanaan tak "punya" vendor — §8) — nullable jg permanen kalau `jenis=pemeliharaan` dieksekusi swakelola |
| `harga_realisasi_satuan`, `total_realisasi` | decimal(15,2), nullable | Diisi setelah harga riil didapat |
| timestamps + soft deletes | | |

**Kolom referensi data lama** (utk ETL nanti, pola `id_sumber` spt SIMPEG):
tidak relevan di sini krn `permintaan` adalah data BARU (tak ada padanan
historis di sistem lama utk diimpor) — beda dgn Aset/Persediaan yg akan
diimpor datanya (lihat §7).

**Nilai "terpakai" dari pagu** per baris = `COALESCE(total_realisasi,
estimasi_total)` — selalu pakai angka paling akurat yg diketahui saat itu.

---

## 5. Aturan kontrol pagu (KUNCI — hard constraint)

**Dikonfirmasi eksplisit oleh pengguna**: "Kontrol pagu otomatis,
pengajuan tidak bisa melebihi pagu. Kalau pagu habis sudah tidak bisa
menambah barang." Berlaku sama utk kedua `jenis`.

- Validasi **real-time, server-side**, saat create/update `permintaan`:
  ```
  SUM(nilai_terpakai) semua permintaan aktif (blm dibatalkan)
    milik (unit_kerja, periode, jenis yg sama) TERMASUK yg baru/diubah
    ≤ pagu terkini (unit_kerja, periode, jenis)
  ```
  Gagal validasi → **ditolak** (bukan cuma peringatan), pesan tampilkan
  sisa pagu yg masih ada supaya user tahu batasnya.
- Berlaku juga saat **penyesuaian** pasca-harga-riil — kalau harga riil
  naik dan bikin melebihi pagu, unit pengaju harus mengurangi jumlah
  atau membatalkan sebagian supaya kembali pas.
- **Tanpa buffer/toleransi** (keputusan simplifikasi, §8) — validasi
  ketat pakai angka apa adanya. Toleransi terhadap harga yg meleset
  sudah tertangani lewat mekanisme "penyesuaian" itu sendiri, tak perlu
  buffer % tambahan yg bikin aturan lebih rumit.

---

## 6. Status `permintaan` (state machine)

```
diajukan ──▶ dalam_proses ──▶ perlu_penyesuaian ──▶ disesuaikan ──▶ direalisasikan
   │                                                                      ▲
   └──▶ dibatalkan                                          (siklus ulang jika
                                                              masih ada gap harga)
```

- `diajukan`: baru dibuat unit, **bisa diedit bebas** oleh pengaju.
- `dalam_proses`: sudah diambil masuk siklus Pengadaan (vendor
  dipilih/proses jalan), TIDAK bisa diedit langsung oleh unit lagi.
- `perlu_penyesuaian`: harga riil beda dari estimasi → unit pengaju
  diberi notifikasi (§8 — email/in-app utk v1) & masuk lagi mengubah
  jumlah/harga.
- `disesuaikan`: unit sudah menyesuaikan, siap dieksekusi ulang/lanjut.
- `direalisasikan`: selesai, barang/perbaikan sudah didapat — trigger
  pencatatan ke Aset (barang baru) atau riwayat perbaikan (pemeliharaan).
- `dibatalkan`: unit atau admin membatalkan sebelum realisasi (mis. utk
  menyesuaikan diri ke sisa pagu).

---

## 7. Keterkaitan dengan Pengadaan (app terpisah lain)

- **Kategori kebutuhan** dipakai Pengadaan utk mengelompokkan
  `permintaan` ke vendor yg sesuai (tiap vendor biasa spesialis
  kategori tertentu) — dibaca via API dari Perencanaan.
- **Vendor MILIK Pengadaan** (keputusan final, §8) — Perencanaan tidak
  menyimpan data vendor sendiri, cuma `vendor_id` (nullable) di
  `permintaan` yg diisi balik dari Pengadaan setelah vendor dipilih.
- Utk `jenis=pemeliharaan`: vendor OPSIONAL (swakelola diperbolehkan
  bebas, tanpa ambang nilai — keputusan simplifikasi §8).
- **UI Pengadaan**: wizard step-by-step dgn draft per langkah (keputusan
  sesi sebelumnya).
- Integrasi antar app: API + event/webhook (pola CLAUDE.md §3.4), BUKAN
  FK lintas-DB — kontrak API detail dirancang saat mulai app Pengadaan.

---

## 8. Keputusan final atas hal yang sebelumnya terbuka

| Pertanyaan (draft v0.1) | Keputusan final |
|---|---|
| Kepemilikan `kategori_kebutuhan` | **Perencanaan.** Tidak perlu naik ke EIP Core sampai terbukti dipakai modul lain (YAGNI). |
| Kepemilikan `vendor` | **Pengadaan.** Perencanaan cuma simpan `vendor_id` nullable. |
| Toleransi/buffer validasi pagu | **Tidak ada buffer.** Ketat apa adanya — mekanisme penyesuaian sudah menangani harga yg meleset, buffer tambahan cuma menambah kerumitan aturan tanpa manfaat jelas. |
| Notifikasi "Perlu Penyesuaian" | **v1: email + notifikasi in-app** (Laravel Notification standar). Integrasi wa-blast DITUNDA sbg enhancement lanjutan — Perencanaan belum py jalur ke wa-blast sama sekali, bikin baru sekarang menambah scope sblm app-nya sendiri jalan. |
| Anggaran genuinely milik Perencanaan? | **Ya, sepenuhnya** — termasuk `jenis=pemeliharaan`. Satu app pemilik seluruh siklus anggaran-kebutuhan lintas-domain. |
| Skema DB detail | **Selesai — lihat §4.** Siap jadi migration. |
| Pemeliharaan: ambang nilai wajib-vendor vs swakelola | **Tidak ada ambang.** Vendor opsional bebas dipilih atau tidak (swakelola) — keputusan simplifikasi, bisa ditambah aturan nanti kalau ternyata dibutuhkan setelah dipakai nyata. |
| Laporan kerusakan: tahap formal terpisah atau langsung? | **Digabung jadi SATU langkah** — `permintaan` dgn `jenis=pemeliharaan` BERFUNGSI SEKALIGUS sbg laporan kerusakan (status `diajukan` = "baru dilaporkan"). Tidak ada tabel/tahap terpisah `laporan_kerusakan` lagi (disatukan sesuai prinsip §2). |

---

## 9. Kenapa build baru, bukan pindah dari sistem Aset lama

Dibaca langsung kode `iams-fmipa-uns`: modul "Pengadaan" di situ
(`ProcurementBatchController` + `RealizationController`) menyatu erat
dgn pembuatan `Asset` (`finalize()` langsung insert baris `Asset`, satu
DB/app yg sama) — memisahkannya jadi app independen butuh bongkar-pasang
setara nulis ulang, sementara sistemnya aktif dipakai produksi. **Tidak
ada modul "Perencanaan" berdiri sendiri** di sana yg bisa diangkat —
konsepnya tersebar di "Pengajuan Aset" (request bottom-up, ternyata belum
pernah dipakai krn belum disosialisasikan, bukan salah desain) & "Anggaran"
(pagu Fakultas→Prodi, model 2-lapis kaku — dirombak di rancangan ini jadi
lebih fleksibel & lebih sederhana: satu tabel `pagu` per unit/jenis).

Aturan bisnis yg **diambil sbg referensi** (bukan kode): vendor wajib di
level header pengadaan (bukan per-item), BAST berbasis unit (bisa gabung
aset dari beberapa pengadaan), kode aset otomatis+QR.

**Data lama bisa diimpor nanti** (dikonfirmasi pengguna) — begitu Fase
5/6 (refactor Aset/Persediaan) tiba, skema pencatatan Aset/Persediaan
(BUKAN skema Perencanaan ini — `permintaan` adalah data baru tanpa
padanan lama) menyiapkan kolom referensi ke ID sistem lama (pola
`id_sumber` spt migrasi SIMPEG), supaya ETL data lama tertelusuri.

---

## 10. Sengaja di luar scope v1 (jaga tetap sederhana)

- Integrasi notifikasi via wa-blast (pakai email/in-app dulu, §8).
- Ambang nilai/aturan kapan wajib vendor vs swakelola (§8).
- Approval berjenjang di tahap Pengajuan (memang sengaja tidak ada,
  dikonfirmasi pengguna — kontrol cukup lewat pagu).
- Kategori kebutuhan hierarkis (parent-child spt `AssetCategory` di
  sistem lama) — mulai dari daftar flat dulu (Alat Lab, Komputer,
  Mebeler), naikkan ke hierarki hanya kalau nyatanya dibutuhkan.

---

## 11. Status & langkah berikut

Desain selesai (2026-09-05). **Scaffold dimulai (2026-09-05, sesi sama)**
di `/ai/projects/perencanaan` (repo Git terpisah, sibling `eip`/`wa-blast`,
BUKAN subfolder di sini): migrasi §4 lengkap, `EipClient` (baca-saja,
bukan composer package terpisah — pola pragmatis sama spt wa-blast),
`PaguService` (implementasi §5), auth Google OIDC + RBAC. 20 test lulus.
Detail lengkap: `PROGRESS.md` di sini + `CLAUDE.md` proyek `perencanaan`.

**Belum ada**: controller/view CRUD (Permintaan/Pagu/Kategori Kebutuhan),
hosting/domain, GitHub remote. Ini langkah lanjutan berikutnya.
