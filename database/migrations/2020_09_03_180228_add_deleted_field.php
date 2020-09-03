<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('institution_students', function (Blueprint $table) {
           $table->timestamp('deleted')->nullable(); 
        });
        Schema::table('security_users', function (Blueprint $table) {
            $table->timestamp('deleted')->nullable(); 
         });
         Schema::table('institution_student_admission', function (Blueprint $table) {
            $table->timestamp('deleted')->nullable(); 
         });
         Schema::table('institution_class_students', function (Blueprint $table) {
            $table->timestamp('deleted')->nullable(); 
         });
         Schema::table('institution_subject_students', function (Blueprint $table) {
            $table->timestamp('deleted')->nullable(); 
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institution_students', function (Blueprint $table) {
            $table->dropColumn('deleted'); 
         });
         Schema::table('security_users', function (Blueprint $table) {
             $table->dropColumn('deleted'); 
          });
          Schema::table('institution_student_admission', function (Blueprint $table) {
             $table->dropColumn('deleted'); 
          });
          Schema::table('institution_class_students', function (Blueprint $table) {
             $table->dropColumn('deleted'); 
          });
          Schema::table('institution_subject_students', function (Blueprint $table) {
             $table->dropColumn('deleted'); 
          });
    }
}
