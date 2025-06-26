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
 * TODO describe file uninstall
 *
 * @package    block_rajabsgames
 * @copyright  2024 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Uninstall function for the block_rajabsgames plugin.
 *
 * @return bool Always returns true.
 */
function xmldb_block_rajabsgames_uninstall() {
    $config = get_config('mod_interactivevideo', 'enablecontenttypes');
    $config = explode(',', $config);
    $config = array_diff($config, ['block_rajabsgames']);
    // Save the new configuration.
    set_config('enablecontenttypes', implode(',', $config), 'mod_interactivevideo');
    return true;
}
