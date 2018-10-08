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
 * A class for efficiently finds questions at random from the question bank.
 *
 * @package   core_question
 * @copyright 2016 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\bank;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/classes/bank/random_question_loader.php');
/**
 * This class efficiently finds questions at random from the question bank.
 *
 * You can ask for questions at random one at a time. Each time you ask, you
 * pass a category id, and whether to pick from that category and all subcategories
 * or just that category.
 *
 * The number of teams each question has been used is tracked, and we will always
 * return a question from among those elegible that has been used the fewest times.
 * So, if there are questions that have not been used yet in the category asked for,
 * one of those will be returned. However, within one instantiation of this class,
 * we will never return a given question more than once, and we will never return
 * questions passed into the constructor as $usedquestions.
 *
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class randomconstrained_question_loader extends random_question_loader {

    // A local cache caching the whole usable category tree for the root category question space.
    protected $usablecategoriescache;

    /**
     * Constructor.
     * @param \qubaid_condition $qubaids the usages to consider when counting previous uses of each question.
     * @param array $usedquestions questionid => number of times used count. If we should allow for
     *      further existing uses of a question in addition to the ones in $qubaids.
     */
    public function __construct(\qubaid_condition $qubaids, $constraints, array $usedquestions = array()) {

        parent::__construct($qubaids, $usedquestions);

        $this->constraints = $constraints;
    }

    public function get_next_constrained_question_id($rootcategoryid) {
        return $this->get_next_question_id($rootcategoryid, true);
    }

    /**
     * changes the way categories are examinated, checks constraints and load
     * category trees that belong to overal allowed question space for the calling quiz
     * AND constraints declared in the current attempt.
     *
     * Populate {@link $availablequestionscache} for this combination of options.
     * @param int $categoryid the id of a category in the question bank.
     * @param bool $includesubcategories wether to pick a question from exactly
     *      that category, or that category and subcategories.
     */
<<<<<<< HEAD
    protected function ensure_questions_for_category_loaded($categoryid, $includesubcategories) {
        global $DB;

=======
    protected function ensure_questions_for_category_loaded($categoryid, $includesubcategories, $tags = []) {
        global $DB;
>>>>>>> MOODLE_35_STABLE
        $categorykey = $this->get_category_key($categoryid, $includesubcategories);

        if (isset($this->availablequestionscache[$categorykey])) {
            // Data is already in the cache, nothing to do.
            return;
        }

        // CHANGE+.
        // Load the available questions from the question bank.
        if (!isset($this->usablecategoriescache[$categorykey])) {
            if ($includesubcategories) {
                $this->usablecategoriescache[$categorykey] = question_categorylist($categoryid);
            } else {
                $this->usablecategoriescache[$categorykey] = array($categoryid);
            }
        }

        $categoryids = array();
        // Filter categoryids againts constraints.
        foreach ($this->constraints as $cid) {
            if (!in_array($cid, $this->usablecategoriescache[$categorykey])) {
                // Checking the constraint is in the quiz focus question space. Discard if not.
                continue;
            }

            if (!in_array($cid, array_keys($categoryids))) {
                // Checking we do not yet get it by subcategory. Aggregate if not.
                $categoryids = array_merge($categoryids, question_categorylist($cid));
            }
        }
        // CHANGE-.

        list($extraconditions, $extraparams) = $DB->get_in_or_equal($this->excludedqtypes,
                SQL_PARAMS_NAMED, 'excludedqtype', false);

        $questionidsandcounts = \question_bank::get_finder()->get_questions_from_categories_with_usage_counts(
                $categoryids, $this->qubaids, 'q.qtype ' . $extraconditions, $extraparams);
        if (!$questionidsandcounts) {
            // No questions in this category.
            $this->availablequestionscache[$categorykey] = array();
            return;
        }
        // Put all the questions with each value of $prevusecount in separate arrays.
        $idsbyusecount = array();
        foreach ($questionidsandcounts as $questionid => $prevusecount) {
            if (isset($this->recentlyusedquestions[$questionid])) {
                // Recently used questions are never returned.
                continue;
            }
            $idsbyusecount[$prevusecount][] = $questionid;
        }

        /*
         * Now put that data into our cache. For each count, we need to shuffle
         * questionids, and make those the keys of an array.
         */
        $this->availablequestionscache[$categorykey] = array();
        foreach ($idsbyusecount as $prevusecount => $questionids) {
            shuffle($questionids);
            $this->availablequestionscache[$categorykey][$prevusecount] = array_combine(
                    $questionids, array_fill(0, count($questionids), 1));
        }
        ksort($this->availablequestionscache[$categorykey]);
    }

}
