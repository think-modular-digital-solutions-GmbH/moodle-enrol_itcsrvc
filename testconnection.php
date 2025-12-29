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
 * Moodle page for menually testing the CAMPUSonline sync.
 *
 * @package    enrol_itcsrvc
 * @copyright  2024, TU Graz
 * @author     think-modular (stefan.weber@think-modular.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

defined('MOODLE_INTERNAL') || die();

use enrol_itcsrvc\itcsrvc;

// Admins only.
require_capability('moodle/site:config', context_system::instance());

// Make fake request to check connection.
$endpoint = '/check-transaction-status';
$data = ['transactionReference' => 'TEST12345'];
$response = itcsrvc::request($endpoint, $data);
$responsedata = $response['data'];

// Create notifications.
if ($responsedata['responseCode'] == '200') {
    \core\notification::add(
        get_string('connection:success', 'enrol_itcsrvc'),
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    $error = $responsedata['responseMessage'] ?? 'Unknown error';
    \core\notification::add(
        get_string('connection:error', 'enrol_itcsrvc', $error),
        \core\output\notification::NOTIFY_ERROR
    );
}

// Redirect back to settings page.
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'enrolsettingsitcsrvc']);
redirect($settingsurl);