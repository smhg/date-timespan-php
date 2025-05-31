<?php
namespace Timespan;

use DateTime;
use DateTimeImmutable;
use DateInterval;
use DatePeriod;

class Timespan
{
    public DateTime|DateTimeImmutable $start;
    public DateTime|DateTimeImmutable $end;

    public function __construct(DateTime|DateTimeImmutable $start, DateTime|DateTimeImmutable $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Check whether timespan contains a date.
     * Includes start, excludes end (like PHP's DatePeriod)
     */
    public function contains(DateTime|DateTimeImmutable $date): bool
    {
        return $this->start <= $date && $date < $this->end;
    }

    /**
     * Check whether timespan overlaps with another timespan
     */
    public function overlaps(Timespan $span): bool
    {
        return $this->start <= $span->end && $span->start <= $this->end;
    }

    /**
     * Get parts of timespan which don't appear in another timespan
     */
    public function diff(Timespan $span): Collection
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
     */
    public function merge(Timespan $span): Collection
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
     * @return Timespan|null A new, trimmed, timespan or `null` if nothing remains
     */
    public function trim(DateTime|DateTimeImmutable $start, DateTime|DateTimeImmutable $end): Timespan|null
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
     * @return int<-1,1>
     */
    public function compare(Timespan $span): int
    {
        if ($this->start == $span->start) {
            return 0;
        }
        return $this->start < $span->start ? -1 : 1;
    }

    /**
     * Convert timespan into a period based on an interval
     */
    public function toPeriod(DateInterval $interval): DatePeriod
    {
        $end = clone $this->end;

        return new DatePeriod($this->start, $interval, $end->modify('+1 second'));
    }

    /**
     * Convert timespan to an array
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array(
            'start' => $this->start->format('c'),
            'end' => $this->end->format('c')
        );
    }

    public function __clone(): void
    {
        $this->start = clone $this->start;
        $this->end = clone $this->end;
    }

    /**
     * Convert to ISO 8601 time interval format
     */
    public function __toString(): string
    {
        return sprintf('%s/%s', $this->start->format('c'), $this->end->format('c'));
    }
}
