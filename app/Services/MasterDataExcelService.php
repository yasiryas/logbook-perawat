<?php

namespace App\Services;

use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\Nurse;
use App\Models\Unit;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataExcelService
{
    /**
     * Build a downloadable XLSX for one entity type.
     */
    public function export(string $type): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $exporters = [
            'units' => fn () => $this->exportUnits($sheet),
            'nurses' => fn () => $this->exportNurses($sheet),
            'indicators' => fn () => $this->exportIndicators($sheet),
        ];

        if (! isset($exporters[$type])) {
            abort(422, 'Tipe ekspor tidak dikenal.');
        }

        $exporters[$type]();

        $filename = 'master-'.$type.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Read an XLSX file and upsert master data.
     *
     * @return array{imported: int, errors: array<int, string>}
     */
    public function import(string $type, string $path): array
    {
        $readers = [
            'units' => fn () => $this->importUnits($path),
            'nurses' => fn () => $this->importNurses($path),
            'indicators' => fn () => $this->importIndicators($path),
        ];

        if (! isset($readers[$type])) {
            abort(422, 'Tipe impor tidak dikenal.');
        }

        return $readers[$type]();
    }

    private function exportUnits(Worksheet $sheet): void
    {
        $rows = Unit::orderBy('name')->get(['name']);

        $sheet->fromArray([
            ['Nama'],
            ...$rows->map(fn (Unit $unit) => [$unit->name])->all(),
        ], null, 'A1');

        $this->styleSheet($sheet, 'A1:A'.($rows->count() + 1));
        $sheet->getColumnDimension('A')->setAutoSize(true);
    }

    private function exportNurses(Worksheet $sheet): void
    {
        $rows = Nurse::with('unit')->orderBy('name')->get();

        $sheet->fromArray([
            ['Nama', 'NIP', 'Pangkat / Golongan', 'Unit'],
            ...$rows->map(fn (Nurse $nurse) => [
                $nurse->name,
                $nurse->nip,
                $nurse->pk,
                $nurse->unit->name,
            ])->all(),
        ], null, 'A1');

        $this->styleSheet($sheet, 'A1:D'.($rows->count() + 1));

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function exportIndicators(Worksheet $sheet): void
    {
        $rows = Indicator::with(['category', 'unit'])
            ->orderBy('category_id')
            ->orderBy('sort')
            ->get();

        $sheet->fromArray([
            ['Kategori', 'Unit', 'Urutan', 'Nama Indikator', 'Target'],
            ...$rows->map(fn (Indicator $indicator) => [
                $indicator->category->code.' — '.$indicator->category->name,
                $indicator->unit?->name ?? '(semua unit)',
                $indicator->sort,
                $indicator->name,
                $indicator->target,
            ])->all(),
        ], null, 'A1');

        $this->styleSheet($sheet, 'A1:E'.($rows->count() + 1));

        foreach (['A', 'B', 'C', 'D', 'E'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function importUnits(string $path): array
    {
        $rows = $this->readRows($path);

        return $this->runImport($rows, 'units', function (array $row, int $line) {
            $name = trim((string) ($row['nama'] ?? ''));

            if ($name === '') {
                return [0, $this->error($line, 'Nama unit kosong')];
            }

            Unit::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);

            return [1, null];
        });
    }

    private function importNurses(string $path): array
    {
        $rows = $this->readRows($path);

        return $this->runImport($rows, 'nurses', function (array $row, int $line) {
            $name = trim((string) ($row['nama'] ?? ''));
            $nip = trim((string) ($row['nip'] ?? ''));
            $pk = trim((string) ($row['pangkat / golongan'] ?? $row['pangkat'] ?? ''));
            $unitName = trim((string) ($row['unit'] ?? ''));

            if ($name === '' || $nip === '' || $pk === '' || $unitName === '') {
                return [0, $this->error($line, 'Nama, NIP, Pangkat dan Unit wajib diisi')];
            }

            $unit = Unit::firstWhere('name', $unitName);

            if (! $unit) {
                return [0, $this->error($line, "Unit \"{$unitName}\" tidak ditemukan")];
            }

            Nurse::updateOrCreate(['nip' => $nip], [
                'name' => $name,
                'pk' => $pk,
                'unit_id' => $unit->id,
            ]);

            return [1, null];
        });
    }

    private function importIndicators(string $path): array
    {
        $rows = $this->readRows($path);

        return $this->runImport($rows, 'indicators', function (array $row, int $line) {
            $categoryLabel = trim((string) ($row['kategori'] ?? ''));
            $unitLabel = trim((string) ($row['unit'] ?? ''));
            $name = trim((string) ($row['nama indikator'] ?? $row['nama'] ?? ''));
            $target = trim((string) ($row['target'] ?? ''));
            $sort = (int) ($row['urutan'] ?? 0);

            if ($categoryLabel === '' || $name === '') {
                return [0, $this->error($line, 'Kategori dan Nama Indikator wajib diisi')];
            }

            $category = $this->resolveCategory($categoryLabel);

            if (! $category) {
                return [0, $this->error($line, "Kategori \"{$categoryLabel}\" tidak ditemukan")];
            }

            $unit = null;

            if ($unitLabel !== '' && strtolower($unitLabel) !== '(semua unit)') {
                $unit = Unit::firstWhere('name', $unitLabel);

                if (! $unit) {
                    return [0, $this->error($line, "Unit \"{$unitLabel}\" tidak ditemukan")];
                }
            }

            Indicator::updateOrCreate([
                'category_id' => $category->id,
                'unit_id' => $unit?->id,
                'name' => $name,
            ], [
                'sort' => $sort > 0 ? $sort : 1,
                'target' => $target !== '' ? $target : '100',
            ]);

            return [1, null];
        });
    }

    /**
     * Resolve a category by code, name, or "CODE — name" export label.
     */
    private function resolveCategory(string $label): ?IndicatorCategory
    {
        $normalized = strtolower(trim($label));

        return IndicatorCategory::query()
            ->where('code', strtoupper($label[0] ?? ''))
            ->orWhere('name', $label)
            ->orWhereRaw('LOWER(name) = ?', [$normalized])
            ->orWhereRaw("CONCAT(code, ' — ', name) = ?", [$label])
            ->first();
    }

    /**
     * Read all rows, keyed by the exported header name (nama, nip, ...).
     * A header row is skipped, then each remaining row is normalised so both
     * export round-trips and hand-made files with the same headers work.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow < 2) {
            throw new \RuntimeException('File tidak memiliki baris data.');
        }

        $header = [];

        for ($column = 1; $column <= 10; $column++) {
            $cell = Coordinate::stringFromColumnIndex($column);
            $header[$column] = strtolower(trim((string) $this->cellValue($sheet, $cell.'1')));
        }

        $rows = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $values = [];

            foreach ($header as $column => $key) {
                if ($key === '') {
                    continue;
                }

                $cell = Coordinate::stringFromColumnIndex($column);

                if ($this->isEmptyRow($sheet, $row)) {
                    continue;
                }

                $values[$key] = $this->cellValue($sheet, $cell.$row);
            }

            if ($values !== []) {
                $rows[] = $values;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{imported: int, errors: array<int, string>}
     */
    private function runImport(array $rows, string $type, callable $processor): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            if ($this->isHeaderRow($type, $row)) {
                continue;
            }

            [$count, $error] = $processor($row, $line);

            if ($error !== null) {
                $errors[] = $error;
            } else {
                $imported += $count;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    private function isHeaderRow(string $type, array $row): bool
    {
        $first = strtolower(trim((string) ($row['nama'] ?? '')));

        return match ($type) {
            'nurses' => $first === 'nama' || $first === 'nip',
            'indicators' => $first === 'kategori' || $first === 'nama indikator',
            default => $first === 'nama',
        };
    }

    private function cellValue(Worksheet $sheet, string $coordinate): mixed
    {
        $value = $sheet->getCell($coordinate)->getValue();

        if (is_object($value) && method_exists($value, 'getPlainText')) {
            return $value->getPlainText();
        }

        return is_scalar($value) || $value === null ? $value : (string) $value;
    }

    private function isEmptyRow(Worksheet $sheet, int $row): bool
    {
        foreach (range(1, 10) as $column) {
            $cell = Coordinate::stringFromColumnIndex($column);
            $value = $this->cellValue($sheet, $cell.$row);

            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function styleSheet(Worksheet $sheet, string $range): void
    {
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle($range)->getFont()->setSize(11);
        $sheet->getStyle('A1:'.$highestColumn.'1')
            ->getFont()
            ->setBold(true);
        $sheet->getStyle('A1:'.$highestColumn.'1')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($range)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function error(int $line, string $message): string
    {
        return "Baris {$line}: {$message}";
    }
}
