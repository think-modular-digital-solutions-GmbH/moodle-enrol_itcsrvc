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
 * ITC payment gateway enrolment plugin settings page.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_itcsrvc\itcsrvc;

global $OUTPUT;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('enrol_itcsrvc_settings', '', get_string('pluginname_desc', 'enrol_itcsrvc')));

    // Connection settings.
    $url = new moodle_url('/enrol/itcsrvc/testconnection.php');
    $button = html_writer::link(
        $url,
        get_string('testconnection', 'enrol_itcsrvc'),
        ['class' => 'btn btn-secondary m-1']
    );
    $settings->add(new admin_setting_heading(
        'enrol_itcsrvc/connectionsettings',
        get_string('connectionsettings', 'enrol_itcsrvc'),
        $button,
    ));

    // Base URL.
    $settings->add(new admin_setting_configtext(
        'enrol_itcsrvc/baseurl',
        get_string('baseurl', 'enrol_itcsrvc'),
        '',
        '',
        PARAM_URL,
        50
    ));

    // Merchant product ID.
    $settings->add(new admin_setting_configtext(
        'enrol_itcsrvc/productid',
        get_string('productid', 'enrol_itcsrvc'),
        '',
        '',
        PARAM_TEXT,
        50
    ));

    // Transflow ID.
    $settings->add(new admin_setting_configtext(
        'enrol_itcsrvc/transflowid',
        get_string('transflowid', 'enrol_itcsrvc'),
        '',
        '',
        PARAM_TEXT,
        50
    ));

    // API key.
    $settings->add(new admin_setting_configpasswordunmask(
        'enrol_itcsrvc/apikey',
        get_string('apikey', 'enrol_itcsrvc'),
        '',
        '',
    ));

    // Payment settings.
    $settings->add(new admin_setting_heading(
        'enrol_itcsrvc/paymentsettings',
        get_string('paymentsettings', 'enrol_itcsrvc'),
        '',
    ));

    // Default text.
    $settings->add(new admin_setting_configtextarea(
        'enrol_itcsrvc/defaulttext',
        get_string('defaulttext', 'enrol_itcsrvc'),
        get_string('text_help', 'enrol_itcsrvc'),
        get_string('defaulttextdefault', 'enrol_itcsrvc'),
        PARAM_RAW,
        50,
        5,
    ));

    // Default cost.
    $settings->add(new admin_setting_configtext(
        'enrol_itcsrvc/defaultcost',
        get_string('defaultcost', 'enrol_itcsrvc'),
        '',
        '0',
        PARAM_FLOAT,
        10,
    ));

    // Default currency.
    $options = itcsrvc::get_currency_codes();
    $settings->add(new admin_setting_configselect(
        'enrol_itcsrvc/defaultcurrency',
        get_string('defaultcurrency', 'enrol_itcsrvc'),
        '',
        'USD',
        $options,
    ));

    // Link to payment logs.
    $url = new moodle_url('/enrol/itcsrvc/payment_log.php');
    $button = html_writer::link(
        $url,
        get_string('logs:view', 'enrol_itcsrvc'),
        ['class' => 'btn btn-secondary m-1']
    );
    $settings->add(new admin_setting_heading(
        'enrol_itcsrvc/paymentlogs',
        get_string('logs', 'enrol_itcsrvc'),
        $button,
    ));
}
