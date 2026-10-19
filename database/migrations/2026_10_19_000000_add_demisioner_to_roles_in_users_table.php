<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('new_member', 'anggota', 'pengurus', 'demisioner') NOT NULL DEFAULT 'new_member'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('new_member', 'anggota', 'pengurus') NOT NULL DEFAULT 'new_member'");
    }
};
