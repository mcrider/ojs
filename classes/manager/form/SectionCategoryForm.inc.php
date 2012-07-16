<?php

/**
 * @file classes/manager/form/SectionForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 * @ingroup manager_form
 *
 * @brief Form for creating and modifying journal section categories.
 */

import('lib.pkp.classes.form.Form');

class SectionCategoryForm extends Form {

	/** @var $sectionCategoryId int The ID of the section being edited */
	var $sectionCategoryId;

	/**
	 * Constructor.
	 * @param $journalId int omit for a new journal
	 */
	function SectionCategoryForm($sectionCategoryId = null) {
		parent::Form('manager/sections/sectionCategoryForm.tpl');

		$journal =& Request::getJournal();
		$this->sectionCategoryId = $sectionCategoryId;

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'categoryName', 'required', 'manager.sections.form.titleRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('sectionCategoryId', $this->sectionCategoryId);

		if (isset($this->sectionCategoryId)) {
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sectionCategory = $sectionDao->getSectionCategory($this->sectionCategoryId, $journal->getId());
			$templateMgr->assign('categoryName', $sectionCategory['name']);
		}

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('categoryName'));
	}

	/**
	 * Save section.
	 */
	function execute() {
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$name = $this->getData('categoryName');

		if (isset($this->sectionCategoryId)) {
			$sectionDao->updateSectionCategory($this->sectionCategoryId, $name);
		} else {
			$sectionDao->insertSectionCategory($journalId, $name);
		}
	}
}

?>
