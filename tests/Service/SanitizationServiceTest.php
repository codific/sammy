<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\AssessmentStream;
use App\Service\SanitizerService;
use App\Tests\_support\AbstractKernelTestCase;

class SanitizationServiceTest extends AbstractKernelTestCase
{
    /**
     * @group asvs
     * @testdox ASVS 5.2.1 - $_dataName
     */
    public function testSanitizersAreSanitizingHTMLInput(): void
    {
        $htmlSanitizer = static::getContainer()->get("html_sanitizer");
        $liberalSanitizer = static::getContainer()->get("html_sanitizer.sanitizer.liberal_sanitizer");
        $sanitizerService = new SanitizerService($htmlSanitizer, $liberalSanitizer);

        $maliciousInput = "<a href=www.attacker.site>malicious link</a><script>alert('evil alert')</script>";
        $expectedInputAfterSanitizationStrict = "<a>malicious link</a>";
        $expectedInputAfterSanitizationNonStrict = "<a href=\"www.attacker.site\">malicious link</a>";
        $expectedInputAfterEntitySanitizationNonStrict = "<a>malicious link</a>";

        $resultAfterSanitizationByValueStrict = $sanitizerService->sanitizeValue($maliciousInput, SanitizerService::STRICT);
        $resultAfterSanitizationByValueLiberal = $sanitizerService->sanitizeValue($maliciousInput, SanitizerService::LIBERAL);
        $resultAfterSanitizationByEntityValue = $sanitizerService->sanitizeEntityValue($maliciousInput, "comment", new AssessmentStream());

        self::assertEquals($expectedInputAfterSanitizationStrict, $resultAfterSanitizationByValueStrict);
        self::assertEquals($expectedInputAfterSanitizationNonStrict, $resultAfterSanitizationByValueLiberal);
        self::assertEquals($expectedInputAfterEntitySanitizationNonStrict, $resultAfterSanitizationByEntityValue);
    }
}