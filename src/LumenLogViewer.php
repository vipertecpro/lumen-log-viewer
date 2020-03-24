<?php

namespace LumenLogViewer;

use Exception;
use Illuminate\Support\Facades\File;
use LumenLogViewer\Utils\{Level, Pattern};

/**
 * Class LumenLogViewer
 *
 * @package LumenLogViewer
 */
class LumenLogViewer {

    private static $logViewer;

    /**
     * @var string file
     */
    private $file;

    /**
     * @var string folder
     */
    private $folder;

    /**
     * @var string storage_path
     */
    private $storage_path;

    /**
     * @var int
     */
    private $max_file_size;

    /**
     * @var Level level
     */
    private $level;

    /**
     * @var Pattern pattern
     */
    private $pattern;

    /**
     * LumenLogViewer constructor.
     *
     */
    public function __construct() {
        $this->level = new Level();
        $this->pattern = new Pattern();
        $this->max_file_size = config('logviewer.max_file_size', 52428800);
        $this->storage_path = config('logviewer.storage_path', storage_path('logs'));
    }

    public static function getInstance() {
        if (!self::$logViewer) {
            self::$logViewer = new self();
        }
        return self::$logViewer;
    }

    /**
     * @param string $folder
     */
    public function setFolder($folder) {
        if (File::exists($folder)) {
            $this->folder = $folder;
        } elseif ($this->storage_path) {
            $logsPath = $this->storage_path . '/' . $folder;
            if (File::exists($logsPath)) {
                $this->folder = $folder;
            }
        }
    }

    /**
     * @param string $file
     *
     * @throws Exception
     */
    public function setFile($file) {
        $file = $this->pathToLogFile($file);

        if (File::exists($file)) {
            $this->file = $file;
        }
    }

    /**
     * @param string $file
     *
     * @return string
     * @throws Exception
     */
    public function pathToLogFile($file) {
        if (File::exists($file)) { // try the absolute path
            return $file;
        }

        $logsPath = $this->storage_path;
        $logsPath .= ($this->folder) ? '/' . $this->folder : '';
        $file = $logsPath . '/' . $file;
        // check if requested file is really in the logs directory
        if (dirname($file) !== $logsPath) {
            throw new Exception('No such log file');
        }
        return $file;
    }

    /**
     * @return string
     */
    public function getFolderName() {
        return $this->folder;
    }

    /**
     * @return string
     */
    public function getFileName() {
        return basename($this->file);
    }

    /**
     * @return array
     */
    public function all() {
        $log = [];
        if (!$this->file) {
            $log_file = (!$this->folder) ? $this->getFiles() : $this->getFolderFiles();
            if (!count($log_file)) {
                return [];
            }
            $this->file = $log_file[0];
        }

        $max_file_size = function_exists('config') ? config('logviewer.max_file_size', $this->max_file_size) : $this->max_file_size;
        if (File::size($this->file) > $max_file_size) {
            return null;
        }

        $file = File::get($this->file);

        preg_match_all($this->pattern->getPattern('logs'), $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($this->pattern->getPattern('logs'), $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach ($this->level->all() as $level) {
                    if (strpos(strtolower($h[$i]), '.' . $level) || strpos(strtolower($h[$i]), $level . ':')) {
                        preg_match($this->pattern->getPattern('current_log', 0) . $level .
                            $this->pattern->getPattern('current_log', 1), $h[$i], $current);
                        if (!isset($current[4])) {
                            continue;
                        }

                        $log[] = [
                            'context' => $current[3],
                            'level' => $level,
                            'folder' => $this->folder,
                            'level_class' => $this->level->cssClass($level),
                            'level_img' => $this->level->img($level),
                            'date' => $current[1],
                            'text' => $current[4],
                            'in_file' => isset($current[5]) ? $current[5] : null,
                            'stack' => preg_replace("/^\n*/", '', $log_data[$i])
                        ];
                    }
                }
            }
        }

        if (empty($log)) {
            $lines = explode(PHP_EOL, $file);
            $log = [];

            foreach ($lines as $key => $line) {
                $log[] = [
                    'context' => '',
                    'level' => '',
                    'folder' => '',
                    'level_class' => '',
                    'level_img' => '',
                    'date' => $key + 1,
                    'text' => $line,
                    'in_file' => null,
                    'stack' => '',
                ];
            }
        }

        return array_reverse($log);
    }

    /**
     * @return array
     */
    public function getFolders() {
        $folders = glob($this->storage_path . '/*', GLOB_ONLYDIR);

        if (is_array($folders)) {
            foreach ($folders as $k => $folder) {
                $folders[$k] = basename($folder);
            }
        }
        return array_values($folders);
    }

    /**
     * @param bool $basename
     *
     * @return array
     */
    public function getFolderFiles($basename = false) {
        return $this->getFiles($basename, $this->folder);
    }

    /**
     * @param bool   $basename
     * @param string $folder
     *
     * @return array
     */
    public function getFiles($basename = false, $folder = '') {
        $pattern = function_exists('config') ? config('logviewer.pattern', '*.log') : '*.log';

        $files = glob(
            $this->storage_path . '/' . $folder . '/' . $pattern,
            preg_match($this->pattern->getPattern('files'), $pattern) ? GLOB_BRACE : 0);

        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');
        if ($basename && is_array($files)) {
            foreach ($files as $k => $file) {
                $files[$k] = basename($file);
            }
        }
        return array_values($files);
    }
}
