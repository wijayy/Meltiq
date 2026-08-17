<?php

namespace App\Actions;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class BuildFinancialReportExcel
{
    private const ALLOWED_SECTIONS = [
        'summary',
        'sales',
        'productions',
        'transfers',
        'losses',
        'inventory',
        'reconciliation',
    ];

    /**
     * @param  array<string, mixed>  $report
     * @param  array<int, string>  $sections
     * @param  array{period: string, product: string, location: string, exported_at: string}  $filters
     */
    public function handle(array $report, array $sections, array $filters): string
    {
        $sections = array_values(array_intersect(self::ALLOWED_SECTIONS, array_unique($sections)));

        if ($sections === []) {
            throw new InvalidArgumentException('Pilih minimal satu bagian laporan untuk diekspor.');
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sections as $section) {
            $this->addSectionSheet($spreadsheet, $section, $report, $filters);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan file ekspor laporan keuangan.');
        }

        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca file ekspor laporan keuangan.');
        }

        return $contents;
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array{period: string, product: string, location: string, exported_at: string}  $filters
     */
    private function addSectionSheet(Spreadsheet $spreadsheet, string $section, array $report, array $filters): void
    {
        [$title, $headers, $rows, $currencyColumns] = $this->sectionData($section, $report);
        $sheet = new Worksheet($spreadsheet, $title);
        $spreadsheet->addSheet($sheet);
        $lastColumn = $this->columnLetter(count($headers));

        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->setCellValue('A1', mb_strtoupper($title));
        $sheet->fromArray([
            ['Periode', $filters['period']],
            ['Produk', $filters['product']],
            ['Lokasi', $filters['location']],
            ['Diekspor pada', $filters['exported_at']],
        ], null, 'A3');
        $sheet->fromArray($headers, null, 'A8');

        foreach ($rows as $index => $row) {
            $sheet->fromArray(array_values($row), null, 'A'.($index + 9));
        }

        $lastRow = max(8, count($rows) + 8);
        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4E2011']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3:A6')->getFont()->setBold(true);
        $sheet->getStyle('A8:'.$lastColumn.'8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4E2011']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A8:'.$lastColumn.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($currencyColumns as $column) {
            if ($rows !== []) {
                $sheet->getStyle($column.'9:'.$column.$lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');
            }
        }

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A9');
        $sheet->setAutoFilter('A8:'.$lastColumn.$lastRow);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{string, array<int, string>, array<int, array<int, mixed>>, array<int, string>}
     */
    private function sectionData(string $section, array $report): array
    {
        return match ($section) {
            'summary' => [
                'Ringkasan',
                ['Keterangan', 'Nilai'],
                [
                    ['Omzet', $report['summary']['revenue']],
                    ['Nilai Terjual', $report['summary']['cost_of_goods_sold']],
                    ['Laba Kotor', $report['summary']['gross_profit']],
                    ['Margin Kotor (%)', $report['summary']['gross_margin']],
                    ['Nilai Produksi', $report['summary']['production_value']],
                    ['Nilai Pengiriman', $report['summary']['transfer_value']],
                    ['Kerugian Produk Rusak', $report['summary']['loss_value']],
                    ['Kontribusi Bersih', $report['summary']['net_contribution']],
                    ['Nilai Persediaan', $report['summary']['inventory_cost_value']],
                ],
                ['B'],
            ],
            'sales' => [
                'Penjualan',
                ['Produk', 'SKU', 'Terjual', 'Omzet', 'Nilai Terjual', 'Laba Kotor', 'Margin Outlet'],
                $this->rows($report['sales'], ['product', 'sku', 'quantity', 'revenue', 'cost_of_goods_sold', 'gross_profit', 'outlet_margin']),
                ['D', 'E', 'F', 'G'],
            ],
            'productions' => [
                'Produksi',
                ['Produk', 'SKU', 'Diproduksi', 'Nilai Produksi'],
                $this->rows($report['productions'], ['product', 'sku', 'quantity', 'cost_value']),
                ['D'],
            ],
            'transfers' => [
                'Pengiriman',
                ['Produk', 'SKU', 'Tujuan', 'Dikirim', 'Nilai Cost', 'Nilai Transfer', 'Margin Internal'],
                $this->rows($report['transfers'], ['product', 'sku', 'destination', 'quantity', 'cost_value', 'transfer_value', 'internal_margin']),
                ['E', 'F', 'G'],
            ],
            'losses' => [
                'Produk Rusak',
                ['Produk', 'SKU', 'Jumlah', 'Nilai Kerugian'],
                $this->rows($report['losses'], ['product', 'sku', 'quantity', 'value']),
                ['D'],
            ],
            'inventory' => [
                'Persediaan',
                ['Produk', 'SKU', 'Lokasi', 'Stok', 'Nilai Cost', 'Nilai Transfer'],
                $this->rows($report['inventory'], ['product', 'sku', 'location', 'quantity', 'cost_value', 'transfer_value']),
                ['E', 'F'],
            ],
            'reconciliation' => [
                'Rekonsiliasi',
                ['Produk', 'SKU', 'Lokasi', 'Awal', 'Masuk', 'Keluar', 'Seharusnya', 'Akhir', 'Selisih'],
                $this->rows($report['reconciliation'], ['product', 'sku', 'location', 'opening', 'incoming', 'outgoing', 'expected_closing', 'closing', 'variance']),
                [],
            ],
            default => throw new InvalidArgumentException('Bagian laporan tidak dikenal.'),
        };
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $items
     * @param  array<int, string>  $keys
     * @return array<int, array<int, mixed>>
     */
    private function rows(iterable $items, array $keys): array
    {
        return collect($items)
            ->map(fn (array $item): array => array_values(Arr::only($item, $keys)))
            ->values()
            ->all();
    }

    private function columnLetter(int $columnCount): string
    {
        return chr(64 + $columnCount);
    }
}
