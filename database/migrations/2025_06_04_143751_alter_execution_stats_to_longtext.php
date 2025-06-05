<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            $table->longText('execution_stats')->nullable()->after('duration');
            //hapus table started_at dan finished_at
            $table->dropColumn(['started_at', 'finished_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {

        });
    }
};
