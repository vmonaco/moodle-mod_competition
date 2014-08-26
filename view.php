<?php
/**
 * View the public leaderboard and user submissions, make a new submission
 * Instructors have the ability to rescore submissions here
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

$PAGE->requires->js('/mod/competition/scripts/jquery-1.10.2.min.js');
$PAGE->requires->js('/mod/competition/scripts/timer.js');
$now = time();
if ($competition->timeopen > 0 && ($competition->timeopen - $now) > 0) {
    // Competition hasn't started yet
    echo create_timer(timestamp2units($competition->timeopen - $now, array('days','hours','minutes','seconds')), 'Competition begins');
} else if ($competition->timeclose > 0 && ($competition->timeclose - $now) > 0) {
    // Competition hasn't ended yet
    echo create_timer(timestamp2units($competition->timeclose - $now, array('days','hours','minutes','seconds')), 'Competition ends');
} else if ($competition->timeclose > 0 && ($now - $competition->timeclose) > 0) {
    // Competition is over
    echo 'Competition is over';
} else {
    // Competition has no time limits
    echo 'No limits';
}
     
$leaderboard = new competition_leaderboard_report($competition);
$leaderboard -> load_users();
$numdata = $leaderboard -> get_numrows();

$leaderboardhtml = $leaderboard -> get_report_table();
echo $leaderboardhtml;

echo $OUTPUT -> footer();
