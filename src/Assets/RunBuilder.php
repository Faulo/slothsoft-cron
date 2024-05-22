<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Assets;

use Slothsoft\Core\IO\Writable\ChunkWriterInterface;
use Slothsoft\Core\IO\Writable\Delegates\ChunkWriterFromChunkWriterDelegate;
use Slothsoft\Cron\InstructionsParser;
use Slothsoft\Cron\Work\CronWorkManager;
use Slothsoft\Farah\FarahUrl\FarahUrlArguments;
use Slothsoft\Farah\Module\Module;
use Slothsoft\Farah\Module\Asset\AssetInterface;
use Slothsoft\Farah\Module\Asset\ExecutableBuilderStrategy\ExecutableBuilderStrategyInterface;
use Slothsoft\Farah\Module\Executable\ExecutableStrategies;
use Slothsoft\Farah\Module\Executable\ResultBuilderStrategy\ChunkWriterResultBuilder;
use Slothsoft\Farah\FarahUrl\FarahUrl;

class RunBuilder implements ExecutableBuilderStrategyInterface {

    public function buildExecutableStrategies(AssetInterface $context, FarahUrlArguments $args): ExecutableStrategies {
        if (! $args->has('ref')) {
            throw new \InvalidArgumentException();
        }

        $ref = FarahUrl::createFromReference($args->get('ref'), $context->createUrl());

        $delegate = function () use ($ref): ChunkWriterInterface {
            $refWriter = Module::resolveToDOMWriter($ref);

            $parser = new InstructionsParser();
            $parser->fromDocument($refWriter->toDocument());

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

