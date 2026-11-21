<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Assets;

use PHPUnit\Framework\TestCase;
use Slothsoft\Farah\FarahUrl\FarahUrl;
use Slothsoft\Farah\FarahUrl\FarahUrlArguments;
use Slothsoft\Farah\Module\Asset\AssetInterface;
use Slothsoft\Farah\Module\Executable\Executable;
use InvalidArgumentException;

class RunBuilderTest extends TestCase {
    
    /**
     *
     * @var AssetInterface
     */
    private $exampleAsset;
    
    /**
     *
     * @var FarahUrlArguments
     */
    private $exampleArgs;
    
    /**
     *
     * @var RunBuilder
     */
    private $sut;
    
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
    
    public function testTextResult(): void {
        $executableStrategies = $this->sut->buildExecutableStrategies($this->exampleAsset, $this->exampleArgs);
        $executable = new Executable($this->exampleAsset, $this->exampleArgs, $executableStrategies);
        $result = $executable->lookupDefaultResult();
        $actual = $result->lookupStringWriter()->toString();
        
        $this->assertStringContainsString('Cron Test', $actual);
    }
}

