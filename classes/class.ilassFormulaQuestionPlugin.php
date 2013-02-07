<?php

	include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";
	class ilassFormulaQuestionPlugin extends ilQuestionsPlugin
	{
		final function getPluginName()
		{
			return "assFormulaQuestion";
		}
		
		final function getQuestionType()
		{
			return "assFormulaQuestion";
		}
		
		final function getQuestionTypeTranslation()
		{
			return $this->txt($this->getQuestionType());
		}
	}
?>