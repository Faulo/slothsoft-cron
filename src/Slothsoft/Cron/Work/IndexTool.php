<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;


class IndexTool extends AbstractCronWork
{

    protected function work(): void
    {
        $ret = [];
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
                        $ret[] = $options;
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
        // $this->log(sprintf('Prepared to download %d files for %s!', count($ret), $options['name']));
        return $ret;
    }

    
}