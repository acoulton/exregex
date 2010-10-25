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
        print_r("<pre>");
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
        print_r("</pre>");
        print_r("<p><em>$this->_compiled_pattern<em></p>");
        return $this->_compiled_pattern;
    }

    /**
     * Recursive function that compiles a numeric range into a regex pattern.
     * @param string $range_from The lower limit of the range
     * @param string $range_to The upper limit of the range
     * @param string $regex Internal param used during recursion to pass the pattern as built
     * @return string
     */
    protected function compile_range($range_from, $range_to) {
        //setup if we are the first recursion
        static $recurse_level = 0;
        static $regex = array();
        static $number_len = null;
        static $first_call=0;
        if ($first_call == 0) {
            $first_call = time();
        }

        if ($first_call < (time() - 5)) {
            die ("Timeout");
        }

        if ($recurse_level > 50) {
            throw new Exception("Recurse $recurse_level");
        }
        //reset if first level
        if ($recurse_level == 0) {
            $first_recursion = true;
            $regex = array();
            $number_len = max(array(strlen($range_from),
                                    strlen($range_to)));
        } else {
            $first_recursion = false;
        }

        //pad with leading zeroes
        $range_from = str_pad($range_from, $number_len, "0", STR_PAD_LEFT);
        $range_to = str_pad($range_to, $number_len, "0", STR_PAD_LEFT);

        $recurse_level++;
        print_r("L$recurse_level - called $range_from:$range_to - \r\n");
        
        /*
         * Try to express the range as a regex. We can do this if:
         *  - less than 10 difference and no digit rollover OR
         *  - $range_from % 10 == 0 AND
         *  - $range_to % 10 == 9 AND
         *  - all digits of $range_from < all digits of $range_to
         */
        $can_express_1 = (($range_to - $range_from) < 10) &&
                            (($range_from % 10) <= ($range_to % 10));        
        $can_express_2 = ($range_from % 10 == 0) && ($range_to % 10 == 9);

        $from_len = strlen($range_from);
        $i = 0;
        //use a while so we break out as soon as condition false
        while (!$can_express_1 && $can_express_2 && ($i < $from_len)) {
            $can_express_2 &= ($range_to[$i] >= $range_from[$i]);
            $i++;
        }

        //build the expression if we can
        if ($can_express_1 || $can_express_2) {
            $pattern = null;
            for ($i = 0; $i < $from_len; $i++) {
                if ($range_from[$i] == $range_to[$i]) {
                    $pattern .= $range_from[$i];
                } else {
                    $pattern .= "[" . $range_from[$i] . "-" . $range_to[$i] . "]";
                }
            }
            print_r("exp".$pattern."\r\n");
            $regex[] = $pattern;
        } else {
            /*
             * If we can't express as a regex, split into ranges and recurse
             */

            $sub_from = $range_from;
            $safety=0;
            while (($sub_from <= $range_to) && ($safety<30)) {
                $safety++;
                if ($sub_from == $range_to) {
                    print_r("single");
                    //the top range is a single match
                    $regex[] = $range_to;
                    break;
                } elseif ((($range_to - $sub_from) < 10) &&
                            (($sub_from % 10) <= ($range_to % 10))) {
                    print_r("tens");
                    $sub_to = $range_to;
                } elseif (($sub_from % 10) != 0) {
                    print_r("to9");
                    // we can only go to 9 in this range
                    $sub_to = (floor($sub_from / 10) * 10) + 9;
                } else {
                    //try to do a full order of magnitude
                    $oom = pow(10, floor(log10($sub_from)));
                    $max = ($oom * (1 + floor($sub_from / $oom))) - 1;
                    
                    if (($max <= $range_to) && ($max > $sub_from)) {
                        $sub_to = $max;
                        print_r("fulloom");
                    } else {
                        //work down hundreds, tens, etc
                        $diff = $range_to - $sub_from;
                        $oom = pow(10, floor(log10($diff)));
                        print_r("hunten $diff $oom ");
                        $sub_to = $sub_from + (($oom * floor($diff / $oom)) - 1);
                        print_r($sub_to);
                    }
                }

                //don't overflow the search range
                if ($sub_to > $range_to) {
                    $sub_to = $range_to;
                }
                print_r("\r\n");
                $this->compile_range($sub_from, $sub_to, $recurse_level, $regex);
                $sub_from = $sub_to + 1;
            }
        }
        $recurse_level--;
        if ($first_recursion) {
            $recurse_level = 0;
            return "(" . implode("|", $regex) . ")";
        } else {
            return null;
        }
    }
}