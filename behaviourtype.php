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
 * Question behaviour type for manually graded with rubric behaviour.
 *
 * @package    qbehaviour_rubricgraded
 * @copyright  2019 André Camacho
 */

defined('MOODLE_INTERNAL') || die();

class qbehaviour_rubricgraded_type extends question_behaviour_type {
    public function is_archetypal() {
        return true;
    }

    public function get_unused_display_options() {
        return array('correctness', 'marks', 'specificfeedback', 'generalfeedback', 'rightanswer');
    }
}