<?php

/**
 * Helpers.php
 *
 * This file is part of InitPHP Console.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Console/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Console;

/**
 * Small, stateless value helpers shared across the console components.
 */
final class Helpers
{
    /**
     * Casts a raw string token coming from the command line into the most
     * appropriate PHP scalar type.
     *
     * The following conversions are applied (case-insensitive):
     *
     * - `""`            → `""` (empty string)
     * - `"null"`        → `null`
     * - `"true"`/`"yes"`→ `true`
     * - `"false"`/`"no"`→ `false`
     * - an integer-like token (optionally signed) → `int`
     * - a decimal token using `.` or `,` as separator → `float`
     * - anything else is returned unchanged as a `string`
     *
     * @param string $value Raw token to cast.
     * @return string|int|float|bool|null
     */
    public static function strValueCast(string $value)
    {
        switch (\strtolower($value)) {
            case '':
                return '';
            case 'null':
                return null;
            case 'yes':
            case 'true':
                return true;
            case 'no':
            case 'false':
                return false;
            default:
                if (\preg_match('/^[-+]?[0-9]+$/', $value) === 1) {
                    return \intval($value);
                }
                if (\preg_match('/^[-+]?[0-9]+[.,][0-9]+$/', $value) === 1) {
                    return \floatval(\strtr($value, [',' => '.']));
                }
                return $value;
        }
    }
}
