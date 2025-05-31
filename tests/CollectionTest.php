<?php declare(strict_types=1);
namespace Timespan;

use DateTime;
use DateTimeImmutable;
use DatePeriod;
use DateInterval;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Collection::class)]
class CollectionTest extends TestCase
{
    public function testConstructor(): Collection
    {
        $start = new DateTime('now');
        $period = new DatePeriod($start, new DateInterval('P1D'), 5);
        $collection = new Collection($period);

        $this->assertEquals(5, count($collection));

        $collection = new Collection();

        $this->assertTrue($collection->isEmpty());

        $start = new DateTime('last monday');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $start = clone $start;
        $start->modify('+3 week');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $start = clone $start;
        $start->modify('+3 week');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $this->assertEquals(3, $collection->count());

        return $collection;
    }

    /**
     * @depends testConstructor
     */
    public function testDiff(Collection $collection): void
    {
        $new = clone $collection;
        foreach ($new as &$span) {
            $span->start->modify('+5 days');
            $span->end->modify('+5 days');
        }

        $diff = $collection->diff($new);
        $this->assertEquals(3, $diff->count());
        $this->assertTrue(isset($collection[0]));
        $this->assertTrue(isset($diff[0]));
        $this->assertEquals($collection[0]->start, $diff[0]->start);
        $firstEnd = clone $collection[0]->end;
        $this->assertEquals($firstEnd->modify('-2 days'), $diff[0]->end);

        $spans1 = new Collection();
        $spans1[] = new Timespan(new DateTime('T9:00:00'), new DateTime('T11:30:00'));

        $spans2 = new Collection();
        $spans2[] = new Timespan(new DateTime('T8:45:00'), new DateTime('T9:30:00'));
        $spans2[] = new Timespan(new DateTimeImmutable('T9:40:00'), new DateTimeImmutable('T10:30:00'));

        $diff = $spans1->diff($spans2);

        $this->assertEquals(2, $diff->count());
        $this->assertTrue(isset($diff[0]));
        $this->assertEquals($diff[0]->start->format('H:i'), '09:30');
        $this->assertEquals($diff[0]->end->format('H:i'), '09:40');
        $this->assertTrue(isset($diff[1]));
        $this->assertEquals($diff[1]->start->format('H:i'), '10:30');
        $this->assertEquals($diff[1]->end->format('H:i'), '11:30');

        $spans1 = new Collection();
        $spans1[] = new Timespan(new DateTime('T9:00:00'), new DateTime('T10:00:00'));
        $spans1[] = new Timespan(new DateTime('T10:15:00'), new DateTime('T10:30:00'));
        $spans1[] = new Timespan(new DateTime('T10:45:00'), new DateTime('T11:30:00'));
        $spans1[] = new Timespan(new DateTime('T11:45:00'), new DateTime('T13:00:00'));

        $spans2 = new Collection();
        $spans2[] = new Timespan(new DateTime('T8:30:00'), new DateTime('T8:45:00'));
        $spans2[] = new Timespan(new DateTime('T9:00:00'), new DateTime('T9:30:00'));
        $spans2[] = new Timespan(new DateTime('T9:45:00'), new DateTime('T11:00:00'));
        $spans2[] = new Timespan(new DateTime('T11:15:00'), new DateTime('T12:00:00'));

        $diff = $spans1->diff($spans2);

        $this->assertEquals(3, $diff->count());
        $this->assertTrue(isset($diff[0]));
        $this->assertEquals($diff[0]->start->format('H:i'), '09:30');
        $this->assertEquals($diff[0]->end->format('H:i'), '09:45');
        $this->assertTrue(isset($diff[1]));
        $this->assertEquals($diff[1]->start->format('H:i'), '11:00');
        $this->assertEquals($diff[1]->end->format('H:i'), '11:15');
        $this->assertTrue(isset($diff[2]));
        $this->assertEquals($diff[2]->start->format('H:i'), '12:00');
        $this->assertEquals($diff[2]->end->format('H:i'), '13:00');
    }

    /**
     * Tests sort,compress and merge
     * @depends testConstructor
     */
    public function testMerge(Collection $original): void
    {
        $collection = clone $original;
        $new = clone $collection;
        foreach ($new as &$span) {
            $span->start->modify('+2 days');
            $span->end->modify('+2 days');
        }
        $new->exchangeArray(array_reverse($new->getArrayCopy()));
        $collection->merge($new);

        $this->assertEquals($original->count(), $collection->count());
        $this->assertTrue(isset($original[0]));
        $this->assertTrue(isset($collection[0]));
        $this->assertEquals($original[0]->start, $collection[0]->start);
        $firstEnd = clone $original[0]->end;
        $this->assertEquals($firstEnd->modify('+2 days'), $collection[0]->end);

        $collection = clone $original;
        $tmp = array();
        foreach ($collection as $span) {
            $newSpan = clone $span;
            $newSpan->start->modify('+2 days');
            $newSpan->end->modify('+2 days');
            $tmp[] = $newSpan;
            $newSpan = clone $span;
            $newSpan->start->modify('+1 days');
            $newSpan->end->modify('+3 days');
            $tmp[] = $newSpan;
            $newSpan = clone $span;
            $newSpan->start->modify('+5 days');
            $newSpan->end->modify('+7 days');
            $tmp[] = $newSpan;
        }
        $new = new Collection($tmp);
        $collection->merge($new);

        $this->assertEquals($original->count(), $collection->count());
        $this->assertTrue(isset($original[0]));
        $this->assertTrue(isset($collection[0]));
        $this->assertEquals($original[0]->start, $collection[0]->start);
        $firstEnd = clone $original[0]->end;
        $this->assertEquals($firstEnd->modify('+7 days'), $collection[0]->end);
    }

    /**
     * @depends testConstructor
     */
    public function testToArray(Collection $collection): void
    {
        $this->assertTrue(count($collection->toArray()) > 0);
    }

    /**
     * @depends testConstructor
     */
    public function testToString(Collection $collection): void
    {
        $result = (string)$collection;

        $this->assertCount(3, explode("\n", $result));
    }
}
