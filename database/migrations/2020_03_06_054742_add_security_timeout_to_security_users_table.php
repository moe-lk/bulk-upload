<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSecurityTimeoutToSecurityUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('security_users', function (Blueprint $table)  {
            $column = Schema::hasColumn('security_users','security_timeout');
            if(!$column){
                $table->dateTime('security_timeout')->nullable(true);
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
            $column = Schema::hasColumn('security_users','security_timeout');
            if($column){
                $table->dropColumn('security_timeout');
            }

        });
    }
}
