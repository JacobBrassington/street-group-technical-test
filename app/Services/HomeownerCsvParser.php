<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Iterator;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;

class HomeownerCsvParser
{
    // So basically writing my thoughts about how to do this at the top here.
    // So we have a few scenarios and I could make the code more dynamic if required, however I don't want to over-engineer the solution.
    // We have homeowner strings that could be one person or two split by either a "&" or "and"
    // Once split up we have a few scenarios

    // If there are 2 homeowners then split them on the above and explode them on empty string delimiters,
    // If there is 3 items then easy.
    // If there are 2 items you have the title and last name
    // If there is one item then you have the title and must infer the last name from the other homeowner

    // If there is one homeowner then
    // If there are 3 items easy
    // If there are 2 items you have the title and last name

    // In both scenarios if you have the first name check if it's one char long if it is then it's an initial not a first name

    protected Iterator $records;
    protected array $parsedHomeowners;

    /**
     * @throws UnavailableStream
     * @throws Exception
     */
    public function __construct(UploadedFile $uploadedFile)
    {
        $csv = Reader::createFromPath($uploadedFile->getRealPath());
        $csv->setHeaderOffset(0);
        $this->records = $csv->getRecords();
    }

    public function parse(): array
    {
        foreach ($this->records as $record) {
            $homeownerString = $record['homeowner'];

            // Check for & or and for multiple homeowners
            if (Str::contains($homeownerString, ['&', 'and'])) {
                $this->parseMultipleHomeowners($homeownerString);
            } else {
                $this->parseSingleHomeowner($homeownerString);
            }
        }

        return $this->parsedHomeowners;
    }

    private function parseMultipleHomeowners($homeownerString): void
    {
        $homeowners = Str::of($homeownerString)->split('/\s*(?:&|and)\s*/i');
        $homeownerOneArray = Str::of($homeowners[0])->explode(' ');
        $homeownerTwoArray = Str::of($homeowners[1])->explode(' ');

        $lastname = $homeownerTwoArray->last();

        $this->pushHomeowner(
            title: $homeownerOneArray[0],
            firstName: count($homeownerOneArray) === 3 ? $homeownerOneArray[1] : null,
            lastName: count($homeownerOneArray) === 3 ? $homeownerOneArray[2] : $lastname,
        );

        $this->pushHomeowner(
            title: $homeownerTwoArray[0],
            firstName: count($homeownerTwoArray) === 3 ? $homeownerTwoArray[1] : null,
            lastName: $lastname,
        );
    }

    private function parseSingleHomeowner($homeownerString): void
    {
        $homeownerArray = Str::of($homeownerString)->explode(' ');

        if (count($homeownerArray) === 3) {
            $length = strlen(preg_replace('/[^a-zA-Z]/', '', $homeownerArray[1]));

            $firstName = $length > 1 ? $homeownerArray[1] : null;
            $initial = $length === 1 ? $homeownerArray[1] : null;
        }

        $this->pushHomeowner(
            title: $homeownerArray[0],
            firstName: $firstName ?? null,
            lastName: $homeownerArray->last(),
            initial: $initial ?? null,
        );
    }

    private function pushHomeowner(string $title, ?string $firstName = null, string $lastName = null, ?string $initial = null): void
    {
        $this->parsedHomeowners[] = [
            'title' => $title,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'initial' => $initial,
        ];
    }
}
