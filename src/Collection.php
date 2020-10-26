<?php

namespace PhpHos\Hub;

/**
 * Class Collection.
 *
 * @author sean <maoxfjob@163.com>
 */
class Collection implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $items;

    /**
     * 构造函数.
     *
     * @param array $items 数据.
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * 得到.
     *
     * @param string $key 键名.
     * @param mixed  $default 默认值.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $items = $this->items;

        if (is_null($key)) {
            return $items;
        }

        if (isset($items[$key])) {
            return $items[$key];
        }

        foreach (explode('.', $key) as $item) {
            if (
                !is_array($items)
                || !array_key_exists($item, $items)
            ) {
                return $default;
            }

            $items = $items[$item];
        }

        return $items;
    }

    /**
     * 设置.
     *
     * @param string $key 键名.
     * @param mixed  $value 值.
     * @return array
     */
    public function set(string $key, $value): array
    {
        $keys = explode('.', $key);
        $items = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (
                !isset($items[$key])
                || !is_array($items[$key])
            ) {
                $items[$key] = [];
            }
            $items = &$items[$key];
        }

        $items[array_shift($keys)] = $value;

        return $items;
    }

    /**
     * 是否存在.
     *
     * @param string $key 键名.
     * @return bool
     */
    public function has(string $key): bool
    {
        return (bool) $this->get($key);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }
}
