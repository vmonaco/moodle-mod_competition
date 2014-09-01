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

global $COMPETITION_VALIDATE_DIR;
global $COMPETITION_SCORE_DIR;
$COMPETITION_VALIDATE_DIR = $CFG -> dirroot . '/mod/competition/validate';
$COMPETITION_SCORE_DIR = $CFG -> dirroot . '/mod/competition/score';

/**
 * Rescore and rerank all submissions
 */
function rescore_competition($competition) {
    global $DB, $CFG;

    $params = array($CFG -> dboptions['dbsocket'], $CFG -> dbuser, $CFG -> dbpass, $CFG -> dbname, $CFG -> prefix, $competition -> id, $competition -> datausage);

    $command = escapeshellcmd($CFG -> dirroot . '/mod/competition/score/' . $competition -> scorescript . ' ' . implode(' ', $params));
    exec($command);
    $DB -> set_field('competition', 'timescored', time(), array('id' => $competition -> id));
}

function validate_submission($competition, $submissionfile) {
    global $DB, $CFG;
    
    $params = array($CFG -> dboptions['dbsocket'], $CFG -> dbuser, $CFG -> dbpass, $CFG -> dbname, $CFG -> prefix, $competition -> id, $submissionfile);

    $command = escapeshellcmd($CFG -> dirroot . '/mod/competition/validate/' . $competition->validatescript . ' ' . implode(' ', $params));
    
    $output = array('Error parsing submission');
    $exitcode = 0;
    exec($command . ' 2>&1 ', $output, $exitcode);
    
    if ($exitcode > 0) {
        return implode('<br/>', $output);
    }
}

function remaining_submissions($competition, $userid) {
    global $DB;
    // The user must be enrolled in the course that hosts the competition

    // Number of submissions made in the current period
    // count submissions since (current time - submission interval)
    $recentsubs = $DB -> count_records_sql("SELECT COUNT(*) FROM {competition_submission}
                            WHERE compid=? AND userid=? AND timesubmitted>=? 
                            ORDER BY timesubmitted
                            LIMIT 1", array($competition -> id, $userid, time() - $competition -> submissioninterval));
    $submissionsleft = $competition -> submissionrate - $recentsubs;

    // Time remaining to next submission
    $timeleft = 0;
    if ($recentsubs >= $competition -> submissionrate) {
        $earliestsub = $DB -> get_records_sql("SELECT * FROM {competition_submission}
                            WHERE compid=? AND userid=? AND timesubmitted>=? 
                            ORDER BY timesubmitted
                            LIMIT 1", array($competition -> id, $userid, time() - $competition -> submissioninterval));
        $keys = array_keys($earliestsub);
        $timeleft = $competition -> submissioninterval - (time() - $earliestsub[$keys[0]] -> timesubmitted);
    }

    // Return the number of allowable submissions in this period and the time
    //  to next submission (0 if submissions are currently allowed)
    return array($submissionsleft, timestamp2units($timeleft, array('hours', 'minutes', 'seconds')));
}

function create_submission($compid, $userid, $mform, $fromform) {
    global $DB;

    $submission = new stdClass();
    $submission -> compid = $compid;
    $submission -> userid = $userid;
    $submission -> ipaddress = ip2long($_SERVER['REMOTE_ADDR']);
    $submission -> submission = $fromform -> submission;
    $submission -> comments = $fromform -> comments;
    $submission -> score = '';
    $submission -> timesubmitted = time();

    $submission -> id = $DB -> insert_record("competition_submission", $submission);
    return $submission;
}

function timestamp2units($timestamp, $units) {
    global $COMPETITION_TIME_UNITS;
    $timeunits = array();

    foreach ($units as $idx => $unit) {
        $timeunits[$unit] = (int)($timestamp / $COMPETITION_TIME_UNITS[$unit]);
        $timestamp -= $COMPETITION_TIME_UNITS[$unit] * $timeunits[$unit];
    }
    return $timeunits;
}

function timer_sort_units_to_show($idstring) {
    $idarray = explode(',', $idstring);
    //array of array positions eg 1,2,3 tha where selected on the settings menu

    $units = block_enrolmenttimer_get_units();
    $unitKeys = array_keys($units);
    $output = array();

    foreach ($idarray as $key => $value) {
        // will equal $output['seconds'] = 1
        $unitKey = $unitKeys[$value];
        $output[$unitKey] = $units[$unitKey];
    }

    return $output;
}

function get_score_scripts() {
    global $COMPETITION_SCORE_DIR;
    return array_values(array_diff(scandir($COMPETITION_SCORE_DIR), array('..', '.')));
}

function get_validate_scripts() {
    global $COMPETITION_VALIDATE_DIR;
    return array_values(array_diff(scandir($COMPETITION_VALIDATE_DIR), array('..', '.')));
}

function create_timer($timeLeft, $expirytext) {
    global $COURSE, $USER, $DB, $CFG;

    $output = '<div class=mod_competition_timer>';
    $activecountdown = 1;
    $output .= '<div';
    if ($activecountdown == 1) {
        $output .= ' class="active"';
    }
    $output .= '>';

    if (!$timeLeft) {
        $displayNothingNoDateSet = get_config('enrolmenttimer', 'displayNothingNoDateSet');
        if ($displayNothingNoDateSet == 1) {
            $output = '';
            return $output;
        } else {
            $output .= '<p class="noDateSet">' . get_string('noDateSet', 'competition') . '</p></div></div>';
            return $output;
        }
    } else {
        //$output .= 'You have ';
        $counter = 1;
        $text = '';
        $force2digits = 1;
        //get_config('enrolmenttimer', 'forceTwoDigits');
        $displayLabels = 1;
        //get_config('enrolmenttimer', 'displayUnitLabels');
        $displayTextCounter = 0;
        //get_config('enrolmenttimer', 'displayTextCounter');

        $output .= '<hr>';
        $output .= '<div class="visual-counter">';
        $output .= '<div class="timer-wrapper"';
        if ($force2digits == 1) {
            $output .= ' data-id="force2" ';
        }
        $output .= '>';
        foreach ($timeLeft as $unit => $count) {
            $stringCount = (string)$count;
            $countLength = strlen($stringCount);

            if ($displayLabels == 1) {
                $output .= '<div class="numberTypeWrapper">';
            }

            $output .= '<div class="timerNum" data-id="' . $unit . '">';

            if ($countLength == 1 && $force2digits == 1) {
                $output .= '<span class="timerNumChar" data-id="0">0</span>';
                $output .= '<span class="timerNumChar" data-id="1">' . $stringCount . '</span>';
            } else {
                for ($i = 0; $i < $countLength; $i++) {
                    $output .= '<span class="timerNumChar" data-id="' . $i . '">' . $stringCount[$i] . '</span>';
                }
            }

            $output .= '</div>';

            if ($displayLabels == 1) {
                $output .= '<p>' . $unit . '</p></div>';
            }

            if ($counter != count($timeLeft)) {
                $output .= '<div class="seperator">:</div>';
            }

            $text .= '<d class="' . $unit . '">' . $count . '</span> ';
            if ($count > 1) {
                $text .= $unit . ' ';
            } else {
                $text .= rtrim($unit, "s") . ' ';
            }

            $counter++;

        }
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<hr>';
        $output .= '<div class="text-wrapper">';
        $output .= '<p class="text-desc"';
        if ($displayTextCounter == 0) {
            $output .= ' style="display: none;"';
        }
        $output .= '>' . $text . '</p>';
        $output .= '<p class="sub-text">' . $expirytext . '</p>';
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

function competition_editors_options(stdclass $context) {
    return array('subdirs' => 1, 'maxbytes' => 0, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $context, 'noclean' => 1, 'trusttext' => 0);
}

function create_forum($name, $section, $course) {
    global $DB, $CFG;
    
    $add = 'forum';
    $course = $DB->get_record('course', array('id'=>$course), '*', MUST_EXIST);
    list($module, $context, $cw) = can_add_moduleinfo($course, $add, $section);
    
    $data = new stdClass();
    $data->section          = $section;
    $data->visible          = 1;
    $data->course           = $course->id;
    $data->module           = $module->id;
    $data->modulename       = $module->name;
    $data->groupmode        = $course->groupmode;
    $data->groupingid       = $course->defaultgroupingid;
    $data->groupmembersonly = 0;
    $data->id               = '';
    $data->instance         = '';
    $data->coursemodule     = '';
    $data->add              = $add;
    $data->return           = 0;
    $data->name             = $name;
    $data->cmidnumber       = $module->id;
    $data->type             = 'general';
    $data->forcesubscribe   = 0;
    
    $draftid_editor = file_get_submitted_draft_itemid('introeditor');
    $data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
    
    $DB->set_field('course_sections', 'visible', '1', array('id' => $section));
    $forum = add_moduleinfo($data, $course);
    return $forum->coursemodule;
}
