<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;  
use App\Http\Requests\Admin\UpdateUserRequest; 
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;   
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     // Bisa aktifkan ini jika pakai policy
    //     // $this->authorizeResource(User::class, 'user');
    // }

    public function index(Request $request)
    {
        $query = User::with('roles')->orderBy('name');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role') && $request->input('role') !== '') {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles_array' => $user->roles->pluck('name')->toArray(),
                    'roles_string' => $user->roles->pluck('name')->implode(', '),
                    'managedKelasIds' => $user->managedClasses->pluck('id_kelas')->toArray(),
                ];
            }),
            'filters' => $request->only(['search', 'role']),
            'allRoles' => Role::orderBy('name')->pluck('name'),
            'allKelas' => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']),
            'can' => [
                'create_user' => $request->user()->can('manage users'),
                'edit_user' => $request->user()->can('manage users'),
                'delete_user' => $request->user()->can('manage users'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            
            $user->assignRole($validated['roles']);

            if (in_array('admin_kelas', $validated['roles']) && !empty($validated['kelas_ids'])) {
                $user->managedClasses()->attach($validated['kelas_ids']);
            }
        });

        return redirect()->route('admin.users.index')->with('message', 'User berhasil dibuat.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            $input = $validated;
            if (!empty($validated['password'])) {
                $input['password'] = Hash::make($validated['password']);
            } else {
                unset($input['password']);
            }
            
            $user->update($input);
            $user->syncRoles($validated['roles']);

            if (in_array('admin_kelas', $validated['roles'])) {
                $user->managedClasses()->sync($validated['kelas_ids'] ?? []);
            } else {
                $user->managedClasses()->detach();
            }
        });

        return Redirect::route('admin.users.index', $request->only(['search', 'role']))
            ->with([
                'message' => 'User berhasil diperbarui.',
                'type' => 'success',
            ]);
    }

    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->can('manage users')) {
            abort(403);
        }

        $user->delete();

        return Redirect::route('admin.users.index', $request->only(['search', 'role']))
            ->with([
                'message' => 'User deleted successfully.',
                'type' => 'success',
            ]);
    }
}