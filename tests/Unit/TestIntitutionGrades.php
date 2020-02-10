<?php

namespace Tests\Unit;

use App\Models\Institution_grade;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestIntitutionGrades extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testIsPool()
    {
        $id = 123;
        $institutionGrades = new Institution_grade();
        $isPool = $institutionGrades->IsPool($id);
        $this->assertEquals(true,$isPool);
    }
}
