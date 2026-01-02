<?php

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Ivanstan\SymfonySupport\Attributes\Api;
use Ivanstan\SymfonySupport\Normalizer\EntityApiNormalizer;
use Ivanstan\SymfonySupport\Services\Util\DoctrineUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityApiNormalizerTest extends TestCase
{
    private EntityManagerInterface $em;
    private UrlGeneratorInterface $router;
    private NormalizerInterface $normalizer;
    private DoctrineUtil $util;
    private EntityApiNormalizer $entityNormalizer;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);
        $this->util = $this->createMock(DoctrineUtil::class);

        $this->entityNormalizer = new EntityApiNormalizer(
            $this->em,
            $this->router,
            $this->util
        );
        $this->entityNormalizer->setNormalizer($this->normalizer);
    }

    public function testNormalizeReturnsHydraContextAndEntityData(): void
    {
        $entity = new TestEntity(123, 'Test Name');
        $metadata = $this->createEntityMetadata(TestEntity::class, 'id', []);

        $this->em->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->normalizer->method('normalize')
            ->with($entity)
            ->willReturn(['id' => 123, 'name' => 'Test Name']);

        $result = $this->entityNormalizer->normalize($entity);

        self::assertArrayHasKey('@context', $result);
        self::assertEquals('https://www.w3.org/ns/hydra/context.jsonld', $result['@context']);
        self::assertArrayHasKey('@type', $result);
        self::assertEquals('TestEntity', $result['@type']);
        self::assertEquals(123, $result['id']);
        self::assertEquals('Test Name', $result['name']);
    }

    public function testNormalizeIncludesEntityUrlWhenRouteConfigured(): void
    {
        $entity = new TestEntity(42, 'Test');
        $metadata = $this->createEntityMetadata(
            TestEntity::class,
            'id',
            ['routes' => ['get' => 'api_test_entity_get']]
        );

        $this->em->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->router->method('generate')
            ->with('api_test_entity_get', ['id' => 42], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/api/test-entity/42');

        $this->normalizer->method('normalize')
            ->willReturn(['id' => 42, 'name' => 'Test']);

        $result = $this->entityNormalizer->normalize($entity);

        self::assertEquals('https://example.com/api/test-entity/42', $result['@id']);
    }

    public function testNormalizeReturnsNullIdWhenNoRouteConfigured(): void
    {
        $entity = new TestEntityWithoutRoutes(1, 'Test');
        $metadata = $this->createEntityMetadata(TestEntityWithoutRoutes::class, 'id', []);

        $this->em->method('getClassMetadata')
            ->with(TestEntityWithoutRoutes::class)
            ->willReturn($metadata);

        $this->normalizer->method('normalize')
            ->willReturn(['id' => 1, 'name' => 'Test']);

        $result = $this->entityNormalizer->normalize($entity);

        self::assertNull($result['@id']);
    }

    public function testNormalizeIncludesMetaWhenRequested(): void
    {
        $entity = new TestEntity(1, 'Test');
        $metadata = $this->createEntityMetadata(TestEntity::class, 'id', [], [
            'id' => ['type' => 'integer', 'nullable' => false],
            'name' => ['type' => 'string', 'nullable' => false],
        ]);

        $this->em->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->normalizer->method('normalize')
            ->willReturn(['id' => 1, 'name' => 'Test']);

        $result = $this->entityNormalizer->normalize($entity, null, ['meta' => true]);

        self::assertArrayHasKey('@meta', $result);
        self::assertArrayHasKey('id', $result['@meta']);
        self::assertArrayHasKey('name', $result['@meta']);
    }

    public function testNormalizeExcludesMetaByDefault(): void
    {
        $entity = new TestEntity(1, 'Test');
        $metadata = $this->createEntityMetadata(TestEntity::class, 'id', []);

        $this->em->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->normalizer->method('normalize')
            ->willReturn(['id' => 1, 'name' => 'Test']);

        $result = $this->entityNormalizer->normalize($entity);

        self::assertArrayNotHasKey('@meta', $result);
    }

    public function testSupportsNormalizationReturnsTrueForDoctrineEntity(): void
    {
        $entity = new TestEntity(1, 'Test');

        $this->util->method('isDoctrineEntity')
            ->with(TestEntity::class)
            ->willReturn(true);

        $result = $this->entityNormalizer->supportsNormalization($entity);

        self::assertTrue($result);
    }

    public function testSupportsNormalizationReturnsFalseForNonDoctrineEntity(): void
    {
        $object = new \stdClass();

        $this->util->method('isDoctrineEntity')
            ->with(\stdClass::class)
            ->willReturn(false);

        $result = $this->entityNormalizer->supportsNormalization($object);

        self::assertFalse($result);
    }

    public function testSupportsNormalizationReturnsFalseForNonObject(): void
    {
        self::assertFalse($this->entityNormalizer->supportsNormalization('string'));
        self::assertFalse($this->entityNormalizer->supportsNormalization(123));
        self::assertFalse($this->entityNormalizer->supportsNormalization(['array']));
        self::assertFalse($this->entityNormalizer->supportsNormalization(null));
    }

    public function testSupportsNormalizationReturnsFalseWhenAlreadyNormalizing(): void
    {
        $entity = new TestEntity(1, 'Test');

        $this->util->method('isDoctrineEntity')
            ->with(TestEntity::class)
            ->willReturn(true);

        // Without context flag, should return true
        self::assertTrue($this->entityNormalizer->supportsNormalization($entity));

        // With context flag set (during delegation), should return false to prevent recursion
        self::assertFalse($this->entityNormalizer->supportsNormalization(
            $entity,
            null,
            [EntityApiNormalizer::class => true]
        ));
    }

    public function testGetSupportedTypes(): void
    {
        $types = $this->entityNormalizer->getSupportedTypes(null);

        self::assertArrayHasKey('*', $types);
        self::assertFalse($types['*']);
    }

    public function testGetRequestedMetaFiltersToNormalizedFields(): void
    {
        $entity = new TestEntity(1, 'Test');
        $metadata = $this->createEntityMetadata(TestEntity::class, 'id', [], [
            'id' => ['type' => 'integer', 'nullable' => false],
            'name' => ['type' => 'string', 'nullable' => false],
            'secret' => ['type' => 'string', 'nullable' => true],
        ]);

        $this->em->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        // Only id and name are normalized, secret is excluded
        $this->normalizer->method('normalize')
            ->willReturn(['id' => 1, 'name' => 'Test']);

        $result = $this->entityNormalizer->normalize($entity, null, ['meta' => true]);

        self::assertArrayHasKey('id', $result['@meta']);
        self::assertArrayHasKey('name', $result['@meta']);
        self::assertArrayNotHasKey('secret', $result['@meta']);
    }

    public function testNormalizeHandlesRouterException(): void
    {
        $entity = new TestEntity(1, 'Test');
        $metadata = $this->createEntityMetadata(
            TestEntity::class,
            'id',
            ['routes' => ['get' => 'api_test_entity_get']]
        );

        $this->em->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->router->method('generate')
            ->willThrowException(new \Exception('Route not found'));

        $this->normalizer->method('normalize')
            ->willReturn(['id' => 1, 'name' => 'Test']);

        $result = $this->entityNormalizer->normalize($entity);

        self::assertNull($result['@id']);
    }

    /**
     * Creates a mock ClassMetadata for testing.
     */
    private function createEntityMetadata(
        string $className,
        string $identifier,
        array $apiOptions,
        array $fields = []
    ): ClassMetadata {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn($className);

        $metadata->identifier = [$identifier];

        // Create a mock reflection class with Api attribute
        $reflClass = new \ReflectionClass($className);
        $metadata->reflClass = $reflClass;

        // Mock getFieldNames and getFieldMapping for fields
        $fieldNames = array_keys($fields);
        $metadata->method('getFieldNames')->willReturn($fieldNames);
        $metadata->method('getFieldMapping')->willReturnCallback(
            function (string $fieldName) use ($fields): FieldMapping {
                $fieldData = $fields[$fieldName] ?? [];
                $mapping = new FieldMapping(
                    type: $fieldData['type'] ?? 'string',
                    fieldName: $fieldName,
                    columnName: $fieldName,
                );
                $mapping->nullable = $fieldData['nullable'] ?? false;
                return $mapping;
            }
        );
        $metadata->method('getAssociationMappings')->willReturn([]);

        return $metadata;
    }
}

/**
 * Test entity class for EntityApiNormalizer tests.
 */
#[Api(['routes' => ['get' => 'api_test_entity_get']])]
class TestEntity
{
    public function __construct(
        private int $id,
        private string $name
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

/**
 * Test entity class without Api routes configured.
 */
class TestEntityWithoutRoutes
{
    public function __construct(
        private int $id,
        private string $name
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

