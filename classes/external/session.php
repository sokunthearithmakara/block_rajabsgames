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

namespace block_rajabsgames\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_single_structure;
use external_api;
use external_value;

require_once($CFG->libdir . '/externallib.php');
/**
 * Class session
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session extends external_api {
    /**
     * Get enrolled users parameters
     *
     * @return external_function_parameters
     */
    public static function session_parameters() {
        return new external_function_parameters([
            'blockinstanceid' => new external_value(PARAM_INT, 'The block instance id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get the list of all course's users
     *
     * @param array $search
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function session($blockinstanceid) {
        global $SESSION;

        require_login();

        unset($SESSION->{'block_rajabsgames_main_' . $blockinstanceid});

        return ['status' => 'success'];
    }

    /**
     * Get enrolled users return fields
     *
     * @return external_single_structure
     */
    public static function session_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'The status of the guest check'),
        ]);
    }
}
