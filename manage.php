<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHAEXT);
$id = optional_param('id', 0, PARAM_INT);
require_login();

$context = context_system::instance();
admin_externalpage_setup('managefilters');
$url = new moodle_url("/local/courselist");
if($action == 'del'){
    global $DB;
    if($id){
        $DB->delete_records('local_courselist',['id' => $id]);
    }
    redirect($url."/manage.php");
}
$title = get_string('manage_course_list', 'local_courselist');
$pagetitle = $title;
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$output = $PAGE->get_renderer('local_courselist');
echo $output->header();
echo $output->heading($pagetitle);
echo $output->render_manage_page($PAGE);
echo $output->footer();