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
 * Payment logs for ITCSrvc enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_itcsrvc\log;

require_once(__DIR__ . '/../../config.php');
require_login();

// Check capabilities.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Set page.
$url = new moodle_url('/enrol/itcsrvc/payment_log.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url($url);

// Create table.
$download = optional_param('download', '', PARAM_ALPHA);
$table = new flexible_table('enrol_itcsrvc');
$table->is_downloading($download, 'payment_log', 'payment_log');

// Columns and headers.
$table->define_columns([
    'id',
    'courseid',
    'userid',
    'transaction_reference',
    'payment_start',
    'payment_complete',
    'status',
]);
$table->define_headers([
    'id',
    get_string('course'),
    get_string('user'),
    get_string('transaction_reference', 'enrol_itcsrvc'),
    get_string('payment_start', 'enrol_itcsrvc'),
    get_string('payment_complete', 'enrol_itcsrvc'),
    get_string('status'),
]);

$table->define_baseurl($PAGE->url);
$table->sortable(true, 'timecreated', SORT_DESC);
$table->pageable(true);
$table->collapsible(false);
$table->is_downloading($download, 'payment_log', 'payment_log');
$table->setup();

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data.
    // Print the page header.
    $PAGE->set_title(get_string('pluginname', 'enrol_itcsrvc'));
    $PAGE->set_heading(get_string('logs', 'enrol_itcsrvc'));
    echo $OUTPUT->header();
}

// Add data.
$logs = $DB->get_records('enrol_itcsrvc_logs');
foreach ($logs as $log) {
    $table->add_data([
        $log->id,
        log::course($log->enrolid),
        log::user($log->userid),
        $log->transaction_reference,
        log::timestamp($log->payment_start),
        log::timestamp($log->payment_complete),
        log::status($log->status),
    ]);
}
$table->finish_output();

if (!$table->is_downloading()) {
    // Back to menu settings link.
    $url = new moodle_url('/admin/settings.php?section=enrolsettingsitcsrvc');
    echo html_writer::link($url, get_string('back'), ['class' => 'btn btn-secondary m-1']);

    echo $OUTPUT->footer();
}
