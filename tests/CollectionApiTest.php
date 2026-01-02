<?php

use Ivanstan\SymfonySupport\Enum\SortDirectionEnum;
use Ivanstan\SymfonySupport\Request\CollectionRequest;
use PHPUnit\Framework\TestCase;

class CollectionApiTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $request = CollectionRequest::create('/api/items');

        self::assertEquals(1, $request->getPage());
        self::assertEquals(20, $request->getPageSize());
        self::assertEquals(SortDirectionEnum::DESCENDING, $request->getSortDirection());
        self::assertNull($request->getSort());
        self::assertNull($request->getSearch());
    }

    public function testCustomPage(): void
    {
        $request = CollectionRequest::create('/api/items?page=5');

        self::assertEquals(5, $request->getPage());
    }

    public function testCustomPageSize(): void
    {
        $request = CollectionRequest::create('/api/items?page-size=50');

        self::assertEquals(50, $request->getPageSize());
    }

    public function testMaxPageSizeEnforced(): void
    {
        $request = CollectionRequest::create('/api/items?page-size=500');

        self::assertEquals(100, $request->getPageSize());
    }

    public function testSortDirection(): void
    {
        $request = CollectionRequest::create('/api/items?sort-dir=asc');

        self::assertEquals(SortDirectionEnum::ASCENDING, $request->getSortDirection());
    }

    public function testSortField(): void
    {
        $request = CollectionRequest::create('/api/items?sort=name');

        self::assertEquals('name', $request->getSort());
    }

    public function testSortFieldWithDefault(): void
    {
        $request = CollectionRequest::create('/api/items');

        self::assertEquals('id', $request->getSort('id'));
    }

    public function testSearch(): void
    {
        $request = CollectionRequest::create('/api/items?search=test+query');

        self::assertEquals('test query', $request->getSearch());
    }
}
