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

global $DB;

// $baseurl = 'https://apisuat.itcsrvc.com/checkout';
$baseurl = 'https://apis.itcsrvc.com/checkout';

// Request payment.
$endpoint = '/request-payments';
$method = 'POST';
$params = [
    'fullName' => 'stefantest weber',
    'email' => 'stefan.weber@think-modular.com',
    "narration" => "This is a generic payment description",
    "paymentMethod" => "card",
    "network" => "VODAFONE",
    'msisdn' => '',
    "amount" => 200,
    "currency" => "GHS",
    "successRedirectUrl" => "https://moodle.develop-modular.com/moodle-4.5/enrol/itcsrvc/success.php",
    "failureRedirectUrl" => "https://moodle.develop-modular.com/moodle-4.5/enrol/itcsrvc/failure.php",
    "callbackUrl" => "https://moodle.develop-modular.com/moodle-4.5/enrol/itcsrvc/callback.php",
    "pageDescription" => "KaiPTC Checkout Page",
    "pageTitle" => "KaiPTC Checkout Page",
    'apiKey' => '74909767197.0767874b70203-5dd9-42ee-afa4-f466d42c13f9',
    'merchantProductId' => 'f0e09a9c-8724-4bcd-b554-34187a02b5af',
    'transflowId' => '4974af90-e6e5-495c-8b79-ab3eaa1894f9',
];

// Send cURL request.
$ch = curl_init();
$url = rtrim($baseurl, '/') . '/' . ltrim($endpoint, '/');
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($params),
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
if ($response === false) {
    throw new Exception('cURL error: ' . curl_error($ch));
}

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
$transaction = $responsedata['data']['transactionReference'];

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
$record->payload = $transaction;
$DB->insert_record('enrol_itcsrvc_logs', $record);

// Open checkout URL in browser.
header('Location: ' . $checkouturl);
exit;