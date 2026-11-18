<?php

declare(strict_types=1);

final class UserController
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function index(): Response
    {
        return Response::json(['data' => $this->service->listAll()]);
    }

    public function store(Request $request): Response
    {
        $id = $this->service->create($request->all());
        return Response::json(['data' => ['id' => $id]], 201);
    }

    public function destroy(int $id): Response
    {
        $this->service->delete($id);
        return Response::noContent();
    }
}