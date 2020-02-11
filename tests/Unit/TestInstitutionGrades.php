<?php

namespace Tests\Unit;

use App\Models\Institution_grade;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestInstitutionGrades extends TestCase
{
    public $institutionGrades;

    public function setUp(): void
    {
        $this->institutionGrades = new Institution_grade();
    }

    public function testIsPool()
    {
        $id = 123;
        $institutionId = 100;
        $isPool = $this->institutionGrades->IsPool($id, $institutionId);
        $this->assertEquals(true, $isPool);
    }

    public function testGetNumberOfParallelClasses()
    {
        $id = 123;
        $institutionId = 100;
        $getNumberOfParallelClasses = $this->institutionGrades->GetNumberOfParallelClasses($id, $institutionId);
        $this->assertEquals(10, $getNumberOfParallelClasses);
    }

    public function testMatchNumberOfClasses()
    {
        $id = 123;
        $institutionId = 100;
        $currentGrade = 1;
        $nextGrade = 2;
        $matchNumberOfClasses = $this->institutionGrades->MatchNumberOfClasses($id, $institutionId, $currentGrade, $nextGrade);
        $this->assertEquals(true, $matchNumberOfClasses);
    }

    public function testGetNextGrade()
    {
        $id = 123;
        $institutionId = 100;
        $currentGrade = 1;
        $getNextGrade = $this->institutionGrades->GetNextGrade($id, $institutionId, $currentGrade);
        $this->assertEquals(2, $getNextGrade);
    }
}
