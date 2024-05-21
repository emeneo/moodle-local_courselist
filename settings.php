<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $ADMIN->add('localplugins', new admin_externalpage('local_courselist_admin_page', get_string('manage_course_list', 'local_courselist'), "$CFG->wwwroot/local/courselist/manage.php", 'moodle/site:config'));
}
