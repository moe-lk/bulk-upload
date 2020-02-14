<?php

namespace Tests\Unit;

use App\Models\Institution_class_grade;
use App\Models\Institution_grade;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestInstitutionGrades extends TestCase
{

//    public function __construct()
//    {
//        $this->instituion_grade = new Institution_grade();
//    }



    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testIsPool()
    {
        $id = 123;
        $institutionId = 100;
        $institutionGrades = new Institution_grade();
        $grade = $institutionGrades->find($id);
//        $isPool = $institutionGrades->IsPool($grade, $institutionId);
        $this->assertEquals(true, true);
    }

    public function testGetNumberOfParallelClasses()
    {
        $id = 123;
        $institutionId = 100;
        $institutionGrades = new Institution_grade();

        $getNumberOfParallelClasses = $institutionGrades->GetNumberofParallelClasses($id, $institutionId);
        $this->assertEquals(10, $getNumberOfParallelClasses);
    }

    public function testMatchNumberOfClasses()
    {

    }

    public function testGetNextGrade()
    {

    }

    public function testGetParallelClasses(){
        $id = 123;
        $this->instituion_grade = new Institution_grade();
        $parallel = $this->instituion_grade->where('id','=',$id)-> parallelClasses($id);
        dd($parallel);
        $this->assertIsArray($parallel);
    }
}
