@php
    $v = fn (string $field, $default = '') => old($field, isset($pegawai) ? ($pegawai->{$field} instanceof \BackedEnum ? $pegawai->{$field}->value : $pegawai->{$field}) : $default);
    $inputCls = 'w-full text-xs bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 rounded-xl px-3 py-2.5 transition-all';
    $labelCls = 'block text-[11px] font-semibold text-slate-500 mb-1.5';
@endphp

@if ($errors->any())
    <div class="apple-glass-card rounded-2xl p-4 border border-rose-200/60 bg-rose-50/60">
        <p class="text-xs font-bold text-rose-600 mb-1">Periksa kembali isian:</p>
        <ul class="text-xs text-rose-600 list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="apple-glass-card rounded-3xl p-6">
    <h2 class="text-sm font-bold text-slate-900 mb-4">Identitas</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <label class="{{ $labelCls }}">Nama Lengkap *</label>
            <input type="text" name="nama_lengkap" value="{{ $v('nama_lengkap') }}" required class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="{{ $inputCls }}">
                <option value="">—</option>
                <option value="L" @selected($v('jenis_kelamin') === 'L')>Laki-laki</option>
                <option value="P" @selected($v('jenis_kelamin') === 'P')>Perempuan</option>
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Gelar Depan</label>
            <input type="text" name="gelar_depan" value="{{ $v('gelar_depan') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">Gelar Belakang</label>
            <input type="text" name="gelar_belakang" value="{{ $v('gelar_belakang') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">Tempat Lahir</label>
            <input type="text" name="tempat_lahir" value="{{ $v('tempat_lahir') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" value="{{ $v('tanggal_lahir') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">Agama</label>
            <select name="agama" class="{{ $inputCls }}">
                <option value="">—</option>
                @foreach (['islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik', 'hindu' => 'Hindu', 'buddha' => 'Buddha', 'konghucu' => 'Konghucu'] as $val => $label)
                    <option value="{{ $val }}" @selected($v('agama') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Status Perkawinan</label>
            <select name="status_perkawinan" class="{{ $inputCls }}">
                <option value="">—</option>
                @foreach (['belum_kawin' => 'Belum Kawin', 'kawin' => 'Kawin', 'cerai_hidup' => 'Cerai Hidup', 'cerai_mati' => 'Cerai Mati'] as $val => $label)
                    <option value="{{ $val }}" @selected($v('status_perkawinan') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="apple-glass-card rounded-3xl p-6">
    <h2 class="text-sm font-bold text-slate-900 mb-4">Identitas Resmi</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelCls }}">NIP</label>
            <input type="text" name="nip" value="{{ $v('nip') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">NIK</label>
            <input type="text" name="nik" value="{{ $v('nik') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">NPWP</label>
            <input type="text" name="npwp" value="{{ $v('npwp') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">NUPTK</label>
            <input type="text" name="nuptk" value="{{ $v('nuptk') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">ID SIMPEG</label>
            <input type="text" name="id_simpeg" value="{{ $v('id_simpeg') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">No. Seri Karpeg</label>
            <input type="text" name="no_seri_kepeg" value="{{ $v('no_seri_kepeg') }}" class="{{ $inputCls }} font-mono-num">
        </div>
    </div>
</div>

<div class="apple-glass-card rounded-3xl p-6">
    <h2 class="text-sm font-bold text-slate-900 mb-4">Kepegawaian</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelCls }}">Status Kepegawaian</label>
            <select name="status_kepegawaian_id" class="{{ $inputCls }}">
                <option value="">—</option>
                @foreach ($statusKepegawaianOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((string) $v('status_kepegawaian_id') === (string) $opt->id)>{{ $opt->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Jenis Pegawai</label>
            <select name="jenis_pegawai" class="{{ $inputCls }}">
                <option value="">—</option>
                <option value="tenaga_pendidik" @selected($v('jenis_pegawai') === 'tenaga_pendidik')>Tenaga Pendidik (Dosen)</option>
                <option value="tenaga_kependidikan" @selected($v('jenis_pegawai') === 'tenaga_kependidikan')>Tenaga Kependidikan</option>
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Pendidikan Terakhir</label>
            <select name="pendidikan_terakhir_id" class="{{ $inputCls }}">
                <option value="">—</option>
                @foreach ($pendidikanOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((string) $v('pendidikan_terakhir_id') === (string) $opt->id)>{{ $opt->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Golongan/Ruang</label>
            <select name="golongan_ruang_id" class="{{ $inputCls }}">
                <option value="">—</option>
                @foreach ($golonganOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((string) $v('golongan_ruang_id') === (string) $opt->id)>{{ $opt->kode }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">TMT Golongan</label>
            <input type="date" name="tmt_golongan" value="{{ $v('tmt_golongan') }}" class="{{ $inputCls }}">
        </div>
        <div></div>
        <div>
            <label class="{{ $labelCls }}">Tanggal Masuk *</label>
            <input type="date" name="tanggal_masuk" value="{{ $v('tanggal_masuk') }}" required class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">TMT CPNS</label>
            <input type="date" name="tmt_cpns" value="{{ $v('tmt_cpns') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">TMT PNS</label>
            <input type="date" name="tmt_pns" value="{{ $v('tmt_pns') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">Tanggal Keluar</label>
            <input type="date" name="tanggal_keluar" value="{{ $v('tanggal_keluar') }}" class="{{ $inputCls }}">
        </div>
        <div class="flex items-center gap-2 pt-6">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked($v('is_active', true)) class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <label for="is_active" class="text-xs font-semibold text-slate-600">Pegawai aktif</label>
        </div>
    </div>
</div>

<div class="apple-glass-card rounded-3xl p-6">
    <h2 class="text-sm font-bold text-slate-900 mb-4">Kontak</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelCls }}">Email</label>
            <input type="email" name="email" value="{{ $v('email') }}" class="{{ $inputCls }}">
        </div>
        <div>
            <label class="{{ $labelCls }}">No. HP</label>
            <input type="text" name="no_hp" value="{{ $v('no_hp') }}" class="{{ $inputCls }}">
        </div>
        <div class="lg:col-span-1">
            <label class="{{ $labelCls }}">Alamat Domisili</label>
            <input type="text" name="alamat_domisili" value="{{ $v('alamat_domisili') }}" class="{{ $inputCls }}">
        </div>
    </div>
</div>

<div class="apple-glass-card rounded-3xl p-6">
    <h2 class="text-sm font-bold text-slate-900 mb-4">Asuransi &amp; Pensiun</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="{{ $labelCls }}">No. BPJS Kesehatan</label>
            <input type="text" name="no_bpjs_kesehatan" value="{{ $v('no_bpjs_kesehatan') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">No. BPJS Ketenagakerjaan</label>
            <input type="text" name="no_bpjs_ketenagakerjaan" value="{{ $v('no_bpjs_ketenagakerjaan') }}" class="{{ $inputCls }} font-mono-num">
        </div>
        <div>
            <label class="{{ $labelCls }}">No. Taspen</label>
            <input type="text" name="no_taspen" value="{{ $v('no_taspen') }}" class="{{ $inputCls }} font-mono-num">
        </div>
    </div>
</div>
