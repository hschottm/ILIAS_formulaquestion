<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General public function License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General public function License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General public function License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

/**
* Base class for GUI components
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.GUIComponent.php 404 2009-04-27 04:56:49Z hschottm $
*/
abstract class GUIComponent
{
	private $class;
	private $id;
	private $style;
	private $title;
	
	/**
	* Constructor
	*/
	function __construct() 
	{
		$this->class = null;
		$this->id = null;
		$this->style = null;
		$this->title = null;
	}

	/**
	* Destructor
	*/
	function __destruct() 
	{
	}
	
	abstract protected function getTemplate();

	public function __get($binding)
	{
		$methodnames = array($binding, "get" . ucfirst($binding), "_" . $binding, "_get" . ucfirst($binding));
		foreach ($methodnames as $methodname)
		{
			if (method_exists($this, $methodname))
			{
				return $this->$methodname;
			}
		}
		return null;
	}

	public function __set($binding, $value)
	{
		$methodnames = array($binding, "get" . ucfirst($binding), "_" . $binding, "_get" . ucfirst($binding));
		foreach ($methodnames as $methodname)
		{
			if (method_exists($this, $methodname))
			{
				$this->$methodname($value);
			}
		}
	}

	public function __isset($binding)
	{
		$methodnames = array($binding, "get" . ucfirst($binding), "_" . $binding, "_get" . ucfirst($binding));
		foreach ($methodnames as $methodname)
		{
			if (method_exists($this, $methodname))
			{
				return is_null($this->$methodname());
			}
		}
	}

	public function __unset($binding)
	{
		$methodnames = array($binding, "get" . ucfirst($binding), "_" . $binding, "_get" . ucfirst($binding));
		foreach ($methodnames as $methodname)
		{
			if (method_exists($this, $methodname))
			{
				$this->$methodname(null);
			}
		}
	}
	
	public function setClass($class)
	{
		$this->class = $class;
	}
	
	public function getClass()
	{
		return $this->class;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setStyle($style)
	{
		$this->style = $style;
	}
	
	public function getStyle()
	{
		return $this->style;
	}
	
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
}
