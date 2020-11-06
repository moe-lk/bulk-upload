<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAdminProfile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('security_users')->where('username','admin')
        ->update([
            'first_name'=>'System Administrator',
            'last_name' => 'System Administrator',
            'email' => 'nsis.moe@gmail.com',
            'address' => 'Data Management Branch , Ministry of Education , Isurupaya, Battaramulla',
            'address_area_id' => 100 ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
