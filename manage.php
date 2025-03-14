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
 * manage
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHAEXT);
$id = optional_param('id', 0, PARAM_INT);
require_login();

if($action){
    require_sesskey();
}

$context = context_system::instance();
require_capability ('local/courselist:manage', $context);
admin_externalpage_setup('managefilters');
$url = new moodle_url("/local/courselist");
if ($action == 'del') {
    global $DB;
    if ($id) {
        $DB->delete_records('local_courselist', ['id' => $id]);
    }
    redirect($url . "/manage.php");
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
