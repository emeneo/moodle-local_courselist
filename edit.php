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
 * edit
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');
require_once('edit_form.php');

global $DB, $USER;

$id = optional_param('id', 0, PARAM_INT);
require_login();
admin_externalpage_setup('managefilters');
$context = context_system::instance();
require_capability ('local/courselist:manage', $context);
$pageparams = [];
if ($id) {
    $pageparams['id'] = $id;
}
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url('/local_courselist/edit.php', $pageparams);
$args = [];
$catcontext = context_user::instance($USER->id);
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true);
$args['editoroptions'] = $editoroptions;
if ($id) {
    $title = get_string('edit', 'local_courselist');
    $args['courselist'] = $DB->get_record('local_courselist', ['id' => $id]);
} else {
    $title =  get_string('add', 'local_courselist');
    $args['courselist'] = [];
}
$returnurl = new moodle_url($CFG->wwwroot . '/local/courselist/manage.php');
$editform = new local_courselist_edit_form(null, $args);
if ($editform->is_cancelled()) {
    // The form has been cancelled, take them back to what ever the return to is.
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $insertdata = new stdClass;
    $insertdata->name = $data->name;
    $insertdata->startdate = $data->startdate;
    $insertdata->enddate = $data->enddate;
    $insertdata->summary = $data->summary_editor['text'];
    $insertdata->defaultappearance = $data->defaultappearance;
    $insertdata->layout = $data->layout;
    $categories = [];
    foreach ($data->categories as $cate) {
        if ($cate > 0) {
            $categories[] = $cate;
        }
    }
    $insertdata->categories = implode(",", $categories);
    if (@!$data->id) {
        $DB->insert_record('local_courselist', $insertdata);
    } else {
        $insertdata->id = $data->id;
        $DB->update_record('local_courselist', $insertdata);
    }
    redirect($returnurl);
}
$PAGE->set_title($title);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();
if ($title == 'Add') {
    echo $OUTPUT->heading(get_string('add_new_courselist', 'local_courselist'));
} else {
    echo $OUTPUT->heading(get_string('edit_courselist', 'local_courselist'));
}
$editform->display();
echo $OUTPUT->footer();
