<?php

defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests the Exregex_Numeric class
 *
 * @group exregex
 * @group exregex.numeric
 *
 * @package    Unittest
 * @author     Andrew Coulton
 * @copyright  (c) 2010 Andrew Coulton
 * @license    http://kohanaframework.org/license
 */
Class Exregex_NumericTest extends Kohana_Unittest_TestCase {

    /**
     * Returns data for test_matchnumericrange
     */
    public function provider_matchnumericrange() {
        return array(
            array('/^#001187-003658#$/', 1187, 3658, "%06d", 1, 4000),
            array('/^#001187-002658#$/', 1187, 2658, "%06d", 1, 3000),
            array('/^#001187-001195#$/', 1187, 1195, "%06d", 1, 3000),
            array('/^#000001-000100#$/', 1, 100, "%06d", 0, 300),
            array('/^#000010-000100#$/', 10, 100, "%06d", 0, 300),
            array('/^#001187-001199#$/', 1187, 1199, "%06d", 1, 2500),
            array('/^#001187-001188#$/', 1187, 1188, "%06d", 1, 2500),
            array('/^#000001-000009#$/', 1, 9, "%06d", 0, 25),
            array('/^#000005-000010#$/', 5, 10, "%06d", 0, 25),
        );
    }

    /**
     * Tests that compiled ranges match the full range of expected numbers
     * and no others
     *
     * @test
     * @dataProvider provider_matchnumericrange()
     * @param string $pattern
     * @param int $low
     * @param int $high
     * @param string $format
     * @param int $testlow
     * @param int $testhigh
     */
    public function test_matchnumericrange($pattern, $low, $high, $format, $testlow, $testhigh) {
        $regex = Exregex_Numeric::factory($pattern);
        $failed_ranges = null;
        $this_failed_range_low = null;
        for ($i = $testlow; $i < $testhigh; $i++) {
            $expected_match = (($i >= $low) && ($i <= $high));            
            $subject = sprintf($format, $i);
            if ($regex->match($subject) != $expected_match) {
                if ($this_failed_range_low === null) {
                    $this_failed_range_low = $i;
                }
            } else {
                if ($this_failed_range_low === null) {
                    continue;
                }
                if ($this_failed_range_low === ($i - 1)) {
                    //single val
                    $failed_ranges .= $i - 1;
                } else {
                    $failed_ranges .= $this_failed_range_low . "-" . ($i - 1);
                }
                $failed_ranges .= ",";
                $this_failed_range_low = null;
            }
        }

        if ($this_failed_range_low === $i) {
            //single val
            $failed_ranges .= $i;
        } elseif ($this_failed_range_low !== null) {
            $failed_ranges .= $this_failed_range_low . "-" . $i;
        }


        $this->assertNull($failed_ranges);
    }

}
