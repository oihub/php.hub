<?php

namespace PhpHos\Hub\Services\Snowflake;

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
     * @param int $time 时间.
     * @return int
     */
    public function next(int $time): int;
}
