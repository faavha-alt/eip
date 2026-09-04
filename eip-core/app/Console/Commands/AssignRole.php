<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * "Role manual per akun" (docs/03) — belum ada UI admin, jadi lewat CLI.
 * User HARUS sudah pernah login Google sekali (baru tercipta di `users`)
 * sebelum diberi role.
 */
class AssignRole extends Command
{
    protected $signature = 'role:assign {email} {role : kode role, mis. admin-gaji}
                            {--create-role : buat role-nya dulu kalau belum ada}';

    protected $description = 'Tetapkan role ke akun user (harus sudah pernah login Google)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User tidak ditemukan — pastikan sudah pernah login Google minimal sekali.');

            return self::FAILURE;
        }

        $kode = $this->argument('role');
        $role = Role::where('kode', $kode)->first();

        if (! $role && $this->option('create-role')) {
            $role = Role::create(['kode' => $kode, 'nama' => $kode, 'is_active' => true]);
            $this->info("Role [{$kode}] dibuat.");
        }

        if (! $role) {
            $this->error("Role [{$kode}] tidak ada. Pakai --create-role utk membuatnya sekalian.");

            return self::FAILURE;
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $this->info("Role [{$kode}] ditetapkan ke {$user->email}.");

        return self::SUCCESS;
    }
}
