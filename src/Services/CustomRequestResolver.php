<?php

namespace Ivanstan\SymfonySupport\Services;

use Ivanstan\SymfonySupport\Request\AbstractRequest;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;

class CustomRequestResolver implements ValueResolverInterface
{
    public function __construct(protected ValidatorInterface $validator, protected ContainerInterface $container)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Return empty array if this resolver doesn't support the argument
        if (Request::class !== $argument->getType() && !is_subclass_of($argument->getType(), Request::class)) {
            return [];
        }
        $class = $argument->getType();

        /** @var AbstractRequest $customRequest */
        $customRequest = new $class(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent(),
        );

        // Call #[Required] methods.
        foreach ((new ReflectionClass($customRequest::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if ($attribute->getName() === Required::class) {

                    $params = [];
                    foreach ($method->getParameters() as $parameter) {
                        $params[] = $this->container->get($parameter->getType()->getName());
                    }
                    call_user_func_array([$customRequest, $method->getName()], $params);
                }
            }
        }

        // Validate
        if (method_exists($customRequest, 'validate')) {
            $violations = $customRequest->validate($this->validator);

            /**
             * ToDo: Messages should be combined and well formatted.
             */
            if ($violations !== null && $violations->has(0)) {
                $violation = $violations->get(0);

                $message = $violation->getPropertyPath() . ' ' . $violation->getMessage();

                throw new BadRequestHttpException($message);
            }
        }

        yield $customRequest;
    }
}
