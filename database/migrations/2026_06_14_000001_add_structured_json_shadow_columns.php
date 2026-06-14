<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStructuredJsonShadowColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addJsonColumnIfMissing('about', 'taglines_json', 'taglines');
        $this->addJsonColumnIfMissing('about', 'social_links_json', 'social_links');
        $this->addJsonColumnIfMissing('projects', 'categories_json', 'categories');
        $this->addJsonColumnIfMissing('projects', 'images_json', 'images');
        $this->addJsonColumnIfMissing('portfolio_configs', 'setting_value_json', 'setting_value');
        $this->addJsonColumnIfMissing('portfolio_configs', 'default_value_json', 'default_value');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropColumnIfExists('portfolio_configs', 'default_value_json');
        $this->dropColumnIfExists('portfolio_configs', 'setting_value_json');
        $this->dropColumnIfExists('projects', 'images_json');
        $this->dropColumnIfExists('projects', 'categories_json');
        $this->dropColumnIfExists('about', 'social_links_json');
        $this->dropColumnIfExists('about', 'taglines_json');
    }

    private function addJsonColumnIfMissing(string $tableName, string $columnName, string $afterColumn): void
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $afterColumn) {
            $table->json($columnName)->nullable()->after($afterColumn);
        });
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
    }
}
