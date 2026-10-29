<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class UserRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.access');
    }

    /**
     * Display a listing of users with their roles.
     */
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);

        return view('admin.user-roles.index', compact('users'));
    }

    /**
     * Show the form for editing user roles.
     */
    public function edit(User $user)
    {
        $roles = Role::active()->orderBy('name')->get();
        $user->load('roles');

        return view('admin.user-roles.edit', compact('user', 'roles'));
    }

    /**
     * Update user roles.
     */
    public function update(UpdateUserRolesRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('admin.user-roles.index')
            ->with('success', "Roles do usuário {$user->name} atualizados com sucesso!");
    }

    /**
     * Show user details with roles and permissions.
     */
    public function show(User $user)
    {
        $user->load('roles.permissions');

        return view('admin.user-roles.show', compact('user'));
    }

    /**
     * Assign a role to user
     */
    public function assignRole(ChangeUserRoleRequest $request, User $user)
    {
        $validated = $request->validated();

        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);

        return redirect()->back()
            ->with('success', "Role '{$role->name}' atribuído ao usuário {$user->name}!");
    }

    /**
     * Remove a role from user
     */
    public function removeRole(ChangeUserRoleRequest $request, User $user)
    {
        $validated = $request->validated();

        $role = Role::findOrFail($validated['role_id']);
        $user->removeRole($role);

        return redirect()->back()
            ->with('success', "Role '{$role->name}' removido do usuário {$user->name}!");
    }

    /**
     * Desativa o usuário: não entra mais (login, sessão e API), mas os dados ficam guardados.
     */
    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('admin.users.deactivate', $user);

        $user->forceFill([
            'deactivated_at' => now(),
            // Invalida o cookie "lembrar de mim" já emitido
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();

        return redirect()->back()
            ->with('success', "Usuário {$user->name} desativado. Os dados dele foram mantidos.");
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('admin.users.deactivate', $user);

        $user->forceFill(['deactivated_at' => null])->save();

        return redirect()->back()
            ->with('success', "Usuário {$user->name} reativado.");
    }

    /**
     * Remove all roles from user
     */
    public function destroy(User $user)
    {
        $user->syncRoles([]);

        return redirect()->route('admin.user-roles.index')
            ->with('success', "Todos os perfis foram removidos do usuário {$user->name}!");
    }
}
