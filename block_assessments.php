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
 * This block generates a daily timetable based on external user class data.
 *
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

use block_assessments\utils;
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/blocks/assessments/lib.php');

class block_assessments extends block_base {

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('title', 'block_assessments');
    }

    /**
    *  We have global config/settings data.
    * @return bool
    */
    public function has_config() {
         return true;
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Controls whether the block is configurable
     *
     * @return bool
     */
    public function instance_allow_config() {
        return false;
    }


    /**
     * Set where the block should be allowed to be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all' => true);
    }


    /**
     * Used to generate the content for the block.
     * @return object
     */
    public function get_content() {
        global $COURSE, $DB, $USER, $PAGE, $OUTPUT;

        if ($this->content !== null ) {
            return $this->content;
        }

        $this->content = new stdClass;

        $this->content->text = '';
        $this->content->footer = '';

        $config = get_config('block_assessments');

        // Check DB settings are available
        if( empty($config->dbtype) ||
            empty($config->dbhost) ||
            empty($config->dbuser) ||
            empty($config->dbpass) ||
            empty($config->dbname) ||
            empty($config->dbassessmentproc)  ) {
            $notification = new \core\output\notification(get_string('nodbsettings', 'block_assessments'),
                                                          \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            return $OUTPUT->render($notification);
        }

        // CampusRoles profile field is required by this plugin.
        profile_load_custom_fields($USER);
        if(!isset($USER->profile['CampusRoles'])) {
            $notification = new \core\output\notification(get_string('userprofilenotsetup', 'block_assessments'),
                                                          \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            return $OUTPUT->render($notification);
        }

        //try {
            $data = utils::get_block_data($this->instance->id);
            if ($data) {
                $this->content->text = $OUTPUT->render_from_template('block_assessments/content', $data);
            }
        //} catch (Exception $e) {
         //   $this->content->text = '<h5>' . get_string('assessmentsunavailable', 'block_assessments') . '</h5>';
        //}

        return $this->content;
    }


    /**
     * Gets Javascript required for the widget functionality.
     */
    public function get_required_javascript() {
        global $USER;
        $config = get_config('block_assessments');
        parent::get_required_javascript();
        $this->page->requires->js_call_amd('block_assessments/control', 'init', [
            'instanceid' => $this->instance->id,
            'date' => date('Y-m-d', time()),
            'preference' => get_user_preferences('block_assessments_collapsed'),
            'userid' => $USER->id,
            'title' => $config->title,
        ]);
    }


}
