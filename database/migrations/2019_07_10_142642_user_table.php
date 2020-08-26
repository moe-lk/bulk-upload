<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('security_users' , function(Blueprint $table){
//            $table->rememberToken();
//            $table->save();
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
        Schema::table('security_users' , function(Blueprint $table){
            // $table->dropRememberToken();
//            $table->save();
        });
    }
}
