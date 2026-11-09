<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('city')->nullable()->after('location');
            $table->string('region')->nullable()->after('city');
            $table->text('user_agent')->nullable()->after('platform');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['ip']);
            $table->dropColumn(['city', 'region', 'user_agent']);
        });
    }
};
