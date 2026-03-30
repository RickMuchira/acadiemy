<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MarksImport implements ToCollection, WithHeadingRow
{
    /** @var Collection<int, array<string, mixed>> */
    private Collection $data;

    public function __construct()
    {
        $this->data = collect();
    }

    /** @param  Collection<int, Collection<int, mixed>>  $collection */
    public function collection(Collection $collection): void
    {
        $this->data = $collection;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function getData(): Collection
    {
        return $this->data;
    }
}
