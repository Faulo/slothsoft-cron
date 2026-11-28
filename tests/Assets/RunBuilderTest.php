<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Assets;

use DOMDocument;
use FilesystemIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Slothsoft\Core\ServerEnvironment;
use Slothsoft\Farah\FarahUrl\FarahUrl;
use Slothsoft\Farah\FarahUrl\FarahUrlArguments;
use Slothsoft\Farah\Module\Asset\AssetInterface;
use Slothsoft\Farah\Module\Module;
use SplFileInfo;

final class RunBuilderTest extends TestCase {
    public function testMissingParameterException(): void {
        $asset = $this->createMock(AssetInterface::class);
        $this->expectException(InvalidArgumentException::class);
        (new RunBuilder())->buildExecutableStrategies($asset, FarahUrlArguments::createEmpty());
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunAssetStreamsLogsAndProducesJobOutput(): void {
        $directory = temp_dir('cron-run-test');
        $destination = $directory . DIRECTORY_SEPARATOR . 'result.txt';
        $script = dirname(__DIR__, 2) . '/test-files/cron-run.php';
        ServerEnvironment::setCacheDirectory($directory);
        ServerEnvironment::setLogDirectory($directory);
        ServerEnvironment::setDataDirectory($directory);
        try {
            file_put_contents($directory . '/manifest.xml', <<<'XML'
                <assets xmlns="http://schema.slothsoft.net/farah/module" version="1.1">
                    <resource name="instructions" type="application/xml" />
                </assets>
                XML
            );
            $namespace = 'http://schema.slothsoft.net/cron/instructions';
            $instructions = new DOMDocument();
            $root = $instructions->createElementNS($namespace, 'instructions');
            $root->setAttribute('version', '0.1');
            $instructions->appendChild($root);
            $job = $instructions->createElementNS($namespace, 'update');
            $root->appendChild($job);
            $attributes = [
                'type' => 'php',
                'active' => '1',
                'name' => 'Cron fixture',
                'source-uri' => $script,
                'dest-path' => $destination,
                'copy-cmd' => '',
                'success-cmd' => '',
                'success-php' => ''
            ];
            foreach ($attributes as $name => $value) {
                $job->setAttribute($name, $value);
            }
            $instructions->save($directory . '/instructions.xml');
            Module::registerWithXmlManifestAndDefaultAssets('slothsoft@cron-test', $directory);
            $url = FarahUrl::createFromReference('farah://slothsoft@cron/run?ref=' . rawurlencode('farah://slothsoft@cron-test/instructions'));
            $result = Module::resolveToResult($url);
            self::assertSame('work.txt', $result->lookupFileName());
            self::assertSame('text/plain', $result->lookupMimeType());
            $chunks = iterator_to_array($result->lookupChunkWriter()->toChunks(), false);
            self::assertCount(2, $chunks);
            self::assertStringContainsString('Prepared to execute Cron fixture!', $chunks[0]);
            self::assertStringContainsString('Updated file', $chunks[1]);
            self::assertSame('Cron fixture output', file_get_contents($destination));
        } finally {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file instanceof SplFileInfo) {
                    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
            }
            rmdir($directory);
        }
    }
}
