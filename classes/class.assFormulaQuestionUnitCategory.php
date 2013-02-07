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
* Formula Question Unit Category
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.assFormulaQuestionUnitCategory.php 404 2009-04-27 04:56:49Z hschottm $
* @ingroup ModulesTestQuestionPool
* */
class assFormulaQuestionUnitCategory
{
	private $id;
	private $category;
	
	/**
	* assFormulaQuestionUnitCategory constructor
	*
	* @param int $id Category id
	* @param string $category Category name
	* @access public
	*/
	function __construct($id, $category) 
	{
		$this->id = $id;
		$this->category = $category;
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
		return "id_".$this->id;
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
		return $this->getCategory();
	}
	
	function getClass()
	{
		return "category";
	}
}
?>
