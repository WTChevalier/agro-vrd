<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('usuarios', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'gurztac_user_id')) {
                $t->unsignedBigInteger('gurztac_user_id')->nullable()->unique()->after('id');
                $t->index('gurztac_user_id');
            }
        });
    }
    public function down(): void {
        Schema::table('usuarios', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'gurztac_user_id')) {
                $t->dropColumn('gurztac_user_id');
            }
        });
    }
};
