<?php

namespace LumenLogViewer\Utils;

/**
 * Class Pattern
 *
 * @property array patterns
 * @package LumenLogViewer\Utils
 */
class Pattern {

    private $patterns = [
        'logs' => '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?\].*/',
        'current_log' => [
            '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([\+-]\d{4})?)\](?:.*?(\w+)\.|.*?)',
            ': (.*?)( in .*?:[0-9]+)?$/i'
        ],
        'files' => '/\{.*?\,.*?\}/i',
    ];

    /**
     * @param string $pattern
     * @param int    $position
     *
     * @return string pattern
     */
    public function getPattern($pattern, $position = null) {
        if ($position !== null) {
            return $this->patterns[$pattern][$position];
        }
        return $this->patterns[$pattern];
    }
}
