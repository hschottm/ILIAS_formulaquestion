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
* Formula Question Result
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id: class.assFormulaQuestionResult.php 944 2009-11-09 16:11:30Z hschottm $
* @ingroup ModulesTestQuestionPool
* */
class assFormulaQuestionResult
{
	private $result;
	private $range_min;
	private $range_max;
	private $tolerance;
	private $unit;
	private $formula;
	private $rating_simple;
	private $rating_sign;
	private $rating_value;
	private $rating_unit;
	private $points;
	private $precision;
	
	/**
	* assFormulaQuestionResult constructor
	*
	* @param string $result Result name
	* @param double $range_min Range minimum
	* @param double $range_max Range maximum
	* @param double $tolerance Tolerance of the result in percent
	* @param object $unit Unit
	* @param string $formula The formula to calculate the result
	* @param double $points The maximum available points for the result
	* @param integer $precision Number of decimal places of the value
	* @param boolean $rating_simple Use simple rating (100% if right, 0 % if wrong)
	* @param double $rating_sign Percentage of rating for the correct sign
	* @param double $rating_value Percentage of rating for the correct value 
	* @param double $rating_unit Percentage of rating for the correct unit
	* @access public
	*/
	function __construct($result, $range_min, $range_max, $tolerance, $unit, $formula, $points, $precision, $rating_simple = TRUE, $rating_sign = 33, $rating_value = 34, $rating_unit = 33) 
	{
		$this->result = $result;
		$this->setRangeMin((is_numeric($range_min)) ? $range_min : NULL);
		$this->setRangeMax((is_numeric($range_max)) ? $range_max : NULL);
		$this->tolerance = $tolerance;
		$this->unit = $unit;
		$this->formula = $formula;
		$this->points = $points;
		$this->precision = $precision;
		$this->rating_simple = $rating_simple;
		$this->rating_sign = $rating_sign;
		$this->rating_value = $rating_value;
		$this->rating_unit = $rating_unit;
	}
	
	public function substituteFormula($variables, $results)
	{
		$formula = $this->getFormula();
		if (preg_match_all("/(\\\$r\\d+)/ims", $formula, $matches))
		{
			foreach ($matches[1] as $result)
			{
				if (strcmp($result, $this->getResult()) == 0) 
				{
					include_once "./Services/Component/classes/class.ilPlugin.php";
					$pl = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assFormulaQuestion");
					ilUtil::sendInfo($pl->txt("errRecursionInResult"));
					return;
				}
				$formula = str_replace($result, $results[$result]->substituteFormula($variables, $results), $formula);
			}
		}
		return $formula;
	}
	
	public function calculateFormula($variables, $results)
	{
		include_once "./Services/Math/classes/class.ilMath.php";
		include_once "./Services/Math/classes/class.EvalMath.php";
		$formula = $this->substituteFormula($variables, $results);
		if (preg_match_all("/(\\\$v\\d+)/ims", $formula, $matches))
		{
			foreach ($matches[1] as $variable)
			{
				$varObj = $variables[$variable];
				$value = $varObj->getBaseValue();
				$formula = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", "(".$value.")" . "\\1", $formula);
			}
		}
		$math = new EvalMath();
		$math->suppress_errors = TRUE;
		$result = $math->evaluate($formula);
		if (is_object($this->getUnit()))
		{
			$result = ilMath::_div($result, $this->getUnit()->getFactor(), 100);
		}
		return ilMath::_mul($result, 1, 100);
	}
	
	public function findValidRandomVariables($variables, $results)
	{
		include_once "./Services/Math/classes/class.EvalMath.php";
		$i = 0;
		$inRange = FALSE;
		while ($i < 1000 && !$inRange)
		{
			$formula = $this->substituteFormula($variables, $results);
			if (preg_match_all("/(\\\$v\\d+)/ims", $formula, $matches))
			{
				foreach ($matches[1] as $variable)
				{
					$varObj = $variables[$variable];
					$varObj->setRandomValue();
					$formula = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", $varObj->getBaseValue() . "\\1", $formula);
				}
			}
			$math = new EvalMath();
			$math->suppress_errors = TRUE;
			$result = $math->evaluate($formula);
			$inRange = (is_numeric($result)) ? TRUE : FALSE;
			if ($inRange)
			{
				if (is_numeric($this->getRangeMin()))
				{
					if ($result < $this->getRangeMinBase())
					{
						$inRange = FALSE;
					}
				}
				if (is_numeric($this->getRangeMax()))
				{
					if ($result > $this->getRangeMaxBase())
					{
						$inRange = FALSE;
					}
				}
			}
			$i++;
		}
	}
	
	public function suggestRange($variables, $results)
	{
		include_once "./Services/Math/classes/class.EvalMath.php";
		$range_min = NULL;
		$range_max = NULL;
		for ($i = 0; $i < 1000; $i++)
		{
			$formula = $this->substituteFormula($variables, $results);
			if (preg_match_all("/(\\\$v\\d+)/ims", $formula, $matches))
			{
				foreach ($matches[1] as $variable)
				{
					$varObj = $variables[$variable];
					$varObj->setRandomValue();
					$formula = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", $varObj->getBaseValue() . "\\1", $formula);
				}
			}
			$math = new EvalMath();
			$math->suppress_errors = TRUE;
			$result = $math->evaluate($formula);
			if (($range_min == NULL) || ($result < $range_min)) $range_min = $result;
			if (($range_max == NULL) || ($result > $range_max)) $range_max = $result;
		}
		include_once "./Services/Math/classes/class.ilMath.php";
		if (is_object($this->getUnit()))
		{
			$range_min = ilMath::_div($range_min, $this->getUnit()->getFactor());
			$range_max = ilMath::_div($range_max, $this->getUnit()->getFactor());
		}
		$this->setRangeMin(ilMath::_mul($range_min, 1, $this->getPrecision()));
		$this->setRangeMax(ilMath::_mul($range_max, 1, $this->getPrecision()));
	}
	
	public function isCorrect($variables, $results, $value, $unit)
	{
		include_once "./Services/Math/classes/class.EvalMath.php";
		include_once "./Services/Math/classes/class.ilMath.php";
		$formula = $this->substituteFormula($variables, $results);
		if (preg_match_all("/(\\\$v\\d+)/ims", $formula, $matches))
		{
			foreach ($matches[1] as $variable)
			{
				$varObj = $variables[$variable];
				$formula = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", $varObj->getBaseValue() . "\\1", $formula);
			}
		}
		$math = new EvalMath();
		$math->suppress_errors = TRUE;
		$result = $math->evaluate($formula);
		if (is_object($this->getUnit()))
		{
			$result = ilMath::_mul($result, $this->getUnit()->getFactor(), 100);
		}
		if (is_object($unit))
		{
			$value = ilMath::_mul($value, $unit->getFactor(), 100);
		}
		$checkvalue = FALSE;
		if ($this->isInTolerance($value, $result, $this->getTolerance())) $checkvalue = TRUE;
		$checkunit = TRUE;
		if (is_object($this->getUnit()))
		{
			$checkunit = FALSE;
			if (is_object($unit))
			{
				if ($unit->getBaseUnit() == $this->getUnit()->getBaseUnit()) $checkunit = TRUE;
			}
		}
		return $checkvalue && $checkunit;
	}
	
	protected function isInTolerance($v1, $v2, $p)
	{
		include_once "./Services/Math/classes/class.ilMath.php";
		$v1 = ilMath::_mul($v1, 1, $this->getPrecision());
		$b1 = ilMath::_sub($v2, abs(ilMath::_div(ilMath::_mul($p, $v2, 100), 100)), $this->getPrecision());
		$b2 = ilMath::_add($v2, abs(ilMath::_div(ilMath::_mul($p, $v2, 100), 100)), $this->getPrecision());
		if (($b1 <= $v1) && ($b2 >= $v1)) return TRUE;
		else return FALSE;
	}
	
	protected function checkSign($v1, $v2)
	{
		if ((($v1 >= 0) && ($v2 >= 0)) || (($v1 <= 0) && ($v2 <= 0)))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function getReachedPoints($variables, $results, $value, $unit, $units)
	{
		global $ilLog;
		if ($this->getRatingSimple())
		{
			if ($this->isCorrect($variables, $results, $value, $units[$unit]))
			{
				return $this->getPoints();
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$points = 0;
			include_once "./Services/Math/classes/class.EvalMath.php";
			include_once "./Services/Math/classes/class.ilMath.php";
			$formula = $this->substituteFormula($variables, $results);
			if (preg_match_all("/(\\\$v\\d+)/ims", $formula, $matches))
			{
				foreach ($matches[1] as $variable)
				{
					$varObj = $variables[$variable];
					$formula = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", $varObj->getBaseValue() . "\\1", $formula);
				}
			}
			$math = new EvalMath();
			$math->suppress_errors = TRUE;
			$result = $math->evaluate($formula);
			if (is_object($this->getUnit()))
			{
				$result = ilMath::_mul($result, $this->getUnit()->getFactor(), 100);
			}
			if (is_object($unit))
			{
				$value = ilMath::_mul($value, $unit->getFactor(), 100);
			}
			else
			{
			}
			if ($this->checkSign($result, $value))
			{
				$points += ilMath::_mul($this->getPoints(), ilMath::_div($this->getRatingSign(), 100));
			}
			if ($this->isInTolerance(abs($value), abs($result), $this->getTolerance())) $checkvalue = TRUE;
			{
				$points += ilMath::_mul($this->getPoints(), ilMath::_div($this->getRatingValue(), 100));
			}
			if (is_object($this->getUnit()))
			{
				$base1 = $units[$unit];
				if (is_object($base1)) $base1 = $units[$base1->getBaseUnit()];
				$base2 = $units[$this->getUnit()->getBaseUnit()];
				if (is_object($base1) && is_object($base2) && $base1->getId() == $base2->getId()) 
				{
					$points += ilMath::_mul($this->getPoints(), ilMath::_div($this->getRatingUnit(), 100));
				}
			}
			return $points;
		}
	}

	public function getResultInfo($variables, $results, $value, $unit, $units)
	{
		global $ilLog;
		if ($this->getRatingSimple())
		{
			if ($this->isCorrect($variables, $results, $value, $units[$unit]))
			{
				return array("points" => $this->getPoints());
			}
			else
			{
				return array("points" => 0);
			}
		}
		else
		{
			include_once "./Services/Math/classes/class.EvalMath.php";
			include_once "./Services/Math/classes/class.ilMath.php";
			$totalpoints = 0;
			$formula = $this->substituteFormula($variables, $results);
			if (preg_match_all("/(\\\$v\\d+)/ims", $formula, $matches))
			{
				foreach ($matches[1] as $variable)
				{
					$varObj = $variables[$variable];
					$formula = preg_replace("/\\\$" . substr($variable, 1) . "([^0123456789]{0,1})/", $varObj->getBaseValue() . "\\1", $formula);
				}
			}
			$math = new EvalMath();
			$math->suppress_errors = TRUE;
			$result = $math->evaluate($formula);
			if (is_object($this->getUnit()))
			{
				$result = ilMath::_mul($result, $this->getUnit()->getFactor(), 100);
			}
			if (is_object($unit))
			{
				$value = ilMath::_mul($value, $unit->getFactor(), 100);
			}
			else
			{
			}
			$details = array();
			if ($this->checkSign($result, $value))
			{
				$points = ilMath::_mul($this->getPoints(), ilMath::_div($this->getRatingSign(), 100));
				$totalpoints += $points;
				$details['sign'] = $points;
			}
			if ($this->isInTolerance(abs($value), abs($result), $this->getTolerance())) $checkvalue = TRUE;
			{
				$points = ilMath::_mul($this->getPoints(), ilMath::_div($this->getRatingValue(), 100));
				$totalpoints += $points;
				$details['value'] = $points;
			}
			if (is_object($this->getUnit()))
			{
				$base1 = $units[$unit];
				if (is_object($base1)) $base1 = $units[$base1->getBaseUnit()];
				$base2 = $units[$this->getUnit()->getBaseUnit()];
				if (is_object($base1) && is_object($base2) && $base1->getId() == $base2->getId()) 
				{
					$points = ilMath::_mul($this->getPoints(), ilMath::_div($this->getRatingUnit(), 100));
					$totalpoints += $points;
					$details['unit'] = $points;
				}
			}
			$details['points'] = $totalpoints;
			return $details;
		}
	}
	
	/************************************
	* Getter and Setter
	************************************/
	
	public function setResult($result)
	{
		$this->result = $result;
	}

	public function getResult()
	{
		return $this->result;
	}

	public function setRangeMin($range_min)
	{
		include_once "./Services/Math/classes/class.EvalMath.php";
		$math = new EvalMath();
		$math->suppress_errors = TRUE;
		$result = $math->evaluate($range_min);
		$val = (strlen($result) > 8) ? strtoupper(sprintf("%e", $result)) : $result;
		$this->range_min = $val;
	}

	public function getRangeMin()
	{
		return $this->range_min;
	}
	
	public function getRangeMinBase()
	{
		if (is_numeric($this->getRangeMin()))
		{
			if (is_object($this->getUnit()))
			{
				include_once "./Services/Math/classes/class.ilMath.php";
				return ilMath::_mul($this->getRangeMin(), $this->getUnit()->getFactor(), 100);
			}
		}
		return $this->getRangeMin();
	}

	public function setRangeMax($range_max)
	{
		include_once "./Services/Math/classes/class.EvalMath.php";
		$math = new EvalMath();
		$math->suppress_errors = TRUE;
		$result = $math->evaluate($range_max);
		$val = (strlen($result) > 8) ? strtoupper(sprintf("%e", $result)) : $result;
		$this->range_max = $val;
	}

	public function getRangeMax()
	{
		return $this->range_max;
	}

	public function getRangeMaxBase()
	{
		if (is_numeric($this->getRangeMax()))
		{
			if (is_object($this->getUnit()))
			{
				include_once "./Services/Math/classes/class.ilMath.php";
				return ilMath::_mul($this->getRangeMax(), $this->getUnit()->getFactor(), 100);
			}
		}
		return $this->getRangeMax();
	}

	public function setTolerance($tolerance)
	{
		$this->tolerance = $tolerance;
	}

	public function getTolerance()
	{
		return $this->tolerance;
	}

	public function setUnit($unit)
	{
		$this->unit = $unit;
	}

	public function getUnit()
	{
		return $this->unit;
	}

	public function setFormula($formula)
	{
		$this->formula = $formula;
	}

	public function getFormula()
	{
		return $this->formula;
	}

	public function setPoints($points)
	{
		$this->points = $points;
	}

	public function getPoints()
	{
		return $this->points;
	}

	public function setRatingSimple($rating_simple)
	{
		$this->rating_simple = $rating_simple;
	}

	public function getRatingSimple()
	{
		return $this->rating_simple;
	}

	public function setRatingSign($rating_sign)
	{
		$this->rating_sign = $rating_sign;
	}

	public function getRatingSign()
	{
		return $this->rating_sign;
	}

	public function setRatingValue($rating_value)
	{
		$this->rating_value = $rating_value;
	}

	public function getRatingValue()
	{
		return $this->rating_value;
	}

	public function setRatingUnit($rating_unit)
	{
		$this->rating_unit = $rating_unit;
	}

	public function getRatingUnit()
	{
		return $this->rating_unit;
	}
	
	public function setPrecision($precision)
	{
		$this->precision = $precision;
	}
	
	public function getPrecision()
	{
		return (int)$this->precision;
	}

}
?>
