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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_interactivevideo\local\external_compat;

external_compat::load();
/**
 * Class session
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session extends external_api {
    /**
     * Clear session parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'blockinstanceid' => new external_value(PARAM_INT, 'The block instance id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Clear session
     *
     * @param int $blockinstanceid The block instance id.
     * @return array The result of the guest check.
     */
    public static function execute($blockinstanceid) {
        global $SESSION;

        $params = self::validate_parameters(self::execute_parameters(), [
            'blockinstanceid' => $blockinstanceid,
        ]);

        require_login();

        unset($SESSION->{'block_rajabsgames_main_' . $params['blockinstanceid']});

        return ['status' => 'success'];
    }

    /**
     * Clear session return fields
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'The status of the guest check'),
        ]);
    }
}
