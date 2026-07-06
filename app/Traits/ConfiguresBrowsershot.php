<?php

namespace App\Traits;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

trait ConfiguresBrowsershot
{
    protected function getPdf(string $html): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'tempDir' => storage_path('app/temp'),
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
