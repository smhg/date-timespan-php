date-timespan [![Build status](https://api.travis-ci.org/smhg/date-timespan-php.png)](https://travis-ci.org/smhg/date-timespan-php)
=============

Collection of PHP classes to work with timespans.

It differs from [DatePeriod](http://php.net/manual/en/class.dateperiod.php) in that it only defines a timespan/period by a start and end date. It offers _algebraic_ methods to manipulate a timespan or a collection of them. Convert it to a DatePeriod passing a DateInterval to `toPeriod()` when you need to iterate over it.

## Installation
```bash
$ composer require smhg/date-timespan
```

## Methods
### Timespan
```php
use Timespan\Timespan;

$start = new DateTime('last monday');
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

#### trim(DateTime, DateTime)
Returns a new timespan fitting within the passed boundaries or `null` if no time remains.

#### compare(Timespan)
Returns whether the timespan occurs before, together or after another timespan.

#### toPeriod(DateInterval)
Converts the timespan to a [DatePeriod](http://www.php.net/dateperiod) using an interval.

#### __toString()
Converts the timespan to a string in [ISO 8601 time interval](https://en.wikipedia.org/wiki/ISO_8601#Time_intervals) format.

#### toArray()
Converts the timespan to an array with a single `interval` element containing the `toString` representation.

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
