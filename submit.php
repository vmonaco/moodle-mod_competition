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

$id = required_param('id', PARAM_INT);
// Course Module ID

$url = new moodle_url('/mod/competition/submit.php', array('id' => $id));
$PAGE -> set_url($url);

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

if (!has_capability('mod/competition:submit', context_module::instance($cm->id))) {
    print_error('nosubmitaccess', 'competition');
}

$PAGE -> set_title(format_string($competition -> name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT -> header();
echo '<h1>' . get_string('submit', 'competition') . '</h1>';
$state = competition_state($competition);

if (COMPETITION_STATE_BEFORE_OPEN == $state) {
    echo "<hr><div class='mod_competition_timer numberTypeWrapper'><p>";
    echo "Competition has not opened yet.";
    echo "</p></div><hr>";
} else if (COMPETITION_STATE_AFTER_CLOSE == $state) {
    echo "<hr><div class='mod_competition_timer numberTypeWrapper'><p>";
    echo "Competition is closed, you cannot make new submissions.";
    echo "</p></div><hr>";
} else { // state open or no limits
    $mform = new competition_submission_form($url -> out());
    $mform -> competition = $competition;
    $mform -> userid = $USER -> id;

    if ($mform -> is_cancelled()) {
        // Do nothing
    } else if ($fromform = $mform -> get_data()) {
        // Make a submission
        create_submission($competition -> id, $USER -> id, $mform, $fromform);
    }
    
    // Show a timer if the user must wait to make a submission
    list($submissionsleft, $timeleft) = remaining_submissions($competition, $USER -> id);
    
    if ($submissionsleft) {
        $s = $submissionsleft > 1 ? 's' : '';
        echo "<hr><div class='mod_competition_timer numberTypeWrapper'><p>";
        echo "You can make up to $submissionsleft submission$s right now";
        echo "</p></div><hr>";
    } else if (COMPETITION_STATE_OPEN == $state) {
        $PAGE -> requires -> js('/mod/competition/scripts/jquery-1.10.2.min.js');
        $PAGE -> requires -> js('/mod/competition/scripts/timer.js');
        echo create_timer($timeleft, 'Until you can make another submission');
    }
    
    $mform -> display();
}

$submissions = new competition_submission_report($competition, $USER);
$submissions-> load_submissions();
$numdata = $submissions -> get_numrows();
echo $submissions-> get_report_table();
echo $OUTPUT -> footer();
