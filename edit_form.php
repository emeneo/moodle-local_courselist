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
 * edit form
 *
 * @package    local_courselist
 * @copyright  (2024-) emeneo
 * @link       emeneo.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * The form for handling editing a course.
 */
class local_courselist_edit_form extends moodleform {
    /**
     * @var array
     */
    protected $courselist;

    /**
     * @var array
     */
    protected $context;

    /**
     * Form definition.
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform    = $this->_form;
        $PAGE->requires->js_call_amd('core_course/formatchooser', 'init');

        $courselist    = $this->_customdata['courselist']; // this contains the data of this form
        $editoroptions = $this->_customdata['editoroptions'];

        $customfieldcategories = local_courselist_getcustomfieldcategories();

        $mform->addElement('header', 'time_period', get_string('time_period', 'local_courselist'));
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_courselist'));
        $date = (new DateTime())->setTimestamp(usergetmidnight(time()));
        $date->modify('+1 day');
        $mform->setDefault('startdate', $date->getTimestamp());
        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'local_courselist'));
        if (!empty($courselist->id)) {
            $mform->setDefault('startdate', $courselist->startdate);
            $mform->setDefault('enddate', $courselist->enddate);
        }

        $mform->addElement('header', 'description', get_string('description', 'local_courselist'));
        $mform->addElement('text', 'name', get_string('name', 'local_courselist'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', '', 'required', null, 'client');
        if (!empty($courselist->id)) {
            $mform->setDefault('name', $courselist->name);
        }
        $mform->addElement('editor', 'summary_editor', get_string('description', 'local_courselist'), null, $editoroptions);
        $mform->setType('summary_editor', PARAM_RAW);
        if (!empty($courselist->id)) {
            $mform->setDefault('summary_editor', array('text' => $courselist->summary, 'format' => 1));
        }

        $mform->addElement('header', 'course_field_categories', get_string('course_field_categories', 'local_courselist'));
        if (!empty($courselist->id)) {
            $usedcategories = explode(",", $courselist->categories);
        } else {
            $usedcategories = [];
        }
        $catecounts = 0;
        foreach ($customfieldcategories as $cate) {
            $mform->addElement('advcheckbox', 'categories[' . $catecounts . ']', '', $cate->name, [], [0, $cate->id]);
            if (in_array($cate->id, $usedcategories)) {
                $mform->setDefault('categories[' . $catecounts . ']', $cate->id);
            }
            $catecounts++;
        }
        if (!empty($courselist->id)) {
            $mform->addElement('hidden', 'id', $courselist->id);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('header', 'layout_radio', get_string('layout', 'local_courselist'));
        $radio = array();
        $radio[] = $mform->createElement('radio', 'layout', null, get_string('layout_titles', 'local_courselist'), '2');
        $radio[] = $mform->createElement('radio', 'layout', null, get_string('layout_cards', 'local_courselist'), '1');
        $mform->addGroup($radio, 'separator', '', ' ', false);
        if (!empty($courselist->id)) {
            $mform->setDefault('layout', $courselist->layout);
        }else{
            $mform->setDefault('layout', '2');
        }


        $mform->addElement('header', 'defaultappearance_radio', get_string('defaultappearance', 'local_courselist'));
        $radio = array();
        $radio[] = $mform->createElement('radio', 'defaultappearance', null, get_string('dont_display_any_courses_until_user_selects_a_course_list_category', 'local_courselist'), '1');
        $radio[] = $mform->createElement('radio', 'defaultappearance', null, get_string('display_course_of_first_course_list_category', 'local_courselist'), '2');
        $mform->addGroup($radio, 'separator', '', ' ', false);
        if (!empty($courselist->id)) {
            $mform->setDefault('defaultappearance', $courselist->defaultappearance);
        }else{
            $mform->setDefault('defaultappearance', '2');
        }

        $mform->setExpanded('description');
        $mform->setExpanded('course_field_categories');
        $mform->setExpanded('defaultappearance_radio');
        $mform->setExpanded('layout_radio');
        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
