<?php
class TestPhpStream {
    protected static $content;
    protected $index;

    public static function setContent($content): void {
        self::$content = $content;
    }

    public function stream_open() { $this->index = 0; return true; }
    public function stream_read($count) {
        $ret = substr(self::$content, $this->index, $count);
        $this->index += strlen($ret);
        return $ret;
    }
    public function stream_eof() { return $this->index >= strlen(self::$content); }
    public function stream_stat() { return []; }
}
