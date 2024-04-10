<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UsersSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        Schema::enableForeignKeyConstraints();

        $user = User::create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'name' => 'Hydra Admin',
        ]);
        $user->roles()->attach(Role::where('slug', 'admin')->first());


        $editor = User::create([
            'email' => 'editor@test.com',
            'password' => Hash::make('password'),
            'name' => 'Editor 1'
        ]);
        $editor->roles()->attach(Role::where('slug', 'editor')->first());
    }
}
