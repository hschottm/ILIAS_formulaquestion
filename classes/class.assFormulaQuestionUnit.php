<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

/**
* Formula Question Unit
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.assFormulaQuestionUnit.php 404 2009-04-27 04:56:49Z hschottm $
* @ingroup ModulesTestQuestionPool
* */
class assFormulaQuestionUnit
{
	private $unit;
	private $factor;
	private $baseunit;
	private $id;
	private $category;
	private $sequence;
	
	/**
	* assFormulaQuestionUnit constructor
	*
	* @param int $id Unit id
	* @param string $unit Unit name
	* @param double $factor Multiplication factor with base unit
	* @param object $baseunit Base Unit
	* @param string $category Category name
	* @param int $sequence Sequence
	* @access public
	*/
	function __construct($id, $unit, $factor = 1, $baseunit = null, $category = "no_category", $sequence = 0) 
	{
		$this->id = $id;
		$this->unit = $unit;
		$this->factor = $factor;
		$this->baseunit = $baseunit;
		$this->category = $category;
		$this->sequence = $sequence;
	}
	
	/************************************
	* Getter and Setter
	************************************/
	
	function setId($id)
	{
		$this->id = $id;
	}

	function getId()
	{
		return $this->id;
	}

	function setUnit($unit)
	{
		$this->unit = $unit;
	}

	function getUnit()
	{
		return $this->unit;
	}

	function setSequence($sequence)
	{
		$this->sequence = $sequence;
	}

	function getSequence()
	{
		return $this->sequence;
	}

	function setFactor($factor)
	{
		$this->factor = $factor;
	}

	function getFactor()
	{
		return $this->factor;
	}

	function setBaseUnit($baseunit)
	{
		if (is_numeric($baseunit) && $baseunit > 0)
		{
			$this->baseunit = $baseunit;
		}
		else
		{
			$this->baseunit = null;
		}
	}

	function getBaseUnit()
	{
		if (is_numeric($this->baseunit) && $this->baseunit > 0)
		{
			return $this->baseunit;
		}
		else
		{
			return $this->id;
		}
	}
	
	function setCategory($category)
	{
		$this->category = $category;
	}
	
	function getCategory()
	{
		return $this->category;
	}

	function getDisplayString()
	{
		return $this->getUnit();
	}

	function getIdString()
	{
		return "unit_value_" . $this->getId();
	}
}
?>
