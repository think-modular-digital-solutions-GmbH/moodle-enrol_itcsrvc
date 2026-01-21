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
 * Contains class for the ITC payment gateway enrolment plugin log table.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_itcsrvc;

/**
 * Helper functions for the ITC payment gateway enrolment plugin log table.
 *
 * @package    enrol_itcsrvc
 * @copyright  2025 think modular
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log {
    /**
     * Format course column.
     *
     * @param int $enrolid
     * @return string
     */
    public static function course($enrolid) {
        global $DB;
        $courseid = $DB->get_field('enrol', 'courseid', ['id' => $enrolid], IGNORE_MISSING);
        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname', IGNORE_MISSING);
        if ($course) {
            if ($course->fullname) {
                $link = new \moodle_url('/course/view.php', ['id' => $courseid]);
                return '<a href="' . $link . '">' . $course->fullname . '</a>';
            }
        }
        return get_string('deletedcourse', 'enrol_itcsrvc');
    }

    /**
     * Format username.
     *
     * @param int $userid
     * @return string formatted username
     */
    public static function user($userid) {
        $user = \core_user::get_user($userid);
        $username = fullname($user);
        $url = new \moodle_url('/user/profile.php', ['id' => $userid]);
        $link = \html_writer::link($url, $username);
        return $link;
    }

    /**
     * Format the status column.
     *
     * @param string status
     * @return string
     */
    public static function status($status) {
        switch ($status) {
            case 0:
                return \html_writer::tag(
                    'div',
                    get_string('status:pending', 'enrol_itcsrvc'),
                    ['class' => 'badge badge-info p-2']
                );
            case 1:
                return \html_writer::tag(
                    'div',
                    get_string('status:completed', 'enrol_itcsrvc'),
                    ['class' => 'badge badge-success p-2']
                );
            case 2:
                return \html_writer::tag(
                    'div',
                    get_string('status:failed', 'enrol_itcsrvc'),
                    ['class' => 'badge badge-danger p-2']
                );
            default:
                return '';
        }
    }

    /**
     * Format timestamps.
     *
     * @param int $value timestamp
     * @return string formatted timestamp
     */
    public static function timestamp($value) {
        if (empty($value)) {
            return '';
        }
        $date = date('Y-m-d', $value);
        $time = date('H:i:s', $value);
        return "$date<br>$time";
    }
}
