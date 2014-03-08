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
     * Check whether the timespan contains a date
     * @param DateTime $date
     * @return boolean
     */
    public function contains(DateTime $date)
    {
        return $this->start < $date && $date < $this->end;
    }

    /**
     * Check whether the timespan overlaps with another timespan
     * @param Timespan $span
     * @return boolean
     */
    public function overlaps(Timespan $span)
    {
        return $this->start <= $span->end && $span->start <= $this->end;
    }

    /**
     * Get parts of the timespan which don't appear in another timespan
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
     * Merge the timespan with another one (if possible)
     * @param Timespan $span
     * @return Timespan
     */
    public function merge(Timespan $span)
    {
        if ($this->overlaps($span)) {
            if ($this->start > $span->start) {
                $this->start = $span->start;
            }
            if ($this->end < $span->end) {
                $this->end = $span->end;
            }
        }

        return $this;
    }

    /**
     * Compare the timespan with another timespan
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
     * Convert the timespan into a period based on an interval
     * @param DateInterval $interval
     * @return DatePeriod
     */
    public function toPeriod(DateInterval $interval)
    {
        $end = clone ($this->end);
        return new DatePeriod($this->start, $interval, $end->modify('+1 second'));
    }

    /**
     * Convert the timespan to an array
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
     * Convert the timespan to an array
     * @return string
     */
    public function __toString()
    {
        return $this->start->format('c') . ' - ' . $this->end->format('c');
    }
}
