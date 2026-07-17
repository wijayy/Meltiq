<?php

namespace App\Actions;

use App\Models\Production;
use App\Models\ProductionDetail;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class BuildProductionExcel
{
    /**
     * @param  Collection<int, Production>  $productions
     * @param  array{production_no: string, period_begin: string, period_end: string, created_by: string, exported_at: string}  $filters
     */
    public function handle(Collection $productions, array $filters): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Production');
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'LAPORAN PRODUCTION');
        $sheet->fromArray([
            ['Nomor Production', $filters['production_no']],
            ['Period', $filters['period_begin'].' s/d '.$filters['period_end']],
            ['Created By', $filters['created_by']],
            ['Diexport Pada', $filters['exported_at']],
            ['Jumlah Production', $productions->count()],
        ], null, 'A3');

        $mineColor = 'E43A19';
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3:A7')->getFont()->setBold(true);

        $row = 9;

        foreach ($productions as $production) {
            $sheet->fromArray(['Nomor Production', 'Tanggal', 'Created By', 'Catatan', 'Total Qty'], null, 'A'.$row);
            $sheet->fromArray([
                $production->production_no,
                $production->production_date->format('d/m/Y'),
                $production->creator->name,
                $production->notes ?: '-',
                $production->details->sum('qty'),
            ], null, 'A'.($row + 1));
            $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle('E'.($row + 1))->getNumberFormat()->setFormatCode('#,##0 "Pcs"');

            $detailHeaderRow = $row + 3;
            $sheet->mergeCells('A'.$detailHeaderRow.':E'.$detailHeaderRow);
            $sheet->setCellValue('A'.$detailHeaderRow, 'DETAIL PRODUCT');
            $sheet->getStyle('A'.$detailHeaderRow.':E'.$detailHeaderRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4DE']],
            ]);
            $sheet->fromArray(['No', 'Product', 'SKU', 'Qty', ''], null, 'A'.($detailHeaderRow + 1));
            $sheet->getStyle('A'.($detailHeaderRow + 1).':E'.($detailHeaderRow + 1))->getFont()->setBold(true);

            $detailRow = $detailHeaderRow + 2;
            $production->details->each(function (ProductionDetail $detail, int $index) use ($sheet, &$detailRow): void {
                $sheet->fromArray([
                    $index + 1,
                    $detail->product->name,
                    $detail->product->sku,
                    $detail->qty,
                    '',
                ], null, 'A'.$detailRow);
                $sheet->getStyle('D'.$detailRow)->getNumberFormat()->setFormatCode('#,##0 "Pcs"');
                $detailRow++;
            });

            $blockEnd = max($detailHeaderRow + 1, $detailRow - 1);
            $sheet->getStyle('A'.$row.':E'.$blockEnd)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row = $blockEnd + 3;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A9');

        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan file export production.');
        }

        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca file export production.');
        }

        return $contents;
    }
}
