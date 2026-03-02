<?php

namespace App\Console\Commands;

use App\Services\DirectionParser;
use Illuminate\Console\Command;

class ParseDirectionCommand extends Command
{
    protected $signature = 'parse-direction {sentence : The direction sentence to parse}';

    protected $description = 'Parse a recipe direction sentence and output structured JSON '
        . '(for testing DirectionParser)';

    public function handle(DirectionParser $parser): int
    {
        $sentence = $this->argument('sentence');

        if (trim($sentence) === '') {
            $this->error('Sentence cannot be empty.');
            return self::FAILURE;
        }

        $result = $parser->parse($sentence);
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
