<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGITFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_students', function (Blueprint $table) {
            $table->boolean('taking_git_exam')->default(false);
            $table->boolean('exam_center_for_special_education_git')->default(false); 
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
            $table->dropColumn('taking_git_exam');
            $table->dropColumn('exam_center_for_special_education_git');
        });
    }
}
