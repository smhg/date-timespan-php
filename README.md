date-timespan
=============

Collection of PHP classes to work with timespans.

## Installation
```bash
$ composer require smhg/date-timespan
```

## Methods
### Timespan
```php
use Timespan\Timespan;

$start = DateTime::createFromFormat('last monday');
$end = clone $start;
$end->modify('+1 week');

$timespan = new Timespan($start, $end);
```

#### contains(DateTime)
Returns whether the timespan contains a date.

#### overlaps(Timespan)
Returns whether the timespan overlaps with another timespan.

#### diff(Timespan)
Returns a collection of timespans (pieces of the original timespan) which do not appear in another timespan.

#### merge(Timespan)
Returns a collection with the merged timespan or both timespans when a merge was not possible.

#### compare(Timespan)
Returns whether the timespan occurs before, together or after another timespan.

#### toPeriod(DateInterval)
Converts the timespan to a [DatePeriod](http://www.php.net/dateperiod) using an interval.

#### toArray()
Converts the timespan to an array containing the `start` and `end` dates.

### Collection
```php
use Timespan\Timespan;
use Timespan\Collection;

$collection = new Collection();
$collection[] = new Timespan(...);
```
#### sort()
Sorts the collection based on the start of each timespan.

#### compress()
Joins timespans in the collection if they overlap. Also sorts the result.

#### merge()
Merges the collection with another collection. Also compresses the result.
