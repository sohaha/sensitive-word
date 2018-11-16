<?php

namespace Zls\SensitiveWord;

use Z;

/**
 * 敏感词匹配
 * @package Zls\SensitiveWord
 */
class SensitiveWord
{

    public $tree = null;

    public function __construct($words = [])
    {
        if (is_array($words)) {
            foreach ($words as $word) {
                $this->addWord($word);
            }
        }
    }

    public function addWord($word)
    {
        $len = mb_strlen($word);
        if (is_null($this->tree)) {
            $tree = $this->TreeNode();
            $tree->isEnd = 0;
        } else {
            $tree = $this->tree;
        }
        $tmp = $tree;
        for ($i = 0; $i < $len; $i++) {
            $nowLetter = mb_substr($word, $i, 1);
            $letterTable = LetterTable::instance();
            $letterTable->set($nowLetter);
            $nowTree = $tree->get($nowLetter);
            if (!is_null($nowTree)) {
                $tree = $nowTree;
            } else {
                $newTree = $this->TreeNode();
                $newTree->isEnd = 0;
                $tree->set($nowLetter, $newTree);
                $tree = $newTree;
            }
            if ($i == ($len - 1)) {
                $tree->isEnd = 1;
            }
        }
        $this->tree = $tmp;
    }

    private function TreeNode()
    {
        return new class
        {
            public  $isEnd      = 0;
            public  $value      = null;
            private $letterList = [];

            public function get($letter)
            {
                return isset($this->letterList[$letter]) ? $this->letterList[$letter] : null;
            }

            public function set($letter, $nextNode)
            {
                $letterTable = LetterTable::instance();
                $letterObject = $letterTable->get($letter);
                $nextNode->value = $letterObject;
                $this->letterList[$letter] = $nextNode;
            }

            public function hasNext()
            {
                return !empty($this->letterList);
            }
        };
    }

    public function has($string, $wordTree = null)
    {
        $wordTree = $this->search($string, $wordTree);

        return !!Z::arrayGet($wordTree, 'words');
    }

    public function search($string, $wordTree = null)
    {
        if (!!$wordTree) {
            return $wordTree;
        }
        $string = $string.'';
        $len = mb_strlen($string);
        $result = [];
        $stack = [];
        $letterTable = LetterTable::instance();
        $tmpTree = $this->tree;
        for ($i = 0; $i < $len; $i++) {
            $nowLetterA = mb_substr($string, $i, 1);
            if ($letterTable->isExists($nowLetterA) && ($i != ($len - 1))) {
                if (!is_null($tmpTree->get($nowLetterA))) {
                    array_push($stack, $i);
                }
            } else {
                $end = $i;
                while (count($stack) > 0) {
                    $curIndex = array_pop($stack);
                    $start = $curIndex;
                    $tmpWord = '';
                    $tree = $tmpTree;
                    for ($j = $curIndex; $j < $end; $j++) {
                        $nowLetter = mb_substr($string, $j, 1);
                        $nowTree = $tree->get($nowLetter);
                        if (!is_null($nowTree)) {
                            $tmpWord .= $nowLetter;
                            if ($nowTree->isEnd) {
                                array_push($result, [
                                    'word' => $tmpWord,
                                    'start' => $start,
                                    'end' => $j + 1,
                                ]);
                                if ($nowTree->hasNext()) {
                                    $tree = $nowTree;
                                } else {
                                    $start = $j;
                                    $tmpWord = '';
                                    $tree = $tmpTree;
                                }
                            } else {
                                $tree = $nowTree;
                            }
                        } else {
                            $start = $j;
                            $tmpWord = '';
                            $tree = $tmpTree;
                        }
                    }
                }
            }
        }

        return [
            'lists' => $result,
            'words' => array_flip(array_flip(array_column($result, 'word'))),
        ];
    }

    public function replace($string, $dot = '*', $wordTree = null)
    {
        $wordTree = $this->search($string, $wordTree);
        $words = Z::arrayGet($wordTree, 'words');
        foreach ($words as $word) {
            $string = str_replace($word, str_repeat($dot, mb_strlen($word)), $string);
        }

        return $string;
    }
}
