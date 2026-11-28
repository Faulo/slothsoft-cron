<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use DOMNodeList;
use DOMXPath;
use Exception;
use Slothsoft\Core\Calendar\Seconds;
use Slothsoft\Core\FileSystem;
use Slothsoft\Core\Storage;

abstract class AbstractCronWork extends AbstractWorkThread {
    
    protected function getName(): string {
        return $this->getOption('name');
    }
    
    /** @param string|DOMXPath $sourceURI */
    protected function downloadXPath($sourceURI): ?DOMXPath {
        if ($sourceURI instanceof DOMXPath) {
            return $sourceURI;
        }
        try {
            return Storage::loadExternalXPath($sourceURI, Seconds::MINUTE);
        } catch (Exception $e) {
            $this->log($e->getMessage(), true);
            return null;
        }
    }
    
    /**
     * @param string|DOMXPath $sourceURI
     * @return DOMNodeList<\DOMNode>|bool|float|string|null
     */
    protected function downloadNode($sourceURI, string $query, ?DOMXPath $xpath = null) {
        $xpath ??= $this->downloadXPath($sourceURI);
        return $xpath === null ? null : $xpath->evaluate($query);
    }
    
    /**
     * @param string|DOMXPath $sourceURI
     * @return iterable<int, \DOMNode>
     */
    protected function downloadNodeList($sourceURI, string $query, ?DOMXPath $xpath = null): iterable {
        $nodes = $this->downloadNode($sourceURI, $query, $xpath);
        if ($nodes instanceof DOMNodeList) {
            yield from $nodes;
        }
    }
    
    /** @param string|DOMXPath $sourceURI */
    protected function downloadString($sourceURI, string $query, ?DOMXPath $xpath = null): string {
        $value = $this->downloadNode($sourceURI, "string($query)", $xpath);
        return is_string($value) ? $value : '';
    }
    
    /**
     * @param string|DOMXPath $sourceURI
     * @return iterable<int, bool|float|string>
     */
    protected function downloadStringList($sourceURI, string $query, ?DOMXPath $xpath = null): iterable {
        $xpath ??= $this->downloadXPath($sourceURI);
        if ($xpath === null) {
            return;
        }
        $result = $this->downloadNode($sourceURI, $query, $xpath);
        if ($result instanceof DOMNodeList) {
            foreach ($result as $node) {
                yield $xpath->evaluate('string(.)', $node);
            }
        } elseif ($result !== null) {
            yield $result;
        }
    }
    
    /** @param string $sourceURI */
    protected function downloadURI($sourceURI, string $query, ?DOMXPath $xpath = null): string {
        $uri = $this->downloadString($sourceURI, $query, $xpath);
        return $uri === '' ? '' : $this->_fixURI($uri, $sourceURI);
    }
    
    /**
     * @param string $sourceURI
     * @return iterable<int, string>
     */
    protected function downloadURIList($sourceURI, string $query, ?DOMXPath $xpath = null): iterable {
        $uriList = $this->downloadStringList($sourceURI, $query, $xpath);
        foreach ($uriList as $uri) {
            if (is_string($uri) && $uri !== '') {
                yield $this->_fixURI($uri, $sourceURI);
            }
        }
    }
    
    protected function _fixFilename(string $name, ?string $ext = ''): string {
        return $ext === '' ? FileSystem::filenameSanitize($name) : sprintf('%s.%s', FileSystem::filenameSanitize($name), $ext);
    }
    
    protected function _fixURI(string $uri, string $sourceURI): string {
        if (substr($uri, 0, 2) === '//') {
            return 'http:' . $uri;
        }
        if ($uri === '' || strpos($uri, '://') !== false) {
            return $uri;
        }
        $sourceParam = parse_url($sourceURI);
        if (! isset($sourceParam['scheme']) || (! isset($sourceParam['host']) && $sourceParam['scheme'] !== 'file')) {
            return $uri;
        }
        $host = $sourceParam['host'] ?? '';
        if (isset($sourceParam['port'])) {
            $host .= ':' . $sourceParam['port'];
        }
        if ($uri[0] !== '/') {
            if (strpos($uri, './') === 0) {
                $uri = substr($uri, 2);
            }
            $path = $sourceParam['path'] ?? '/';
            $lastSlash = strrpos($path, '/');
            $directory = $lastSlash === false ? '/' : substr($path, 0, $lastSlash + 1);
            $uri = $directory . $uri;
        }
        return sprintf('%s://%s%s', $sourceParam['scheme'], $host, $uri);
    }
    
    /** @return mixed */
    protected function _eval(string $code) {
        return eval($code);
    }
}
