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
 * This block displays a list of assessments pulled from Synergetic.
 *
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
// Include required files and classes.
require_once('../../config.php');
use block_assessments\utils;

$instanceid = required_param('instanceid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$username   = required_param('username', PARAM_RAW);

// Determine course and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($courseid);

// Get specific block config and context.
$blockinstance = $DB->get_record('block_instances', array('id' => $instanceid), '*', MUST_EXIST);
$blockconfig = unserialize(base64_decode($blockinstance->configdata));
$blockcontext = context_block::instance($instanceid);

// Set up page parameters.
$PAGE->set_course($course);
$pageurl = new moodle_url('/blocks/assessments/print.php', array(
    'instanceid' => $instanceid,
    'courseid' => $courseid,
));
$PAGE->set_url($pageurl);
$PAGE->set_context($coursecontext);
$title = get_string('title', 'block_assessments');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);

// Check user is logged in and capable of viewing.
require_login($course, false);

$data = utils::get_block_data($instanceid, $username);
if (empty($data)) {
  exit;
}

// Not using standard moodle footer and header output.
// Add styles.
echo '<link rel="stylesheet" type="text/css" href="' . new moodle_url($CFG->wwwroot . '/block/assessments/styles.css', array('nocache' => rand())) . '">';
// Some reset css.
echo '<style>body{margin:0;padding:0;font-family:sans-serif;} table,th,td{border: 1px solid black;border-collapse:collapse;}</style>';
// Display asssessment schedule.
echo $OUTPUT->render_from_template('block_assessments/print', $data);
exit;