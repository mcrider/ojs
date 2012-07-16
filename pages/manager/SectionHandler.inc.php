<?php

/**
 * @file pages/manager/SectionHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for section management functions.
 */

import('pages.manager.ManagerHandler');

class SectionHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function SectionHandler() {
		parent::ManagerHandler();
	}
	/**
	 * Display a list of the sections within the current journal.
	 */
	function sections() {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();
		$rangeInfo =& Handler::getRangeInfo('sections');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getId(), $rangeInfo);
		$emptySectionIds = $sectionDao->getJournalEmptySectionIds($journal->getId());
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'manager'), 'manager.journalManagement')));
		$templateMgr->assign_by_ref('sections', $sections);
		$templateMgr->assign('emptySectionIds', $emptySectionIds);
		$templateMgr->assign('helpTopicId','journal.managementPages.sections');
		$templateMgr->display('manager/sections/sections.tpl');
	}

	/**
	 * Display form to create a new section.
	 */
	function createSection() {
		$this->editSection();
	}

	/**
	 * Display form to create/edit a section.
	 * @param $args array optional, if set the first parameter is the ID of the section to edit
	 */
	function editSection($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		import('classes.manager.form.SectionForm');

		$sectionForm = new SectionForm(!isset($args) || empty($args) ? null : ((int) $args[0]));
		if ($sectionForm->isLocaleResubmit()) {
			$sectionForm->readInputData();
		} else {
			$sectionForm->initData();
		}
		$sectionForm->display();
	}

	/**
	 * Save changes to a section.
	 */
	function updateSection($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('classes.manager.form.SectionForm');
		$sectionForm = new SectionForm(!isset($args) || empty($args) ? null : ((int) $args[0]));

		switch (Request::getUserVar('editorAction')) {
			case 'addSectionEditor':
				$sectionForm->includeSectionEditor((int) Request::getUserVar('userId'));
				$canExecute = false;
				break;
			case 'removeSectionEditor':
				$sectionForm->omitSectionEditor((int) Request::getUserVar('userId'));
				$canExecute = false;
				break;
			default:
				$canExecute = true;
				break;
		}

		$sectionForm->readInputData();
		if ($canExecute && $sectionForm->validate()) {
			$sectionForm->execute();
			Request::redirect(null, null, 'sections');
		} else {
			$sectionForm->display();
		}
	}

	/**
	 * Delete a section.
	 * @param $args array first parameter is the ID of the section to delete
	 */
	function deleteSection($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sectionDao->deleteSectionById($args[0], $journal->getId());
		}

		Request::redirect(null, null, 'sections');
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSection() {
		$this->validate();

		$journal =& Request::getJournal();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection(Request::getUserVar('id'), $journal->getId());

		if ($section != null) {
			$direction = Request::getUserVar('d');

			if ($direction != null) {
				// moving with up or down arrow
				$section->setSequence($section->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

			} else {
				// Dragging and dropping
				$prevId = Request::getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevJournal = $sectionDao->getSection($prevId);
					$prevSeq = $prevJournal->getSequence();
				}

				$section->setSequence($prevSeq + .5);
			}

			$sectionDao->updateSection($section);
			$sectionDao->resequenceSections($journal->getId());
		}

		// Moving up or down with the arrows requires a page reload.
		if ($direction != null) {
			Request::redirect(null, null, 'sections');
		}
	}



















	/**
	 * Display a list of the section categories within the current journal.
	 */
	function sectionCategories() {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionCategories =& $sectionDao->getSectionCategories($journal->getId());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('sectionCategories', $sectionCategories);
		$templateMgr->display('manager/sections/sectionCategories.tpl');
	}

	/**
	 * Display form to create a new section category.
	 */
	function createSectionCategory() {
		$this->editSectionCategory();
	}

	/**
	 * Display form to create/edit a section category.
	 * @param $args array optional, if set the first parameter is the ID of the section to edit
	 */
	function editSectionCategory($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		import('classes.manager.form.SectionCategoryForm');

		$sectionCategoryForm = new SectionCategoryForm(!isset($args) || empty($args) ? null : ((int) $args[0]));
		if ($sectionCategoryForm->isLocaleResubmit()) {
			$sectionCategoryForm->readInputData();
		} else {
			$sectionCategoryForm->initData();
		}
		$sectionCategoryForm->display();
	}

	/**
	 * Save changes to a section category.
	 */
	function updateSectionCategory($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('classes.manager.form.SectionCategoryForm');
		$sectionCategoryForm = new SectionCategoryForm(!isset($args) || empty($args) ? null : ((int) $args[0]));

		$sectionCategoryForm->readInputData();
		if ($sectionCategoryForm->validate()) {
			$sectionCategoryForm->execute();
			Request::redirect(null, null, 'sectionCategories');
		} else {
			$sectionCategoryForm->display();
		}
	}

	/**
	 * Delete a section category.
	 * @param $args array first parameter is the ID of the category to delete
	 */
	function deleteSectionCategory($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sectionDao->deleteSectionCategory((int) $args[0]);


		// TODO: Set all sections with this category ID to 0
		}

		Request::redirect(null, null, 'sectionCategories');
	}



















	function setupTemplate($subclass = false) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'sections'), 'section.sections'));
		}
	}
}

?>
