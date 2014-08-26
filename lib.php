<?php
/**
 * Library of functions for the competition module.
 *
 *
 * @package    mod_competition
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/competition/locallib.php');

define('COMPETITION_INTERVAL_HOUR', 60*60);
define('COMPETITION_INTERVAL_DAY', 60*60*24);
define('COMPETITION_INTERVAL_WEEK', 60*60*24*7);

define('COMPETITION_PUBLISH_ANONYMOUS', '0');
define('COMPETITION_PUBLISH_NAMES', '1');

define('COMPETITION_SHOWSCORE_NOT', '0');
define('COMPETITION_SHOWSCORE_ALWAYS', '1');

/** @global array $COMPETITION_PUBLISH */
global $COMPETITION_PUBLISH;
$COMPETITION_PUBLISH = array (
                               COMPETITION_PUBLISH_NAMES      => get_string('publishnames', 'competition'),
                               COMPETITION_PUBLISH_ANONYMOUS  => get_string('publishanonymous', 'competition'),
                               );

/** @global array $COMPETITION_SHOWRESULTS */
global $COMPETITION_SHOWSCORE;
$COMPETITION_SHOWSCORE = array (
                                 COMPETITION_SHOWSCORE_ALWAYS => get_string('showscorealways', 'competition'),
                                 COMPETITION_SHOWSCORE_NOT => get_string('showscorenot', 'competition'),
                                 );


/** @global array $COMPETITION_INTERVAL */
global $COMPETITION_INTERVAL;
$COMPETITION_INTERVAL = array (COMPETITION_INTERVAL_HOUR => get_string('hour', 'competition'),
                                COMPETITION_INTERVAL_DAY => get_string('day','competition'),
                                COMPETITION_INTERVAL_WEEK => get_string('week','competition'));

global $COMPETITION_TIME_UNITS;
$COMPETITION_TIME_UNITS = array(
    get_string('years', 'competition')     => 31536000,
    get_string('months', 'competition')    => 2592000,
    get_string('weeks', 'competition')     => 604800,
    get_string('days', 'competition')      => 86400,
    get_string('hours', 'competition')     => 3600,
    get_string('minutes', 'competition')   => 60,
    get_string('seconds', 'competition')   => 1 
);

/**
 * For each active competition, rescore all submissions when the scoring interval
 * has elapsed.
 * 
 */
 function competition_cron() {
    global $DB;
    $now = time();
    $competitions = $DB->get_records_sql(
        'SELECT * FROM {competition}
         WHERE (timeopen=0 AND timeclose=0)
         OR (timeopen=0 AND timeclose<=?)
         OR (timeopen>=? AND timeclose=0)
         OR (timeopen>=? AND timeclose<=?)',
         array($now, $now, $now, $now)
    );
    // Rescore active competitions
    foreach ($competitions as $id => $competition) {
        if (($now - $competition->timescored) > $competition->scoringinterval) {
            echo 'Rescoring competition ', $competition->id;
            rescore_competition($competition->id);
        } else {
            echo 'Must wait ', $competition->scoringinterval - ($now - $competition->timescored), ' seconds to score competition ', $competition->id;
        }
    }
}

function competition_add_instance($competition, $mform) {
    global $DB;
    
    unset($competition->scoringtemplate);
    // $competition->scoringtemplate = $mform->get_file_content('scoringtemplate');
    $competition->timemodified = time();

    if (empty($competition->timerestrict)) {
        $competition->timeopen = 0;
        $competition->timeclose = 0;
    }
    
    $competition->id = $DB->insert_record("competition", $competition);
    return $competition->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $competition
 * @return bool
 */
function competition_update_instance($competition) {
    global $DB;
    
    $competition->id = $competition->instance;
    $competition->timemodified = time();

    if (empty($competition->timerestrict)) {
        $competition->timeopen = 0;
        $competition->timeclose = 0;
    }
    
    return $DB->update_record('competition', $competition);
}

/**
 * Gets a full competition record
 *
 * @global object
 * @param int $competitionid
 * @return object|bool The competition or false
 */
function competition_get_competition($competitionid) {
    global $DB;

    if ($competition = $DB->get_record("competition", array("id" => $competitionid))) {
        return $competition;
    }
    return false;
}

function competition_extend_navigation($module, $course, $competition, $cm) {
    global $PAGE;
    // $previewnode = $PAGE->navigation->add(get_string('preview'), new moodle_url('/a/link/if/you/want/one.php'), navigation_node::TYPE_CONTAINER);
    // $thingnode = $previewnode->add(get_string('name of thing'), new moodle_url('/a/link/if/you/want/one.php'));
    // $thingnode->make_active();
    
    $coursenode = $PAGE->navigation->find($course->id, navigation_node::TYPE_COURSE);
    
    // Remove all other nodes
    foreach ($coursenode->get_children_key_list() as $idx => $key) {
        $coursenode->get($key)->hide();
    }
    $description = $coursenode->add(get_string('description', 'competition'), new moodle_url('description.php', array('id' => $module->key)));
    $dataset = $coursenode->add(get_string('dataset', 'competition'), new moodle_url('dataset.php', array('id' => $module->key)));
    $leaderboard = $coursenode->add(get_string('leaderboard', 'competition'), new moodle_url('view.php', array('id' => $module->key)));
    $submissions = $coursenode->add(get_string('mysubmissions', 'competition'), new moodle_url('submit.php', array('id' => $module->key)));
    $coursenode->force_open();
    
}

function competition_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;
    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);
    
    if ($filearea === 'description') {
        $itemid = (int)array_shift($args);
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_competition/$filearea/$itemid/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // finally send the file forcing download for security reasons
        send_stored_file($file, 0, 0, true, $options);
        return false;
    } else if ($filearea === 'dataset') {
        $itemid = (int)array_shift($args);
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_competition/$filearea/$itemid/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // finally send the file forcing download for security reasons
        send_stored_file($file, 0, 0, true, $options);
        return false;
    }

    return false;
}