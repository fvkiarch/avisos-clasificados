<?php

declare(strict_types=1);

final class ListingService
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly ListingValidator $validator,
        private readonly CurrentUser $user,
    ) {
    }

    public function search(?string $category, ?string $text, int $page, int $perPage, bool $onlyMine = false): array
    {
        $authorId = $onlyMine ? ($this->user->id() ?? throw new UnauthorizedException()) : null;
        $category = in_array($category, Category::ALL, true) ? $category : null;
        $text = $text !== null && preg_match('/^.{1,100}/us', $text, $match) ? $match[0] : null;
        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        
        
        $items = $this->listings->search($category, $text, $authorId, $perPage + 1, ($page - 1) * $perPage);
        $hasMore = count($items) > $perPage;

        return ['items' => array_slice($items, 0, $perPage), 'has_more' => $hasMore];
    }

    public function stats(): array
    {
        return $this->listings->stats();
    }

    public function get(int $id): Listing
    {
        return $this->listings->find($id) ?? throw new NotFoundException('El aviso no existe');
    }

    public function publish(array $input): Listing
    {
        $authorId = $this->user->id() ?? throw new UnauthorizedException();

        if (!$this->user->can(Permission::LISTING_CREATE)) {
            throw new ForbiddenException('Sin permiso: listing.create');
        }

        $types = array_column($this->catalog->propertyTypes(), 'id');
        [$data, $errors] = $this->validator->validate($input, $types);

        if ($errors) {
            throw new ValidationException($errors);
        }

        if ($data['highlighted'] && !$this->user->can(Permission::LISTING_HIGHLIGHT)) {
            throw new ForbiddenException('Sin permiso: listing.highlight');
        }

        $id = $this->listings->publish($authorId, $data['category'], $data['title'], $data['highlighted'], $data);

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $authorId = $this->user->id() ?? throw new UnauthorizedException();
        $this->listings->delete($id, $authorId);
    }

    public function canDelete(Listing $listing): bool
    {
        return $this->user->can(Permission::LISTING_DELETE_ANY)
            || ($this->user->can(Permission::LISTING_DELETE_OWN) && $listing->authorId === $this->user->id());
    }
}
