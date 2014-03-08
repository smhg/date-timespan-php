<?php
require_once __DIR__ . '/../src/Timespan/Timespan.php';
require_once __DIR__ . '/../src/Timespan/Collection.php';

use Timespan\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $collection = new Collection();
    }
}
