<?php

namespace App\Services\Catalog;

class ImportReport
{
    public int $created = 0;

    public int $updated = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function addError(int $line, string $message): void
    {
        $this->errors[] = "ردیف $line: $message";
    }

    public function total(): int
    {
        return $this->created + $this->updated;
    }

    public function summary(): string
    {
        $parts = [];

        if ($this->created) {
            $parts[] = "{$this->created} محصول جدید ساخته";
        }

        if ($this->updated) {
            $parts[] = "{$this->updated} محصول به‌روزرسانی";
        }

        return $parts ? implode(' و ', $parts).' شد.' : 'هیچ ردیفی وارد نشد.';
    }
}
