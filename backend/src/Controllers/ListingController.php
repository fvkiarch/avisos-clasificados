<?php

declare(strict_types=1);

final class ListingController
{
    public function __construct(private readonly ListingService $listings)
    {
    }

    public function index(Request $request): Response
    {
        return $this->paginated($request, false);
    }
    
    public function mine(Request $request): Response
    {
        return $this->paginated($request, true);
    }

    private function paginated(Request $request, bool $onlyMine): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $q = trim((string) $request->query('q', ''));

        $result = $this->listings->search(
            $request->query('category'),
            $q !== '' ? $q : null,
            $page,
            $perPage,
            $onlyMine,
        );

        return Response::json([
            'data' => array_map(fn (Listing $listing) => $this->present($listing), $result['items']),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => $result['has_more']],
        ]);
    }

    public function show(int $id): Response
    {
        return Response::json(['data' => $this->present($this->listings->get($id))]);
    }

    public function store(Request $request): Response
    {
        $listing = $this->listings->publish($request->all());

        return Response::json(
            ['data' => $this->present($listing)],
            201,
            ['Location' => '/api/v1/listings/' . $listing->id],
        );
    }

    public function destroy(int $id): Response
    {
        $this->listings->delete($id);

        return Response::noContent();
    }

    private function present(Listing $listing): array
    {
        return $listing->toArray() + ['can_delete' => $this->listings->canDelete($listing)];
    }
}
