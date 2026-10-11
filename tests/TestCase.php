<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        TestDatabaseGuard::assertSafeTestProcessOrFailClosed();
        parent::setUpBeforeClass();
    }

    public function createApplication()
    {
        TestDatabaseGuard::assertSafeTestProcessOrFailClosed();

        return parent::createApplication();
    }

    protected function setUpTraits()
    {
        TestDatabaseGuard::assertLaravelApplicationIsSafe($this->app);

        return parent::setUpTraits();
    }
}
