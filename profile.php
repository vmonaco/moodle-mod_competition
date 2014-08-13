<?php
/**
 * 
 *
 * @package    mod_competition
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once ("../../config.php");
require_once ("lib.php");
require_once ('reportlib.php');

$id = required_param('id', PARAM_INT); // Course Module ID

$PAGE -> set_url(new moodle_url('/mod/competition/view.php', array('id' => $id)));

require_login();

if (!$cm = get_coursemodule_from_id('competition', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB -> get_record("course", array("id" => $cm -> course))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

if (!$competition = competition_get_competition($cm -> instance)) {
    print_error('invalidcoursemodule');
}

$PAGE->set_title(format_string($competition->name));
$PAGE->set_heading($competition->name);

echo $OUTPUT -> header();
 
$leaderboard = new competition_submission_report($competition, $USER);
$leaderboard -> load_submissions();
$numdata = $leaderboard -> get_numrows();

$leaderboardhtml = $leaderboard -> get_report_table();
echo $leaderboardhtml;

echo $OUTPUT -> footer();
