<?php

namespace Tests\Unit;

use App\Console\Commands\PromoteStudents;
use App\Models\Education_grade;
use App\Models\Institution_grade;
use Tests\TestCase;

class TestPromotion extends TestCase
{
    public $promoteStudents ;
    private $instituion_grade, $education_grades, $inst_cid_to_be_tested, $next_year, $current_year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promoteStudents = new PromoteStudents();
        $this->instituion_grade = new Institution_grade();
        $this->education_grades = new Education_grade();
        $this->inst_cid_to_be_tested = '2000';
        $this->next_year = '2020';
        $this->current_year = '2019';
    }

    /**Test Case name : testPromotionParallelProcessNextZero
     * Target class : PromoteStudents
     * Target method : process
     * Description : Checks whether the function returns 1 if the next grade count is zero
     * Promotion type : Parallel
     * */
    public function testPromotionParallelProcessNextZero()
    {
        $institutionGrade = $this->instituion_grade->getInstitutionGradeToPromoted($this->next_year, $this->inst_cid_to_be_tested);
        $educationGrades = $this->education_grades->getNextGrade($institutionGrade->education_grade_id);
        $promoteProcess = $this->promoteStudents->process($institutionGrade, $educationGrades, $this->current_year);
        $this->assertEquals(1, $promoteProcess, 'The grade is '.$this->education_grades);
    }

    /**Test Case name : testPromotionParallelProcessNextEqual
     * Target class : PromoteStudents
     * Target method : process
     * Description : Checks whether the function returns 1 if the next grade count is equal to the current count
     * Promotion type : Parallel
     * */
    public function testPromotionParallelProcessNextEqual()
    {
        $institutionGrade = $this->instituion_grade->getInstitutionGradeToPromoted($this->next_year,$this->inst_cid_to_be_tested);
        $educationGrades = $this->education_grades->getNextGrade($institutionGrade->education_grade_id);
        $promoteProcess = $this->promoteStudents->process($institutionGrade, $educationGrades, $this->current_year);
        $this->assertEquals(1, $promoteProcess);
    }

    /**Test Case name : testPoolProcessNextHigh
     * Target class : PromoteStudents
     * Target method : process
     * Description : Checks whether the function returns 2 if the next grade count is higher than the current count
     * Promotion type : Pool
     * */
    public function testPoolProcessNextHigh()
    {
        $institutionGrade = $this->instituion_grade->getInstitutionGradeToPromoted($this->next_year,$this->inst_cid_to_be_tested);
        $educationGrades = $this->education_grades->getNextGrade($institutionGrade->education_grade_id);
        $promoteProcess = $this->promoteStudents->process($institutionGrade, $educationGrades, $this->current_year);
        $this->assertEquals(2, $promoteProcess);
    }
}
