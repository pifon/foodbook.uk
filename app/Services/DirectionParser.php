<?php

namespace App\Services;

class DirectionParser
{
    private const ACTIONS = [
        'add', 'arrange', 'bake', 'baste', 'beat', 'blanch', 'blend', 'boil', 'braise',
        'bread', 'bring', 'broil', 'brown', 'brush', 'caramelize', 'check', 'chop',
        'close', 'coat', 'combine', 'cook', 'cool', 'cover', 'cream', 'crush', 'cube',
        'cut', 'deep-fry', 'deglaze', 'dice', 'discard', 'dissolve', 'drain', 'dress',
        'drizzle', 'dry', 'dust', 'flip', 'fold', 'freeze', 'fry', 'garnish', 'glaze',
        'grate', 'grill', 'grind', 'heat', 'infuse', 'insert', 'julienne', 'knead',
        'layer', 'let', 'line', 'marinate', 'mash', 'measure', 'melt', 'mince', 'mix',
        'open', 'pan-fry', 'parboil', 'pat', 'peel', 'pickle', 'place', 'poach', 'pour',
        'preheat', 'prepare', 'press', 'puree', 'put', 'reduce', 'refrigerate', 'remove',
        'rest', 'rinse', 'roast', 'roll', 'rub', 'saute', 'sauté', 'scald', 'score',
        'scramble', 'sear', 'season', 'serve', 'set', 'shred', 'sift', 'simmer', 'skim',
        'slice', 'smoke', 'soak', 'soften', 'spread', 'sprinkle', 'squeeze', 'steam',
        'stew', 'stir', 'strain', 'stuff', 'taste', 'toss', 'transfer', 'trim', 'truss',
        'turn', 'warm', 'wash', 'whip', 'whisk', 'wrap', 'zest',
    ];

    private const TOOLS = [
        'baking dish', 'baking pan', 'baking sheet', 'baking tray', 'blender',
        'board', 'bowl', 'cake tin', 'casserole', 'chopping board', 'colander',
        'cooling rack', 'cutting board', 'dish', 'dutch oven', 'food processor',
        'frying pan', 'griddle', 'grill pan', 'loaf pan', 'mixing bowl', 'muffin tin',
        'pan', 'pie dish', 'plate', 'pot', 'rack', 'ramekin', 'roasting pan',
        'roasting tin', 'saucepan', 'sheet pan', 'sieve', 'skillet', 'spatula',
        'springform', 'stand mixer', 'stockpot', 'strainer', 'tray', 'whisk',
        'wire rack', 'wok', 'wooden spoon',
    ];

    private const LOCATIONS = [
        'broiler', 'counter', 'countertop', 'freezer', 'fridge', 'grill',
        'hob', 'microwave', 'oven', 'refrigerator', 'stove', 'stovetop',
    ];

    private const HEAT_LEVELS = ['low', 'medium-low', 'medium', 'medium-high', 'high'];

    private const MODIFIERS = [
        'carefully', 'coarsely', 'evenly', 'finely', 'gently', 'generously',
        'lightly', 'quickly', 'roughly', 'slowly', 'thoroughly', 'vigorously', 'well',
    ];

    private const STATES = [
        'canned', 'chopped', 'cleaned', 'cooked', 'crushed', 'deseeded', 'diced',
        'drained', 'dried', 'fresh', 'frozen', 'grated', 'melted', 'minced', 'peeled',
        'raw', 'roasted', 'seasoned', 'sliced', 'softened', 'toasted', 'trimmed',
        'washed',
    ];

    private const UNITS = [
        'bottles?', 'bunch(?:es)?', 'cans?', 'cloves?', 'cups?', 'dl', 'drops?',
        'g', 'gallons?', 'handfuls?', 'jars?', 'kg', 'l', 'lb', 'lbs', 'litres?',
        'liters?', 'mg', 'ml', 'oz', 'ounces?', 'packets?', 'pieces?', 'pinch(?:es)?',
        'pints?', 'pounds?', 'quarts?', 'slices?', 'sprigs?', 'sticks?',
        'tablespoons?', 'tbsp', 'teaspoons?', 'tsp',
    ];

    private const COMPOUNDS = [
        'then', 'and', 'next', 'after that', 'alternatively'
    ];

    private array $actionSet;

    public function __construct()
    {
        $this->actionSet = array_flip(self::ACTIONS);
    }

    /**
     * Parse one or more direction sentences into structured step objects.
     *
     * @return list<array>
     */
    public function parse(string $sentence): array
    {
        $sentence = trim($sentence);
        if ($sentence === '') {
            return [];
        }

        $compounds = $this->splitCompound($sentence);

        return array_values(array_filter(
            array_map(fn (string $s) => $this->parseSingle(trim($s)), $compounds),
        ));
    }

    // ------------------------------------------------------------------
    // Compound splitting (pattern 6)
    //
    // Sequential Compound Action
    // ------------------------------------------------------------------
    // | [ACTION1], then [ACTION2] ...
    // | Mix the marinade and coat the chicken.
    // | Heat the oil, then add the onions.
    // | Remove from heat and let cool.
    // ------------------------------------------------------------------

    /**
     * compounds: then, next, after that, and, alternatively
     * @param string $sentence
     * @return string[]
     */
    private function splitCompound(string $sentence): array
    {
        $parts = preg_split('/[,;.]\s*then\s+/i', $sentence);
        if (count($parts) > 1) {
            return array_filter($parts, fn ($p) => trim($p) !== '');
        }

        $actionAlt = implode('|', array_map(fn ($a) => preg_quote($a, '/'), self::ACTIONS));
        $parts = preg_split('/\band\s+(?=' . $actionAlt . '\b)/i', $sentence);
        if (count($parts) > 1) {
            return array_filter($parts, fn ($p) => trim($p) !== '');
        }

        return [$sentence];
    }

    // ------------------------------------------------------------------
    // Single-step parser — consume-and-remove approach
    // ------------------------------------------------------------------

    private function parseSingle(string $sentence): array
    {
        $result = [];
        $work = $sentence;

        [$action, $actionWord, $work] = $this->extractAction($work);
        if ($action) {
            $result['type'] = $action;
        }

        [$duration, $work] = $this->extractDuration($work);
        if ($duration) {
            $result['duration'] = $duration;
        }

        [$heat, $work] = $this->extractHeat($work);
        if ($heat) {
            $result['heat'] = $heat;
        }

        [$condition, $work] = $this->extractCondition($work);
        if ($condition) {
            $result['condition'] = $condition;
        }

        [$tool, $work] = $this->extractTool($work);
        if ($tool) {
            $result['tool'] = $tool;
        }

        [$location, $work] = $this->extractLocation($work);
        if ($location) {
            $result['location'] = $location;
        }

        [$modifiers, $work] = $this->extractModifiers($work);
        if ($modifiers) {
            $result['modifiers'] = $modifiers;
        }

        $targets = $this->extractTargets($work);
        if ($targets) {
            $result['targets'] = $targets;
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Action extraction
    // ------------------------------------------------------------------

    private function extractAction(string $text): array
    {
        $words = preg_split('/\s+/', preg_replace('/[,.()]/', ' ', $text));
        $isConditional = (bool) preg_match('/^\s*(when|after|once|before|if|as\s+soon\s+as)\b/i', $text);
        $skippedFirst = false;

        foreach ($words as $word) {
            $stem = $this->stemVerb($word);
            if ($stem === null) {
                continue;
            }

            if ($isConditional && ! $skippedFirst) {
                $skippedFirst = true;
                continue;
            }

            $cleaned = preg_replace(
                '/\b' . preg_quote($word, '/') . '\b/i',
                '',
                $text,
                1
            );

            return [$stem, $word, $this->normalizeSpaces($cleaned)];
        }

        return [null, null, $text];
    }

    // ------------------------------------------------------------------
    // Duration: "for 10 minutes", "40-45 minutes"
    // ------------------------------------------------------------------

    private function extractDuration(string $text): array
    {
        $pattern = '/\bfor\s+(\d+(?:\s*[-–]\s*\d+)?)\s*(' . $this->timeUnitsPattern() . ')\b/i';

        if (preg_match($pattern, $text, $m)) {
            return [$this->buildDuration($m[1], $m[2]), $this->remove($text, $m[0])];
        }

        $pattern2 = '/\b(\d+(?:\s*[-–]\s*\d+)?)\s*(' . $this->timeUnitsPattern() . ')\b/i';

        if (preg_match($pattern2, $text, $m)) {
            return [$this->buildDuration($m[1], $m[2]), $this->remove($text, $m[0])];
        }

        return [null, $text];
    }

    private function buildDuration(string $valueStr, string $unitStr): array
    {
        $valueStr = preg_replace('/\s/', '', $valueStr);
        $unit = strtolower($unitStr);

        if (str_contains($valueStr, '-') || str_contains($valueStr, '–')) {
            $parts = preg_split('/[-–]/', $valueStr);
            $value = (int) ceil((intval($parts[0]) + intval($parts[1])) / 2);
        } else {
            $value = intval($valueStr);
        }

        $normalUnit = match (true) {
            str_starts_with($unit, 'hour'), str_starts_with($unit, 'hr') => 'hours',
            str_starts_with($unit, 'sec') => 'seconds',
            default => 'minutes',
        };

        return ['value' => $value, 'unit' => $normalUnit];
    }

    // ------------------------------------------------------------------
    // Heat: temperature + level
    // ------------------------------------------------------------------

    private function extractHeat(string $text): array
    {
        $heat = [];
        $cleaned = $text;

        if (preg_match('/\b(?:at\s+|to\s+)?(\d+)\s*°\s*([FCfc])\b/', $cleaned, $m)) {
            $heat['temperature'] = [
                'value' => intval($m[1]),
                'unit' => strtoupper($m[2]),
            ];
            $cleaned = $this->remove($cleaned, $m[0]);
        }

        $levels = implode('|', array_map(fn ($l) => preg_quote($l, '/'), self::HEAT_LEVELS));
        if (preg_match('/\b(?:on|over|at)\s+(' . $levels . ')\s*(?:heat)?\b/i', $cleaned, $m)) {
            $heat['level'] = strtolower($m[1]);
            $cleaned = $this->remove($cleaned, $m[0]);
        }

        return [$heat ?: null, $cleaned];
    }

    // ------------------------------------------------------------------
    // Condition: "until golden brown", leading "when X,"
    // ------------------------------------------------------------------
    // WRONG: until [target] (is) golden brown

    private function extractCondition(string $text): array
    {
        if (preg_match('/\buntil\s+(.+?)(?:\.|,|$)/i', $text, $m)) {
            return [
                ['type' => 'until', 'value' => trim($m[1])],
                $this->remove($text, $m[0]),
            ];
        }

        if (preg_match('/^\s*(when|after|once|before)\s+(.+?)\s*,/i', $text, $m)) {
            $cleaned = preg_replace(
                '/^\s*(when|after|once|before)\s+.+?\s*,\s*/i',
                '',
                $text,
                1
            );

            return [
                ['type' => strtolower($m[1]), 'value' => trim($m[2])],
                $this->normalizeSpaces($cleaned),
            ];
        }

        return [null, $text];
    }

    // ------------------------------------------------------------------
    // Tool: "in a large bowl", "with a whisk"
    // ------------------------------------------------------------------

    private function extractTool(string $text): array
    {
        $toolAlt = implode('|', array_map(fn ($t) => preg_quote($t, '/'), self::TOOLS));
        $sizeAdj = '(?:large|small|medium|big|deep|shallow|heavy|wide' .
        '|narrow|clean|separate|new|hot|cold|warm|dry|oiled|greased|non-stick)';
        $pattern = '/\b(in|on|into|onto|with|using)\s+(?:a\s+|the\s+)?(' . $sizeAdj . '\s+)?(' . $toolAlt . ')\b/i';

        if (preg_match($pattern, $text, $m)) {
            $tool = ['name' => strtolower(trim($m[3]))];
            $size = trim($m[2] ?? '');
            if ($size !== '') {
                $tool['size'] = strtolower($size);
            }

            return [$tool, $this->remove($text, $m[0])];
        }

        return [null, $text];
    }

    // ------------------------------------------------------------------
    // Location: "in the oven"
    // ------------------------------------------------------------------

    private function extractLocation(string $text): array
    {
        $locAlt = implode('|', array_map(fn ($l) => preg_quote($l, '/'), self::LOCATIONS));
        $pattern = '/\b(?:in|on|into)\s+(?:the\s+)?(' . $locAlt . ')\b/i';

        if (preg_match($pattern, $text, $m)) {
            return [strtolower($m[1]), $this->remove($text, $m[0])];
        }

        return [null, $text];
    }

    // ------------------------------------------------------------------
    // Modifiers: "gently", "thoroughly"; also "half of"
    // ------------------------------------------------------------------

    private function extractModifiers(string $text): array
    {
        $mods = [];
        $cleaned = $text;

        foreach (self::MODIFIERS as $mod) {
            if (preg_match('/\b' . preg_quote($mod, '/') . '\b/i', $cleaned)) {
                $mods['intensity'] = $mod;
                $cleaned = preg_replace(
                    '/\b' . preg_quote($mod, '/') . '\b/i',
                    '',
                    $cleaned,
                    1
                );
                break;
            }
        }

        $fractions = ['half' => 0.5, 'quarter' => 0.25, 'third' => 0.333];
        foreach ($fractions as $word => $value) {
            if (preg_match('/\b' . $word . '\s*(?:of\s+)?/i', $cleaned, $m)) {
                $mods['amount_fraction'] = $value;
                $cleaned = str_replace($m[0], '', $cleaned);
                break;
            }
        }

        return [$mods ?: null, $this->normalizeSpaces($cleaned)];
    }

    // ------------------------------------------------------------------
    // Targets (ingredients) from remaining text
    // ------------------------------------------------------------------

    private function extractTargets(string $text): array
    {
        // wrong - those may predict targets!
        $text = preg_replace('/^\s*(the|a|an|your|some|it)\s+/i', '', trim($text));
        $text = preg_replace('/\s+(the|a|an)\s+/i', ' ', $text);
        $text = $this->normalizeSpaces($text);

        if ($text === '') {
            return [];
        }

        $mainText = $text;
        $withText = '';

        if (preg_match('/\bwith\b/i', $mainText)) {
            $parts = preg_split('/\bwith\b/i', $mainText, 2);
            $mainText = trim($parts[0]);
            $withText = trim($parts[1] ?? '');
        }

        if (preg_match('/\b(?:over|onto)\b/i', $mainText)) {
            $parts = preg_split('/\b(?:over|onto)\b/i', $mainText, 2);
            $mainText = trim($parts[0]);
        }

        $strip = '/\b(from|off|on|in|into|out|up|down|back|away|together|aside)\b/i';
        $mainText = $this->normalizeSpaces(preg_replace($strip, '', $mainText));
        $withText = $this->normalizeSpaces(preg_replace($strip, '', $withText));
        $mainText = preg_replace('/\beach\s+side\b/i', '', $mainText);
        $mainText = $this->normalizeSpaces($mainText);

        $targets = [];

        foreach ($this->splitIngredientList($mainText) as $phrase) {
            $target = $this->parseIngredientPhrase($phrase);
            if ($target !== null) {
                $targets[] = $target;
            }
        }

        foreach ($this->splitIngredientList($withText) as $phrase) {
            $target = $this->parseIngredientPhrase($phrase);
            if ($target !== null) {
                $targets[] = $target;
            }
        }

        return $targets;
    }

    /** @return list<string> */
    private function splitIngredientList(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $items = preg_split('/\s*(?:,\s*(?:and\s+)?|\s+and\s+)\s*/i', $text);

        return array_filter(
            array_map('trim', $items),
            fn ($s) => $s !== '' && strlen($s) >= 2,
        );
    }

    private function parseIngredientPhrase(string $phrase): ?array
    {
        $target = [];
        $phrase = preg_replace('/^\s*(the|a|an|some)\s+/i', '', trim($phrase));

        $unitAlt = implode('|', self::UNITS);
        $qtyPattern = '/^([\d.,\/]+)\s*(' . $unitAlt . ')?\s*(?:of\s+)?/i';

        if (preg_match($qtyPattern, $phrase, $m)) {
            $amount = $this->parseAmount($m[1]);
            $target['quantity'] = ['amount' => $amount];
            if (! empty($m[2])) {
                $target['quantity']['unit'] = $this->normalizeUnit($m[2]);
            }
            $phrase = trim(substr($phrase, strlen($m[0])));
        }

        $phrase = preg_replace('/^\s*(the|a|an|some|of)\s+/i', '', $phrase);

        foreach (self::STATES as $state) {
            if (preg_match('/\b' . preg_quote($state, '/') . '\b/i', $phrase)) {
                $target['state'] = $state;
                $phrase = preg_replace(
                    '/\b' . preg_quote($state, '/') . '\b\s*/i',
                    '',
                    $phrase,
                    1
                );
                break;
            }
        }

        $phrase = $this->normalizeSpaces($phrase);
        if ($phrase === '') {
            return null;
        }

        $target['name'] = strtolower($phrase);
        $target['ingredient_id'] = preg_replace('/[^a-z0-9]+/', '_', strtolower($phrase));

        return $target;
    }

    // ------------------------------------------------------------------
    // Verb stemming
    // ------------------------------------------------------------------

    private function stemVerb(string $word): ?string
    {
        $w = strtolower($word);

        if (isset($this->actionSet[$w])) {
            return $w;
        }

        // -ing: boiling→boil, chopping→chop, baking→bake
        if (str_ends_with($w, 'ing') && strlen($w) > 4) {
            $base = substr($w, 0, -3);
            if (isset($this->actionSet[$base])) {
                return $base;
            }
            if (isset($this->actionSet[$base . 'e'])) {
                return $base . 'e';
            }
            if (
                strlen($base) > 2 && $base[-1] === $base[-2] &&
                isset($this->actionSet[substr($base, 0, -1)])
            ) {
                return substr($base, 0, -1);
            }
        }

        // -ed: roasted→roast, chopped→chop, baked→bake
        if (str_ends_with($w, 'ed') && strlen($w) > 3) {
            $b2 = substr($w, 0, -2);
            if (isset($this->actionSet[$b2])) {
                return $b2;
            }
            $b1 = substr($w, 0, -1);
            if (isset($this->actionSet[$b1])) {
                return $b1;
            }
            if (isset($this->actionSet[$b2 . 'e'])) {
                return $b2 . 'e';
            }
            if (strlen($b2) > 2 && $b2[-1] === $b2[-2] && isset($this->actionSet[substr($b2, 0, -1)])) {
                return substr($b2, 0, -1);
            }
        }

        // -s/-es: chops→chop, slices→slice, fries→fry
        if (str_ends_with($w, 's') && ! str_ends_with($w, 'ss') && strlen($w) > 3) {
            if (isset($this->actionSet[substr($w, 0, -1)])) {
                return substr($w, 0, -1);
            }
            if (
                str_ends_with($w, 'es') && strlen($w) > 4
                && isset($this->actionSet[substr($w, 0, -2)])
            ) {
                return substr($w, 0, -2);
            }
            if (
                str_ends_with($w, 'ies') && strlen($w) > 4
                && isset($this->actionSet[substr($w, 0, -3) . 'y'])
            ) {
                return substr($w, 0, -3) . 'y';
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function parseAmount(string $raw): float
    {
        $raw = str_replace(',', '.', $raw);

        if (str_contains($raw, '/')) {
            $parts = explode('/', $raw);

            return count($parts) === 2
                ? round(floatval($parts[0]) / max(floatval($parts[1]), 1), 3)
                : floatval($raw);
        }

        return floatval($raw);
    }

    private function normalizeUnit(string $unit): string
    {
        $u = strtolower(rtrim($unit, 's'));

        return match ($u) {
            'tablespoon' => 'tbsp',
            'teaspoon' => 'tsp',
            'pound' => 'lb',
            'ounce' => 'oz',
            'litre', 'liter' => 'l',
            default => strtolower($unit),
        };
    }

    private function timeUnitsPattern(): string
    {
        return 'minutes?|mins?|hours?|hrs?|seconds?|secs?';
    }

    private function remove(string $text, string $match): string
    {
        return $this->normalizeSpaces(str_replace($match, '', $text));
    }

    private function normalizeSpaces(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
