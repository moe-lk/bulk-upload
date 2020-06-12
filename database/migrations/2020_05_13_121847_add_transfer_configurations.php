<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransferConfigurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $data = [
            [   
                'id' => 8004,
                'name' => 'Student Transfer In',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => '1000',
                '_view' => 'StudentTransferIn.index|StudentTransferIn.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => 'StudentTransferIn.remove',
                '_execute' => 'StudentTransferIn.add|StudentTransferIn.edit|StudentTransferIn.add',
                'order' => '31',
                'visible' => '1',
                'description' => NULL,
                'modified_user_id' => '2',
                'modified' => now(),
                'created_user_id' => '1',
                'created' => now(),
            ],
            [
                'id' => 8005,
                'name' => 'Student Transfer Out',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => '1000',
                '_view' => 'StudentTransferOut.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentTransferOut.edit|StudentTransferOut.view',
                'order' => '32',
                'visible' => '1',
                'description' => NULL,
                'modified_user_id' => '2',
                'modified' => '2017-10-12 17:06:58',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '1022',
                'name' => 'Transfer Request',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => '1000',
                '_view' => 'TransferRequests.index|TransferRequests.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => 'TransferRequests.remove',
                '_execute' => 'TransferRequests.add|TransferRequests.edit|Transfer.add',
                'order' => '31',
                'visible' => '1',
                'description' => NULL,
                'modified_user_id' => '2',
                'modified' => '2017-10-12 17:06:58',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ],
            [
                'id' => '1023',
                'name' => 'Transfer Approval',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Students',
                'parent_id' => '1000',
                '_view' => 'TransferApprovals.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'TransferApprovals.edit|TransferApprovals.view',
                'order' => '32',
                'visible' => '1',
                'description' => NULL,
                'modified_user_id' => '2',
                'modified' => '2017-10-12 17:06:58',
                'created_user_id' => '1',
                'created' => '1990-01-01 00:00:00',
            ]
            ];

   //         DB::table('security_functions')->whereIn('id',[1022,1023,8001,8002])->delete();
   //         DB::table('security_functions')->insert($data);
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
