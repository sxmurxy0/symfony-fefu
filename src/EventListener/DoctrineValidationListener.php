<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class DoctrineValidationListener
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->validate($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->validate($args->getObject());
    }

    private function validate(object $entity): void
    {
        $errors = $this->validator->validate($entity);

        if (count($errors) > 0) {
            throw new ValidationException((string) $errors);
        }
    }
}
