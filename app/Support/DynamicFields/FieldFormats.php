<?php

namespace App\Support\DynamicFields;

/**
 * Formatos que un docente puede exigir en un campo de texto. Es una lista cerrada:
 * el usuario elige una clave y nunca escribe expresiones regulares propias.
 */
final class FieldFormats
{
    /**
     * clave => [etiqueta, regla de Laravel, descripción para el mensaje de error]
     */
    private const FORMATS = [
        'solo_numeros' => ['Solo números', 'regex:/^[0-9]+$/', 'solo puede contener números'],
        'solo_letras' => ['Solo letras', 'regex:/^[\pL\s]+$/u', 'solo puede contener letras y espacios'],
        'issn' => ['ISSN', 'regex:/^\d{4}-\d{3}[\dXx]$/', 'debe ser un ISSN válido (ej: 1234-567X)'],
        'isbn' => ['ISBN', 'regex:/^(?:\d[- ]?){9}[\dXx]$|^(?:\d[- ]?){12}\d$/', 'debe ser un ISBN-10 o ISBN-13 válido'],
        'doi' => ['DOI', 'regex:/^10\.\d{4,9}\/\S+$/', 'debe ser un DOI válido (ej: 10.1234/abc.2025)'],
        'correo' => ['Correo electrónico', 'email', 'debe ser un correo electrónico válido'],
        'orcid' => ['ORCID', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', 'debe ser un ORCID válido (ej: 0000-0002-1825-0097)'],
    ];

    /**
     * @return array<string, string> clave => etiqueta, para el selector de la configuración.
     */
    public static function options(): array
    {
        return array_map(fn (array $format) => $format[0], self::FORMATS);
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && isset(self::FORMATS[$key]);
    }

    public static function rule(string $key): string
    {
        return self::FORMATS[$key][1];
    }

    public static function description(string $key): string
    {
        return self::FORMATS[$key][2];
    }

    /**
     * Nombre de la regla de Laravel, para armar la clave del mensaje (regex o email).
     */
    public static function ruleName(string $key): string
    {
        return explode(':', self::FORMATS[$key][1], 2)[0];
    }
}
