<?php

declare(strict_types=1);


final class PdoListingRepository implements ListingRepositoryInterface
{
    private const SELECT = 'SELECT id_aviso, titulo, fecha_creacion, resaltado, id_autor, autor,
                                   categoria, referencia, detalle, monto
                            FROM v_avisos_publicos';

    public function __construct(
        private readonly ConnectionResolver $db,
        private readonly PdoErrorTranslator $errors,
    ) {
    }

    public function search(?string $category, ?string $text, ?int $authorId, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];

        if ($category !== null) {
            $conditions[] = 'categoria = ?';
            $params[] = $category;
        }

        if ($text !== null) {
            $like = '%' . strtr($text, ['|' => '||', '%' => '|%', '_' => '|_']) . '%';
            $conditions[] = "(titulo LIKE ? ESCAPE '|' OR detalle LIKE ? ESCAPE '|')";
            array_push($params, $like, $like);
        }
        
        if ($authorId !== null) {
            $conditions[] = 'id_autor = ?';
            $params[] = $authorId;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $statement = $this->db->current()->prepare(
                self::SELECT . $where
                . ' ORDER BY resaltado DESC, fecha_creacion DESC, id_aviso DESC LIMIT ? OFFSET ?',
            );

            foreach ($params as $index => $value) {
                $statement->bindValue($index + 1, $value);
            }

            $statement->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
            $statement->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
            $statement->execute();

            return array_map([Listing::class, 'fromRow'], $statement->fetchAll());
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }

    public function stats(): array
    {
        try {
            $rows = $this->db->current()->query(
                'SELECT categoria, COUNT(*) AS total, SUM(resaltado) AS highlighted_total
                 FROM v_avisos_publicos
                 GROUP BY categoria',
            )->fetchAll();
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }

        $byCategory = array_fill_keys(Category::ALL, 0);
        $highlighted = 0;

        foreach ($rows as $row) {
            $byCategory[$row['categoria']] = (int) $row['total'];
            $highlighted += (int) $row['highlighted_total'];
        }

        return [
            'total' => array_sum($byCategory),
            'highlighted' => $highlighted,
            'by_category' => $byCategory,
        ];
    }

    public function find(int $id): ?Listing
    {
        try {
            $statement = $this->db->current()->prepare(self::SELECT . ' WHERE id_aviso = ?');
            $statement->execute([$id]);
            $row = $statement->fetch();

            return $row ? Listing::fromRow($row) : null;
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }

    public function publish(int $authorId, string $category, string $title, bool $highlighted, array $data): int
    {
        $pdo = $this->db->current();
        $common = [$authorId, $title, (int) $highlighted];

        [$sql, $extra] = match ($category) {
            Category::JOB => [
                'CALL sp_publicar_laboral(?,?,?,?,?,?,@id)',
                [$data['company'], $data['job_description'], $data['monthly_salary']],
            ],
            Category::PRODUCT => [
                'CALL sp_publicar_producto(?,?,?,?,?,@id)',
                [$data['product_description'], $data['price']],
            ],
            Category::REAL_ESTATE => [
                'CALL sp_publicar_inmobiliario(?,?,?,?,?,?,?,@id)',
                [$data['property_type_id'], $data['location'], $data['area_m2'], $data['price']],
            ],
        };

        try {
            $statement = $pdo->prepare($sql);
            $statement->execute([...$common, ...$extra]);
            $statement->closeCursor();

            return (int) $pdo->query('SELECT @id')->fetchColumn();
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }

    public function delete(int $id, int $authorId): void
    {
        try {
            $statement = $this->db->current()->prepare('CALL sp_eliminar_aviso(?, ?)');
            $statement->execute([$id, $authorId]);
            $statement->closeCursor();
        } catch (PDOException $e) {
            throw $this->errors->translate($e);
        }
    }
}
