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
 * Defines the renderer for the manual graded with rubric behaviour.
 *
 * @package    qbehaviour
 * @subpackage rubricgraded
 * @copyright  2019 André Camacho
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/grading/form/rubric/renderer.php');
require_once($CFG->dirroot . '/grade/grading/form/rubric/lib.php');

/**
 * Renderer for outputting parts of a question belonging to the manual
 * graded behaviour.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_rubricgraded_renderer extends qbehaviour_renderer {

    /**
     * Generate some HTML (which may be blank) that appears in the outcome area,
     * after the question-type generated output.
     *
     * For example, the CBM models use this to display an explanation of the score
     * adjustment that was made based on the certainty selected.
     *
     * @param question_attempt $qa a question attempt.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     ***/
    public function feedback(question_attempt $qa, question_display_options $options) {
        return '';
    }

    public function manual_comment_fields(question_attempt $qa, question_display_options $options) {
        global $CFG, $PAGE;

        require_once($CFG->dirroot.'/lib/filelib.php');
        require_once($CFG->dirroot.'/repository/lib.php');

        $elementname = $qa->get_field_prefix();
        $elementname = $elementname . "-rubric";

        $inputname = $qa->get_behaviour_field_name('comment');
        $id = $inputname . '_id';
        list($commenttext, $commentformat, $commentstep) = $qa->get_current_manual_comment();

        $editor = editors_get_preferred_editor($commentformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }

        $draftitemareainputname = $qa->get_behaviour_field_name('comment:itemid');
        $draftitemid = optional_param($draftitemareainputname, false, PARAM_INT);

        if (!$draftitemid && $commentstep === null) {
            $commenttext = '';
            $draftitemid = file_get_unused_draft_itemid();
        } else if (!$draftitemid) {
            list($draftitemid, $commenttext) = $commentstep->prepare_response_files_draft_itemid_with_text(
                'bf_comment', $options->context->id, $commenttext);
        }

        $editor->set_text($commenttext);
        $editor->use_editor($id, question_utils::get_editor_options($options->context),
            question_utils::get_filepicker_options($options->context, $draftitemid));

        $commenteditor = html_writer::tag('div', html_writer::tag('textarea', s($commenttext),
            array('id' => $id, 'name' => $inputname, 'rows' => 10, 'cols' => 60)));

        $attributes = ['type'  => 'hidden', 'name'  => $draftitemareainputname, 'value' => $draftitemid];
        $commenteditor .= html_writer::empty_tag('input', $attributes);

        $editorformat = '';
        if (count($formats) == 1) {
            reset($formats);
            $editorformat .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => $inputname . 'format', 'value' => key($formats)));
        } else {
            $editorformat = html_writer::start_tag('div', array('class' => 'fitem'));
            $editorformat .= html_writer::start_tag('div', array('class' => 'fitemtitle'));
            $editorformat .= html_writer::tag('label', get_string('format'), array('for'=>'menu'.$inputname.'format'));
            $editorformat .= html_writer::end_tag('div');
            $editorformat .= html_writer::start_tag('div', array('class' => 'felement fhtmleditor'));
            $editorformat .= html_writer::select($formats, $inputname.'format', $commentformat, '');
            $editorformat .= html_writer::end_tag('div');
            $editorformat .= html_writer::end_tag('div');
        }

        $comment = html_writer::tag('div', html_writer::tag('div',
                html_writer::tag('label', get_string('comment', 'question'),
                    array('for' => $id)), array('class' => 'fitemtitle')) .
            html_writer::tag('div', $commenteditor, array('class' => 'felement fhtmleditor', 'data-fieldtype' => "editor")),
            array('class' => 'fitem'));
        $comment .= $editorformat;

        $mark = '';
        if ($qa->get_max_mark()) {
            $currentmark = $qa->get_current_manual_mark();
            $maxmark = $qa->get_max_mark();

            $fieldsize = strlen($qa->format_max_mark($options->markdp)) - 1;
            $markfield = $qa->get_behaviour_field_name('mark');

            $attributes = array(
                'type' => 'text',
                'size' => $fieldsize,
                'name' => $markfield,
                'id'=> $markfield
            );
            if (!is_null($currentmark)) {
                $attributes['value'] = $currentmark;
            }

            $markrange = html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $qa->get_behaviour_field_name('maxmark'),
                    'value' => $maxmark,
                )) . html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $qa->get_control_field_name('minfraction'),
                    'value' => $qa->get_min_fraction(),
                )) . html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $qa->get_control_field_name('maxfraction'),
                    'value' => $qa->get_max_fraction(),
                ));

            $error = $qa->validate_manual_mark($currentmark);
            $errorclass = '';
            if ($error !== '') {
                $erroclass = ' error';
                $error = html_writer::tag('span', $error,
                        array('class' => 'error')) . html_writer::empty_tag('br');
            }

            $a = new stdClass();
            $a->max = $qa->format_max_mark($options->markdp);
            $a->mark = html_writer::empty_tag('input', $attributes);
            $mark = html_writer::tag('div', html_writer::tag('div',
                    html_writer::tag('label', get_string('mark', 'question'),
                        array('for' => $markfield),
                    array('class' => 'fitemtitle')) .
                html_writer::tag('div', $error . get_string('xoutofmax', 'question', $a) .
                    $markrange, array('class' => 'felement ftext' . $errorclass, 'id' => 'totalMark'))
                ), array('class' => 'fitem'));
        }

        $prefix = html_writer::tag('div', html_writer::tag('div',
            html_writer::tag('label',
                get_string('rubric_with_mode', 'qbehaviour_rubricgraded', 'grading')
            )
        ));

        // Get rubric definition
        $rubric_id = intval($qa->get_question()->rubricid);
        $definition = $this->load_definition_from_id($rubric_id);
        $rubric_options = json_decode($definition->options, 'true');
        $criteria = $definition->rubric_criteria;

        // Require JS for calculating total score
        $maximum_mark = ($qa->get_max_mark() ? $qa->get_max_mark() : null );

        $max_points = 0;
        foreach ( $definition->rubric_criteria as $my_criteria ) {
            $max_points = $max_points + end($my_criteria['levels'])['score'];
        }

        $PAGE->requires->js_call_amd('qbehaviour_rubricgraded/main', 'init', array( $maximum_mark, $elementname, $max_points ) );

        $mode = 4;
        $values = array();
        $values['criteria'] = array();

        // Get rubric renderer
        $rubric_renderer = new gradingform_rubric_renderer($PAGE, '');

            // Get rubric filling data if exist
            $last_step = $qa->get_sequence_check_count()-1;
            $step = $qa->get_step($last_step);
            $filling_input = $step->get_behaviour_var('rubfilling');
            $filling_array = $this->get_rubric_filling_array($values, $filling_input);

            if ( !empty($filling_array) ) {
                $values = $filling_array;
            }

        /*
        $total_score = html_writer::tag('label', 'Total points : ' . html_writer::tag('span', '0', array('class' => 'total_points', 'id' => 'totalPoints') ) );
        $total_score_decimal = html_writer::tag('label', 'Total score (max ' . html_writer::tag('span', $maximum_mark, array('class' => 'maximum_mark', 'id' => 'maximumMark') ) . ') : ' . html_writer::tag('span', '0', array('class' => 'total_score_decimal', 'id' => 'totalScoreDecimal') ) );
        */

        // Debug information
        /*
        echo '<b>Question id = </b>' . $qa->get_question()->id . '<br />';
        echo '<b>Question attempt id = </b>' . 'X' . '<br />';
        echo '<b>Question usage id = </b>' . $qa->get_usage_id() . ' (propre à chaque utilisateur)<br />';
        echo '<b>Number of steps for this attempt = </b>' . $qa->get_num_steps() . '<br /><br />';

        echo '<b>Rubric id = </b>' . $rubric_id . '<br />';

        echo '<b>Criterions(levels) used = </b>';
        if ( !$filling_input ) {
            echo 'No filling found' ;
        } else {
            echo $filling_input;
        }

        echo '<br /><br />';
        */

        $rubric_editor = $rubric_renderer->display_rubric($criteria, $rubric_options, $mode, $elementname, $values);

        $rubric_editor .= html_writer::empty_tag('input', array( 'class' => 'hidden', "type" => "text", "id" => $qa->get_field_prefix() . "-rubfilling", "name" => $qa->get_field_prefix() . "-rubfilling", "value" => $filling_input ) );

        $fieldset = html_writer::tag('fieldset', html_writer::tag('div', $comment . $mark,
            array('class' => 'fcontainer clearfix')), array('class' => 'hidden'));

        return  $prefix . $rubric_editor . $total_score . html_writer::empty_tag('br') . $total_score_decimal .
            html_writer::empty_tag('br') . html_writer::empty_tag('br') . $fieldset;

    }

    /**
     * Display the manual comment, and a link to edit it, if appropriate.
     *
     * @param question_attempt $qa a question attempt.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function manual_comment(question_attempt $qa, question_display_options $options) {
        global $PAGE;

        // ********************************** //

        // Get rubric definition
        $rubric_id = intval($qa->get_question()->rubricid);
        $definition = $this->load_definition_from_id($rubric_id);

        // Get rubric renderer
        $rubric_renderer = new gradingform_rubric_renderer($PAGE, '');
        $criteria = $definition->rubric_criteria;

        // Create elementname
        $elementname = $qa->get_field_prefix();
        $elementname = $elementname . "-rubric";

        // Get rubric parameters
        $rubric_options = json_decode($definition->options, 'true');
        $values = array();
        $values['criteria'] = array();

        // ********************************** //

        if ($options->manualcomment == question_display_options::EDITABLE) {

            // Do not do anything as rubric is already included here
            $output = $this->manual_comment_fields($qa, $options);
            return $output;

        } else if ($options->manualcomment == question_display_options::VISIBLE) {

            // ********************************** //

            $mode = 6;

            // Get rubric filling data if exist
            $last_step = $qa->get_sequence_check_count()-1;
            $step = $qa->get_step($last_step);
            $filling_input = $step->get_behaviour_var('rubfilling');
            $filling_array = $this->get_rubric_filling_array($values, $filling_input);

            if ( !empty($filling_array) ) {
                $values = $filling_array;
            }

            $rubric_editor = $rubric_renderer->display_rubric($criteria, $rubric_options, $mode, $elementname, $values);
            $rubric_editor .= html_writer::empty_tag('input', array( 'class' => 'hidden', "type" => "text", "id" => $qa->get_field_prefix() . "-rubfilling", "name" => $qa->get_field_prefix() . "-rubfilling", "value" => $filling_input ) );

            // ********************************** //

            $output = '';

            $content = get_string('rubric_with_mode', 'qbehaviour_rubricgraded', 'review');
            $content .= $rubric_editor;
            $output .= $this->manual_comment_view($qa, $options);
            $output .= '</div>';
            $output .= '<div>';
            $output .= html_writer::div(html_writer::tag('h4', "Rubric", array( 'class' => 'accesshide' ) ) . $content, 'comment clearfix');

            return $output;

        } else {

            $output = '';
            $content = '';

            // Check if option to "alwayshowrubric" is enabled (1) or disabled (null)

            if ( $rubric_options['alwaysshowdefinition'] == '1' ) {

                $mode = 8;

                // Get rubric filling data if exist
                $last_step = $qa->get_sequence_check_count()-1;
                $step = $qa->get_step($last_step);
                $filling_input = $step->get_behaviour_var('rubfilling');
                $filling_array = $this->get_rubric_filling_array($values, $filling_input);

                if ( !empty($filling_array) ) {
                    $values = $filling_array;
                }

                $rubric_editor = $rubric_renderer->display_rubric($criteria, $rubric_options, $mode, $elementname, $values);

                $content .= get_string('rubric_with_mode', 'qbehaviour_rubricgraded', 'review, no manual comment');
                $content .= $rubric_editor;

                $output .= html_writer::tag('h4', "Rubric", array( 'class' => 'accesshide' ) ) . $content;

            }

            return $output;

        }
    }

    public function manual_comment_view(question_attempt $qa, question_display_options $options) {
        $output = '';
        if ($qa->has_manual_comment()) {
            $output .= get_string('commentx', 'question', $qa->get_behaviour()->format_comment(null, null, $options->context));
        }
        if ($options->manualcommentlink) {
            $url = new moodle_url($options->manualcommentlink, array('slot' => $qa->get_slot()));
            $link = $this->output->action_link($url, get_string('commentormark', 'question'),
                new popup_action('click', $url, 'commentquestion',
                    array('width' => 600, 'height' => 800)));
            $output .= html_writer::tag('div', $link, array('class' => 'commentlink'));
        }
        return $output;
    }

    /**
     * Loads the rubric form definition if it exists
     *
     * There is a new array called 'rubric_criteria' appended to the list of parent's definition properties.
     *
     * @param $id
     * @return bool|stdClass
     * @throws dml_exception
     */
    protected function load_definition_from_id($id) {
        global $DB;
        $sql = "SELECT gd.*,
                       rc.id AS rcid, rc.sortorder AS rcsortorder, rc.description AS rcdescription, rc.descriptionformat AS rcdescriptionformat,
                       rl.id AS rlid, rl.score AS rlscore, rl.definition AS rldefinition, rl.definitionformat AS rldefinitionformat
                  FROM {grading_definitions} gd
             LEFT JOIN {gradingform_rubric_criteria} rc ON (rc.definitionid = :rubricid)
             LEFT JOIN {gradingform_rubric_levels} rl ON (rl.criterionid = rc.id)
                 WHERE gd.id = :rubricid_bis AND gd.method = :method
              ORDER BY rc.sortorder,rl.score";
        $params = array('rubricid' => $id, 'rubricid_bis' => $id, 'method' => 'rubric');

        $rs = $DB->get_recordset_sql($sql, $params);

        $this->definition = false;
        foreach ($rs as $record) {
            // pick the common definition data
            if ($this->definition === false) {
                $this->definition = new stdClass();
                foreach (array('id', 'name', 'description', 'descriptionformat', 'status', 'copiedfromid',
                             'timecreated', 'usercreated', 'timemodified', 'usermodified', 'timecopied', 'options') as $fieldname) {
                    $this->definition->$fieldname = $record->$fieldname;
                }
                $this->definition->rubric_criteria = array();
            }
            // pick the criterion data
            if (!empty($record->rcid) and empty($this->definition->rubric_criteria[$record->rcid])) {
                foreach (array('id', 'sortorder', 'description', 'descriptionformat') as $fieldname) {
                    $this->definition->rubric_criteria[$record->rcid][$fieldname] = $record->{'rc'.$fieldname};
                }
                $this->definition->rubric_criteria[$record->rcid]['levels'] = array();
            }
            // pick the level data
            if (!empty($record->rlid)) {
                foreach (array('id', 'score', 'definition', 'definitionformat') as $fieldname) {
                    $value = $record->{'rl'.$fieldname};
                    if ($fieldname == 'score') {
                        $value = (float)$value; // To prevent display like 1.00000
                    }
                    $this->definition->rubric_criteria[$record->rcid]['levels'][$record->rlid][$fieldname] = $value;
                }
            }
        }
        $rs->close();
        $options = $this->get_default_options();
        if (!$options['sortlevelsasc']) {
            foreach (array_keys($this->definition->rubric_criteria) as $rcid) {
                $this->definition->rubric_criteria[$rcid]['levels'] = array_reverse($this->definition->rubric_criteria[$rcid]['levels'], true);
            }
        }

        return($this->definition);
    }

    /**
     * Returns the default options for the rubric display
     *
     * @return array
     */
    public static function get_default_options() {
        $options = array(
            'sortlevelsasc' => 1,
            'lockzeropoints' => 1,
            'alwaysshowdefinition' => 1,
            'showdescriptionteacher' => 1,
            'showdescriptionstudent' => 1,
            'showscoreteacher' => 1,
            'showscorestudent' => 1,
            'enableremarks' => 1,
            'showremarksstudent' => 1
        );
        return $options;
    }

    /**
     * Returns the well formated array from a JSON string regarding rubric filling
     *
     * @return array
     */
    public function get_rubric_filling_array( $values, $filling_input ) {
        $filling_array = json_decode($filling_input);
        if ( $filling_array ) {
            foreach ( $filling_array as $criterion ) {
                $remark = $criterion->remark ? $criterion->remark : "";
                $values['criteria'][$criterion->criterion] = array('id' => $criterion->level, 'criterionid' => $criterion->criterion, 'levelid' => $criterion->level, 'savedlevelid' => $criterion->level, 'remark' => $remark );
            }
        }
        return($values);
    }

}