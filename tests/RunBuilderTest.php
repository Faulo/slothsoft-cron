<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Tests;

use PHPUnit\Framework\TestCase;
use Slothsoft\Farah\Module\Executable\Executable;
use Slothsoft\Cron\Assets\RunBuilder;
use Slothsoft\Farah\FarahUrl\FarahUrlArguments;
use Slothsoft\Farah\Module\Asset\AssetInterface;
use InvalidArgumentException;
use Slothsoft\Farah\FarahUrl\FarahUrl;

class RunBuilderTest extends TestCase {

    private AssetInterface $exampleAsset;

    private FarahUrlArguments $exampleArgs;

    private RunBuilder $sut;

    protected function setUp(): void {
        $this->exampleAsset = $this->getMockBuilder(AssetInterface::class)->getMock();
        $this->exampleAsset->method('createUrl')->willReturn(FarahUrl::createFromReference('farah://slothsoft@cron/invalid'));

        $this->exampleArgs = FarahUrlArguments::createFromValueList([
            'ref' => '/example-cron'
        ]);

        $this->sut = new RunBuilder();
    }

    public function testMissingParameterException(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->sut->buildExecutableStrategies($this->exampleAsset, FarahUrlArguments::createEmpty());
    }

    public function testExecutableStrategies(): void {
        $strategies = $this->sut->buildExecutableStrategies($this->exampleAsset, $this->exampleArgs);

        $this->assertNotNull($strategies);
    }

    /**
     *
     * @depends      testExecutableStrategies
     */
    public function testDefaultResult(): void {
        $executableStrategies = $this->sut->buildExecutableStrategies($this->exampleAsset, $this->exampleArgs);

        $executable = new Executable($this->exampleAsset, $this->exampleArgs, $executableStrategies);

        $result = $executable->lookupDefaultResult();

        $this->assertNotNull($result);
    }
}

