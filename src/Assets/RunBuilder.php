<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Assets;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Core\IO\Writable\ChunkWriterInterface;
use Slothsoft\Core\IO\Writable\Delegates\ChunkWriterFromChunkWriterDelegate;
use Slothsoft\Cron\InstructionsParser;
use Slothsoft\Cron\Work\CronWorkManager;
use Slothsoft\Farah\FarahUrl\FarahUrlArguments;
use Slothsoft\Farah\Module\Asset\AssetInterface;
use Slothsoft\Farah\Module\Asset\ExecutableBuilderStrategy\ExecutableBuilderStrategyInterface;
use Slothsoft\Farah\Module\Executable\ExecutableStrategies;
use Slothsoft\Farah\Module\Executable\ResultBuilderStrategy\ChunkWriterResultBuilder;

class RunBuilder implements ExecutableBuilderStrategyInterface {

    public function buildExecutableStrategies(AssetInterface $context, FarahUrlArguments $args): ExecutableStrategies {
        $instructionsFile = $context->createUrl()->withPath('/instructions/update-test');

        $delegate = function () use ($instructionsFile): ChunkWriterInterface {
            $parser = new InstructionsParser();
            $parser->fromDocument(DOMHelper::loadDocument((string) $instructionsFile));

            $manager = new CronWorkManager(16);
            foreach ($parser->getUpdateInstructions() as $options) {
                $className = $manager->getIndexWork($options['type']);
                $manager->thenDo($className, $options);
            }

            return $manager;
        };
        $writer = new ChunkWriterFromChunkWriterDelegate($delegate);
        $resultBuilder = new ChunkWriterResultBuilder($writer, 'work.txt');
        return new ExecutableStrategies($resultBuilder);
    }
}

