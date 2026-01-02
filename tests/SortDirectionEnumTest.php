<?php

use Ivanstan\SymfonySupport\Enum\SortDirectionEnum;
use PHPUnit\Framework\TestCase;

class SortDirectionEnumTest extends TestCase
{
    public function testAscendingConstant(): void
    {
        self::assertEquals('asc', SortDirectionEnum::ASCENDING);
    }

    public function testDescendingConstant(): void
    {
        self::assertEquals('desc', SortDirectionEnum::DESCENDING);
    }

    public function testConstantsAreDifferent(): void
    {
        self::assertNotEquals(SortDirectionEnum::ASCENDING, SortDirectionEnum::DESCENDING);
    }
}



