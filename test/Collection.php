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
    }

    /**
     * @depends testConstructor
     */
    public function testMerge($collection)
    {
    }

    /**
     * @depends testConstructor
     */
    public function testCompress($collection)
    {
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
