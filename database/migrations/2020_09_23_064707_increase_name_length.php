<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IncreaseNameLength extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('security_users', function (Blueprint $table) {
            $column = Schema::hasColumn('security_users','first_name');
            if($column){
                $table->string('first_name',256)->change();
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
            $column = Schema::hasColumn('security_users','first_name');
            if($column){
                $table->string('first_name',100)->change();
            }
        });
    }
}
