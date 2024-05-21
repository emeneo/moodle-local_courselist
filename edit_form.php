<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * The form for handling editing a course.
 */
class local_courselist_edit_form extends moodleform
{
    protected $courselist;
    protected $context;

    /**
     * Form definition.
     */
    function definition()
    {
        global $CFG, $PAGE;

        $mform    = $this->_form;
        $PAGE->requires->js_call_amd('core_course/formatchooser', 'init');

        $courselist    = $this->_customdata['courselist']; // this contains the data of this form
        $editoroptions = $this->_customdata['editoroptions'];

        $custom_field_categories = get_custom_field_categories();

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
            $usedCategories = explode(",", $courselist->categories);
        } else {
            $usedCategories = [];
        }
        $cate_counts = 0;
        foreach ($custom_field_categories as $cate) {
            $mform->addElement('advcheckbox', 'categories[' . $cate_counts . ']', '', $cate->name, [], [0,$cate->id]);
            if (in_array($cate->id, $usedCategories)) {
                $mform->setDefault('categories[' . $cate_counts . ']', $cate->id);
            }
            $cate_counts++;
        }
        if (!empty($courselist->id)) {
            $mform->addElement('hidden', 'id', $courselist->id);
            $mform->setType('id', PARAM_INT);
        }
        $mform->setExpanded('description');
        $mform->setExpanded('course_field_categories');
        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'save', get_string('save'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
