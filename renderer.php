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
 * page renderer
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

 /**
  * local courselist renderer
  */
class local_courselist_renderer extends plugin_renderer_base {

    public function render_manage_page($page) {
        global $DB;
        $data = new stdClass;
        $data->addurl = $page->url . "/edit.php";
        $data->delurl = $page->url . "/manage.php?action=del";
        $data->viewurl = $page->url . "/view.php";
        $data->btnAdd = get_string('add_new_list', 'local_courselist');
        $data->btnEdit = get_string('edit', 'local_courselist');
        $data->btnDel = get_string('del', 'local_courselist');
        $data->btnCopy = get_string('copy_url', 'local_courselist');
        $data->tipsCopy = get_string('copy_url_tips', 'local_courselist');
        $rows = $DB->get_records('local_courselist', null, "", "id,name");
        $i = 0;
        foreach ($rows as $row) {
            @$data->courses[$i]->name = $row->name;
            @$data->courses[$i]->id = $row->id;
            $i++;
        }
        return parent::render_from_template('local_courselist/manage_page', $data);
    }

    public function render_view_page($page, $outputdata) {
        global $USER;
        $usercontext = context_user::instance($USER->id);
        $outputdata->gourl = $page->url . "/view.php?id=" . $outputdata->id;
        if (isset($outputdata->description)) {
            $outputdata->description = file_rewrite_pluginfile_urls($outputdata->description, 'pluginfile.php', 1, 'core_customfield', 'description', $outputdata->fid);
        }
        if (!empty($outputdata->courses)) {
            for ($i = 0; $i < count($outputdata->courses); $i++) {
                $context = context_course::instance($outputdata->courses[$i]->id);
                $outputdata->courses[$i]->summary = file_rewrite_pluginfile_urls($outputdata->courses[$i]->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
            }
        }
        $outputdata->btnEnroll = get_string('enroll', 'local_courselist');
        return parent::render_from_template('local_courselist/view_page', $outputdata);
    }
}
