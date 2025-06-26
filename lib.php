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
 * Callback implementations for Rajab's Games
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Enables the block_rajabsgames plugin as ivplugin.
 *
 * @return array Plugin information including class and name.
 */
function block_rajabsgames_ivplugin() {
    return [
        'class' => 'block_rajabsgames\\main',
        'name' => 'rajabsgames',
    ];
}

/**
 * Handles serving of files for the block_rajabsgames plugin.
 *
 * @param stdClass $course The course object.
 * @param mixed $birecordorcm Block instance record or course module.
 * @param context $context The context of the file.
 * @param string $filearea The file area.
 * @param array $args Arguments remaining after filearea.
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting file serving.
 */
function block_rajabsgames_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_BLOCK) {
        send_file_not_found();
    }

    // If block is in course context, then check if user has capability to access course.
    if ($context->get_course_context(false)) {
        require_course_login($course);
    } else if ($CFG->forcelogin) {
        require_login();
    }

    if ($filearea !== 'content') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $file = $fs->get_file($context->id, 'block_rajabsgames', 'content', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, true, $options);
}

/**
 * Retrieves and unserializes the configuration data for a given block instance.
 *
 * @param int $instanceid The ID of the block instance.
 * @return mixed|null The unserialized configuration object, or null if not found.
 */
function block_rajabsgames_get_unserialized_config($instanceid) {
    global $DB;

    $config = $DB->get_field('block_instances', 'configdata', ['id' => $instanceid]);
    if ($config) {
        $config = unserialize_object(base64_decode($config));
        return $config;
    }
    return null;
}

/**
 * Sets the configuration data for a given block instance.
 *
 * @param int $instanceid The ID of the block instance.
 * @param mixed $data The configuration data to be serialized and stored.
 * @return void
 */
function block_rajabsgames_set_config($instanceid, $data) {
    global $DB;

    $config = base64_encode(serialize($data));
    $DB->set_field('block_instances', 'configdata', $config, ['id' => $instanceid]);
}

/**
 * Retrieves the configuration data for the Rajab's Games block for a given course.
 *
 * @param int $courseid The ID of the course.
 * @return stdClass|null The configuration object, or null if not found.
 */
function block_rajabsgames_get_configdata($courseid) {
    global $DB, $CFG;
    $cache = cache::make('block_rajabsgames', 'rajabsgames_configs');
    $blockdata = $cache->get($courseid);
    if (!$blockdata) {
        $contextid = context_course::instance($courseid)->id;
        $block = $DB->get_record(
            'block_instances',
            ['parentcontextid' => $contextid, 'blockname' => 'rajabsgames'],
            'id, configdata',
            IGNORE_MISSING
        );
        return $contextid;
        if ($block) {
            require_once($CFG->libdir . '/filelib.php');
            $blockcontextid = context_block::instance($block->id)->id;
            $config = $block->configdata;
            $config = unserialize_object(base64_decode($config));
            $config->badges = file_rewrite_pluginfile_urls(
                $config->badges,
                'pluginfile.php',
                $blockcontextid,
                'block_rajabsgames',
                'content',
                null
            );
            $config->id = $block->id;
            $cache->set($courseid, $config);
            $blockdata = $config;
        } else {
            $blockdata = null;
        }
    }
    return $blockdata;
}
