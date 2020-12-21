<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateInstitutionShiftTableClonedColumn extends Migration
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
            if($column){
                $table->string('cloned',20)->change();
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
                $table->string('cloned',4)->change();
            }
        });
    }
}
