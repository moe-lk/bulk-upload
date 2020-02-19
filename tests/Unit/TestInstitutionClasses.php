<?php


namespace Tests\Unit;


use App\Models\Institution_class;
use PHPUnit\Framework\TestCase;

class TestInstitutionClasses extends TestCase
{
    public $institution_classes;
    public $currentGrade;
    public $nextGrade;
    public $institution_id;

    public function setUp()
    {
        $this->institution_classes = new Institution_class();
        $this->currentGrade = 2;
        $this->nextGrade = 3;
        $this->nextGrade = 1;
    }

    public function testNumberOfParallelClassesGrade2(){
        $numberOfClasses = $this->institution_classes->getNumberOfParallelClasses($this->currentGrade ,$this->institution_id);
        $this->assertEquals(10,$numberOfClasses,'Number of classes must be equales when institution code is 06369 and garde 2-3 promotion');
    }

    public function testNumberOfParallelClassesNextGrade3(){
        $numberOfClasses = $this->institution_classes->getNumberOfParallelClasses($this->nextGrade,$this->institution_id);
        $this->assertEquals(10,$numberOfClasses,'');
    }

    public function testNumberOfParallelClassesNextGrade1(){
        $numberOfClasses = $this->institution_classes->getNumberOfParallelClasses($this->nextGrade,$this->institution_id);
        $this->assertEquals(7,$numberOfClasses,'Number of classes must be equales when institution code is 06369 and grade a promotion');
    }

    public function testIsMatchingGradeTwoToThree(){
        $numberOfClassesGr2 = $this->institution_classes->getNumberOfParallelClasses($this->currentGrade,$this->institution_id);
        $numberOfClassesGr3 = $this->institution_classes->getNumberOfParallelClasses($this->currentGrade,$this->institution_id);

        $this->assertEquals(true,$numberOfClassesGr2 == $numberOfClassesGr3 , 'Number of classes must be equales when institution code is 06369 and grade 2-3 promotion');
    }



}
