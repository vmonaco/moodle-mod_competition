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
    $datausage = $DB->get_field('competition_competition', 'datausage', array('id'=>$compid));
    
    $params = array($CFG->dboptions->dbsocket,
                     $CFG->dbuser,
                     $CFG->dbpass,
                     $CFG->dbname,
                     $CFG->prefix,
                     $compid,
                     $datsetusage,
                     );
    
    $command = escapeshellcmd($CFG -> dirroot . '/mod/competition/scripts/score.py ' . implode(' ', $params));
    exec($command);
}

function insert_submission() {
    // Check if a submission is allowed:
    // Competition is active and the user is able to make a submission
    // Validate the submission
}