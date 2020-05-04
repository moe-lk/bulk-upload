<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPromotedToInstitutionGrades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_grades', function (Blueprint $table) {
            $column = Schema::hasColumn('institution_grades','promoted');
            if(!$column){
                $table->string('promoted')->default('2019');//

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
        Schema::table('institution_grades', function (Blueprint $table) {
            $column = Schema::hasColumn('institution_grades','promoted');
            if($column){
                $table->removeColumn('promoted');
            }
        });
    }
}
