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

include_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Single choice question GUI representation
*
* The assFormulaQuestionGUI class encapsulates the GUI representation
* for single choice questions.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.assFormulaQuestionGUI.php 944 2009-11-09 16:11:30Z hschottm $
* @ingroup ModulesTestQuestionPool
* @ilctrl_iscalledby assFormulaQuestionGUI: ilObjQuestionPoolGUI
* */
class assFormulaQuestionGUI extends assQuestionGUI
{
	private $newUnitId;
	
	/**
	* assFormulaQuestionGUI constructor
	*
	* The constructor takes possible arguments an creates an instance of the assFormulaQuestionGUI object.
	*
	* @param integer $id The database id of a single choice question object
	* @access public
	*/
	function assFormulaQuestionGUI(
			$id = -1
	)
	{
		$this->assQuestionGUI();
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$pl = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
		$pl->includeClass("class.assFormulaQuestion.php");
		$this->object = new assFormulaQuestion();
		$this->newUnitId = null;
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	function getCommand($cmd)
	{
		if (preg_match("/suggestrange_(.*?)/", $cmd, $matches))
		{
			$cmd = "suggestRange";
		}
		return $cmd;
	}

	/**
	* Suggest a range for a result
	*
	* @access public
	*/
	function suggestRange()
	{
		if ($this->writePostData())
		{
			ilUtil::sendInfo($this->getErrorMessage());
		}
		$this->editQuestion();
	}

	/**
	* Creates an output of the edit form for the question
	*
	* Creates an output of the edit form for the question
	*
	* @access public
	*/
	function editQuestion()
	{
		$this->tpl->addJavascript("./Services/JavaScript/js/Basic.js");
		$javascript = "<script type=\"text/javascript\">ilAddOnLoad(initialSelect);\n".
			"function initialSelect() {\n%s\n}</script>";
		$this->getQuestionTemplate();
		
		$pl = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
		$tpl_qd = $pl->getTemplate("tpl.il_as_qpl_formulaquestion.html");

		$this->object->getPlugin()->includeClass("class.GUIPopUpButton.php");
		$allUnits = new GUIPopUpButton();
		$allUnits->setList($this->object->getCategorizedUnits());
		$allUnits->setDisplayString("displayString");
		$allUnits->setValue("id");
		$allUnits->setListItemClassPath("class");

		if (count($this->object->getVariables()))
		{
			foreach ($this->object->getVariables() as $variable)
			{
				$tpl_qd->setCurrentBlock("variable");
				$allUnits->setMultiple(null);
				$allUnits->setNoSelection($this->object->getPlugin()->txt("no_selection"));
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
				$tpl_qd->setVariable("VARIABLE_UNIT", $allUnits->getHTML());
				$tpl_qd->setVariable("TEXT_VARIABLE", $variable->getVariable());
				$tpl_qd->setVariable("TEXT_SELECT_UNIT", $this->object->getPlugin()->txt("select_unit"));
				if (strlen($variable->getRangeMin())) $tpl_qd->setVariable("VALUE_RANGE_MIN", ' value="' . ilUtil::prepareFormOutput($variable->getRangeMin()) . '"');
				if (strlen($variable->getRangeMax())) $tpl_qd->setVariable("VALUE_RANGE_MAX", ' value="' . ilUtil::prepareFormOutput($variable->getRangeMax()) . '"');
				if (strlen($variable->getPrecision())) $tpl_qd->setVariable("VALUE_PRECISION", ' value="' . ilUtil::prepareFormOutput($variable->getPrecision()) . '"');
				if (strlen($variable->getIntprecision())) $tpl_qd->setVariable("VALUE_INTPRECISION", ' value="' . ilUtil::prepareFormOutput($variable->getIntprecision()) . '"');
				$tpl_qd->parseCurrentBlock();
			}
			$tpl_qd->setCurrentBlock("variables");
			$tpl_qd->setVariable("TEXT_VARIABLES", $this->object->getPlugin()->txt("variables"));
			$tpl_qd->setVariable("TEXT_RANGE_MIN", $this->object->getPlugin()->txt("range_min"));
			$tpl_qd->setVariable("TEXT_RANGE_MAX", $this->object->getPlugin()->txt("range_max"));
			$tpl_qd->setVariable("TEXT_UNIT", $this->object->getPlugin()->txt("unit"));
			$tpl_qd->setVariable("TEXT_PRECISION", $this->object->getPlugin()->txt("precision"));
			$tpl_qd->setVariable("TEXT_INTPRECISION", $this->object->getPlugin()->txt("intprecision"));
			$tpl_qd->parseCurrentBlock();
		}

		if (count($this->object->getResults()))
		{
			foreach ($this->object->getResults() as $result)
			{
				$tpl_qd->setCurrentBlock("initrating");
				$advanced_rating = $this->object->canUseAdvancedRating($result);
				$tpl_qd->setVariable("RESULT", $result->getResult());
				$tpl_qd->setVariable("VISIBILITY", ($result->getRatingSimple() || !$advanced_rating) ? "hidden" : "visible");
				$tpl_qd->parseCurrentBlock();
			}
			foreach ($this->object->getResults() as $result)
			{
				$tpl_qd->setCurrentBlock("result_header");
				$tpl_qd->setVariable("TEXT_RANGE_MIN", $this->object->getPlugin()->txt("range_min"));
				$tpl_qd->setVariable("TEXT_RANGE_MAX", $this->object->getPlugin()->txt("range_max"));
				$tpl_qd->setVariable("TEXT_UNIT", $this->object->getPlugin()->txt("unit"));
				$tpl_qd->setVariable("TEXT_TOLERANCE", $this->object->getPlugin()->txt("tolerance"));
				$tpl_qd->setVariable("TEXT_PRECISION", $this->object->getPlugin()->txt("precision"));
				$tpl_qd->setVariable("TEXT_POINTS", $this->object->getPlugin()->txt("points"));
				$tpl_qd->parseCurrentBlock();

				$selectedvalues = array();
				foreach ($this->object->getUnits() as $unit)
				{
					if ($this->object->hasResultUnit($result, $unit->getId()))
					{
						array_push($selectedvalues, $unit->getId());
					}
				}
				$advanced_rating = $this->object->canUseAdvancedRating($result);
				if (!$advanced_rating)
				{
					$tpl_qd->setCurrentBlock("force_simple_rating");
					$tpl_qd->setVariable("TEXT_RESULT", $result->getResult());
					$tpl_qd->parseCurrentBlock();
				}
				$tpl_qd->setCurrentBlock("result");
				
				$allUnits->setMultiple(null);
				$allUnits->setSize("1");
				$allUnits->setStyle(null);
				$allUnits->setNoSelection($this->object->getPlugin()->txt("no_selection"));
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
				$tpl_qd->setVariable("RESULT_UNIT", $allUnits->getHTML());
				
				$allUnits->setMultiple("multiple");
				$allUnits->setSize("10");
				$allUnits->setNoSelection(null);
				$allUnits->setStyle("width: 300px;");
				$allUnits->setName("units_" . $result->getResult() . "[]");
				$allUnits->setId("units_" . $result->getResult());
				$allUnits->setSelectedValue($selectedvalues);
				$tpl_qd->setVariable("POPUP_AVAILABLE_UNITS", $allUnits->getHTML());
				$tpl_qd->setVariable("TEXT_RESULT", $result->getResult());
				$tpl_qd->setVariable("TEXT_SUGGEST_RANGE", $this->object->getPlugin()->txt("suggest_range"));
				$tpl_qd->setVariable("TEXT_SELECT_UNIT", $this->object->getPlugin()->txt("select_unit"));
				if (strlen($result->getTolerance())) $tpl_qd->setVariable("VALUE_TOLERANCE", ' value="' . ilUtil::prepareFormOutput($result->getTolerance()) . '"');
				if (strlen($result->getRatingSign())) $tpl_qd->setVariable("VALUE_RATING_SIGN", ' value="' . ilUtil::prepareFormOutput($result->getRatingSign()) . '"');
				if (strlen($result->getRatingValue())) $tpl_qd->setVariable("VALUE_RATING_VALUE", ' value="' . ilUtil::prepareFormOutput($result->getRatingValue()) . '"');
				if (strlen($result->getRatingUnit())) $tpl_qd->setVariable("VALUE_RATING_UNIT", ' value="' . ilUtil::prepareFormOutput($result->getRatingUnit()) . '"');
				if (strlen($result->getPoints())) $tpl_qd->setVariable("VALUE_POINTS", ' value="' . ilUtil::prepareFormOutput($result->getPoints()) . '"');
				if (strlen($result->getPrecision())) $tpl_qd->setVariable("VALUE_PRECISION", ' value="' . ilUtil::prepareFormOutput($result->getPrecision()) . '"');
				$tpl_qd->setVariable("TEXT_FORMULA", $this->object->getPlugin()->txt("formula"));
				if (strlen($result->getFormula()))
				{
					$tpl_qd->setVariable("VALUE_FORMULA", ' value="' . ilUtil::prepareFormOutput($result->getFormula()) . '"');
				}
				$tpl_qd->setVariable("TEXT_RATING_SIMPLE", $this->object->getPlugin()->txt("rating_simple"));
				if (!$advanced_rating)
				{
					$tpl_qd->setVariable("CHECKED_RATING_SIMPLE", ' checked="checked"');
					$tpl_qd->setVariable("DISABLED_RATING_SIMPLE", ' disabled="disabled"');
				}
				else
				{
					if ($result->getRatingSimple())
					{
						$tpl_qd->setVariable("CHECKED_RATING_SIMPLE", ' checked="checked"');
					}
				}
				$tpl_qd->setVariable("TEXT_AVAILABLE_RESULT_UNITS", $this->object->getPlugin()->txt("result_units"));
				$tpl_qd->setVariable("TEXT_RATING_SIGN", $this->object->getPlugin()->txt("rating_sign"));
				$tpl_qd->setVariable("TEXT_RATING_VALUE", $this->object->getPlugin()->txt("rating_value"));
				$tpl_qd->setVariable("TEXT_RATING_UNIT", $this->object->getPlugin()->txt("rating_unit"));
				
				if (strcmp($this->ctrl->getCmd(), "suggestrange_" . $result->getResult()) == 0)
				{
					// suggest a range for the result
					if (strlen($result->substituteFormula($this->object->getVariables(), $this->object->getResults())))
					{
						$result->suggestRange($this->object->getVariables(), $this->object->getResults());
					}
				}
				if (strlen(trim($result->getRangeMin()))) $tpl_qd->setVariable("VALUE_RANGE_MIN", ' value="' . ilUtil::prepareFormOutput($result->getRangeMin()) . '"');
				if (strlen(trim($result->getRangeMax()))) $tpl_qd->setVariable("VALUE_RANGE_MAX", ' value="' . ilUtil::prepareFormOutput($result->getRangeMax()) . '"');

				$tpl_qd->parseCurrentBlock();
			}
			$tpl_qd->setCurrentBlock("results");
			$tpl_qd->setVariable("TEXT_RESULTS", $this->object->getPlugin()->txt("results"));
			$tpl_qd->parseCurrentBlock();
		}

		$internallinks = array(
			"lm" => $this->lng->txt("obj_lm"),
			"st" => $this->lng->txt("obj_st"),
			"pg" => $this->lng->txt("obj_pg"),
			"glo" => $this->lng->txt("glossary_term")
		);
		foreach ($internallinks as $key => $value)
		{
			$tpl_qd->setCurrentBlock("internallink");
			$tpl_qd->setVariable("TYPE_INTERNAL_LINK", $key);
			$tpl_qd->setVariable("TEXT_INTERNAL_LINK", $value);
			$tpl_qd->parseCurrentBlock();
		}
		
		if (count($this->object->suggested_solutions))
		{
			$tpl_qd->setCurrentBlock("remove_solution");
			$tpl_qd->setVariable("BUTTON_REMOVE_SOLUTION", $this->lng->txt("remove"));
			$tpl_qd->parseCurrentBlock();

			$solution_array = $this->object->getSuggestedSolution(0);
			include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
			$href = assQuestion::_getInternalLinkHref($solution_array["internal_link"]);
			$tpl_qd->setVariable("VALUE_SOLUTION_HINT", $solution_array["internal_link"]);
			$tpl_qd->setVariable("TEXT_VALUE_SOLUTION_HINT", " <a href=\"$href\" target=\"content\">" . $this->lng->txt("solution_hint"). "</a> ");
			$tpl_qd->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("change"));
		}
		else
		{
			$tpl_qd->setVariable("BUTTON_ADD_SOLUTION", $this->lng->txt("add"));
		}

		$tpl_qd->setVariable("QUESTION_ID", $this->object->getId());
		$tpl_qd->setVariable("TEXT_TITLE", $this->lng->txt("title"));
		$tpl_qd->setVariable("VALUE_TITLE", ilUtil::prepareFormOutput($this->object->getTitle()));
		$tpl_qd->setVariable("TEXT_COMMENT", $this->lng->txt("description"));
		$tpl_qd->setVariable("VALUE_COMMENT", ilUtil::prepareFormOutput($this->object->getComment()));
		$tpl_qd->setVariable("TEXT_AUTHOR", $this->lng->txt("author"));
		$tpl_qd->setVariable("VALUE_AUTHOR", ilUtil::prepareFormOutput($this->object->getAuthor()));
		$tpl_qd->setVariable("TEXT_QUESTION", $this->lng->txt("question"));
		$questiontext = $this->object->getQuestion();
		$tpl_qd->setVariable("VALUE_QUESTION", ilUtil::prepareFormOutput($this->object->prepareTextareaOutput($questiontext)));

		$est_working_time = $this->object->getEstimatedWorkingTime();
		$tpl_qd->setVariable("TEXT_WORKING_TIME", $this->lng->txt("working_time"));
		$tpl_qd->setVariable("TIME_FORMAT", $this->lng->txt("time_format"));
		$tpl_qd->setVariable("VALUE_WORKING_TIME", ilUtil::makeTimeSelect("Estimated", false, $est_working_time[h], $est_working_time[m], $est_working_time[s]));

		$tpl_qd->setVariable("TEXT_SOLUTION_HINT", $this->lng->txt("solution_hint"));

		$tpl_qd->setVariable("SAVE",$this->lng->txt("save"));
		$tpl_qd->setVariable("SAVE_EDIT", $this->lng->txt("save_edit"));
		$tpl_qd->setVariable("CANCEL",$this->lng->txt("cancel"));
		$tpl_qd->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->ctrl->setParameter($this, "sel_question_types", "assFormulaQuestion");
		$tpl_qd->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "parseQuestion"));
		$tpl_qd->setVariable("TEXT_QUESTION_TYPE", $this->outQuestionType());
		$tpl_qd->setVariable("PARSE_QUESTION", $this->object->getPlugin()->txt("parseQuestion"));
		
		$this->tpl->setCurrentBlock("HeadContent");
		$this->tpl->setVariable("CONTENT_BLOCK", sprintf($javascript, "document.frmFormula.title.focus();"));
		$this->tpl->parseCurrentBlock();

		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");

		$this->tpl->setVariable("QUESTION_DATA", $tpl_qd->get());
		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDomEvent();
		$this->tpl->addCss($this->object->getPlugin()->getStyleSheetLocation("formula.css"));
	}
	
	public function parseQuestion()
	{
		$this->writePostData();
		$this->editQuestion();
	}

	/**
	* check input fields
	*/
	function checkInput()
	{
		$cmd = $this->ctrl->getCmd();

		if ((!$_POST["title"]) or (!$_POST["author"]) or (!$_POST["question"]))
		{
			$this->addErrorMessage($this->lng->txt("fill_out_all_required_fields"));
			return FALSE;
		}
		
		
		return TRUE;
	}

	/**
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* @return integer A positive value, if one of the required fields wasn't set, else 0
	* @access private
	*/
	function writePostData()
	{
		global $ilLog;
		$this->setErrorMessage("");
		$checked = $this->checkInput();

		$this->object->setTitle(ilUtil::stripSlashes($_POST["title"]));
		$this->object->setAuthor(ilUtil::stripSlashes($_POST["author"]));
		$this->object->setComment(ilUtil::stripSlashes($_POST["comment"]));
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$questiontext = ilUtil::stripSlashes($_POST["question"], false, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
		$this->object->setQuestion($questiontext);
		$this->object->setSuggestedSolution($_POST["solution_hint"], 0);
		$saved = $this->writeOtherPostData();
		$this->object->parseQuestionText();
		$found_vars = array();
		$found_results = array();
		foreach ($_POST as $key => $value)
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

		if (!$this->object->checkForDuplicateVariables())
		{
			$this->addErrorMessage($this->object->getPlugin()->txt("err_duplicate_variables"));
			$checked = FALSE;
		}
		if (!$this->object->checkForDuplicateResults())
		{
			$this->addErrorMessage($this->object->getPlugin()->txt("err_duplicate_results"));
			$checked = FALSE;
		}

		foreach ($found_vars as $variable)
		{
			if ($this->object->getVariable($variable) != null)
			{
				$this->object->getPlugin()->includeClass("class.assFormulaQuestionVariable.php");
				$varObj = new assFormulaQuestionVariable($variable, $_POST["range_min_$variable"], $_POST["range_max_$variable"], $this->object->getUnit($_POST["unit_$variable"]), $_POST["precision_$variable"], $_POST["intprecision_$variable"]);
				// ERROR HANDLING
				if (strlen($varObj->getRangeMin()) == 0)
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_no_min_range"));
					$checked = FALSE;
				}
				if (strlen($varObj->getRangeMax()) == 0)
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_no_max_range"));
					$checked = FALSE;
				}
				if (strlen($varObj->getPrecision()) == 0)
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_no_precision"));
					$checked = FALSE;
				}
				if (!is_numeric($varObj->getPrecision()))
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_wrong_precision"));
					$checked = FALSE;
				}
				if ($checked)
				{
					if ((!is_integer($varObj->getPrecision())) || ($varObj->getPrecision() < 0))
					{
						$this->addErrorMessage($this->object->getPlugin()->txt("err_wrong_precision"));
						$checked = FALSE;
					}
				}
				if ($checked)
				{
					if (!is_numeric($varObj->getRangeMin()))
					{
						$this->addErrorMessage($this->object->getPlugin()->txt("err_no_min_range_number"));
						$checked = FALSE;
					}
				}
				if ($checked)
				{
					if (!is_numeric($varObj->getRangeMax()))
					{
						$this->addErrorMessage($this->object->getPlugin()->txt("err_no_max_range_number"));
						$checked = FALSE;
					}
				}
				if ($checked)
				{
					if ($varObj->getRangeMin() > $varObj->getRangeMax())
					{
						$this->addErrorMessage($this->object->getPlugin()->txt("err_range"));
						$checked = FALSE;
					}
				}
				// END ERROR HANDLING
				$this->object->addVariable($varObj);
			}
		}
		foreach ($found_results as $result)
		{
			if ($this->object->getResult($result) != null)
			{
				$use_simple_rating = ($_POST["rating_simple_$result"] == 1) ? TRUE : FALSE;
				$this->object->getPlugin()->includeClass("class.assFormulaQuestionResult.php");
				$resObj = new assFormulaQuestionResult(
					$result, 
					$_POST["range_min_$result"], 
					$_POST["range_max_$result"], 
					$_POST["tolerance_$result"], 
					$this->object->getUnit($_POST["unit_$result"]), 
					$_POST["formula_$result"], 
					$_POST["points_$result"], 
					$_POST["precision_$result"], 
					$use_simple_rating, 
					($_POST["rating_simple_$result"] != 1) ? $_POST["rating_sign_$result"] : "",
					($_POST["rating_simple_$result"] != 1) ? $_POST["rating_value_$result"] : "",
					($_POST["rating_simple_$result"] != 1) ? $_POST["rating_unit_$result"] : ""
				);
				$this->object->addResult($resObj);
				$this->object->addResultUnits($resObj, $_POST["units_$result"]);
				$advanced_rating = $this->object->canUseAdvancedRating($resObj);
				// ERROR HANDLING
				if (!$advanced_rating && !$use_simple_rating)
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_rating_advanced_not_allowed"));
					$checked = FALSE;
				}
				if ($_POST["rating_simple_$result"] != 1)
				{
					$percentage = $_POST["rating_sign_$result"] + $_POST["rating_value_$result"] + $_POST["rating_unit_$result"];
					if ($percentage != 100)
					{
						$this->addErrorMessage($this->object->getPlugin()->txt("err_wrong_rating_advanced"));
						$checked = FALSE;
					}
				}
				if ((!is_integer($resObj->getPrecision())) || ($resObj->getPrecision() < 0))
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_wrong_precision"));
					$checked = FALSE;
				}
				if (strlen($resObj->getTolerance))
				{
					if (!is_numeric($resObj->getTolerance()))
					{
						$this->addErrorMessage($this->object->getPlugin()->txt("err_tolerance_wrong_value"));
						$checked = FALSE;
					}
					if ($checked)
					{
						if (($resObj->getTolerance() < 0) || ($resObj->getTolerance() > 100))
						{
							$this->addErrorMessage($this->object->getPlugin()->txt("err_tolerance_wrong_value"));
							$checked = FALSE;
						}
					}
				}
				if (strlen($resObj->getFormula()) == 0)
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_no_formula"));
					$checked = FALSE;
				}
				if (strpos($resObj->getFormula(), $resObj->getResult()) !== FALSE)
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("errRecursionInResult"));
					$checked = FALSE;
				}
				if ((strlen($resObj->getPoints()) == 0) || (!is_numeric($resObj->getPoints())))
				{
					$this->addErrorMessage($this->object->getPlugin()->txt("err_wrong_points"));
					$checked = FALSE;
				}
				// END ERROR HANDLING
			}
		}

		// Set the question id from a hidden form parameter
		if ($_POST["id"] > 0)
		{
			$this->object->setId($_POST["id"]);
		}

		if ($saved)
		{
			// If the question was saved automatically before an upload, we have to make
			// sure, that the state after the upload is saved. Otherwise the user could be
			// irritated, if he presses cancel, because he only has the question state before
			// the upload process.
			$this->object->saveToDb();
			$this->ctrl->setParameter($this, "q_id", $this->object->getId());
		}
		return ($checked) ? 0 : 1;
	}

	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions, $show_feedback); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE, $result_output = FALSE, $show_question_only = TRUE, $show_feedback = FALSE, $show_correct_solution = FALSE)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = "";
		if (($active_id > 0) && (!$show_correct_solution))
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$user_solution["active_id"] = $active_id;
			$user_solution["pass"] = $pass;
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				if (preg_match("/^(\\\$v\\d+)$/", $solution_value["value1"], $matches))
				{
					$user_solution[$matches[1]] = $solution_value["value2"];
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
		}
		else if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$user_solution = $this->object->getBestSolution($active_id, $pass);
		}
		$pl = $this->object->getPlugin();
		$template = $pl->getTemplate("tpl.il_as_qpl_formulaquestion_output_solution.html");
		$questiontext = $this->object->substituteVariables($user_solution, $graphicalOutput, TRUE, $result_output);
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);
		$solutionoutput = $solutiontemplate->get(); 
		if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = $this->getILIASPage($solutionoutput);
		}
		return $solutionoutput;
	}
	
	function getPreview($show_question_only = FALSE)
	{
		$pl = $this->object->getPlugin();
		$template = $pl->getTemplate("tpl.il_as_qpl_formulaquestion_output.html");
		$questiontext = $this->object->substituteVariables();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		if (!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = null;
		if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			$user_solution["active_id"] = $active_id;
			$user_solution["pass"] = $pass;
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				if (preg_match("/^(\\\$v\\d+)$/", $solution_value["value1"], $matches))
				{
					$user_solution[$matches[1]] = $solution_value["value2"];
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
		}
		
		// generate the question output
		$pl = $this->object->getPlugin();
		$template = $pl->getTemplate("tpl.il_as_qpl_formulaquestion_output.html");

		$questiontext = $this->object->substituteVariables($user_solution);

		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput;
	}

	function addSuggestedSolution()
	{
		$_SESSION["subquestion_index"] = 0;
		if ($_POST["cmd"]["addSuggestedSolution"])
		{
			if ($this->writePostData())
			{
				ilUtil::sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
		}
		$this->object->saveToDb();
		$this->ctrl->setParameter($this, "q_id", $this->object->getId());
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate();
		parent::addSuggestedSolution();
	}

	/**
	* Saves the feedback for a single choice question
	*
	* Saves the feedback for a single choice question
	*
	* @access public
	*/
	function saveFeedback()
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$this->object->saveFeedbackGeneric(0, ilUtil::stripSlashes($_POST["feedback_incomplete"], false, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")));
		$this->object->saveFeedbackGeneric(1, ilUtil::stripSlashes($_POST["feedback_complete"], false, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment")));
		$this->object->cleanupMediaObjectUsage();
		parent::saveFeedback();
	}

	/**
	* Creates the output of the feedback page for a single choice question
	*
	* Creates the output of the feedback page for a single choice question
	*
	* @access public
	*/
	function feedback()
	{
		$template = $this->object->getPlugin()->getTemplate("tpl.il_as_qpl_formulaquestion_feedback.html");
		$template->setVariable("FEEDBACK_TEXT", $this->lng->txt("feedback"));
		$template->setVariable("FEEDBACK_COMPLETE", $this->lng->txt("feedback_complete_solution"));
		$template->setVariable("VALUE_FEEDBACK_COMPLETE", ilUtil::prepareFormOutput($this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(1)), FALSE));
		$template->setVariable("FEEDBACK_INCOMPLETE", $this->lng->txt("feedback_incomplete_solution"));
		$template->setVariable("VALUE_FEEDBACK_INCOMPLETE", ilUtil::prepareFormOutput($this->object->prepareTextareaOutput($this->object->getFeedbackGeneric(0)), FALSE));
		$template->setVariable("FEEDBACK_ANSWERS", $this->lng->txt("feedback_answers"));
		$template->setVariable("SAVE", $this->lng->txt("save"));
		$template->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex");
		include_once "./classes/class.ilObject.php";
		$obj_id = $_GET["q_id"];
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, $obj_type, "assessment");
		$this->tpl->setVariable("ADM_CONTENT", $template->get());
	}
	
	/**
	* Adds a new category to the units
	*
	* @access public
	*/
	function addCategory()
	{
		$this->object->saveNewUnitCategory($_POST["addCategory"]);
		$this->units();
	}
	
	/**
	* Adds a new unit
	*
	* @access public
	*/
	function addUnit()
	{
		$this->newUnitId = $this->object->createNewUnit($_POST["categories"], $_POST["addUnit"]);
		$this->units();
	}
	
	/**
	* Save a unit
	*
	* @access public
	*/
	function saveUnit()
	{
		$error = false;
		if (strlen($_POST["unitname"]) == 0)
		{
			ilUtil::sendInfo($this->object->getPlugin()->txt("err_no_unitname"));
			$error = true;
		}
		if (!is_numeric($_POST["unitfactor"]))
		{
			ilUtil::sendInfo($this->object->getPlugin()->txt("err_wrong_unitfactor"));
			$error = true;
		}
		if (!$error)
		{
			$this->object->saveUnit($_POST["unitname"], $_POST["baseunit"], $_POST["unitfactor"], $_POST["categories"], $_POST["units"]);
		}
		$this->units();
	}
	
	/**
	* Save a category
	*
	* @access public
	*/
	function saveCategory()
	{
		$error = false;
		if (strlen($_POST["categoryname"]) == 0)
		{
			ilUtil::sendInfo($this->object->getPlugin()->txt("err_no_categoryname"));
			$error = true;
		}
		if (!$error)
		{
			$this->object->saveCategory($_POST["categoryname"], $_POST["categories"]);
		}
		$this->units();
	}
	
	/**
	* Delete a unit
	*
	* @access public
	*/
	function deleteUnit()
	{
		$result = $this->object->deleteUnit($_POST["units"]);
		if (!is_null($result))
		{
			ilUtil::sendInfo($result);
		}
		$this->units();
	}
	
	/**
	* Delete a category
	*
	* @access public
	*/
	function deleteCategory()
	{
		$result = $this->object->deleteCategory($_POST["categories"]);
		if (!is_null($result))
		{
			ilUtil::sendInfo($result);
		}
		$this->units();
	}
	
	function unitSelected()
	{
		$this->units();
	}
	
	function reorderUnits()
	{
		$this->units();
	}
	
	function saveOrder()
	{
		$this->object->saveUnitOrder($_POST[$categories], $_POST["unitsequence"]);
		$this->units();
	}
	
	function cancelSaveOrder()
	{
		$this->units();
	}
	
	/**
	* Creates the editor tab to define units for formula questions
	*
	* @access public
	*/
	function units()
	{
		$template = $this->object->getPlugin()->getTemplate("tpl.il_as_qpl_formulaquestion_units.html");
		$categories = $this->object->getUnitCategories();
		$this->object->getPlugin()->includeClass("class.GUIPopUpButton.php");
		$categoriesPopup = new GUIPopUpButton();
		$categoriesPopup->setList($categories);
		$categoriesPopup->setDisplayString("text");
		$categoriesPopup->setValue("value");
		$categoriesPopup->setStyle("width: 30em;");
		$categoriesPopup->setSize("20");
		$categoriesPopup->setId("categories");
		$categoriesPopup->setName("categories");
		if (array_key_exists("categories", $_POST))
		{
			$categoriesPopup->setSelectedValue($_POST["categories"]);
		}
		else
		{
			$categoriesPopup->setSelectedValue(NULL);
		}

		$allUnits = new GUIPopUpButton();
		$allUnits->setList($this->object->getCategorizedUnits());
		$allUnits->setDisplayString("displayString");
		$allUnits->setValue("id");
		$allUnits->setSize("1");
		$allUnits->setId("baseunit");
		$allUnits->setName("baseunit");
		$allUnits->setListItemClassPath("class");
		$allUnits->setNoSelection($this->object->getPlugin()->txt("no_selection"));

		if (strlen($_POST["categories"]))
		{
			$template->setCurrentBlock("units");
			$unitPopup = new GUIPopUpButton();
			$catunits = $this->object->loadUnitsForCategory($_POST["categories"]);
			$unitPopup->setList($catunits);
			$unitPopup->setListItemIdPath("idString");
			$unitPopup->setDisplayString("unit");
			$unitPopup->setValue("id");
			$unitPopup->setStyle("width: 20em;");
			$unitPopup->setSize("20");
			$unitPopup->setId("units");
			$unitPopup->setName("units");
			if (array_key_exists("units", $_POST) || ($this->newUnitId > 0))
			{
				if ($this->newUnitId > 0)
				{
					$unitPopup->setSelectedValue($this->newUnitId);
				}
				else
				{
					$unitPopup->setSelectedValue($_POST["units"]);
				}
			}
			$template->setVariable("TEXT_UNITS", $this->object->getPlugin()->txt("units"));
			$template->setVariable("POPUP_UNITS", $unitPopup->getHTML());
			$template->setVariable("ADD_UNIT", $this->lng->txt("add"));
			$template->setVariable("TEXT_NEW_UNIT", $this->object->getPlugin()->txt("new_unit"));
			$template->parseCurrentBlock();

			if ($unitPopup->hasSelection() && (strcmp($this->ctrl->getCmd(), "reorderUnits") != 0))
			{
				if (!$this->object->isUnitInUse($_POST["units"]))
				{
					$template->setCurrentBlock("show_save_unit");
					$template->setVariable("SAVE_UNIT", $this->lng->txt("save"));
					$template->parseCurrentBlock();
					$template->setCurrentBlock("show_delete_unit");
					$template->setVariable("DELETE_UNIT", $this->lng->txt("delete"));
					$template->parseCurrentBlock();
				}
				$template->setCurrentBlock("unit");
				$template->setVariable("UNIT_HEADING", $this->object->getPlugin()->txt("selected_unit"));
				if ($this->newUnitId > 0)
				{
					$unit = $this->object->getUnit($this->newUnitId);
				}
				else
				{
					$unit = $this->object->getUnit($_POST["units"]);
				}
				if ($unit->getBaseUnit() != $unit->getId())
				{
					$allUnits->setSelectedValue($unit->getBaseUnit());
				}
				else
				{
					$allUnits->setSelectedValue(NULL);
				}
				$template->setVariable("UNITVALUE", " value=\"" . ilUtil::prepareFormOutput($unit->getUnit()) . "\"");
				$template->setVariable("FACTORVALUE", " value=\"" . ilUtil::prepareFormOutput($unit->getFactor()) . "\"");
				$template->setVariable("TEXT_UNIT", $this->object->getPlugin()->txt("unit"));
				$template->setVariable("TEXT_FACTOR", $this->object->getPlugin()->txt("factor"));
				$template->setVariable("TEXT_BASEUNIT", $this->object->getPlugin()->txt("baseunit"));
				$template->setVariable("SELECT_ALLUNITS", $allUnits->getHTML());
				$template->setVariable("REORDER", $this->object->getPlugin()->txt("reorder"));
				$template->parseCurrentBlock();
			}
			else if ($unitPopup->hasSelection() && (strcmp($this->ctrl->getCmd(), "reorderUnits") == 0))
			{
				foreach ($catunits as $unit)
				{
					$template->setCurrentBlock("listitem");
					$template->setVariable("LISTITEM", ilUtil::prepareFormOutput($unit->getUnit()));
					$template->setVariable("LISTITEMID", "reorder_unit_" . $unit->getId());
					$template->parseCurrentBlock();
					$template->setCurrentBlock("reorderlistitem");
					$template->setVariable("LISTITEMID", $unit->getId());
					$template->parseCurrentBlock();
				}
				$template->setCurrentBlock("reorder");
				$template->setVariable("SAVE_ORDER", $this->object->getPlugin()->txt("save_order"));
				$template->setVariable("REORDER_HEADING", $this->object->getPlugin()->txt("reorder"));
				$template->setVariable("CANCEL", $this->lng->txt("cancel"));
				$template->parseCurrentBlock();
			}
			else
			{
				if ($categoriesPopup->hasSelection())
				{
					if (!$this->object->getCategoryUnitCount($_POST["categories"]))
					{
						$template->setCurrentBlock("show_delete");
						$template->setVariable("DELETE_CATEGORY", $this->lng->txt("delete"));
						$template->parseCurrentBlock();
					}
					$template->setCurrentBlock("category");
					$template->setVariable("CATEGORY_HEADING", $this->object->getPlugin()->txt("selected_category"));
					$template->setVariable("CATEGORYVALUE", " value=\"" . ilUtil::prepareFormOutput($categories[$_POST["categories"]]["text"]) . "\"");
					$template->setVariable("TEXT_CATEGORY", $this->object->getPlugin()->txt("category"));
					$template->setVariable("SAVE_CATEGORY", $this->lng->txt("save"));
					$template->parseCurrentBlock();
				}
			}

		}
		$template->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "unitSelected"));
		$template->setVariable("TEXT_CATEGORIES", $this->object->getPlugin()->txt("categories"));
		$template->setVariable("POPUP_CATEGORIES", $categoriesPopup->getHTML());
		$template->setVariable("TEXT_NEW_CATEGORY", $this->object->getPlugin()->txt("new_category"));
		$template->setVariable("ADD_CATEGORY", $this->lng->txt("add"));
		$this->tpl->setVariable("ADM_CONTENT", $template->get());
		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDomEvent();
		ilYuiUtil::initDragDropList();
		$this->tpl->addCss($this->object->getPlugin()->getStyleSheetLocation("formula.css"));
	}

	/**
	* Sets the ILIAS tabs for this question type
	*
	* Sets the ILIAS tabs for this question type
	*
	* @access public
	*/
	function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;
		
		$this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_content",
					$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", $force_active);
			}
	
			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilPageObjectGUI", "preview"),
				array("preview"),
				"ilPageObjectGUI", "", $force_active);
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";
			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			$commands = $_POST["cmd"];
			if (is_array($commands))
			{
				foreach ($commands as $key => $value)
				{
					if (preg_match("/^suggestrange_.*/", $key, $matches))
					{
						$force_active = true;
					}
				}
			}
			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array("editQuestion", "save", "cancel", "addSuggestedSolution",
					"cancelExplorer", "linkChilds", "removeSuggestedSolution",
					"parseQuestion", "saveEdit", "suggestRange"),
				$classname, "", $force_active);
		}

		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("feedback",
				$this->ctrl->getLinkTargetByClass($classname, "feedback"),
				array("feedback", "saveFeedback"),
				$classname, "");
		}
		
		// Unit editor
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("qpl_qst_formulaquestion_units",
				$this->ctrl->getLinkTargetByClass($classname, "units"),
				array("units", "addCategory", "unitSelected", "addUnit", "saveUnit", "deleteUnit", "saveCategory", "deleteCategory", "reorderUnits", "saveOrder", "cancelSaveOrder"),
				$classname, "");
		}
		
		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}
}
?>
