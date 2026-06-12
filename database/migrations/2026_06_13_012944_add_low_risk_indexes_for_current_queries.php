<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLowRiskIndexesForCurrentQueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->index('email', 'admins_email_idx');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->index('setting_key', 'settings_key_idx');
        });

        Schema::table('portfolio_configs', function (Blueprint $table) {
            $table->index('setting_key', 'port_cfg_key_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('email', 'messages_email_idx');
            $table->index('created_at', 'messages_created_idx');
            $table->index(['replied', 'created_at'], 'messages_replied_created_idx');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->index('tracking_id', 'visitors_tracking_idx');
            $table->index('created_at', 'visitors_created_idx');
            $table->index(['is_new', 'created_at'], 'visitors_new_created_idx');
            $table->index('browser', 'visitors_browser_idx');
            $table->index('platform', 'visitors_platform_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('created_at', 'projects_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_created_idx');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex('visitors_platform_idx');
            $table->dropIndex('visitors_browser_idx');
            $table->dropIndex('visitors_new_created_idx');
            $table->dropIndex('visitors_created_idx');
            $table->dropIndex('visitors_tracking_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_replied_created_idx');
            $table->dropIndex('messages_created_idx');
            $table->dropIndex('messages_email_idx');
        });

        Schema::table('portfolio_configs', function (Blueprint $table) {
            $table->dropIndex('port_cfg_key_idx');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('settings_key_idx');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropIndex('admins_email_idx');
        });
    }
}
