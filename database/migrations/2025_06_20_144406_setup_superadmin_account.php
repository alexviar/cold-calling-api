<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $admin = config('auth.admin_user');
        User::create([
            'name' => $admin['name'] ?? 'Super Admin',
            'email' => $admin['email'] ?? 'superadmin@example.com',
            'password' => $admin['password'] ?? 'password',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
