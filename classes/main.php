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

namespace block_rajabsgames;

/**
 * Class main
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Class main
 *
 * @package    block_rajabsgames
 * @copyright  2024 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main extends \ivplugin_richtext\main {
    /**
     * Get the property.
     */
    public function get_property() {
        return [
            'name' => 'rajabsgames',
            'title' => 'Rajab\'s Games',
            'icon' => 'bi bi-shield-fill-check',
            'amdmodule' => 'block_rajabsgames/main',
            'class' => 'block_rajabsgames\\main',
            'form' => 'block_rajabsgames\\form',
            'hascompletion' => true,
            'hastimestamp' => false,
            'allowmultiple' => false,
            'hasreport' => true,
            'description' => get_string('description', 'block_rajabsgames'),
            'author' => 'tsmakara',
            'authorlink' => 'mailto:sokunthearithmakara@gmail.com',
            'stringcomponent' => 'block_rajabsgames',
            'tutorial' => get_string('tutorialurl', 'block_rajabsgames'),
        ];
    }

    /**
     * Get the content.
     * @param array $arg The argument.
     * @return string The content.
     */
    public function get_content($arg) {
        return true;
    }
}
