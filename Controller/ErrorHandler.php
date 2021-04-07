<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use UniMethod\JsonapiMapper\External\Error;

trait ErrorHandler
{
    /**
     * @param object $object
     * @return Error[]
     */
    protected function validate(object $object): array
    {
        $errors = $this->validationService->validate($object);

        if ($this->pathResolver->getAlias() !== $this->getRawArray()['data']['type']) {
            $errors[] = new Error(
                '400',
                sprintf('Please provide equal types in body "%s" and route "%s"', $this->getRawArray()['data']['type'], $this->pathResolver->getAlias()),
                'Non equal types',
                null
            );
        }

        return $errors;
    }

    /**
     * @param array $errors
     * @return int
     */
    protected function getStatusByErrors(array $errors): int
    {
        $errorCodes = array_values(array_unique(array_map(fn (Error $error) => $error->getStatus(), $errors)));
        return (count($errorCodes) === 1) ? (int) $errorCodes[0] : 400;
    }
}
