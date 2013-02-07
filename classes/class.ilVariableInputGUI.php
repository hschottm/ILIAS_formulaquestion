<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php");

/**
* This class represents formula question variables in a property form.
*
* @author Helmut Schottmüller <ilias@aurealis.de> 
* @version $Id: class.ilVariableInputGUI.php 1234 2010-02-15 13:33:46Z hschottm $
* @ingroup	ServicesForm
*/
class ilVariableInputGUI extends ilSubEnabledFormPropertyGUI
{
	protected $variables;
	protected $units;
	private $plugin;

	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title)
	{
		parent::__construct($a_title, '');
		$this->variables = array();
		$this->units = array();
	}

	/**
	 * @return object The plugin object
	 */
	public function getPlugin() 
	{
		if ($this->plugin == null)
		{
			include_once "./Services/Component/classes/class.ilPlugin.php";
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
			
		}
		return $this->plugin;
	}

	public function setCategorizedUnits($a_units)
	{
		$this->units = $a_units;
	}
	
	public function getCategorizedUnits()
	{
		return $this->units;
	}

	/**
	* Set Value.
	*
	* @param	string	$a_value	Value
	*/
	public function setVariables($a_value)
	{
		$this->variables = $a_value;
	}

	/**
	* Get Value.
	*
	* @return	string	Value
	*/
	public function getVariables()
	{
		return $this->variables;
	}

	/**
	* Set value by array
	*
	* @param	array	$a_values	value array
	*/
	function setValueByArray($a_values)
	{
		//$this->setValue($a_values[$this->getPostVar()]);
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		
		$found_vars = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^unit_(\\\$v\d+)$/", $key, $matches))
			{
				array_push($found_vars, $matches[1]);
			}
		}

		foreach ($found_vars as $variable)
		{
			if ($this->getVariable($variable) != null)
			{
				$this->getPlugin()->includeClass("class.assFormulaQuestionVariable.php");
				$varObj = new assFormulaQuestionVariable($variable, $_POST["range_min_$variable"], $_POST["range_max_$variable"], $this->getUnit($_POST["unit_$variable"]), $_POST["precision_$variable"], $_POST["intprecision_$variable"]);
				// ERROR HANDLING
				if (strlen($varObj->getRangeMin()) == 0)
				{
					$this->setAlert($this->getPlugin()->txt("err_no_min_range"));
					return false;
				}
				if (strlen($varObj->getRangeMax()) == 0)
				{
					$this->setAlert($this->getPlugin()->txt("err_no_max_range"));
					return false;
				}
				if (strlen($varObj->getPrecision()) == 0)
				{
					$this->setAlert($this->getPlugin()->txt("err_no_precision"));
					return false;
				}
				if (!is_numeric($varObj->getPrecision()))
				{
					$this->setAlert($this->getPlugin()->txt("err_wrong_precision"));
					return false;
				}
				if ((!is_integer($varObj->getPrecision())) || ($varObj->getPrecision() < 0))
				{
					$this->setAlert($this->getPlugin()->txt("err_wrong_precision"));
					return false;
				}
				if (!is_numeric($varObj->getRangeMin()))
				{
					$this->setAlert($this->getPlugin()->txt("err_no_min_range_number"));
					return false;
				}
				if (!is_numeric($varObj->getRangeMax()))
				{
					$this->setAlert($this->getPlugin()->txt("err_no_max_range_number"));
					return false;
				}
				if ($varObj->getRangeMin() > $varObj->getRangeMax())
				{
					$this->setAlert($this->getPlugin()->txt("err_range"));
					return false;
				}
				// END ERROR HANDLING
			}
		}
		
		return $this->checkSubItemsInput();
	}

	/**
	* Render item
	*/
	protected function render($a_mode = "")
	{
		$pl = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
		$tpl = $pl->getTemplate("tpl.prop_variable.html");

		$this->getPlugin()->includeClass("class.GUIPopUpButton.php");
		$allUnits = new GUIPopUpButton();
		$allUnits->setList($this->units);
		$allUnits->setDisplayString("displayString");
		$allUnits->setValue("id");
		$allUnits->setListItemClassPath("class");

		foreach ($this->variables as $variable)
		{
			$tpl->setCurrentBlock("variable");
			$allUnits->setMultiple(null);
			$allUnits->setNoSelection($this->getPlugin()->txt("no_selection"));
			$allUnits->setSize("1");
			$allUnits->setStyle(null);
			$allUnits->setName("unit_" . $variable->getVariable());
			$allUnits->setId("unit_" . $variable->getVariable());
			if (is_object($variable->getUnit()))
			{
				$allUnits->setSelectedValue($variable->getUnit()->getId());
			}
			else
			{
				$allUnits->setSelectedValue(NULL);
			}
			$tpl->setVariable("VARIABLE_UNIT", $allUnits->getHTML());
			$tpl->setVariable("TEXT_VARIABLE", $variable->getVariable());
			$tpl->setVariable("TEXT_SELECT_UNIT", $this->getPlugin()->txt("select_unit"));
			if (strlen($variable->getRangeMin())) $tpl->setVariable("VALUE_RANGE_MIN", ' value="' . ilUtil::prepareFormOutput($variable->getRangeMin()) . '"');
			if (strlen($variable->getRangeMax())) $tpl->setVariable("VALUE_RANGE_MAX", ' value="' . ilUtil::prepareFormOutput($variable->getRangeMax()) . '"');
			if (strlen($variable->getPrecision())) $tpl->setVariable("VALUE_PRECISION", ' value="' . ilUtil::prepareFormOutput($variable->getPrecision()) . '"');
			if (strlen($variable->getIntprecision())) $tpl->setVariable("VALUE_INTPRECISION", ' value="' . ilUtil::prepareFormOutput($variable->getIntprecision()) . '"');
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("TEXT_VARIABLES", $this->getPlugin()->txt("variables"));
		$tpl->setVariable("TEXT_RANGE_MIN", $this->getPlugin()->txt("range_min"));
		$tpl->setVariable("TEXT_RANGE_MAX", $this->getPlugin()->txt("range_max"));
		$tpl->setVariable("TEXT_UNIT", $this->getPlugin()->txt("unit"));
		$tpl->setVariable("TEXT_PRECISION", $this->getPlugin()->txt("precision"));
		$tpl->setVariable("TEXT_INTPRECISION", $this->getPlugin()->txt("intprecision"));

		return $tpl->get();
	}
	
	/**
	* Insert property html
	*
	* @return	int	Size
	*/
	function insert(&$a_tpl)
	{
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}
	
	private function getVariable($variable)
	{
		if (array_key_exists($variable, $this->variables))
		{
			return $this->variables[$variable];
		}
		return null;
	}

	private function getUnit($id)
	{
		if (array_key_exists($id, $this->units))
		{
			return $this->units[$id];
		}
		return null;
	}
}
?>