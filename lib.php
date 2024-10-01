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
 * local_cousrse lib.
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

/**
 * get custom field categories
 */
function getcustomfieldcategories() {
    global $DB;
    $raws = $DB->get_records('customfield_category', [], '', 'id,name');
    return $raws;
}

/**
 * get custom field
 */
function getcustomfield($cateid) {
    global $DB;
    $raws = $DB->get_records('customfield_field', ['categoryid' => $cateid], 'sortorder ASC', 'id,name,shortname,description');
    return $raws;
}

/**
 * get course by custom field
 */
function getcoursebycustomfield($fieldid) {
    global $DB;
    $subquery = $DB->get_records_sql('SELECT instanceid FROM {customfield_data} WHERE intvalue = 1 AND fieldid =?', [$fieldid]);
    $instanceids = array_keys($subquery);
    return $DB->get_records_list('course', 'id', $instanceids, 'fullname ASC');
}

/**
 * get free seats
 */
function getfreeseats($courseid) {
    global $DB;
    $seatssummary = '';
    $enrol = $DB->get_record_sql('SELECT id, enrol, customint3 FROM {enrol} WHERE courseid =? AND status = 0 ORDER BY sortorder ASC LIMIT 1', [$courseid]);
    if ($enrol) {
        $enrolment = $DB->count_records('user_enrolments', ['enrolid' => $enrol->id]);
        if ($enrol->customint3 > 0) {
            $seatssummary = ($enrol->customint3 - $enrolment) . " " . get_string('out_of', 'local_courselist') . " " . $enrol->customint3;
            if ($enrolment == $enrol->customint3 && $enrol->enrol == "waitlist") {
                $seatssummary .= ", " . get_string('waitlist_possible', 'local_courselist');
            }
        } else {
            $seatssummary = get_string('unlimited', 'local_courselist');
        }
    }
    return $seatssummary;
}

/**
 * get course by key
 */
function getcoursebykey($key, $categoryid) {
    global $DB;
    $fieldIds = $DB->get_records_sql('SELECT id FROM {customfield_field} WHERE categoryid IN (?)', [$categoryid]);
    $fieldIdArray = array_keys($fieldIds);
    $raws = $DB->get_records_sql('SELECT instanceid FROM {customfield_data} WHERE fieldid IN ('.implode(',', $fieldIdArray).') GROUP BY instanceid');
    $courseids = [];
    foreach ($raws as $raw) {
        $courseids[] = $raw->instanceid;
    }
    $courseid = implode(",", $courseids);
    $raws = $DB->get_records_select('course', "id IN ($courseid) AND fullname LIKE?", ['%'.$key.'%']);
    foreach ($raws as $k => $raw) {
        $field = $DB->get_records_sql('SELECT fieldid FROM {customfield_data} WHERE intvalue = 1 AND instanceid =?', [$raw->id]);
        $fieldids = [];
        foreach ($field as $f) {
            $fieldids[] = $f->fieldid;
        }
        $raws[$k]->fieldid = $fieldids;
    }
    return $raws;
}
