<?php

namespace App\Actions;

use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class BuildVisitExcel
{
    /**
     * @param  Collection<int, Visit>  $visits
     * @param  array{visit_no: string, location: string, period_begin: string, period_end: string, exported_at: string}  $filters
     */
    public function handle(Collection $visits, array $filters): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengiriman');
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'LAPORAN PENGIRIMAN');
        $sheet->fromArray([
            ['Nomor Pengiriman', $filters['visit_no']],
            ['Outlet', $filters['location']],
            ['Periode', $filters['period_begin'].' s/d '.$filters['period_end']],
            ['Diekspor Pada', $filters['exported_at']],
            ['Jumlah Pengiriman', $visits->count()],
        ], null, 'A3');

        $mineColor = '4E2011';
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3:A7')->getFont()->setBold(true);

        $row = 9;

        foreach ($visits as $visit) {
            $sheet->fromArray(['Nomor Pengiriman', 'Tanggal', 'Outlet', 'Dibuat Oleh', 'Catatan', '', '', '', '', 'Total Pengiriman'], null, 'A'.$row);
            $sheet->fromArray([
                $visit->visit_no,
                $visit->visit_date->format('d/m/Y'),
                $visit->location->name,
                $visit->creator->name,
                $visit->notes ?: '-',
                '', '', '', '',
                $visit->details->sum('newDeliveryQty'),
            ], null, 'A'.($row + 1));
            $sheet->getStyle('A'.$row.':J'.$row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $mineColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle('J'.($row + 1))->getNumberFormat()->setFormatCode('#,##0 "Pcs"');

            $detailHeaderRow = $row + 3;
            $sheet->mergeCells('A'.$detailHeaderRow.':J'.$detailHeaderRow);
            $sheet->setCellValue('A'.$detailHeaderRow, 'DETAIL PRODUK');
            $sheet->getStyle('A'.$detailHeaderRow.':J'.$detailHeaderRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4DE']],
            ]);
            $sheet->fromArray(['No', 'Produk', 'SKU', 'Stok Sebelum', 'Stok Fisik', 'Terjual', 'Dikembalikan', 'Kedaluwarsa', 'Pengiriman Baru', 'Stok Akhir'], null, 'A'.($detailHeaderRow + 1));
            $sheet->getStyle('A'.($detailHeaderRow + 1).':J'.($detailHeaderRow + 1))->getFont()->setBold(true);

            $detailRow = $detailHeaderRow + 2;
            $visit->details->each(function (VisitDetail $detail, int $index) use ($sheet, &$detailRow): void {
                $sold = $detail->stockBefore - $detail->physicalStock - $detail->expiredQty;
                $finalStock = $detail->physicalStock - $detail->returnedQty + $detail->newDeliveryQty;

                $sheet->fromArray([
                    $index + 1,
                    $detail->product->name,
                    $detail->product->sku,
                    $detail->stockBefore,
                    $detail->physicalStock,
                    $sold,
                    $detail->returnedQty,
                    $detail->expiredQty,
                    $detail->newDeliveryQty,
                    $finalStock,
                ], null, 'A'.$detailRow);
                $sheet->getStyle('D'.$detailRow.':J'.$detailRow)->getNumberFormat()->setFormatCode('#,##0 "Pcs"');
                $detailRow++;
            });

            $blockEnd = max($detailHeaderRow + 1, $detailRow - 1);
            $sheet->getStyle('A'.$row.':J'.$blockEnd)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row = $blockEnd + 3;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A9');

        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan file ekspor pengiriman.');
        }

        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $spreadsheet->disconnectWorksheets();

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca file ekspor pengiriman.');
        }

        return $contents;
    }
}
