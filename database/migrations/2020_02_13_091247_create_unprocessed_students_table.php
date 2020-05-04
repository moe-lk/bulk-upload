<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnprocessedStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $hasTable = Schema::hasTable('unprocessed_students');
        if(!$hasTable){
            Schema::create('unprocessed_students', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('current_unprocessed_students_count');
                $table->tinyInteger('is_processed');
                $table->tinyInteger('notification');
                $table->integer('institution_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unprocessed_students');
    }
}
