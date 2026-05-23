<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportsController
{
    public function index(Request $request): mixed
    {
        ['projects' => $projects, 'projectIds' => $projectIds, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd] = $this->loadProjects($request);

        if ($request->expectsJson()) {
            return response()->json([
                'projects' => $this->formatProjects($projects),
            ]);
        }

        $allProjects = $request->user()->projects()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client_name' => $p->client->name,
            ])
            ->values();

        return Inertia::render('ExportPage', [
            'allProjects' => $allProjects,
            'selectedProjectIds' => $projectIds,
            'dateStart' => $dateStart ?? '',
            'dateEnd' => $dateEnd ?? '',
            'projects' => $this->formatProjects($projects),
        ]);
    }

    public function exportCsv(Request $request): mixed
    {
        ['projects' => $projects] = $this->loadProjects($request);

        $filename = 'export_'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($projects) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 for Excel

            fputcsv($handle, ['Projet', 'Client', 'Date', 'Label', 'Couverture (%)', 'Taux / j (€)', 'Montant (€)', 'Commentaires'], ';');

            foreach ($projects as $project) {
                $rate = $project->daily_rate ?? $project->client->daily_rate ?? 0;

                if ($project->timesheetEntries->isEmpty()) {
                    fputcsv($handle, [$project->name, $project->client->name, '', '', '', '', '', ''], ';');

                    continue;
                }

                foreach ($project->timesheetEntries as $entry) {
                    fputcsv($handle, [
                        $project->name,
                        $project->client->name,
                        $entry->date->format('d/m/Y'),
                        $entry->title ?? '',
                        $entry->coverage,
                        $rate,
                        round(($entry->coverage / 100) * $rate, 2),
                        $entry->description ?? '',
                    ], ';');
                }
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportXlsx(Request $request): mixed
    {
        ['projects' => $projects] = $this->loadProjects($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Export');

        $headers = ['Projet', 'Client', 'Date', 'Label', 'Couverture (%)', 'Taux / j (€)', 'Montant (€)', 'Commentaires'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E0']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($projects as $project) {
            $rate = $project->daily_rate ?? $project->client->daily_rate ?? 0;

            if ($project->timesheetEntries->isEmpty()) {
                $sheet->fromArray([$project->name, $project->client->name, '', '', '', '', '', ''], null, "A{$row}");
                $row++;

                continue;
            }

            $projectStartRow = $row;
            foreach ($project->timesheetEntries as $entry) {
                $sheet->fromArray([
                    $project->name,
                    $project->client->name,
                    $entry->date->format('d/m/Y'),
                    $entry->title ?? '',
                    $entry->coverage,
                    $rate,
                    ($entry->coverage / 100) * $rate,
                    $entry->description ?? '',
                ], null, "A{$row}");

                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00 "€"');
                $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00 "€"');
                $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:H{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                }

                $row++;
            }

            $sheet->getStyle("A{$projectStartRow}:B".($row - 1))->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');
            $sheet->getStyle("A{$projectStartRow}:B".($row - 1))->getFont()->setBold(true);
            $sheet->getStyle("A{$projectStartRow}:A".($row - 1))->getBorders()->getLeft()
                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF3B82F6');
        }

        if ($row > 2) {
            $sheet->getStyle('A1:H'.($row - 1))->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');
        }

        foreach (['A' => 22, 'B' => 18, 'C' => 13, 'D' => 20, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 35] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $filename = 'export_'.now()->format('Y-m-d').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');

        return response(ob_get_clean(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function loadProjects(Request $request): array
    {
        $projectIds = (array) $request->query('projects', []);
        $dateStart = $request->query('date_start');
        $dateEnd = $request->query('date_end');

        $projects = $projectIds
            ? Project::whereIn('id', $projectIds)
                ->with(['client', 'timesheetEntries' => function ($q) use ($dateStart, $dateEnd) {
                    $q->when($dateStart, fn ($q) => $q->whereDate('date', '>=', $dateStart))
                        ->when($dateEnd, fn ($q) => $q->whereDate('date', '<=', $dateEnd))
                        ->orderBy('date');
                }])
                ->get()
            : collect();

        return compact('projects', 'projectIds', 'dateStart', 'dateEnd');
    }

    private function formatProjects($projects): array
    {
        return $projects->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'client_name' => $p->client->name,
            'daily_rate' => (float) ($p->daily_rate ?? $p->client->daily_rate ?? 0),
            'entries' => $p->timesheetEntries->map(fn ($e) => [
                'date' => $e->date->toDateString(),
                'coverage' => $e->coverage,
                'title' => $e->title ?? '',
                'description' => $e->description ?? '',
            ])->values(),
        ])->values()->all();
    }
}
