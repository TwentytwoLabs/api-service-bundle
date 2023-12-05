<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Exception;

use TwentytwoLabs\ApiValidator\Validator\ConstraintViolation;

class ConstraintViolations extends ApiServiceError
{
    /**
     * @var array|ConstraintViolation[]
     */
    private array $violations;

    /**
     * @param ConstraintViolation[] $violations
     */
    public function __construct(array $violations)
    {
        $this->violations = $violations;
        parent::__construct($this->__toString());
    }

    /**
     * @return array|ConstraintViolation[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    public function __toString(): string
    {
        $message = "Request constraint violations:\n";
        foreach ($this->violations as $violation) {
            $message .= sprintf(
                "[property]: %s\n[message]: %s\n[constraint]: %s\n[location]: %s\n\n",
                $violation->getProperty(),
                $violation->getMessage(),
                $violation->getConstraint(),
                $violation->getLocation()
            );
        }

        return $message;
    }
}
