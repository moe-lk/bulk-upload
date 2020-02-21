<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestPromotion extends TestCase
{
    /**
     * Check the command exiting
     *
     * @return void
     */
    public function testCommand()
    {
        $this->artisan('promote:students 2020')
            ->assertExitCode(0);
    }
}
