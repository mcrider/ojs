<?php

/**
 * @file pages/author/TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackSubmissionHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission tracking.
 */

import('pages.author.AuthorHandler');

class TrackSubmissionHandler extends AuthorHandler {
	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 **/
	function TrackSubmissionHandler() {
		parent::AuthorHandler();
	}

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$authorSubmission =& $this->submission;
		$this->setupTemplate($request, true);

		// If the submission is incomplete, allow the author to delete it.
		if ($authorSubmission->getSubmissionProgress()!=0) {
			import('classes.file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->deleteArticleTree();

			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$articleDao->deleteArticleById($args[0]);
		}

		$request->redirect(null, null, 'index');
	}

	/**
	 * Delete an author version file.
	 * @param $args array ($articleId, $fileId)
	 */
	function deleteArticleFile($args, $request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revisionId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$authorSubmission =& $this->submission;

		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			AuthorAction::deleteArticleFile($authorSubmission, $fileId, $revisionId);
		}

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Display a summary of the status of an author's submission.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, $request) {
		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$articleId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $articleId);

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getJournalSettings($journal->getId());

		// Setting the round.
		$round = (int) array_shift($args);
		if (!$round) $round = $submission->getCurrentRound();

		$templateMgr =& TemplateManager::getManager();

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		if ($publishedArticle) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('issue', $issue);
		}

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($submission->getSectionId());
		$templateMgr->assign_by_ref('section', $section);

		$submitterDao =& DAORegistry::getDAO('SubmitterDAO');
		$submitters =& $submitterDao->getBySubmissionId($submission->getId());
		$templateMgr->assign_by_ref('submitters', $submitters);

		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());

		import('classes.submission.sectionEditor.SectionEditorSubmission');
		$templateMgr->assign_by_ref('editorDecisionOptions', SectionEditorSubmission::getEditorDecisionOptions());

		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		if ( $paymentManager->submissionEnabled() || $paymentManager->fastTrackEnabled() || $paymentManager->publicationEnabled()) {
			$templateMgr->assign('authorFees', true);
			$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');

			if ($paymentManager->submissionEnabled()) {
				$templateMgr->assign_by_ref('submissionPayment', $completedPaymentDao->getSubmissionCompletedPayment ($journal->getId(), $articleId));
			}

			if ($paymentManager->fastTrackEnabled()) {
				$templateMgr->assign_by_ref('fastTrackPayment', $completedPaymentDao->getFastTrackCompletedPayment ($journal->getId(), $articleId));
			}

			if ($paymentManager->publicationEnabled()) {
				$templateMgr->assign_by_ref('publicationPayment', $completedPaymentDao->getPublicationCompletedPayment ($journal->getId(), $articleId));
			}
		}

		$templateMgr->assign('helpTopicId','editorial.authorsRole');

		$initialCopyeditSignoff = $submission->getSignoff('SIGNOFF_COPYEDITING_INITIAL');
		$templateMgr->assign('canEditMetadata', !$initialCopyeditSignoff->getDateCompleted() && $submission->getStatus() != STATUS_PUBLISHED);

		$templateMgr->display('author/submission.tpl');
	}

	/**
	 * Display specific details of an author's submission.
	 */
	function submissionReview($args, $request) {
		$user =& $request->getUser();
		$articleId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$authorSubmission =& $this->submission;
		$this->setupTemplate($request, true, $articleId);
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR); // editor.article.decision etc. FIXME?

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewModifiedByRound = $reviewAssignmentDao->getLastModifiedByRound($articleId);
		$reviewEarliestNotificationByRound = $reviewAssignmentDao->getEarliestNotificationByRound($articleId);
		$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($articleId);
		$authorViewableFilesByRound =& $reviewAssignmentDao->getAuthorViewableFilesByRound($articleId);

		$editorDecisions = $authorSubmission->getDecisions($authorSubmission->getCurrentRound());
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1] : null;

		$templateMgr =& TemplateManager::getManager();

		$reviewAssignments =& $authorSubmission->getReviewAssignments();
		$templateMgr->assign_by_ref('reviewAssignments', $reviewAssignments);
		$templateMgr->assign_by_ref('submission', $authorSubmission);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('authorViewableFilesByRound', $authorViewableFilesByRound);
		$templateMgr->assign_by_ref('reviewModifiedByRound', $reviewModifiedByRound);

		$reviewIndexesByRound = array();
		for ($round = 1; $round <= $authorSubmission->getCurrentRound(); $round++) {
			$reviewIndexesByRound[$round] = $reviewAssignmentDao->getReviewIndexesForRound($articleId, $round);
		}
		$templateMgr->assign_by_ref('reviewIndexesByRound', $reviewIndexesByRound);

		$templateMgr->assign('reviewEarliestNotificationByRound', $reviewEarliestNotificationByRound);
		$templateMgr->assign_by_ref('submissionFile', $authorSubmission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $authorSubmission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $authorSubmission->getSuppFiles());
		$templateMgr->assign('lastEditorDecision', $lastDecision);
		import('classes.submission.sectionEditor.SectionEditorSubmission');
		$templateMgr->assign('editorDecisionOptions', SectionEditorSubmission::getEditorDecisionOptions());
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.review');
		$templateMgr->display('author/submissionReview.tpl');
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($articleId)
	 */
	function addSuppFile($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($request, $articleId);
		$authorSubmission =& $this->submission;

		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			$this->setupTemplate($request, true, $articleId, 'summary');

			import('classes.submission.form.SuppFileForm');

			$submitForm = new SuppFileForm($authorSubmission, $journal);

			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$submitForm->display();
		} else {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function editSuppFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$authorSubmission =& $this->submission;

		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			$this->setupTemplate($request, true, $articleId, 'summary');

			import('classes.submission.form.SuppFileForm');

			$journal =& $request->getJournal();
			$submitForm = new SuppFileForm($authorSubmission, $journal, $suppFileId);

			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$submitForm->display();
		} else {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$authorSubmission =& $this->submission;

		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			$suppFileId = $request->getUserVar('fileId');
			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

			if (isset($suppFile) && $suppFile != null) {
				$suppFile->setShowReviewers($request->getUserVar('hide')==1?0:1);
				$suppFileDao->updateSuppFile($suppFile);
			}
		}
		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$suppFileId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$authorSubmission =& $this->submission;
		$this->setupTemplate($request, true, $articleId, 'summary');

		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			import('classes.submission.form.SuppFileForm');

			$journal =& $request->getJournal();
			$submitForm = new SuppFileForm($authorSubmission, $journal, $suppFileId);
			$submitForm->readInputData();

			if ($submitForm->validate()) {
				$submitForm->execute();
				$request->redirect(null, null, 'submission', $articleId);
			} else {
				$submitForm->display();
			}
		} else {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Display the status and other details of an author's submission.
	 */
	function submissionEditing($args, $request) {
		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$articleId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $articleId);

		AuthorAction::copyeditUnderway($submission);
		import('classes.submission.proofreader.ProofreaderAction');
		ProofreaderAction::proofreadingUnderway($submission, 'SIGNOFF_PROOFREADING_AUTHOR');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('copyeditor', $submission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('initialCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('editorAuthorCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_AUTHOR'));
		$templateMgr->assign_by_ref('finalCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_FINAL'));
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('useCopyeditors', $journal->getSetting('useCopyeditors'));
		$templateMgr->assign('useLayoutEditors', $journal->getSetting('useLayoutEditors'));
		$templateMgr->assign('useProofreaders', $journal->getSetting('useProofreaders'));
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.editing');
		$templateMgr->display('author/submissionEditing.tpl');
	}

	/**
	 * Upload the author's revised version of an article.
	 */
	function uploadRevisedVersion($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true);

		AuthorAction::uploadRevisedVersion($submission, $request);

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	function viewMetadata($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();
		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $articleId, 'summary');

		AuthorAction::viewMetadata($submission, $journal);
	}

	function saveMetadata($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $articleId);

		// If the copy editor has completed copyediting, disallow
		// the author from changing the metadata.
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$initialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $submission->getId());
		if ($initialSignoff->getDateCompleted() != null || AuthorAction::saveMetadata($submission, $request)) {
 			$request->redirect(null, null, 'submission', $articleId);
 		}
	}

	/**
	 * Remove cover page from article
	 */
	function removeArticleCoverPage($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$formLocale = array_shift($args);
		if (!AppLocale::isLocaleValid($formLocale)) {
			$request->redirect(null, null, 'viewMetadata', $articleId);
		}

		$submission =& $this->submission;
		$journal =& $request->getJournal();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$submission->getFileName($formLocale));
		$submission->setFileName('', $formLocale);
		$submission->setOriginalFileName('', $formLocale);
		$submission->setWidth('', $formLocale);
		$submission->setHeight('', $formLocale);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleDao->updateArticle($submission);

		$request->redirect(null, null, 'viewMetadata', $articleId);
	}

	function uploadCopyeditVersion($args, $request) {
		$copyeditStage = $request->getUserVar('copyeditStage');
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $articleId);

		AuthorAction::uploadCopyeditVersion($submission, $copyeditStage);

		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	function completeAuthorCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true);

		if (AuthorAction::completeAuthorCopyedit($submission, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function downloadFile($args, $request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $articleId);
		$submission =& $this->submission;
		if (!AuthorAction::downloadAuthorFile($submission, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function download($args, $request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $articleId);
		Action::downloadFile($articleId, $fileId, $revision);
	}

	//
	// Proofreading
	//

	/**
	 * Set the author proofreading date completion
	 */
	function authorProofreadingComplete($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);

		$send = (int) array_shift($args);

		import('classes.submission.proofreader.ProofreaderAction');

		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_COMPLETE', $request, $send?'':$request->url(null, 'author', 'authorProofreadingComplete', 'send'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalley($args, $request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate($request);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyTop($args, $request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate($request);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionEditing');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyFile($args, $request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);

		import('classes.file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet($request->url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				TrackSubmissionHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function viewFile($args, $request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $articleId);
		if (!AuthorAction::viewFile($articleId, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	//
	// Payment Actions
	//

	/**
	 * Display a form to pay for the submission an article
	 * @param $args array ($articleId)
	 */
	function paySubmissionFee($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$this->setupTemplate($request, true, $articleId);

		$journal =& $request->getJournal();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_SUBMISSION, $user->getId(), $articleId, $journal->getSetting('submissionFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	/**
	 * Display a form to pay for Fast Tracking an article
	 * @param $args array ($articleId)
	 */
	function payFastTrackFee($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$this->setupTemplate($request, true, $articleId);

		$journal =& $request->getJournal();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_FASTTRACK, $user->getId(), $articleId, $journal->getSetting('fastTrackFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	/**
	 * Display a form to pay for Publishing an article
	 * @param $args array ($articleId)
	 */
	function payPublicationFee($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($request, $articleId);
		$this->setupTemplate($request, true, $articleId);

		$journal =& $request->getJournal();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_PUBLICATION, $user->getId(), $articleId, $journal->getSetting('publicationFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is the author for the article.
	 * Redirects to author index page if validation fails.
	 */
	function validate($request, $articleId) {
		parent::validate();

		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$submitterDao =& DAORegistry::getDAO('SubmitterDAO');
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$isValid = true;

		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($articleId);

		if(!$submitterDao->submitterExists($user->getId(), $articleId)) {
			$isValid = false;
		} else if ($authorSubmission == null) {
			$isValid = false;
		} else if ($authorSubmission->getJournalId() != $journal->getId()) {
			$isValid = false;
		}

		if (!$isValid) {
			$request->redirect(null, $request->getRequestedPage());
		}

		$this->journal =& $journal;
		$this->submission =& $authorSubmission;
		return true;
	}

}

?>
