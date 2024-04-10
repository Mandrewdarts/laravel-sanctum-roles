<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller {

    public function index(User $user) {
        return $user->load('roles');
    }

    public function store(Request $request, User $user) {
        $data = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::find($data['role_id']);

        if (!$user->roles()->find($data['role_id'])) {
            $user->roles()->attach($role);
        }

        return $user->load('roles');
    }

    public function destroy(User $user, Role $role) {
        $user->roles()->detach($role);

        return $user->load('roles');
    }
}
