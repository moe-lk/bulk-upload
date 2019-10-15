<?php

use Illuminate\Database\Seeder;

class UpdateInsertField extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('uploads')->where('is_processed', 1)->update(['insert' => 1]);

    }
}
