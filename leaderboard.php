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
$PAGE -> set_url(new moodle_url('/mod/competition/leaderboard.php', array('id' => $id)));

require_login();

if (!$cm = get_coursemodule_from_id('competition', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB -> get_record("course", array("id" => $cm -> course))) {
    print_error('coursemisconf');
}

require_course_login($course, true, $cm);

if (!$competition = competition_get_competition($cm -> instance)) {
    print_error('invalidcoursemodule');
}

$PAGE->set_title(format_string($competition->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT -> header();
echo '<h1>' . get_string('leaderboard', 'competition') . '</h1>';

$PAGE->requires->js('/mod/competition/scripts/jquery-1.10.2.min.js');
$PAGE->requires->js('/mod/competition/scripts/timer.js');

$now = time();

$state = competition_state($competition);
if (COMPETITION_STATE_BEFORE_OPEN == $state) {
    echo create_timer(timestamp2units($competition->timeopen - $now, array('days','hours','minutes','seconds')), 'Competition begins');
}

if (COMPETITION_STATE_OPEN == $state) {
    echo create_timer(timestamp2units($competition->timeclose - $now, array('days','hours','minutes','seconds')), 'Competition ends');
}
    
if (COMPETITION_STATE_AFTER_CLOSE == $state) {
    echo "<hr><div class='mod_competition_timer numberTypeWrapper'><p>";
    echo "Competition is closed.";
    echo "</p></div><hr>";
}

if (COMPETITION_STATE_NO_LIMIT == $state) {
    echo "<hr><div class='mod_competition_timer numberTypeWrapper'><p>";
    echo "Competition is open, no deadline for submissions.";
    echo "</p></div><hr>";
}
     
$leaderboard = new competition_leaderboard_report($competition);
$leaderboard -> load_users();
$numdata = $leaderboard -> get_numrows();

$leaderboardhtml = $leaderboard -> get_report_table();
echo "<div class='mod_competition'><div class='leaderboard'><p>This leaderboard is calculated using $competition->datausage% of the test data</p></div></div>";
echo $leaderboardhtml;

echo $OUTPUT -> footer();
