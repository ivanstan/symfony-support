<?php

namespace Ivanstan\SymfonySupport\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Ivanstan\SymfonySupport\Services\ApiEntityMetadata;
use Ivanstan\SymfonySupport\Services\Util\DoctrineUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class EntityApiNormalizer extends HydraApiNormalizer implements NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        protected EntityManagerInterface $em,
        protected UrlGeneratorInterface $router,
        protected DoctrineUtil $util,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $data = parent::normalize($object, $format, $context);

        $metadata = new ApiEntityMetadata($this->em->getClassMetadata(get_class($object)));

        $data['@id'] = $this->getEntityUrl($object, $metadata);
        $data['@type'] = $metadata->getName();

        // Mark as already normalized to prevent infinite recursion
        $context[self::class] = true;
        $normalized = $this->normalizer->normalize($object, $format, $context);

        if (($context['meta'] ?? false) === true) {
            $data['@meta'] = $this->getRequestedMeta($metadata, $normalized);
        }

        return array_merge($data, $normalized);
    }

    protected function getEntityUrl(object $entity, ApiEntityMetadata $meta): ?string
    {
        $options = $meta->getApiOptions();

        if (!isset($options['routes']['get'])) {
            return null;
        }

        $identifierGetter = 'get' . ucfirst($meta->getIdentifier());

        if (!method_exists($entity, $identifierGetter)) {
            return null;
        }

        try {
            return $this->router->generate(
                $options['routes']['get'],
                [
                    $meta->getIdentifier() => $entity->$identifierGetter(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Compares normalized entity and available fields only to return meta for fields that are
     * present in normalized entity. We want to avoid exposing filed information for fields ignored fields.
     */
    public function getRequestedMeta(ApiEntityMetadata $metadata, array $normalized): array
    {
        $meta = $metadata->getFields();

        $data = [];
        foreach ($normalized as $fieldName => $item) {
            if (isset($meta[$fieldName])) {
                $data[$fieldName] = $meta[$fieldName];
            }
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        // Prevent infinite recursion when delegating to the serializer
        if (isset($context[self::class])) {
            return false;
        }

        if (!is_object($data)) {
            return false;
        }

        return $this->util->isDoctrineEntity(get_class($data));
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
        ];
    }
}
