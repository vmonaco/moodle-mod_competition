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

require_once ($CFG -> dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');
// require_once($CFG->libdir . '/editorlib.php');

class mod_competition_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB, $COMPETITION_INTERVAL, $COMPETITION_SHOWSCORE, $COMPETITION_PUBLISH;

        $mform = &$this -> _form;

        //-------------------------------------------------------------------------------
        $mform -> addElement('header', 'general', get_string('general', 'form'));

        $mform -> addElement('text', 'name', get_string('competitionname', 'competition'), array('size' => '64'));
        if (!empty($CFG -> formatstringstriptags)) {
            $mform -> setType('name', PARAM_TEXT);
        } else {
            $mform -> setType('name', PARAM_CLEANHTML);
        }
        $mform -> addRule('name', null, 'required', null, 'client');
        $mform -> addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this -> add_intro_editor(true, get_string('intro', 'competition'));

        $mform -> addElement('editor', 'descriptioneditor', get_string('description', 'competition'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this -> context, 'subdirs' => true));
        $mform -> setType('descriptioneditor', PARAM_RAW);
        $mform -> addRule('descriptioneditor', get_string('required'), 'required', null, 'client');

        $mform -> addElement('editor', 'dataseteditor', get_string('dataset', 'competition'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this -> context, 'subdirs' => true));
        $mform -> setType('dataseteditor', PARAM_RAW);
        $mform -> addRule('dataseteditor', get_string('required'), 'required', null, 'client');

        $mform -> addElement('filepicker', 'scoringtemplate', get_string('scoringtemplate', 'competition'));

        //-------------------------------------------------------------------------------
        $mform -> addElement('header', 'optionhdr', get_string('options', 'competition'));
        $mform -> addElement('text', 'submissionrate', get_string('submissionrate', 'competition'));
        $mform -> setType('submissionrate', PARAM_INT);
        $mform -> setDefault('submissionrate', 1);
        $mform -> addElement('select', 'submissioninterval', get_string('submissioninterval', 'competition'), $COMPETITION_INTERVAL);
        $mform -> setDefault('submissioninterval', COMPETITION_INTERVAL_DAY);
        $mform -> addElement('select', 'scoringinterval', get_string('scoringinterval', 'competition'), $COMPETITION_INTERVAL);
        $mform -> addElement('text', 'datausage', get_string('datausage', 'competition'), array('size' => '3'));
        $mform -> setType('datausage', PARAM_INT);
        $mform -> setDefault('datausage', 50);
        $mform -> addHelpButton('datausage', 'datausage', 'competition');

        $mform -> addElement('select', 'scorescript', get_string('scorescript', 'competition'), get_score_scripts());
        $mform -> addElement('select', 'validatescript', get_string('validatescript', 'competition'), get_validate_scripts());

        //-------------------------------------------------------------------------------
        $mform -> addElement('header', 'timerestricthdr', get_string('availability', 'competition'));
        $mform -> addElement('checkbox', 'timerestrict', get_string('timerestrict', 'competition'));

        $mform -> addElement('date_time_selector', 'timeopen', get_string('competitionopen', 'competition'));
        $mform -> disabledIf('timeopen', 'timerestrict');

        $mform -> addElement('date_time_selector', 'timeclose', get_string('competitionclose', 'competition'));
        $mform -> disabledIf('timeclose', 'timerestrict');

        //-------------------------------------------------------------------------------
        $mform -> addElement('header', 'resultshdr', get_string('results', 'competition'));

        $mform -> addElement('select', 'showscore', get_string('showscore', 'competition'), $COMPETITION_SHOWSCORE);
        $mform -> addElement('select', 'publish', get_string('leaderboardprivacy', 'competition'), $COMPETITION_PUBLISH);
        //-------------------------------------------------------------------------------
        $this -> standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        $this -> add_action_buttons();
    }

    function data_preprocessing(&$data) {
        global $DB;

        if (empty($data['timeopen'])) {
            $data['timerestrict'] = 0;
        } else {
            $data['timerestrict'] = 1;
        }

        if ($this -> current -> instance) {
            // editing an existing competition - let us prepare the added editor elements (intro done automatically)
            $draftitemid = file_get_submitted_draft_itemid('description');
            $data['descriptioneditor']['text'] = file_prepare_draft_area($draftitemid, $this -> context -> id, 'mod_competition', 'description', 0, competition_editors_options($this -> context), $data['description']);
            $data['descriptioneditor']['format'] = $data['descriptionformat'];
            $data['descriptioneditor']['itemid'] = $draftitemid;

            $draftitemid = file_get_submitted_draft_itemid('dataset');
            $data['dataseteditor']['text'] = file_prepare_draft_area($draftitemid, $this -> context -> id, 'mod_competition', 'dataset', 0, competition_editors_options($this -> context), $data['dataset']);
            $data['dataseteditor']['format'] = $data['datasetformat'];
            $data['dataseteditor']['itemid'] = $draftitemid;
            
        } else {
            // adding a new competition instance
            $draftitemid = file_get_submitted_draft_itemid('description');
            file_prepare_draft_area($draftitemid, null, 'mod_competition', 'description', 0);
            $data['descriptioneditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);

            $draftitemid = file_get_submitted_draft_itemid('dataset');
            file_prepare_draft_area($draftitemid, null, 'mod_competition', 'dataset', 0);
            $data['dataseteditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);
            
            $this -> _form -> addRule('scoringtemplate', null, 'required', null, 'client');
        }
    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        
        // Unset the scoring template if a new one hasn't been uploaded
        if ($scoringtemplate = $this->get_file_content('scoringtemplate')) {
            $data->scoringtemplate = $scoringtemplate;
        } else {
            unset($data->scoringtemplate);
        }
        
        $validatescripts = get_validate_scripts();
        $data->validatescript = $validatescripts[$data->validatescript];
        
        $scorescripts = get_score_scripts();
        $data->scorescript = $scorescripts[$data->scorescript];
         
        return $data;
    }

}
