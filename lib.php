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
 * Library for the ITC payment gateway enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_itcsrvc\itcsrvc;
use enrol_itcsrvc\form\enrol_form;
use enrol_itcsrvc\form\empty_form;

/**
 * ITC payment gateway enrolment plugin.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_itcsrvc_plugin extends enrol_plugin {
    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('moodle/course:enrolconfig', $context);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('moodle/course:enrolconfig', $context);
    }

    /**
     * Return true if we can add a new instance to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Use standard editing UI.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Add fields to edit instance form.
     * @param stdClass $instance The instance data loaded from the DB.
     * @param MoodleQuickForm $mform The form to add elements to.
     * @param context $context The context of the instance we are editing
     * @return void
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context): void {
        $config = get_config('enrol_itcsrvc');

        // Role.
        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        $mform->addElement(
            'select',
            'roleid',
            get_string('role'),
            $roles
        );
        $mform->setType('roleid', PARAM_INT);
        $mform->setDefault('roleid', '5');

        // Text.
        $defaulttext = $config->defaulttext ?? get_string('defaulttextdefault', 'enrol_itcsrvc');
        $mform->addElement(
            'textarea',
            'customtext1',
            get_string('text', 'enrol_itcsrvc'),
            'wrap="virtual" rows="5" cols="50"',
        );
        $mform->setType('customtext1', PARAM_RAW);
        $mform->setDefault('customtext1', $defaulttext);
        $mform->addHelpButton('customtext1', 'text', 'enrol_itcsrvc');

        // Cost.
        $default = $config->defaultcost ?? '0.00';
        $default = number_format($default, 2);
        $mform->addElement(
            'text',
            'cost',
            get_string('cost', 'enrol_itcsrvc')
        );
        $mform->setType('cost', PARAM_TEXT);
        $mform->setDefault('cost', $default);

        // Currency.
        $options = itcsrvc::get_currency_codes();
        $mform->addElement(
            'select',
            'currency',
            get_string('currency', 'enrol_itcsrvc'),
            $options
        );
        $mform->setDefault('currency', $config->defaultcurrency ?? 'USD');
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance data loaded from the DB.
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        return [];
    }

    /**
     * Should show enrol me link on course page?
     */
    public function show_enrolme_link(stdClass $instance) {
        return true;
    }

    /**
     * Add conditions for self enrolment availability.
     */
    public function is_self_enrol_available(stdClass $instance) {
        return true;
    }

    /**
     * Can a user self enrol via this plugin?
     *
     * @param stdClass $instance
     * @param bool $checkuserenrolment
     * @return bool
     */
    public function can_self_enrol(stdClass $instance, $checkuserenrolment = true) {
        global $DB, $OUTPUT, $USER;

        if ($checkuserenrolment) {
            if (isguestuser()) {
                // Can not enrol guest.
                return get_string('noguestaccess', 'enrol') . $OUTPUT->continue_button(get_login_url());
            }
            // Check if user is already enroled.
            if ($DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
                return get_string('alreadyenrolled', 'enrol_itcsrvc');
            }
        }

        // Check if self enrolment is available right now for users.
        $result = $this->is_self_enrol_available($instance);
        if ($result !== true) {
            return $result;
        }

        // Check if user has the capability to enrol in this context.
        if (!has_capability('enrol/itcsrvc:enrolself', context_course::instance($instance->courseid))) {
            return get_string('nopermission', 'enrol_itcsrvc');
        }

        return true;
    }

    /**
     * Enrol page hook to render and process custom enrolment form.
     *
     * @param stdClass $instance
     */
    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $OUTPUT, $USER;

        // Check if the user already payed for the course.
        itcsrvc::check_payment_status($instance, $USER->id);

        $enrolstatus = $this->can_self_enrol($instance);

        if (true === $enrolstatus) {
            // This user can self enrol using this instance.
            $url = new moodle_url('/enrol/index.php', ['id' => $instance->courseid]);
            $form = new enrol_form($url, $instance);
            if ($data = $form->get_data()) {
                // Process payment.
                itcsrvc::pay($instance);
            }
        } else {
            // This user can not self enrol using this instance. Using an empty form to keep
            // the UI consistent with other enrolment plugins that returns a form.
            $data = new stdClass();
            $data->header = $this->get_instance_name($instance);
            $data->info = $enrolstatus;

            // The can_self_enrol call returns a button to the login page if the user is a
            // guest, setting the login url to the form if that is the case.
            $url = isguestuser() ? get_login_url() : null;
            $form = new empty_form($url, $data);
        }

        // Render form.
        ob_start();
        $form->display();
        $output = ob_get_clean();
        return $OUTPUT->box(
            html_writer::div($output, 'px-3'),
            'generalbox'
        );
    }
}
