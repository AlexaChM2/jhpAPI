<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'reset_password')) {
                $table->string('reset_password')->nullable();
            }
            if (!Schema::hasColumn('empleados', 'reset_password_expires')) {
                $table->timestamp('reset_password_expires')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (Schema::hasColumn('empleados', 'reset_password')) {
                $table->dropColumn('reset_password');
            }
            if (Schema::hasColumn('empleados', 'reset_password_expires')) {
                $table->dropColumn('reset_password_expires');
            }
        });
    }
};
