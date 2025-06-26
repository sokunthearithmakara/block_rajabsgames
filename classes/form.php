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
 * Class form
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form extends \mod_interactivevideo\form\base_form {
    /**
     * Sets data for dynamic submission
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $data = $this->set_data_default();
        $this->set_data($data);
    }

    /**
     * Pre-processes form data before use.
     *
     * @param object $data The form data object.
     * @return object The processed form data object.
     */
    public function pre_processing_data($data) {
        if ($data->xp > 0) {
            $data->hascompletion = 1;
        } else {
            $data->hascompletion = 0;
        }

        return $data;
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB, $OUTPUT, $CFG;
        $mform = &$this->_form;

        $courseid = $this->optional_param('courseid', 0, PARAM_INT);

        // Get the rajabsgames block instance.
        $cache = \cache::make('block_rajabsgames', 'rajabsgames_configs');
        $blockdata = $cache->get($courseid);
        if (!$blockdata) {
            $contextid = \context_course::instance($courseid)->id;
            $block = $DB->get_record(
                'block_instances',
                ['parentcontextid' => $contextid, 'blockname' => 'rajabsgames'],
                'id, configdata',
                IGNORE_MISSING
            );
            if ($block) {
                $blockcontextid = \context_block::instance($block->id)->id;
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

        if ($blockdata == null) {
            $mform->addElement('html', get_string('rajabsgamesnotconfigured', 'block_rajabsgames'));
            $mform->addElement('html', '<button class="btn btn-secondary" id="cancel-submit">'
                . get_string('cancel') . '</button>');
            return;
        }

        $blockdata = (object)$blockdata;
        $badges = $blockdata->badges;
        $badges = json_decode($badges, true);
        if (empty($badges)) {
            $mform->addElement(
                'html',
                '<p class="alert alert-warning">' . get_string('badgesnotfound', 'block_rajabsgames') . $badges . '</p>'
            );
            $mform->addElement('html', "<a class=\"btn btn-primary iv-mr-1\" href=\"$CFG->wwwroot"
                . "/course/view.php?id=$courseid&bui_editid=$blockdata->id\">"
                . get_string('setupbadge', 'block_rajabsgames') . '</a><button class="btn btn-secondary" id="cancel-submit">'
                . get_string('cancel') . '</button>');
            return;
        }

        $badges = array_map(function ($badge) {
            $badge = (object)$badge;
            $badge->isblock = true;
            return $badge;
        }, $badges);

        $badgehtml = $OUTPUT->render_from_template('block_rajabsgames/badge', [
            'badges' => $badges,
        ]);
        $mform->addElement(
            'html',
            '<h5 class="d-flex align-items-center justify-content-between"><span>'
                . get_string('availablebadges', 'block_rajabsgames')
                . '</span><a class="btn btn-light" href="'
                . $CFG->wwwroot . '/course/view.php?id=' . $courseid . '&bui_editid=' . $blockdata->id
                . '" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a></h5>
            <div class="rg_badges_list w-100 mb-3 position-relative">'
                . $badgehtml . '</div>'
        );

        $this->standard_elements(false);

        // Store badge data for select menu.
        $mform->addElement('hidden', 'badges', json_encode($badges));
        $mform->setType('badges', PARAM_RAW);

        $mform->addElement(
            'hidden',
            'title',
            'Rajab\'s Games'
        );
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('hidden', 'content', $this->optional_param('content', '', PARAM_RAW));
        $mform->setType('content', PARAM_RAW);

        $mform->addElement('hidden', 'xp', $this->optional_param('xp', '', PARAM_INT));
        $mform->setType('xp', PARAM_INT);

        $mform->addElement('html', '<label class="d-flex align-items-center col-form-label iv-pl-0 w-100 justify-content-between">
        <span>
            <i class="bi bi-bullseye iv-mr-2"></i>'
            . get_string('positions', 'block_rajabsgames') .
            '</span>
        <span class="btn btn-sm btn-primary iv-float-right" id="add-destination">
            <i class="bi bi-plus-lg"></i>
        </span>
        </label>
        <p class="text-muted">' . get_string('youcannotaddthesamebadgemorethanonce', 'block_rajabsgames') . '</p>
        <div id="destination-list" class="w-100 mb-3"></div>');

        $mform->addElement('static', 'destination', '', '');

        $group = [];
        $group[] = $mform->createElement(
            'advcheckbox',
            'intg2',
            '',
            get_string('showbadgesontop', 'block_rajabsgames'),
            ['group' => 1],
            [0, 1]
        );
        $mform->setDefault('intg2', 1);

        $group[] = $mform->createElement(
            'advcheckbox',
            'intg3',
            '',
            get_string('pausevideoonbadge', 'block_rajabsgames'),
            ['group' => 1],
            [0, 1]
        );
        $mform->setDefault('intg3', 0);
        $mform->addGroup($group, '', '', null, false);

        $this->close_form();

        // Action buttons.
        $actionbuttons = '<div class="d-flex justify-content-end mb-3 mt-n3" id="form-action-btns">';
        if ($this->optional_param('id', 0, PARAM_INT) > 0) {
            $actionbuttons .= '<button class="btn btn-primary iv-mr-2" id="submitform-submit">'
                . get_string('savechanges') . '</button><button class="btn btn-secondary" id="cancel-submit">'
                . get_string('cancel') . '</button>';
        } else {
            $actionbuttons .= '<button class="btn btn-primary iv-mr-2" id="submitform-submit">' . get_string('submit')
                . '</button><button class="btn btn-secondary" id="cancel-submit">'
                . get_string('cancel') . '</button>';
        }
        $actionbuttons .= '</div>';
        $mform->addElement('html', $actionbuttons);
    }

    /**
     * Form validation
     *
     * @param array $data form data
     * @param array $files form files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['content'])) {
            $errors['destination'] = get_string('youmusthaveatleastonedestination', 'local_ivdecision');
        }

        return $errors;
    }
}
