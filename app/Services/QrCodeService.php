<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\DiningTable;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function getImageForTable(DiningTable $table): array
    {
        $baseUrl = config('app.frontend_public_url');

        if (! $baseUrl) {
            throw new ApiException('Public app URL is not configured on the server', 500);
        }

        $url = rtrim($baseUrl, '/')."/table/{$table->qr_token}";

        $svg = QrCode::format('svg')->size(400)->margin(1)->generate($url);
        $dataUrl = 'data:image/svg+xml;base64,'.base64_encode($svg);

        return ['url' => $url, 'data_url' => $dataUrl, 'table_number' => $table->table_number];
    }
}