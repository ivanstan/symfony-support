<?php

namespace Ivanstan\SymfonySupport\Services\Util;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class ClassUtil
{
    public static function getClassNameFromFqn(string $input): string
    {
        $pos = strrpos($input, '\\');

        return $pos === false ? $input : substr($input, $pos + 1);
    }

    public static function camelCaseToSnakeCase(string $input): string
    {
        return str_replace('_', '-', (new CamelCaseToSnakeCaseNameConverter())->normalize($input));
    }

    public static function snakeCaseToCamelCase(string $input, string $separator = '-'): string
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }
}
