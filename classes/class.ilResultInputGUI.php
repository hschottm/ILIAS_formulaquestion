<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php");

/**
* This class represents formula question results in a property form.
*
* @author Helmut Schottmüller <ilias@aurealis.de> 
* @version $Id: class.ilResultInputGUI.php 1235 2010-02-15 15:21:18Z hschottm $
* @ingroup	ServicesForm
*/
class ilResultInputGUI extends ilSubEnabledFormPropertyGUI
{
	protected $results;
	protected $variables;
	protected $units;
	protected $resultunits;
	protected $suggestrange;
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
		$this->results = array();
		$this->variables = array();
		$this->units = array();
		$this->resultunits = array();
		$this->suggestrange = '';
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
	
	public function suggestRange($a_var)
	{
		$this->suggestrange = $a_var;
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
	public function setResults($a_value)
	{
		$this->results = $a_value;
	}

	/**
	* Set Value.
	*
	* @param	string	$a_value	Value
	*/
	public function setResultUnits($a_value)
	{
		$this->resultunits = $a_value;
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
	* Set value by array
	*
	* @param	array	$a_values	value array
	*/
	function setValueByArray($a_values)
	{
		$found_vars = array();
		$found_results = array();
		foreach ($a_values as $key => $value)
		{
			if (preg_match("/^unit_(\\\$v\d+)$/", $key, $matches))
			{
				array_push($found_vars, $matches[1]);
			}
			if (preg_match("/^unit_(\\\$r\d+)$/", $key, $matches))
			{
				array_push($found_results, $matches[1]);
			}
		}
		$this->variables = array();
		$this->results = array();
		$this->resultunits = array();
		foreach ($found_vars as $variable)
		{
			$this->getPlugin()->includeClass("class.assFormulaQuestionVariable.php");
			$varObj = new assFormulaQuestionVariable($variable, $_POST["range_min_$variable"], $_POST["range_max_$variable"], $this->getUnit($_POST["unit_$variable"]), $_POST["precision_$variable"], $_POST["intprecision_$variable"]);
			$this->variables[$varObj->getVariable()] = $varObj;
		}
		foreach ($found_results as $result)
		{
			$use_simple_rating = ($_POST["rating_simple_$result"] == 1) ? TRUE : FALSE;
			$this->getPlugin()->includeClass("class.assFormulaQuestionResult.php");
			$resObj = new assFormulaQuestionResult(
				$result, 
				$_POST["range_min_$result"], 
				$_POST["range_max_$result"], 
				$_POST["tolerance_$result"], 
				$this->getUnit($_POST["unit_$result"]), 
				$_POST["formula_$result"], 
				$_POST["points_$result"], 
				$_POST["precision_$result"], 
				$use_simple_rating, 
				($_POST["rating_simple_$result"] != 1) ? $_POST["rating_sign_$result"] : "",
				($_POST["rating_simple_$result"] != 1) ? $_POST["rating_value_$result"] : "",
				($_POST["rating_simple_$result"] != 1) ? $_POST["rating_unit_$result"] : ""
			);
			$this->results[$resObj->getResult()] = $resObj;
			$this->resultunits[$resObj->getResult()] = array();
			if ((!is_object($resObj)) || (!is_array($_POST["units_$result"]))) return;
			foreach ($_POST["units_$result"] as $id)
			{
				if (is_numeric($id) && ($id > 0)) $this->resultunits[$resObj->getResult()][$id] = $this->getUnit($id);
			}
		}
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		
		$found_results = array();
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^unit_(\\\$r\d+)$/", $key, $matches))
			{
				array_push($found_results, $matches[1]);
			}
		}

		foreach ($found_results as $result)
		{
			if ($this->getResult($result) != null)
			{
				$use_simple_rating = ($_POST["rating_simple_$result"] == 1) ? TRUE : FALSE;
				$this->getPlugin()->includeClass("class.assFormulaQuestionResult.php");
				$resObj = new assFormulaQuestionResult(
					$result, 
					$_POST["range_min_$result"], 
					$_POST["range_max_$result"], 
					$_POST["tolerance_$result"], 
					$this->getUnit($_POST["unit_$result"]), 
					$_POST["formula_$result"], 
					$_POST["points_$result"], 
					$_POST["precision_$result"], 
					$use_simple_rating, 
					($_POST["rating_simple_$result"] != 1) ? $_POST["rating_sign_$result"] : "",
					($_POST["rating_simple_$result"] != 1) ? $_POST["rating_value_$result"] : "",
					($_POST["rating_simple_$result"] != 1) ? $_POST["rating_unit_$result"] : ""
				);
				$advanced_rating = $this->canUseAdvancedRating($resObj);
				// ERROR HANDLING
				if (!$advanced_rating && !$use_simple_rating)
				{
					$this->setAlert($this->getPlugin()->txt("err_rating_advanced_not_allowed"));
					return false;
				}
				if ($_POST["rating_simple_$result"] != 1)
				{
					$percentage = $_POST["rating_sign_$result"] + $_POST["rating_value_$result"] + $_POST["rating_unit_$result"];
					if ($percentage != 100)
					{
						$this->setAlert($this->getPlugin()->txt("err_wrong_rating_advanced"));
						return false;
					}
				}
				if ((!is_numeric($resObj->getPrecision())) || ($resObj->getPrecision() < 0))
				{
					$this->setAlert($this->getPlugin()->txt("err_wrong_precision"));
					return false;
				}
				if (strlen($resObj->getTolerance))
				{
					if (!is_numeric($resObj->getTolerance()))
					{
						$this->setAlert($this->getPlugin()->txt("err_tolerance_wrong_value"));
						return false;
					}
					if (($resObj->getTolerance() < 0) || ($resObj->getTolerance() > 100))
					{
						$this->setAlert($this->getPlugin()->txt("err_tolerance_wrong_value"));
						return false;
					}
				}
				if (strlen($resObj->getFormula()) == 0)
				{
					$this->setAlert($this->getPlugin()->txt("err_no_formula"));
					return false;
				}
				if (strpos($resObj->getFormula(), $resObj->getResult()) !== FALSE)
				{
					$this->setAlert($this->getPlugin()->txt("errRecursionInResult"));
					return false;
				}
				if ((strlen($resObj->getPoints()) == 0) || (!is_numeric($resObj->getPoints())))
				{
					$this->setAlert($this->getPlugin()->txt("err_wrong_points"));
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
		$tpl = $pl->getTemplate("tpl.prop_result.html");

		$this->getPlugin()->includeClass("class.GUIPopUpButton.php");
		$allUnits = new GUIPopUpButton();
		$allUnits->setList($this->units);
		$allUnits->setDisplayString("displayString");
		$allUnits->setValue("id");
		$allUnits->setListItemClassPath("class");

		foreach ($this->results as $result)
		{
			$tpl->setCurrentBlock("initrating");
			$advanced_rating = $this->canUseAdvancedRating($result);
			$tpl->setVariable("RESULT", $result->getResult());
			$tpl->setVariable("VISIBILITY", ($result->getRatingSimple() || !$advanced_rating) ? "hidden" : "visible");
			$tpl->parseCurrentBlock();
		}
		foreach ($this->results as $result)
		{
			$tpl->setCurrentBlock("result_header");
			$tpl->setVariable("TEXT_RANGE_MIN", $this->getPlugin()->txt("range_min"));
			$tpl->setVariable("TEXT_RANGE_MAX", $this->getPlugin()->txt("range_max"));
			$tpl->setVariable("TEXT_UNIT", $this->getPlugin()->txt("unit"));
			$tpl->setVariable("TEXT_TOLERANCE", $this->getPlugin()->txt("tolerance"));
			$tpl->setVariable("TEXT_PRECISION", $this->getPlugin()->txt("precision"));
			$tpl->setVariable("TEXT_POINTS", $this->getPlugin()->txt("points"));
			$tpl->parseCurrentBlock();

			$selectedvalues = array();
			foreach ($this->units as $unit)
			{
				if ($this->hasResultUnit($result, $unit->getId()))
				{
					array_push($selectedvalues, $unit->getId());
				}
			}
			$advanced_rating = $this->canUseAdvancedRating($result);
			if (!$advanced_rating)
			{
				$tpl->setCurrentBlock("force_simple_rating");
				$tpl->setVariable("TEXT_RESULT", $result->getResult());
				$tpl->parseCurrentBlock();
			}
			$tpl->setCurrentBlock("result");
			
			$allUnits->setMultiple(null);
			$allUnits->setSize("1");
			$allUnits->setStyle(null);
			$allUnits->setNoSelection($this->getPlugin()->txt("no_selection"));
			$allUnits->setName("unit_" . $result->getResult());
			$allUnits->setId("unit_" . $result->getResult());
			if (is_object($result->getUnit()))
			{
				$allUnits->setSelectedValue($result->getUnit()->getId());
			}
			else
			{
				$allUnits->setSelectedValue(NULL);
			}
			$tpl->setVariable("RESULT_UNIT", $allUnits->getHTML());
			
			$allUnits->setMultiple("multiple");
			$allUnits->setSize("10");
			$allUnits->setNoSelection(null);
			$allUnits->setStyle("width: 300px;");
			$allUnits->setName("units_" . $result->getResult() . "[]");
			$allUnits->setId("units_" . $result->getResult());
			$allUnits->setSelectedValue($selectedvalues);
			$tpl->setVariable("POPUP_AVAILABLE_UNITS", $allUnits->getHTML());
			$tpl->setVariable("TEXT_RESULT", $result->getResult());
			$tpl->setVariable("TEXT_SUGGEST_RANGE", $this->getPlugin()->txt("suggest_range"));
			$tpl->setVariable("TEXT_SELECT_UNIT", $this->getPlugin()->txt("select_unit"));
			if (strlen($result->getTolerance())) $tpl->setVariable("VALUE_TOLERANCE", ' value="' . ilUtil::prepareFormOutput($result->getTolerance()) . '"');
			if (strlen($result->getRatingSign())) $tpl->setVariable("VALUE_RATING_SIGN", ' value="' . ilUtil::prepareFormOutput($result->getRatingSign()) . '"');
			if (strlen($result->getRatingValue())) $tpl->setVariable("VALUE_RATING_VALUE", ' value="' . ilUtil::prepareFormOutput($result->getRatingValue()) . '"');
			if (strlen($result->getRatingUnit())) $tpl->setVariable("VALUE_RATING_UNIT", ' value="' . ilUtil::prepareFormOutput($result->getRatingUnit()) . '"');
			if (strlen($result->getPoints())) $tpl->setVariable("VALUE_POINTS", ' value="' . ilUtil::prepareFormOutput($result->getPoints()) . '"');
			if (strlen($result->getPrecision())) $tpl->setVariable("VALUE_PRECISION", ' value="' . ilUtil::prepareFormOutput($result->getPrecision()) . '"');
			$tpl->setVariable("TEXT_FORMULA", $this->getPlugin()->txt("formula"));
			if (strlen($result->getFormula()))
			{
				$tpl->setVariable("VALUE_FORMULA", ' value="' . ilUtil::prepareFormOutput($result->getFormula()) . '"');
			}
			$tpl->setVariable("TEXT_RATING_SIMPLE", $this->getPlugin()->txt("rating_simple"));
			if (!$advanced_rating)
			{
				$tpl->setVariable("CHECKED_RATING_SIMPLE", ' checked="checked"');
				$tpl->setVariable("DISABLED_RATING_SIMPLE", ' disabled="disabled"');
			}
			else
			{
				if ($result->getRatingSimple())
				{
					$tpl->setVariable("CHECKED_RATING_SIMPLE", ' checked="checked"');
				}
			}
			$tpl->setVariable("TEXT_AVAILABLE_RESULT_UNITS", $this->getPlugin()->txt("result_units"));
			$tpl->setVariable("TEXT_RATING_SIGN", $this->getPlugin()->txt("rating_sign"));
			$tpl->setVariable("TEXT_RATING_VALUE", $this->getPlugin()->txt("rating_value"));
			$tpl->setVariable("TEXT_RATING_UNIT", $this->getPlugin()->txt("rating_unit"));

			if (strcmp($this->suggestrange, $result->getResult()) == 0)
			{
				// suggest a range for the result
				if (strlen($result->substituteFormula($this->variables, $this->results)))
				{
					$result->suggestRange($this->variables, $this->results);
				}
			}
			
			if (strlen(trim($result->getRangeMin()))) $tpl->setVariable("VALUE_RANGE_MIN", ' value="' . ilUtil::prepareFormOutput($result->getRangeMin()) . '"');
			if (strlen(trim($result->getRangeMax()))) $tpl->setVariable("VALUE_RANGE_MAX", ' value="' . ilUtil::prepareFormOutput($result->getRangeMax()) . '"');

			$tpl->parseCurrentBlock();
		}
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
	
	private function getResultUnits($result)
	{
		if (array_key_exists($result->getResult(), $this->resultunits))
		{
			return $this->resultunits[$result->getResult()];
		}
		else
		{
			return array();
		}
	}

	public function hasResultUnit($result, $unit_id)
	{
		if (array_key_exists($result->getResult(), $this->resultunits))
		{
			if (array_key_exists($unit_id, $this->resultunits[$result->getResult()])) return TRUE;
		}
		return FALSE;
	}

	/**
	* Check if advanced rating can be used for a result. This is only possible if there is exactly 
	* one possible correct unit for the result, otherwise it is impossible to determine wheather the
	* unit is correct or the value.
	*
	* @return boolean True if advanced rating could be used, false otherwise
	*/
	private function canUseAdvancedRating($result)
	{
		$result_units = $this->getResultUnits($result);
		$resultunit = $result->getUnit();
		$similar_units = 0;
		foreach ($result_units as $unit)
		{
			if (is_object($resultunit))
			{
				if ($resultunit->getId() != $unit->getId())
				{
					if ($resultunit->getBaseUnit() && $unit->getBaseUnit())
					{
						if ($resultunit->getBaseUnit() == $unit->getBaseUnit()) return false;
					}
					if ($resultunit->getBaseUnit())
					{
						if ($resultunit->getBaseUnit() == $unit->getId()) return false;
					}
					if ($unit->getBaseUnit())
					{
						if ($unit->getBaseUnit() == $resultunit->getId()) return false;
					}
				}
			}
		}
		return true;
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

	private function getResult($result)
	{
		if (array_key_exists($result, $this->results))
		{
			return $this->results[$result];
		}
		return null;
	}
}
?>