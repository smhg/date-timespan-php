<?php
namespace Timespan;

use \DateTime;
use \DateInterval;
use \DatePeriod;

class Timespan
{
    public $start;
    public $end;

    public function __construct(DateTime $start, DateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Check whether timespan contains a date
     * @param DateTime $date
     * @return boolean
     */
    public function contains(DateTime $date)
    {
        return $this->start < $date && $date < $this->end;
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
        } else {
            if ($span->start <= $this->start) {
                // start before/together
                if ($this->end > $span->end) {
                    // eindigt erin
                    $collection[] = new $this($span->end, $this->end);
                }
                // end after/together
            } else {
                // start inside
                if ($span->end < $this->end) {
                    // end inside
                    $collection[] = new $this($this->start, $span->start);
                    $collection[] = new $this($span->end, $this->end);
                } else {
                    // end after/together
                    $collection[] = new $this($this->start, $span->start);
                }
            }
        }

        return $collection;
    }

    /**
     * Merge timespan with another one (if possible)
     * @param Timespan $span
     * @return Collection
     */
    public function merge(Timespan $span)
    {
        $result = new Collection();

        if ($this->overlaps($span)) {
            $start = $this->start > $span->start ? $span->start : $this->start;
            $end = $this->end < $span->end ? $span->end : $this->end;
            $result[] = new $this($start, $end);
        } else {
            if ($this->start <= $span->start) {
                $result[] = $this;
                $result[] = $span;
            } else {
                $result[] = $span;
                $result[] = $this;
            }
        }

        return $result;
    }

    /**
     * Trim timespan to fit within boundaries
     * @param  DateTime $start
     * @param  DateTime $end
     * @return Timespan
     */
    public function trim(DateTime $start, DateTime $end)
    {
        if ($this->start < $start) {
            $this->start = $start;
        }

        if ($this->end > $end) {
            $this->end = $end;
        }

        return $this;
    }

    /**
     * Compare timespan with another timespan
     * @param Timespan $span
     * @return boolean
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
     * Convert timespan to an array
     * @return string
     */
    public function __toString()
    {
        return $this->start->format('c') . ' - ' . $this->end->format('c');
    }
}
