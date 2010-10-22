<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Improves support for number handling to regular expressions. Currently this
 * only implements support for dynamic compilation of numeric range expressions
 * within the regex pattern.
 *
 * # Numeric range support
 * The normal regex syntax is extended to support testing for numeric values within
 * a given range. For example the pattern /^#00001-00234#$/ will be compiled into a
 * regex that matches any number in the range 00001-00234.
 *
 * This regex can be returned by the [Exregex_Numeric::get_compiled_pattern()]
 * method, or for convenience can be directly tested with the [Exregex_Numeric::match()]
 * and [Exregex_Numeric::replace()] methods which are wrappers for the preg_match
 * and preg_replace functions.
 *
 * [!!] Currently, all numbers must be given as equal length strings left-padded with zeroes.
 *
 * # Configuration Options
 * Configuration is controlled by exregex.php, although key options can also be set
 * with the relevant instance methods.
 * Group           | Parameter | Type   | Default | Behaviour
 * ----------------|-----------|--------|---------|----------
 * exregex_numeric | delimiter | string | #       | The character used to start and finish number ranges
 *
 * @package    Exregex
 * @category   Base
 * @author     Andrew Coulton
 * @copyright  (c) 2010 Andrew Coulton
 * @license    http://kohanaphp.com/license
 */
class Andrewc_Exregex_Numeric {

    /**
     * The delimiter character used to top and tail number ranges
     * @var string
     */
    protected $_delimiter = null;

    /**
     * The raw user-supplied pattern
     * @var string
     */
    protected $_raw_pattern = null;

    /**
     * The compiled pattern
     * @var string
     */
    protected $_compiled_pattern = null;

    /**
     * Returns a new instance of Exregex_Numeric based on the supplied pattern
     * @param string $pattern
     * @return Exregex_Numeric
     */
    public static function factory($pattern) {
        return new Exregex_Numeric($pattern);
    }

    /**
     * Creates an instance of Exregex_Numeric
     * ready for use.
     * @param string $pattern
     * @return Exregex_Numeric
     */
    public function __construct($pattern) {
        $config = Kohana::config('exregex');
        $this->_delimiter = $config->exregex_numeric->delimiter;
        $this->_raw_pattern = $pattern;
    }

    /**
     * Wrapper for preg_match, using the compiled regex
     * @link http://php.net/manual/en/function.preg-match.php
     *
     * @param string $subject The input string.
     * @param array $matches [optional] If provided, then filled with the results of search.
     * @param int $flags [optional]
     * flags can be the following flag:
     * PREG_OFFSET_CAPTURE
     * If this flag is passed, for every occurring match the appendant string
     * offset will also be returned. Note that this changes the value of
     * matches into an array where every element is an
     * array consisting of the matched string at offset 0
     * and its string offset into subject at offset
     * 1.
     * @param int $offset [optional] Byte-position from where to start the search
     * @return int Returns the number of times pattern matches.
     */
    public function match($subject, &$matches=nullarray, $flags=null, $offset=null) {
        $pattern = $this->get_compiled_pattern();
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Wrapper for preg_replace using the compiled pattern
     * @link http://php.net/manual/en/function.preg-replace.php
     * @param mixed $replacement The string or an array with strings to replace.
     * @param mixed $subject The string or an array with strings to search and replace.
     * @param int $limit [optional] The maximum possible replacements for each pattern in each subject string.
     * @param int $count [optional] If specified, this variable will be filled with the number of replacements done.
     * @return mixed returns an array if the subject parameter is an array, or a string otherwise.
     */
    public function replace($replacement, $subject, $limit=null, &$count=null) {
        $pattern = $this->get_compiled_pattern();
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Sets a new pattern value - supports fluid interface
     * @param string $pattern
     * @return Andrewc_Exregex_Numeric
     */
    public function set_pattern($pattern) {
        $this->_raw_pattern = $pattern;
        $this->_compiled_pattern = null;
        return $this;
    }

    /**
     * Sets a new range delimiter - will reset the compiled pattern - supports fluid interface
     * @param string $delimiter
     * @return Andrewc_Exregex_Numeric
     */
    public function set_delimiter($delimiter){
        $this->_delimiter = $delimiter;
        $this->_compiled_pattern = null;
        return $this;
    }

    /**
     * Returns the compiled pattern, with all numeric ranges replaced with full regexes
     * @return string
     */
    public function get_compiled_pattern() {
        if ($this->_compiled_pattern) {
            return $this->_compiled_pattern;
        }

        //compile the pattern
        //get all ranges and compile each
        //then replace them in the pattern
    }

    /**
     * Recursive function that compiles a numeric range into a regex pattern.
     * @param string $from The lower limit of the range
     * @param string $to The upper limit of the range
     * @param string $first_recursion Whether this is the first recursion
     * @return string The compiled pattern
     */
    protected function compile_range($from, $to, $first_recursion=true) {

    }

}