<?php

namespace Package;

use \DateTime;

class Logger
{
    public const LEVEL_HIGH = 2;
    public const LEVEL_MEDIUM = 1;
    public const LEVEL_LOW = 0;

    private $filename;
    private $minLevel;

    public function __construct($filename, $minLevel=self::LEVEL_LOW)
    {
        $this->filename = $filename;
        if (!file_exists($this->filename)) {
            file_put_contents($this->filename, "Timestamp,Level,Category,Message" . PHP_EOL);
        }

        $this->minLevel = $minLevel;

        $this->log("Log Started", $category="", $level=self::LEVEL_HIGH);
    }

    public function log($message, $category="", $level=self::LEVEL_LOW)
    {
        if ($level < $this->minLevel) {
            return;
        }

        $now = new DateTime();

        $entry = implode(",", [
            $now->format("Y-m-d\TH:i:s.uP"),
            $level,
            $category,
            $message
        ]);

        $entry .= PHP_EOL;

        file_put_contents($this->filename, $entry, FILE_APPEND);
    }
}