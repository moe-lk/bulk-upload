<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExaminationAttributesToInstitutionStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_students', function (Blueprint $table) {
            $table->boolean('taking_g5_exam')->nullable(true);
            $table->integer('income_at_g5')->nullable(true);
            $table->boolean('exam_center_for_special_education_g5')->nullable(true);
            $table->boolean('taking_ol_exam')->nullable(true);
            $table->boolean('exam_center_for_special_education_ol')->nullable(true);
            $table->boolean('taking_al_exam')->nullable(true);
            $table->boolean('exam_center_for_special_education_al')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institution_students', function (Blueprint $table) {
            //
            $table->dropColumn('taking_g5_exam');
            $table->dropColumn('income_at_g5');
            $table->dropColumn('exam_center_for_special_education_g5');
            $table->dropColumn('taking_ol_exam');
            $table->dropColumn('exam_center_for_special_education_ol');
            $table->dropColumn('taking_al_exam');
            $table->dropColumn('exam_center_for_special_education_al');
        });
    }
}
