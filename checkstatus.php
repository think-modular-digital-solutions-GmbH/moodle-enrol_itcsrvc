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

use enrol_itcsrvc\itcsrvc;

global $DB;

// Request payment.
$endpoint = '/check-transaction-status';
$method = 'POST';
$data = [
    'transactionReference' => '9953d294-fef2-464c-b5ec-6436d697c12e',
];

// Send cURL request.
$response = itcsrvc::request($endpoint, $data);

echo "<pre>";
var_dump($response);
die();

// Get HTTP status code.
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode < 200 || $httpcode >= 300) {
    throw new Exception('HTTP error: ' . $httpcode . ' Response: ' . $response);
}

// Get checkout URL.
$responsedata = json_decode($response, true);
if ($responsedata === null) {
    throw new Exception('Invalid response: ' . json_encode($response));
}
if (!isset($responsedata['data']['checkoutUrl'])) {
    throw new Exception('Invalid response: ' . json_encode($response));
}
$checkouturl = $responsedata['data']['checkoutUrl'];

// Make params log-safe.
unset($params['apiKey']);
unset($params['merchantProductId']);
unset($params['transflowId']);

// Log response.
$record = new stdClass();
$record->timestamp = time();
$record->type = 0; // Replace with global later.
$record->enrolid = 1;
$record->courseid = 1;
$record->userid = 1;
$record->httpstatus = $httpcode;
$record->payload = json_encode($responsedata);
$DB->insert_record('enrol_itcsrvc_logs', $record);

// Open checkout URL in browser.
header('Location: ' . $checkouturl);
exit;