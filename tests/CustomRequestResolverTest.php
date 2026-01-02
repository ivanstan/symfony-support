<?php

use Ivanstan\SymfonySupport\Request\AbstractRequest;
use Ivanstan\SymfonySupport\Services\CustomRequestResolver;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;

class CustomRequestResolverTest extends TestCase
{
    private ValidatorInterface $validator;
    private ContainerInterface $container;
    private CustomRequestResolver $resolver;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->resolver = new CustomRequestResolver($this->validator, $this->container);
    }

    public function testReturnsEmptyArrayForNonRequestType(): void
    {
        $request = Request::create('/test');
        $argument = new ArgumentMetadata('arg', \stdClass::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertEmpty($result);
    }

    public function testReturnsEmptyArrayForNullType(): void
    {
        $request = Request::create('/test');
        $argument = new ArgumentMetadata('arg', null, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertEmpty($result);
    }

    public function testResolvesRequestClass(): void
    {
        $request = Request::create('/test', 'GET', ['foo' => 'bar']);
        $argument = new ArgumentMetadata('request', Request::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(Request::class, $result[0]);
    }

    public function testResolvesAbstractRequestSubclass(): void
    {
        $request = Request::create('/test', 'GET', ['foo' => 'bar']);
        $argument = new ArgumentMetadata('request', AbstractRequest::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(AbstractRequest::class, $result[0]);
    }

    public function testCopiesRequestData(): void
    {
        $request = Request::create(
            uri: '/test?query=value',
            method: 'POST',
            parameters: ['post' => 'data'],
            cookies: ['cookie' => 'value'],
            server: ['HTTP_HOST' => 'example.com'],
            content: 'raw content'
        );
        $request->attributes->set('attr', 'value');

        $argument = new ArgumentMetadata('request', AbstractRequest::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        /** @var AbstractRequest $resolvedRequest */
        $resolvedRequest = $result[0];

        self::assertEquals('value', $resolvedRequest->query->get('query'));
        self::assertEquals('data', $resolvedRequest->request->get('post'));
        self::assertEquals('value', $resolvedRequest->cookies->get('cookie'));
        self::assertEquals('value', $resolvedRequest->attributes->get('attr'));
        self::assertEquals('raw content', $resolvedRequest->getContent());
    }

    public function testThrowsBadRequestOnValidationFailure(): void
    {
        $request = Request::create('/test');
        $argument = new ArgumentMetadata('request', TestValidatedRequest::class, false, false, null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('field This value is invalid.');

        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testPassesValidationWithNoViolations(): void
    {
        $request = Request::create('/test');
        $argument = new ArgumentMetadata('request', TestValidRequest::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(TestValidRequest::class, $result[0]);
    }

    public function testCallsRequiredMethods(): void
    {
        $mockService = new \stdClass();
        $mockService->called = false;

        $this->container
            ->method('get')
            ->with(\stdClass::class)
            ->willReturn($mockService);

        $request = Request::create('/test');
        $argument = new ArgumentMetadata('request', TestRequestWithRequired::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        /** @var TestRequestWithRequired $resolvedRequest */
        $resolvedRequest = $result[0];

        self::assertTrue($resolvedRequest->wasInjected);
        self::assertSame($mockService, $resolvedRequest->injectedService);
    }
}

/**
 * Test request class that fails validation.
 */
class TestValidatedRequest extends AbstractRequest
{
    public function validate(\Symfony\Component\Validator\Validator\ValidatorInterface $validator): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return new ConstraintViolationList([
            new ConstraintViolation(
                message: 'This value is invalid.',
                messageTemplate: 'This value is invalid.',
                parameters: [],
                root: $this,
                propertyPath: 'field',
                invalidValue: null
            )
        ]);
    }
}

/**
 * Test request class that passes validation.
 */
class TestValidRequest extends AbstractRequest
{
    public function validate(\Symfony\Component\Validator\Validator\ValidatorInterface $validator): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return new ConstraintViolationList();
    }
}

/**
 * Test request class with #[Required] method.
 */
class TestRequestWithRequired extends AbstractRequest
{
    public bool $wasInjected = false;
    public mixed $injectedService = null;

    #[Required]
    public function injectService(\stdClass $service): void
    {
        $this->wasInjected = true;
        $this->injectedService = $service;
    }
}


