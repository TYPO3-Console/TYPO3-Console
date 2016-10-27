<?php
namespace Helhum\Typo3Console\Service\Database\Schema;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Mathias Brodala <mbrodala@pagemachine.de>
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

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * List of database schema update types
 */
class SchemaUpdateType extends Enumeration {

	/**
	 * @var int
	 */
	protected $value;

	/**
	 * Add a field
	 */
	const FIELD_ADD = 'field.add';

	/**
	 * Change a field
	 */
	const FIELD_CHANGE = 'field.change';

	/**
	 * Prefix a field
	 */
	const FIELD_PREFIX = 'field.prefix';

	/**
	 * Drop a field
	 */
	const FIELD_DROP = 'field.drop';

	/**
	 * Add a table
	 */
	const TABLE_ADD = 'table.add';

	/**
	 * Change a table
	 */
	const TABLE_CHANGE = 'table.change';

	/**
	 * Prefix a table
	 */
	const TABLE_PREFIX = 'table.prefix';

	/**
	 * Drop a table
	 */
	const TABLE_DROP = 'table.drop';

	/**
	 * Truncate a table
	 */
	const TABLE_CLEAR = 'table.clear';
}
