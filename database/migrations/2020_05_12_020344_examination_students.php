<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExaminationStudents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        Schema::create('ExaminationStudents',function(Blueprint $table){
            $table->string('nsid',12);
            $table->integer('school_id');
            $table->string('full_name');
            $table->date('dob');
            $table->char('gender',1);
            $table->string('address');
            $table->string('annual_income');
            $table->boolean('has_special_need')->defualt(false);
            $table->string('disable_type');
            $table->string('disbale_details');
            $table->string('special_education_cenetr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('ExaminationStudents');
    }
}
