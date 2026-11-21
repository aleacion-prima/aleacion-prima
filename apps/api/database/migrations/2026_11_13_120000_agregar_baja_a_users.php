<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // El correo es único: al dar de baja se libera y el original queda aquí.
            $table->string('email_baja')->nullable()->after('email');
            $table->timestamp('dado_de_baja_en')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_baja', 'dado_de_baja_en']);
        });
    }
};
