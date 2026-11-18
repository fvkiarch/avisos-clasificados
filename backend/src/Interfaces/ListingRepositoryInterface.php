<?php

declare(strict_types=1);

interface ListingRepositoryInterface
{
    public function search(?string $category, ?string $text, ?int $authorId, int $limit, int $offset): array;

    public function stats(): array;

    public function find(int $id): ?Listing;


    public function publish(int $authorId, string $category, string $title, bool $highlighted, array $data): int;

    public function delete(int $id, int $authorId): void;
}
