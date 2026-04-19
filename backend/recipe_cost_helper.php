<?php
/**
 * Recipe / menu COGS from product_ingredients × ingredients.default_unit_cost.
 * default_unit_cost is per ingredient stock unit (updated on purchase receive).
 */

if (!function_exists('convert_ingredient_quantity_to_stock_unit')) {
    /**
     * Convert quantity from recipe unit to the ingredient's stock unit (same family: weight, volume, or count).
     */
    function convert_ingredient_quantity_to_stock_unit($quantity, $fromUnit, $toUnit) {
        $from = strtolower(trim((string) $fromUnit));
        $to   = strtolower(trim((string) $toUnit));
        if ($from === $to) {
            return (float) $quantity;
        }

        $weightToG = ['kg' => 1000, 'g' => 1, 'mg' => 0.001, 'oz' => 28.3495, 'lb' => 453.592];
        $volToMl   = ['liters' => 1000, 'l' => 1000, 'ml' => 1, 'cup' => 236.588, 'tbsp' => 14.7868, 'tsp' => 4.92892, 'fl oz' => 29.5735];
        $countUnits = ['pcs', 'pieces', 'units'];

        if (isset($weightToG[$from]) && isset($weightToG[$to])) {
            return (float) $quantity * $weightToG[$from] / $weightToG[$to];
        }
        if (isset($volToMl[$from]) && isset($volToMl[$to])) {
            return (float) $quantity * $volToMl[$from] / $volToMl[$to];
        }
        if (in_array($from, $countUnits, true) && in_array($to, $countUnits, true)) {
            return (float) $quantity;
        }

        return (float) $quantity;
    }
}

/**
 * Cost of one recipe line: qty (in stock units) × default_unit_cost.
 */
function recipe_line_cost_amount($recipeQty, $recipeUnit, $stockUnit, $defaultUnitCost) {
    $qtyInStockUnit = convert_ingredient_quantity_to_stock_unit($recipeQty, $recipeUnit, $stockUnit);
    return round($qtyInStockUnit * (float) $defaultUnitCost, 2);
}

/**
 * @param array $rows Each row: quantity, recipe_unit (or unit), stock_unit, default_unit_cost, plus any other keys
 * @return array{recipe_cost: float, lines: array}
 */
function recipe_cost_enrich_lines(array $rows) {
    $total = 0.0;
    $lines = [];
    foreach ($rows as $row) {
        $recipeUnit = $row['recipe_unit'] ?? $row['unit'] ?? '';
        $stockUnit  = $row['stock_unit'] ?? $row['ingredient_unit'] ?? '';
        $lc = recipe_line_cost_amount(
            (float) ($row['quantity'] ?? 0),
            $recipeUnit,
            $stockUnit,
            (float) ($row['default_unit_cost'] ?? 0)
        );
        $total += $lc;
        $lines[] = array_merge($row, ['line_cost' => $lc]);
    }
    return [
        'recipe_cost' => round($total, 2),
        'lines'       => $lines,
    ];
}
