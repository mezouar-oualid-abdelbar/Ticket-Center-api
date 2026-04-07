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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- USERS ---
$users = User::factory(10)->create();

// Create admin user
$admin = User::create([
    'name' => 'admin',
    'email' => 'admin@gmail.com',
    'email_verified_at' => now(),
    'password' => Hash::make('123456789'),
]);

// --- ROLES ---
$roles = collect([
    Role::create(['name' => 'admin', 'guard_name' => 'web']),
    Role::create(['name' => 'leader', 'guard_name' => 'web']),
    Role::create(['name' => 'dispatcher', 'guard_name' => 'web']),
]);

// ✅ Assign ADMIN role to admin user
$adminRole = $roles->firstWhere('name', 'admin');
$admin->assignRole($adminRole);

        $permissions = collect([
            Permission::create(['name' => 'create_ticket', 'guard_name' => 'web']),
            Permission::create(['name' => 'update_ticket', 'guard_name' => 'web']),
            Permission::create(['name' => 'delete_ticket', 'guard_name' => 'web']),
            Permission::create(['name' => 'assign_ticket', 'guard_name' => 'web']),
            Permission::create(['name' => 'close_ticket', 'guard_name' => 'web']),
        ]);

        // Attach permissions to roles randomly
        foreach ($roles as $role) {
            $role->syncPermissions($permissions->random(2));
        }

        // Attach roles to users randomly
        foreach ($users as $user) {
            $user->assignRole($roles->random());
        }

        // --- TICKETS ---
        $tickets = Ticket::factory(15)->create([
            'reporter_id' => $users->random()->id
        ]);

        // --- ASSIGNMENTS ---
        $assignments = Assignment::factory(10)->create([
            'ticket_id' => $tickets->random()->id,
            'leader_id' => $users->random()->id,
            'dispatcher_id' => $users->random()->id,
        ]);

        // --- ASSIGNMENT_USER (pivot) ---
        foreach ($assignments as $assignment) {
            $randomUsers = $users->random(3);
            foreach ($randomUsers as $user) {
                DB::table('assignment_user')->insert([
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                ]);
            }
        }

        // --- INTERVENTIONS ---
        foreach ($assignments as $assignment) {
            Intervention::factory(2)->create([
                'ticket_id' => $assignment->ticket_id,
                'leader_id' => $assignment->leader_id,
                'appointment' => now()->addDays(rand(0, 5)),
            ]);
        }

        // --- MESSAGES ---
        foreach ($tickets as $ticket) {
            Message::factory(3)->create([
                'ticket_id' => $ticket->id,
                'sender_id' => $users->random()->id,
            ]);
        }

        // --- PERSONAL ACCESS TOKENS ---
        foreach ($users as $user) {
            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'Test Token ' . Str::random(5),
                'token' => Str::random(64),
                'abilities' => '["*"]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- CACHE ---
        DB::table('cache')->insert([
            ['key' => 'site_name', 'value' => 'TestApp', 'expiration' => time() + 3600],
            ['key' => 'maintenance', 'value' => '0', 'expiration' => time() + 3600],
        ]);

        // --- CACHE LOCKS ---
        DB::table('cache_locks')->insert([
            ['key' => 'lock_1', 'owner' => 'Seeder', 'expiration' => time() + 600],
        ]);

        // --- SESSIONS ---
        foreach ($users as $user) {
            DB::table('sessions')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SeederAgent',
                'payload' => '{}',
                'last_activity' => time(),
            ]);
        }

        // --- JOB BATCHES ---
        $batchId = (string) Str::uuid();
        DB::table('job_batches')->insert([
            'id' => $batchId,
            'name' => 'Batch 1',
            'total_jobs' => 5,
            'pending_jobs' => 5,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'options' => '{}',
            'cancelled_at' => null,
            'created_at' => time(),
            'finished_at' => null,
        ]);

        // --- JOBS ---
        for ($i = 0; $i < 5; $i++) {
            DB::table('jobs')->insert([
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'available_at' => time(),
                'created_at' => time(),
            ]);
        }

        // --- FAILED JOBS ---
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Seeder Exception',
            'failed_at' => now(),
        ]);

        $this->command->info('✅ Database seeded with full test data!');
    }
}
