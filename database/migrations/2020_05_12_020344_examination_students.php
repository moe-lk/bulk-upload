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

        Schema::create('examination_students',function(Blueprint $table){
            $table->integer('st_no',9);
            $table->string('stu_no',9);
            $table->string('nsid',12);
            $table->integer('schoolid');
            $table->string('f_name');
            $table->integer('medium');
            $table->date('b_date');
            $table->char('gender',1);
            $table->string('pvt_address');
            $table->string('a_income');
            $table->boolean('spl_need')->nullable();
            $table->string('disability_type')->nullable();
            $table->string('disability')->nullable();
            $table->string('sp_centre')->nullable();
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
        Schema::drop('examination_students');
    }
}
