<?php
namespace Database\Seeders;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Assignment;
use App\Models\Intervention;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::create([
            'name'               => 'admin',
            'email'              => 'admin@gmail.com',
            'email_verified_at'  => now(),
            'password'           => Hash::make('123456789'),
        ]);

        $striker = User::create([
            'name'               => 'Striker',
            'email'              => 'striker@gmail.com',
            'email_verified_at'  => now(),
            'password'           => Hash::make('123456789'),
        ]);

        $walid = User::create([
            'name'               => 'Walid',
            'email'              => 'walid@gmail.com',
            'email_verified_at'  => now(),
            'password'           => Hash::make('123456789'),
        ]);

        // --- ROLES ---
        $roles = collect([
            Role::create(['name' => 'admin',       'guard_name' => 'web']),
            Role::create(['name' => 'leader',      'guard_name' => 'web']),
            Role::create(['name' => 'dispatcher',  'guard_name' => 'web']),
	    Role::create(['name' => 'technician',  'guard_name' => 'web']),
	    Role::create(['name' => 'employee',  'guard_name' => 'web']),
        ]);

        $admin->assignRole($roles->firstWhere('name', 'admin'));

        $techRole = $roles->firstWhere('name', 'technician');
        $striker->assignRole($techRole);
        $walid->assignRole($techRole);
    }
}
