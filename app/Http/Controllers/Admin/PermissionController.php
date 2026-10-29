<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Permission;
use App\Models\User;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.access');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::with('roles')
            ->orderBy('module')
            ->orderBy('action')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modules = Permission::distinct()->pluck('module')->filter()->sort();
        $actions = Permission::distinct()->pluck('action')->filter()->sort();

        return view('admin.permissions.create', compact('modules', 'actions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        $validated = $request->validated();

        Permission::create($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissão criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        $permission->load(['roles' => fn ($query) => $query->withCount('users')]);

        // Usuários que têm a permissão por meio de algum perfil
        $usersWithAccess = User::whereHas('roles.permissions', fn ($query) => $query->whereKey($permission->id))
            ->with('roles')
            ->orderBy('name')
            ->get();

        return view('admin.permissions.show', compact('permission', 'usersWithAccess'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        $modules = Permission::distinct()->pluck('module')->filter()->sort();
        $actions = Permission::distinct()->pluck('action')->filter()->sort();

        return view('admin.permissions.edit', compact('permission', 'modules', 'actions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $validated = $request->validated();

        $permission->update($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissão atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Não é possível deletar uma permissão que está associada a roles.');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissão deletada com sucesso!');
    }

    /**
     * Toggle permission status
     */
    public function toggle(Permission $permission)
    {
        $permission->update(['is_active' => ! $permission->is_active]);

        $status = $permission->is_active ? 'ativada' : 'desativada';

        return redirect()->route('admin.permissions.index')
            ->with('success', "Permissão {$status} com sucesso!");
    }
}
