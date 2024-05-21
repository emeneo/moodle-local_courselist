<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT);
$fid = optional_param('fid', 0, PARAM_INT);
$key = optional_param('key', '', PARAM_TEXT);
require_login();
$context = context_course::instance(1);
$PAGE->set_context($context);
$outputData = new stdClass;
if ($id) {
    $data = $DB->get_record('local_courselist', ['id' => $id]);
    (isset($data->name))?$outputData->name = $data->name:$outputData->name = "";
    (isset($data->summary))?$outputData->summary = $data->summary:$outputData->summary = "";


    (isset($data->categories))?$usedCategories = explode(",", $data->categories):$usedCategories = [];
    $i = 0;
    $fields = [];
    $fieldslen = 0;
    foreach ($usedCategories as $cateId) {
        $course_fields = get_custom_field($cateId);
        foreach ($course_fields as $field) {
            $outputData->fields[$i] = [
                'id' => $field->id,
                'name' => $field->name,
                'shortname' => $field->shortname,
                'description' => $field->description,
            ];
            $fields[$field->id] = $outputData->fields[$i];
            if (strlen($field->name) > $fieldslen) $fieldslen = strlen($field->name);
            $i++;
        }
        if (!$fid && count($outputData->fields) > 0) $fid = $outputData->fields[0]['id'];
    }
    $outputData->fieldboxwidth = $fieldslen * 9;

    $courses = [];
    if (!empty($key)) {
        $categoryid = [];
        $rows = get_custom_field_categories();
        foreach ($rows as $row) {
            $categoryid[] = $row->id;
        }
        $categoryids = implode(",", $categoryid);
        $courses = get_course_by_key($key, $categoryids);
        $cid = 0;
        foreach ($courses as $course) {
            $cid = $course->id;
            break;
        }
        if ($cid) {
            foreach ($courses[$cid]->fieldid as $fid) {
                if (isset($fields[$fid])) {
                    $outputData->description = $fields[$fid]['description'];
                    $outputData->fid = $fid;
                    break;
                }
            }
        }
    } elseif ($fid) {
        $courses = get_course_by_custom_field($fid);
        if(isset($fields[$fid])){
            $outputData->description = $fields[$fid]['description'];
            $outputData->fid = $fid;
        }
    }
    if (!empty($courses)) {
        $formatedCourse = [];
        $i = 0;
        foreach ($courses as $course) {
            $course->startdate = date('Y-m-d H:i:s', $course->startdate);
            $course->enrolseats = get_free_seats($course->id);
            $formatedCourse[$i] = $course;
            $i++;
        }
        $outputData->courses = $formatedCourse;
    }
    $outputData->id = $id;
    $outputData->courseurl = new moodle_url("/course/view.php");
    $outputData->enrolurl = new moodle_url("/enrol/index.php");
    $outputData->searchurl = new moodle_url("/local/courselist/view.php?id=" . $id);
}

$url = new moodle_url("/local/courselist");
$PAGE->set_url($url);
$PAGE->add_body_class('limitedwidth');
//$PAGE->set_title(get_string('pluginname', 'local_courselist'));
$output = $PAGE->get_renderer('local_courselist');
echo $output->header();
//echo $output->heading($pagetitle);
echo $output->render_view_page($PAGE, $outputData);
echo $output->footer();
