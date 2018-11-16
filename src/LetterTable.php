<?php

namespace Zls\SensitiveWord;

class LetterTable
{
    private static $instance    = null;
    private        $letterTable = [];

    private function __construct()
    {
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __clone()
    {
        return self::$instance;
    }

    public function set($letter)
    {
        if (!$this->isExists($letter)) {
            $letterObject = new class($letter)
            {

                public $value;
                public $frequency;

                public function __construct($value)
                {
                    $this->value = $value;
                    $this->frequency = 1;
                }

            };
            $this->letterTable[$letter] = $letterObject;
        } else {
            $letterObject = $this->get($letter);
            $letterObject->frequency = $letterObject->frequency + 1;
        }
    }

    public function isExists($letter)
    {
        return isset($this->letterTable[$letter]);
    }

    public function get($letter)
    {
        return $this->isExists($letter) ? $this->letterTable[$letter] : null;
    }
}
