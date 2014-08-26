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
require_once ($CFG -> libdir . '/formslib.php');
require_once ($CFG -> libdir . '/tablelib.php');
require_once ($CFG -> dirroot . '/mod/competition/locallib.php');

/*
 * 
 */
class competition_leaderboard_report {

    /**
     * @var array $users
     */
    public $competition;

    public $leaderboard;

    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    public $rowcount = 0;

    public $numrows = 0;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    public function __construct($competition) {
        global $CFG;
        $this -> competition = $competition;

        $this -> baseurl = new moodle_url('view.php');
        $this -> pbarurl = new moodle_url('/mod/competition/view.php');
    }

    /**
     * Get information about which students to show in the report.
     * @return an array
     */
    public function load_users() {
        global $DB;

        $sort = 'rank';
        $fields = 'rank,userid,score';
        $condition = array('compid' => $this -> competition -> id);
        $this -> numrows = $DB -> count_records('competition_leaderboard', $condition);
        $this -> leaderboard = $DB -> get_records('competition_leaderboard', $condition, $sort, $fields);
        $this -> scorenames = array();
        foreach ($this->leaderboard as $rank => $userrank) {
            if (empty($userrank->score)) {
               $userrank -> score = array();
            } else {
                
                $userrank -> score = json_decode($userrank -> score, true);
            }
            
            $this -> scorenames = $this -> scorenames + $userrank -> score;
        }
        ksort($this->scorenames);
        $this->scorenames = array_keys($this->scorenames);
        return $this -> leaderboard;
    }

    public function get_report_table() {
        global $CFG, $DB, $OUTPUT, $PAGE;

        if (!$this ->leaderboard) {
            echo $OUTPUT -> notification(get_string('emptyleaderboard', 'competition'));
            return;
        }

        $html = '';

        $rows = $this -> get_rows();

        $datatable = new html_table();
        $datatable -> attributes['class'] = 'gradestable flexible boxaligncenter generaltable';
        $datatable -> id = 'competition-leaderboard';
        $datatable -> data = $rows;
        $html .= html_writer::table($datatable);

        return $html;
    }

    public function get_rows() {
        global $CFG, $USER, $OUTPUT, $DB;

        $rows = array();

        $headerrow = new html_table_row();
        $headerrow -> attributes['class'] = 'heading';

        $rankheader = new html_table_cell();
        $rankheader -> attributes['class'] = 'header';
        $rankheader -> scope = 'col';
        $rankheader -> header = true;
        $rankheader -> id = 'rankheader';
        $rankheader -> text = get_string('rank', 'competition');
        $headerrow -> cells[] = $rankheader;

        $userheader = new html_table_cell();
        $userheader -> attributes['class'] = 'header';
        $userheader -> scope = 'col';
        $userheader -> header = true;
        $userheader -> id = 'userheader';
        $userheader -> text = get_string('name');
        $headerrow -> cells[] = $userheader;

        foreach ($this->scorenames as $scorename) {
            $scoreheader = new html_table_cell();
            $scoreheader -> attributes['class'] = 'header';
            $scoreheader -> scope = 'col';
            $scoreheader -> header = true;
            $scoreheader -> id = 'scoreheader';
            $scoreheader -> text = $scorename;
            $headerrow -> cells[] = $scoreheader;
        }

        $rows[] = $headerrow;
        $rowclasses = array('even', 'odd');

        foreach ($this->leaderboard as $rank => $userrank) {

            $user = $DB -> get_record('user', array('id' => $userrank -> userid));

            $row = new html_table_row();
            $row -> id = 'fixed_rank_' . $rank;
            $row -> attributes['class'] = 'r' . $this -> rowcount++ . ' ' . $rowclasses[$this -> rowcount % 2];

            $rankcell = new html_table_cell();
            $rankcell -> attributes['class'] = 'rank';
            $rankcell -> header = true;
            $rankcell -> scope = 'row';
            $rankcell -> text .= $rank;
            $row -> cells[] = $rankcell;
            
            $usercell = new html_table_cell();
            $usercell -> attributes['class'] = 'user';

            $usercell -> header = true;
            $usercell -> scope = 'row';

            $usercell -> text .= html_writer::link(new moodle_url('/user/view.php', array('id' => $user -> id)), fullname($user));

            if (!empty($user -> suspendedenrolment)) {
                $usercell -> attributes['class'] .= ' usersuspended';

                // May be lots of suspended users so only get the string once
                if (empty($suspendedstring)) {
                    $suspendedstring = get_string('userenrolmentsuspended', 'grades');
                }
                $usercell -> text .= html_writer::empty_tag('img', array('src' => $OUTPUT -> pix_url('i/enrolmentsuspended'), 'title' => $suspendedstring, 'alt' => $suspendedstring, 'class' => 'usersuspendedicon'));
            }

            $row -> cells[] = $usercell;

            foreach ($this->scorenames as $scorename) {
                $scorecell = new html_table_cell();
                $scorecell -> attributes['class'] = 'score';
                $scorecell -> header = true;
                $scorecell -> scope = 'row';
                if (array_key_exists($scorename, $userrank->score)) {
                    $scorecell -> text .= $userrank -> score[$scorename];
                } else {
                    $scorecell -> text .= get_string('missingvalue', 'competition');
                }
                $row -> cells[] = $scorecell;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function get_numrows() {
        return $this -> numrows;
    }

    /**
     * Processes the data sent by the form (grades and feedbacks).
     * Caller is responsible for all access control checks
     * @param array $data form submission (with magic quotes)
     * @return array empty array if success, array of warnings if something fails.
     */
    public function process_data($data) {
        global $DB;
        $warnings = array();

        return $warnings;
    }

}

/*
 * 
 */
class competition_submission_report {

    /**
     * @var array $users
     */
    public $competition;

    public $submissions;

    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    public $rowcount = 0;

    public $numrows = 0;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    public function __construct($competition, $user) {
        global $CFG;
        $this -> competition = $competition;
        $this->user = $user;
        $this -> baseurl = new moodle_url('profile.php');
        $this -> pbarurl = new moodle_url('/mod/competition/profile.php');
    }

    /**
     * Get information about which students to show in the report.
     * @return an array
     */
    public function load_submissions() {
        global $DB;

        $sort = 'id';
        $fields = 'id,comments,score,timesubmitted,timescored';
        $condition = array('compid' => $this -> competition -> id, 'userid' => $this->user->id);
        $this -> numrows = $DB -> count_records('competition_submission', $condition);
        $this -> submissions = $DB -> get_records('competition_submission' , $condition, $sort, $fields);
            $this -> scorenames = array();
            foreach ($this->submissions as $id => $submission) {
            if (empty($submission->score)) {
            $submission -> score = array();
            } else {

            $submission -> score = json_decode($submission -> score, true);
            }
            $this -> scorenames = $this -> scorenames + $submission -> score;
            }
            ksort($this->scorenames);
            $this->scorenames = array_keys($this->scorenames);
            return $this -> submissions;
            }

            public function get_report_table() {
            global $CFG, $DB, $OUTPUT, $PAGE;

            if (!$this -> submissions) {
            echo $OUTPUT -> notification(get_string(
        'nosubmissions', 'competition'));
            return;
        }

        $html = '';

        $rows = $this -> get_rows();

        $datatable = new html_table();
        $datatable -> attributes['class'] = 'gradestable flexible boxaligncenter generaltable';
        $datatable -> id = 'competition-submissions';
        $datatable -> data = $rows;
        $html .= html_writer::table($datatable);

        return $html;
    }

    public function get_rows() {
        global $CFG, $USER, $OUTPUT, $DB;

        $rows = array();

        $headerrow = new html_table_row();
        $headerrow -> attributes['class'] = 'heading';

        $idheader = new html_table_cell();
        $idheader -> attributes['class'] = 'header';
        $idheader -> scope = 'col';
        $idheader -> header = true;
        $idheader -> id = 'idheader';
        $idheader -> text = get_string('submission', 'competition');
        $headerrow -> cells[] = $idheader;

        $commentsheader = new html_table_cell();
        $commentsheader -> attributes['class'] = 'header';
        $commentsheader -> scope = 'col';
        $commentsheader -> header = true;
        $commentsheader -> id = 'commentsheader';
        $commentsheader -> text = get_string('comments', 'competition');
        $headerrow -> cells[] = $commentsheader;
        
        $timesubmittedheader = new html_table_cell();
        $timesubmittedheader -> attributes['class'] = 'header';
        $timesubmittedheader -> scope = 'col';
        $timesubmittedheader -> header = true;
        $timesubmittedheader -> id = 'timesubmittedheader';
        $timesubmittedheader -> text = get_string('timesubmitted', 'competition');
        $headerrow -> cells[] = $timesubmittedheader;
        
        $timescoredheader = new html_table_cell();
        $timescoredheader -> attributes['class'] = 'header';
        $timescoredheader -> scope = 'col';
        $timescoredheader -> header = true;
        $timescoredheader -> id = 'timescoredheader';
        $timescoredheader -> text = get_string('timescored', 'competition');
        $headerrow -> cells[] = $timescoredheader;

        foreach ($this->scorenames as $scorename) {
            $scoreheader = new html_table_cell();
            $scoreheader -> attributes['class'] = 'header';
            $scoreheader -> scope = 'col';
            $scoreheader -> header = true;
            $scoreheader -> id = 'scoreheader';
            $scoreheader -> text = $scorename;
            $headerrow -> cells[] = $scoreheader;
        }

        
        $downloadheader = new html_table_cell();
        $downloadheader -> attributes['class'] = 'header';
        $downloadheader -> scope = 'col';
        $downloadheader -> header = true;
        $downloadheader -> id = 'downloadheader';
        $downloadheader -> text = get_string('name');
        $headerrow -> cells[] = $downloadheader;
        
        $rows[] = $headerrow;
        $rowclasses = array('even', 'odd');
        
        foreach ($this->submissions as $id => $submission) {

            $row = new html_table_row();
            $row -> attributes['class'] = 'r' . $this -> rowcount++ . ' ' . $rowclasses[$this -> rowcount % 2];
            $row -> id = 'fixed_submission_' . $this -> rowcount;

            $idcell = new html_table_cell();
            $idcell -> attributes['class'] = 'id';
            $idcell -> header = true;
            $idcell -> scope = 'row';
            $idcell -> text .= $this -> rowcount;
            $row -> cells[] = $idcell;

            $commentscell = new html_table_cell();
            $commentscell -> attributes['class'] = 'comments';
            $commentscell -> header = true;
            $commentscell -> scope = 'row';
            $commentscell -> text .= $submission->comments;
            $row -> cells[] = $commentscell;
            
            $timesubmittedcell = new html_table_cell();
            $timesubmittedcell -> attributes['class'] = 'timesubmitted';
            $timesubmittedcell -> header = true;
            $timesubmittedcell -> scope = 'row';
            $timesubmittedcell -> text .= $submission->timesubmitted;
            $row -> cells[] = $timesubmittedcell;
            
            $timescoredcell = new html_table_cell();
            $timescoredcell -> attributes['class'] = 'timescored';
            $timescoredcell -> header = true;
            $timescoredcell -> scope = 'row';
            $timescoredcell -> text .= $submission->timescored;
            $row -> cells[] = $timescoredcell;

            foreach ($this->scorenames as $scorename) {
                $scorecell = new html_table_cell();
                $scorecell -> attributes['class'] = 'score';
                $scorecell -> header = true;
                $scorecell -> scope = 'row';
                if (array_key_exists($scorename, $submission->score)) {
                    $scorecell -> text .= $submission -> score[$scorename];
                } else {
                    $scorecell -> text .= get_string('missingvalue', 'competition');
                }
                $row -> cells[] = $scorecell;
            }

            $downloadcell = new html_table_cell();
            $downloadcell -> attributes['class'] = 'download';
            $downloadcell -> header = true;
            $downloadcell -> scope = 'row';
            $downloadcell -> text .= 'Download link';
            $row -> cells[] = $downloadcell;
            
            $rows[] = $row;
        }

        // TODO: High, low, mean, median, std

        return $rows;
    }

    public function get_numrows() {
        return $this -> numrows;
    }

    /**
     * Processes the data sent by the form (grades and feedbacks).
     * Caller is responsible for all access control checks
     * @param array $data form submission (with magic quotes)
     * @return array empty array if success, array of warnings if something fails.
     */
    public function process_data($data) {
        global $DB;
        $warnings = array();
        
        // Download a submission
        
        // Upload a new submission
        
        return $warnings;
    }

}

class competition_submission_form extends moodleform {
    
    public $competition;
    
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore!
        
        $mform->addElement('header', 'general', get_string('newsubmission', 'competition'));
        
        $mform->addElement('textarea', 'comments', get_string("comments", "competition"), 'wrap="virtual" rows="5" cols="80"');
        $mform->addRule('comments', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
         $mform->addRule('comments', null, 'required');
         
        $mform->addElement('file', 'submission', get_string('submissionfile', 'competition')); //, null, array('accepted_types' => '*'));
        $mform->addRule('submission', null, 'required', null, 'client');
        
        $this->add_action_buttons(false, get_string('submit','competition'));
    }
    //Custom validation should be added here
    function validation($data, $files) {
        $errors = array();
      // Allowed to make a submission 
         
       list($submissionsleft, $timeleft)  = remaining_submissions($this->competition, $this->userid);
        if ($submissionsleft <= 0) {
            $errors['submission'] = get_string('nosubmissionsremaining', 'competition');
           return $errors; # No need to continue
        }
    
        if ($submissionerror = validate_submission($this->competition->id, $files['submission'])) {
            $errors['submission'] = $submissionerror;
        }
        
        return $errors;
    }
    
}
