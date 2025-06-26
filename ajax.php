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
 * TODO describe file ajax
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once('lib.php');
require_sesskey();
require_login();

$action = required_param('action', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);

switch ($action) {
    case 'get_badges':
        $blockdata = (object)block_rajabsgames_get_configdata($courseid);
        echo json_encode($blockdata->badges);
        break;
    case 'get_completiondetails':
        $cmid = required_param('cmid', PARAM_INT);
        $userid = required_param('userid', PARAM_INT);
        $completiondetails = $DB->get_field('interactivevideo_completion', 'completiondetails', [
            'cmid' => $cmid,
            'userid' => $userid,
        ]);
        echo json_encode($completiondetails);
        break;
    default:
        break;
}
