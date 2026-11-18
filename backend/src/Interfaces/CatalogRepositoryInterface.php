<?php

declare(strict_types=1);

interface CatalogRepositoryInterface
{
    public function categories(): array;

    public function propertyTypes(): array;
}
