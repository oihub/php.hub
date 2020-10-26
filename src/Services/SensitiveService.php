<?php

namespace PhpHos\Hub\Services;

use PhpHos\Hub\Services\Sensitive\CharIterator;

/**
 * Class SensitiveService.
 *
 * 生成词库：
 * $filter = new SensitiveService();
 * $data = $filter->readfile('input_path'); // 读取文件数据.
 * $filter->filltrie($data); // 填充数据.
 * $filter->saveLexicon('output_path'); // 保存词库.
 *
 * 搜索：
 * $filter = new SensitiveService();
 * $filter->readLexicon('bin_path');
 * $result = $filter->search('some text here...');
 *
 * 替换：
 * $filter = new SensitiveService();
 * $filter->readLexicon('bin_path');
 * $replaced = $filter->replace('some text here...', '**');
 *
 * 高级替换：
 * $filter = new SensitiveService();
 * $filter->readLexicon('bin_path');
 * $replaced = $filter->replace('我要包二奶', function ($word, $value) {
 *          return "[$word -> $value]";
 *      }
 *  );
 *
 * @author sean <maoxfjob@163.com>
 */
class SensitiveService extends Service
{
    /**
     * char for padding value.
     */
    const CHAR_PAD = ' ';
    /**
     * stop chars.
     */
    const CHAR_STOP = ',.? ';

    /**
     * @var resource file handle.
     */
    private $file;
    /**
     * @var array trie data.
     */
    private $trie = [];
    /**
     * @var int fixed row length.
     */
    private $rowLength = 0;
    /**
     * @var int fixed value length.
     */
    private $valueLength = 0;
    /**
     * @var array first chars cache.
     */
    private $start = [];

    /**
     * 读文件.
     *
     * @param string $filename 文件名.
     * @param string $separator 分隔符.
     * @return array
     */
    public function readfile(
        string $filename,
        string $separator = ','
    ): array {
        $fp = fopen($filename, 'r');
        while ($line = fgets($fp, 1024)) {
            $line = trim($line);
            empty($line) or $data[] = explode($separator, $line);
        }
        fclose($fp);
        return $data;
    }

    /**
     * 填充.
     *
     * @param array $words 词汇.
     * @return void
     */
    public function filltrie(array $words): void
    {
        foreach ($words as $item) {
            list($word, $value) = $item;
            $iterator = new CharIterator($word);
            $prefix = '';
            foreach ($iterator as $char) {
                $next = &$this->trie[$prefix]['next'];
                if (!isset($next) || !in_array($char, $next)) {
                    $next[] = $char;
                }
                $prefix .= $char;
            }
            if (strlen($value) > $this->valueLength) {
                $this->valueLength = strlen($value);
            }
            $this->trie[$word]['value'] = $value;
        }
    }

    /**
     * 保存词库.
     *
     * @param string $filename 文件名.
     * @return void
     */
    public function saveLexicon(string $filename): void
    {
        sort($this->trie['']['next'], SORT_STRING);
        $stack = [array_fill_keys($this->trie['']['next'], 0)];
        $prefix = [];
        $fp = fopen($filename, 'w');
        // header: count, valueLength, rowLength
        $line = pack(
            "nnn",
            count($stack[0]),
            $this->valueLength,
            $this->valueLength + 9
        );
        fwrite($fp, $line);
        $offset = strlen($line);
        do {
            foreach ($stack[0] as $char => &$addr) {
                if ($addr > 0) {
                    continue;
                }
                $line = str_pad($char, 3, self::CHAR_PAD)
                    . pack("nN", 0, 0)
                    . str_repeat(
                        self::CHAR_PAD,
                        $this->valueLength
                    );
                fwrite($fp, $line);
                $addr = $offset;
                $offset += strlen($line);
            }

            $nextKeys = array_keys($stack[0]);
            $nextChar = $nextKeys[0];
            $next = $this->trie[implode('', $prefix) . $nextChar];
            $nextSize = count($next['next'] ?? []);
            $nextVal = $next['value'] ?? '';
            $line = pack("nN", $nextSize, $offset)
                . str_pad(
                    $nextVal,
                    $this->valueLength,
                    self::CHAR_PAD
                );
            fseek($fp, $stack[0][$nextChar] + 3);
            fwrite($fp, $line);
            fseek($fp, $offset);
            if (isset($next['next'])) {
                $prefix[] = $nextChar;
                sort($next['next'], SORT_STRING);
                array_unshift(
                    $stack,
                    array_fill_keys($next['next'], 0)
                );
            } else {
                unset($stack[0][$nextChar]);
            }

            while (empty($stack[0]) && !empty($stack)) {
                array_shift($stack);
                if (empty($stack)) {
                    break;
                }
                $keys = array_keys($stack[0]);
                unset($stack[0][$keys[0]]);
                array_pop($prefix);
            }
        } while (!empty($stack));
        fclose($fp);
    }

    /**
     * 读取词库.
     *
     * @param string $filename 文件名.
     * @return void
     */
    public function readLexicon(string $filename)
    {
        $this->file = fopen($filename, 'r');
        $unpack = unpack("n3", fread($this->file, 6));
        $count  = $unpack[1];
        $this->valueLength = $unpack[2];
        $this->rowLength = $unpack[3];
        foreach ($this->readLine(6, $count) as $line) {
            list($fChar, $fCount, $fOffset, $fValue) = $line;
            $this->start[$fChar] = [$fCount, $fOffset, $fValue];
        }
    }

    /**
     * search $str, return words found in dict:
     * [
     *   'word1' => ['value' => 'value1', 'count' => 'count1'],
     *   ...
     * ]
     *
     * @param string $str
     * @return array
     */
    public function search(string $str): array
    {
        $ret = [];
        $iterator = new CharIterator($str);
        $stops = self::CHAR_STOP;

        $buff = [];
        foreach ($iterator as $char) {
            if (strpos($stops, $char) !== false) {
                $buff = [];
                continue;
            }

            foreach ($buff as $prefix => $next) {
                $newPrefix = $prefix . $char;
                list(
                    $count,
                    $offset,
                    $value
                ) = $this->findWord($char, $next[0], $next[1]);
                if (!empty($value)) {
                    if (isset($ret[$newPrefix])) {
                        $ret[$newPrefix]['count']++;
                    } else {
                        $ret[$newPrefix] = [
                            'count' => 1,
                            'value' => $value
                        ];
                    }
                }
                if ($count > 0) {
                    $buff[$newPrefix] = [$count, $offset];
                }
                unset($buff[$prefix]);
            }

            if (isset($this->start[$char])) {
                list(
                    $count,
                    $offset,
                    $value
                ) = $this->start[$char];
                if (!empty($value)) {
                    if (isset($ret[$char])) {
                        $ret[$char]['count']++;
                    } else {
                        $ret[$char] = [
                            'count' => 1,
                            'value' => $value
                        ];
                    }
                }
                if ($count > 0 && !isset($buff[$char])) {
                    $buff[$char] = [$count, $offset];
                }
            }
        }
        return $ret;
    }

    /**
     * replace words to $to.
     * if $to is callable, replace to call_user_func($to, $word, $value).
     *
     * @param string $str
     * @param callable|string $to
     * @return string
     */
    public function replace(string $str, $to): string
    {
        $ret = '';
        $iterator = new CharIterator($str);
        $stops = self::CHAR_STOP;

        $buff = '';
        $size = 0;
        $offset = 0;
        $buffValue = [];
        foreach ($iterator as $char) {
            if (strpos($stops, $char) !== false) {
                if (empty($buffValue)) {
                    $ret .= $buff . $char;
                } else {
                    $ret .= $this->replaceTo(
                        $buffValue[0],
                        $buffValue[1],
                        $to
                    );
                    $ret .= substr($buff, strlen($buffValue[0]));
                    $ret .= $char;
                }
                $buff = '';
                $buffValue = [];

                continue;
            }

            if ($buff !== '') {
                list(
                    $fCount,
                    $fOffset,
                    $fValue
                ) = $this->findWord($char, $size, $offset);
                if ($fValue === null) {
                    if (empty($buffValue)) {
                        $ret .= $buff;
                    } else {
                        $ret .= $this->replaceTo(
                            $buffValue[0],
                            $buffValue[1],
                            $to
                        );
                        $ret .= substr($buff, strlen($buffValue[0]));
                    }
                    $buff = '';
                    $buffValue = [];
                } else {
                    if ($fCount > 0) {
                        $buff .= $char;
                        $size = $fCount;
                        $offset = $fOffset;
                        if (!empty($fValue)) {
                            $buffValue = [$buff, $fValue];
                        }
                    } else {
                        $ret .= $this->replaceTo($buff . $char, $fValue, $to);
                        $buff = '';
                        $buffValue = [];
                    }
                    continue;
                }
            }

            if (isset($this->start[$char])) {
                list(
                    $fCount,
                    $fOffset,
                    $fValue
                ) = $this->start[$char];
                if ($fCount > 0) {
                    $buff = $char;
                    $size = $fCount;
                    $offset = $fOffset;
                    if (!empty($fValue))
                        $buffValue = [$buff, $fValue];
                } else {
                    $ret .= $this->replaceTo($char, $fValue, $to);
                }
            } else {
                $ret .= $char;
            }
        }

        if ($buff !== '') {
            if (empty($buffValue)) {
                $ret .= $buff;
            } else {
                $ret .= $this->replaceTo(
                    $buffValue[0],
                    $buffValue[1],
                    $to
                ) . substr($buff, strlen($buffValue[0]));
            }
        }

        return $ret;
    }

    /**
     * 替换.
     *
     * @param string $word
     * @param string $value
     * @param callable|string $to
     * @return string
     */
    protected function replaceTo(
        string $word,
        string $value,
        $to
    ): string {
        return is_callable($to)
            ? call_user_func($to, $word, $value)
            : $to;
    }

    /**
     * from $offset, find $char, up to $count record.
     *
     * @param string $char
     * @param int $count
     * @param int $offset
     * @return array($count, $offset, $value)
     */
    protected function findWord(
        string $char,
        int $count,
        int $offset
    ): array {
        fseek($this->file, $offset);
        $len = $this->rowLength;
        $data = fread($this->file, $count * $len);
        for ($i = 0; $i < $count; $i++) {
            $row = substr($data, $i * $len, $len);
            $un = unpack("c3char/ncount/Noffset/c*value", $row);
            $fChar = rtrim(chr($un['char1'])
                . chr($un['char2'])
                . chr($un['char3']));
            if ($fChar !== $char) {
                continue;
            }
            $fCount = $un['count'];
            $fOffset = $un['offset'];
            $fValue = '';
            for ($j = 1; $j <= $this->rowLength - 9; $j++) {
                $v = $un['value' . $j];
                if ($v == 32) {
                    break;
                }
                $fValue .= chr($v);
            }
            return [$fCount, $fOffset, $fValue];
        }
        return [0, 0, null];
    }

    /**
     * 读取一行.
     *
     * @param int $offset
     * @param int $size
     * @return array
     */
    protected function readLine(int $offset, int $size): array
    {
        $ret = [];
        fseek($this->file, $offset);
        $data = fread($this->file, $size * $this->rowLength);
        for ($i = 0; $i < $size; $i++) {
            $row = substr(
                $data,
                $i * $this->rowLength,
                $this->rowLength
            );
            $un = unpack("c3char/ncount/Noffset/c*value", $row);
            $fChar = rtrim(chr($un['char1'])
                . chr($un['char2'])
                . chr($un['char3']));
            $fCount = $un['count'];
            $fOffset = $un['offset'];
            $fValue = '';
            for ($j = 1; $j <= $this->rowLength - 9; $j++) {
                $v = $un['value' . $j];
                if ($v == 32) {
                    break;
                }
                $fValue .= chr($v);
            }
            $ret[] = [$fChar, $fCount, $fOffset, $fValue];
        }
        return $ret;
    }

    public function __destruct()
    {
        unset($this->start);
        $this->file and fclose($this->file);
    }
}
