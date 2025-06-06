<?php declare(strict_types=1);
namespace Timespan;

use DateTime;
use DateInterval;
use DatePeriod;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Timespan::class)]
class TimespanTest extends TestCase
{
    public function testConstructor(): Timespan
    {
        $start = new DateTime('this monday');
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
    public function testContains(Timespan $span): void
    {
        $date = clone $span->start;
        $date->modify('+3 day');

        $this->assertTrue($span->contains($span->start));
        $this->assertFalse($span->contains($span->end));
        $this->assertTrue($span->contains($date));
        $this->assertFalse($span->contains($date->modify('-1 week')));
    }

    /**
     * @depends testConstructor
     */
    public function testToPeriod(Timespan $span): void
    {
        $period = $span->toPeriod(new DateInterval('P1D'));
        $arr = iterator_to_array($period);
        $this->assertEquals($span->start, reset($arr));
        $this->assertEquals($span->end, end($arr));
    }

    /**
     * @depends testConstructor
     */
    public function testToArray(Timespan $span): void
    {
        $arr = $span->toArray();
        $this->assertTrue(isset($arr['start']));
        $this->assertTrue(isset($arr['end']));
    }

    /**
     * @depends testConstructor
     */
    public function testToString(Timespan $span): void
    {
        $this->assertTrue(stripos((string)$span, '/') !== false);
    }

    /**
     * @depends testConstructor
     */
    public function testOverlaps(Timespan $span): void
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
    public function testDiff(Timespan $original): void
    {
        // cut out at the beginning
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('-3 days');
        $new->end->modify('-3 days');
        $result = (array)$span->diff($new);
        $this->assertTrue(count($result) === 1);
        $first = reset($result);
        $this->assertEquals($new->end, $first->start);
        $this->assertEquals($original->end, $first->end);

        // cut out at the end
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('+3 days');
        $new->end->modify('+3 days');
        $result = (array)$span->diff($new);
        $this->assertTrue(count($result) === 1);
        $first = reset($result);
        $this->assertEquals($original->start, $first->start);
        $this->assertEquals($new->start, $first->end);

        // cut out in the middle
        $span = clone $original;
        $new = clone $span;
        $new->start->modify('+3 days');
        $new->end->modify('-3 days');
        $result = (array)$span->diff($new);
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
        $result = (array)$span->diff($new);
        $this->assertTrue(count($result) === 1);
        $first = reset($result);
        $this->assertEquals($original->start, $first->start);
        $this->assertEquals($original->end, $first->end);
    }

    /**
     * @depends testConstructor
     */
    public function testMerge(Timespan $span): void
    {
        $new = clone $span;
        $new->start->modify('-3 days');
        $new->end->modify('-3 days');
        $col = $span->merge($new);
        $this->assertEquals(1, $col->count());
        $this->assertTrue(isset($col[0]));
        $this->assertEquals($new->start, $col[0]->start);
        $this->assertEquals($span->end, $col[0]->end);

        $new = clone $span;
        $new->start->modify('+3 days');
        $new->end->modify('+3 days');
        $col = $span->merge($new);
        $this->assertEquals(1, $col->count());

        $new = clone $span;
        $col = $span->merge($new);
        $this->assertEquals(1, $col->count());

        $new = clone $span;
        $new->start->modify('-3 days');
        $new->end->modify('+3 days');
        $col = $span->merge($new);
        $this->assertEquals(1, $col->count());

        $new = clone $span;
        $new->start->modify('+3 days');
        $new->end->modify('-3 days');
        $col = $span->merge($new);
        $this->assertEquals(1, $col->count());

        $new = clone $span;
        $new->start->modify('+14 days');
        $new->end->modify('+14 days');
        $col = $span->merge($new);
        $this->assertEquals(2, $col->count());
    }

    /**
     * @depends testConstructor
     */
    public function testTrim(Timespan $span): void
    {
        $start = clone $span->start;
        $start->modify('+1 day');
        $trimmed = $span->trim($start, $span->end);
        $this->assertTrue(!is_null($trimmed));
        $this->assertTrue($trimmed !== $span, 'Trim should not mutate the original span, but return a new one.');
        $this->assertEquals($start, $trimmed->start);
        $this->assertNotEquals($span->start, $trimmed->start);

        $end = clone $span->end;
        $end->modify('-1 day');
        $trimmed = $span->trim($span->start, $end);
        $this->assertTrue(!is_null($trimmed));
        $this->assertEquals($end, $trimmed->end);
        $this->assertNotEquals($span->end, $trimmed->end);

        $start = clone $span->start;
        $start->modify('+2 week');
        $end = clone $span->end;
        $end->modify('+2 week');
        $this->assertTrue(!$span->trim($start, $end), 'Trim should not return anything if no time is left inside the span.');

        $start = clone $span->start;
        $start->modify('-2 week');
        $end = clone $span->end;
        $end->modify('-2 week');
        $this->assertTrue(!$span->trim($start, $end), 'Trim should not return anything if no time is left inside the span.');

        $start = clone $span->start;
        $start->modify('+1 day');
        $this->assertTrue(!$span->trim($start, $start), 'Trim should not return anything if no time is left inside the span.');

        $start = clone $span->start;
        $start->modify('-1 week');
        $end = clone $span->end;
        $end->modify('-1 week');
        $this->assertTrue(!$span->trim($start, $end), 'Trim should not return anything if boundaries touch, but don\'t overlap.');
    }

    /**
     * @depends testConstructor
     */
    public function testCompare(Timespan $span): void
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
