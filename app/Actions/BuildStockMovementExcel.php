<?php

namespace App\Actions;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class BuildStockMovementExcel
{
    /**
     * @param  EloquentCollection<int, StockMovement>  $movements
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Location>  $locations
     * @param  array<int, array<int, array{increase: int, decrease: int}>>  $summary
     * @param  array{period_begin: string, period_end: string, product: string, location: string, exported_at: string}  $filters
     */
    public function handle(EloquentCollection $movements, Collection $products, Collection $locations, array $summary, array $filters): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pergerakan Stok');
        $mineColor = '4E2011';
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'LAPORAN PERGERAKAN STOK');
        $sheet->fromArray([
            ['Periode', $filters['period_begin'].' s/d '.$filters['period_end']],
            ['Produk', $filters['product']],
            ['Lokasi', $filters['location']],
            ['Diekspor Pada', $filters['exported_at']],
            ['Jumlah Pergerakan', $movements->count()],
        ], null, 'B3');
        $sheet->fromArray(['No', 'Tanggal', 'Produk', 'Tipe', 'Dari', 'Ke', 'Jumlah', 'Referensi'], null, 'A9');

        foreach ($movements as $index => $movement) {
            $sheet->fromArray([
                $index + 1,
                $movement->movement_date->format('d/m/Y H:i'),
                $movement->product->name.' — '.$movement->product->sku,
                ucfirst($movement->movement_type),
                $movement->from_location_id !== null ? $movement->fromLocation->name : '-',
                $movement->to_location_id !== null ? $movement->toLocation->name : '-',
                $movement->qty,
                $movement->reference_no ?? '-',
            ], null, 'A'.($index + 10));
        }

        $detailLastRow = max(9, $movements->count() + 9);
        $summaryTitleRow = $detailLastRow + 3;
        $summaryHeaderRow = $summaryTitleRow + 1;
        $summarySubHeaderRow = $summaryHeaderRow + 1;
        $summaryValueColumnCount = $locations->sum(fn (Location $location): int => $location->type === 'virtual' ? 1 : 2);
        $lastSummaryColumnIndex = max(3, 2 + $summaryValueColumnCount);
        $lastSummaryColumn = Coordinate::stringFromColumnIndex($lastSummaryColumnIndex);
        $sheet->mergeCells('B'.$summaryTitleRow.':'.$lastSummaryColumn.$summaryTitleRow);
        $sheet->setCellValue('B'.$summaryTitleRow, 'RANGKUMAN PERGERAKAN PER PRODUK DAN LOKASI');
        $sheet->mergeCells('B'.$summaryHeaderRow.':B'.$summarySubHeaderRow);
        $sheet->setCellValue('B'.$summaryHeaderRow, 'Produk');

        $summaryColumn = 3;
        foreach ($locations as $location) {
            if ($location->type !== 'virtual') {
                $sheet->mergeCells([$summaryColumn, $summaryHeaderRow, $summaryColumn + 1, $summaryHeaderRow]);
            }
            $sheet->setCellValue([$summaryColumn, $summaryHeaderRow], $location->name);
            $sheet->setCellValue([$summaryColumn, $summarySubHeaderRow], 'Bertambah');

            if ($location->type !== 'virtual') {
                $sheet->setCellValue([$summaryColumn + 1, $summarySubHeaderRow], 'Berkurang');
            }

            $summaryColumn += $location->type === 'virtual' ? 1 : 2;
        }

        foreach ($products as $productIndex => $product) {
            $row = $summarySubHeaderRow + $productIndex + 1;
            $sheet->setCellValue('B'.$row, $product->name.' — '.$product->sku);

            $summaryColumn = 3;
            foreach ($locations as $location) {
                $values = $summary[$product->id][$location->id] ?? ['increase' => 0, 'decrease' => 0];
                $sheet->setCellValue([$summaryColumn, $row], $values['increase']);

                if ($location->type !== 'virtual') {
                    $sheet->setCellValue([$summaryColumn + 1, $row], $values['decrease']);
                }

                $summaryColumn += $location->type === 'virtual' ? 1 : 2;
            }
        }

        $summaryLastRow = max($summarySubHeaderRow, $summarySubHeaderRow + $products->count());
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B3:B7')->getFont()->setBold(true);
        $sheet->getStyle('A9:H9')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A9:H'.$detailLastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B'.$summaryTitleRow.':'.$lastSummaryColumn.$summaryTitleRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4DE']],
        ]);
        $sheet->getStyle('B'.$summaryHeaderRow.':'.$lastSummaryColumn.$summarySubHeaderRow)->getFont()->setBold(true);
        $sheet->getStyle('B'.$summaryHeaderRow.':'.$lastSummaryColumn.$summaryLastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B'.$summaryHeaderRow.':'.$lastSummaryColumn.$summarySubHeaderRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($movements->isNotEmpty()) {
            $sheet->getStyle('G10:G'.$detailLastRow)->getNumberFormat()->setFormatCode('#,##0 "Pcs"');
        }
        if ($products->isNotEmpty() && $locations->isNotEmpty()) {
            $sheet->getStyle('C'.($summarySubHeaderRow + 1).':'.$lastSummaryColumn.$summaryLastRow)->getNumberFormat()->setFormatCode('#,##0 "Pcs"');
        }

        $sheet->freezePane('A10');
        $sheet->setAutoFilter('A9:H'.$detailLastRow);
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        for ($column = 2; $column <= $lastSummaryColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan file ekspor pergerakan stok.');
        }

        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca file ekspor pergerakan stok.');
        }

        return $contents;
    }
}
