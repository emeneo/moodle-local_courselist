<?php
class local_courselist_renderer extends plugin_renderer_base
{

    public function render_manage_page($page)
    {
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
        $rows = $DB->get_records('local_courselist', NULL, "", "id,name");
        $i = 0;
        foreach ($rows as $row) {
            @$data->courses[$i]->name = $row->name;
            @$data->courses[$i]->id = $row->id;
            $i++;
        }
        return parent::render_from_template('local_courselist/manage_page', $data);
    }

    public function render_view_page($page, $outputData)
    {
        global $USER;
        $usercontext = context_user::instance($USER->id);
        $outputData->gourl = $page->url . "/view.php?id=" . $outputData->id;
        if(isset($outputData->description)){
            $outputData->description = file_rewrite_pluginfile_urls($outputData->description, 'pluginfile.php', 1, 'core_customfield', 'description', $outputData->fid);
        }
        if (!empty($outputData->courses)) {
            for ($i = 0; $i < count($outputData->courses); $i++) {
                $context = context_course::instance($outputData->courses[$i]->id);
                $outputData->courses[$i]->summary = file_rewrite_pluginfile_urls($outputData->courses[$i]->summary, 'pluginfile.php', $context->id, 'course', 'summary', NULL);
            }
        }
        $outputData->btnEnroll = get_string('enroll', 'local_courselist');
        return parent::render_from_template('local_courselist/view_page', $outputData);
    }
}
