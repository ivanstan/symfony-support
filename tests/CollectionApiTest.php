<?php

use Ivanstan\SymfonySupport\Enum\SortDirectionEnum;
use Ivanstan\SymfonySupport\Request\CollectionRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

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

    public function testValidationPassesWithValidParams(): void
    {
        $request = CollectionRequest::create('/api/items?page=1&page-size=20&sort-dir=asc');
        $validator = Validation::createValidator();

        $violations = $request->validate($validator);

        self::assertCount(0, $violations);
    }

    public function testValidationFailsWithInvalidPage(): void
    {
        $request = CollectionRequest::create('/api/items?page=0');
        $validator = Validation::createValidator();

        $violations = $request->validate($validator);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testValidationFailsWithNegativePageSize(): void
    {
        $request = CollectionRequest::create('/api/items?page-size=-5');
        $validator = Validation::createValidator();

        $violations = $request->validate($validator);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testValidationFailsWithExcessivePageSize(): void
    {
        $request = CollectionRequest::create('/api/items?page-size=150');
        $validator = Validation::createValidator();

        $violations = $request->validate($validator);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testValidationFailsWithInvalidSortDirection(): void
    {
        $request = CollectionRequest::create('/api/items?sort-dir=invalid');
        $validator = Validation::createValidator();

        $violations = $request->validate($validator);

        self::assertGreaterThan(0, $violations->count());
    }
}
