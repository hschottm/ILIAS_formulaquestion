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
include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Class for single choice questions
*
* assFormulaQuestion is a class for single choice questions.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.assFormulaQuestion.php 1236 2010-02-15 15:44:16Z hschottm $
* @ingroup ModulesTestQuestionPool
*/
class assFormulaQuestion extends assQuestion
{
	private $variables;
	private $results;
	private $units;
	private $categorizedUnits;
	private $resultunits;
	private $plugin;

	/**
	* assFormulaQuestion constructor
	*
	* The constructor takes possible arguments an creates an instance of the assFormulaQuestion object.
	*
	* @param string $title A title string to describe the question
	* @param string $comment A comment string to describe the question
	* @param string $author A string containing the name of the questions author
	* @param integer $owner A numerical ID to identify the owner/creator
	* @param string $question The question string of the single choice question
	* @access public
	* @see assQuestion:assQuestion()
	*/
	function __construct(
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = ""
	)
	{
		parent::__construct($title, $comment, $author, $owner, $question);
		$this->variables = array();
		$this->results = array();
		$this->units = array();
		$this->resultunits = array();
		$this->categorizedUnits = array();
		$this->plugin = null;
	}
	
	/**
	 * @return object The plugin object
	 */
	public function getPlugin() {
		if ($this->plugin == null)
		{
			include_once "./Services/Component/classes/class.ilPlugin.php";
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
			
		}
		return $this->plugin;
	}
	
	public function clearVariables()
	{
		$this->variables = array();
	}
	
	public function getVariables()
	{
		return $this->variables;
	}
	
	public function getVariable($variable)
	{
		if (array_key_exists($variable, $this->variables))
		{
			return $this->variables[$variable];
		}
		return null;
	}

	public function addVariable($variable)
	{
		$this->variables[$variable->getVariable()] = $variable;
	}
	
	public function clearResults()
	{
		$this->results = array();
	}
	
	public function getResults()
	{
		return $this->results;
	}
	
	public function getResult($result)
	{
		if (array_key_exists($result, $this->results))
		{
			return $this->results[$result];
		}
		return null;
	}

	public function addResult($result)
	{
		$this->results[$result->getResult()] = $result;
	}
	
	public function addResultUnits($result, $unit_ids)
	{
		$this->resultunits[$result->getResult()] = array();
		if ((!is_object($result)) || (!is_array($unit_ids))) return;
		foreach ($unit_ids as $id)
		{
			if (is_numeric($id) && ($id > 0)) $this->resultunits[$result->getResult()][$id] = $this->getUnit($id);
		}
	}
	
	public function addResultUnit($result, $unit)
	{
		if (!is_array($this->resultunits[$result->getResult()])) $this->resultunits[$result->getResult()] = array();
		$this->resultunits[$result->getResult()][$unit->getId()] = $unit;
	}
	
	public function getResultUnits($result)
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
	
	public function parseQuestionText()
	{
		global $ilLog;
		$this->clearResults();
		$this->clearVariables();
		if (preg_match_all("/(\\\$v\\d+)/ims", $this->getQuestion(), $matches))
		{
			foreach ($matches[1] as $variable)
			{
				$this->getPlugin()->includeClass("class.assFormulaQuestionVariable.php");
				$varObj = new assFormulaQuestionVariable($variable, 0, 0, null, 0);
				$this->addVariable($varObj);
			}
		}
		if (preg_match_all("/(\\\$r\\d+)/ims", $this->getQuestion(), $rmatches))
		{
			foreach ($rmatches[1] as $result)
			{
				$this->getPlugin()->includeClass("class.assFormulaQuestionResult.php");
				$resObj = new assFormulaQuestionResult($result, NULL, NULL, 0, -1, NULL, 1, 1, TRUE);
				$this->addResult($resObj);
			}
		}
	}
	
	public function checkForDuplicateVariables()
	{
		if (preg_match_all("/(\\\$v\\d+)/ims", $this->getQuestion(), $matches))
		{
			if ((count(array_unique($matches[1]))) != count($matches[1])) return false;
		}
		return true;
	}
	
	public function checkForDuplicateResults()
	{
		if (preg_match_all("/(\\\$r\\d+)/ims", $this->getQuestion(), $rmatches))
		{
			if ((count(array_unique($rmatches[1]))) != count($rmatches[1])) return false;
		}
		return true;
	}
	
	public function substituteVariables($userdata = null, $graphicalOutput = FALSE, $forsolution = FALSE, $result_output = FALSE)
	{
		global $ilDB, $ilLog;

		if ((count($this->results) == 0) && (count($this->variables) == 0)) return;
		$text = $this->getQuestion();
		if (preg_match_all("/(\\\$r\\d+)/ims", $this->getQuestion(), $rmatches))
		{
			foreach ($rmatches[1] as $result)
			{
				$resObj = $this->getResult($result);
				$resObj->findValidRandomVariables($this->getVariables(), $this->getResults());
			}
		}
		if (preg_match_all("/(\\\$v\\d+)/ims", $this->getQuestion(), $matches))
		{
			foreach ($matches[1] as $variable)
			{
				$varObj = $this->getVariable($variable);
				if (is_array($userdata))
				{
					if (strlen($userdata[$varObj->getVariable()]))
					{
						$value = $userdata[$varObj->getVariable()];
						$varObj->setValue($value);
					}
					else
					{
						// save value to db
						$next_id = $ilDB->nextId('tst_solutions');
						$affectedRows = $ilDB->insert("tst_solutions", array(
							"solution_id" => array("integer", $next_id),
							"active_fi" => array("integer", $userdata["active_id"]),
							"question_fi" => array("integer", $this->getId()),
							"value1" => array("clob", $variable),
							"value2" => array("clob", $varObj->getValue()),
							"points" => array("float", 0),
							"pass" => array("integer", $userdata["pass"]),
							"tstamp" => array("integer", time())
						));
					}
				}
				$unit = (is_object($varObj->getUnit())) ? $varObj->getUnit()->getUnit() : "";
				$val = (strlen($varObj->getValue()) > 8) ? strtoupper(sprintf("%e", $varObj->getValue())) : $varObj->getValue();
				$text = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", $val . " " . $unit . "\\1", $text);
			}
		}
		if (preg_match_all("/(\\\$r\\d+)/ims", $this->getQuestion(), $rmatches))
		{
			foreach ($rmatches[1] as $result)
			{
				$resObj = $this->getResult($result);
				$value = "";
				if (is_array($userdata))
				{
					if (is_array($userdata[$result]))
					{
						if ($forsolution)
						{
							$value = $userdata[$result]["value"];
						}
						else
						{
							$value = ' value="' . $userdata[$result]["value"] . '"';
						}
					}
				}
				else
				{
					if ($forsolution)
					{
						$value = $resObj->calculateFormula($this->getVariables(), $this->getResults());
						$value = sprintf("%." . $resObj->getPrecision() . "f", $value);
					}
					else
					{
						$val = $resObj->calculateFormula($this->getVariables(), $this->getResults());
						$val = sprintf("%." . $resObj->getPrecision() . "f", $val);
						$val = (strlen($val) > 8) ? strtoupper(sprintf("%e", $val)) : $val;
						$value = ' value="' . $val . '"';
					}
				}
				if ($forsolution)
				{
					$input = '<span class="solutionbox">' . ilUtil::prepareFormOutput($value) . '</span>';
				}
				else
				{
					$input = '<input type="text" name="result_' . $result . '"' . $value . ' />';
				}
				$units = "";
				if (count($this->getResultUnits($resObj)) > 0)
				{
					if ($forsolution)
					{
						if (is_array($userdata))
						{
							foreach ($this->getResultUnits($resObj) as $unit)
							{
								if ($userdata[$result]["unit"] == $unit->getId())
								{
									$units = $unit->getUnit();
								}
							}
						}
						else
						{
							if ($resObj->getUnit())
							{
								$units = $resObj->getUnit()->getUnit();
							}
						}
					}
					else
					{
						$units = '<select name="result_' . $result . '_unit">';
						$units .= '<option value="-1">' . $this->getPlugin()->txt("select_unit") . '</option>';
						foreach ($this->getResultUnits($resObj) as $unit)
						{
							$units .= '<option value="' . $unit->getId() . '"';
							if ((is_array($userdata[$result])) && (strlen($userdata[$result]["unit"])))
							{
								if ($userdata[$result]["unit"] == $unit->getId())
								{
									$units .= ' selected="selected"';
								}
							}
							$units .= '>' . $unit->getUnit() . '</option>';
						}
						$units .= '</select>';
					}
				}
				else
				{
					$units = "";
				}
				$checkSign = "";
				if ($graphicalOutput)
				{
					$resunit = null;
					if ($userdata[$result]["unit"] > 0)
					{
						$resunit = $this->getUnit($userdata[$result]["unit"]);
					}
					$pl = $this->getPlugin();
					$template = $pl->getTemplate("tpl.il_as_qpl_formulaquestion_output_solution_image.html");
					if ($resObj->isCorrect($this->getVariables(), $this->getResults(), $userdata[$result]["value"], $resunit))
					{
						$template->setCurrentBlock("icon_ok");
						$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.gif"));
						$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
						$template->parseCurrentBlock();
					}
					else
					{
						$template->setCurrentBlock("icon_not_ok");
						$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.gif"));
						$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
						$template->parseCurrentBlock();
					}
					$checkSign = $template->get();
				}
				$resultOutput = "";
				if ($result_output)
				{
					$pl = $this->getPlugin();
					$template = $pl->getTemplate("tpl.il_as_qpl_formulaquestion_output_solution_result.html");
					if (is_array($userdata))
					{
						$found = $resObj->getResultInfo($this->getVariables(), $this->getResults(), $userdata[$resObj->getResult()]["value"], $userdata[$resObj->getResult()]["unit"], $this->getUnits());
					}
					else
					{
						$found = $resObj->getResultInfo($this->getVariables(), $this->getResults(), $resObj->calculateFormula($this->getVariables(), $this->getResults()), is_object($resObj->getUnit()) ? $resObj->getUnit()->getId() : NULL, $this->getUnits());
					}
					$resulttext = "(";
					if ($resObj->getRatingSimple())
					{
						$resulttext .= $found['points'] . " " . (($found['points'] == 1) ? $this->lng->txt('point') : $this->lng->txt('points'));
					}
					else
					{
						$resulttext .= $pl->txt("rated_sign") . " " . (($found['sign']) ? $found['sign'] : 0) . " " . (($found['sign'] == 1) ? $this->lng->txt('point') : $this->lng->txt('points')) . ", ";
						$resulttext .= $pl->txt("rated_value") . " " . (($found['value']) ? $found['value'] : 0) . " " . (($found['value'] == 1) ? $this->lng->txt('point') : $this->lng->txt('points')) . ", ";
						$resulttext .= $pl->txt("rated_unit") . " " . (($found['unit']) ? $found['unit'] : 0) . " " . (($found['unit'] == 1) ? $this->lng->txt('point') : $this->lng->txt('points'));
					}
					$resulttext .= ")";
					$template->setVariable("RESULT_OUTPUT", $resulttext);
					$resultOutput = $template->get();
				}
				$text = preg_replace("/\\\$" . substr($result, 1) . "([^0123456789]{0,1})/", $input  . " " . $units . " " . $checkSign . " " . $resultOutput . " " . "\\1", $text);
			}
		}
		return $text;
	}
	
	public function getUnitCategories()
	{
		global $ilDB;
		
		$categories = array();
		$result = $ilDB->query("SELECT * FROM il_qpl_qst_fq_ucat ORDER BY category");
		if ($result->numRows())
		{
			while ($row = $ilDB->fetchAssoc($result))
			{
				$value = (strcmp("-qpl_qst_formulaquestion_".$row["category"]."-", $this->getPlugin()->txt($row["category"])) == 0) ? $row["category"] : $this->getPlugin()->txt($row["category"]);
				if (strlen(trim($row["category"])))
				{
					$cat = array("value" => $row["category_id"], "text" => $value);
					$categories[$row["category_id"]] = $cat;
				}
			}
		}
		return $categories;
	}
	
	public function saveUnitOrder($category, $neworder)
	{
		global $ilDB;
		
		$unit_ids = split(",", $neworder);
		if (count($unit_ids))
		{
			$sequence = 1;
			foreach ($unit_ids as $unit_id)
			{
				if (preg_match("/reorder_unit_(\\d+)/", $unit_id, $matches))
				{
					$affectedRows = $ilDB->manipulateF("UPDATE il_qpl_qst_fq_unit SET sequence = %s WHERE unit_id = %s",
						array('integer','integer'),
						array($sequence, $matches[1])
					);
					$sequence++;
				}
			}
		}
	}
	
	public function saveNewUnitCategory($category)
	{
		global $ilDB;
		
		if (strlen($category) == 0) return null;
		$result = $ilDB->queryF("SELECT category FROM il_qpl_qst_fq_ucat WHERE category = %s",
			array("text"),
			array($category)
		);
		if (!$result->numRows())
		{
			$next_id = $ilDB->nextId('il_qpl_qst_fq_ucat');
			$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_fq_ucat (category_id, category) VALUES (%s, %s)",
				array('integer','text'),
				array(
					$next_id,
					$category
				)
			);
			return $next_id;
		}
		return null;
	}
	
	public function createNewUnit($category, $unitname = "")
	{
		global $ilDB;
		
		$name = (strlen($unitname)) ? $unitname : $this->getPlugin()->txt("unit_placeholder");
		$next_id = $ilDB->nextId('il_qpl_qst_fq_unit');
		$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_fq_unit (unit_id, unit, factor, baseunit_fi, category_fi, sequence) VALUES (%s, %s, %s, %s, %s, %s)",
			array('integer','text','float','integer','integer','integer'),
			array(
				$next_id,
				$name,
				1,
				NULL,
				$category,
				0
			)
		);
		$this->clearUnits();
		return $next_id;
	}

	private function checkDeleteUnit($id, $category_id = null)
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_var WHERE unit_fi = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows() > 0)
		{
			return $this->getPlugin()->txt("err_unit_in_variables");
		}
		$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_res WHERE unit_fi = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows() > 0)
		{
			return $this->getPlugin()->txt("err_unit_in_results");
		}
		if (!is_null($category_id))
		{
			$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_unit WHERE baseunit_fi = %s AND category_fi <> %s",
				array('integer','integer'),
				array($id, $category_id)
			);
		}
		else
		{
			$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_unit WHERE baseunit_fi = %s",
				array('integer'),
				array($id)
			);
		}
		if ($result->numRows() > 0)
		{
			return $this->getPlugin()->txt("err_unit_is_baseunit");
		}
		return null;
	}
	
	/**
	* Check if advanced rating can be used for a result. This is only possible if there is exactly 
	* one possible correct unit for the result, otherwise it is impossible to determine wheather the
	* unit is correct or the value.
	*
	* @return boolean True if advanced rating could be used, false otherwise
	*/
	public function canUseAdvancedRating($result)
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

	public function getCategoryUnitCount($id)
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_unit WHERE category_fi = %s",
			array('integer'),
			array($id)
		);
		return $result->numRows();
	}
	
	public function isUnitInUse($id)
	{
		global $ilDB;

		$result = $ilDB->queryF("SELECT unit_fi FROM il_qpl_qst_fq_res_unit WHERE unit_fi = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows()) return true;
		$result = $ilDB->queryF("SELECT unit_fi FROM il_qpl_qst_fq_var WHERE unit_fi = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows()) return true;
		$result = $ilDB->queryF("SELECT unit_fi FROM il_qpl_qst_fq_res WHERE unit_fi = %s",
			array('integer'),
			array($id)
		);
		return ($result->numRows()) ? true : false;
	}
	
	private function checkDeleteCategory($id)
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_unit WHERE category_fi = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows() > 0)
		{
			while ($row = $ilDB->fetchAssoc($result))
			{
				$res = $this->checkDeleteUnit($row["unit_id"], $id);
				if (!is_null($res)) return $res;
			}
		}
		return null;
	}
	
	public function deleteUnit($id)
	{
		global $ilDB;

		$res = $this->checkDeleteUnit($id);
		if (!is_null($res)) return $res;
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_unit WHERE unit_id = %s",
			array('integer'),
			array($id)
		);
		if ($affectedRows > 0) $this->clearUnits();
		return null;
	}
	
	public function deleteCategory($id)
	{
		global $ilDB;

		$res = $this->checkDeleteCategory($id);
		if (!is_null($res)) return $this->getPlugin()->txt("err_category_in_use");

		$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_unit WHERE category_fi = %s",
			array('integer'),
			array($id)
		);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$this->deleteUnit($row["unit_id"]);
		}
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_ucat WHERE category_id = %s",
			array('integer'),
			array($id)
		);
		if ($affectedRows > 0) 
		{
			$this->clearUnits();
		}
		return null;
	}
	
	public function saveUnit($name, $baseunit, $factor, $category, $id)
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT sequence FROM il_qpl_qst_fq_unit WHERE unit_id = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows())
		{
			$row = $ilDB->fetchAssoc($result);
			$sequence = $row["sequence"];
			if (is_null($baseunit) || (strlen($baseunit) == 0)) 
			{
				$factor = 1;
				$baseunit = null;
			}
			$affectedRows = $ilDB->manipulateF("UPDATE il_qpl_qst_fq_unit SET unit = %s, factor = %s, baseunit_fi = %s, category_fi = %s, sequence = %s WHERE unit_id = %s",
				array('text','float','integer','integer','integer','integer'),
				array($name, $factor, $baseunit, $category, $sequence, $id)
			);
			if ($affectedRows > 0) $this->clearUnits();
		}
	}
	
	public function saveCategory($name, $id)
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_ucat WHERE category = %s",
			array('integer'),
			array($id)
		);
		if ($result->numRows())
		{
			$row = $ilDB->fetchAssoc($result);
			if ($row["category_id"] != $id)
			{
				return $this->getPlugin()->txt("err_wrong_categoryname");
			}
		}

		$affectedRows = $ilDB->manipulateF("UPDATE il_qpl_qst_fq_ucat SET category = %s WHERE category_id = %s",
			array('text','integer'),
			array($name, $id)
		);
	}
	
	protected function loadUnits()
	{
		global $ilDB;
		
		$units = array();
		$result = $ilDB->query("SELECT il_qpl_qst_fq_unit.*, il_qpl_qst_fq_ucat.category FROM il_qpl_qst_fq_unit, il_qpl_qst_fq_ucat WHERE il_qpl_qst_fq_unit.category_fi = il_qpl_qst_fq_ucat.category_id ORDER BY il_qpl_qst_fq_ucat.category, il_qpl_qst_fq_unit.sequence");
		if ($result->numRows())
		{
			$this->getPlugin()->includeClass("class.assFormulaQuestionUnit.php");
			$category = "";
			while ($row = $ilDB->fetchAssoc($result))
			{
				$unit = new assFormulaQuestionUnit($row["unit_id"], $row["unit"], $row["factor"], $row["baseunit_fi"], $row["category"]);
				$this->addUnit($unit);
			}
		}
	}
	
	public function getCategorizedUnits()
	{
		global $ilDB;

		if (count($this->categorizedUnits) == 0)
		{
			$result = $ilDB->query("SELECT il_qpl_qst_fq_unit.*, il_qpl_qst_fq_ucat.category FROM il_qpl_qst_fq_unit, il_qpl_qst_fq_ucat WHERE il_qpl_qst_fq_unit.category_fi = il_qpl_qst_fq_ucat.category_id ORDER BY il_qpl_qst_fq_ucat.category, il_qpl_qst_fq_unit.sequence");
			if ($result->numRows())
			{
				$this->getPlugin()->includeClass("class.assFormulaQuestionUnit.php");
				$this->getPlugin()->includeClass("class.assFormulaQuestionUnitCategory.php");
				$category = "";
				while ($row = $ilDB->fetchAssoc($result))
				{
					$unit = new assFormulaQuestionUnit($row["unit_id"], $row["unit"], $row["factor"], $row["baseunit_fi"], (strcmp("-qpl_qst_formulaquestion_".$row["category"]."-", $this->getPlugin()->txt($row["category"])) == 0) ? $row["category"] : $this->getPlugin()->txt($row["category"]));
					if (strcmp($category, $unit->getCategory()) != 0)
					{
						$cat = new assFormulaQuestionUnitCategory($row["category_fi"], (strcmp("-qpl_qst_formulaquestion_".$row["category"]."-", $this->getPlugin()->txt($row["category"])) == 0) ? $row["category"] : $this->getPlugin()->txt($row["category"]));
						array_push($this->categorizedUnits, $cat);
						$category = $unit->getCategory();
					}
					array_push($this->categorizedUnits, $unit);
				}
			}
		}
		return $this->categorizedUnits;
	}
	
	protected function clearUnits()
	{
		$this->units = array();
	}
	
	protected function addUnit($unit)
	{
		$this->units[$unit->getId()] = $unit;
	}
	
	public function getUnits()
	{
		if (count($this->units) == 0)
		{
			$this->loadUnits();
		}
		return $this->units;
	}
	
	public function loadUnitsForCategory($category)
	{
		global $ilDB;
		
		$units = array();
		$result = $ilDB->queryF("SELECT il_qpl_qst_fq_unit.* FROM il_qpl_qst_fq_unit, il_qpl_qst_fq_ucat WHERE il_qpl_qst_fq_unit.category_fi = il_qpl_qst_fq_ucat.category_id AND il_qpl_qst_fq_ucat.category_id = %s ORDER BY il_qpl_qst_fq_unit.sequence",
			array('integer'),
			array($category)
		);
		if ($result->numRows())
		{
			$this->getPlugin()->includeClass("class.assFormulaQuestionUnit.php");
			while ($row = $ilDB->fetchAssoc($result))
			{
				$unit = new assFormulaQuestionUnit($row["unit_id"], $row["unit"], $row["factor"], $row["baseunit_fi"], $row["category"], $row["sequence"]);
				array_push($units, $unit);
			}
		}
		return $units;
	}
	
	public function getUnit($id)
	{
		if (count($this->units) == 0)
		{
			$this->loadUnits();
		}
		if (array_key_exists($id, $this->units))
		{
			return $this->units[$id];
		}
		return null;
	}
	
	/**
	* Returns true, if the question is complete for use
	*
	* @return boolean True, if the single choice question is complete for use, otherwise false
	*/
	public function isComplete()
	{
		if (($this->title) and ($this->author) and ($this->question) and ($this->getMaximumPoints() > 0))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Saves a assFormulaQuestion object to a database
	*
	* @access public
	*/
	function saveToDb($original_id = "")
	{
		global $ilDB, $ilLog;

		$this->saveQuestionDataToDb($original_id);

		// save variables
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_var WHERE question_fi = %s", 
			array("integer"),
			array($this->getId())
		);
		foreach ($this->variables as $variable)
		{
			$next_id = $ilDB->nextId('il_qpl_qst_fq_var');
			$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_fq_var (variable_id, question_fi, variable,range_min, range_max, unit_fi, varprecision, intprecision) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
				array('integer','integer', 'text', 'float', 'float', 'integer', 'integer', 'integer'),
				array(
					$next_id,
					$this->getId(),
					$variable->getVariable(),
					((strlen($variable->getRangeMin())) ? $variable->getRangeMin() : 0.0),
					((strlen($variable->getRangeMax())) ? $variable->getRangeMax() : 0.0),
					(is_object($variable->getUnit()) ? $variable->getUnit()->getId() : NULL),
					$variable->getPrecision(),
					$variable->getIntprecision()
				)
			);
		}
		// save results
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_res WHERE question_fi = %s", 
			array("integer"),
			array($this->getId())
		);
		foreach ($this->results as $result)
		{ 
			$next_id = $ilDB->nextId('il_qpl_qst_fq_res');
			$affectedRows = $ilDB->insert("il_qpl_qst_fq_res", array(
				"result_id" => array("integer", $next_id),
				"question_fi" => array("integer", $this->getId()),
				"result" => array("text", $result->getResult()),
				"range_min" => array("float", ((strlen($result->getRangeMin())) ? $result->getRangeMin() : NULL)),
				"range_max" => array("float", ((strlen($result->getRangeMax())) ? $result->getRangeMax() : NULL)),
				"tolerance" => array("float", ((strlen($result->getTolerance())) ? $result->getTolerance() : NULL)),
				"unit_fi" => array("integer", is_object($result->getUnit()) ? $result->getUnit()->getId() : NULL),
				"formula" => array("clob", $result->getFormula()),
				"resprecision" => array("integer", $result->getPrecision()),
				"rating_simple" => array("integer", ($result->getRatingSimple()) ? 1 : 0),
				"rating_sign" => array("float", ($result->getRatingSimple()) ? 25 : $result->getRatingSign()),
				"rating_value" => array("float", ($result->getRatingSimple()) ? 25 : $result->getRatingValue()),
				"rating_unit" => array("float", ($result->getRatingSimple()) ? 25 : $result->getRatingUnit()),
				"points" => array("float", $result->getPoints())
			));
		}
		// save result units
		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_res_unit WHERE question_fi = %s", 
			array("integer"),
			array($this->getId())
		);
		foreach ($this->results as $result)
		{
			foreach ($this->getResultUnits($result) as $unit)
			{
				$next_id = $ilDB->nextId('il_qpl_qst_fq_res_unit');
				$affectedRows = $ilDB->manipulateF("INSERT INTO il_qpl_qst_fq_res_unit (result_unit_id, question_fi, result, unit_fi) VALUES (%s, %s, %s, %s)", 
					array('integer','integer','text','integer'),
					array(
						$next_id,
						$this->getId(),
						$result->getResult(),
						$unit->getId()
					)
				);
			}
		}
		parent::saveToDb();
	}

	/**
	* Loads a assFormulaQuestion object from a database
	*
	* @param integer $question_id A unique key which defines the question in the database
	*/
	public function loadFromDb($question_id)
	{
		global $ilDB;

		$result = $ilDB->queryF("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = %s",
			array('integer'),
			array($question_id)
		);
		if ($result->numRows() == 1)
		{
			$data = $ilDB->fetchAssoc($result);
			$this->setId($question_id);
			$this->setTitle($data["title"]);
			$this->setComment($data["description"]);
			$this->setSuggestedSolution($data["solution_hint"]);
			$this->setOriginalId($data["original_id"]);
			$this->setObjId($data["obj_fi"]);
			$this->setAuthor($data["author"]);
			$this->setOwner($data["owner"]);

			include_once("./Services/RTE/classes/class.ilRTE.php");
			$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
			$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));

			// load variables
			$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_var WHERE question_fi = %s",
				array('integer'),
				array($question_id)
			);
			if ($result->numRows() > 0)
			{
				$this->getPlugin()->includeClass("class.assFormulaQuestionVariable.php");
				while ($data = $ilDB->fetchAssoc($result))
				{
					$varObj = new assFormulaQuestionVariable($data["variable"], $data["range_min"], $data["range_max"], $this->getUnit($data["unit_fi"]), $data["varprecision"], $data["intprecision"]);
					$this->addVariable($varObj);
				}
			}
			// load results
			$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_res WHERE question_fi = %s",
				array('integer'),
				array($question_id)
			);
			if ($result->numRows() > 0)
			{
				$this->getPlugin()->includeClass("class.assFormulaQuestionResult.php");
				while ($data = $ilDB->fetchAssoc($result))
				{
					$resObj = new assFormulaQuestionResult($data["result"], $data["range_min"], $data["range_max"], $data["tolerance"], $this->getUnit($data["unit_fi"]), $data["formula"], $data["points"], $data["resprecision"], $data["rating_simple"], $data["rating_sign"], $data["rating_value"], $data["rating_unit"]);
					$this->addResult($resObj);
				}
			}

			// load result units
			$result = $ilDB->queryF("SELECT * FROM il_qpl_qst_fq_res_unit WHERE question_fi = %s",
				array('integer'),
				array($question_id)
			);
			if ($result->numRows() > 0)
			{
				while ($data = $ilDB->fetchAssoc($result))
				{
					$unit = $this->getUnit($data["unit_fi"]);
					$resObj = $this->getResult($data["result"]);
					$this->addResultUnit($resObj, $unit);
				}
			}
		}
		parent::loadFromDb($question_id);
	}
	
	/**
	* Duplicates an assFormulaQuestion
	*
	* @access public
	*/
	function duplicate($for_test = true, $title = "", $author = "", $owner = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$this_id = $this->getId();
		$clone = $this;
		include_once ("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;
		if ($title)
		{
			$clone->setTitle($title);
		}

		if ($author)
		{
			$clone->setAuthor($author);
		}
		if ($owner)
		{
			$clone->setOwner($owner);
		}

		if ($for_test)
		{
			$clone->saveToDb($original_id);
		}
		else
		{
			$clone->saveToDb();
		}

		// copy question page content
		$clone->copyPageOfQuestion($this_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this_id);
		// duplicate the generic feedback
		$clone->duplicateFeedbackGeneric($this_id);

		return $clone->id;
	}

	/**
	* Copies an assFormulaQuestion object
	*
	* @access public
	*/
	function copyObject($target_questionpool, $title = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$clone = $this;
		include_once ("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;
		$source_questionpool = $this->getObjId();
		$clone->setObjId($target_questionpool);
		if ($title)
		{
			$clone->setTitle($title);
		}
		$clone->saveToDb();

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);
		// duplicate the generic feedback
		$clone->duplicateFeedbackGeneric($original_id);

		return $clone->id;
	}

	/**
	* Returns the maximum points, a learner can reach answering the question
	*
	* @see $points
	*/
	public function getMaximumPoints()
	{
		$points = 0;
		foreach ($this->results as $result) 
		{
			$points += $result->getPoints();
		}
		return $points;
	}

	/**
	* Returns the points, a learner has reached answering the question
	* The points are calculated from the given answers including checks
	* for all special scoring options in the test container.
	*
	* @param integer $user_id The database ID of the learner
	* @param integer $test_id The database Id of the test containing the question
	* @access public
	*/
	function calculateReachedPoints($active_id, $pass = NULL)
	{
		global $ilLog;
		
		$found_values = array();
		if (is_null($pass))
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}
		$solutions =& $this->getSolutionValues($active_id, $pass);
		$user_solution = array();
		foreach ($solutions as $idx => $solution_value)
		{
			if (preg_match("/^(\\\$v\\d+)$/", $solution_value["value1"], $matches))
			{
				$user_solution[$matches[1]] = $solution_value["value2"];
				$varObj = $this->getVariable($solution_value["value1"]);
				$varObj->setValue($solution_value["value2"]);
			}
			else if (preg_match("/^(\\\$r\\d+)$/", $solution_value["value1"], $matches))
			{
				if (!array_key_exists($matches[1], $user_solution)) $user_solution[$matches[1]] = array();
				$user_solution[$matches[1]]["value"] = $solution_value["value2"];
			}
			else if (preg_match("/^(\\\$r\\d+)_unit$/", $solution_value["value1"], $matches))
			{
				if (!array_key_exists($matches[1], $user_solution)) $user_solution[$matches[1]] = array();
				$user_solution[$matches[1]]["unit"] = $solution_value["value2"];
			}
		}
		$points = 0;
		foreach ($this->getResults() as $result)
		{
			$points += $result->getReachedPoints($this->getVariables(), $this->getResults(), $user_solution[$result->getResult()]["value"], $user_solution[$result->getResult()]["unit"], $this->getUnits());
		}
		$points = parent::calculateReachedPoints($active_id, $pass = NULL, $points);
		return $points;
	}
	
	/**
	* Saves the learners input of the question to the database
	*
	* @param integer $test_id The database id of the test containing this question
  * @return boolean Indicates the save status (true if saved successful, false otherwise)
	* @access public
	* @see $answers
	*/
	function saveWorkingData($active_id, $pass = NULL)
	{
		global $ilDB;
		global $ilUser;

		if (is_null($pass))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}
		$entered_values = FALSE;
		foreach ($_POST as $key => $value)
		{
			if (preg_match("/^result_(\\\$r\\d+)$/", $key, $matches))
			{
				if (strlen($value)) $entered_values = TRUE;
				$result = $ilDB->queryF("SELECT solution_id FROM tst_solutions WHERE active_fi = %s AND pass = %s AND question_fi = %s  AND " . $ilDB->like('value1', 'clob', $matches[1]),
					array('integer','integer','integer'),
					array($active_id, $pass, $this->getId())
				);
				if ($result->numRows())
				{
					while ($row = $ilDB->fetchAssoc($result))
					{
						$affectedRows = $ilDB->manipulateF("DELETE FROM tst_solutions WHERE solution_id = %s",
							array('integer'),
							array($row['solution_id'])
						);
					}
				}

				$next_id = $ilDB->nextId('tst_solutions');
				$affectedRows = $ilDB->insert("tst_solutions", array(
					"solution_id" => array("integer", $next_id),
					"active_fi" => array("integer", $active_id),
					"question_fi" => array("integer", $this->getId()),
					"value1" => array("clob", $matches[1]),
					"value2" => array("clob", str_replace(",", ".", $value)),
					"pass" => array("integer", $pass),
					"tstamp" => array("integer", time())
				));
			}
			else if (preg_match("/^result_(\\\$r\\d+)_unit$/", $key, $matches))
			{
				if ($value > -1) $entered_values = TRUE;

				$result = $ilDB->queryF("SELECT solution_id FROM tst_solutions WHERE active_fi = %s AND pass = %s AND question_fi = %s AND " . $ilDB->like('value1', 'clob', $matches[1] . "_unit"),
					array('integer','integer','integer'),
					array($active_id, $pass, $this->getId())
				);
				if ($result->numRows())
				{
					while ($row = $ilDB->fetchAssoc($result))
					{
						$affectedRows = $ilDB->manipulateF("DELETE FROM tst_solutions WHERE solution_id = %s",
							array('integer'),
							array($row['solution_id'])
						);
					}
				}

				$next_id = $ilDB->nextId('tst_solutions');
				$affectedRows = $ilDB->insert("tst_solutions", array(
					"solution_id" => array("integer", $next_id),
					"active_fi" => array("integer", $active_id),
					"question_fi" => array("integer", $this->getId()),
					"value1" => array("clob", $matches[1] . "_unit"),
					"value2" => array("clob", $value),
					"pass" => array("integer", $pass),
					"tstamp" => array("integer", time())
				));
			}
		}
		if ($entered_values)
		{
			include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}
		else
		{
			include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}
    parent::saveWorkingData($active_id, $pass);
		return true;
	}

	/**
	* Returns the question type of the question
	*
	* @return string The question type of the question
	*/
	public function getQuestionType()
	{
		return "assFormulaQuestion";
	}
	
	/**
	* Returns the name of the additional question data table in the database
	*
	* @return string The additional table name
	*/
	public function getAdditionalTableName()
	{
		return "";
	}
	
	/**
	* Returns the name of the answer table in the database
	*
	* @return string The answer table name
	*/
	public function getAnswerTableName()
	{
		return "";
	}
	
	/**
	* Deletes datasets from answers tables
	*
	* @param integer $question_id The question id which should be deleted in the answers table
	* @access public
	*/
	function deleteAnswers($question_id)
	{
		global $ilDB;

		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_var WHERE question_fi = %s",
			array('integer'),
			array($question_id)
		);

		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_res WHERE question_fi = %s",
			array('integer'),
			array($question_id)
		);

		$affectedRows = $ilDB->manipulateF("DELETE FROM il_qpl_qst_fq_res_unit WHERE question_fi = %s",
			array('integer'),
			array($question_id)
		);
	}

	/**
	* Collects all text in the question which could contain media objects
	* which were created with the Rich Text Editor
	*/
	function getRTETextWithMediaObjects()
	{
		$text = parent::getRTETextWithMediaObjects();
		return $text;
	}

	/**
	* Creates an Excel worksheet for the detailed cumulated results of this question
	*
	* @param object $worksheet Reference to the parent excel worksheet
	* @param object $startrow Startrow of the output in the excel worksheet
	* @param object $active_id Active id of the participant
	* @param object $pass Test pass
	* @param object $format_title Excel title format
	* @param object $format_bold Excel bold format
	* @param array $eval_data Cumulated evaluation data
	* @access public
	*/
	public function setExportDetailsXLS(&$worksheet, $startrow, $active_id, $pass, &$format_title, &$format_bold)
	{
		include_once ("./classes/class.ilExcelUtils.php");
		$solution = $this->getSolutionValues($active_id, $pass);
		$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($this->getPlugin()->txt($this->getQuestionType())), $format_title);
		$worksheet->writeString($startrow, 1, ilExcelUtils::_convert_text($this->getTitle()), $format_title);
		$i = 1;
		foreach ($solution as $solutionvalue)
		{
			$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($solutionvalue["value1"]), $format_bold);
			if (strpos($solutionvalue["value1"], "_unit"))
			{
				$unit = $this->getUnit($solutionvalue["value2"]);
				if (is_object($unit))
				{
					$worksheet->write($startrow + $i, 1, $unit->getUnit());
				}
			}
			else
			{
				$worksheet->write($startrow + $i, 1, $solutionvalue["value2"]);
			}
			if (preg_match("/(\\\$v\\d+)/", $solutionvalue["value1"], $matches))
			{
				$var = $this->getVariable($solutionvalue["value1"]);
				if (is_object($var) && (is_object($var->getUnit())))
				{
					$worksheet->write($startrow + $i, 2, $var->getUnit()->getUnit());
				}
			}
			$i++;
		}
		return $startrow + $i + 1;
	}
	
	/**
	* Creates a question from a QTI file
	*
	* Receives parameters from a QTI parser and creates a valid ILIAS question object
	*
	* @param object $item The QTI item object
	* @param integer $questionpool_id The id of the parent questionpool
	* @param integer $tst_id The id of the parent test if the question is part of a test
	* @param object $tst_object A reference to the parent test object
	* @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	* @param array $import_mapping An array containing references to included ILIAS objects
	* @access public
	*/
	function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass("import/qti12/class.assFormulaQuestionImport.php");
		$import = new assFormulaQuestionImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}
	
	/**
	* Returns a QTI xml representation of the question and sets the internal
	* domxml variable with the DOM XML representation of the QTI xml representation
	*
	* @return string The QTI xml representation of the question
	* @access public
	*/
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass("export/qti12/class.assFormulaQuestionExport.php");
		$export = new assFormulaQuestionExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}

	/**
	* Returns the best solution for a given pass of a participant
	*
	* @return array An associated array containing the best solution
	* @access public
	*/
	public function getBestSolution($active_id, $pass)
	{
		$user_solution = array();
		$user_solution["active_id"] = $active_id;
		$user_solution["pass"] = $pass;
		$solutions =& $this->getSolutionValues($active_id, $pass);
		foreach ($solutions as $idx => $solution_value)
		{
			if (preg_match("/^(\\\$v\\d+)$/", $solution_value["value1"], $matches))
			{
				$user_solution[$matches[1]] = $solution_value["value2"];
				$varObj = $this->getVariable($matches[1]);
				$varObj->setValue($solution_value["value2"]);
			}
		}
		foreach ($this->getResults() as $result)
		{
			$resVal = $result->calculateFormula($this->getVariables(), $this->getResults());
			$user_solution[$result->getResult()]["value"] = $resVal;
			if (is_object($result->getUnit()))
			{
				$user_solution[$result->getResult()]["unit"] = $result->getUnit()->getId();
			}
		}
		return $user_solution;
	}

	/**
	* Object getter
	*/
	public function __get($value)
	{
		switch ($value)
		{
			case "resultunits":
				return $this->resultunits;
				break;
			default:
				return parent::__get($value);
				break;
		}
	}
}

?>
