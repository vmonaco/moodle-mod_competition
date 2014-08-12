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
require_once($CFG->dirroot . '/mod/competition/constants.php');

define('COMPETITION_INTERVAL_HOUR', 60*60);
define('COMPETITION_INTERVAL_DAY', 60*60*24);
define('COMPETITION_INTERVAL_WEEK', 60*60*24*7);

define('COMPETITION_PUBLISH_ANONYMOUS', '0');
define('COMPETITION_PUBLISH_NAMES', '1');

define('COMPETITION_SHOWSCORE_NOT', '0');
define('COMPETITION_SHOWSCORE_ALWAYS', '1');

/** @global array $COMPETITION_PUBLISH */
global $COMPETITION_PUBLISH;
$COMPETITION_PUBLISH = array (COMPETITION_PUBLISH_ANONYMOUS  => get_string('publishanonymous', 'competition'),
                               COMPETITION_PUBLISH_NAMES      => get_string('publishnames', 'competition'));

/** @global array $COMPETITION_SHOWSCORE */
global $COMPETITION_SHOWSCORE;
$COMPETITION_SHOWSCORE = array (COMPETITION_SHOWSCORE_NOT => get_string('showscorenot', 'competition'),
                                 COMPETITION_SHOWSCORE_ALWAYS => get_string('showscorealways', 'competition'));


/** @global array $COMPETITION_INTERVAL */
global $COMPETITION_INTERVAL;
$COMPETITION_INTERVAL = array (COMPETITION_INTERVAL_HOUR => get_string('hour', 'competition'),
                                COMPETITION_INTERVAL_DAY => get_string('day','competition'),
                                COMPETITION_INTERVAL_WEEK => get_string('week','competition'));

/**
 * For each active competition, rescore all submissions when the scoring interval
 * has elapsed.
 * 
 */
 function mod_competition_cron() {
    global $DB;
    
}