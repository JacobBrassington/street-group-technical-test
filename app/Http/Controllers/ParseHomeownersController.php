<?php

namespace App\Http\Controllers;

use App\Services\HomeownerCsvParser;
use Illuminate\Http\JsonResponse;
use League\Csv\Exception;
use League\Csv\UnavailableStream;

class ParseHomeownersController
{
    /**
     * @throws UnavailableStream
     * @throws Exception
     */
    public function __invoke(): JsonResponse
    {
        $parsedHomeowners = (new HomeownerCsvParser(request()->file('names')))->parse();

        return response()->json($parsedHomeowners)
            ->header('Content-Disposition', 'attachment; filename="parsed-homeowners.json"');
    }
}
