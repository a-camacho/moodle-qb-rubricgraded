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


/**
 * Renderer for outputting parts of a question belonging to the manual
 * graded behaviour.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_rubricgraded_renderer extends qbehaviour_renderer {

    /**
     * Display the manual comment, and a link to edit it, if appropriate.
     *
     * @param question_attempt $qa a question attempt.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment == question_display_options::EDITABLE) {
            // return $this->manual_comment_fields($qa, $options);
            return 'test string output';

        } else if ($options->manualcomment == question_display_options::VISIBLE) {
            // return $this->manual_comment_view($qa, $options);
            return 'test string output';

        } else {
            // return '';
            return 'test string output';
        }
    }

    public function manual_comment_view(question_attempt $qa, question_display_options $options) {
        $output = '';
        if ($qa->has_manual_comment()) {
            $output .= get_string('commentx', 'question', $qa->get_behaviour()->format_comment(null, null, $options->context));
        }
        if ($options->manualcommentlink) {
            $url = new moodle_url($options->manualcommentlink, array('slot' => $qa->get_slot()));
            $link = $this->output->action_link($url, 'asdasdasdasdd',
                new popup_action('click', $url, 'commentquestion',
                    array('width' => 600, 'height' => 800)));
            $output .= html_writer::tag('div', $link, array('class' => 'commentlink'));
        }
        return $output;
    }

}
