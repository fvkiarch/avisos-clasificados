<?php

declare(strict_types=1);

final class Listing
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly DateTimeImmutable $createdAt,
        public readonly bool $highlighted,
        public readonly int $authorId,
        public readonly string $author,
        public readonly string $category,
        public readonly ?string $reference,
        public readonly ?string $detail,
        public readonly ?float $amount,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id_aviso'],
            $row['titulo'],
            new DateTimeImmutable($row['fecha_creacion']),
            (bool) $row['resaltado'],
            (int) $row['id_autor'],
            $row['autor'],
            $row['categoria'],
            $row['referencia'],
            $row['detalle'],
            $row['monto'] !== null ? (float) $row['monto'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'highlighted' => $this->highlighted,
            'author_id' => $this->authorId,
            'author' => $this->author,
            'category' => $this->category,
            'reference' => $this->reference,
            'detail' => $this->detail,
            'amount' => $this->amount,
        ];
    }
}
