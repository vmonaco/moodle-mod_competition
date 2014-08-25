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

function check_submission_history($competition, $userid) {
    // The user must be enrolled in the course that hosts the competition
    
    // Check if the number of submissions has reached the submission rate
    
    // Number of submissions made in the current period
    // count submissions since (current time - submission interval)
    
    // Return the number of allowable submissions in this period and the time
    //  to next submission (0 if submissions are currently allowed)
    
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
