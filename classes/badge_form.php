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

use block_manager;

/**
 * Class badge_form
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge_form extends \core_form\dynamic_form {

    /**
     * Returns the context for dynamic submission.
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        $contextid = $this->optional_param('contextid', null, PARAM_INT);
        return \context::instance_by_id($contextid, MUST_EXIST);
    }

    /**
     * Checks access for dynamic submission
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('block/rajabsgames:addinstance', $this->get_context_for_dynamic_submission());
    }

    /**
     * Processes the dynamic submission.
     *
     * @param array $data
     * @return mixed
     */
    public function process_dynamic_submission(): \stdClass {
        global $USER;
        $usercontextid = \context_user::instance($USER->id)->id;

        $fromform = $this->get_data();
        if ($fromform->badgeid === 0) {
            // If badgeid is 0, it means we are creating a new badge.
            $fromform->badgeid = time();
        } else {
            // If badgeid is not 0, we are editing an existing badge.
            $fromform->badgeid = (int)$fromform->badgeid;
        }
        $fromform->usercontextid = $usercontextid;
        // Get the draft file.
        if (!empty($fromform->badge_image)) {
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $fromform->usercontextid,
                'user',
                'draft',
                $fromform->badge_image,
                'filesize DESC',
            );
            $fromform->files = $files;
            $file = reset($files);
            if ($file) {
                $downloadurl = \moodle_url::make_draftfile_url(
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
                // Replace pluginfile with draftfile.
                $fromform->url = $downloadurl;
            } else {
                $fromform->url = new \moodle_url('');
            }
        }
        return $fromform;
    }

    /**
     * Sets data for dynamic submission.
     *
     * @param array|stdClass $data
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $USER, $CFG;
        $usercontextid = \context_user::instance($USER->id)->id;
        $data = new \stdClass();
        $data->blockid = $this->optional_param('blockid', 0, PARAM_INT);
        $data->contextid = $this->optional_param('contextid', null, PARAM_INT);
        $data->usercontextid = $this->optional_param('usercontextid', null, PARAM_INT);
        $data->badge_name = $this->optional_param('badge_name', '', PARAM_TEXT);
        $data->badgeid = $this->optional_param('badgeid', 0, PARAM_INT);
        $data->url = $this->optional_param('url', null, PARAM_URL);
        $data->draftid = $this->optional_param('draftid', 0, PARAM_INT);
        require_once($CFG->libdir . '/filelib.php');
        $newdraftitemid = $data->draftid;
        if ($data->url) {
            $urls = extract_draft_file_urls_from_text($data->url, false, $usercontextid, 'user', 'draft', $data->draftid);
            $url = reset($urls);
            $filename = urldecode($url['filename']);
            $newdraftitemid = file_get_unused_draft_itemid();
            file_copy_file_to_file_area($url, $filename, $newdraftitemid);
        }
        $data->badge_image = $newdraftitemid;
        $this->set_data($data);
    }

    /**
     * Returns the page URL for dynamic submission.
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/blocks/rajabsgames/badge_form.php');
    }

    /**
     * Defines the form elements.
     */
    protected function definition(): void {
        // Add form elements here.
        $mform = &$this->_form;
        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);
        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'badgeid');
        $mform->setType('badgeid', PARAM_INT);
        $mform->addElement('text', 'badge_name', get_string('badgename', 'block_rajabsgames'), [
            'size' => '100',
        ]);
        $mform->setType('badge_name', PARAM_TEXT);
        $mform->addRule('badge_name', get_string('required'), 'required', null, 'client');

        // Upload badge image.
        $mform->addElement(
            'filemanager',
            'badge_image',
            get_string('badgeimage', 'block_rajabsgames'),
            null,
            [
                'maxbytes' => 1024 * 1024,
                'accepted_types' => ['image'],
                'subdirs' => 0,
                'maxfiles' => 1,
            ]
        );
        $mform->addRule('badge_image', get_string('required'), 'required', null, 'client');

        // Set vertical display.
        $this->set_display_vertical();
    }
}
