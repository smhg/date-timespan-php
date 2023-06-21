<?php
namespace Timespan;

use \DateTimeInterface;
use \DateInterval;
use \DatePeriod;

class Timespan
{
    public $start;
    public $end;

    public function __construct(DateTimeInterface $start, DateTimeInterface $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Check whether timespan contains a date.
     * Includes start, excludes end (like PHP's DatePeriod)
     * @param DateTimeInterface $date
     * @return boolean
     */
    public function contains(DateTimeInterface $date)
    {
        return $this->start <= $date && $date < $this->end;
    }

    /**
     * Check whether timespan overlaps with another timespan
     * @param Timespan $span
     * @return boolean
     */
    public function overlaps(Timespan $span)
    {
        return $this->start <= $span->end && $span->start <= $this->end;
    }

    /**
     * Get parts of timespan which don't appear in another timespan
     * @param Timespan $span
     * @return Collection
     */
    public function diff(Timespan $span)
    {
        $collection = new Collection();

        if (!$this->overlaps($span)) {
            $collection[] = clone $this;
            return $collection;
        }

        if ($span->compare($this) <= 0) {
            // start before/together
            if ($this->end > $span->end) {
                // end inside
                $collection[] = new $this($span->end, $this->end);
            }
            // end after/together
            return $collection;
        }

        // start inside
        if ($span->end < $this->end) {
            // end inside
            $collection[] = new $this($this->start, $span->start);
            $collection[] = new $this($span->end, $this->end);
            return $collection;
        }

        // end after/together
        $collection[] = new $this($this->start, $span->start);

        return $collection;
    }

    /**
     * Merge timespan with another one and return a collection with 1 or 2 new timespans
     * @param Timespan $span
     * @return Collection
     */
    public function merge(Timespan $span)
    {
        $result = new Collection();

        if ($this->overlaps($span)) {
            $start = $this->compare($span) > 0 ? $span->start : $this->start;
            $end = $this->end < $span->end ? $span->end : $this->end;
            $result[] = new $this($start, $end);

            return $result;
        }

        if ($this->compare($span) <= 0) {
            $result[] = $this;
            $result[] = $span;

            return $result;
        }

        $result[] = $span;
        $result[] = $this;

        return $result;
    }

    /**
     * Trim timespan to fit within boundaries
     * @param  DateTimeInterface $start
     * @param  DateTimeInterface $end
     * @return Timespan|null A new, trimmed, timespan or `null` if nothing remains
     */
    public function trim(DateTimeInterface $start, DateTimeInterface $end)
    {
        $trimmed = clone $this;

        if ($start >= $trimmed->end || $end <= $trimmed->start) {
            return null;
        }

        if ($trimmed->start < $start) {
            $trimmed->start = $start;
        }

        if ($trimmed->end > $end) {
            $trimmed->end = $end;
        }

        if ($trimmed->start == $trimmed->end) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Compare timespan with another timespan
     * @param Timespan $span
     * @return int
     */
    public function compare(Timespan $span)
    {
        if ($this->start == $span->start) {
            return 0;
        }
        return $this->start < $span->start ? -1 : 1;
    }

    /**
     * Convert timespan into a period based on an interval
     * @param DateInterval $interval
     * @return DatePeriod
     */
    public function toPeriod(DateInterval $interval)
    {
        $end = clone $this->end;
        return new DatePeriod($this->start, $interval, $end->modify('+1 second'));
    }

    /**
     * Convert timespan to an array
     * @return array
     */
    public function toArray()
    {
        return array(
            'start' => $this->start->format('c'),
            'end' => $this->end->format('c')
        );
    }

    public function __clone()
    {
        $this->start = clone $this->start;
        $this->end = clone $this->end;
    }

    /**
     * Convert to ISO 8601 time interval format
     * @return string
     */
    public function __toString()
    {
        return $this->start->format('c') . '/' . $this->end->format('c');
    }
}
