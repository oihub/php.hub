<?php

namespace PhpHos\Hub\Services;

use PhpHos\Hub\Services\Snowflake\RandomSequence;
use PhpHos\Hub\Services\Snowflake\SequenceInterface;

/**
 * Class SnowflakeService.
 *
 * @author sean <maoxfjob@163.com>
 */
class SnowflakeService extends Service
{
    /**
     * 首位比特数.
     */
    const FIRST_BIT = 1;
    /**
     * 毫秒时间戳比特数.
     */
    const MICROTIME_BIT = 41;
    /**
     * 数据中心比特数.
     */
    const DATACENTER_BIT = 5;
    /**
     * 机器 ID 比特数.
     */
    const WORKID_BIT = 5;
    /**
     * 序列号比特数.
     */
    const SEQUENCE_BIT = 12;

    /**
     * @var int 数据中心.
     */
    protected $datacenter;
    /**
     * @var int 机器 ID.
     */
    protected $workerid;
    /**
     * @var SequenceInterface|callable 序列号实例.
     */
    protected $sequence;
    /**
     * @var int 开始时间戳.
     */
    protected $startTime;

    /**
     * 初始化.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->config = array_replace([
            'datacenter' => 0,
            'workerid' => 0,
        ], $this->config);

        $maxDataCenter = $this->maxBin(static::DATACENTER_BIT);
        $maxWorkId = $this->maxBin(static::WORKID_BIT);

        if (
            $this->config['datacenter'] > $maxDataCenter
            || $this->config['datacenter'] < 0
        ) {
            throw new \Exception(sprintf('The datacenter can\'t be greater than %d or less than 0', $maxDataCenter));
        }

        if (
            $this->config['workerid'] > $maxWorkId
            || $this->config['workerid'] < 0
        ) {
            throw new \Exception(sprintf('The worker id can\'t be greater than %d or less than 0', $maxWorkId));
        }

        $this->datacenter = $this->config['datacenter'];
        $this->workerid = $this->config['workerid'];
    }

    /**
     * 生成 ID.
     *
     * @return string
     */
    public function id(): string
    {
        $microtime = $this->getMicrotime();
        $maxSequence = $this->maxBin(static::SEQUENCE_BIT);
        while (($sequence = $this->callSequence($microtime)) > $maxSequence) {
            usleep(1);
            $microtime = $this->getMicrotime();
        }

        $workerLeftMoveLength = static::SEQUENCE_BIT;
        $datacenterLeftMoveLength = static::WORKID_BIT + $workerLeftMoveLength;
        $timestampLeftMoveLength = static::DATACENTER_BIT + $datacenterLeftMoveLength;

        return (string) ((($microtime - $this->getStartMicrotime()) << $timestampLeftMoveLength)
            | ($this->datacenter << $datacenterLeftMoveLength)
            | ($this->workerid << $workerLeftMoveLength)
            | ($sequence));
    }

    /**
     * 解析 ID.
     *
     * @param string $id Snowflake ID.
     * @param bool $bin 是否返回二进制.
     * @return array
     */
    public function parseId(string $id, $bin = false): array
    {
        $id = decbin($id);

        $data = [
            'timestamp' => substr($id, 0, -22),
            'datacenter' => substr($id, -22, 5),
            'workerid' => substr($id, -17, 5),
            'sequence' => substr($id, -12),
        ];

        return $bin ? $data : array_map(function ($item) {
            return bindec($item);
        }, $data);
    }

    /**
     * 得到开始毫秒时间戳.
     *
     * @return int
     */
    public function getStartMicrotime(): int
    {
        if ($this->startTime > 0) {
            return $this->startTime;
        }

        return strtotime('2020-00-00 00:00:00') * 1000;
    }

    /**
     * 设置开始毫秒时间戳.
     *
     * @param int $microtime 毫秒时间戳.
     * @return self
     */
    public function setStartMicrotime(int $microtime): self
    {
        $misstime = $this->getMicrotime() - $microtime;

        if ($misstime < 0) {
            throw new \Exception('The start time cannot be greater than the current time');
        }

        $maxMicrotime = $this->maxBin(static::MICROTIME_BIT);
        if ($misstime > $maxMicrotime) {
            throw new \Exception(sprintf('The microtime can\'t be greater than %d', $maxMicrotime));
        }

        $this->startTime = $microtime;

        return $this;
    }

    /**
     * 得到序列号提供者.
     *
     * @return SequenceInterface|callable
     */
    public function getSequence()
    {
        $this->sequence or $this->sequence = new RandomSequence();

        return $this->sequence;
    }

    /**
     * 设置序列号提供者.
     *
     * @param SequenceInterface|callable $sequence 序列号提供者.
     * @return self
     */
    public function setSequence($sequence): self
    {
        if (
            is_null($sequence)
            && !is_callable($sequence)
            && !($sequence instanceof SequenceInterface)
        ) {
            throw new \Exception('Invalid sequence');
        }

        $this->sequence = $sequence;

        return $this;
    }

    /**
     * 调用序列号.
     * 
     * @param int $time 时间.
     * @return int
     */
    protected function callSequence(int $time): int
    {
        $sequence = $this->getSequence();

        if (is_callable($sequence)) {
            return $sequence($time);
        }

        return $sequence->next($time);
    }

    /**
     * 得到当前毫秒时间戳.
     *
     * @return int
     */
    protected function getMicrotime(): int
    {
        return floor(microtime(true) * 1000) | 0;
    }

    /**
     * 二进制最大值.
     *
     * @return int
     */
    protected function maxBin(int $num): int
    {
        return -1 ^ (-1 << $num);
    }
}
