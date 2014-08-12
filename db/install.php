<?php
/**
 * 
 * 
 * @package mod_competition
 * @copyright Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/competition/locallib.php');

/**
 * Post-install script
 */
function xmldb_mod_competition_install() {

    return true;
}
