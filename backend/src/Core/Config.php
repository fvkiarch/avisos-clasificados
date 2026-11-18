<?php

declare(strict_types=1);

final class Config
{
    public function __construct(private readonly array $items)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cur = $this->items;

        foreach (explode('.', $key) as $seg) {
            if (!is_array($cur) || !array_key_exists($seg, $cur)) {
                return $default;
            }
            $cur = $cur[$seg];
        }

        return $cur;
    }
}
