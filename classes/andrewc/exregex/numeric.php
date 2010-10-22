<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Improves support for number handling to regular expressions. Currently this
 * only implements support for dynamic compilation of numeric range expressions
 * within the regex pattern.
 *
 * ### Numeric range support
 * The normal regex syntax is extended to support testing for numeric values within
 * a given range. For example:
 * Pattern | Matches
 * --------|------------
 * /^#00001-00234#$/ | Any number between 00001 and 00234 inclusive
 * /^myref-(#15-98#)$/ | Any string of the form "myref-##" where ## between 15 and 98. The numeric portion is returned as a capture group
 *
 * This regex can be returned by the [Exregex_Numeric::get_compiled_pattern]
 * method, or for convenience can be directly tested with the [Exregex_Numeric::match]
 * and [Exregex_Numeric::replace] methods which are wrappers for the preg_match
 * and preg_replace functions.
 *
 * [!!] Currently, all numbers must be given as equal length strings left-padded with zeroes if required.
 *
 * ### Configuration Options
 * Configuration is controlled by exregex.php, although key options can also be set
 * with the relevant instance methods.
 * Group           |Parameter  |Type    |Default  |Behaviour
 * ----------------|-----------|--------|---------|----------
 * exregex_numeric | delimiter | string | #       | The character used to start and finish number ranges
 *
 * @package    Exregex
 * @category   Base
 * @author     Andrew Coulton
 * @copyright  (c) 2010 Andrew Coulton
 * @license    http://kohanaframework.org/license
 */
class Andrewc_Exregex_Numeric {

    /**
     * @var string The delimiter character used to top and tail number ranges
     */
    protected $_delimiter = null;

    /**
     * @var string The raw user-supplied pattern
     */
    protected $_raw_pattern = null;

    /**
     * @var string The compiled pattern
     */
    protected $_compiled_pattern = null;

    /**
     * Returns a new instance of Exregex_Numeric based on the supplied pattern
     *
     *     $regex = Exregex_Numeric::factory('/^#0005-3024#$/')
     *                  ->get_compiled_pattern();
     *
     * @param string $pattern
     * @return Exregex_Numeric The instance for chaining
     */
    public static function factory($pattern) {
        return new Exregex_Numeric($pattern);
    }

    /**
     * Creates an instance of Exregex_Numeric
     * ready for use.
     *
     *     $regex = new Exregex_Numeric('/^#2303-5093#$/');
     *
     * @param string $pattern
     * @return Exregex_Numeric
     */
    public function __construct($pattern) {
        $config = Kohana::config('exregex');
        $this->_delimiter = $config->exregex_numeric->delimiter;
        $this->_raw_pattern = $pattern;
    }

    /**
     * Wrapper for [preg_match](http://php.net/manual/en/function.preg-match.php), using the compiled regex
     * @param string $subject The input string.
     * @param array $matches [optional] If provided, then filled with the results of search.
     * @param int $flags [optional]
     * @param int $offset [optional] Byte-position from where to start the search
     * @return int Returns the number of times pattern matches.
     */
    public function match($subject, &$matches=null, $flags=null, $offset=null) {
        $pattern = $this->get_compiled_pattern();
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Wrapper for [preg_replace](http://php.net/manual/en/function.preg-replace.php) using the compiled pattern
     * @param mixed $replacement The string or an array with strings to replace.
     * @param mixed $subject The string or an array with strings to search and replace.
     * @param int $limit [optional] The maximum possible replacements for each pattern in each subject string.
     * @param int $count [optional] If specified, this variable will be filled with the number of replacements done.
     * @return array if the subject is an array
     * @return string if the subject is a string
     */
    public function replace($replacement, $subject, $limit=null, &$count=null) {
        $pattern = $this->get_compiled_pattern();
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Sets a new pattern value
     *
     *     $regex->set_pattern($pattern);
     *
     * @param string $pattern
     * @return Andrewc_Exregex_Numeric The instance for chaining
     */
    public function set_pattern($pattern) {
        $this->_raw_pattern = $pattern;
        $this->_compiled_pattern = null;
        return $this;
    }

    /**
     * Sets a new range delimiter - will reset the compiled pattern - supports fluid interface
     *
     *     $regex->set_delimiter('%');
     *
     * @param string $delimiter
     * @return Andrewc_Exregex_Numeric The instance for chaining
     */
    public function set_delimiter($delimiter){
        $this->_delimiter = $delimiter;
        $this->_compiled_pattern = null;
        return $this;
    }

    /**
     * Returns the compiled pattern, with all numeric ranges replaced with full regexes
     *
     *     $pattern = $regex->get_compiled_pattern();
     *
     * @return string The compiled pattern
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