<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExaminationValidationFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $column = Schema::hasColumn('security_users','updated_from');
        $column2 = Schema::hasColumn('institution_students','updated_from');
        $column3 = Schema::hasColumn('institution_student_admission','updated_from');

        Schema::table('security_users', function (Blueprint $table)  use ($column){
            if(!$column){
                $table->string('updated_from',20)->default('sis');
            }
        });
        Schema::table('institution_students', function (Blueprint $table) use ($column2) {
            if(!$column2){
                $table->string('updated_from',20)->default('sis');
            }
        });
        Schema::table('institution_student_admission', function (Blueprint $table) use ($column3) {
            if(!$column3){
                $table->string('updated_from',20)->default('sis');
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
        Schema::table('security_users', function (Blueprint $table) {
            $table->dropColumn('updated_from');
        });
        Schema::table('institution_students', function (Blueprint $table) {
            $table->dropColumn('updated_from');
        });
        Schema::table('institution_student_admission', function (Blueprint $table) {
            $table->dropColumn('updated_from');
        });
    }
}
