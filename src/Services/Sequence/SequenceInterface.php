<?php

namespace PhpHos\Hub\Services\Sequence;

/**
 * Class SequenceInterface.
 *
 * @author sean <maoxfjob@163.com>
 */
interface SequenceInterface
{
    /**
     * 得到序列号.
     *
     * @return int
     */
    public function next(int $min = 0, int $step = 1): int;
}
