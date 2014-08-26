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
require_once ("$CFG->libdir/filelib.php");

$id = required_param('id', PARAM_INT);
// Course Module ID

$url = new moodle_url('/mod/competition/dataset.php', array('id' => $id));
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

global $CFG;

$context = context_module::instance($cm -> id);

$options = array('noclean' => true, 'para' => false, 'filter' => true, 'context' => $context, 'overflowdiv' => true);

$dataset = file_rewrite_pluginfile_urls($competition -> dataset, 'pluginfile.php', $PAGE -> context -> id, 'mod_competition', 'dataset', 0, competition_editors_options($PAGE -> context));

echo $OUTPUT -> box(trim(format_text($dataset, $competition -> introformat, $options, null)));

echo $OUTPUT -> footer();
