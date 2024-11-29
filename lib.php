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
function local_courselist_getcustomfieldcategories()
{
    global $DB;
    $raws = $DB->get_records('customfield_category', [], '', 'id,name');
    return $raws;
}

/**
 * get custom field
 */
function local_courselist_getcustomfield($cateid)
{
    global $DB;
    $raws = $DB->get_records('customfield_field', ['categoryid' => $cateid], 'sortorder ASC', 'id,name,shortname,description');
    return $raws;
}

/**
 * get course by custom field
 */
function local_courselist_getcoursebycustomfield($fieldid)
{
    global $DB;
    $sql = "SELECT instanceid 
            FROM {customfield_data} 
            WHERE intvalue = 1 
                AND fieldid = :fieldid";
    $subquery = $DB->get_records_sql($sql, ['fieldid' => $fieldid]);
    $instanceids = array_keys($subquery);
    $rows = $DB->get_records_list('course', 'id', $instanceids, 'fullname ASC');
    foreach($rows as $k => $row){
        if($row->visible <= 0){
            unset($rows[$k]);
        }
    }
    return $rows;
}

/**
 * get free seats
 */
function local_courselist_getfreeseats($courseid)
{
    global $DB;
    $seatssummary = '';
    $sql = "SELECT id, enrol, customint3 
            FROM {enrol} 
            WHERE courseid = :courseid 
                AND status = 0 
                ORDER BY sortorder ASC LIMIT 1";
    $enrol = $DB->get_record_sql($sql, ["courseid" => $courseid]);
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
function local_courselist_getcoursebykey($key, $categoryid)
{
    global $DB;
    [$insql, $inparams] = $DB->get_in_or_equal($categoryid);
    /*
    $sql = "SELECT * FROM {customfield_field} 
            WHERE categoryid $insql";
    $fieldIds = $DB->get_records_sql($sql, $inparams);
    */
    $sql = "SELECT * FROM {customfield_field} WHERE categoryid IN (".$categoryid.")";
    $fieldIds = $DB->get_records_sql($sql);
    $fieldIdArray = array_keys($fieldIds);
    [$insql, $inparams] = $DB->get_in_or_equal($fieldIdArray);
    $sql = "SELECT instanceid 
            FROM {customfield_data} 
            WHERE intvalue=1 AND fieldid $insql 
            GROUP BY instanceid";
    $raws = $DB->get_records_sql($sql, $inparams);
    $courseids = [];
    foreach ($raws as $raw) {
        $courseids[] = $raw->instanceid;
    }
    /*
    $likeKey = $DB->sql_like('fullname', ':key');
    $raws = $DB->get_records_sql(
        "SELECT * FROM {course} WHERE {$likeKey}",
        [
            'key' => '%' . $key . '%',
        ]
    );
    */
    $raws = $DB->get_records_sql("SELECT * FROM {course} WHERE visible=1 AND fullname like '%" . $key . "%'");
    foreach ($raws as $k => $raw) {
        if (!in_array($raw->id, $courseids)) {
            unset($raws[$k]);
            continue;
        }

        $sql = "SELECT fieldid 
                FROM {customfield_data} 
                WHERE intvalue = 1 
                    AND instanceid = :instanceid";
        $field = $DB->get_records_sql($sql, ['instanceid' => $raw->id]);
        $fieldids = [];
        foreach ($field as $f) {
            $fieldids[] = $f->fieldid;
        }
        $raws[$k]->fieldid = $fieldids;
    }
    return $raws;
}
