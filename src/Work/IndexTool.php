<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

class IndexTool extends AbstractCronWork {

    protected function work(): void {
        $options = $this->getOptions();

        $sourceURI = $options['source-uri'];
        if (isset($options['source-xpath'])) {
            $sourceXPathList = [
                $options['source-xpath']
            ];
        } else {
            $sourceXPathList = [];
            for ($i = 0; $i < 10; $i ++) {
                if (isset($options['source-xpath-' . $i])) {
                    $sourceXPathList[] = $options['source-xpath-' . $i];
                }
            }
        }
        $options['type'] = 'file';
        while (count($sourceXPathList)) {
            $sourceXPath = array_shift($sourceXPathList);
            $this->log(sprintf('Checking website "%s"...', $sourceURI));
            if ($xpath = $this->downloadXPath($sourceURI)) {
                if ($uri = $this->downloadURI($sourceURI, $sourceXPath, $xpath)) {
                    if (count($sourceXPathList)) {
                        $sourceURI = $uri;
                    } else {
                        $options['source-uri'] = $uri;
                        $this->thenDo(FetchFile::class, $options);
                    }
                } else {
                    $this->log(sprintf('Could not find URL at %s (%s) ???', $sourceURI, $sourceXPath), true);
                    break;
                }
            } else {
                $this->log(sprintf('Could not find XML document at %s ???', $sourceURI), true);
                break;
            }
        }
    }
}