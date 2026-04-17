<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Project;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportsController
{
    public function exportCsv(Request $request)
    {
        $auth = Account::authenticated();

        ['projects' => $projects] = $this->getAllProjects($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export');

        // En-têtes
        $headers = ['Projet', 'Client', 'Date', 'Label', 'Couverture (%)', 'Taux / j (€)', 'Montant (€)', 'Commentaires'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E0']]],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($projects as $project) {
            $rate = $project->daily_rate ?? $project->client->daily_rate ?? 0;

            if ($project->activityTimes->isEmpty()) {
                $sheet->fromArray([$project->name, $project->client->name, '', '', '', '', '', ''], null, "A{$row}");
                $row++;
                continue;
            }

            $projectStartRow = $row;
            foreach ($project->activityTimes as $activity) {
                $montant = ($activity->day_coverage / 100) * $rate;

                $sheet->fromArray([
                    $project->name,
                    $project->client->name,
                    $activity->start_date->format('d/m/Y'),
                    $activity->label ?? '',
                    $activity->day_coverage,
                    $rate,
                    $montant,
                    $activity->comments ?? '',
                ], null, "A{$row}");

                // Formatage numérique
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00 "€"');
                $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00 "€"');
                $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Zébrure
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:H{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                }

                $row++;
            }

            // Colonne Projet en bleu clair avec bordure gauche
            $projectRange = "A{$projectStartRow}:B" . ($row - 1);
            $sheet->getStyle($projectRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');
            $sheet->getStyle($projectRange)->getFont()->setBold(true);
            $sheet->getStyle("A{$projectStartRow}:A" . ($row - 1))->getBorders()->getLeft()
                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF3B82F6');
        }

        // Bordures globales
        if ($row > 2) {
            $sheet->getStyle("A1:H" . ($row - 1))->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');
        }

        // Largeurs de colonnes
        foreach (['A' => 22, 'B' => 18, 'C' => 13, 'D' => 20, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 35] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $filename = 'export_' . now()->format('Y-m-d') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth = Account::authenticated();

        [
            'projects' => $projects,
            'projects_ids' => $projects_ids,
            'date_start' => $date_start,
            'date_end' => $date_end
        ] = $this->getAllProjects($request);

        if ($request->expectsJson()) {
            return response()->json([
                'projects' => $projects->map(fn($p) => [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'client_name' => $p->client->name,
                    'daily_rate'  => (float) ($p->daily_rate ?? $p->client->daily_rate ?? 0),
                    'entries'     => $p->activityTimes->map(fn($a) => [
                        'id'           => $a->id,
                        'start_date'   => $a->start_date->format('Y-m-d'),
                        'day_coverage' => (int) $a->day_coverage,
                        'label'        => $a->label ?? '',
                        'comments'     => $a->comments ?? '',
                    ])->values(),
                ])->values(),
            ]);
        }

        return view('dashboard.singletons.exports', [
            'auth' => $auth,
            'user' => $auth->user,
            'projects' => $projects,
            'projects_ids' => array_map('intval', $projects_ids),
            'date_start' => $date_start,
            'date_end' => $date_end,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function getAllProjects(Request $request): array
    {
        $projects_ids = $request->query('projects', []);
        $date_start = $request->query('date_start');
        $date_end = $request->query('date_end');

        $projects = $projects_ids
            ? Project::whereIn('id', $projects_ids)
                ->with(['client', 'activityTimes' => function ($q) use ($date_start, $date_end) {
                    if ($date_start) $q->whereDate('start_date', '>=', $date_start);
                    if ($date_end) $q->whereDate('start_date', '<=', $date_end);
                    $q->orderBy('start_date');
                }])
                ->get()
            : collect();

        return compact('projects', 'projects_ids', 'date_start', 'date_end');
    }
}
