@extends('layouts.app')

@section('title', 'Profil Saya')
@section('subtitle', 'Kelola data akun, foto profil, kata sandi, dan sesi')

@section('content')
<div class="profil-grid">
    <div class="profil-side">
        <div class="card card-profil">
            <div class="card-body center">
                <div class="profil-avatar-wrap">
                    <x-avatar :user="$user" size="xl"/>
                    <form method="POST" action="{{ route('profil.avatar') }}" enctype="multipart/form-data" class="avatar-form">
                        @csrf
                        <label class="avatar-edit" title="Ganti foto profil">
                            <x-icon name="upload"/>
                            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" hidden data-avatar-input>
                        </label>
                    </form>
                </div>
                <h2>{{ $user->nama }}</h2>
                <p class="muted">{{ $user->email }}</p>
                @if ($user->google_id)
                    <span class="badge badge-google">
                        <svg class="icon" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                        Terhubung dengan Google
                    </span>
                @endif
                <dl class="meta-grid">
                    <div><dt>Bergabung sejak</dt><dd>{{ format_tanggal($user->created_at) }}</dd></div>
                    <div><dt>Workspace diikuti</dt><dd>{{ $jumlahWorkspace }}</dd></div>
                    <div><dt>Workspace dipimpin</dt><dd>{{ $jumlahDipimpin }}</dd></div>
                    <div><dt>Tugas ditanggung</dt><dd>{{ $jumlahTugas }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-block"><x-icon name="dashboard"/> Kembali ke Dashboard</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-8">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-block"><x-icon name="logout"/> Keluar dari Akun</button>
                </form>
                <button type="button" class="btn btn-danger-outline btn-block mt-8" data-modal-open="modalHapusAkun">
                    <x-icon name="trash"/> Hapus Akun Permanen
                </button>
            </div>
        </div>
    </div>

    <div class="profil-main">
        <div class="card">
            <div class="card-head">
                <h3><x-icon name="user"/> Data Akun</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profil.update') }}" class="stack-form">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="p_nama">Nama Lengkap</label>
                        <input type="text" id="p_nama" name="nama" required maxlength="100" value="{{ old('nama', $user->nama) }}">
                        @error('nama')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="p_email">Email</label>
                        <input type="email" id="p_email" name="email" required maxlength="100" value="{{ old('email', $user->email) }}">
                        <p class="field-hint">Email digunakan untuk login dan penerima pengingat deadline.</p>
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-actions-right">
                        <button type="submit" class="btn btn-primary"><x-icon name="check"/> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><x-icon name="lock"/> Kata Sandi</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profil.password') }}" class="stack-form">
                    @csrf
                    @method('PATCH')
                    @if ($user->password)
                        <div class="form-group">
                            <label for="s_lama">Kata Sandi Saat Ini</label>
                            <input type="password" id="s_lama" name="kata_sandi_saat_ini" required placeholder="••••••••">
                            @error('kata_sandi_saat_ini')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <p class="modal-note">Akun Anda dibuat melalui Google dan belum memiliki kata sandi lokal. Atur kata sandi agar bisa masuk tanpa Google.</p>
                    @endif
                    <div class="form-row">
                        <div class="form-group">
                            <label for="s_baru">Kata Sandi Baru</label>
                            <input type="password" id="s_baru" name="kata_sandi_baru" required minlength="8" placeholder="Minimal 8 karakter">
                            @error('kata_sandi_baru')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="s_konfirmasi">Ulangi Kata Sandi Baru</label>
                            <input type="password" id="s_konfirmasi" name="kata_sandi_baru_confirmation" required minlength="8" placeholder="Ulangi kata sandi">
                        </div>
                    </div>
                    <div class="form-actions-right">
                        <button type="submit" class="btn btn-primary"><x-icon name="check"/> Perbarui Kata Sandi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Hapus Akun -->
<div class="modal-overlay" id="modalHapusAkun" hidden>
    <div class="modal modal-sm modal-danger" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="alert"/> Hapus Akun Permanen?</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('profil.destroy') }}">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Tindakan ini <b>tidak dapat dibatalkan</b>. Akun Anda akan dihapus, dan:</p>
                <ul class="modal-list">
                    <li>Workspace yang Anda pimpin akan <b>dipindahkan</b> ke anggota paling lama bergabung (atau dihapus bila kosong).</li>
                    <li>Anda akan dikeluarkan dari seluruh workspace dan kehilangan akses ke tugas &amp; berkas.</li>
                    <li>Pesan chat Anda tetap tersimpan dengan label “Akun terhapus”.</li>
                </ul>

                @if ($user->password)
                    <div class="form-group">
                        <label for="h_sandi">Konfirmasi Kata Sandi <b>*</b></label>
                        <input type="password" id="h_sandi" name="kata_sandi" required placeholder="Masukkan kata sandi Anda">
                        @error('kata_sandi')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="form-group">
                        <label for="h_teks">Ketik <b>HAPUS</b> untuk konfirmasi <b>*</b></label>
                        <input type="text" id="h_teks" name="konfirmasi_teks" required placeholder="HAPUS">
                        @error('konfirmasi_teks')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-danger"><x-icon name="trash"/> Hapus Akun Saya</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector('[data-avatar-input]');
        if (input) {
            input.addEventListener('change', function () {
                if (input.files.length > 0) input.closest('form').submit();
            });
        }
    });
</script>
@endpush
