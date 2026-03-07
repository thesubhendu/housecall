<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function withApiKey(): static
    {
        return $this->withHeader('Authorization', 'Bearer test-api-key-for-testing');
    }
}
