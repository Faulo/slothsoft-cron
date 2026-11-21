<?php
declare(strict_types = 1);

namespace Slothsoft\Cron\Work;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class AbstractCronWorkTest extends TestCase {
    
    public function testClassExists(): void {
        $this->assertTrue(class_exists(AbstractCronWork::class), "Failed to load class 'Slothsoft\Cron\Work\AbstractCronWork'!");
    }
    
    public function testXPathDownloadHandlesMissingResults(): void {
        $document = new DOMDocument();
        $document->loadXML('<page><image>cover.png</image></page>');
        $xpath = new DOMXPath($document);
        $work = new class([]) extends AbstractCronWork {
            public function findImage(DOMXPath $xpath, string $query): string {
                return $this->downloadURI('https://example.test:8443', $query, $xpath);
            }
            
            public function countNodes(DOMXPath $xpath, string $query): int {
                return count([...$this->downloadNodeList($xpath, $query)]);
            }
            
            protected function work(): void {
            }
        };
        
        $this->assertSame('https://example.test:8443/cover.png', $work->findImage($xpath, '//image'));
        $this->assertSame('', $work->findImage($xpath, '//missing'));
        $this->assertSame(1, $work->countNodes($xpath, '//image'));
        $this->assertSame(0, $work->countNodes($xpath, 'count(//image)'));
    }
}
