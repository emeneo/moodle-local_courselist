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
 * view
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT);
$fid = optional_param('fid', 0, PARAM_INT);
$key = optional_param('key', '', PARAM_TEXT);
require_login();
//$context = context_course::instance(1);
$context = context_system::instance();
$PAGE->set_context($context);
$outputdata = new stdClass;

if ($id) {
    $data = $DB->get_record('local_courselist', ['id' => $id]);
    (isset($data->name)) ? $outputdata->name = $data->name : $outputdata->name = "";
    (isset($data->summary)) ? $outputdata->summary = $data->summary : $outputdata->summary = "";


    (isset($data->categories)) ? $usedcategories = explode(",", $data->categories) : $usedcategories = [];
    $i = 0;
    $fields = [];
    $fieldslen = 0;
    foreach ($usedcategories as $cateid) {
        $coursefields = local_courselist_getcustomfield($cateid);
        $outputdata->fields = [];
        foreach ($coursefields as $field) {
            $temp = [
                'id' => $field->id,
                'name' => $field->name,
                'shortname' => $field->shortname,
                'description' => $field->description,
            ];
            $outputdata->fields[] = $temp;

            $fields[$field->id] = $temp;
            if (strlen($field->name) > $fieldslen) {
                $fieldslen = strlen($field->name);
            }
            $i++;
        }
    }
    if (!$fid && count($outputdata->fields) > 0) {
        $fid = $outputdata->fields[0]['id'];
    }
    $outputdata->fieldboxwidth = $fieldslen * 9;
    $courses = [];
    if (!empty($key)) {
        $categoryid = [];
        $rows = local_courselist_getcustomfieldcategories();
        foreach ($rows as $row) {
            $categoryid[] = $row->id;
        }
        $categoryids = implode(",", $categoryid);
        $courses = local_courselist_getcoursebykey($key, $categoryids);
        $cid = 0;
        foreach ($courses as $course) {
            $cid = $course->id;
            break;
        }
        if ($cid) {
            foreach ($courses[$cid]->fieldid as $fid) {
                if (isset($fields[$fid])) {
                    $outputdata->description = $fields[$fid]['description'];
                    $outputdata->fid = $fid;
                    break;
                }
            }
        }
    } else if ($fid) {
        $courses = local_courselist_getcoursebycustomfield($fid);
        if (isset($fields[$fid])) {
            $outputdata->description = $fields[$fid]['description'];
            $outputdata->fid = $fid;
        }
    }
    if (!empty($courses)) {
        $formatedcourse = [];
        $i = 0;
        foreach ($courses as $course) {
            if($course->startdate > $data->startdate && $course->startdate < $data->enddate){
                $course->startdate = date('Y-m-d H:i:s', $course->startdate);
                $course->startdatelite = userdate(strtotime($course->startdate), '%d %B %Y');
                $course->startdatelabel = get_string('startdate_lable', 'local_courselist') . ": ";
                $course->enrolseatslabel = get_string('free_seats', 'local_courselist') . ": ";
                $course->enrolseats = local_courselist_getfreeseats($course->id);
                $formatedcourse[$i] = $course;
                $i++;
            }
        }
        $outputdata->courses = $formatedcourse;
    }
    $outputdata->id = $id;
    $outputdata->courseurl = new moodle_url("/course/view.php");
    $outputdata->enrolurl = new moodle_url("/enrol/index.php");
    $outputdata->searchurl = new moodle_url("/local/courselist/view.php?id=" . $id);
}
$url = new moodle_url("/local/courselist");
$PAGE->set_url($url);
$PAGE->add_body_class('limitedwidth');
$output = $PAGE->get_renderer('local_courselist');
echo $output->header();
echo $output->render_view_page($PAGE, $outputdata);
echo $output->footer();
