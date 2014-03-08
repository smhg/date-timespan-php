<?php
require_once __DIR__ . '/../src/Timespan/Timespan.php';
require_once __DIR__ . '/../src/Timespan/Collection.php';

use Timespan\Timespan;
use Timespan\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $collection = new Collection();

        $this->assertTrue($collection->isEmpty());

        $start = new DateTime('last monday');
        $end = clone $start;
        $end->modify('+1 week');

        $collection[] = new Timespan($start, $end);
        $start = clone $start;
        $start->modify('+2 week');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $start = clone $start;
        $start->modify('+2 week');
        $end = clone $start;
        $end->modify('+1 week');
        $collection[] = new Timespan($start, $end);

        $this->assertEquals(3, count($collection));

        return $collection;
    }

    /**
     * @depends testConstructor
     */
    public function testDiff($collection)
    {
        $new = clone $collection;
        foreach ($new as &$span) {
            $span->start->modify('+5 days');
            $span->end->modify('+5 days');
        }

        $diff = $collection->diff($new);
        $this->assertEquals($collection[0]->start, $diff[0]->start);
        $firstEnd = clone $collection[0]->end;
        $this->assertEquals($firstEnd->modify('-2 days'), $diff[0]->end);
    }

    /**
     * Tests sort,compress and merge
     * @depends testConstructor
     */
    public function testMerge($original)
    {
        $collection = clone $original;
        $new = clone $collection;
        foreach ($new as &$span) {
            $span->start->modify('+5 days');
            $span->end->modify('+5 days');
        }
        $new->exchangeArray(array_reverse($new->getArrayCopy()));

        $collection->merge($new);
        $this->assertTrue(count($original) === count($collection));
        $this->assertEquals($original[0]->start, $collection[0]->start);
        $firstEnd = clone $original[0]->end;
        $this->assertEquals($firstEnd->modify('+5 days'), $collection[0]->end);
    }

    /**
     * @depends testConstructor
     */
    public function testToArray($collection)
    {
        $this->assertTrue(count($collection->toArray()) > 0);
    }

    /**
     * @depends testConstructor
     */
    public function testToString($collection)
    {
        $this->assertTrue(is_string((string)$collection));
    }
}
