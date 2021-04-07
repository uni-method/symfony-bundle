<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniMethod\JsonapiMapper\External\Error;

class ValidationService
{
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;

    /**
     * Constructor
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param mixed $item
     * @return Error[]
     */
    public function validate(object $item): array
    {
        $errors = $this->validator->validate($item);
        $output = [];

        if ($errors->count() > 0) {
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $output[] = new Error('422', $error->getMessage(), 'Non valid value ' . $error->getInvalidValue(), $error->getPropertyPath());
            }
        }

        return $output;
    }
}
