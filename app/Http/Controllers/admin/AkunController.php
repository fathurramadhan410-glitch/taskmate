<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AkunController extends Controller
{
    public function index()
    {
        $q = request('q');

        $users = User::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->get();

        $anggotaMap = $this->petaAnggota();

        return view('admin.akun', compact('users', 'anggotaMap', 'q'));
    }

    public function terblokir()
    {
        $users = User::where('status', 'diblokir')
            ->orderByDesc('diblokir_pada')
            ->get();

        $anggotaMap = $this->petaAnggota();

        return view('admin.terblokir', compact('users', 'anggotaMap'));
    }

    public function create()
    {
        return view('admin.tambah');
    }

    public function store()
    {
        $data = request()->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:mahasiswa,admin'],
        ]);

        $user = new User();
        $user->name        = $data['name'];
        $user->email       = $data['email'];
        $user->password    = bcrypt($data['password']);
        $user->role        = $data['role'];
        $user->status      = 'aktif';
        $user->gagal_login = 0;
        $user->save();

        return redirect()->route('admin.akun')
            ->with('success', 'Akun ' . $user->email . ' berhasil dibuat.');
    }

    public function blokir(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kamu tidak bisa memblokir akunmu sendiri.');
        }

        if ($user->role === 'admin') {
            return back()->with('error', 'Akun admin tidak bisa diblokir.');
        }

        $user->forceFill([
            'status'        => 'diblokir',
            'diblokir_pada' => now(),
            'alasan_blokir' => 'Diblokir oleh admin',
        ])->save();

        return back()->with('success', 'Akun ' . $user->email . ' telah diblokir.');
    }

    public function buka(User $user)
    {
        $user->forceFill([
            'status'        => 'aktif',
            'gagal_login'   => 0,
            'diblokir_pada' => null,
            'alasan_blokir' => null,
        ])->save();

        return back()->with('success', 'Blokir akun ' . $user->email . ' sudah dibuka.');
    }

    /**
     * Membaca keanggotaan workspace tiap user (beserta perannya:
     * Project Manager / Anggota) langsung dari database,
     * dengan deteksi otomatis nama tabel & kolom agar cocok
     * dengan struktur database project ini.
     */
    private function petaAnggota(): array
    {
        // 1) Cari tabel pivot keanggotaan workspace
        $pivot = null;
        foreach (['workspace_user', 'workspace_users', 'workspace_members', 'anggota_workspace', 'members'] as $tbl) {
            if (
                Schema::hasTable($tbl)
                && Schema::hasColumn($tbl, 'user_id')
                && Schema::hasColumn($tbl, 'workspace_id')
            ) {
                $pivot = $tbl;
                break;
            }
        }

        if (! $pivot) {
            return [];
        }

        // 2) Cari kolom peran di pivot (kalau ada)
        $adaRole = null;
        foreach (['role', 'peran', 'jabatan'] as $col) {
            if (Schema::hasColumn($pivot, $col)) {
                $adaRole = $col;
                break;
            }
        }

        // 3) Nama kolom nama workspace
        $kolomNama = Schema::hasColumn('workspaces', 'nama') ? 'nama' : 'name';

        // 4) Susun query
        $query = DB::table($pivot)
            ->join('workspaces', 'workspaces.id', '=', $pivot . '.workspace_id')
            ->select(
                $pivot . '.user_id as user_id',
                'workspaces.' . $kolomNama . ' as nama_workspace'
            );

        if ($adaRole) {
            $query->addSelect($pivot . '.' . $adaRole . ' as peran_pivot');
        }

        if (Schema::hasColumn('workspaces', 'user_id')) {
            $query->addSelect('workspaces.user_id as pemilik_id');
        }

        // 5) Petakan ke format [user_id => [ ['nama'=>..,'peran'=>..], ... ]]
        $map = [];
        foreach ($query->get() as $row) {
            $peran = 'Anggota';

            if (isset($row->peran_pivot) && $row->peran_pivot) {
                $p = strtolower((string) $row->peran_pivot);
                if (
                    str_contains($p, 'pm') || str_contains($p, 'project')
                    || str_contains($p, 'manager') || str_contains($p, 'ketua')
                    || str_contains($p, 'owner') || str_contains($p, 'pemilik')
                ) {
                    $peran = 'Project Manager';
                }
            } elseif (isset($row->pemilik_id) && $row->pemilik_id == $row->user_id) {
                // Fallback: pembuat workspace dianggap Project Manager
                $peran = 'Project Manager';
            }

            $map[$row->user_id][] = [
                'nama'  => $row->nama_workspace,
                'peran' => $peran,
            ];
        }

        return $map;
    }
}