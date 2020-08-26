<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FileUpload extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $hasTable = Schema::hasTable('uploads');
        if(!$hasTable)
        {
            Schema::create('uploads', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('security_user_id');
                $table->integer('institution_class_id');
                $table->string('model');
                $table->string('filename');
                $table->boolean('is_processed')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
            Schema::table('uploads', function($table) {
                $table->foreign('security_user_id')->references('id')->on('security_users')->onDelete('cascade')->unique()->unsigned();
                $table->foreign('institution_class_id')->references('id')->on('institution_classes')->onDelete('cascade')->unique()->unsigned();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('uploads');
    }
}
