<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;

class UserController extends Controller {

    public function index() {
        return User::with('roles')->get();
    }

    public function store(Request $request) {
        $creds = $request->validate([
            'email' => ['required', 'email'],
            'password' => 'required',
            'name' => ['nullable', 'string'],
            'roles' => ['nullable', 'array', 'exists:roles,slug']
        ]);

        $user = User::where('email', $creds['email'])->first();
        if ($user) {
            return response(['message' => 'user already exists'], 409);
        }

        $user = User::create([
            'email' => $creds['email'],
            'password' => Hash::make($creds['password']),
            'name' => $creds['name'],
        ]);

        if($creds['roles']) {
            $user->roles()->attach(Role::whereIn('slug', $creds['roles'])->get());
        } else {
            $defaultRoleSlug = 'user';
            $user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());
        }

        return $user;
    }

    public function login(Request $request) {
        $creds = $request->validate([
            'email' => ['required', 'email'],
            'password' => 'required',
        ]);

        $user = User::where('email', $creds['email'])->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response(['message' => 'invalid credentials'], 401);
        }

        $deletePreviousTokens = false;
        if ($deletePreviousTokens) {
            $user->tokens()->delete();
        }

        $roles = $user->roles->pluck('slug')->all();

        $plainTextToken = $user->createToken('api-token', $roles)->plainTextToken;
        // $plainTextToken = $user->createToken('api-token', $roles, Carbon::now()->addMinutes(2))->plainTextToken;

        return response(['id' => $user->id, 'name' => $user->name, 'token' => $plainTextToken], 200);
    }

    public function show(User $user) {
        return $user;
    }

    public function update(Request $request, User $user) {
        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->password = $request->password ? Hash::make($request->password) : $user->password;
        $user->email_verified_at = $request->email_verified_at ?? $user->email_verified_at;

        //check if the logged in user is updating it's own record

        $loggedInUser = $request->user();
        if ($loggedInUser->id == $user->id) {
            $user->update();
        } elseif ($loggedInUser->tokenCan('admin') || $loggedInUser->tokenCan('super-admin')) {
            $user->update();
        } else {
            throw new MissingAbilityException('Not Authorized');
        }

        return $user;
    }

    public function destroy(User $user) {
        $adminRole = Role::where('slug', 'admin')->first();
        $userRoles = $user->roles;

        if ($userRoles->contains($adminRole)) {
            //the current user is admin, then if there is only one admin - don't delete
            $numberOfAdmins = Role::where('slug', 'admin')->first()->users()->count();
            if ($numberOfAdmins == 1) {
                return response(['message' => 'Create another admin before deleting this only admin user'], 409);
            }
        }

        $user->delete();

        return response(['message' => 'user deleted']);
    }

    public function me(Request $request) {
        return $request->user();
    }
}
