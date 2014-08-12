<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 *
 * @package    mod_competition
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG -> dirroot . '/mod/competition/constants.php');
require_once ($CFG -> dirroot . '/mod/competition/lib.php');

/**
 * Rescore and rerank all submissions
 */
function rescore_competition($compid) {
    global $DB, $CFG;
    $datausage = $DB->get_field('competition_competition', 'datausage', array('id'=>$compid));
    
    $params = array($CFG->dboptions->dbsocket,
                     $CFG->dbuser,
                     $CFG->dbpass,
                     $CFG->dbname,
                     $CFG->prefix,
                     $compid,
                     $datsetusage,
                     );
    
    $command = escapeshellcmd($CFG -> dirroot . '/mod/competition/scripts/score.py ' . implode(' ', $params));
    exec($command);
}

function insert_submission() {
    // Check if a submission is allowed:
    // Competition is active and the user is able to make a submission
    // Validate the submission
}