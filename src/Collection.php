<?php declare(strict_types=1);
namespace Timespan;

use DatePeriod;
use ArrayObject;

/**
 * @extends ArrayObject<int, Timespan>
 */
class Collection extends ArrayObject
{
    /**
     * @param DatePeriod|array<Timespan>|null $mixed
     */
    public function __construct(DatePeriod|array|null $mixed = null)
    {
        if ($mixed instanceof DatePeriod) {
            $iterator = $mixed->getIterator();

            $previousDate = null;
            foreach ($iterator as $date) {
                if ($previousDate) {
                    $this[] = new Timespan($previousDate, $date);
                }

                $previousDate = $date;
            }
        } elseif (is_array($mixed)) {
            $this->exchangeArray($mixed);
        }
    }

    /**
     * Get the difference between this and the provided collection
     */
    public function diff(Collection $collection): Collection
    {
        $this->compress();
        $result = clone $this;

        if ($collection->isEmpty()) {
            return $result;
        }

        $collection->compress();
        $arrCollection = (array)$collection;

        $resultLength = $result->count();
        $lastRight = end($arrCollection);
        $firstRight = reset($arrCollection);
        $collectionLength = $collection->count();

        $tmp = $result->getArrayCopy();

        $idx2 = 0;
        for ($idx1 = 0; $idx1 < $resultLength; $idx1++) {
            if ($tmp[$idx1]->end <= $firstRight->start) {
                // before first right, go to next
                continue;
            }

            if ($tmp[$idx1]->start >= $lastRight->end) {
                // after last right, end
                break;
            }

            for (; $idx2 < $collectionLength;) {
                if (!isset($collection[$idx2])) {
                    throw new Exception(sprintf('Invalid index (%d)', $idx2));
                }

                if ($collection[$idx2]->end <= $tmp[$idx1]->start) {
                    // right item is before current left, go to next right
                    $idx2++;

                    continue;
                }

                if ($collection[$idx2]->start >= $tmp[$idx1]->end) {
                    // right item is after current left, done
                    break;
                }

                // right item intersects with current left, do diff
                $diff = $tmp[$idx1]->diff($collection[$idx2])->getArrayCopy();

                if (empty($diff)) {
                    // remove left item, proceed to next
                    array_splice($tmp, $idx1, 1);
                    $idx1--;
                    $resultLength--;

                    break;
                }

                // replace left item with first item from diff
                array_splice($tmp, $idx1, 1, array_splice($diff, 0, 1));
                if (!empty($diff)) {
                    // if diff has second item, insert at right location
                    for ($idx3 = $idx1 + 1; $idx3 < $resultLength; $idx3++) {
                        if ($tmp[$idx3]->compare($diff[0]) > 0) {
                            array_splice($tmp, $idx3, 0, array_splice($diff, 0, 1));
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

        $result->exchangeArray($tmp);

        return $result->compress();
    }

    /**
     * Merge the collection with another collection
     */
    public function merge(Collection $collection): Collection
    {
        $this->exchangeArray(array_merge($this->getArrayCopy(), $collection->getArrayCopy()));

        return $this->compress();
    }

    /**
     * Merge timespans in this collection together when possible
     */
    public function compress(): Collection
    {
        $this->sort();

        $length = $this->count();

        for ($idx1 = 0; $idx1 < $length - 1;) {
            $tmp = $this->getArrayCopy();

            $merge = $tmp[$idx1]->merge($tmp[$idx1 + 1])->getArrayCopy();

            if (count($merge) === 2 && $merge == array_slice($tmp, $idx1, 2)) {
                // no change after merge
                $idx1++;

                continue;
            }

            // merge returned something new
            // replace original elements with first merge result (which is always in order)
            array_splice($tmp, $idx1, 2, array_splice($merge, 0, 1));
            $length--;

            // insert remaining merge results at right location
            for ($idx2 = $idx1 + 1; !empty($merge) && $idx2 < $length; $idx2++) {
                if ($tmp[$idx2]->compare($merge[0]) > 0) {
                    array_splice($tmp, $idx2, 0, array_splice($merge, 0, 1));
                    $length++;
                }
            }

            // append remaining merge results to end
            $tmp = array_merge($tmp, $merge);
            $length += count($merge);

            $this->exchangeArray($tmp);
        }

        return $this;
    }

    /**
     * Sort items in this collection
     */
    public function sort(): Collection
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
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Converts the collection to an array
     * @return array<int, array<string, string>>
     */
    public function toArray(): array
    {
        return array_map(function ($span) {
            return $span->toArray();
        }, $this->getArrayCopy());
    }

    /**
     * Converts the collection to a string
     */
    public function __toString(): string
    {
        return implode(
            "\n",
            array_map(
                function ($span) {
                    return (string)$span;
                },
                $this->getArrayCopy()
            )
        );
    }

    public function __clone(): void
    {
        foreach ($this as $key => $span) {
            $this[$key] = clone $span;
        }
    }
}
