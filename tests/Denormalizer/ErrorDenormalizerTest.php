<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Denormalizer;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\ApiServiceBundle\Denormalizer\ErrorDenormalizer;
use TwentytwoLabs\ApiServiceBundle\Model\ErrorInterface;
use TwentytwoLabs\ApiServiceBundle\Model\ResourceInterface;

#[AllowMockObjectsWithoutExpectations]
final class ErrorDenormalizerTest extends TestCase
{
    public function testShouldSupportsDenormalization(): void
    {
        $denormalizer = $this->getDenormalizer();
        $this->assertTrue($denormalizer->supportsDenormalization([], ErrorInterface::class));
    }

    public function testShouldNotSupportsDenormalization(): void
    {
        $denormalizer = $this->getDenormalizer();
        $this->assertFalse($denormalizer->supportsDenormalization([], ResourceInterface::class));
    }

    public function testShouldDenormalizeErrorWithOutViolations(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(500);
        $response->expects($this->once())->method('getReasonPhrase')->willReturn('Internal Server Error');

        $denormalizer = $this->getDenormalizer();
        $error = $denormalizer->denormalize([], ErrorDenormalizer::class, null, ['response' => $response]);

        $this->assertSame('Internal Server Error', $error->getMessage());
        $this->assertSame([], $error->getViolations());
        $this->assertSame(500, $error->getCode());
    }

    public function testShouldDenormalizeErrorWithViolations(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(400);
        $response->expects($this->once())->method('getReasonPhrase')->willReturn('Bad Request');

        $violations = [
            [
                'propertyPath' => 'title',
                'message' => 'assert.not-blank.title',
                'code' => 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
            ],
        ];

        $denormalizer = $this->getDenormalizer();
        $error = $denormalizer->denormalize(
            ['violations' => $violations],
            ErrorDenormalizer::class,
            null,
            ['response' => $response]
        );

        $this->assertSame('Bad Request', $error->getMessage());
        $this->assertSame($violations, $error->getViolations());
        $this->assertSame(400, $error->getCode());
    }

    public function testShouldValidateSupportedTypes(): void
    {
        $denormalizer = $this->getDenormalizer();
        $this->assertSame(
            [
                '*' => false,
                ErrorInterface::class => true,
            ],
            $denormalizer->getSupportedTypes(null)
        );
    }

    private function getDenormalizer(): ErrorDenormalizer
    {
        return new ErrorDenormalizer();
    }
}
