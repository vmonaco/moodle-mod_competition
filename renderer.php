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
 * @package   mod_competition
 * @copyright Vinnie Monaco
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

class mod_competition_renderer extends plugin_renderer_base {

    /**
     * Returns HTML to display competition submissions
     * @param object $options
     * @param int  $coursemoduleid
     * @param bool $vertical
     * @return string
     */
    public function display_submissions($options, $coursemoduleid, $vertical = false) {
        $layoutclass = 'horizontal';
        if ($vertical) {
            $layoutclass = 'vertical';
        }
        $target = new moodle_url('/mod/competition/view.php');
        $attributes = array('method'=>'POST', 'action'=>$target, 'class'=> $layoutclass);

        $html = html_writer::start_tag('form', $attributes);
        $html .= html_writer::start_tag('ul', array('class'=>'competitions' ));

        $availableoption = count($options['options']);
        $competitioncount = 0;
        foreach ($options['options'] as $option) {
            $competitioncount++;
            $html .= html_writer::start_tag('li', array('class'=>'option'));
            $option->attributes->name = 'answer';
            $option->attributes->type = 'radio';
            $option->attributes->id = 'competition_'.$competitioncount;

            $labeltext = $option->text;
            if (!empty($option->attributes->disabled)) {
                $labeltext .= ' ' . get_string('full', 'competition');
                $availableoption--;
            }

            $html .= html_writer::empty_tag('input', (array)$option->attributes);
            $html .= html_writer::tag('label', $labeltext, array('for'=>$option->attributes->id));
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::tag('li','', array('class'=>'clearfloat'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::tag('div', '', array('class'=>'clearfloat'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$coursemoduleid));

        if (!empty($options['hascapability']) && ($options['hascapability'])) {
            if ($availableoption < 1) {
               $html .= html_writer::tag('label', get_string('competitionfull', 'competition'));
            } else {
                $html .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('savemycompetition','competition'), 'class'=>'button'));
            }

            if (!empty($options['allowupdate']) && ($options['allowupdate'])) {
                $url = new moodle_url('view.php', array('id'=>$coursemoduleid, 'action'=>'delcompetition', 'sesskey'=>sesskey()));
                $html .= html_writer::link($url, get_string('removemycompetition','competition'));
            }
        } else {
            $html .= html_writer::tag('label', get_string('havetologin', 'competition'));
        }

        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Returns HTML to display competitions result
     * @param object $competitions
     * @param bool $forcepublish
     * @return string
     */
    public function display_leaderboard($leaderboard, $forcepublish = false) {
        global $PAGE;

        if (empty($forcepublish)) { //allow the publish setting to be overridden
            $forcepublish = $leaderboard->publish;
        }

        $html ='';
        $html .= html_writer::tag('h2',format_string(get_string("responses", "competition")), array('class'=>'main'));

        $attributes = array('method'=>'POST');
        $attributes['action'] = new moodle_url($PAGE->url);
        $attributes['id'] = 'attemptsform';

        if ($competitions->viewresponsecapability) {
            $html .= html_writer::start_tag('form', $attributes);
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=> $competitions->coursemoduleid));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=> sesskey()));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'mode', 'value'=>'overview'));
        }

        $table = new html_table();
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results names ';
        $table->tablealign = 'center';
        $table->summary = get_string('responsesto', 'competition', format_string($competitions->name));
        $table->data = array();

        $count = 0;
        ksort($leaderboard->users);

        $columns = array();
        $celldefault = new html_table_cell();
        $celldefault->attributes['class'] = 'data';

        $rankheader = clone($celldefault);
        $rankheader->header = true;
        $rankheader->attributes['class'] = 'header data';
        $rankheader->text = get_string('rank', 'competition');
        $columns['usernumber'][] = $rankheader;

        $table->head = $columns['options'];
        $table->data[] = new html_table_row($columns['usernumber']);

        foreach ($leaderboard->users as $rank => $user) {
            $cell = new html_table_cell();
            $cell->attributes['class'] = 'data';

            if ($competitions->showunanswered || $optionid > 0) {
                if (!empty($options->user)) {
                    $optionusers = '';
                    foreach ($options->user as $user) {
                        $data = '';
                        if (empty($user->imagealt)){
                            $user->imagealt = '';
                        }

                        $userfullname = fullname($user, $competitions->fullnamecapability);
                        if ($competitions->viewresponsecapability && $competitions->deleterepsonsecapability  && $optionid > 0) {
                            $attemptaction = html_writer::label($userfullname, 'attempt-user'.$user->id, false, array('class' => 'accesshide'));
                            $attemptaction .= html_writer::checkbox('attemptid[]', $user->id,'', null, array('id' => 'attempt-user'.$user->id));
                            $data .= html_writer::tag('div', $attemptaction, array('class'=>'attemptaction'));
                        }
                        $userimage = $this->output->user_picture($user, array('courseid'=>$competitions->courseid));
                        $data .= html_writer::tag('div', $userimage, array('class'=>'image'));

                        $userlink = new moodle_url('/user/view.php', array('id'=>$user->id,'course'=>$competitions->courseid));
                        $name = html_writer::tag('a', $userfullname, array('href'=>$userlink, 'class'=>'username'));
                        $data .= html_writer::tag('div', $name, array('class'=>'fullname'));
                        $data .= html_writer::tag('div','', array('class'=>'clearfloat'));
                        $optionusers .= html_writer::tag('div', $data, array('class'=>'user'));
                    }
                    $cell->text = $optionusers;
                }
            }
            $columns[] = $cell;
            $count++;
        }
        $row = new html_table_row($columns);
        $table->data[] = $row;

        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        $actiondata = '';
        if ($competitions->viewresponsecapability && $competitions->deleterepsonsecapability) {
            $selecturl = new moodle_url('#');

            $selectallactions = new component_action('click',"checkall");
            $selectall = new action_link($selecturl, get_string('selectall'), $selectallactions);
            $actiondata .= $this->output->render($selectall) . ' / ';

            $deselectallactions = new component_action('click',"checknone");
            $deselectall = new action_link($selecturl, get_string('deselectall'), $deselectallactions);
            $actiondata .= $this->output->render($deselectall);

            $actiondata .= html_writer::tag('label', ' ' . get_string('withselected', 'competition') . ' ', array('for'=>'menuaction'));

            $actionurl = new moodle_url($PAGE->url, array('sesskey'=>sesskey(), 'action'=>'delete_confirmation()'));
            $select = new single_select($actionurl, 'action', array('delete'=>get_string('delete')), null, array(''=>get_string('chooseaction', 'competition')), 'attemptsform');

            $actiondata .= $this->output->render($select);
        }
        $html .= html_writer::tag('div', $actiondata, array('class'=>'responseaction'));

        if ($competitions->viewresponsecapability) {
            $html .= html_writer::end_tag('form');
        }

        return $html;
    }


    /**
     * Returns HTML to display competitions result
     * @param object $competitions
     * @return string
     */
    public function display_publish_anonymous_vertical($competitions) {
        global $competition_COLUMN_HEIGHT;

        $html = '';
        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->summary = get_string('responsesto', 'competition', format_string($competitions->name));
        $table->data = array();

        $count = 0;
        ksort($competitions->options);
        $columns = array();
        $rows = array();

        $headercelldefault = new html_table_cell();
        $headercelldefault->scope = 'row';
        $headercelldefault->header = true;
        $headercelldefault->attributes = array('class'=>'header data');

        // column header
        $tableheader = clone($headercelldefault);
        $tableheader->text = html_writer::tag('div', get_string('competitionoptions', 'competition'), array('class' => 'accesshide'));
        $rows['header'][] = $tableheader;

        // graph row header
        $graphheader = clone($headercelldefault);
        $graphheader->text = html_writer::tag('div', get_string('responsesresultgraphheader', 'competition'), array('class' => 'accesshide'));
        $rows['graph'][] = $graphheader;

        // user number row header
        $usernumberheader = clone($headercelldefault);
        $usernumberheader->text = get_string('numberofuser', 'competition');
        $rows['usernumber'][] = $usernumberheader;

        // user percentage row header
        $userpercentageheader = clone($headercelldefault);
        $userpercentageheader->text = get_string('numberofuserinpercentage', 'competition');
        $rows['userpercentage'][] = $userpercentageheader;

        $contentcelldefault = new html_table_cell();
        $contentcelldefault->attributes = array('class'=>'data');

        foreach ($competitions->options as $optionid => $option) {
            // calculate display length
            $height = $percentageamount = $numberofuser = 0;
            $usernumber = $userpercentage = '';

            if (!empty($option->user)) {
               $numberofuser = count($option->user);
            }

            if($competitions->numberofuser > 0) {
               $height = ($competition_COLUMN_HEIGHT * ((float)$numberofuser / (float)$competitions->numberofuser));
               $percentageamount = ((float)$numberofuser/(float)$competitions->numberofuser)*100.0;
            }

            $displaygraph = html_writer::tag('img','', array('style'=>'height:'.$height.'px;width:49px;', 'alt'=>'', 'src'=>$this->output->pix_url('column', 'competition')));

            // header
            $headercell = clone($contentcelldefault);
            $headercell->text = $option->text;
            $rows['header'][] = $headercell;

            // Graph
            $graphcell = clone($contentcelldefault);
            $graphcell->attributes = array('class'=>'graph vertical data');
            $graphcell->text = $displaygraph;
            $rows['graph'][] = $graphcell;

            $usernumber .= html_writer::tag('div', ' '.$numberofuser.'', array('class'=>'numberofuser', 'title'=> get_string('numberofuser', 'competition')));
            $userpercentage .= html_writer::tag('div', format_float($percentageamount,1). '%', array('class'=>'percentage'));

            // number of user
            $usernumbercell = clone($contentcelldefault);
            $usernumbercell->text = $usernumber;
            $rows['usernumber'][] = $usernumbercell;

            // percentage of user
            $numbercell = clone($contentcelldefault);
            $numbercell->text = $userpercentage;
            $rows['userpercentage'][] = $numbercell;
        }

        $table->head = $rows['header'];
        $trgraph = new html_table_row($rows['graph']);
        $trusernumber = new html_table_row($rows['usernumber']);
        $truserpercentage = new html_table_row($rows['userpercentage']);
        $table->data = array($trgraph, $trusernumber, $truserpercentage);

        $header = html_writer::tag('h2',format_string(get_string("responses", "competition")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::tag('a', get_string('skipresultgraph', 'competition'), array('href'=>'#skipresultgraph', 'class'=>'skip-block'));
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        return $html;
    }
}

