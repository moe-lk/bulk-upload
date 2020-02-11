<?php

namespace Tests\Unit;

use App\Models\Institution_grade;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestInstitutionGrades extends TestCase
{
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
        $isPool = $institutionGrades->IsPool($id, $institutionId);
        $this->assertEquals(true, $isPool);
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
}
