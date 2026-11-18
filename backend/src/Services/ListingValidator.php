<?php

declare(strict_types=1);

final class ListingValidator
{

    public function validate(array $input, array $validTypes): array
    {
        $errors = [];
        $clean = ['category' => (string) ($input['category'] ?? '')];

        if (!in_array($clean['category'], Category::ALL, true)) {
            $errors['category'] = 'Categoría inválida.';
        }

        $clean['title'] = $this->text($input['title'] ?? null, 150, 'title', 'El título', $errors);
        $clean['highlighted'] = filter_var($input['highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);

        switch ($clean['category']) {
            case Category::JOB:
                $clean['company'] = $this->text($input['company'] ?? null, 100, 'company', 'La empresa', $errors);
                $clean['job_description'] = $this->text(
                    $input['job_description'] ?? null,
                    5000,
                    'job_description',
                    'La descripción del cargo',
                    $errors,
                );
                $clean['monthly_salary'] = $this->decimal(
                    $input['monthly_salary'] ?? null,
                    'monthly_salary',
                    'El sueldo',
                    false,
                    0.01,
                    99999999.99,
                    $errors,
                );
                break;

            case Category::PRODUCT:
                $clean['product_description'] = $this->text(
                    $input['product_description'] ?? null,
                    5000,
                    'product_description',
                    'La descripción',
                    $errors,
                );
                $clean['price'] = $this->decimal(
                    $input['price'] ?? null,
                    'price',
                    'El precio',
                    true,
                    0,
                    9999999999.99,
                    $errors,
                );
                break;

            case Category::REAL_ESTATE:
                $type = filter_var($input['property_type_id'] ?? null, FILTER_VALIDATE_INT);

                if ($type === false || !in_array($type, $validTypes, true)) {
                    $errors['property_type_id'] = 'Tipo de inmueble inválido.';
                }

                $clean['property_type_id'] = $type === false ? null : $type;
                $clean['location'] = $this->text($input['location'] ?? null, 150, 'location', 'La ubicación', $errors);
                $clean['area_m2'] = $this->decimal(
                    $input['area_m2'] ?? null,
                    'area_m2',
                    'La superficie',
                    true,
                    0.01,
                    99999999.99,
                    $errors,
                );
                $clean['price'] = $this->decimal(
                    $input['price'] ?? null,
                    'price',
                    'El precio',
                    true,
                    0,
                    99999999999999.99,
                    $errors,
                );
                break;
        }

        return [$clean, $errors];
    }

    private function length(string $value): int
    {
        return preg_match_all('/./us', $value) ?: strlen($value);
    }

    private function text(mixed $value, int $max, string $field, string $label, array &$errors): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';

        if ($text === '') {
            $errors[$field] = "$label es obligatorio/a.";
        } elseif ($this->length($text) > $max) {
            $errors[$field] = "$label no puede superar $max caracteres.";
        }

        return $text;
    }

    private function decimal(
        mixed $value,
        string $field,
        string $label,
        bool $required,
        float $min,
        float $max,
        array &$errors,
    ): ?float {
        $text = is_scalar($value) ? str_replace(',', '.', trim((string) $value)) : '';

        if ($text === '') {
            if ($required) {
                $errors[$field] = "$label es obligatorio/a.";
            }

            return null;
        }

        if (!is_numeric($text) || (float) $text < $min || (float) $text > $max) {
            $errors[$field] = "$label no es válido/a.";

            return null;
        }

        return round((float) $text, 2);
    }
}
