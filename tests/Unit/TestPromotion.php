<?php

namespace Tests\Unit;

use App\Console\Commands\PromoteStudents;
use App\Models\Education_grade;
use App\Models\Institution_grade;
use Tests\TestCase;

class TestPromotion extends TestCase
{
    public $promoteStudents ;
    private $instituion_grade;
    private $education_grades;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promoteStudents = new PromoteStudents();
        $this->instituion_grade = new Institution_grade();
        $this->education_grades = new Education_grade();
    }

    public function testPromotionParallelProcess()
    {
        $institutionGrade = $this->instituion_grade->getInstitutionGradeToPromoted('2020','9861');
        $educationGrades = $this->education_grades->getNextGrade($institutionGrade->education_grade_id);
        $promoteProcess = $this->promoteStudents->process($institutionGrade, $educationGrades, 2019);
        $this->assertEquals(2, $promoteProcess);
    }

    public function testPromotionPoolProcess()
    {
        //todo : build test cases according to the school entries
    }

    public function testPromotionGraduationProcess()
    {
        //todo : build test cases according to the school entries
    }
}
