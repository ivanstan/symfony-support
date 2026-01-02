<?php

namespace Ivanstan\SymfonySupport\Normalizer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HydraApiNormalizer implements NormalizerInterface
{
    protected const HYDRA_CONTEXT = 'https://www.w3.org/ns/hydra/context.jsonld';

    protected RequestStack $stack;

    public function getRequest(array $context = []): Request
    {
        return $context['request'] ?? $this->stack->getCurrentRequest();
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            '@context' => self::HYDRA_CONTEXT,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return ($context['hydra'] ?? null) === true;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
        ];
    }
}
