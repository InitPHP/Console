<?php
/**
 * Helpers.php
 *
 * This file is part of Console.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    2.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Console;

final class Helpers
{

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
                if ((bool)\preg_match('/^[-|+]*[0-9]+$/', $value)) {
                    return \intval($value);
                }
                if ((bool)\preg_match('/^[-|+]*[0-9]+[\.|,]{1}[0-9]+$/', $value)) {
                    return \floatval(\strtr($value, [','=>'.']));
                }
                return $value;
        }
    }

}
