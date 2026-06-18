<?php

namespace App\Services\Recruitment;

use App\Models\EmploymentApplication;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class ApplicationFormPdfService
{
    public function __construct(
        private readonly ApplicationFormDisplayService $displayService,
        private readonly ApplicationFormPrintLayoutService $printLayoutService,
    ) {}

    public function generate(EmploymentApplication $application): string
    {
        $application->loadMissing('person');

        $html = view('admin.applications.pdf', [
            'application' => $application,
            'layout' => $this->printLayoutService->build($application),
            'profilePhotoDataUri' => $this->displayService->profilePhotoDataUri($application),
            'initials' => $this->displayService->applicantInitials($application),
            'applicantName' => $this->displayService->applicantDisplayName($application),
        ])->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    private function makeMpdf(): Mpdf
    {
        $fontDir = resource_path('fonts');
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $defaultFontConfig = (new FontVariables)->getDefaults();

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => 'rtl',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'fontDir' => array_merge($defaultConfig['fontDir'], [$fontDir]),
            'fontdata' => $defaultFontConfig['fontdata'] + [
                'vazirmatn' => [
                    'R' => 'Vazirmatn-Regular.ttf',
                    'B' => 'Vazirmatn-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'vazirmatn',
            'tempDir' => $tempDir,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
    }
}
