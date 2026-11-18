<?php

declare(strict_types=1);

final class PdoCatalogRepository implements CatalogRepositoryInterface
{
    public function __construct(private readonly ConnectionResolver $db)
    {
    }

    public function categories(): array
    {
        return $this->db->current()
            ->query('SELECT nombre FROM categoria ORDER BY id_categoria')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function propertyTypes(): array
    {
        $rows = $this->db->current()
            ->query('SELECT id_tipo_inmueble, nombre FROM tipo_inmueble ORDER BY nombre')
            ->fetchAll();

        return array_map(
            fn (array $row) => ['id' => (int) $row['id_tipo_inmueble'], 'name' => $row['nombre']],
            $rows,
        );
    }
}
