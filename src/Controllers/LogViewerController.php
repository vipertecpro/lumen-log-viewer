<?php

namespace LumenLogViewer\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt, File, View};
use LumenLogViewer\LumenLogViewer;
use Laravel\Lumen\Routing\Controller as LumenRoutingController;
use Illuminate\Routing\Controller as IlluminateRoutingController;

if (class_exists(LumenRoutingController::class)) {
    class Controller extends LumenRoutingController {
    }
} else {
    class Controller extends IlluminateRoutingController {
    }
}

/**
 * Class LogViewerController
 *
 * @package LumenLogViewer\Controllers
 */
class LogViewerController extends Controller {
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var LumenLogViewer
     */
    private $log_viewer;

    /**
     * @var string
     */
    protected $view_log = 'lumen-log-viewer::logviewer';

    /**
     * LogViewerController constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request) {
        $this->log_viewer = LumenLogViewer::getInstance();
        $this->request = $request;
    }

    /**
     * @return array|mixed
     * @throws Exception
     */
    public function index() {
        $folderFiles = [];

        $params = $this->request->input();
        $paramName = key($params);

        if ($paramName === 'f') {
            $this->log_viewer->setFolder(Crypt::decrypt($params['f']));
            $folderFiles = $this->log_viewer->getFolderFiles(true);
        }

        if ($paramName === 'l') {
            $this->log_viewer->setFile(Crypt::decrypt($params['l']));
        }

        if ($early_return = $this->earlyReturn()) {
            return $early_return;
        }

        $data = [
            'logs' => $this->log_viewer->all(),
            'folders' => $this->log_viewer->getFolders(),
            'current_folder' => $this->log_viewer->getFolderName(),
            'folder_files' => $folderFiles,
            'files' => $this->log_viewer->getFiles(true),
            'current_file' => $this->log_viewer->getFileName(),
            'standardFormat' => true,
        ];

        if ($this->request->wantsJson()) {
            return $data;
        }

        if (is_array($data['logs'])) {
            $firstLog = reset($data['logs']);
            if (!$firstLog['context'] && !$firstLog['level']) {
                $data['standardFormat'] = false;
            }
        }

        return View::make($this->view_log, $data);
    }

    /**
     * @return bool|mixed
     * @throws Exception
     */
    private function earlyReturn() {
        $params = $this->request->input();
        $paramName = key($params);
        if ($paramName === 'f') {
            $this->log_viewer->setFolder(Crypt::decrypt($params['f']));
        }
        switch ($paramName) {
            case 'dl':
                return response()->download($this->pathFromInput('dl'));
            case 'clean':
                File::put($this->pathFromInput('clean'), '');
                return redirect(strtok($this->request->server('HTTP_REFERER'), '?'));
            case 'del':
                File::delete($this->pathFromInput('del'));
                return redirect(strtok($this->request->server('HTTP_REFERER'), '?'));
            case 'delall':
                $files = ($this->log_viewer->getFolderName())
                    ? $this->log_viewer->getFolderFiles(true)
                    : $this->log_viewer->getFiles(true);
                foreach ($files as $file) {
                    File::delete($this->log_viewer->pathToLogFile($file));
                }
                return redirect(strtok($this->request->server('HTTP_REFERER'), '?'));
        }
        return false;
    }

    /**
     * @param string $input_string
     *
     * @return string
     * @throws Exception
     */
    private function pathFromInput($input_string) {
        return $this->log_viewer->pathToLogFile(Crypt::decrypt($this->request->input($input_string)));
    }
}
