<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Validator\ConstraintViolation;
use TwentytwoLabs\ApiServiceBundle\Exception\ApiServiceError;
use TwentytwoLabs\ApiServiceBundle\Exception\ConstraintViolations;

final class ConstraintViolationsTest extends TestCase
{
    public function testShouldExtendApiServiceError(): void
    {
        $exception = new ConstraintViolations([]);

        $this->assertInstanceOf(ApiServiceError::class, $exception);
    }

    public function testShouldProvideTheListOfViolations(): void
    {
        $violationFoo = $this->createMock(ConstraintViolation::class);
        $violationFoo->expects($this->exactly(2))->method('getProperty')->willReturn('foo');
        $violationFoo->expects($this->exactly(2))->method('getMessage')->willReturn('bar is not a string');
        $violationFoo->expects($this->exactly(2))->method('getConstraint')->willReturn('');
        $violationFoo->expects($this->exactly(2))->method('getLocation')->willReturn('foo');

        $violationBar = $this->createMock(ConstraintViolation::class);
        $violationBar->expects($this->exactly(2))->method('getProperty')->willReturn('bar');
        $violationBar->expects($this->exactly(2))->method('getMessage')->willReturn('foo is not a string');
        $violationBar->expects($this->exactly(2))->method('getConstraint')->willReturn('');
        $violationBar->expects($this->exactly(2))->method('getLocation')->willReturn('body');

        $exception = new ConstraintViolations([$violationFoo, $violationBar]);

        $this->assertSame([$violationFoo, $violationBar], $exception->getViolations());

        $this->assertSame("Request constraint violations:\n[property]: foo\n[message]: bar is not a string\n[constraint]: \n[location]: foo\n\n[property]: bar\n[message]: foo is not a string\n[constraint]: \n[location]: body\n\n", (string) $exception);
    }
}
