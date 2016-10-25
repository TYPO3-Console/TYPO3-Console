<?php

namespace Helhum\Typo3Console\Tests\Unit\Parser;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Parser;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class PhpParserTest.
 */
class PhpParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parsingClassFileReturnsParsedClass()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClassFile(__DIR__.'/Fixtures/NamespacedClassFixture.php');
        $this->assertInstanceOf(\Helhum\Typo3Console\Parser\ParsedClass::class, $result);
    }

    /**
     * @test
     * @expectedException \Helhum\Typo3Console\Parser\ParsingException
     */
    public function parserThrowsExceptionIfClassFileIsNotFound()
    {
        $subject = new Parser\PhpParser();
        $subject->parseClassFile('doesNotExist');
    }

    /**
     * @test
     */
    public function parsedResultCorrectlySetsNamespaceOfParsedClass()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClassFile(__DIR__.'/Fixtures/NamespacedClassFixture.php');
        $this->assertSame('Helhum\\Typo3Console\\Tests\\Unit\\Parser\\Fixtures', $result->getNamespace());
    }

    /**
     * @test
     */
    public function parsedResultCorrectlySetsClassNameOfParsedClass()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClassFile(__DIR__.'/Fixtures/NamespacedClassFixture.php');
        $this->assertSame('NamespacedClassFixture', $result->getClassName());
    }

    /**
     * @test
     */
    public function parsedResultCorrectlyFindsInterfaceNameOfParsedClass()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClassFile(__DIR__.'/Fixtures/NamespacedInterfaceFixture.php');
        $this->assertSame('NamespacedInterfaceFixture', $result->getClassName());
        $this->assertTrue($result->isInterface());
    }

    /**
     * @test
     */
    public function parsedResultCorrectlySetsNamespaceSeparatorOfParsedClass()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClassFile(__DIR__.'/Fixtures/NamespacedClassFixture.php');
        $this->assertSame('\\', $result->getNamespaceSeparator());
    }

    /**
     * @test
     */
    public function parsedResultCorrectlySetsFullyQualifiedClassNameOfParsedClass()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClassFile(__DIR__.'/Fixtures/NamespacedClassFixture.php');
        $this->assertSame(\Helhum\Typo3Console\Tests\Unit\Parser\Fixtures\NamespacedClassFixture::class, $result->getFullyQualifiedClassName());
    }

    /**
     * @test
     * @expectedException \Helhum\Typo3Console\Parser\ParsingException
     */
    public function parserThrowsExceptionIfNoClassDefinitionIsFound()
    {
        $subject = new Parser\PhpParser();
        $subject->parseClass('xclsa');
    }

    /**
     * @test
     */
    public function parserDetectsAbstractClassDefinition()
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClass('	 abstract 	 class Foo 	 {');
        $this->assertTrue($result->isAbstract());
    }

    /**
     * @return array
     */
    public function nonNamespacedClassesDataProvider()
    {
        return [
            'normal class'      => ['class Tx_Ext_Bar {', ['className' => 'Bar', 'namespace' => 'Tx_Ext', 'separator' => '_', 'full' => 'Tx_Ext_Bar']],
            'abstract class'    => ['abstract class Tx_Ext_BarAbstract {', ['className' => 'BarAbstract', 'namespace' => 'Tx_Ext', 'separator' => '_', 'full' => 'Tx_Ext_BarAbstract']],
            'without namespace' => ['class TxExtBar {', ['className' => 'TxExtBar', 'namespace' => '', 'separator' => '', 'full' => 'TxExtBar']],
        ];
    }

    /**
     * @param string $classContent
     * @param array  $expectedResults
     * @test
     * @dataProvider nonNamespacedClassesDataProvider
     */
    public function parserCorrectlyParsesClassNameOfNonNamespacedClasses($classContent, array $expectedResults)
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClass($classContent);

        $this->assertSame($expectedResults['className'], $result->getClassName());
    }

    /**
     * @param string $classContent
     * @param array  $expectedResults
     * @test
     * @dataProvider nonNamespacedClassesDataProvider
     */
    public function parserCorrectlyParsesSeparatorOfNonNamespacedClasses($classContent, array $expectedResults)
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClass($classContent);

        $this->assertSame($expectedResults['separator'], $result->getNamespaceSeparator());
    }

    /**
     * @param string $classContent
     * @param array  $expectedResults
     * @test
     * @dataProvider nonNamespacedClassesDataProvider
     */
    public function parserCorrectlyParsesFullyQualifiedClassNameOfNonNamespacedClasses($classContent, array $expectedResults)
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClass($classContent);

        $this->assertSame($expectedResults['full'], $result->getFullyQualifiedClassName());
    }

    /**
     * @param string $classContent
     * @param array  $expectedResults
     * @test
     * @dataProvider nonNamespacedClassesDataProvider
     */
    public function parserCorrectlyParsesNamespaceOfNonNamespacedClasses($classContent, array $expectedResults)
    {
        $subject = new Parser\PhpParser();
        $result = $subject->parseClass($classContent);

        $this->assertSame($expectedResults['namespace'], $result->getNamespace());
    }
}
