<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_intakes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patient')->onDelete('cascade');
            $table->enum('patient_type', ['new', 'existing'])->default('new');
            $table->enum('diabetes', ['yes', 'no'])->nullable();
            $table->enum('blood_thinners', ['yes', 'no'])->nullable();
            $table->enum('alcohol', ['yes', 'no'])->nullable();
            $table->enum('glp_history', ['yes', 'no'])->nullable();
            $table->enum('pancreatitis', ['yes', 'no'])->nullable();
            $table->enum('thyroid_cancer', ['yes', 'no'])->nullable();
            $table->enum('renal_impairment', ['yes', 'no'])->nullable();
            $table->json('current_conditions')->nullable();
            $table->json('additional_conditions')->nullable();
            $table->json('goals')->nullable();
            $table->json('medical_history')->nullable();
            $table->text('medications')->nullable();
            $table->text('current_conditions_notes')->nullable();
            $table->text('allergies')->nullable();
            $table->text('allergy_reactions')->nullable();
            $table->enum('submission_status', ['pending', 'reviewed', 'complete'])->default('pending');
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_intakes');
    }
};
