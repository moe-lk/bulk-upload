<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGradeAndYearToExaminaitonStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('examination_students', function (Blueprint $table) {
            $table->string('grade')->default('G5');
            $table->string('year')->default('2020');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('examinaiton_students', function (Blueprint $table) {
            //
        });
    }
}
