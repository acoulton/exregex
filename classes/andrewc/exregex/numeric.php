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
 * [!!] Currently the regex is not optimised for size of range.
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
        $this->_delimiter = $config['exregex_numeric']['delimiter'];
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
    public function set_delimiter($delimiter) {
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
        $range_pattern = "/" . $this->_delimiter . "([0-9]+-[0-9]+)" . $this->_delimiter . "/";
        if (preg_match_all($range_pattern, $this->_raw_pattern, $matchesarray, PREG_SET_ORDER)) {
            //get all ranges, compile each and replace in the pattern
            foreach ($matchesarray as $match_range) {
                list($from, $to) = explode('-', $match_range[1]);
                $this->_compiled_pattern = str_replace($match_range[0],
                                $this->compile_range($from, $to),
                                $this->_raw_pattern);
            }
        }
        return $this->_compiled_pattern;
    }

    /**
     * Function that compiles a numeric range into a regex pattern.
     * @param string $range_from The lower limit of the range
     * @param string $range_to The upper limit of the range     
     * @return string
     */
    protected function compile_range($range_from, $range_to) {
        $number_len = max(array(strlen($range_from),
                    strlen($range_to)));

        //pad with leading zeroes
        $range_from = str_pad($range_from, $number_len, "0", STR_PAD_LEFT);
        $range_to = str_pad($range_to, $number_len, "0", STR_PAD_LEFT);

        /*
         * The range must be broken into constituent [0-9]{x} ranges, with a
         * [x-9] range at the beginning and a [0-x] range at the end.
         *
         * Begin by working up orders of magnitude to find all the 9-endings less
         * than the range end target.
         *
         * 1187-3596 will give ranges:
         *  - 1187 - 1189
         *  - 1190 - 1199
         *  - 1200 - 1999
         */

        $sub_from = $range_from;
        for ($i = 1; $range_to >= pow(10, $i); $i++) {
            $oom = pow(10, $i);
            $rounded = $oom * ceil($range_from / $oom);
            if (($rounded - 1) > $range_to) {
                break;
            }
            $ranges[] = array($sub_from, $rounded - 1);
            $sub_from = $rounded;
        }

        /*
         * Now process the inverse, working from the target number and slowly
         * rounding each order of magnitude off to zero.
         *
         * 1187 - 3596 will give ranges:
         *  - 3590 - 3596
         *  - 3500 - 3589
         *  - 3000 - 3499
         */
        $sub_to = $range_to;
        for ($i = 1; $range_from >= pow(10, $i); $i++) {
            $oom = pow(10, $i);
            $rounded = $oom * floor($range_to / $oom);
            if ($rounded < $range_from) {
                break;
            }
            $ranges[] = array($rounded, $sub_to);
            $sub_to = $rounded - 1;
        }

        /*
         * There may be a range between the worked-up-9 and worked-down-0 ranges
         * in the case of 1187 - 3596, 2000 - 2999. Process this.
         */
        if ($sub_from <= $sub_to) {
            $ranges[] = array($sub_from, $sub_to);
        }

        /*
         * Assemble the range matches
         */
        foreach ($ranges as $range) {
            list ($from, $to) = $range;
            $from = str_pad($from, $number_len, "0", STR_PAD_LEFT);
            $to = str_pad($to, $number_len, "0", STR_PAD_LEFT);

            $pattern = "";
            for ($i = 0; $i < $number_len; $i++) {
                if ($from[$i] == $to[$i]) {
                    $pattern .= $from[$i];
                } else {
                    $pattern .= "[" . $from[$i] . "-" . $to[$i] . "]";
                }
            }
            $patterns[] = $pattern;
        }

        /*
         * Return the compiled alternations as a capture group
         */
        return "(" . implode("|", $patterns) . ")";
    }

}