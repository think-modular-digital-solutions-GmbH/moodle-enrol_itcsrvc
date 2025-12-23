<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains class for helper functions for the ITC payment gateway enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper functions for ITC payment gateway enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_itcsrvc;

class locallib {
    /**
     * Get all official ISO 4217 currency codes
     */
    public static function get_currency_codes() {
        $currencies = \ResourceBundle::getLocales('');
        $currencyCodes = \ResourceBundle::create('en', 'ICUDATA-curr')->get('Currencies');

        $codes = [];
        foreach ($currencyCodes as $code => $data) {
            if (strlen($code) === 3) {
                $codes[] = $code;
            }
        }

        sort($codes);
        return $codes;
    }
}