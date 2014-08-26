<?php
/**
 *
 *
 * @package    mod_competition
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG -> dirroot . '/mod/competition/lib.php');


/**
 * Rescore and rerank all submissions
 */
function rescore_competition($compid) {
    global $DB, $CFG;
    $datausage = $DB->get_field('competition', 'datausage', array('id'=>$compid));
    
    $params = array($CFG->dboptions['dbsocket'],
                     $CFG->dbuser,
                     $CFG->dbpass,
                     $CFG->dbname,
                     $CFG->prefix,
                     $compid,
                     $datausage
                     );
    
    $command = escapeshellcmd($CFG -> dirroot . '/mod/competition/scripts/score.py ' . implode(' ', $params));
    
    exec($command);
}

function validate_submission($compid, $submissionfile) {
    global $DB, $CFG;
    
    $template = $DB->get_field('competition', 'scoringtemplate', array('id'=>$compid));
    $tmpHandle = tmpfile();
    fwrite($tmpHandle, $template);
    fseek($tmpHandle, 0);
    $metaDatas = stream_get_meta_data($tmpHandle);
    $templatefile = $metaDatas['uri'];
    
    $params = array($templatefile,
                     $submissionfile
                     );
    
    $command = escapeshellcmd($CFG -> dirroot . '/mod/competition/scripts/validate.py ' . implode(' ', $params));
    $output = array('Error parsing submission');
    $exitcode = 0;
    exec($command . ' 2>&1 ', $output, $exitcode);
    fclose($tmpHandle);
    
    if ($exitcode > 0) {
        return implode('<br/>', $output);
    }
}

function submission_rate($competition, $userid) {
    
}

function remaining_submissions($competition, $userid) {
    global $DB;
    // The user must be enrolled in the course that hosts the competition
    
    // Number of submissions made in the current period
    // count submissions since (current time - submission interval)
    $recentsubs = $DB->count_records_sql(
                            "SELECT COUNT(*) FROM {competition_submission}
                            WHERE compid=? AND userid=? AND timesubmitted>=? 
                            ORDER BY timesubmitted
                            LIMIT 1", 
                            array($competition->id, $userid, time() - $competition->submissioninterval));
    $submissionsleft = $competition->submissionrate - $recentsubs;
    
    // Time remaining to next submission
    $timeleft = 0;
    if ($recentsubs >= $competition->submissionrate) {
        $earliestsub = $DB->get_records_sql( 
                            "SELECT * FROM {competition_submission}
                            WHERE compid=? AND userid=? AND timesubmitted>=? 
                            ORDER BY timesubmitted
                            LIMIT 1", 
                            array($competition->id, $userid, time() - $competition->submissioninterval));
        $timeleft = $competition->submissioninterval - (time() - $earliestsub[array_keys($earliestsub)[0]]->timesubmitted);
    }

    // Return the number of allowable submissions in this period and the time
    //  to next submission (0 if submissions are currently allowed)
    return array($submissionsleft, $timeleft);
}

function create_submission($compid, $userid, $mform, $fromform) {
    global $DB;
    
    $submission = new stdClass();
    $submission->compid = $compid;
    $submission->userid = $userid;
    $submission->ipaddress = ip2long($_SERVER['REMOTE_ADDR']);
    $submission->submission = $mform->get_file_content('submission');
    $submission->comments = $fromform->comments;
    $submission->score = '';
    $submission->timesubmitted = time();
    
    $submission->id = $DB->insert_record("competition_submission", $submission);
    return $submission;
}

function competition_get_js_module() {
    global $PAGE;

    return array(
        'name' => 'mod_quiz',
        'fullpath' => '/mod/competition/module.js',
        'requires' => array('base', 'dom', 'event-delegate', 'event-key',
                'core_question_engine', 'moodle-core-formchangechecker'),
        'strings' => array(
            array('cancel', 'moodle'),
            array('flagged', 'question'),
            array('functiondisabledbysecuremode', 'quiz'),
            array('startattempt', 'quiz'),
            array('timesup', 'quiz'),
            array('changesmadereallygoaway', 'moodle'),
        ),
    );
}