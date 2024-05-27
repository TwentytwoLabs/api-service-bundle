<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\DataTransformer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiServiceBundle\DataTransformer\DataTransformer;
use TwentytwoLabs\ApiServiceBundle\DataTransformer\DataTransformerInterface;

final class DataTransformerTest extends TestCase
{
    private DataTransformerInterface|MockObject $fooDataTransformer;
    private DataTransformerInterface|MockObject $barDataTransformer;

    protected function setUp(): void
    {
        $this->fooDataTransformer = $this->createMock(DataTransformerInterface::class);
        $this->barDataTransformer = $this->createMock(DataTransformerInterface::class);
    }

    public function testShouldNotTransformData(): void
    {
        $this->fooDataTransformer->expects($this->once())->method('support')->with('json')->willReturn(false);
        $this->fooDataTransformer->expects($this->never())->method('transform');

        $this->barDataTransformer->expects($this->once())->method('support')->with('json')->willReturn(false);
        $this->barDataTransformer->expects($this->never())->method('transform');

        $dataTransformer = $this->getDataTransformer();
        $this->assertSame(
            ['foo' => 'bar', 'bar' => 'baz'],
            $dataTransformer->transform('json', ['foo' => 'bar', 'bar' => 'baz'])
        );
    }

    public function testShouldTransformData(): void
    {
        $this->fooDataTransformer->expects($this->once())->method('support')->with('json')->willReturn(false);
        $this->fooDataTransformer->expects($this->never())->method('transform');

        $this->barDataTransformer->expects($this->once())->method('support')->with('json')->willReturn(true);
        $this->barDataTransformer
            ->expects($this->once())
            ->method('transform')
            ->with([])
            ->willReturn([
                ['foo' => 'bar'],
                ['bar' => 'baz'],
            ])
        ;

        $dataTransformer = $this->getDataTransformer();
        $this->assertSame(
            [
                ['foo' => 'bar'],
                ['bar' => 'baz'],
            ],
            $dataTransformer->transform('json', [])
        );
    }

    private function getDataTransformer(): DataTransformer
    {
        return new DataTransformer([$this->fooDataTransformer, $this->barDataTransformer]);
    }
}
