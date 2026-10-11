<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test roto a propósito para comprobar que la CI se pone en rojo (issue #5, criterio 3).
 * Esta rama no se mergea nunca.
 */
final class CiCanaryTest extends TestCase
{
    public function test_ci_fails_when_a_test_fails(): void
    {
        $this->assertSame(1, 2);
    }
}
