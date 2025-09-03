<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('flow_dataset_templates', function (Blueprint $table) {
            $table->string('category')->default('quantities')->after('name');
        });
    }

    public function down()
    {
        Schema::table('flow_dataset_templates', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
