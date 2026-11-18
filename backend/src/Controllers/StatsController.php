<?php

declare(strict_types=1);

final class StatsController
{
    public function __construct(private readonly ListingService $listings)
    {
    }

    public function index(): Response
    {
        return Response::json(['data' => $this->listings->stats()]);
    }
}
