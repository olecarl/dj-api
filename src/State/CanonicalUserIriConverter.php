<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;

/** @psalm-api */
final readonly class CanonicalUserIriConverter implements IriConverterInterface
{
    public function __construct(
        private IriConverterInterface $decorated,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    #[\Override]
    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object
    {
        return $this->decorated->getResourceFromIri($iri, $context, $operation);
    }

    #[\Override]
    public function getIriFromResource(
        object|string $resource,
        int $referenceType = UrlGeneratorInterface::ABS_PATH,
        ?Operation $operation = null,
        array $context = [],
    ): ?string {
        if (null === $operation || 'me' !== $operation->getName()) {
            return $this->decorated->getIriFromResource($resource, $referenceType, $operation, $context);
        }

        $resourceClass = $operation->getClass();
        if (null === $resourceClass) {
            return $this->decorated->getIriFromResource($resource, $referenceType, $operation, $context);
        }

        $canonicalOperation = $this->resourceMetadataCollectionFactory
            ->create($resourceClass)
            ->getOperation(null, false, true);

        return $this->decorated->getIriFromResource($resource, $referenceType, $canonicalOperation, $context);
    }
}
