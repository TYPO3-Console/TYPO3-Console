<?php
namespace Helhum\Typo3Console\Tests\Unit\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Tests\UnitTestCase;
use Helhum\Typo3Console\Parser;

/**
 * Class PhpParserTest
 */
class PhpParserTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function parsingClassFileReturnsParsedClass() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClassFile(__DIR__ . '/Fixtures/NamespacedClassFixture.php');
		$this->assertInstanceOf('Helhum\\Typo3Console\\Parser\\ParsedClass', $result);
	}

	/**
	 * @test
	 * @expectedException \Helhum\Typo3Console\Parser\ParsingException
	 */
	public function parserThrowsExceptionIfClassFileIsNotFound() {
		$subject = new Parser\PhpParser();
		$subject->parseClassFile('doesNotExist');
	}

	/**
	 * @test
	 */
	public function parsedResultCorrectlySetsNamespaceOfParsedClass() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClassFile(__DIR__ . '/Fixtures/NamespacedClassFixture.php');
		$this->assertSame('Helhum\\Typo3Console\\Tests\\Unit\\Parser\\Fixtures', $result->getNamespace());
	}

	/**
	 * @test
	 */
	public function parsedResultCorrectlySetsClassNameOfParsedClass() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClassFile(__DIR__ . '/Fixtures/NamespacedClassFixture.php');
		$this->assertSame('NamespacedClassFixture', $result->getClassName());
	}

	/**
	 * @test
	 */
	public function parsedResultCorrectlyFindsInterfaceNameOfParsedClass() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClassFile(__DIR__ . '/Fixtures/NamespacedInterfaceFixture.php');
		$this->assertSame('NamespacedInterfaceFixture', $result->getClassName());
		$this->assertTrue($result->isInterface());
	}

	/**
	 * @test
	 */
	public function parsedResultCorrectlySetsNamespaceSeparatorOfParsedClass() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClassFile(__DIR__ . '/Fixtures/NamespacedClassFixture.php');
		$this->assertSame('\\', $result->getNamespaceSeparator());
	}

	/**
	 * @test
	 */
	public function parsedResultCorrectlySetsFullyQualifiedClassNameOfParsedClass() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClassFile(__DIR__ . '/Fixtures/NamespacedClassFixture.php');
		$this->assertSame('Helhum\\Typo3Console\\Tests\\Unit\\Parser\\Fixtures\\NamespacedClassFixture', $result->getFullyQualifiedClassName());
	}

	/**
	 * @test
	 * @expectedException \Helhum\Typo3Console\Parser\ParsingException
	 */
	public function parserThrowsExceptionIfNoClassDefinitionIsFound() {
		$subject = new Parser\PhpParser();
		$subject->parseClass('xclsa');
	}

	/**
	 * @test
	 */
	public function parserDetectsAbstractClassDefinition() {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClass('	 abstract 	 class Foo 	 {');
		$this->assertTrue($result->isAbstract());
	}

	/**
	 * @return array
	 */
	public function nonNamespacedClassesDataProvider() {
		return array(
			'normal class' => array('class Tx_Ext_Bar {', array('className' => 'Bar', 'namespace' => 'Tx_Ext', 'separator' => '_', 'full' => 'Tx_Ext_Bar')),
			'abstract class' => array('abstract class Tx_Ext_BarAbstract {', array('className' => 'BarAbstract', 'namespace' => 'Tx_Ext', 'separator' => '_', 'full' => 'Tx_Ext_BarAbstract')),
			'without namespace' => array('class TxExtBar {', array('className' => 'TxExtBar', 'namespace' => '', 'separator' => '', 'full' => 'TxExtBar')),
		);
	}

	/**
	 * @param string $classContent
	 * @param array $expectedResults
	 * @test
	 * @dataProvider nonNamespacedClassesDataProvider
	 */
	public function parserCorrectlyParsesClassNameOfNonNamespacedClasses($classContent, array $expectedResults) {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClass($classContent);

		$this->assertSame($expectedResults['className'], $result->getClassName());
	}


	/**
	 * @param string $classContent
	 * @param array $expectedResults
	 * @test
	 * @dataProvider nonNamespacedClassesDataProvider
	 */
	public function parserCorrectlyParsesSeparatorOfNonNamespacedClasses($classContent, array $expectedResults) {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClass($classContent);

		$this->assertSame($expectedResults['separator'], $result->getNamespaceSeparator());
	}


	/**
	 * @param string $classContent
	 * @param array $expectedResults
	 * @test
	 * @dataProvider nonNamespacedClassesDataProvider
	 */
	public function parserCorrectlyParsesFullyQualifiedClassNameOfNonNamespacedClasses($classContent, array $expectedResults) {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClass($classContent);

		$this->assertSame($expectedResults['full'], $result->getFullyQualifiedClassName());
	}


	/**
	 * @param string $classContent
	 * @param array $expectedResults
	 * @test
	 * @dataProvider nonNamespacedClassesDataProvider
	 */
	public function parserCorrectlyParsesNamespaceOfNonNamespacedClasses($classContent, array $expectedResults) {
		$subject = new Parser\PhpParser();
		$result = $subject->parseClass($classContent);

		$this->assertSame($expectedResults['namespace'], $result->getNamespace());
	}



}
