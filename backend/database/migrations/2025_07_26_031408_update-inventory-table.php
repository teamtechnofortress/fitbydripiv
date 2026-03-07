<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            DB::statement('ALTER TABLE inventory CHANGE size_mg vial_conc_mg DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE size_ml vial_conc_ml DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE dosage_min inject_dosage DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE dosage_max iv_dosage DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE duration inject_duration DOUBLE;');
            $table->double("iv_duration")->nullable();
            $table->integer("sales_weekly")->nullable();
            $table->dropColumn("dosage");
            $table->boolean("peptide")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            DB::statement('ALTER TABLE inventory CHANGE vial_conc_mg size_mg DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE vial_conc_ml size_ml DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE inject_dosage dosage_min DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE iv_dosage dosage_max DOUBLE;');
            DB::statement('ALTER TABLE inventory CHANGE inject_duration duration DOUBLE;');
            $table->dropColumn("iv_duration");
            $table->dropColumn("sales_weekly");
            $table->text("dosage")->nullable();
            $table->dropColumn("peptide");
        });
    }
};
