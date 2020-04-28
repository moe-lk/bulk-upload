<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFailedColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uploads', function (Blueprint $table) {
            //
             $column1 = Schema::hasColumn('uploads','update');
             $column2 = Schema::hasColumn('uploads','insert');
             if(!$column1){
                $table->boolean('update')->default(false);
             }
            if(!$column2){
                $table->boolean('insert')->default(false);
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
        Schema::table('uploads', function (Blueprint $table) {
            //
            $table->removeColumn('update');
            $table->removeColumn('insert');
        });
    }
}
