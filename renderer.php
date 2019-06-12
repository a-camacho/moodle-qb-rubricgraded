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
 * Defines the renderer for the manual graded behaviour.
 *
 * @package    qbehaviour
 * @subpackage rubricgraded
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
                        array('for' => $markfield)),
                    array('class' => 'fitemtitle')) .
                html_writer::tag('div', $error . get_string('xoutofmax', 'question', $a) .
                    $markrange, array('class' => 'felement ftext' . $errorclass)
                ), array('class' => 'fitem'));
        }

        /* TODO: Change hard-coded strings to language based strings */

        $prefix =   html_writer::tag('div', html_writer::tag('div',
                html_writer::tag('label', 'Rubrics') .
                        html_writer::tag('div', '<i>Here will come the rubrics grading strategy</i>')));

        /* TODO : Find if there is a cleaner (more up) way to show the rubrics */
        $cmid = $PAGE->cm->id;

        $rubric_renderer = new gradingform_rubric_renderer($PAGE, '');
            $criteria = array(
                            array(  'id' => '1',
                                    'sortorder' => '1',
                                    'description' => 'Criterion1',
                                    'descriptionformat' => '0',
                                    'levels' => array(
                                        array( 'id' => 1, 'score' => floatval(0), 'definition' => 'Lev1', 'definitionformat' => '0'),
                                        array( 'id' => 2, 'score' => floatval(1), 'definition' => 'Lev2', 'definitionformat' => '0'),
                                        array( 'id' => 3, 'score' => floatval(2), 'definition' => 'Lev3', 'definitionformat' => '0'),
                                    )
                            ),
                            array(  'id' => '2',
                                    'sortorder' => '2',
                                    'description' => 'Criterion2',
                                    'descriptionformat' => '0',
                                    'levels' => array(
                                        array( 'id' => 4, 'score' => floatval(0), 'definition' => 'Lav1', 'definitionformat' => '0'),
                                        array( 'id' => 5, 'score' => floatval(1), 'definition' => 'Lav2', 'definitionformat' => '0'),
                                        array( 'id' => 6, 'score' => floatval(2), 'definition' => 'Lav3', 'definitionformat' => '0'),
                                    )
                            )
                        );
            $options = array(   'sortlevelsasc' => '1',
                                'lockzeropoints' => '1',
                                'alwaysshowdefinition' => '1',
                                'showdescriptionteacher' => '1',
                                'showdescriptionstudent' => '1',
                                'showscoreteacher' => '1',
                                'showscorestudent' => '1',
                                'enableremarks' => '1',
                                'showremarksstudent' => '1',
                            );
            $mode = 4;
            $elementname = 'mycustomname';
            $values = null;

        $rubric_editor = $rubric_renderer->display_rubric($criteria, $options, $mode, $elementname, $values);

        return $prefix . $rubric_editor . html_writer::tag('fieldset', html_writer::tag('div', $comment . $mark,
            array('class' => 'fcontainer clearfix')), array('class' => 'hidden'));

    }

    /* Visiblement ça apparaît dans une prévisualisation de la question */

    /**
     * Display the manual comment, and a link to edit it, if appropriate.
     *
     * @param question_attempt $qa a question attempt.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment == question_display_options::EDITABLE) {
            return $this->manual_comment_fields($qa, $options);
            // return 'shows when manual_comment is editable';

        } else if ($options->manualcomment == question_display_options::VISIBLE) {
            return $this->manual_comment_view($qa, $options);
            // return 'shows when we can view manual comment but not edit it';

        } else {
            // return '';
            return 'show nothing if we can\'t VIEW or EDIT manual comment';
        }
    }

    /* Visiblement ça apparaît dans une prévisualisation de la question */

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

}
