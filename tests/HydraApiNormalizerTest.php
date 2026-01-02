<?php

use Ivanstan\SymfonySupport\Normalizer\HydraApiNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HydraApiNormalizerTest extends TestCase
{
    public function testNormalizeReturnsHydraContext(): void
    {
        $normalizer = new HydraApiNormalizer();

        $result = $normalizer->normalize(new \stdClass());

        self::assertArrayHasKey('@context', $result);
        self::assertEquals('https://www.w3.org/ns/hydra/context.jsonld', $result['@context']);
    }

    public function testSupportsNormalizationWithHydraContext(): void
    {
        $normalizer = new HydraApiNormalizer();

        self::assertTrue($normalizer->supportsNormalization(new \stdClass(), null, ['hydra' => true]));
    }

    public function testSupportsNormalizationWithoutHydraContext(): void
    {
        $normalizer = new HydraApiNormalizer();

        self::assertFalse($normalizer->supportsNormalization(new \stdClass(), null, []));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass(), null, ['hydra' => false]));
    }

    public function testGetSupportedTypes(): void
    {
        $normalizer = new HydraApiNormalizer();

        $types = $normalizer->getSupportedTypes(null);

        self::assertArrayHasKey('*', $types);
        self::assertFalse($types['*']);
    }

    public function testGetRequestFromContext(): void
    {
        $normalizer = new HydraApiNormalizer();
        $request = Request::create('/test');

        $result = $normalizer->getRequest(['request' => $request]);

        self::assertSame($request, $result);
    }

    public function testGetRequestFromRequestStack(): void
    {
        $request = Request::create('/test');
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $normalizer = new class($requestStack) extends HydraApiNormalizer {
            public function __construct(RequestStack $stack)
            {
                $this->stack = $stack;
            }
        };

        $result = $normalizer->getRequest([]);

        self::assertSame($request, $result);
    }
}





