<?php

namespace Ivanstan\SymfonySupport\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AbstractRequest extends Request implements ValidatedRequestInterface
{
    public function validate(ValidatorInterface $validator): ConstraintViolationListInterface
    {
        return new ConstraintViolationList();
    }
}
