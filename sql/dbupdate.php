<#1>
DROP TABLE IF EXISTS il_qpl_qst_formulaquestion_unit;
DROP TABLE IF EXISTS il_qpl_qst_formulaquestion_constant;
DROP TABLE IF EXISTS il_qpl_qst_formulaquestion_result_unit;
DROP TABLE IF EXISTS il_qpl_qst_formulaquestion_variable;
CREATE TABLE `il_qpl_qst_formulaquestion_variable` (
`variable_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`question_fi` INT NOT NULL ,
`variable` VARCHAR( 255 ) NOT NULL ,
`range_min` DOUBLE NOT NULL DEFAULT '0',
`range_max` DOUBLE NOT NULL DEFAULT '0',
`unit_fi` INT NULL ,
`step_dim_min` INT NOT NULL ,
`step_dim_max` INT NOT NULL ,
`precision` INT NOT NULL DEFAULT '0' ,
INDEX ( `question_fi` , `variable` )
);
DROP TABLE IF EXISTS il_qpl_qst_formulaquestion_result;
CREATE TABLE `il_qpl_qst_formulaquestion_result` (
`result_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`question_fi` INT NOT NULL ,
`result` VARCHAR( 255 ) NOT NULL ,
`range_min` DOUBLE NULL,
`range_max` DOUBLE NULL,
`tolerance` DOUBLE NULL,
`unit_fi` INT NULL ,
`formula` TEXT NOT NULL ,
`rating_simple` INT NOT NULL DEFAULT '1',
`rating_sign` DOUBLE NOT NULL DEFAULT '0.25',
`rating_value` DOUBLE NOT NULL DEFAULT '0.25',
`rating_dim` DOUBLE NOT NULL DEFAULT '0.25',
`rating_unit` DOUBLE NOT NULL DEFAULT '0.25',
`points` DOUBLE NOT NULL DEFAULT '0',
`precision` INT NOT NULL DEFAULT '0' ,
INDEX ( `question_fi` , `result` )
);
CREATE TABLE `il_qpl_qst_formulaquestion_unit` (
`unit_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`unit` VARCHAR( 255 ) NOT NULL ,
`factor` DOUBLE NOT NULL DEFAULT '0',
`baseunit_fi` INT NULL,
`category_fi` INT NOT NULL,
`sequence` INT NOT NULL DEFAULT '0',
INDEX ( `baseunit_fi` , `category_fi` )
);

CREATE TABLE `il_qpl_qst_formulaquestion_unit_category` (
`category_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`category` VARCHAR( 255 ) NOT NULL
);

INSERT INTO `il_qpl_qst_formulaquestion_unit_category` ( `category_id` , `category` ) VALUES (NULL , '00_no_category');

CREATE TABLE `il_qpl_qst_formulaquestion_result_unit` (
`result_unit_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`question_fi` INT NOT NULL ,
`result` VARCHAR( 255 ) NOT NULL ,
`unit_fi` INT NOT NULL ,
INDEX ( `question_fi`, `unit_fi` )
);
CREATE TABLE `il_qpl_qst_formulaquestion_constant` (
`constant_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`constant` VARCHAR( 255 ) NOT NULL ,
`value` DOUBLE NOT NULL DEFAULT '0'
);
INSERT INTO qpl_question_type (type_tag, plugin) VALUES ('assFormulaQuestion', 1);
<#2>
ALTER TABLE `il_qpl_qst_formulaquestion_variable` ADD `intprecision` INT NOT NULL DEFAULT '1';
<#3>
ALTER TABLE `il_qpl_qst_formulaquestion_result` DROP `rating_dim`;