<?php

namespace App\Services;

/**
 * Parses a direction sentence for preview (ingredients, tools, duration).
 * Keep this file in sync with api/app/Services/DirectionParser.php (the API app uses it
 * for POST /v1/recipes/{slug}/directions/from-text); there is no automatic sync.
 */
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
        'drained', 'dried', 'dry', 'fresh', 'frozen', 'grated', 'melted', 'minced', 'peeled',
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

    /** Unit regex alternation with word boundary so e.g. "l" does not match inside "lime". */
    private function unitsPattern(): string
    {
        return implode('\b|', self::UNITS) . '\b';
    }

    private const COMPOUNDS = [
        'then', 'and', 'next', 'after that', 'alternatively'
    ];

    /** Names that refer to mixtures/intermediates from previous steps, not pantry products. */
    private const INTERMEDIATE_NAMES = [
        'spice mix', 'spice mixture', 'spices', 'marinade', 'sauce', 'mixture', 'batter', 'dough',
        'paste', 'rub', 'glaze', 'dressing', 'custard', 'frosting', 'icing', 'stuffing', 'filling',
        'relish', 'salsa', 'dip', 'purée', 'puree', 'reduction', 'stock', 'broth', 'base',
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
        $steps = [];
        foreach ($compounds as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $step = $this->parseSingle($part);
            if ($step !== []) {
                $step['source_text'] = $part;
                $steps[] = $step;
            }
        }

        // "cover with X" (implicit target): inject previous step's main ingredient as first so step is "cover {chicken} with {spice mix}"
        for ($i = 1, $n = count($steps); $i < $n; $i++) {
            if (! empty($steps[$i]['target_from_previous_step']) && ! empty($steps[$i - 1]['ingredients'])) {
                $prevFirst = $steps[$i - 1]['ingredients'][0];
                $steps[$i]['ingredients'] = array_merge([$prevFirst], $steps[$i]['ingredients'] ?? []);
                unset($steps[$i]['target_from_previous_step']);
            }
        }

        // "Cover and refrigerate" → one step: refrigerate (covered) for 2 hours; drop the bare "cover" step
        $steps = $this->mergeCoverIntoRefrigerate($steps);

        return $steps;
    }

    /**
     * Merge a lone "cover" step into the following "refrigerate" step as "refrigerate in a way: covered".
     *
     * @param list<array> $steps
     * @return list<array>
     */
    private function mergeCoverIntoRefrigerate(array $steps): array
    {
        $out = [];
        $i = 0;
        while ($i < count($steps)) {
            $step = $steps[$i];
            $next = $steps[$i + 1] ?? null;
            if (
                isset($step['type']) && $step['type'] === 'cover'
                && empty($step['ingredients']) && empty($step['duration'])
                && $next !== null && isset($next['type']) && $next['type'] === 'refrigerate'
            ) {
                $next['modifiers'] = array_merge($next['modifiers'] ?? [], ['covered' => true]);
                $out[] = $next;
                $i += 2;
                continue;
            }
            $out[] = $step;
            $i++;
        }
        return $out;
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
        $parts = $this->splitCompoundOnce($sentence);
        if (count($parts) <= 1) {
            return $parts;
        }
        // After ", then " or "along with", also split each part on " and " + action (e.g. "cover and refrigerate for 2 hours" → "cover" + "refrigerate for 2 hours")
        $expanded = [];
        foreach ($parts as $p) {
            $sub = $this->splitCompoundOnce(trim($p));
            $expanded = array_merge($expanded, $sub);
        }

        return array_values(array_filter($expanded, fn ($p) => trim($p) !== ''));
    }

    /**
     * One level of compound split: ", then " or "along with" or " and " + action.
     *
     * @return list<string>
     */
    private function splitCompoundOnce(string $sentence): array
    {
        $sentence = trim($sentence);
        if ($sentence === '') {
            return [];
        }

        $parts = preg_split('/[,;.]\s*then\s+/i', $sentence);
        if (count($parts) > 1) {
            return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
        }

        // "X, along with Y" → two steps: X and "add Y" (second step gets implicit "add")
        if (preg_match('/\s*,?\s*along\s+with\s+/i', $sentence)) {
            $parts = preg_split('/\s*,?\s*along\s+with\s+/i', $sentence, 2);
            if (count($parts) === 2) {
                $first = trim($parts[0]);
                $second = trim($parts[1]);
                if ($first !== '' && $second !== '') {
                    return [$first, 'add ' . $second];
                }
            }
        }

        // "X and [modifier] Y" or "X and action Y" → split so "and generously cover" / "and refrigerate" becomes a new step
        $modAlt = implode('|', array_map(fn ($m) => preg_quote($m, '/'), self::MODIFIERS));
        $actionAlt = implode('|', array_map(fn ($a) => preg_quote($a, '/'), self::ACTIONS));
        $parts = preg_split('/\band\s+(?=(?:' . $modAlt . '\s+' . $actionAlt . '\b|' . $actionAlt . '\b))/i', $sentence);
        if (count($parts) > 1) {
            return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
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

        // Extract tool first so "In a separate small bowl, mix..." is stripped before action
        [$tool, $work] = $this->extractTool($work);
        if ($tool) {
            $result['tool'] = $tool;
        }
        $work = $this->normalizeSpaces(preg_replace('/^[\s,;]+/', '', $work));

        // "In a large boiling pot of water, cook ..." → tool=pot, first ingredient=water, then action=cook and pasta
        // Case A: we matched "large boiling pot", remainder is " of water, cook..."
        if (preg_match('/^of\s+(?:boiling\s+)?(?:cold\s+)?(?:hot\s+)?water\s*,?\s*/i', $work, $waterMatch)) {
            $result['_water_ingredient'] = [$this->ingredientFromName('water')];
            $work = trim($this->remove($work, $waterMatch[0]));
        }
        // Case B: we only matched "large pot", remainder is " boiling of water, cook..." or " pot of water, cook..." — strip and add water so we don't get "pot of water" / "boiling of water" as ingredient
        elseif (preg_match('/^(?:\s*(?:boiling\s+)?pot\s+of\s+water\s*,?\s*)/i', $work, $potWaterMatch)) {
            $result['_water_ingredient'] = [$this->ingredientFromName('water')];
            $work = trim($this->remove($work, $potWaterMatch[0]));
        }

        [$action, $actionWord, $work] = $this->extractAction($work);
        if ($action) {
            $result['type'] = $action;
        }

        // "Transfer the entire contents of the X bowl to a large baking pan so that ..." → tools [source, destination], condition; no ingredients
        if (isset($result['type']) && $result['type'] === 'transfer') {
            [$fromTool, $toTool, $transferCondition, $work] = $this->parseTransferClause($work);
            $tools = array_values(array_filter([$fromTool, $toTool]));
            if ($tools !== []) {
                $result['tools'] = $tools;
            }
            if ($transferCondition !== null) {
                $result['condition'] = $transferCondition;
            }
        }

        [$duration, $work] = $this->extractDuration($work);
        if ($duration) {
            $result['duration'] = $duration;
        }

        [$heat, $work] = $this->extractHeat($work);
        if ($heat) {
            $result['heat'] = $heat;
        }

        // Preheat: temperature as condition (not "heat") and oven as tool
        if (isset($result['type']) && $result['type'] === 'preheat' && ! empty($result['heat']['temperature'])) {
            $result['condition'] = [
                'type' => 'temperature',
                'value' => $result['heat']['temperature']['value'],
                'unit' => $result['heat']['temperature']['unit'],
            ];
            unset($result['heat']['temperature']);
            if ($result['heat'] === []) {
                unset($result['heat']);
            } else {
                $result['heat'] = $result['heat'];
            }
        }

        [$condition, $work] = $this->extractCondition($work);
        if ($condition) {
            $result['condition'] = $condition;
        }

        [$location, $work] = $this->extractLocation($work);
        if ($location) {
            $result['location'] = $location;
        }

        [$modifiers, $work] = $this->extractModifiers($work);
        if ($modifiers) {
            $result['modifiers'] = $modifiers;
        }

        // "add X to Y" → parse X as ingredients, then add Y as second ingredient (so "add chicken to marinade" = ingredients [chicken, marinade])
        // "add X to Y, along with A, B, and C" → to-target = Y only; parse X and A,B,C as separate ingredients
        if (isset($result['type']) && $result['type'] === 'add' && preg_match('/^(.+?)\s+to\s+(.+)$/i', $work, $toMatch)) {
            $afterTo = trim($toMatch[2]);
            if ($afterTo !== '' && ! preg_match('/^(taste|boil|simmer|cool|rest)\b/i', $afterTo)) {
                $work = trim($toMatch[1]);
                $toTarget = $afterTo;
                $alongWith = '';
                if (preg_match('/^(.+?),\s*along\s+with\s+(.+)$/is', $afterTo, $aw)) {
                    $toTarget = trim($aw[1]);
                    $alongWith = trim($aw[2]);
                }
                $toIngredient = strtolower(preg_replace('/^\s*(the|a|an)\s+/i', '', $toTarget));
                $toIngredient = preg_replace('/[.,;]+$/', '', $toIngredient);
                if ($toIngredient !== '' && strlen($toIngredient) >= 2) {
                    $result['_add_to_ingredient'] = $this->ingredientFromName($toIngredient);
                }
                if ($alongWith !== '') {
                    $work = $work . ', ' . $alongWith;
                }
            }
        }

        // ", if you like" / ", if desired" → step is optional; strip so it doesn't become an ingredient
        if (preg_match('/\s*,\s*if you (?:like|want)\s*\.?\s*$/i', $work, $optMatch)) {
            $result['optional'] = true;
            $work = trim($this->remove($work, $optMatch[0]));
        }
        if (preg_match('/\s*,\s*if desired\s*\.?\s*$/i', $work, $optMatch)) {
            $result['optional'] = true;
            $work = trim($this->remove($work, $optMatch[0]));
        }

        // "according to package instructions" / "per package directions" → procedural note; strip so it doesn't become part of ingredient name
        if (preg_match('/\s*,?\s*according to package instructions?\s*\.?\s*$/i', $work, $pkgMatch)) {
            $work = trim($this->remove($work, $pkgMatch[0]));
        }
        if (preg_match('/\s*,?\s*(?:per|according to) package directions?\s*\.?\s*$/i', $work, $pkgMatch)) {
            $work = trim($this->remove($work, $pkgMatch[0]));
        }

        $ingredients = $this->extractIngredients($work);
        if (isset($result['_water_ingredient'])) {
            $ingredients = array_merge($result['_water_ingredient'], $ingredients);
            unset($result['_water_ingredient']);
        }
        if (isset($result['_add_to_ingredient'])) {
            $ingredients = array_merge($ingredients, [$result['_add_to_ingredient']]);
            unset($result['_add_to_ingredient']);
        }
        // "X into Y" / "half of X into Y" = transfer between intermediates (spice mix, marinade); no new recipe ingredients
        $intoTransfer = $this->getIntoTransfer($work);
        if ($intoTransfer !== null) {
            $result['into_transfer'] = $intoTransfer;
            $ingredients = [];
        }
        if ($ingredients) {
            $result['ingredients'] = $ingredients;
            // Move step-level amount_fraction onto the first ingredient when we have two (e.g. "half of X into Y") so it's clear the fraction applies to the source
            if (isset($result['modifiers']['amount_fraction']) && count($result['ingredients']) >= 2) {
                $first = &$result['ingredients'][0];
                $frac = $result['modifiers']['amount_fraction'];
                if (! isset($first['quantity'])) {
                    $first['quantity'] = ['amount' => $frac];
                }
                unset($result['modifiers']['amount_fraction']);
                if ($result['modifiers'] === []) {
                    unset($result['modifiers']);
                }
            }
        }

        // "cover with X" but no explicit object → the thing being covered is from the previous step (e.g. chicken)
        if (isset($result['type']) && $result['type'] === 'cover' && ! empty($result['ingredients'])) {
            if ($this->coverTargetIsImplicit($sentence)) {
                $result['target_from_previous_step'] = true;
            }
        }

        // "refrigerate" / "refrigerate for 2 hours" → tool is refrigerator
        if (isset($result['type']) && $result['type'] === 'refrigerate') {
            $result['tool'] = ['name' => 'refrigerator'];
        }

        // Transfer: if we still have a single "to X" ingredient (parseTransferClause missed it), add as second tool and drop ingredient
        if (isset($result['type']) && $result['type'] === 'transfer' && ! empty($result['ingredients']) && count($result['ingredients']) === 1) {
            $name = trim($result['ingredients'][0]['name'] ?? '');
            if (preg_match('/^to\s+(?:a|the)?\s*(.+)$/i', $name, $m)) {
                $targetPhrase = trim($m[1]);
                $parsed = $this->parseToolPhrase($targetPhrase);
                if ($parsed !== null) {
                    $result['tools'] = array_merge($result['tools'] ?? [], [$parsed]);
                    unset($result['ingredients']);
                }
            }
        }

        // "Preheat your oven to 475°F" → oven is tool, not ingredient
        if (isset($result['type']) && $result['type'] === 'preheat' && ! empty($result['ingredients'])) {
            $keep = [];
            foreach ($result['ingredients'] as $ing) {
                $name = strtolower(trim($ing['name'] ?? ''));
                $id = strtolower(trim($ing['ingredient_id'] ?? ''));
                if ($name === 'oven' || $id === 'oven' || preg_match('/^(the|your)\s+oven$/i', $name)) {
                    if (empty($result['tool'])) {
                        $result['tool'] = ['name' => 'oven'];
                    }
                    continue;
                }
                $keep[] = $ing;
            }
            $result['ingredients'] = $keep;
            if ($result['ingredients'] === []) {
                unset($result['ingredients']);
            }
        }

        // Fallback: if no tool was found but sentence starts with "in a/the ...", parse leading phrase as tool
        if (empty($result['tool']) && preg_match('/^\s*in\s+(?:a|the)\s+/i', $sentence)) {
            $parts = preg_split('/\s*,\s*/', $sentence, 2);
            $leading = trim($parts[0] ?? '');
            if ($leading !== '' && preg_match('/^\s*in\s+(?:a|the)\s+(.+)$/i', $leading, $m)) {
                $afterArticle = trim($m[1]);
                $toolFromPhrase = $this->parseToolPhrase($afterArticle);
                if ($toolFromPhrase !== null) {
                    $result['tool'] = $toolFromPhrase;
                }
            }
        }

        return $result;
    }

    /**
     * True when "cover with X" has no explicit object — the thing being covered is from context (e.g. previous step).
     */
    private function coverTargetIsImplicit(string $sentence): bool
    {
        $parts = preg_split('/\bwith\b/i', $sentence, 2);
        $beforeWith = trim($parts[0] ?? '');
        if ($beforeWith === '') {
            return true;
        }
        $beforeWith = preg_replace('/\bcover\b/i', '', $beforeWith);
        $modAlt = implode('|', array_map(fn ($m) => preg_quote($m, '/'), self::MODIFIERS));
        $beforeWith = preg_replace('/\b(?:' . $modAlt . ')\b/i', '', $beforeWith);
        $beforeWith = preg_replace('/\b(the|a|an)\b/i', '', $beforeWith);
        $beforeWith = $this->normalizeSpaces($beforeWith);

        return $beforeWith === '';
    }

    /**
     * Parse "separate small bowl" or "large bowl" into tool array, or null.
     */
    private function parseToolPhrase(string $phrase): ?array
    {
        $phrase = strtolower(trim($phrase));
        if ($phrase === '') {
            return null;
        }
        $toolsSorted = self::TOOLS;
        usort($toolsSorted, fn ($a, $b) => strlen($b) - strlen($a));
        $adjSet = array_flip(array_map('strtolower', self::TOOL_ADJECTIVES));
        foreach ($toolsSorted as $toolName) {
            $toolLower = strtolower($toolName);
            if ($phrase === $toolLower) {
                return ['name' => $toolLower];
            }
            if (str_ends_with($phrase, ' ' . $toolLower)) {
                $prefix = trim(substr($phrase, 0, -strlen($toolLower) - 1));
                if ($prefix === '') {
                    return ['name' => $toolLower];
                }
                $words = preg_split('/\s+/', $prefix, -1, PREG_SPLIT_NO_EMPTY);
                $allAdj = true;
                foreach ($words as $w) {
                    if (! isset($adjSet[$w])) {
                        $allAdj = false;
                        break;
                    }
                }
                if ($allAdj) {
                    $tool = ['name' => $toolLower];
                    $size = $this->toolSizeFromAdjectives($prefix);
                    if ($size !== '') {
                        $tool['size'] = $size;
                    }
                    return $tool;
                }
            }
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Action extraction
    // ------------------------------------------------------------------

    /** Phrasal actions: phrase => normalized type (meaning "add juice of" etc.) */
    private const PHRASAL_ACTIONS = [
        'juice in' => 'juice_in',  // "juice in 1 lime" = add juice of 1 lime
    ];

    private function extractAction(string $text): array
    {
        foreach (self::PHRASAL_ACTIONS as $phrase => $type) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/i', $text)) {
                $cleaned = preg_replace(
                    '/\b' . preg_quote($phrase, '/') . '\b/i',
                    '',
                    $text,
                    1
                );
                return [$type, $phrase, $this->normalizeSpaces($cleaned)];
            }
        }

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
            $value = trim($m[1]);
            $value = $this->normalizeSpaces(preg_replace('/\s*\(\s*\)\s*/', ' ', $value));
            return [
                ['type' => 'until', 'value' => $value],
                $this->remove($text, $m[0]),
            ];
        }

        // "to coat" = until coated (not an ingredient)
        if (preg_match('/\bto\s+coat\b/i', $text, $m)) {
            return [
                ['type' => 'until', 'value' => 'coated'],
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
    // Tool: "in a large bowl", "with a whisk", "in a separate small bowl"
    // ------------------------------------------------------------------

    /** Physical size/type adjectives to store on tool; "separate", "clean" etc. are dropped. */
    private const TOOL_SIZE_ADJECTIVES = [
        'large', 'small', 'medium', 'big', 'deep', 'shallow', 'heavy', 'wide',
        'narrow', 'new', 'hot', 'cold', 'warm', 'dry', 'oiled', 'greased', 'non-stick',
    ];

    /** Adjectives allowed before tool name (consumed but only size-adjectives stored). */
    private const TOOL_ADJECTIVES = [
        'large', 'small', 'medium', 'big', 'deep', 'shallow', 'heavy', 'wide',
        'narrow', 'clean', 'separate', 'new', 'hot', 'cold', 'warm', 'dry', 'oiled', 'greased', 'non-stick',
        'boiling',
    ];

    private function extractTool(string $text): array
    {
        $toolAlt = implode('|', array_map(fn ($t) => preg_quote($t, '/'), self::TOOLS));
        $adjAlt = implode('|', array_map(fn ($a) => preg_quote($a, '/'), self::TOOL_ADJECTIVES));

        // Two-step: find preposition+article, then adjectives+tool in the rest (avoids complex backtracking)
        if (preg_match('/\b(in|on|into|onto|with|using)\s+(?:a\s+|the\s+)/i', $text, $pref, PREG_OFFSET_CAPTURE)) {
            $prefix = $pref[0][0];
            $start = $pref[0][1];
            $rest = substr($text, $start + strlen($prefix));
            $subPattern = '/^(\s*(?:(?:' . $adjAlt . ')\s+)*)(' . $toolAlt . ')\b/i';
            if (preg_match($subPattern, $rest, $m)) {
                $fullMatch = substr($text, $start, strlen($prefix) + strlen($m[0]));
                $tool = ['name' => strtolower(trim($m[2]))];
                $size = $this->toolSizeFromAdjectives(trim($m[1] ?? ''));
                if ($size !== '') {
                    $tool['size'] = $size;
                }

                $remaining = $this->remove($text, $fullMatch);
                return [$tool, $remaining];
            }
        }

        return [null, $text];
    }

    /** Return the tool size to store: only physical-size adjective, drop "separate", "clean", etc. */
    private function toolSizeFromAdjectives(string $adjectives): string
    {
        if ($adjectives === '') {
            return '';
        }
        $words = preg_split('/\s+/', strtolower($adjectives), -1, PREG_SPLIT_NO_EMPTY);
        $stored = array_flip(self::TOOL_SIZE_ADJECTIVES);
        foreach (array_reverse($words) as $w) {
            if (isset($stored[$w])) {
                return $w;
            }
        }
        return '';
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

    /**
     * Parse "transfer the entire contents of the X bowl to a large baking pan so that ... in one layer".
     * Returns [source_tool, destination_tool, condition, remaining_text]. Order = used earlier first for tools.
     *
     * @return array{0: array|null, 1: array|null, 2: array|null, 3: string}
     */
    private function parseTransferClause(string $text): array
    {
        $work = $this->normalizeSpaces(trim($text));
        $fromTool = null;
        $toTool = null;
        $condition = null;

        // Build tool alternation (longest first so "baking pan" matches before "pan")
        $toolsSorted = self::TOOLS;
        usort($toolsSorted, fn ($a, $b) => strlen($b) - strlen($a));
        $toolAlt = implode('|', array_map(fn ($t) => preg_quote($t, '/'), $toolsSorted));
        $adjAlt = implode('|', array_map(fn ($a) => preg_quote($a, '/'), self::TOOL_ADJECTIVES));

        // 1. " so that ... in one layer" → arrangement condition (remove first so it doesn't affect other matches)
        if (preg_match('/\s+so that\s+.+?\s+in (?:one|a single) layer\.?\s*$/is', $work, $m)) {
            $condition = ['type' => 'arrangement', 'value' => 'one layer'];
            $work = trim($this->remove($work, $m[0]));
        }

        // 2. " to (a|the) (phrase)" → destination tool; parse phrase as "[adj]* tool" (e.g. "large baking pan")
        if (preg_match('/\s+to\s+(?:a|the)\s+(.+?)(?=\s+so that|\s*$)/is', $work, $toMatch)) {
            $targetPhrase = trim($toMatch[1]);
            $fullMatch = $toMatch[0];
            if (preg_match('/^((?:' . $adjAlt . '\s+)*)(' . $toolAlt . ')\s*$/i', $targetPhrase, $m)) {
                $toTool = ['name' => strtolower(trim($m[2]))];
                $size = $this->toolSizeFromAdjectives(trim($m[1] ?? ''));
                if ($size !== '') {
                    $toTool['size'] = $size;
                }
                $work = trim($this->remove($work, $fullMatch));
            }
        }

        // 3. "(the entire )?contents of (the )?(desc )?(tool)" → source tool (non-greedy desc so we get "bowl" not "pan")
        if (preg_match('/^(?:the entire\s+)?contents of (?:the\s+)?(.+?)\s+(' . $toolAlt . ')\b/i', $work, $m)) {
            $fromTool = ['name' => strtolower(trim($m[2]))];
            $work = trim($this->remove($work, $m[0]));
        }

        return [$fromTool, $toTool, $condition, $work];
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

    /**
     * Expand "quantity? each A, B, and C" into one ingredient per item with shared quantity.
     * List is comma-separated; last item may be preceded by ", and".
     *
     * @return array{0: list<array>, 1: string} [ingredients, remaining_text]
     */
    private function expandEachBlock(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [[], ''];
        }

        $unitAlt = $this->unitsPattern();
        $amountPattern = '\d+\s+\d+\/\d+|[\d.,\/]+';
        $quantityOptional = '(?:(' . $amountPattern . ')\s*(' . $unitAlt . ')?\s*(?:of\s+)?)?';
        $listToEnd = '(.+)$';

        if (! preg_match('/\b' . $quantityOptional . 'each\s+' . $listToEnd . '/is', $text, $m)) {
            return [[], $text];
        }

        $amountRaw = $m[1] ?? null;
        $unitRaw = $m[2] ?? null;
        $listStr = trim($m[3] ?? '');

        if ($listStr === '') {
            return [[], $text];
        }

        $sharedQuantity = null;
        if ($amountRaw !== null && $amountRaw !== '') {
            $sharedQuantity = ['amount' => $this->parseAmount($amountRaw)];
            if (! empty($unitRaw)) {
                $sharedQuantity['unit'] = $this->normalizeUnit($unitRaw);
            }
        }

        $items = $this->splitEachList($listStr);
        $ingredients = [];

        foreach ($items as $item) {
            $ingredient = $this->parseIngredientPhrase($item);
            if ($ingredient === null) {
                continue;
            }
            if ($sharedQuantity !== null) {
                $ingredient['quantity'] = $sharedQuantity;
            }
            $ingredients[] = $ingredient;
        }

        $matched = $m[0];
        $pos = strpos($text, $matched);
        $remaining = $pos === 0
            ? trim(substr($text, strlen($matched)))
            : trim(substr($text, 0, $pos));

        return [$ingredients, $remaining];
    }

    /**
     * Split "A, B, and C" into ["A", "B", "C"].
     *
     * @return list<string>
     */
    private function splitEachList(string $listStr): array
    {
        $parts = preg_split('/\s*,\s*/', $listStr);
        $out = [];
        foreach ($parts as $p) {
            $p = trim(preg_replace('/^\s*and\s+/i', '', trim($p)));
            $p = preg_replace('/[.,;]+$/', '', $p);
            if ($p !== '' && strlen($p) >= 2) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * Detect "X into Y" / "half of X into Y" as intermediate transfer (no new ingredients).
     * Returns structured data for display; use this to avoid treating source/target as products.
     *
     * @return array{source: string, target: string, fraction: ?float}|null
     */
    public function getIntoTransfer(string $text): ?array
    {
        $text = $this->normalizeSpaces(trim($text));
        if ($text === '') {
            return null;
        }
        if (! preg_match('/^(?:about\s+)?(half|quarter|third)?(?:\s+of\s+(?:the\s+)?)?(.+?)\s+into\s+(?:the\s+)?(.+?)\.?\s*$/is', $text, $m)) {
            return null;
        }
        $fractionWord = isset($m[1]) && $m[1] !== '' ? strtolower($m[1]) : null;
        $source = $this->normalizeSpaces(preg_replace('/^\s*(the|a|an)\s+/i', '', trim($m[2])));
        $target = $this->normalizeSpaces(preg_replace('/^\s*(the|a|an)\s+/i', '', trim(preg_replace('/\.$/', '', trim($m[3])))));
        if ($source === '' || $target === '' || strlen($source) < 2 || strlen($target) < 2) {
            return null;
        }
        $fraction = $fractionWord !== null ? match ($fractionWord) {
            'half' => 0.5,
            'quarter' => 0.25,
            'third' => 0.333,
            default => 0.5,
        } : null;

        return ['source' => $source, 'target' => $target, 'fraction' => $fraction];
    }

    /**
     * "about half of the spice mix into the marinade" → [spice mix, marinade] as two ingredients.
     * Returns null if the pattern doesn't match.
     *
     * @return list<array>|null
     */
    private function extractIntoPattern(string $text): ?array
    {
        $text = $this->normalizeSpaces(trim($text));
        if ($text === '') {
            return null;
        }
        // (?:about\s+)?(half|quarter|third)?(?:\s+of\s+(?:the\s+)?)?  source  \s+into\s+(?:the\s+)?  target  [.]
        if (! preg_match('/^(?:about\s+)?(half|quarter|third)?(?:\s+of\s+(?:the\s+)?)?(.+?)\s+into\s+(?:the\s+)?(.+?)\.?\s*$/is', $text, $m)) {
            return null;
        }
        $fractionWord = isset($m[1]) && $m[1] !== '' ? strtolower($m[1]) : null;
        $source = $this->normalizeSpaces(preg_replace('/^\s*(the|a|an)\s+/i', '', trim($m[2])));
        $target = $this->normalizeSpaces(preg_replace('/^\s*(the|a|an)\s+/i', '', trim(preg_replace('/\.$/', '', trim($m[3])))));
        if ($source === '' || $target === '' || strlen($source) < 2 || strlen($target) < 2) {
            return null;
        }

        $sourceIngredient = $this->ingredientFromName($source);
        if ($fractionWord !== null) {
            $sourceIngredient['quantity'] = ['amount' => match ($fractionWord) {
                'half' => 0.5,
                'quarter' => 0.25,
                'third' => 0.333,
                default => 0.5,
            }];
        }

        return [
            $sourceIngredient,
            $this->ingredientFromName($target),
        ];
    }

    private function ingredientFromName(string $name): array
    {
        $name = strtolower(trim($name));
        return [
            'name' => $name,
            'ingredient_id' => preg_replace('/[^a-z0-9]+/', '_', $name),
        ];
    }

    /**
     * Extract ingredients from a single clause (e.g. the with-clause) without "with" split.
     *
     * @return list<array>
     */
    private function extractIngredientsFromText(string $text): array
    {
        $text = $this->normalizeSpaces(preg_replace('/\b(from|off|on|in|into|out|up|down|back|away|together|aside)\b/i', '', $text));
        $text = $this->normalizeSpaces($text);
        if ($text === '') {
            return [];
        }
        $out = [];
        [$eachIngredients, $text] = $this->expandEachBlock($text);
        foreach ($eachIngredients as $ing) {
            $out[] = $ing;
        }
        foreach ($this->splitIngredientList($text) as $phrase) {
            if ($this->isProceduralPhrase($phrase)) {
                continue;
            }
            $ingredient = $this->parseIngredientPhrase($phrase);
            if ($ingredient !== null && ! $this->isActionVerb($ingredient['name']) && ! $this->isToolPhrase($ingredient['name']) && ! $this->isNoiseWord($ingredient['name']) && ! $this->isIntermediateReference($ingredient)) {
                $out[] = $ingredient;
            }
        }
        return $out;
    }

    /**
     * True if the phrase is a procedural note (e.g. "lifting the skin", "applying some underneath")
     * rather than an ingredient — skip adding as ingredient.
     */
    private function isProceduralPhrase(string $phrase): bool
    {
        $phrase = trim($phrase);
        if ($phrase === '') {
            return false;
        }
        $phrase = preg_replace('/^\s*(the|a|an|some)\s+/i', '', $phrase);
        $first = preg_split('/\s+/', $phrase, 2)[0] ?? '';
        if ($first === '') {
            return false;
        }
        $gerunds = ['lifting', 'applying', 'stirring', 'folding', 'spreading', 'rubbing', 'pressing', 'sprinkling', 'drizzling', 'adding', 'removing', 'transferring', 'placing', 'setting', 'leaving', 'continuing', 'repeating', 'returning', 'covering', 'uncovering'];
        return in_array(strtolower($first), $gerunds, true);
    }

    private function extractIngredients(string $text): array
    {
        // wrong - those may predict ingredients!
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

        // "half of the X into the Y" / "X into the Y" → two ingredients (source + target), then skip normal parse
        $intoIngredients = $this->extractIntoPattern($mainText);
        if ($intoIngredients !== null) {
            $rest = $withText !== '' ? $this->extractIngredientsFromText($withText) : [];

            return array_merge($intoIngredients, $rest);
        }

        $strip = '/\b(from|off|on|in|into|out|up|down|back|away|together|aside)\b/i';
        $mainText = $this->normalizeSpaces(preg_replace($strip, '', $mainText));
        $withText = $this->normalizeSpaces(preg_replace($strip, '', $withText));
        $mainText = preg_replace('/\beach\s+side\b/i', '', $mainText);
        $mainText = $this->normalizeSpaces($mainText);

        $ingredients = [];

        [$eachIngredients, $mainText] = $this->expandEachBlock($mainText);
        foreach ($eachIngredients as $ing) {
            $ingredients[] = $ing;
        }

        foreach ($this->splitIngredientList($mainText) as $phrase) {
            if ($this->isProceduralPhrase($phrase)) {
                continue;
            }
            $ingredient = $this->parseIngredientPhrase($phrase);
            if ($ingredient !== null && ! $this->isActionVerb($ingredient['name']) && ! $this->isToolPhrase($ingredient['name']) && ! $this->isNoiseWord($ingredient['name']) && ! $this->isIntermediateReference($ingredient)) {
                $ingredients[] = $ingredient;
            }
        }

        [$eachWith, $withText] = $this->expandEachBlock($withText);
        foreach ($eachWith as $ing) {
            if (! $this->isIntermediateReference($ing)) {
                $ingredients[] = $ing;
            }
        }

        foreach ($this->splitIngredientList($withText) as $phrase) {
            if ($this->isProceduralPhrase($phrase)) {
                continue;
            }
            $ingredient = $this->parseIngredientPhrase($phrase);
            if ($ingredient !== null && ! $this->isActionVerb($ingredient['name']) && ! $this->isToolPhrase($ingredient['name']) && ! $this->isNoiseWord($ingredient['name']) && ! $this->isIntermediateReference($ingredient)) {
                $ingredients[] = $ingredient;
            }
        }

        return $ingredients;
    }

    /** Words that are never ingredient names (e.g. "along" from "along with"). */
    private function isNoiseWord(string $name): bool
    {
        return in_array(strtolower(trim($name)), ['along'], true);
    }

    /**
     * True if this "ingredient" is a reference to an intermediate (e.g. marinade, spice mix).
     * Do not add as a recipe ingredient — it's from a previous step.
     */
    private function isIntermediateReference(array $ingredient): bool
    {
        return $this->isIntermediateName($ingredient['name'] ?? '');
    }

    /** True if name refers to a mixture/intermediate (spice mix, marinade, etc.), not a pantry product. */
    private function isIntermediateName(string $name): bool
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return false;
        }
        foreach (self::INTERMEDIATE_NAMES as $intermediate) {
            if ($name === $intermediate || str_contains($name, $intermediate) || str_contains($intermediate, $name)) {
                return true;
            }
        }
        return false;
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
        $phrase = preg_replace('/\s+to\s+taste\b/i', '', $phrase);
        if (preg_match('/\s*\(optional\)\s*$/i', $phrase)) {
            $phrase = preg_replace('/\s*\(optional\)\s*$/i', '', $phrase);
            $target['optional'] = true;
        }
        $phrase = $this->normalizeSpaces($phrase);

        // "more X" → quantifier (more), ingredient name = X
        if (preg_match('/^more\s+(.+)$/is', $phrase, $m)) {
            $target['quantity'] = ($target['quantity'] ?? []) + ['more' => true];
            $phrase = trim($m[1]);
        }

        // "dash of X" / "pinch of X" / "splash of X" → quantity.unit + ingredient X
        if (preg_match('/^(dash|pinch|splash)\s+of\s+(.+)$/is', $phrase, $m)) {
            $target['quantity'] = ($target['quantity'] ?? []) + ['unit' => $this->normalizeUnit($m[1])];
            $phrase = trim($m[2]);
        }
        $phrase = $this->normalizeSpaces($phrase);

        // "remainder of (the)? X" / "rest of (the)? X" → ingredient X with quantity.remainder/rest = true
        if (preg_match('/^(?:remainder|rest)\s+of\s+(?:the\s+)?(.+)$/is', $phrase, $rm)) {
            $rest = trim($rm[1]);
            $rest = preg_replace('/[.,;]+$/', '', $rest);
            if ($rest !== '' && strlen($rest) >= 2) {
                $key = preg_match('/\brest\s+of\b/i', $phrase) ? 'rest' : 'remainder';
                $target['quantity'] = [$key => true];
                $target['name'] = strtolower($rest);
                $target['ingredient_id'] = preg_replace('/[^a-z0-9]+/', '_', strtolower($rest));
                return $target;
            }
        }

        $unitAlt = $this->unitsPattern();
        // Amount: "1 1/2" (one and a half) or simple number/fraction "1", "1/2", "2.5"
        $amountPattern = '\d+\s+\d+\/\d+|[\d.,\/]+';
        $qtyPattern = '/^(' . $amountPattern . ')\s*(' . $unitAlt . ')?\s*(?:of\s+)?/i';

        if (preg_match($qtyPattern, $phrase, $m)) {
            $amount = $this->parseAmount(trim($m[1]));
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

        // Size adjective (large, small, etc.) → ingredient size, not part of name
        if (preg_match('/^(large|small|medium|big)\s+/i', $phrase, $sm)) {
            $target['size'] = strtolower($sm[1]);
            $phrase = trim(substr($phrase, strlen($sm[0])));
        }

        $phrase = $this->normalizeSpaces($phrase);
        if ($phrase === '') {
            return null;
        }

        $name = strtolower($phrase);
        $name = preg_replace('/\s+bulb\b$/i', '', $name);
        $name = $this->singularizeIngredientName($name);
        $target['name'] = $name;
        $target['ingredient_id'] = preg_replace('/[^a-z0-9]+/', '_', $name);

        return $target;
    }

    /** "oranges" → "orange", "limes" → "lime"; leave "fennel", "onion" etc. unchanged. */
    private function singularizeIngredientName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }
        // Remove trailing "s" only (so "oranges" → "orange", "limes" → "lime"; not "orang")
        if (str_ends_with($name, 's') && ! str_ends_with($name, 'ss') && strlen($name) > 1) {
            return substr($name, 0, -1);
        }
        return $name;
    }

    // ------------------------------------------------------------------
    // Verb stemming
    // ------------------------------------------------------------------

    /**
     * True if the given string is a single word that is (or stems to) an action verb.
     * Used to avoid adding leftover verbs (e.g. "mix") as ingredients.
     */
    private function isActionVerb(string $name): bool
    {
        $w = strtolower(trim($name));
        if ($w === '' || str_contains($w, ' ')) {
            return false;
        }

        return isset($this->actionSet[$w]) || $this->stemVerb($w) !== null;
    }

    /**
     * True if the name looks like a tool phrase (e.g. "separate small bowl", "large bowl")
     * so we don't add it as an ingredient when tool extraction missed it.
     */
    private function isToolPhrase(string $name): bool
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return false;
        }
        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === []) {
            return false;
        }
        $toolSet = array_flip(array_map('strtolower', self::TOOLS));
        $adjSet = array_flip(array_map('strtolower', self::TOOL_ADJECTIVES));
        $last = $words[array_key_last($words)];
        if (! isset($toolSet[$last])) {
            return false;
        }
        foreach (array_slice($words, 0, -1) as $w) {
            if (! isset($adjSet[$w])) {
                return false;
            }
        }
        return true;
    }

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
        $raw = trim(str_replace(',', '.', $raw));

        // "1 1/2" or "2 3/4" → whole + fraction
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $raw, $m)) {
            $whole = (int) $m[1];
            $num = (float) $m[2];
            $den = max((int) $m[3], 1);

            return round($whole + $num / $den, 3);
        }

        if (str_contains($raw, '/')) {
            $parts = explode('/', $raw);

            return count($parts) === 2
                ? round(floatval($parts[0]) / max(floatval($parts[1]), 1), 3)
                : floatval($raw);
        }

        return floatval($raw);
    }

    /**
     * Normalize parsed unit to measure slug from API measures table.
     * All quantities use key "unit" with one of these slugs.
     */
    private function normalizeUnit(string $unit): string
    {
        $u = strtolower(trim($unit));
        $u = preg_replace('/s$/', '', $u);

        return match ($u) {
            'tablespoon' => 'tbsp',
            'teaspoon' => 'tsp',
            'pound' => 'lb',
            'ounce' => 'oz',
            'litre', 'liter' => 'l',
            'piece' => 'pcs',
            'gram', 'g' => 'g',
            'kilogram' => 'kg',
            'milliliter', 'millilitre' => 'ml',
            'cup' => 'cup',
            'handful' => 'hf',
            'pinch' => 'pinch',
            'dash' => 'dash',
            'bunch' => 'bch',
            'splash' => 'spl',
            'minute', 'min' => 'min',
            'hour', 'hr' => 'h',
            'inch' => 'in',
            'second', 'sec' => 's',
            'meter', 'metre' => 'm',
            'quarter' => 'quarter',
            'half' => 'half',
            'branch' => 'branch',
            'lb', 'lbs' => 'lb',
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