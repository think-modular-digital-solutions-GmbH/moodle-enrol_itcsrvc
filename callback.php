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
 * Callback endpoint for ITC payment gateway enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

defined('MOODLE_INTERNAL') || die();

global $DB;

// Read contents.
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log data.
$record = new stdClass();
$record->timestamp = time();
$record->type = enrol_itcsrvc_plugin::TYPE_INCOMING; // Replace with global later.
$record->enrolid = 1;
$record->courseid = 1;
$record->userid = 1;
$record->httpstatus = 200;
$record->payload = json_encode($data);
$DB->insert_record('enrol_itcsrvc_logs', $record);

echo "OK";