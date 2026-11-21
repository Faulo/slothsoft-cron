<?php
declare(strict_types = 1);

namespace Slothsoft\Cron;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Core\IO\Readable\DOMReaderInterface;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use LogicException;

class InstructionsParser implements DOMReaderInterface {
    
    private DOMElement $sourceNode;
    
    public function fromDocument(DOMDocument $sourceDoc): void {
        $root = $sourceDoc->documentElement;
        if ($root === null) {
            throw new InvalidArgumentException('The cron instructions document has no root element.');
        }
        $this->fromElement($root);
    }
    
    public function fromElement(DOMElement $sourceElement): void {
        $this->sourceNode = $sourceElement;
    }
    
    /** @return iterable<int, array<string, string>> */
    public function getUpdateInstructions(): iterable {
        $document = $this->sourceNode->ownerDocument;
        if ($document === null) {
            throw new LogicException('The cron instructions element has no owner document.');
        }
        $xpath = DOMHelper::loadXPath($document, DOMHelper::XPATH_SLOTHSOFT);
        
        foreach ($xpath->evaluate('descendant-or-self::sci:update', $this->sourceNode) as $updateNode) {
            $options = [];
            do {
                foreach ($updateNode->attributes as $attr) {
                    if (! isset($options[$attr->name])) {
                        $options[$attr->name] = $attr->value;
                    }
                }
                if ($php = $xpath->evaluate('string(sci:php)', $updateNode)) {
                    $options['success-php'] = $php;
                }
                if ($blacklist = $xpath->evaluate('string(sci:blacklist)', $updateNode)) {
                    $options['blacklist'] = $blacklist;
                }
            } while ($updateNode = $updateNode->parentNode and $updateNode->nodeType === XML_ELEMENT_NODE);
            if (($options['active'] ?? null) === '1') {
                yield $options;
            }
        }
    }
}

