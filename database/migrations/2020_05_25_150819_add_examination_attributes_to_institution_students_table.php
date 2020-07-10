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
            $column = Schema::hasColumn('institution_students','taking_g5_exam');
            if(!$column){
                $table->dateTime('taking_g5_exam')->nullable(true);
            }else{
                $table->dateTime('taking_g5_exam')->nullable(true)->change();
            }

            $column1 = Schema::hasColumn('institution_students','income_at_g5');
            if(!$column1){
                $table->dateTime('income_at_g5')->nullable(true);
            }else{
                $table->dateTime('income_at_g5')->nullable(true)->change();
            }

            $column = Schema::hasColumn('institution_students','income_at_g5');
            if(!$column){
                $table->dateTime('exam_center_for_special_education_g5')->nullable(true);
            }else{
                $table->dateTime('exam_center_for_special_education_g5')->nullable(true)->change();
            }

            $column = Schema::hasColumn('institution_students','taking_ol_exam');
            if(!$column){
                $table->dateTime('taking_ol_exam')->nullable(true);
            }else{
                $table->dateTime('taking_ol_exam')->nullable(true)->change();
            }

            $column = Schema::hasColumn('institution_students','exam_center_for_special_education_ol');
            if(!$column){
                $table->dateTime('exam_center_for_special_education_ol')->nullable(true);
            }else{
                $table->dateTime('exam_center_for_special_education_ol')->nullable(true)->change();
            }

            $column = Schema::hasColumn('institution_students','taking_al_exam');
            if(!$column){
                $table->dateTime('taking_al_exam')->nullable(true);
            }else{
                $table->dateTime('taking_al_exam')->nullable(true)->change();
            }

            $column = Schema::hasColumn('institution_students','exam_center_for_special_education_al');
            if(!$column){
                $table->dateTime('exam_center_for_special_education_al')->nullable(true);
            }else{
                $table->dateTime('exam_center_for_special_education_al')->nullable(true)->change();
            }

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
