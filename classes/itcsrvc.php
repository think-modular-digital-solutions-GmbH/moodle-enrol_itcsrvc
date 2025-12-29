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
 * Contains class for the ITC payment gateway enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_itcsrvc;

use Exception;
use stdClass;
use moodle_url;

/**
 * ITC payment gateway enrolment plugin functions.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class itcsrvc {
    /**
     * Payment pending.
     *
     * @var int
     */
    const STATUS_PENDING = 0;

    /**
     * Payment completed.
     *
     * @var int
     */
    const STATUS_COMPLETED = 1;

    /**
     * Payment failed.
     *
     * @var int
     */
    const STATUS_FAILED = 2;

    /**
     * Process a payment for the given enrolment instance.
     * @param object $instance
     */
    public static function pay($instance) {

        global $DB, $USER;

        // URLs.
        $courseurl = new \moodle_url('/course/view.php', ['id' => $instance->courseid]);
        $courseurl = $courseurl->out(false);
        $failureurl = new \moodle_url('/enrol/itcsrvc/failure.php', ['instanceid' => $instance->id]);
        $failureurl = $failureurl->out(false);
        $callbackurl = new \moodle_url('/enrol/itcsrvc/callback.php', ['instanceid' => $instance->id]);
        $callbackurl = $callbackurl->out(false);

        // Prepare request.
        $endpoint = '/request-payments';
        $data = [
            'fullName' => fullname($USER),
            'email' => $USER->email,
            "narration" => get_string(
                'narration',
                'enrol_itcsrvc',
                $courseurl
            ),
            "msisdn" => '', // Required for some reason.
            "amount" => $instance->cost,
            "currency" => $instance->currency,
            "successRedirectUrl" => $courseurl,
            "failureRedirectUrl" => $failureurl,
            "callbackUrl" => $callbackurl,
            "pageDescription" => "Checkout Page",
            "pageTitle" => "Checkout Page",
        ];

        // Send request.
        $response = self::request($endpoint, $data);
        $responsedata = $response['data'];

        // Get checkout URL.
        if ($responsedata === null) {
            throw new Exception('Invalid response: ' . json_encode($response));
        }
        if (!isset($responsedata['data']['checkoutUrl'])) {
            throw new Exception('Invalid response: ' . json_encode($response));
        }
        $checkouturl = $responsedata['data']['checkoutUrl'];

        // Log response.
        $record = new stdClass();
        $record->enrolid = $instance->id;
        $record->userid = $USER->id;
        $record->productid = get_config('enrol_itcsrvc', 'productid');
        $record->transflowid = get_config('enrol_itcsrvc', 'transflowid');
        $record->transaction_reference = $responsedata['data']['transactionReference'];
        $record->payment_start = time();
        $record->status = self::STATUS_PENDING;
        $DB->insert_record('enrol_itcsrvc_logs', $record);

        // Open checkout URL in browser.
        redirect(new moodle_url($checkouturl));
    }

    /**
     * Check payment status for the given enrolment instance and user.
     * @param object $instance
     * @param int $userid
     */
    public static function check_payment_status($instance, $userid) {

        global $DB;

        // Check if there is a pending payment for this user and enrolment instance.
        $payments = $DB->get_records('enrol_itcsrvc_logs', [
            'enrolid' => $instance->id,
            'userid' => $userid,
        ]);
        if (!$payments) {
            return;
        }

        // Prepare request.
        $endpoint = '/check-transaction-status';

        // Check payments.
        foreach ($payments as $payment) {
            $data = [
                'transactionReference' => $payment->transaction_reference,
            ];

            // Send request.
            $response = self::request($endpoint, $data);

            // Evaluate response.
            if (isset($response['data']['data']['responseCode']) && $response['data']['data']['responseCode'] === '01') {
                // Update record.
                $payment->payment_complete = time();
                $payment->status = self::STATUS_COMPLETED;
                $DB->update_record('enrol_itcsrvc_logs', $payment);

                // Enrol user in course.
                $enrol = enrol_get_plugin($instance->enrol);
                if (!$enrol) {
                    throw new moodle_exception('invalidenrolplugin');
                }
                $enrol->enrol_user(
                    $instance,
                    $userid,
                    $instance->roleid,
                    time(),
                    0
                );

                // Show notification about successful payment.
                \core\notification::add(
                    get_string('payment:success', 'enrol_itcsrvc'),
                    \core\notification::SUCCESS
                );

                // Redirect to course.
                $courseurl = new \moodle_url('/course/view.php', ['id' => $instance->courseid]);
                redirect($courseurl);
            }
        }
    }

    /**
     * Send a request to the ITC service.
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public static function request($endpoint, $data) {
        $baseurl = get_config('enrol_itcsrvc', 'baseurl');
        $data['apiKey'] = get_config('enrol_itcsrvc', 'apikey');
        $data['merchantProductId'] = get_config('enrol_itcsrvc', 'productid');
        $data['transflowId'] = get_config('enrol_itcsrvc', 'transflowid');

        // Send cURL request.
        $ch = curl_init();
        $url = rtrim($baseurl, '/') . '/' . ltrim($endpoint, '/');
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        // Get HTTP status code.
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responsedata = json_decode($response, true);
        return [
            'data' => $responsedata,
            'httpcode' => $httpcode,
        ];
    }

    /**
     * Get all official ISO 4217 currency codes
     * @return array
     */
    public static function get_currency_codes() {
        $currencies = \ResourceBundle::getLocales('');
        $currencycodes = \ResourceBundle::create('en', 'ICUDATA-curr')->get('Currencies');

        $codes = [];
        foreach ($currencycodes as $code => $data) {
            if (strlen($code) === 3) {
                $codes[$code] = $code;
            }
        }

        return $codes;
    }
}
