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
 * Provides {@link block_assessments\external\timetable_exporter} class.
 *
 * @package   block_assessments
 * @copyright 2022 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_assessments\external;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use core\external\exporter;

/**
 * Exporter of the day's periods.
 */
class assessments_exporter extends exporter {

    /**
     * Return the list of standard exported properties. The following properties simply pass in and out of the exporter without manipulation.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
          'instanceid' => [
              'type' => PARAM_INT,
          ],
          'courseid' => [
              'type' => PARAM_INT,
          ],
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * Data needed to generate "other" properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'scheduledata' => 'stdClass[]',
            'classmapping' => 'stdClass[]',
            'username' => 'string?',
        ];
    }

    /**
     * Return the list of additional properties.
     *
     * Calculated values or properties generated on the fly based on standard properties and related data.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
          'schedule' => [
              'type' => PARAM_RAW,
              'multiple' => true,
          ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $DB, $USER;

        // The data structure.
        $schedule = array(
            'semesters' => array(
                array(
                    'semester' => 1,
                    'terms' => array(
                        1 => array(
                            'term' => 1,
                            'assessments' => array()
                        ),
                        2 => array(
                            'term' => 2,
                            'assessments' => array()
                        )
                    )
                ),
                array(
                    'semester' => 2,
                    'terms' => array(
                        3 => array(
                            'term' => 3,
                            'assessments' => array()
                        ),
                        4 => array(
                            'term' => 4,
                            'assessments' => array()
                        ),
                    )
                ),
            )
        );

        // Build a useful and clean array of periods.
        foreach ($this->related['scheduledata'] as $ix => $assessment) {
            // Build the array by term.
            //if (!isset($schedule[$assessment->term])) {
            //    $schedule[$assessment->term] = array(
            //        'term' => $assessment->term,
            //        'assessments' => array(),
            //    );
            //}
            // Check for mapped course. 
            $url = '';
            $altdescription = '';
            $classmapping = array_filter($this->related['classmapping'], function($map) use ($assessment) {
              if (strpos($map->syncode, $assessment->classcode) !== false || $map->assessmentcode == $assessment->assessmentcode) {
                  return true;
              }
              return false;
            });
            foreach ($classmapping as $classmap) {
                if ($classmap->moodlecode) {
                    $course = $DB->get_record('course', array('idnumber' => $classmap->moodlecode));
                    if ($course) {
                        // Use alt description for the course instead of the Synergetic timetable desc.
                        $altdescription = $course->shortname;
                        $url = new \moodle_url('/course/view.php', array('id' => $course->id));
                        $url = $url->out(false);
                        break;
                    }
                }
            }
            $semester = 0;
            if ($assessment->term > 2) {
                $semester = 1;
            }

            // Due in / Days until.
            $duein = '';
            $difference = strtotime(date("Y-m-d", strtotime($assessment->testdate))) - strtotime(date("Y-m-d", time()));
            if ($difference < 0) {
                $duein = 'past';
            } else if ($difference == 0) {
                $duein = 'today';
            } else {
                $duein = floor($difference/60/60/24);
                $duein = $duein . ' days';
            }

            $schedule['semesters'][$semester]['terms'][$assessment->term]['assessments'][] = array(
                'term' => $assessment->term,
                'week' => $assessment->weeknumber,
                'classcode' => $assessment->classcode,
                'date' => date("d/m/Y", strtotime($assessment->testdate)),
                'title1' => $assessment->hdgabbrev1,
                'title2' => $assessment->heading,
                'eventorder' => $assessment->eventorder,
                'url' => $url,
                'altdescription' => $altdescription,
                'duein' => $duein,
            );
        }

        // Unset terms with no assessments.
        for( $s = 0; $s <= 1; $s++ ) {
          foreach ($schedule['semesters'][$s]['terms'] as $i => $term) {
            if (empty($term['assessments'])) {
              unset($schedule['semesters'][$s]['terms'][$i]);
            }
          }
        }

        $schedule['semesters'][0]['terms'] = array_values($schedule['semesters'][0]['terms']);
        $schedule['semesters'][1]['terms'] = array_values($schedule['semesters'][1]['terms']);
        //echo "<pre>"; var_export($schedule);exit;


        // Get the print preview url.
        $printurl = new \moodle_url('/blocks/assessments/print.php', array(
          'instanceid' => $this->data->instanceid,
          'courseid' => $this->data->courseid,
          'username' => $this->related['username'],
        ));
        $schedule['printurl'] = $printurl->out(false);

        return [
            'schedule' => $schedule,
        ];

    }


}