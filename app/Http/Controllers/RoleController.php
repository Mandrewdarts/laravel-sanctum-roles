<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller {

    public function index() {
        return Role::all();
    }


    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required',
            'slug' => 'required',
        ]);

        $existing = Role::where('slug', $data['slug'])->first();

        if (!$existing) {
            $role = Role::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
            ]);

            return $role;
        }

        return response(['message' => 'role already exists'], 409);
    }

    public function show(Role $role) {
        return $role;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role) {
        if ($role->slug != 'admin' && $role->slug != 'super-admin') {
            //don't allow changing the admin slug, because it will make the routes inaccessbile due to faile ability check
            $role->delete();

            return response(['message' => 'role has been deleted']);
        }

        return response(['message' => 'you cannot delete this role'], 422);
    }
}
