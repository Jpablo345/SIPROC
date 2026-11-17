<?php

namespace App\Support;

/**
 * Colores de los cuartiles de revista (Q1 a Q5), en tonos del azul de la Facultad de Ingeniería
 * del manual UFPSO: más intenso cuanto mejor es el cuartil.
 */
class Quartile
{
    private const INSIGNIA = [
        'Q1' => 'bg-fac-ing text-white',
        'Q2' => 'bg-fac-ing-500 text-white',
        'Q3' => 'bg-fac-ing-300 text-fac-ing',
        'Q4' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'Q5' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    ];

    private const BARRA = [
        'Q1' => 'bg-fac-ing',
        'Q2' => 'bg-fac-ing-500',
        'Q3' => 'bg-fac-ing-300',
        'Q4' => 'bg-gray-400',
        'Q5' => 'bg-gray-300 dark:bg-gray-600',
    ];

    public static function badgeClass(?string $category): ?string
    {
        return self::INSIGNIA[strtoupper(trim((string) $category))] ?? null;
    }

    public static function barClass(?string $category): string
    {
        return self::BARRA[strtoupper(trim((string) $category))] ?? 'bg-gray-200 dark:bg-gray-700';
    }
}
