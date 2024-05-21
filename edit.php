<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');
require_once('edit_form.php');

global $DB, $USER;

$id = optional_param('id', 0, PARAM_INT);
require_login();
admin_externalpage_setup('managefilters');
$context = context_system::instance();
$pageparams = [];
if ($id) {
    $pageparams['id'] = $id;
}
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url('/local_courselist/edit.php', $pageparams);
$args = [];
$catcontext = context_user::instance($USER->id);
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
$args['editoroptions'] = $editoroptions;
if ($id) {
    $title = "Edit";
    $args['courselist'] = $DB->get_record('local_courselist', ['id' => $id]);
} else {
    $title = "Add";
    $args['courselist'] = [];
}
$returnurl = new moodle_url($CFG->wwwroot . '/local/courselist/manage.php');
$editform = new local_courselist_edit_form(null, $args);
if ($editform->is_cancelled()) {
    // The form has been cancelled, take them back to what ever the return to is.
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $insertData = new stdClass;
    $insertData->name = $data->name;
    $insertData->startdate = $data->startdate;
    $insertData->enddate = $data->enddate;
    $insertData->summary = $data->summary_editor['text'];
    $categories = [];
    foreach ($data->categories as $cate) {
        if ($cate > 0) $categories[] = $cate;
    }
    $insertData->categories = implode(",", $categories);
    if (@!$data->id) {
        $DB->insert_record('local_courselist', $insertData);
    } else {
        $insertData->id = $data->id;
        $DB->update_record('local_courselist', $insertData);
    }
    redirect($returnurl);
}
$PAGE->set_title($title);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();
if($title == 'Add'){
    echo $OUTPUT->heading(get_string('add_new_courselist', 'local_courselist'));
}else{
    echo $OUTPUT->heading(get_string('edit_courselist', 'local_courselist'));
}
$editform->display();
echo $OUTPUT->footer();
