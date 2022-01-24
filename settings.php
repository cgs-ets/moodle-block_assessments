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
 * Defines the global settings of the block
 *
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'block_assessments_settings', 
        '', 
        get_string('pluginname_desc', 'block_assessments')
    ));

    $settings->add(new admin_setting_heading(
        'block_assessments_exdbheader', 
        get_string('settingsheaderdb', 'block_assessments'), 
        ''
    ));

    $options = array('', "mysqli", "oci", "pdo", "pgsql", "sqlite3", "sqlsrv");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect(
        'block_assessments/dbtype', 
        get_string('dbtype', 'block_assessments'), 
        get_string('dbtype_desc', 'block_assessments'), 
        '', 
        $options
    ));

    $settings->add(new admin_setting_configtext('block_assessments/dbhost', get_string('dbhost', 'block_assessments'), get_string('dbhost_desc', 'block_assessments'), 'localhost'));

    $settings->add(new admin_setting_configtext('block_assessments/dbuser', get_string('dbuser', 'block_assessments'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('block_assessments/dbpass', get_string('dbpass', 'block_assessments'), '', ''));

    $settings->add(new admin_setting_configtext('block_assessments/dbname', get_string('dbname', 'block_assessments'), '', ''));

    $settings->add(new admin_setting_configtext('block_assessments/dbassessmentproc', get_string('dbassessmentproc', 'block_assessments'), get_string('dbassessmentproc_desc', 'block_assessments'), ''));

    // The user's constit codes are how this plugin determines which timetable to fetch (student vs staff).
    $settings->add(new admin_setting_configtext('block_assessments/studentroles', get_string('studentroles', 'block_assessments'), get_string('studentroles_desc', 'block_assessments'), ''));
    $settings->add(new admin_setting_configtext('block_assessments/staffroles', get_string('staffroles', 'block_assessments'), get_string('staffroles_desc', 'block_assessments'), ''));

    $settings->add(new admin_setting_configtext('block_assessments/title', get_string('title', 'block_assessments'), '', ''));

}
