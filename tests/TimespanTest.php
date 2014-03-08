<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Timespan\Timespan;

class TimespanTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $start = new DateTime();
        $start->modify('this monday');
        $end = clone $start;
        $end->modify('+1 week');

        $span = new Timespan($start, $end);
    }
}
