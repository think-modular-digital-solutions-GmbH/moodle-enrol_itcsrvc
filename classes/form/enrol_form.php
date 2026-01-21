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
 * Contains class for the ITC payment gateway enrolment form.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_itcsrvc\form;

use enrol_itcsrvc\itcsrvc;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Enrolment form.
 *
 * @package enrol_itcsrvc
 */
class enrol_form extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    public function definition() {

        // Instance ID.
        $instance = $this->_customdata;
        $this->_form->addElement(
            'hidden',
            'instanceid',
            $instance->id
        );
        $this->_form->setType('instanceid', PARAM_INT);

        $this->_form->addElement(
            'static',
            'test',
            "isntance: " . $instance->id,
            ""
        );

        // Payment text.
        $currencies = itcsrvc::get_currency_codes();
        $fee = $instance->cost . ' ' . $instance->currency;
        $text = str_replace('[fee]', $fee, $instance->customtext1);
        $this->_form->addElement(
            'html',
            $text,
        );

        // Spacer.
        $this->_form->addElement(
            'static',
            'spacer',
            '',
            \html_writer::empty_tag('br'),
        );

        // Submit button.
        $this->_form->addElement(
            'submit',
            'submitbutton',
            get_string('paynow', 'enrol_itcsrvc'),
        );
    }
}
