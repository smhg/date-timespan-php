<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Timespan\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $collection = new Collection();
    }
}
