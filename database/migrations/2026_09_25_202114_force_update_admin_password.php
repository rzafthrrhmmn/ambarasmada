<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'abcdxwyz06@gmail.com')
            ->update([
                'password' => '$2y$12$aYhTWUH4tXEJWm.r.AssuurrPCZ0lwlQPUxIKYmzl3/Y.0ttYIwKm',
                'status' => 'approved',
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};
