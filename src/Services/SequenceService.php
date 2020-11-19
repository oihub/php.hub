<?php

namespace PhpHos\Hub\Services;

use PhpHos\Hub\Services\Sequence\SequenceInterface;

/**
 * Class SequenceService.
 *
 * @author sean <maoxfjob@163.com>
 */
class SequenceService extends Service
{
    /**
     * 初始化.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->config = array_replace([
            'min' => 0,
            'max' => 999999999999,
            'step' => 1,
            'length' => 12,
            'prefix' => '',
            'suffix' => '',
            'secret' => 127466127478,
        ], $this->config);
    }

    /**
     * 生成 ID.
     *
     * @return string
     */
    public function id(): string
    {
        $sequence = $this->callSequence();
        $sequence = str_pad($sequence, $this->config['length'], '0', STR_PAD_LEFT);
        return join('', [
            $this->config['prefix'],
            $sequence,
            $this->config['suffix'],
        ]);
    }

    /**
     * 加密生成 ID.
     *
     * @return string
     */
    public function encode(): string
    {
        $sequence = $this->callSequence();
        $sequence = $sequence + time() + $this->config['secret'];
        $sequence = str_pad($sequence, $this->config['length'], '0', STR_PAD_LEFT);
        return join('', [
            $this->config['prefix'],
            $sequence,
            $this->config['suffix'],
        ]);
    }

    /**
     * 得到序列号提供者.
     *
     * @return SequenceInterface|callable
     */
    public function getSequence()
    {
        if (is_null($this->sequence)) {
            throw new \Exception('Invalid sequence');
        }

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
     * @return int
     */
    protected function callSequence(): int
    {
        $sequence = $this->getSequence();

        if (is_callable($sequence)) {
            return $sequence();
        }

        $no = $sequence->next(
            $this->config['min'],
            $this->config['step']
        );

        if ($no > $this->config['max']) {
            throw new \Exception('The sequence can\'t be greater than max sequence');
        }

        return $no;
    }
}
