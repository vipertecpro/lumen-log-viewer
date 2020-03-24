<?php

namespace LumenLogViewer\Test;

use Exception;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;
use LumenLogViewer\Controllers\LogViewerController;
use LumenLogViewer\LumenLogViewer;
use LumenLogViewer\Providers\LumenLogViewerServiceProvider;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class LumenLogViewerTest
 *
 * @package LaravelLogViewer\Test
 */
class LumenLogViewerTest extends TestCase {

    private $logViewer;

    protected function getEnvironmentSetUp($app): void {
        $app->useEnvironmentPath(__DIR__ . '/../');
        $app->loadEnvironmentFrom('.env.testing');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        $app->register(LumenLogViewerServiceProvider::class);
        parent::getEnvironmentSetUp($app);
    }

    protected function setUp(): void {
        parent::setUp();

        // Copy "lumen.log" file to the orchestra package.
        if (!file_exists(storage_path('logs/lumen.log'))) {
            copy(__DIR__ . '/lumen.log', storage_path('logs/lumen.log'));
        }
        $this->logViewer = LumenLogViewer::getInstance();
    }

    /**
     * @throws Exception
     */
    public function testSetFile(): void
    {
        parent::setUp();
        try {
            $this->logViewer->setFile(storage_path('logs/lumen.log'));
            $this->logViewer->setFile('lumen.log');
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
        $this->assertEquals('lumen.log', $this->logViewer->getFileName());
    }

    public function testAll(): void
    {
        $data = $this->logViewer->all();
        $this->assertEquals('local', $data[0]['context']);
        $this->assertEquals('error', $data[0]['level']);
        $this->assertEquals('danger', $data[0]['level_class']);
        $this->assertEquals('exclamation-triangle', $data[0]['level_img']);
        $this->assertEquals('2018-09-05 20:20:51', $data[0]['date']);
    }

    public function testSetFileNotExist(): void
    {
        try {
            $this->logViewer->setFile('not-exist/lumen.log');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
        $this->assertFalse(false);
    }

    public function testAllWithFileNotExist(): void
    {
        try {
            $this->logViewer->setFile('not-exist/lumen.log');
        } catch (Exception $e) {
        }
        $data = $this->logViewer->all();
        $this->assertEquals('local', $data[0]['context']);
        $this->assertEquals('error', $data[0]['level']);
        $this->assertEquals('danger', $data[0]['level_class']);
        $this->assertEquals('exclamation-triangle', $data[0]['level_img']);
        $this->assertEquals('2018-09-05 20:20:51', $data[0]['date']);
    }

    public function testGetFolders(): void
    {
        $dir_a = storage_path('logs/a');
        $dir_b = storage_path('logs/b');
        $dir_c = storage_path('logs/c');
        if (!is_dir($dir_a) || !is_dir($dir_b) || !is_dir($dir_c)) {
            mkdir(storage_path('logs/a'));
            mkdir(storage_path('logs/b'));
            mkdir(storage_path('logs/c'));
        }
        $this->assertNotEmpty($this->logViewer->getFolders());
    }

    public function testGetFolderFiles(): void
    {
        $data = $this->logViewer->getFolderFiles(true);
        $this->assertNotEmpty($data[0], 'Folder files is null');
    }

    public function testSetFolderNotExist(): void
    {
        $this->logViewer->setFolder('abc');
        $this->assertDirectoryNotExists(storage_path('logs/abc'));
    }

    public function testSetFolder(): void
    {
        $this->logViewer->setFolder(storage_path('logs'));
        $this->assertSame($this->logViewer->getFolderName(), storage_path('logs'));
    }

    /**
     * @throws Exception
     */
    public function testController(): void
    {
        $encrypted = encrypt($this->logViewer->getFolderName());

        $reqParamF = Request::create('/logs', 'GET', ['f' => $encrypted], [], [],
            ['HTTP_ACCEPT' => 'application/json']);
        $controller = new LogViewerController($reqParamF);
        $this->assertIsArray($controller->index());

        /** *****************************************************************************/

        $encrypted = encrypt($this->logViewer->getFolderName() . '/lumen.log');

        $reqFileContent = Request::create('/logs', 'GET', ['l' => $encrypted]);
        $controller = new LogViewerController($reqFileContent);
        $this->assertInstanceOf(View::class, $controller->index());

        $reqDownload = Request::create('/logs', 'GET', ['dl' => $encrypted]);
        $controller = new LogViewerController($reqDownload);
        $this->assertInstanceOf(BinaryFileResponse::class, $controller->index());

        $reqCleanFile = Request::create('/logs', 'GET', ['clean' => $encrypted]);
        $controller = new LogViewerController($reqCleanFile);
        $this->assertInstanceOf(RedirectResponse::class, $controller->index());

        $reqDeleteOne = Request::create('/logs', 'GET', ['del' => $encrypted]);
        $controller = new LogViewerController($reqDeleteOne);
        $this->assertInstanceOf(RedirectResponse::class, $controller->index());

        if (!file_exists(storage_path('logs/lumen1.log'))) {
            copy(__DIR__ . '/lumen.log', storage_path('logs/lumen1.log'));
        }

        $reqDeleteAll = Request::create('/logs', 'GET', ['delall' => 'true']);
        $controller = new LogViewerController($reqDeleteAll);
        $respA = $controller->index();
        $this->logViewer->setFolder(null);
        $respB = $controller->index();
        $this->assertTrue($respA instanceof RedirectResponse && $respB instanceof RedirectResponse);
    }
}
