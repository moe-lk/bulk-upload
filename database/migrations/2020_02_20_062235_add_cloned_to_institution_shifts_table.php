<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClonedToInstitutionShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_shifts', function (Blueprint $table) {
            $column = Schema::hasColumn('institution_shifts','cloned');
            if(!$column){
                $table->string('cloned',4)->default('2019');
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
        Schema::table('institution_shifts', function (Blueprint $table) {
            $column = Schema::hasColumn('institution_shifts','cloned');
            if($column){
                $table->removeColumn('cloned');
            }
        });
    }
}
