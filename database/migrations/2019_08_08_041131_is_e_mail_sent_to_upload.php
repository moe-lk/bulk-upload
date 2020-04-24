<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IsEMailSentToUpload extends Migration
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
            $column = Schema::hasColumn('uploads','is_email_sent');
            if(!$column){
                $table->boolean('is_email_sent')->default(false);
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
            $column = Schema::hasColumn('uploads','is_email_sent');
            if($column){
                $table->removeColumn('is_email_sent');
            }
        });
    }
}
