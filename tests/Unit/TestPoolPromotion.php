<?php


namespace Tests\Unit;


use App\Console\Commands\PromoteStudents;
use App\Models\Education_grade;
use App\Models\Institution_grade;
use Tests\TestCase;

class TestPoolPromotion extends TestCase
{
    private $promoteStudents, $institution_grade, $education_grades, $inst_cid_to_be_tested, $next_year, $current_year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promoteStudents = new PromoteStudents();
        $this->institution_grade = new Institution_grade();
        $this->education_grades = new Education_grade();
        $this->inst_cid_to_be_tested = '01361'; //Institution censes id
        $this->next_year = '2020';
        $this->current_year = '2019';
    }

    /**Test Case name : testPromotionPoolProcessIfNextGradeCountHigh
     * Target class : PromoteStudents
     * Target method : process
     * Description : Checks whether the function returns 2 if the next grade count is higher than the current count
     * Promotion type : Pool
     * */
    public function testPromotionPoolProcessIfNextGradeCountHigh()
    {
        $institutionGrade = $this->institution_grade->getInstitutionGradeToPromoted($this->next_year,$this->inst_cid_to_be_tested);
        $educationGrades = $this->education_grades->getNextGrade($institutionGrade[4]['education_grade_id']); //array number of the grade
        $promoteProcess = $this->promoteStudents->process($institutionGrade[3], $educationGrades, $this->current_year);
        $this->assertEquals(2, $promoteProcess); //expected result 2->Pool 1->Parallel
    }

}
