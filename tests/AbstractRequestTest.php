<?php

use Ivanstan\SymfonySupport\Request\AbstractRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class AbstractRequestTest extends TestCase
{
    public function testValidateReturnsEmptyViolationList(): void
    {
        $request = AbstractRequest::create('/test');
        $validator = Validation::createValidator();

        $violations = $request->validate($validator);

        self::assertCount(0, $violations);
    }

    public function testExtendsSymfonyRequest(): void
    {
        $request = AbstractRequest::create('/test?foo=bar');

        self::assertEquals('bar', $request->query->get('foo'));
        self::assertEquals('/test', $request->getPathInfo());
    }
}





