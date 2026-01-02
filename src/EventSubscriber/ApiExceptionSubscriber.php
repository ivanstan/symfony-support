<?php

namespace Ivanstan\SymfonySupport\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    protected string $env;
    protected array $paths = [];

    public function __construct(protected ParameterBagInterface $parameters)
    {
        $this->env = strtolower((string)$parameters->get('kernel.environment'));

        if ($this->parameters->has('symfony_support.exception_subscriber')) {
            $this->paths = $this->parameters->get('symfony_support.exception_subscriber')['paths'];
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->shouldIntercept($event->getRequest()->getPathInfo())) {
            return;
        }

        $exception = $event->getThrowable();

        $response = ['message' => 'Unspecified error'];

        if ($this->isDev()) {
            $response['message'] = $exception->getMessage();
            $response['exception'] = $this->throwableToArray($exception);
        }

        if ($exception instanceof HttpException) {
            $response['message'] = $exception->getMessage();

            $this->setJsonResponse($event, $response);

            return;
        }

        $this->setJsonResponse($event, $response);
    }

    protected function shouldIntercept(string $currentPath): bool
    {
        foreach ($this->paths as $path) {
            if (str_contains($currentPath, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function isDev(): bool
    {
        return in_array($this->env, ['dev', 'test', 'testing'], true);
    }

    protected function throwableToArray(\Throwable $throwable): array
    {
        return [
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile() . ':' . $throwable->getLine(),
            'message' => $throwable->getMessage(),
            'trace' => $throwable->getTrace(),
        ];
    }

    protected function setJsonResponse(ExceptionEvent $event, array $response): void
    {
        $event->setResponse(
            new JsonResponse(
                [
                    'response' => $response,
                ],
                Response::HTTP_OK,
            )
        );
    }
}
