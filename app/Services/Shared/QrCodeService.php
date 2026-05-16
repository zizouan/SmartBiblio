<?php

namespace App\Services\Shared;

use Illuminate\Support\Str;

class QrCodeService
{
    public function memberCode(?string $id = null): string
    {
        return 'MEM-'.($id ?? Str::uuid()->toString());
    }

    public function copyCode(?string $id = null): string
    {
        return 'COPY-'.($id ?? Str::uuid()->toString());
    }
}
