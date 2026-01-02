<?php

namespace Ivanstan\SymfonySupport\Normalizer;

use Ivanstan\SymfonySupport\Services\QueryBuilderPaginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class CollectionApiNormalizer extends HydraApiNormalizer implements NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(protected RouterInterface $router, protected RequestStack $stack)
    {
    }

    /**
     * @param QueryBuilderPaginator $object
     *
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $request = $this->getRequest($context);

        $data = parent::normalize($object, $format, $context);

        return array_merge(
            $data,
            [
                '@id' => $this->router->generate($request->attributes->get('_route'), [], UrlGeneratorInterface::ABSOLUTE_URL),
                '@type' => $object->getType(),
                'totalItems' => $object->getTotal(),
                'member' => $this->normalizer->normalize($object->getCurrentPageResult(), $format, $context),
                'parameters' => array_merge($request->request->all(), $request->query->all()),
                'view' => $object->getView($request, $this->router),
            ]
        );
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof QueryBuilderPaginator;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            QueryBuilderPaginator::class => true,
        ];
    }
}
