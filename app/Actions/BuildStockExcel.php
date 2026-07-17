<?php

namespace App\Actions;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class BuildStockExcel
{
    /**
     * @param  Collection<int, array{product_id: int, product_name: string, sku: string, location_id: int, location_name: string, location_type: string, stock: int}>  $stocks
     * @param  array{stock_time: string, product: string, location: string, exported_at: string}  $filters
     */
    public function handle(Collection $stocks, array $filters): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock');
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'LAPORAN STOCK');
        $sheet->fromArray([
            ['Waktu Stock', $filters['stock_time']],
            ['Product', $filters['product']],
            ['Location', $filters['location']],
            ['Diexport Pada', $filters['exported_at']],
            ['Jumlah Baris', $stocks->count()],
        ], null, 'A3');
        $sheet->fromArray(['No', 'Product', 'SKU', 'Location', 'Stock'], null, 'A9');

        foreach ($stocks as $index => $stock) {
            $sheet->fromArray([
                $index + 1,
                $stock['product_name'],
                $stock['sku'],
                $stock['location_name'].' ('.ucfirst($stock['location_type']).')',
                $stock['stock'],
            ], null, 'A'.($index + 10));
        }

        $lastRow = max(9, $stocks->count() + 9);
        $mineColor = 'E43A19';
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3:A7')->getFont()->setBold(true);
        $sheet->getStyle('A9:E9')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A9:E'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($stocks->isNotEmpty()) {
            $sheet->getStyle('E10:E'.$lastRow)->getNumberFormat()->setFormatCode('#,##0 "Pcs"');
            $sheet->getStyle('A10:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E10:E'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->freezePane('A10');
        $sheet->setAutoFilter('A9:E'.$lastRow);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan file export stock.');
        }

        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca file export stock.');
        }

        return $contents;
    }
}
