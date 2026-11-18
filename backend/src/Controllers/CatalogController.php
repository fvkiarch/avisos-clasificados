<?php

declare(strict_types=1);

final class CatalogController
{
    public function __construct(private readonly CatalogRepositoryInterface $catalog)
    {
    }

    public function index(): Response
    {
        return Response::json([
            'data' => [
                'categories' => $this->catalog->categories(),
                'property_types' => $this->catalog->propertyTypes(),
            ],
        ]);
    }
}
