<?php

/**
 * @file pages/comment/CommentHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user comments.
 *
 */

// $Id$


import('classes.rt.ojs.RTDAO');
import('classes.rt.ojs.JournalRT');
import('classes.handler.Handler');

class CommentHandler extends Handler {
	/** issue associated with this request **/
	var $issue;

	/** article associated with this request **/
	var $article;

	/**
	 * Constructor
	 **/
	function CommentHandler() {
		parent::Handler();
	}

	/**
	 * View a comment
	 * @param $args array
	 * @param $request object
	 */
	function view($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($articleId);
		$article =& $this->article;

		$user =& Request::getUser();
		$userId = isset($user)?$user->getId():null;

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$comment =& $commentDao->getById($commentId, $articleId, 2);

		$journal =& Request::getJournal();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isManager = $roleDao->userHasRole($journal->getId(), $userId, ROLE_ID_JOURNAL_MANAGER);

		if (!$comment) $comments =& $commentDao->getRootCommentsBySubmissionId($articleId, 1);
		else $comments =& $comment->getChildren();

		$this->setupTemplate($article, $galleyId, $comment);

		$templateMgr =& TemplateManager::getManager();
		if (Request::getUserVar('refresh')) $templateMgr->setCacheability(CACHEABILITY_NO_CACHE);
		if ($comment) {
			$templateMgr->assign_by_ref('comment', $comment);
			$templateMgr->assign_by_ref('parent', $commentDao->getById($comment->getParentCommentId(), $articleId));
		}
		$templateMgr->assign_by_ref('comments', $comments);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));
		$templateMgr->assign('isManager', $isManager);

		$templateMgr->display('comment/comments.tpl');
	}

	/**
	 * Add a comment
	 * @param $args array
	 * @param $request Request
	 */
	function add($args, $request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$parentId = isset($args[2]) ? (int) $args[2] : 0;
		$journal =& Request::getJournal();
		$commentDao =& DAORegistry::getDAO('CommentDAO');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$parent =& $commentDao->getById($parentId, $articleId);
		if (isset($parent) && $parent->getSubmissionId() != $articleId) {
			Request::redirect(null, null, 'view', array($articleId, $galleyId));
		}

		$this->validate($articleId);
		$this->setupTemplate($publishedArticle, $galleyId, $parent);

		// Bring in comment constants
		$enableComments = $journal->getSetting('enableComments');
		switch ($enableComments) {
			case COMMENTS_UNAUTHENTICATED:
				break;
			case COMMENTS_AUTHENTICATED:
			case COMMENTS_ANONYMOUS:
				// The user must be logged in to post comments.
				if (!Request::getUser()) {
					Validation::redirectLogin();
				}
				break;
			default:
				// Comments are disabled.
				Validation::redirectLogin();
		}

		import('classes.comment.form.CommentForm');
		$commentForm = new CommentForm(null, $articleId, $galleyId, isset($parent)?$parentId:null);
		$commentForm->initData();

		if (isset($args[3]) && $args[3]=='save') {
			$commentForm->readInputData();
			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDao->getArticle($articleId);
				$notificationUsers = $article->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_USER_COMMENT,
						$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
					);
				}

				Request::redirect(null, null, 'view', array($articleId, $galleyId, $parentId), array('refresh' => 1));
			}
		}

		$commentForm->display();
	}

	/**
	 * Delete the specified comment and all its children.
	 * @params $args array
	 */
	function delete($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($articleId);
		$journal =& Request::getJournal();
		$user =& Request::getUser();
		$userId = isset($user)?$user->getId():null;

		$commentDao =& DAORegistry::getDAO('CommentDAO');

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		if (!$roleDao->userHasRole($journal->getId(), $userId, ROLE_ID_JOURNAL_MANAGER)) {
			Request::redirect(null, 'index');
		}

		$comment =& $commentDao->getById($commentId, $articleId, SUBMISSION_COMMENT_RECURSE_ALL);
		if ($comment)$commentDao->deleteComment($comment);

		Request::redirect(null, null, 'view', array($articleId, $galleyId), array('refresh' => '1'));
	}

	/**
	 * Validation
	 * @param $articleId int
	 */
	function validate($articleId) {
		parent::validate();

		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$article =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);

		// Bring in comment constants
		$commentDao =& DAORegistry::getDAO('CommentDAO');

		$enableComments = $journal->getSetting('enableComments');

		if ((!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess')) || ($article && !$article->getEnableComments()) || ($enableComments != COMMENTS_ANONYMOUS && $enableComments != COMMENTS_AUTHENTICATED && $enableComments != COMMENTS_UNAUTHENTICATED)) {
			Validation::redirectLogin();
		}

		// Subscription Access
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueByArticleId($articleId);

		if (isset($issue) && isset($article)) {
			import('classes.issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser($journal, $issue->getId(), $articleId);

			if (!(!$subscriptionRequired || $article->getAccessStatus() == ARTICLE_ACCESS_OPEN || $subscribedUser)) {
				Request::redirect(null, 'index');
			}
		} else {
			Request::redirect(null, 'index');
		}

		$this->issue =& $issue;
		$this->article =& $article;
		return true;
	}

	function setupTemplate($article, $galleyId, $comment = null) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_READER));
		$templateMgr =& TemplateManager::getManager();
		$journal =& Request::getJournal();

		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}

		$pageHierarchy = array(
			array(
				Request::url(null, 'article', 'view', array(
					$article->getBestArticleId(Request::getJournal()), $galleyId
				)),
				String::stripUnsafeHtml($article->getLocalizedTitle()),
				true
			)
		);

		if ($comment) $pageHierarchy[] = array(Request::url(null, 'comment', 'view', array($article->getId(), $galleyId)), 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
