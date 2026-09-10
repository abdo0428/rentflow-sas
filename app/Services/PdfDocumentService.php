<?php

namespace App\Services;

use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfDocumentService
{
    public function render(array $data): string
    {
        $fonts = (new FontVariables)->getDefaults()['fontdata'];
        $fonts['dejavusans']['useOTL'] = 0xFF;
        $pdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans',
            'fontdata' => $fonts, 'tempDir' => storage_path('app/private/pdf-temp'),
            'margin_left' => 18, 'margin_right' => 18, 'margin_top' => 18, 'margin_bottom' => 18,
        ]);
        $pdf->SetDirectionality(app()->getLocale() === 'ar' ? 'rtl' : 'ltr');
        $pdf->SetTitle($data['title']);
        $pdf->SetAuthor(__('app.brand'));
        $pdf->WriteHTML(view('pdf.document', $data)->render());

        return $pdf->Output('', Destination::STRING_RETURN);
    }
}
