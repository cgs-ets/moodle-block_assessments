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
 * Plugin utils.
 *
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_assessments;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir .'/filelib.php');
require_once($CFG->libdir .'/accesslib.php');

/**
 * Provides utility functions for this plugin.
 *
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

class utils {

    /**
     * Iterate through an array until one of the array keys is found in a string and return the corresponding value.
     *
     * @param string $haystack The array to match against a string
     * @param string $string The string that you want to search against
     * @return mixed Returns the value for the key found to be contained in the string, FALSE otherwise.
     */
    public static function array_get_by_key_in_string($haystack, $string) {
        foreach ($haystack as $key => $value) {
            if (stripos($string, $key) !== false) {
                return $value;
            }
        }
        return false;
    }

    public static function get_block_data($instanceid) {
        global $COURSE, $DB, $USER, $PAGE, $OUTPUT;
    
        $data = null;
    
        $context = \CONTEXT_COURSE::instance($COURSE->id);
        $config = get_config('block_assessments');
    
        // Initialise some defaults.
        $username = $USER->username;
        $role = '';
        profile_load_custom_fields($USER);
        $userroles = array_map('trim', explode(',', $USER->profile['CampusRoles']));
    
        // Load in some config.
        $studentroles = array_map('trim', explode(',', $config->studentroles));
        $staffroles = array_map('trim', explode(',', $config->staffroles));
    
        // Determine if user is viewing this block on a profile page.
        if ( $PAGE->url->get_path() == '/user/profile.php' ) {
            // Get the profile user.
            $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);
            $username = $profileuser->username;
            // Load the user's custom profile fields.
            profile_load_custom_fields($profileuser);
            $profileroles = explode(',', $profileuser->profile['CampusRoles']);
            // Get the user type.
            if ( !$role = static::get_user_type($profileroles, $studentroles, $staffroles) ) {
                return null;
            }
            // Check whether the current user can view the profile timetable.
            if ( !static::can_view_on_profile($profileuser, $userroles, $staffroles) ) {
                return null;
            }
        } else {
            // Get the timetable user.
            if ( !$role = static::get_user_type($userroles, $studentroles, $staffroles) ) {
                //return null;
            }
        }

        $scheduledata = null;
        $config = get_config('block_assessments');
        try {
            // Get our prefered database driver.
            // Last parameter (external = true) means we are not connecting to a Moodle database.
            $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);
            // Connect to external DB
            $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

            $scheduledata = $externalDB->get_records_sql($config->dbassessmentproc, array($username));

        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    
        if (empty($scheduledata)) {
            return;
        }

        // Get Moodle class mappings
        $classmapping = array();
        if (!empty($config->mappingtable)) {
            $classcodes = array_filter(array_column($scheduledata, 'classcode'));
            if ($classcodes) {
                //$classcodes = array_map(function($code) {
                //    return $code . #;
                //}, $classcodes);
                $sql = "SELECT  $config->mappingtableid,
                                $config->mappingtableextcode,
                                $config->mappingtablemoocode
                          FROM  $config->mappingtable
                         WHERE  0 = 1";
                foreach ($classcodes as $code) {
                    $sql .= " OR SynCode LIKE '$code%'";
                }
                $classmapping = $externalDB->get_records_sql($sql);

                //echo "<pre>"; 
                //var_export($sql);
                //var_export($classmapping); 
                //exit;
            }
        }

        $props = (object) [
            'instanceid' => $instanceid,
        ];
        $relateds = [
            'scheduledata' => $scheduledata,
            'classmapping' => $classmapping,
        ];
        $schedule = new \block_assessments\external\assessments_exporter($props, $relateds);
        $data = $schedule->export($OUTPUT);
        //echo "<pre>"; var_export($data); exit;
    
        return $data;
    }
    
    public static function get_user_type($userroles, $studentroles, $staffroles) {
        // Check whether the timetable should be displayed for this profile user.
        // E.g. Senior student's and staff.
        if (array_intersect($userroles, $studentroles)) {
            return 'student';
        } elseif (array_intersect($userroles, $staffroles)) {
            return 'staff';
        }
    
        return null;
    }
    

    public static function can_view_on_profile($profileuser, $userroles, $staffroles) {
        global $DB, $USER;
    
        // Staff are always allowed to view timetables in profiles.
        if (array_intersect($userroles, $staffroles)) {
            return true;
        }
    
        // Students are allowed to see timetables in their own profiles.
        if ($profileuser->username == $USER->username) {
            return true;
        }
    
        // Parents are allowed to view timetables in their mentee profiles.
        $mentorrole = $DB->get_record('role', array('shortname' => 'parent'));
        $sql = "SELECT ra.*, r.name, r.shortname
                FROM {role_assignments} ra
                INNER JOIN {role} r ON ra.roleid = r.id
                INNER JOIN {user} u ON ra.userid = u.id
                WHERE ra.userid = ?
                AND ra.roleid = ?
                AND ra.contextid IN (SELECT c.id
                    FROM {context} c
                    WHERE c.contextlevel = ?
                    AND c.instanceid = ?)";
        $params = array(
            $USER->id, //Where current user
            $mentorrole->id, // is a mentor
            CONTEXT_USER,
            $profileuser->id, // of the prfile user
        );
        $mentor = $DB->get_records_sql($sql, $params);
        if ( !empty($mentor) ) {
            return true;
        }
    
        return false;
    }
    
}



