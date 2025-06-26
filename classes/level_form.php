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
 * Class level_form
 *
 * @package    block_rajabsgames
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class level_form extends \core_form\dynamic_form {

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

        $fromform = $this->get_data();
        if ($fromform->levelid == 0) {
            $fromform->levelid = time();
            $fromform->editing = false;
        } else {
            $fromform->levelid = (int)$fromform->levelid;
            $fromform->editing = true;
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
        $data = new \stdClass();
        $data->blockid = $this->optional_param('blockid', 0, PARAM_INT);
        $data->contextid = $this->optional_param('contextid', null, PARAM_INT);
        $data->levelid = $this->optional_param('levelid', 0, PARAM_INT);
        $data->level_name = $this->optional_param('level_name', '', PARAM_TEXT);
        $data->order = $this->optional_param('order', 0, PARAM_INT);
        $data->xplimit = $this->optional_param('xplimit', 0, PARAM_INT);

        $this->set_data($data);
    }

    /**
     * Returns the page URL for dynamic submission.
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        // TODO: Return the correct URL.
        return new \moodle_url('/blocks/rajabsgames/badge_form.php');
    }

    /**
     * Defines the form elements.
     */
    protected function definition(): void {
        // TODO: Add form elements here.
        $mform = &$this->_form;
        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_INT);
        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        // Level ID.
        $mform->addElement('hidden', 'levelid');
        $mform->setType('levelid', PARAM_INT);

        // Level order.
        $mform->addElement('hidden', 'order');
        $mform->setType('order', PARAM_INT);

        // Level name.
        $mform->addElement('text', 'level_name', get_string('levelname', 'block_rajabsgames'), [
            'size' => '100',
        ]);
        $mform->setType('level_name', PARAM_TEXT);
        $mform->addRule('level_name', get_string('required'), 'required', null, 'client');

        // XP limit'.
        $mform->addElement('text', 'xplimit', get_string('xplimit', 'block_rajabsgames'), [
            'size' => '100',
            'min' => '1',
        ]);
        $mform->setType('xplimit', PARAM_INT);
        $mform->addRule('xplimit', get_string('required'), 'required', null, 'client');
        $mform->addRule('xplimit', get_string('numeric'), 'numeric', null, 'client');

        // Set vertical display.
        $this->set_display_vertical();
    }

    /*
     * Validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        // xplimit must be greater than 0.
        if ($data['xplimit'] <= 0) {
            $errors['xplimit'] = get_string('xplimitmustbegreaterthan0', 'block_rajabsgames');
        }
        return $errors;
    }
}
