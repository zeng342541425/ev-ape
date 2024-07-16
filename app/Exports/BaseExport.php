<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class BaseExport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements FromArray,WithHeadings,WithCustomValueBinder
{

    protected array $data;
    protected array $headings;

    public function __construct(array $data, array $headings = [])
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    public function array(): array
    {
        return $this->data;
    }

    // 表頭
    public function headings():array
    {
        return $this->headings;
    }
}
