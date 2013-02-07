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

include_once "./Services/Component/classes/class.ilPlugin.php";
$plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
$plugin->includeClass("class.GUIComponent.php");

/**
* Base class for GUI popup buttons
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.GUIPopUpButton.php 404 2009-04-27 04:56:49Z hschottm $
*/
class GUIPopupButton extends GUIComponent
{
	private $disabled;
	private $multiple;
	private $name;
	private $onblur;
	private $onchange;
	private $onfocus;
	private $size;
	private $tabindex;
	
	private $list;
	private $displayString;
	private $value;
	private $selectedValue;
	private $noSelection;
	private $listItemClassPath;
	private $listItemIdPath;
	
	/**
	* Constructor
	*/
	function __construct() 
	{
		parent::__construct();
		$this->disabled = null;
		$this->multiple = null;
		$this->name = null;
		$this->onblur = null;
		$this->onchange = null;
		$this->onfocus = null;
		$this->size = null;
		$this->tabindex = null;
		
		$this->list = null;
		$this->displayString = null;
		$this->value = null;
		$this->selectedValue = null;
		$this->noSelection = null;
		$this->listItemClassPath = null;
		$this->listItemIdPath = null;
	}

	/**
	* Destructor
	*/
	function __destruct() 
	{
	}
	
	public function setList($list)
	{
		if (is_array($list)) $this->list = $list;
	}
	
	public function setDisplayString($displayString)
	{
		$this->displayString = $displayString;
	}
	
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	public function setSelectedValue($selectedValue)
	{
		$this->selectedValue = $selectedValue;
	}
	
	public function hasSelection()
	{
		$hasSelection = FALSE;
		foreach ($this->list as $item)
		{
			if (is_array($item))
			{
				if (is_array($this->selectedValue))
				{
					if (in_array($item[$this->value], $this->selectedValue)) $hasSelection = TRUE;
				}
				else
				{
					if (strcmp($this->selectedValue, $item[$this->value]) == 0) $hasSelection = TRUE;
				}
			}
			else if (is_object($item))
			{
				$method = "get" . ucfirst($this->value);
				if (is_array($this->selectedValue))
				{
					if (in_array($item->$method(), $this->selectedValue)) $hasSelection = TRUE;
				}
				else
				{
					if (strcmp($this->selectedValue, $item->$method()) == 0) $hasSelection = TRUE;
				}
			}
			else
			{
				if (strcmp($this->selectedValue, $item) == 0) $hasSelection = TRUE;
			}
		}
		return $hasSelection;
	}
	
	public function setNoSelection($noSelection)
	{
		$this->noSelection = $noSelection;
	}
	
	public function setListItemClassPath($listItemClassPath)
	{
		$this->listItemClassPath = $listItemClassPath;
	}
	
	public function setListItemIdPath($listItemIdPath)
	{
		$this->listItemIdPath = $listItemIdPath;
	}
	
	protected function getTemplate()
	{
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
		$template = $plugin->getTemplate("tpl.GUIPopupButton.html");
		return $template;
	}
	
	public function getHTML()
	{
		$template = $this->getTemplate();
		$params = array();
		if (!is_null($this->getClass())) array_push($params, 'class="' . $this->getClass() . '"');
		if (!is_null($this->getId())) array_push($params, 'id="' . $this->getId() . '"');
		if (!is_null($this->getStyle())) array_push($params, 'style="' . $this->getStyle() . '"');
		if (!is_null($this->getTitle())) array_push($params, 'title="' . $this->getTitle() . '"');
		if (!is_null($this->getDisabled())) array_push($params, 'disabled="' . $this->getDisabled() . '"');
		if (!is_null($this->getMultiple())) array_push($params, 'multiple="' . $this->getMultiple() . '"');
		if (!is_null($this->getName())) array_push($params, 'name="' . $this->getName() . '"');
		if (!is_null($this->getOnblur())) array_push($params, 'onblur="' . $this->getOnblur() . '"');
		if (!is_null($this->getOnchange())) array_push($params, 'onchange="' . $this->getOnchange() . '"');
		if (!is_null($this->getOnfocus())) array_push($params, 'onfocus="' . $this->getOnfocus() . '"');
		if (!is_null($this->getSize())) array_push($params, 'size="' . $this->getSize() . '"');
		if (!is_null($this->getTabindex())) array_push($params, 'tabindex="' . $this->getTabindex() . '"');
		$SELECTPARAMS = trim(implode(" ", $params));
		if (!is_null($this->noSelection))
		{
			$template->setCurrentBlock("noselection");
			$template->setVariable("NOSELECTION", $this->noSelection);
			$template->parseCurrentBlock();
		}
		foreach ($this->list as $item)
		{
			$template->setCurrentBlock("option");
			if (is_array($item))
			{
				$itemparams = array();
				array_push($itemparams, 'value="' . $item[$this->value] . '"');
				if (is_array($this->selectedValue))
				{
					if (in_array($item[$this->value], $this->selectedValue)) array_push($itemparams, 'selected="selected"');
				}
				else
				{
					if (strcmp($this->selectedValue, $item[$this->value]) == 0) array_push($itemparams, 'selected="selected"');
				}
				if (!is_null($this->listItemClassPath))
				{
					if (array_key_exists($this->listItemClassPath, $item))
					{
						array_push($itemparams, 'class="' . $item[$this->listItemClassPath] . '"');
					}
				}
				if (!is_null($this->listItemIdPath))
				{
					if (array_key_exists($this->listItemIdPath, $item))
					{
						array_push($itemparams, 'id="' . $item[$this->listItemIdPath] . '"');
					}
				}
				$OPTIONVALUE = $item[$this->displayString];
				$OPTIONPARAMS = trim(implode(" ", $itemparams));
			}
			else if (is_object($item))
			{
				$itemparams = array();
				$method = "get" . ucfirst($this->value);
				array_push($itemparams, 'value="' . $item->$method() . '"');
				if (is_array($this->selectedValue))
				{
					if (in_array($item->$method(), $this->selectedValue)) array_push($itemparams, 'selected="selected"');
				}
				else
				{
					if (strcmp($this->selectedValue, $item->$method()) == 0) array_push($itemparams, 'selected="selected"');
				}
				if (!is_null($this->listItemClassPath))
				{
					$method = "get" . ucfirst($this->listItemClassPath);
					if (method_exists($item, $method))
					{
						array_push($itemparams, 'class="' . $item->$method() . '"');
					}
				}
				if (!is_null($this->listItemIdPath))
				{
					$method = "get" . ucfirst($this->listItemIdPath);
					if (method_exists($item, $method))
					{
						array_push($itemparams, 'id="' . $item->$method() . '"');
					}
				}
				$method = "get" . ucfirst($this->displayString);
				$OPTIONVALUE = $item->$method();
				$OPTIONPARAMS = trim(implode(" ", $itemparams));
			}
			else
			{
				$itemparams = array();
				array_push($itemparams, 'value="' . $item . '"');
				if (strcmp($this->selectedValue, $item) == 0) array_push($itemparams, 'selected="selected"');
				$OPTIONVALUE = $item;
				$OPTIONPARAMS = trim(implode(" ", $itemparams));
			}
			$template->setVariable("OPTIONPARAMS", " " . $OPTIONPARAMS);
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			$template->setVariable("OPTIONVALUE", ilUtil::prepareFormOutput($OPTIONVALUE));
			$template->parseCurrentBlock();
		}
		$template->setVariable("SELECTPARAMS", " " . $SELECTPARAMS);
		return $template->get();
	}

	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}
	
	public function getDisabled()
	{
		return $this->disabled;
	}
	
	public function setMultiple($multiple)
	{
		$this->multiple = $multiple;
	}
	
	public function getMultiple()
	{
		return $this->multiple;
	}

	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getName()
	{
		return $this->name;
	}

	public function setOnblur($onblur)
	{
		$this->onblur = $onblur;
	}
	
	public function getOnblur()
	{
		return $this->onblur;
	}

	public function setOnchange($onchange)
	{
		$this->onchange = $onchange;
	}
	
	public function getOnchange()
	{
		return $this->onchange;
	}

	public function setOnfocus($onfocus)
	{
		$this->onfocus = $onfocus;
	}
	
	public function getOnfocus()
	{
		return $this->onfocus;
	}

	public function setSize($size)
	{
		$this->size = $size;
	}
	
	public function getSize()
	{
		return $this->size;
	}

	public function setTabindex($tabindex)
	{
		$this->tabindex = $tabindex;
	}
	
	public function getTabindex()
	{
		return $this->tabindex;
	}
}
