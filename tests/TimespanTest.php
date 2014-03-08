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

        $this->assertEquals($start, $span->start);
        $this->assertEquals($end, $span->end);

        return $span;
    }

    /**
     * @depends testConstructor
     */
    public function testContains($span)
    {
        $date = clone $span->start;
        $date->modify('+3 day');

        $this->assertTrue($span->contains($date));
        $this->assertFalse($span->contains($date->modify('-1 week')));
    }

    /**
     * @depends testConstructor
     */
    public function testToPeriod($span)
    {
        $period = $span->toPeriod(new \DateInterval('P1D'));
        $this->assertInstanceOf('DatePeriod', $period);
        $arr = iterator_to_array($period);
        $this->assertEquals($span->start, reset($arr));
        $this->assertEquals($span->end, end($arr));
    }

    /**
     * @depends testConstructor
     */
    public function testToArray($span)
    {
        $arr = $span->toArray();
        $this->assertTrue(isset($arr['start']));
        $this->assertTrue(isset($arr['end']));
    }

    /**
     * @depends testConstructor
     */
    public function testToString($span)
    {
        $this->assertTrue(is_string((string)$span));
    }

    /**
     * @depends testConstructor
     */
    public function testOverlaps($span)
    {
        $new = clone $span;
        $new->start->modify('+3 day');
        $new->end->modify('+1 week');
        $this->assertTrue($new->overlaps($span));
        $this->assertTrue($span->overlaps($new));

        $new = clone $span;
        $this->assertTrue($new->overlaps($span));
        $this->assertTrue($span->overlaps($new));

        $new = clone $span;
        $new->start->modify('+2 week');
        $new->end->modify('+1 week');
        $this->assertFalse($new->overlaps($span));
        $this->assertFalse($span->overlaps($new));
    }

    /**
     * @depends testConstructor
     */
    public function testDiff($span)
    {

    }

    /**
     * @depends testConstructor
     */
    public function testMerge($span)
    {

    }

    /**
     * @depends testConstructor
     */
    public function testCompare($span)
    {
        $new = clone $span;
        $new->start->modify('+1 week');
        $new->end->modify('+1 week');
        $this->assertEquals(-1, $span->compare($new));

        $new = clone $span;
        $new->start->modify('-1 week');
        $new->end->modify('+1 week');
        $this->assertEquals(1, $span->compare($new));

        $this->assertEquals(0, $span->compare(clone $span));
    }
}
