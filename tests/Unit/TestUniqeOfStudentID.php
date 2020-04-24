<?php


namespace Tests\Unit;

use App\Models\Security_user;
use Tests\TestCase;
use Mohamednizar\MoeUuid\MoeUuid;

class TestUniqeOfStudentID extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $this->faker = new \Faker\Factory();
    }

    public function testUniqeOnMillionUsers(){
        $number = 10;
       while ($number >= 0){
            $number--;
            $this->createUser();
        }
    }

    public function createUser(){
         $faker = \Faker\Factory::create();
         $openemis_no = MoeUuid::getUniqAlphanumeric();
         $exists = Security_user::query()->where('openemis_no',$openemis_no)->exists();
         $this->assertEquals(false,$exists);
         $full_name = $faker->name;
         Security_user::create([
            'username' =>  $openemis_no,
            'openemis_no' =>  $openemis_no,
            'first_name' => $full_name, // here we save full name in the column of first name. re reduce breaks of the system.
            'last_name' => genNameWithInitials($full_name),
            'gender_id' => $faker->numberBetween(1,2),
            'date_of_birth' => $faker->date,
            'address' => $faker->address,
            'birthplace_area_id' => $faker->numberBetween(1,250),
            'nationality_id' => $faker->numberBetween(1,2),
            'identity_type_id' => $faker->numberBetween(1,2),
            'identity_number' => $openemis_no,
            'is_student' => 1,
            'created_user_id' => $faker->numberBetween(1,2),
            ]);
          $this->output->writeln('Student Created:'.$openemis_no);
    }

}
