<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/manager/technicians  (existing — manager assigns techs)
    |--------------------------------------------------------------------------
    */
    public function technicians()
    {
        $users = User::role('technician')->get(['id', 'name', 'email']);
        return response()->json($users);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/users  — list all users with their roles
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $users = User::with('roles')->orderBy('created_at', 'desc')->get()
            ->map(fn($u) => $this->format($u));

        return response()->json($users);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/roles  — list all available roles
    |--------------------------------------------------------------------------
    */
    public function roles()
    {
        return response()->json(Role::orderBy('name')->pluck('name'));
    }

    /*
    |--------------------------------------------------------------------------
    | PATCH /api/admin/users/{id}/role  — assign a single role to a user
    |--------------------------------------------------------------------------
    */
    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($id);

        // Prevent admin from demoting themselves
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot change your own role.'], 403);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => "Role updated to \"{$request->role}\" for {$user->name}.",
            'user'    => $this->format($user->fresh('roles')),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/admin/users/{id}  — delete a user
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/admin/users  — create a user and assign role immediately
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'email_verified_at' => now(), // admin-created users skip verification
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $this->format($user->fresh('roles')),
        ], 201);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function format(User $u): array
    {
        return [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'roles'      => $u->roles->pluck('name'),
            'role'       => $u->roles->first()?->name ?? 'none',
            'verified'   => (bool) $u->email_verified_at,
            'created_at' => $u->created_at?->toDateString(),
        ];
    }
}
