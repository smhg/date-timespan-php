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
        $this->compress();
        $result = clone $this;

        if ($collection->isEmpty()) {
            return $result;
        }

        $collection->compress();

        $resultLength = count($result);
        $lastRight = end($collection);
        $firstRight = reset($collection);
        $collectionLength = count($collection);

        $tmp = $result->getArrayCopy();

        $j = 0;
        for ($i = 0; $i < $resultLength; $i++) {
            if ($tmp[$i]->end <= $firstRight->start) {
                // before first right, go to next
                continue;
            }

            if ($tmp[$i]->start >= $lastRight->end) {
                // after last right, end
                break;
            }

            for (; $j < $collectionLength;) {
                if ($collection[$j]->end <= $tmp[$i]->start) {
                    // right item is before current left, go to next right
                    $j++;
                    continue;
                }

                if ($collection[$j]->start >= $tmp[$i]->end) {
                    // right item is after current left, done
                    break;
                }

                // right item intersects with current left, do diff
                $diff = $tmp[$i]->diff($collection[$j])->getArrayCopy();

                if (empty($diff)) {
                    // remove left item, proceed to next
                    array_splice($tmp, $i, 1);
                    $i--;
                    $resultLength--;
                    break;
                } else {
                    // replace left item with first item from diff
                    array_splice($tmp, $i, 1, array_splice($diff, 0, 1));
                    if (!empty($diff)) {
                        // if diff has second item, insert at right location
                        for ($k = $i + 1; $k < $resultLength; $k++) {
                            if ($tmp[$k]->compare($diff[0]) > 0) {
                                array_splice($tmp, $k, 0, array_splice($diff, 0, 1));
                                $resultLength++;
                                break;
                            }

                        }

                        // right location = append at end
                        if (!empty($diff)) {
                            $tmp[] = $diff[0];
                            $resultLength++;
                        }
                    }
                }
            }
        }

        $result->exchangeArray($tmp);

        return $result->compress();
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

        $length = count($this);

        for ($i = 0; $i < $length - 1;) {
            $tmp = $this->getArrayCopy();

            $merge = $tmp[$i]->merge($tmp[$i + 1])->getArrayCopy();

            if (count($merge) === 2 && $merge == array_slice($tmp, $i, 2)) { // no change after merge
                $i++;
            } else { // merge returned something new
                // replace original elements with first merge result (which is always in order)
                array_splice($tmp, $i, 2, array_splice($merge, 0, 1));
                $length--;

                // insert remaining merge results at right location
                for ($j = $i + 1; !empty($merge) && $j < $length; $j++) {
                    if ($tmp[$j]->compare($merge[0]) > 0) {
                        array_splice($tmp, $j, 0, array_splice($merge, 0, 1));
                        $length++;
                    }
                }

                // append remaining merge results to end
                $tmp = array_merge($tmp, $merge);
                $length += count($merge);

                $this->exchangeArray($tmp);
            }
        }

        return $this;
    }

    /**
     * Sort items in this collection
     * @return Collection
     */
    public function sort()
    {
        $this->uasort(function (Timespan $span1, Timespan $span2) {
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
        return implode(
            '\n',
            array_map(
                function ($span) {
                    return (string)$span;
                },
                $this->getArrayCopy()
            )
        );
    }

    public function __clone()
    {
        foreach ($this as $key => $span) {
            $this[$key] = clone $span;
        }
    }
}
