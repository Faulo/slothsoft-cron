<?php
declare(strict_types = 1);
namespace Slothsoft\Cron;

use Slothsoft\Core\DOMHelper;
use Slothsoft\Core\IO\Readable\DOMReaderInterface;
use DOMDocument;
use DOMElement;

class InstructionsParser implements DOMReaderInterface {

    private $sourceNode;

    public function fromDocument(DOMDocument $sourceDoc): void {
        $this->fromElement($sourceDoc->documentElement);
    }

    public function fromElement(DOMElement $sourceElement): void {
        $this->sourceNode = $sourceElement;
    }

    public function getUpdateInstructions(): iterable {
        $xpath = DOMHelper::loadXPath($this->sourceNode->ownerDocument, DOMHelper::XPATH_SLOTHSOFT);

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
            if ($options['active'] === '1') {
                yield $options;
            }
        }
    }
}

