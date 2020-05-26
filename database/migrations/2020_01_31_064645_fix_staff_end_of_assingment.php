<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixStaffEndOfAssingment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

       DB::raw("UPDATE institution_staff SET end_date=null ,end_year = null ,staff_status_id =2");
       $rows = DB::table('institution_staff')->get(['id']);
       foreach ($rows as $row) {
           DB::table('institution_staff')
               ->where('id', $row->id)
               ->update(['end_date' => null,'end_year' => null ,'staff_status_id' =>1 ]);
       }
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
