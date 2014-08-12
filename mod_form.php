<?php
/**
 * 
 * 
 * @package    mod_competition
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_competition_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COMPETITION_INTERVAL, $DB;

        $mform =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('competitionname', 'competition'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_intro_editor(true, get_string('chatintro', 'chat'));
        $mform->addElement('file', 'template', get_string('template', 'competition'));
        $mform->addRule('template', null, 'required', null, 'client');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'optionhdr', get_string('options', 'competition'));
        $mform->addElement('text', 'submissionrate', get_string('submissionrate', 'competition'));
        $mform->addElement('select', 'submissioninterval', get_string('submissioninterval', 'competition'), $COMPETITION_INTERVAL);
        $mform->addElement('select', 'scoringinterval', get_string('scoringinterval', 'competition'), $COMPETITION_INTERVAL);
        $mform->addElement('text', 'datausage', get_string('datausage', 'competition'));
        
        $mform->addHelpButton('datausage', 'datausage', 'competition');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'timerestricthdr', get_string('availability', 'competition'));
        $mform->addElement('checkbox', 'timerestrict', get_string('timerestrict', 'competition'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('choiceopen', 'competition'));
        $mform->disabledIf('timeopen', 'timerestrict');

        $mform->addElement('date_time_selector', 'timeclose', get_string('choiceclose', 'competition'));
        $mform->disabledIf('timeclose', 'timerestrict');
        
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'resultshdr', get_string('results', 'competition'));

        $mform->addElement('select', 'showscore', get_string('showscore', 'competition'), $COMPETITION_SHOWSCORE);
        $mform->addElement('select', 'publish', get_string('privacy', 'competition'), $COMPETITION_PUBLISH);
//-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){
        global $DB;
        if (!empty($this->_instance) && ($options = $DB->get_records_menu('choice_options',array('choiceid'=>$this->_instance), 'id', 'id,text'))
               && ($options2 = $DB->get_records_menu('choice_options', array('choiceid'=>$this->_instance), 'id', 'id,maxanswers')) ) {
            $choiceids=array_keys($options);
            $options=array_values($options);
            $options2=array_values($options2);

            foreach (array_keys($options) as $key){
                $default_values['option['.$key.']'] = $options[$key];
                $default_values['limit['.$key.']'] = $options2[$key];
                $default_values['optionid['.$key.']'] = $choiceids[$key];
            }

        }
        if (empty($default_values['timeopen'])) {
            $default_values['timerestrict'] = 0;
        } else {
            $default_values['timerestrict'] = 1;
        }

    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Set up completion section even if checkbox is not ticked
        if (!empty($data->completionunlocked)) {
            if (empty($data->completionsubmit)) {
                $data->completionsubmit = 0;
            }
        }
        return $data;
    }

    function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'choice'));
        return array('completionsubmit');
    }

    function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }
}

