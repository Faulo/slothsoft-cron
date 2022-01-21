<?php
declare(strict_types = 1);
namespace Slothsoft\Cron\Work;

use Slothsoft\Core\FileSystem;
use Slothsoft\Core\Storage;
use Slothsoft\Core\Calendar\Seconds;
use Slothsoft\Farah\PThreads\AbstractWorkThread;
use DOMXPath;
use Exception;

abstract class AbstractCronWork extends AbstractWorkThread
{
    protected function getName() : string {
        return $this->getOption('name');
    }
    protected function downloadXPath($sourceURI) : DOMXPath
    {
        if ($sourceURI instanceof DOMXPath) {
            return $sourceURI;
        }
        $ret = null;
        try {
            if ($xpath = Storage::loadExternalXPath($sourceURI, Seconds::MINUTE)) {
                $ret = $xpath;
            }
        } catch (Exception $e) {
            $this->log($e->getMessage(), true);
        }
        return $ret;
    }

    protected function downloadNode($sourceURI, string $query, ?DOMXPath $xpath = null)
    {
        $ret = null;
        if (! $xpath) {
            $xpath = $this->downloadXPath($sourceURI);
        }
        if ($xpath) {
            $ret = $xpath->evaluate($query);
        }
        return $ret;
    }

    protected function downloadNodeList($sourceURI, string $query, ?DOMXPath $xpath = null) : iterable
    {
        foreach ($this->downloadNode($sourceURI, $query, $xpath) as $node) {
            yield $node;
        }
    }

    protected function downloadString($sourceURI, string $query, ?DOMXPath $xpath = null) : string
    {
        $ret = '';
        if (! $xpath) {
            $xpath = $this->downloadXPath($sourceURI);
        }
        if ($xpath) {
            $ret = $xpath->evaluate("string($query)");
        }
        return $ret;
    }

    protected function downloadStringList($sourceURI, string $query, ?DOMXPath $xpath = null) : iterable
    {
        if (! $xpath) {
            $xpath = $this->downloadXPath($sourceURI);
        }
        if ($xpath) {
            $nodeList = $xpath->evaluate($query);
            if (is_object($nodeList)) {
                foreach ($nodeList as $node) {
                    yield $xpath->evaluate('string(.)', $node);
                }
            } else {
                yield $nodeList;
            }
        }
    }

    protected function downloadURI($sourceURI, string $query, ?DOMXPath $xpath = null) : string
    {
        $ret = null;
        $uri = $this->downloadString($sourceURI, $query, $xpath);
        if (strlen($uri)) {
            $ret = $this->_fixURI($uri, $sourceURI);
        }
        return $ret;
    }

    protected function downloadURIList($sourceURI, string $query, ?DOMXPath $xpath = null) : iterable
    {
        $uriList = $this->downloadStringList($sourceURI, $query, $xpath);
        foreach ($uriList as $uri) {
            if (strlen($uri)) {
                yield $this->_fixURI($uri, $sourceURI);
            }
        }
    }

    protected function _fixFilename(string $name, ?string $ext = '') : string
    {
        return $ext === '' ? FileSystem::filenameSanitize($name) : sprintf('%s.%s', FileSystem::filenameSanitize($name), $ext);
    }

    protected function _fixURI(string $uri, string $sourceURI) : string
    {
        if (substr($uri, 0, 2) === '//') {
            $uri = 'http:' . $uri;
        }
        $ret = $uri;
        $sourceParam = parse_url($sourceURI);
        if (strpos($ret, '://') === false) {
            if (strpos($ret, '/') === 0) {
                $sourceParam['path'] = '';
            }
            if (strpos($ret, './') === 0) {
                $ret = substr($ret, 2);
            }
            if (strlen($sourceParam['path'])) {
                $i = strrpos($sourceParam['path'], '/');
                if ($i !== null) {
                    $sourceParam['path'] = substr($sourceParam['path'], 0, $i + 1);
                }
            }
            $ret = sprintf('%s://%s%s%s', $sourceParam['scheme'], $sourceParam['host'], $sourceParam['path'], $ret);
        }
        return $ret;
    }

    protected function _eval(string $code)
    {
        return eval($code);
    }
}