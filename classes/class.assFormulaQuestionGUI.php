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
* @version	$Id: class.assFormulaQuestionGUI.php 1235 2010-02-15 15:21:18Z hschottm $
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
	* @param integer $id The database id of a multiple choice question object
	* @access public
	*/
	function __construct($id = -1)
	{
		parent::__construct();
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
	* Evaluates a posted edit form and writes the form data in the question object
	*
	* @return integer A positive value, if one of the required fields wasn't set, else 0
	*/
	function writePostData($always = false)
	{
		$hasErrors = (!$always) ? $this->editQuestion(true) : false;
		if (!$hasErrors)
		{
			$this->object->setTitle($_POST["title"]);
			$this->object->setAuthor($_POST["author"]);
			$this->object->setComment($_POST["comment"]);
			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
			$questiontext = $_POST["question"];
			$this->object->setQuestion($questiontext);
			$this->object->setEstimatedWorkingTime(
				$_POST["Estimated"]["hh"],
				$_POST["Estimated"]["mm"],
				$_POST["Estimated"]["ss"]
			);

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
				}
			}

			return 0;
		}
		else
		{
			return 1;
		}
	}

	/**
	* Creates an output of the edit form for the question
	*
	* @access public
	*/
	function editQuestion()
	{
		$save = ((strcmp($this->ctrl->getCmd(), "save") == 0) || (strcmp($this->ctrl->getCmd(), "saveEdit") == 0)) ? TRUE : FALSE;
		$this->getQuestionTemplate();

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(FALSE);
		$form->setTableWidth("100%");
		$form->setId("assformulaquestion");

		// title, author, description, question, working time (assessment mode)
		$this->addBasicQuestionFormProperties($form);

		if (count($this->object->getVariables()))
		{
			$this->object->getPlugin()->includeClass("class.ilVariableInputGUI.php");
			$variables = new ilVariableInputGUI($this->object->getPlugin()->txt("variables"));
			$variables->setVariables($this->object->getVariables());
			$variables->setCategorizedUnits($this->object->getCategorizedUnits());
			$form->addItem($variables);
		}
		if (count($this->object->getResults()))
		{
			$this->object->getPlugin()->includeClass("class.ilResultInputGUI.php");
			$results = new ilResultInputGUI($this->object->getPlugin()->txt("results"));
			$results->setResults($this->object->getResults());
			$results->setVariables($this->object->getVariables());
			$results->setResultUnits($this->object->resultunits);
			$results->setCategorizedUnits($this->object->getCategorizedUnits());
			if (preg_match("/suggestrange_(.*)/", $this->ctrl->getCmd(), $matches))
			{
				$results->suggestRange($matches[1]);
			}
			$form->addItem($results);
		}

		if ($this->object->getId())
		{
			$hidden = new ilHiddenInputGUI("", "ID");
			$hidden->setValue($this->object->getId());
			$form->addItem($hidden);
		}

		$form->addCommandButton('parseQuestion', $this->object->getPlugin()->txt("parseQuestion"));
		$this->addQuestionFormCommandButtons($form);
	
		$errors = false;
	
		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
			if ($errors) $checkonly = false;
		}

		if (!$checkonly) $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		return $errors;
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
	
	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_feedback = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions, $show_feedback); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
	}

	/**
	* Get the question solution output
	*
	* @param integer $active_id The active user id
	* @param integer $pass The test pass
	* @param boolean $graphicalOutput Show visual feedback for right/wrong answers
	* @param boolean $result_output Show the reached points for parts of the question
	* @param boolean $show_question_only Show the question without the ILIAS content around
	* @param boolean $show_feedback Show the question feedback
	* @param boolean $show_correct_solution Show the correct solution instead of the user solution
	* @param boolean $show_manual_scoring Show specific information for the manual scoring output
	* @return The solution output of the question as HTML code
	*/
	function getSolutionOutput(
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE
	)
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

	/**
	* Saves the feedback for a formula question
	*
	* @access public
	*/
	function saveFeedback()
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$errors = $this->feedback(true);
		$this->object->saveFeedbackGeneric(0, $_POST["feedback_incomplete"]);
		$this->object->saveFeedbackGeneric(1, $_POST["feedback_complete"]);
		$this->object->cleanupMediaObjectUsage();
		parent::saveFeedback();
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
