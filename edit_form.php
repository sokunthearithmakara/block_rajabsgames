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
 * Form for editing block_rajabsgames instances
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_rajabsgames_edit_form extends block_edit_form {

    /**
     * Form fields specific to this type of block
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $OUTPUT, $USER, $DB;

        $draftid = $this->block->config->draftid ?? file_get_submitted_draft_itemid('config_badges');

        $mform->addElement('hidden', 'config_draftid', $draftid);
        $mform->setType('config_draftid', PARAM_INT);

        $mform->addElement('hidden', 'config_badges', '');
        $mform->setType('config_badges', PARAM_RAW);

        $mform->addElement('hidden', 'config_levels', '');
        $mform->setType('config_levels', PARAM_RAW);

        $block = $this->block;

        $badges = $this->block->config->badges ?? '[]';
        $badges = file_prepare_draft_area(
            $draftid,
            $this->block->context->id,
            'block_rajabsgames',
            'content',
            0,
            ['subdirs' => true],
            $badges
        );

        $badges = json_decode($badges, true);

        // For each badge, check if it is in used (find in interactivevideo_completion).
        $badges = array_map(function ($badge) use ($DB) {
            $badge = (object)$badge;
            $badge->used = false;
            $sql = 'SELECT * FROM {interactivevideo_items} WHERE courseid = :courseid AND type = :ctype AND content LIKE :badgeid';
            $paramsarray = [
                'badgeid' => '%' . $badge->id . '%',
                'ctype' => 'rajabsgames',
                'courseid' => $this->block->page->course->id,
            ];
            $completion = $DB->record_exists_sql($sql, $paramsarray);
            if ($completion) {
                $badge->used = true;
            }
            return $badge;
        }, $badges);

        $usedbadges = array_filter($badges, function ($badge) {
            return $badge->used;
        });
        $isused = count($usedbadges) > 0;

        $levels = $this->block->config->levels ?? '[]';
        $levels = json_decode($levels, true);

        $mform->addElement('html', $OUTPUT->render_from_template(
            'block_rajabsgames/form',
            [
                'badges' => $badges,
                'usedbadges' => $isused,
                'levels' => $levels,
                'block' => json_encode($block),
                'courseid' => $block->page->course->id,
                'contextid' => $block->context->id,
                'userid' => $USER->id,
                'blockid' => $this->optional_param('blockid', 0, PARAM_INT),
            ]
        ));

        // Block settings.
        $mform->addElement('header', 'rajabsgamessettings', get_string('rajabsgamessettings', 'block_rajabsgames'));

        $elements = [];
        $elements[] = $mform->createElement(
            'advcheckbox',
            'config_showleaderboard',
            '',
            get_string('showleaderboard', 'block_rajabsgames'),
            ['group' => 1],
            [0, 1]
        );
        $elements[] = $mform->createElement(
            'advcheckbox',
            'config_showlevels',
            '',
            get_string('showlevels', 'block_rajabsgames'),
            ['group' => 1],
            [0, 1]
        );
        $elements[] = $mform->createElement(
            'advcheckbox',
            'config_showbadges',
            '',
            get_string('showbadges', 'block_rajabsgames'),
            ['group' => 1],
            [0, 1]
        );

        $mform->addGroup($elements, '', '', null, false);
        $mform->setDefault('config_showleaderboard', 1);
        $mform->setDefault('config_showlevels', 1);
        $mform->setDefault('config_showbadges', 1);

        // Group mode.
        $mform->addElement('select', 'config_groupmode', get_string('mode', 'block_rajabsgames'), [
            0 => get_string('nogroupmode', 'block_rajabsgames'),
            1 => get_string('separategroup', 'block_rajabsgames'),
            2 => get_string('visiblegroup', 'block_rajabsgames'),
        ]);
        $mform->setDefault('config_mode', 0);

        // Select grouping.
        $groupings = groups_get_all_groupings($this->page->course->id);
        $select = [
            0 => get_string('nogrouping', 'block_rajabsgames'),
        ];
        foreach ($groupings as $grouping) {
            $select[$grouping->id] = $grouping->name;
        }
        $mform->addElement('select', 'config_grouping', get_string('grouping', 'block_rajabsgames'), $select);
        $mform->setDefault('config_grouping', 0);

        $mform->addElement('advcheckbox', 'config_showgroupleaderboard', get_string('showgroupleaderboard', 'block_rajabsgames'));
        $mform->setDefault('config_showgroupleaderboard', 1);
    }

    /**
     * Validates the form data.
     *
     * @param array $data The form data.
     * @param array $files The files submitted with the form.
     * @return array An array of validation errors, or an empty array if none.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // If group mode is set, then grouping must be set.
        if ($data['config_groupmode'] != 0 && $data['config_grouping'] == 0) {
            $errors['config_grouping'] = get_string('groupingrequired', 'block_rajabsgames');
        }
        return $errors;
    }

    /**
     * Sets the data for the form, including handling the editor and title fields.
     *
     * @param stdClass $defaults Default values for the form fields.
     */
    public function set_data($defaults) {

        if (!empty($this->block->config) && !empty($this->block->config->badges)) {
            $text = $this->block->config->badges;
            $draftid = file_get_submitted_draft_itemid('config_badges');
            $defaults->draftid = $draftid;
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $defaults->config_badges = file_prepare_draft_area(
                $draftid,
                $this->block->context->id,
                'block_rajabsgames',
                'content',
                0,
                ['subdirs' => true],
                $currenttext
            );
            $badges = $defaults->config_badges;
        } else {
            $badges = '';
        }

        unset($this->block->config->badges);
        unset($this->block->config->draftid);
        parent::set_data($defaults);
        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }
        $this->block->config->badges = $badges;
        $this->block->config->draftid = $defaults->draftid ?? 0;
        $this->block->config->levels = isset($defaults->config_levels) ? $defaults->config_levels : '';
    }

    /**
     * Display the configuration form when block is being added to the page
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        return false;
    }
}
