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
     * @param  Collection<int, array{
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     location_id: int,
     *     location_name: string,
     *     location_type: string,
     *     physical: int,
     *     sales: int,
     *     returned: int,
     *     expired: int,
     *     total: int
     * }>  $stocks
     * @param  array{stock_time: string, product: string, location: string, exported_at: string}  $filters
     */
    public function handle(Collection $stocks, array $filters): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Konsinyasi');
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'LAPORAN KONSINYASI');
        $sheet->fromArray([
            ['Waktu Stok', $filters['stock_time']],
            ['Produk', $filters['product']],
            ['Lokasi', $filters['location']],
            ['Diekspor Pada', $filters['exported_at']],
            ['Jumlah Baris', $stocks->count()],
        ], null, 'A3');
        $sheet->fromArray(
            ['No', 'Lokasi', 'Produk', 'SKU', 'Kedaluwarsa', 'Dikembalikan', 'Fisik', 'Terjual', 'Total'],
            null,
            'A9',
        );

        foreach ($stocks as $index => $stock) {
            $sheet->fromArray([
                $index + 1,
                $stock['location_name'].' ('.$this->locationTypeLabel($stock['location_type']).')',
                $stock['product_name'],
                $stock['sku'],
                $stock['expired'],
                $stock['returned'],
                $stock['physical'],
                $stock['sales'],
                $stock['total'],
            ], null, 'A'.($index + 10));
        }

        $lastRow = max(9, $stocks->count() + 9);
        $mineColor = '4E2011';
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3:A7')->getFont()->setBold(true);
        $sheet->getStyle('A9:I9')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A9:I'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($stocks->isNotEmpty()) {
            $sheet->getStyle('E10:I'.$lastRow)->getNumberFormat()->setFormatCode('#,##0 "Pcs"');
            $sheet->getStyle('A10:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E10:I'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->freezePane('A10');
        $sheet->setAutoFilter('A9:I'.$lastRow);

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan file ekspor stok.');
        }

        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca file ekspor stok.');
        }

        return $contents;
    }

    private function locationTypeLabel(string $type): string
    {
        return match ($type) {
            'warehouse' => 'Gudang',
            'outlet' => 'Outlet',
            'virtual' => 'Virtual',
            default => $type,
        };
    }
}
