<?php

use Ivanstan\SymfonySupport\Services\Util\ClassUtil;
use PHPUnit\Framework\TestCase;

class ClassUtilTest extends TestCase
{
    public function testGetClassNameFromFqn(): void
    {
        self::assertEquals('MyClass', ClassUtil::getClassNameFromFqn('App\\Entity\\MyClass'));
        self::assertEquals('User', ClassUtil::getClassNameFromFqn('App\\Model\\User'));
        self::assertEquals('SimpleClass', ClassUtil::getClassNameFromFqn('SimpleClass'));
    }

    public function testCamelCaseToSnakeCase(): void
    {
        self::assertEquals('my-class-name', ClassUtil::camelCaseToSnakeCase('MyClassName'));
        self::assertEquals('user-profile', ClassUtil::camelCaseToSnakeCase('UserProfile'));
        self::assertEquals('simple', ClassUtil::camelCaseToSnakeCase('Simple'));
        self::assertEquals('api-entity-metadata', ClassUtil::camelCaseToSnakeCase('ApiEntityMetadata'));
    }

    public function testSnakeCaseToCamelCase(): void
    {
        self::assertEquals('MyClassName', ClassUtil::snakeCaseToCamelCase('my-class-name'));
        self::assertEquals('UserProfile', ClassUtil::snakeCaseToCamelCase('user-profile'));
        self::assertEquals('Simple', ClassUtil::snakeCaseToCamelCase('simple'));
    }

    public function testSnakeCaseToCamelCaseWithCustomSeparator(): void
    {
        self::assertEquals('MyClassName', ClassUtil::snakeCaseToCamelCase('my_class_name', '_'));
        self::assertEquals('UserProfile', ClassUtil::snakeCaseToCamelCase('user_profile', '_'));
    }
}





