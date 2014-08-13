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

$url = new moodle_url('/mod/competition/profile.php', array('id' => $id));
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

$PAGE -> set_title(format_string($competition -> name));
$PAGE -> set_heading($competition -> name);

echo $OUTPUT -> header();

// Only show the submission form if a user can currently make submissions

//Instantiate simplehtml_form
$mform = new competition_submission_form($url -> out());
$mform -> competition = $competition;

if ($mform -> is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform -> get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    // Make a submission
    
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    //Set default data (if any)
    $data = new stdClass();
    $mform -> set_data($data);
    //displays the form
    $mform -> display();
}

$leaderboard = new competition_submission_report($competition, $USER);
$leaderboard -> load_submissions();
$numdata = $leaderboard -> get_numrows();

$leaderboardhtml = $leaderboard -> get_report_table();
echo $leaderboardhtml;

echo $OUTPUT -> footer();
