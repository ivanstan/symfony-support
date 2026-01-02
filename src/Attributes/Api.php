<?php

namespace Ivanstan\SymfonySupport\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Api
{
    public function __construct(public array $context = [])
    {
    }
}
