<?php

declare(strict_types=1);

/**
 * Fixed kitchen expo stations (six lanes). Persisted per product via products.kitchen_station.
 */

function zoryn_kitchen_station_slugs_ordered(): array
{
    return ['soup', 'noodles', 'pasta', 'fry', 'salad', 'soda_wares'];
}

function zoryn_kitchen_station_labels(): array
{
    return [
        'soup' => 'Soup',
        'noodles' => 'Noodles',
        'pasta' => 'Pasta',
        'fry' => 'Fry',
        'salad' => 'Salad',
        'soda_wares' => 'Soda/wares',
    ];
}

function zoryn_normalize_kitchen_station($raw): string
{
    $s = strtolower(trim((string) $raw));
    $allowed = zoryn_kitchen_station_slugs_ordered();
    if (!in_array($s, $allowed, true)) {
        return 'fry';
    }
    return $s;
}

function zoryn_kitchen_station_label(string $slug): string
{
    $labels = zoryn_kitchen_station_labels();
    return $labels[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
}

/**
 * Bootstrap default lane when admins add products without overriding (by menu category_id).
 *
 * @internal Adjust this map anytime your menu taxonomy changes.
 */
function zoryn_default_kitchen_station_for_category(int $categoryId): string
{
    static $map = [
        1 => 'pasta',
        2 => 'fry',
        3 => 'fry',
        4 => 'fry',
        5 => 'soda_wares',
        6 => 'soda_wares',
        7 => 'fry',
        8 => 'soda_wares',
        9 => 'soda_wares',
        10 => 'fry',
        11 => 'noodles',
        12 => 'salad',
        13 => 'fry',
        14 => 'fry',
        15 => 'soup',
    ];
    return $map[$categoryId] ?? 'fry';
}
