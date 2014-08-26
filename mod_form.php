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
        global $CFG, $DB, $COMPETITION_INTERVAL, $COMPETITION_SHOWSCORE, $COMPETITION_PUBLISH;

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

        $this->add_intro_editor(true, get_string('description', 'competition'));
        
        $mform->addElement('file', 'scoringtemplate', get_string('scoringtemplate', 'competition')); //, null, array('accepted_types' => '*'));
        $mform->addRule('scoringtemplate', null, 'required', null, 'client');  

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'optionhdr', get_string('options', 'competition'));
        $mform->addElement('text', 'submissionrate', get_string('submissionrate', 'competition'));
        $mform->setType('submissionrate', PARAM_INT);
        $mform->setDefault('submissionrate', 1);
        $mform->addElement('select', 'submissioninterval', get_string('submissioninterval', 'competition'), $COMPETITION_INTERVAL);
        $mform->setDefault('submissioninterval', COMPETITION_INTERVAL_DAY);
        $mform->addElement('select', 'scoringinterval', get_string('scoringinterval', 'competition'), $COMPETITION_INTERVAL);
        $mform->addElement('text', 'datausage', get_string('datausage', 'competition'), array('size'=>'3'));
        $mform->setType('datausage', PARAM_INT);
        $mform->setDefault('datausage', 50);
        $mform->addHelpButton('datausage', 'datausage', 'competition');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'timerestricthdr', get_string('availability', 'competition'));
        $mform->addElement('checkbox', 'timerestrict', get_string('timerestrict', 'competition'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('competitionopen', 'competition'));
        $mform->disabledIf('timeopen', 'timerestrict');

        $mform->addElement('date_time_selector', 'timeclose', get_string('competitionclose', 'competition'));
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

        $mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'competition'));
        return array('completionsubmit');
    }

    function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }
}

