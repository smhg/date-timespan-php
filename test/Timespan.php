<?php
require_once __DIR__ . '/../src/Timespan/Timespan.php';
require_once __DIR__ . '/../src/Timespan/Collection.php';

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
    public function testDiff($original)
    {
        // cut out at the beginning
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('-3 days');
        $new->end->modify('-3 days');
        $result = $span->diff($new);
        $this->assertTrue(count($result) === 1);
        $first = reset($result);
        $this->assertEquals($new->end, $first->start);
        $this->assertEquals($original->end, $first->end);

        // cut out at the end
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('+3 days');
        $new->end->modify('+3 days');
        $result = $span->diff($new);
        $this->assertTrue(count($result) === 1);
        $first = reset($result);
        $this->assertEquals($original->start, $first->start);
        $this->assertEquals($new->start, $first->end);

        // cut out in the middle
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('+3 days');
        $new->end->modify('-3 days');
        $result = $span->diff($new);
        $this->assertTrue(count($result) === 2);
        $first = reset($result);
        $this->assertEquals($original->start, $first->start);
        $this->assertEquals($new->start, $first->end);
        $last = end($result);
        $this->assertEquals($new->end, $last->start);
        $this->assertEquals($original->end, $last->end);

        // no overlap
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('+2 weeks');
        $new->end->modify('+2 weeks');
        $result = $span->diff($new);
        $this->assertTrue(count($result) === 1);
        $first = reset($result);
        $this->assertEquals($original->start, $first->start);
        $this->assertEquals($original->end, $first->end);
    }

    /**
     * @depends testConstructor
     */
    public function testMerge($original)
    {
        $span = clone $original;
        $new = clone $original;
        $new->start->modify('-3 days');
        $new->end->modify('-3 days');
        $span->merge($new);
        $this->assertEquals($new->start, $span->start);
        $this->assertEquals($original->end, $span->end);

        $span = clone $original;
        $new = clone $original;
        $new->start->modify('+3 days');
        $new->end->modify('+3 days');
        $span->merge($new);
        $this->assertEquals($original->start, $span->start);
        $this->assertEquals($new->end, $span->end);

        $span = clone $original;
        $new = clone $original;
        $span->merge($new);
        $this->assertEquals($original->start, $span->start);
        $this->assertEquals($original->end, $span->end);

        $span = clone $original;
        $new = clone $original;
        $new->start->modify('-3 days');
        $new->end->modify('+3 days');
        $span->merge($new);
        $this->assertEquals($new->start, $span->start);
        $this->assertEquals($new->end, $span->end);

        $span = clone $original;
        $new = clone $original;
        $new->start->modify('+3 days');
        $new->end->modify('-3 days');
        $span->merge($new);
        $this->assertEquals($original->start, $span->start);
        $this->assertEquals($original->end, $span->end);
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
