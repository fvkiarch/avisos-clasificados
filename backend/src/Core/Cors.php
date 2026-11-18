<?php

declare(strict_types=1);

final class Cors
{
    public function __construct(private readonly Config $config)
    {
    }

    public function headersFor(Request $request): array
    {
        $origin = $request->header('origin');

        if ($origin === null || !in_array($origin, (array) $this->config->get('cors.origins', []), true)) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin'  => $origin,
            'Vary'                         => 'Origin',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Max-Age'       => '600',
        ];
    }
}
