<?php
namespace Timespan;

use \DatePeriod;

class Collection extends \ArrayObject
{
    /**
     * Create a new timespan collection
     * @param DatePeriod|array $mixed
     */
    public function __construct($mixed = null)
    {
        if ($mixed instanceof DatePeriod) {
            $length = count($mixed);
            $date = current($mixed);
            for ($i = 1; $i < $length; $i++) {
                $this[] = new Timespan($date, $mixed[$i]);
                $date = $mixed[$i];
            }
        } elseif (is_array($mixed)) {
            $this->exchangeArray($mixed);
        }
    }

    public function diff(Collection $collection)
    {
        $result = clone $this;

        foreach ($collection as $span2) {
            $tmp = array();
            foreach ($result->getArrayCopy() as $span) {
                $tmp = array_merge($tmp, $span->diff($span2)->getArrayCopy());
            }
            $result->exchangeArray($tmp);
        }

        return $result;
    }

    /**
     * Merge the collection with another collection
     * @param Collection $collection
     * @return Collection
     */
    public function merge(Collection $collection)
    {
        $this->exchangeArray(array_merge($this->getArrayCopy(), $collection->getArrayCopy()));

        return $this->compress();
    }

    /**
     * Merge timespans in this collection together when possible
     * @return Collection
     */
    public function compress()
    {
        $this->sort();

        $tmp = $this->getArrayCopy();
        $length = count($tmp) - 1;

        for ($i = 0;$i < $length;$i++) {
            if ($tmp[$i]->overlaps($tmp[$i + 1])) {
                $tmp[$i]->merge($tmp[$i + 1]);
                unset($tmp[$i + 1]);
                $tmp = array_values($tmp);
                $length = count($tmp) - 1;
                $i--;
            }
        }

        $this->exchangeArray($tmp);

        return $this;
    }

    /**
     * Sort items in this collection
     * @return Collection
     */
    public function sort()
    {
        $this->uasort(function ($span1, $span2) {
            return $span1->compare($span2);
        });

        // make sure keys are reset
        $this->exchangeArray(array_values($this->getArrayCopy()));

        return $this;
    }

    /**
     * Returns whether the collection is empty
     * @return boolean
     */
    public function isEmpty()
    {
        return count($this) === 0;
    }

    /**
     * Converts the collection to an array
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($span) {
            return $span->toArray();
        }, $this->getArrayCopy());
    }

    /**
     * Converts the collection to a string
     * @return string [description]
     */
    public function __toString()
    {
        return implode(array_map(function ($span) {
            return (string)$span;
        }, $this->getArrayCopy()), "\n");
    }

    public function __clone()
    {
        foreach ($this as $key => $span) {
            $this[$key] = clone $span;
        }
    }
}
