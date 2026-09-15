<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/** @psalm-api */
final readonly class ItemList
{
    /**
     * @param list<ListItem> $itemListElement
     */
    public function __construct(
        private int $numberOfItems,
        private string $itemListOrder,
        #[ApiProperty(readableLink: true, types: ['https://schema.org/ListItem'])]
        private array $itemListElement,
    ) {
    }

    public function getNumberOfItems(): int
    {
        return $this->numberOfItems;
    }

    public function getItemListOrder(): string
    {
        return $this->itemListOrder;
    }

    /** @return list<ListItem> */
    public function getItemListElement(): array
    {
        return $this->itemListElement;
    }
}
