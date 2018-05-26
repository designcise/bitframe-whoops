<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @author    Franz Liedke
 * @copyright Copyright (c) 2015-2017 Franz Liedke
 *
 * @license   https://github.com/designcise/bitframe-whoops/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\Test;

use \PHPUnit\Framework\TestCase;

use \BitFrame\ErrorHandler\FormatNegotiator;
use \BitFrame\Factory\HttpMessageFactory;

/**
 * @covers \BitFrame\ErrorHandler\FormatNegotiator
 */
class FormatNegotiatorTest extends TestCase
{
    public function testRequestsWithoutAcceptHeaderReturnsHtml()
    {
        $request = HttpMessageFactory::createServerRequest();
        $format = FormatNegotiator::getPreferredFormat($request);

        $this->assertEquals('html', $format);
    }

    /**
     * @dataProvider knownTypes
     */
    public function testKnownMimetypesWillReturnPreferredFormat($mimeType, $expectedFormat)
    {
        $format = FormatNegotiator::getPreferredFormat(
            $this->makeRequestWithAccept($mimeType)
        );

        $this->assertEquals($expectedFormat, $format);
    }

    public function knownTypes()
    {
        return [
            ['text/html', 'html'],
            ['application/xhtml+xml', 'html'],
            ['application/json', 'json'],
            ['text/json', 'json'],
            ['application/x-json', 'json'],
            ['text/xml', 'xml'],
            ['application/xml', 'xml'],
            ['application/x-xml', 'xml'],
            ['text/plain', 'txt'],
        ];
    }

    private function makeRequestWithAccept($acceptHeader)
    {
        $request = HttpMessageFactory::createServerRequest();

        return $request->withHeader('accept', $acceptHeader);
    }
}
