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
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
function get_custom_field_categories()
{
    global $DB;
    $raws = $DB->get_records('customfield_category', [], '', 'id,name');
    return $raws;
}

function get_custom_field($cateId)
{
    global $DB;
    $raws = $DB->get_records('customfield_field', ['categoryid' => $cateId], '', 'id,name,shortname,description');
    return $raws;
}

function get_course_by_custom_field($fieldId)
{
    global $DB;
    $raws = $DB->get_records_sql("select * from {course} where id in (select instanceid from {customfield_data} where intvalue=1 and fieldid=" . $fieldId . ")");
    return $raws;
}

function get_free_seats($courseid){
    global $DB;
    $seats_summary = '';
    $enrol = $DB->get_record_sql("select id,enrol,customint3 from {enrol} where courseid=".$courseid." and status=0 order by sortorder asc limit 1");
    if($enrol){
        $enrolment = $DB->count_records_sql("select count(id) from {user_enrolments} where enrolid=".$enrol->id);
        if($enrol->customint3 > 0){
            $seats_summary = ($enrol->customint3 - $enrolment)." ".get_string('out_of', 'local_courselist')." ".$enrol->customint3;
            if($enrolment == $enrol->customint3 && $enrol->enrol == "waitlist"){
                $seats_summary.= ", ".get_string('waitlist_possible', 'local_courselist');
            }
        }else{
            $seats_summary = get_string('unlimited', 'local_courselist');
        }
    }
    if(!empty($seats_summary)){
        $seats_summary = get_string('free_seats', 'local_courselist').": ".$seats_summary;
    }
    return $seats_summary;
}

function get_course_by_key($key,$categoryid){
    global $DB;
    $raws = $DB->get_records_sql("select instanceid from {customfield_data} where fieldid in (select id from {customfield_field} where categoryid in (".$categoryid.")) group by instanceid");
    $courseids = [];
    foreach($raws as $raw){
        $courseids[] = $raw->instanceid;
    }
    $courseid = implode(",",$courseids);
    $raws = $DB->get_records_sql("select * from {course} where id in (".$courseid.") and fullname like '%".$key."%'");
    foreach($raws as $k=>$raw){
        $field = $DB->get_records_sql("select fieldid from {customfield_data} where intvalue=1 and instanceid=".$raw->id);
        $fieldids = [];
        foreach($field as $f){
            $fieldids[] = $f->fieldid;
        }
        $raws[$k]->fieldid = $fieldids;
    }
    return $raws;
}
