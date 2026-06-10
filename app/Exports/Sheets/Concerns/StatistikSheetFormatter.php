<?php

namespace App\Exports\Sheets\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StatistikSheetFormatter
{
    private const NAVY = '004A87';
    private const YELLOW = 'FDB813';
    private const BORDER = 'CBD5E1';
    private const TEXT = '0F172A';
    private const MUTED = '64748B';

    public static function styleSectionedSheet(Worksheet $sheet, array $widths = []): void
    {
        self::setupPage($sheet);

        $lastColumn = $sheet->getHighestColumn();
        $maxRow = $sheet->getHighestRow();
        $maxColumnIndex = Coordinate::columnIndexFromString($lastColumn);

        self::applyWidths($sheet, $widths, $maxColumnIndex);
        self::styleUsedRange($sheet, $lastColumn, $maxRow);

        for ($row = 1; $row <= $maxRow; $row++) {
            $filled = self::countFilledCells($sheet, $row, $maxColumnIndex);

            if ($filled === 0) {
                continue;
            }

            self::applyRowBorder($sheet, $row, $lastColumn);

            if ($filled === 1) {
                self::styleSectionTitle($sheet, $row, $lastColumn);

                $nextFilled = $row + 1 <= $maxRow
                    ? self::countFilledCells($sheet, $row + 1, $maxColumnIndex)
                    : 0;

                if ($nextFilled > 1) {
                    self::styleTableHeader($sheet, $row + 1, $lastColumn);
                }
            }
        }
    }

    public static function styleSummarySheet(Worksheet $sheet): void
    {
        self::setupPage($sheet);

        $lastColumn = $sheet->getHighestColumn();
        $maxRow = $sheet->getHighestRow();

        self::applyWidths($sheet, ['A' => 38, 'B' => 64], 2);
        self::styleUsedRange($sheet, $lastColumn, $maxRow);

        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::MUTED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        for ($row = 1; $row <= $maxRow; $row++) {
            $value = trim((string) $sheet->getCell('A' . $row)->getValue());
            if ($value === '') {
                continue;
            }

            self::applyRowBorder($sheet, $row, $lastColumn);

            if (in_array($value, ['Filter yang Digunakan', 'Ringkasan Umum', 'Insight Utama'], true)) {
                self::styleSectionTitle($sheet, $row, $lastColumn);
                if ($row + 1 <= $maxRow) {
                    self::styleTableHeader($sheet, $row + 1, $lastColumn);
                }
            }
        }
    }

    public static function styleDetailSheet(Worksheet $sheet): void
    {
        self::setupPage($sheet);

        $lastColumn = $sheet->getHighestColumn();
        $maxRow = $sheet->getHighestRow();

        self::applyWidths($sheet, [
            'A' => 6,
            'B' => 30,
            'C' => 18,
            'D' => 12,
            'E' => 12,
            'F' => 14,
            'G' => 16,
            'H' => 15,
            'I' => 28,
            'J' => 44,
            'K' => 18,
            'L' => 18,
            'M' => 14,
            'N' => 18,
            'O' => 25,
            'P' => 14,
            'Q' => 32,
        ], Coordinate::columnIndexFromString($lastColumn));

        self::styleUsedRange($sheet, $lastColumn, $maxRow);
        self::styleTableHeader($sheet, 1, $lastColumn);

        if ($maxRow > 1) {
            $sheet->setAutoFilter('A1:' . $lastColumn . $maxRow);
            $sheet->freezePane('A2');
            $sheet->getStyle('A2:' . $lastColumn . $maxRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => self::BORDER],
                    ],
                ],
            ]);
        }

        $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C:C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('D:E')->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('L:M')->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('N:N')->getNumberFormat()->setFormatCode('"Rp" #,##0');

        $sheet->getStyle('D:E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K:Q')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I:J')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private static function setupPage(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getDefaultRowDimension()->setRowHeight(-1);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.45)
            ->setRight(0.35)
            ->setBottom(0.45)
            ->setLeft(0.35);
    }

    private static function applyWidths(Worksheet $sheet, array $widths, int $maxColumnIndex): void
    {
        for ($index = 1; $index <= $maxColumnIndex; $index++) {
            $column = Coordinate::stringFromColumnIndex($index);
            $dimension = $sheet->getColumnDimension($column);

            if (isset($widths[$column])) {
                $dimension->setWidth((float) $widths[$column]);
            } else {
                $dimension->setAutoSize(true);
            }
        }
    }

    private static function styleUsedRange(Worksheet $sheet, string $lastColumn, int $maxRow): void
    {
        $range = 'A1:' . $lastColumn . max(1, $maxRow);

        $sheet->getStyle($range)->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'color' => ['rgb' => self::TEXT]],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
    }

    private static function styleSectionTitle(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $range = 'A' . $row . ':' . $lastColumn . $row;

        if ($lastColumn !== 'A') {
            $sheet->mergeCells($range);
        }

        $sheet->getRowDimension($row)->setRowHeight(24);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NAVY]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private static function styleTableHeader(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $range = 'A' . $row . ':' . $lastColumn . $row;

        $sheet->getRowDimension($row)->setRowHeight(22);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::NAVY]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::YELLOW]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER],
                ],
            ],
        ]);
    }

    private static function applyRowBorder(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::BORDER],
                ],
            ],
        ]);
    }

    private static function countFilledCells(Worksheet $sheet, int $row, int $maxColumnIndex): int
    {
        $count = 0;

        for ($index = 1; $index <= $maxColumnIndex; $index++) {
            $column = Coordinate::stringFromColumnIndex($index);
            $value = $sheet->getCell($column . $row)->getValue();

            if (trim((string) $value) !== '') {
                $count++;
            }
        }

        return $count;
    }
}
