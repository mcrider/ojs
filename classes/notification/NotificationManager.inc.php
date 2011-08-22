<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationManager
 * @ingroup notification
 * @see NotificationDAO
 * @see Notification
 * @brief Class for Notification Manager.
 */


import('lib.pkp.classes.notification.PKPNotificationManager');

class NotificationManager extends PKPNotificationManager {
	/* @var $privilegedRoles array Cache each user's most privileged role for each article */
	var $privilegedRoles;

	/**
	 * Constructor.
	 */
	function NotificationManager() {
		parent::PKPNotificationManager();
	}


	/**
	 * Construct a URL for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationUrl(&$request, &$notification) {
		$router =& $request->getRouter();
		$dispatcher =& $router->getDispatcher();
		$type = $notification->getType();

		if(in_array($type, $this->_getArticleNotificationTypes())) {
			assert($notification->getAssocType() == ASSOC_TYPE_ARTICLE);
			$articleId = (int) $notification->getAssocId();
			$userId = $notification->getUserId();
			if(!isset($this->privilegedRoles[$userId][$articleId])) $this->privilegedRoles[$userId][$articleId] = $this->_getHighestPrivilegedRole($request, $articleId);
			$role = $this->privilegedRoles[$userId][$articleId];
			if (!$role) return false;
		}

		switch ($type) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submission', $articleId);
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $articleId);
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submission', $articleId, null, 'metadata');
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $articleId, null, 'layout');
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionReview', $articleId, null, 'editorDecision');
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $articleId, null, 'layout');
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $articleId, null, 'coypedit');
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionEditing', $articleId, null, 'proofread');
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionReview', $articleId, null, 'peerReview');
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, $role, 'submissionReview', $articleId, null, 'editorDecision');
			case NOTIFICATION_TYPE_USER_COMMENT:
				return $dispatcher->url($request, ROUTE_PAGE, null, 'comment', 'view', $articleId);
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return $dispatcher->url($request, ROUTE_PAGE, null, 'issue', 'current');
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				return $dispatcher->url($request, ROUTE_PAGE, null, 'announcement', 'view', array($notification->getAssocId()));
			default:
				return parent::getNotificationUrl($request, $notification);
		}
	}

	/**
	 * Get an array of Article-based notifications
	 * @return array
	 */
	function _getArticleNotificationTypes() {
		return array(NOTIFICATION_TYPE_ARTICLE_SUBMITTED, NOTIFICATION_TYPE_METADATA_MODIFIED,
					 NOTIFICATION_TYPE_SUPP_FILE_MODIFIED, NOTIFICATION_TYPE_GALLEY_MODIFIED,
					 NOTIFICATION_TYPE_SUBMISSION_COMMENT, NOTIFICATION_TYPE_LAYOUT_COMMENT,
					 NOTIFICATION_TYPE_COPYEDIT_COMMENT, NOTIFICATION_TYPE_PROOFREAD_COMMENT,
					 NOTIFICATION_TYPE_REVIEWER_COMMENT, NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT,
					 NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT, NOTIFICATION_TYPE_USER_COMMENT);
	}

	/**
	 * Get the most 'privileged' role a user has associated with an article.  This will
	 *  determine the URL to point them to for notifications about articles
	 * @param $articleId
	 * @return string
	 */
	function _getHighestPrivilegedRole(&$request, $articleId) {
		$user =& $request->getUser();
		$userId = $user->getId();
		$roleDao =& DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

		// Check if user is editor
		if(Validation::isEditor()) {
			return $roleDao->getRolePath(ROLE_ID_EDITOR);
		}

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO'); /* @var $editAssignmentDao EditAssignmentDAO */
		$editAssignments =& $editAssignmentDao->getEditingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment =& $editAssignments->next()) {
			if ($userId == $editAssignment->getEditorId()) return $roleDao->getRolePath(ROLE_ID_SECTION_EDITOR);
			unset($editAssignment);
		}

		// Check if user is copy/layout editor or proofreader
		$signoffDao =& DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */
		$copyedSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $articleId);
		if ($userId == $copyedSignoff->getUserId()) return $roleDao->getRolePath(ROLE_ID_COPYEDITOR);

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		if ($userId == $layoutSignoff->getUserId()) return $roleDao->getRolePath(ROLE_ID_LAYOUT_EDITOR);

		$proofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
		if ($userId == $proofSignoff->getUserId()) return $roleDao->getRolePath(ROLE_ID_PROOFREADER);

		// Check if user is author
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article =& $articleDao->getArticle($articleId);
		if ($userId == $article->getUserId()) return $roleDao->getRolePath(ROLE_ID_AUTHOR);

		// Check if user is reviewer
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
		$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($articleId);
		foreach ($reviewAssignments as $reviewAssignment) {
			if ($userId == $reviewAssignment->getReviewerId()) return $roleDao->getRolePath(ROLE_ID_REVIEWER);
		}

		// Not affiliated with this article; return false
		return false;
	}

	/**
	 * Construct the contents for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationContents(&$request, &$notification) {
		$type = $notification->getType();
		assert(isset($type));

		$notificationDao =& DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */

		// Any notification types declared before NOTIFICATION_TYPE_USER_COMMENT in
		//  the Notification class have articles as associated objects
		if($notification->getLevel() != NOTIFICATION_LEVEL_TRIVIAL && in_array($type, $this->_getArticleNotificationTypes())) {
			assert($notification->getAssocType() == ASSOC_TYPE_ARTICLE);
			assert(is_numeric($notification->getAssocId()));
			$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
			$article =& $articleDao->getArticle($notification->getAssocId());
			$title = $article->getLocalizedTitle();
		}

		switch ($type) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				return __('notification.type.articleSubmitted', array('title' => $title));
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return __('notification.type.suppFileModified', array('title' => $title));
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
				return __('notification.type.metadataModified', array('title' => $title));
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return __('notification.type.galleyModified', array('title' => $title));
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				return __('notification.type.submissionComment', array('title' => $title));
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
				return __('notification.type.layoutComment', array('title' => $title));
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
				return __('notification.type.copyeditComment', array('title' => $title));
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
				return __('notification.type.proofreadComment', array('title' => $title));
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				return __('notification.type.reviewerComment', array('title' => $title));
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				return __('notification.type.reviewerFormComment', array('title' => $title));
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
				return __('notification.type.editorDecisionComment', array('title' => $title));
			case NOTIFICATION_TYPE_USER_COMMENT:
				return __('notification.type.userComment', array('title' => $title));
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return __('notification.type.issuePublished');
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				return __('notification.type.newAnnouncement');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS:
				return __('gifts.giftRedeemed');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
				return __('gifts.noGiftToRedeem');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
				return __('gifts.giftAlreadyRedeemed');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
				return __('gifts.giftNotValid');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
				return __('gifts.subscriptionTypeNotValid');
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
				return __('gifts.subscriptionNonExpiring');
			case NOTIFICATION_TYPE_BOOK_REQUESTED:
				return __('plugins.generic.booksForReview.notification.bookRequested');
			case NOTIFICATION_TYPE_BOOK_CREATED:
				return __('plugins.generic.booksForReview.notification.bookCreated');
			case NOTIFICATION_TYPE_BOOK_UPDATED:
				return __('plugins.generic.booksForReview.notification.bookUpdated');
			case NOTIFICATION_TYPE_BOOK_DELETED:
				return __('plugins.generic.booksForReview.notification.bookDeleted');
			case NOTIFICATION_TYPE_BOOK_MAILED:
				return __('plugins.generic.booksForReview.notification.bookMailed');
			case NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
				return __('plugins.generic.booksForReview.notification.settingsSaved');
			case NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
				return __('plugins.generic.booksForReview.notification.submissionAssigned');
			case NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
				return __('plugins.generic.booksForReview.notification.authorAssigned');
			case NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
				return __('plugins.generic.booksForReview.notification.authorDenied');
			case NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
				return __('plugins.generic.booksForReview.notification.authorRemoved');
			case NOTIFICATION_TYPE_SWORD_DEPOSIT_COMPLETE:
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				$params = $notificationSettingsDao->getNotificationSettings($notification->getId());
				return __('plugins.generic.sword.depositComplete', $this->getParamsForCurrentLocale($params));
			case NOTIFICATION_TYPE_SWORD_AUTO_DEPOSIT_COMPLETE:
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				$params = $notificationSettingsDao->getNotificationSettings($notification->getId());
				return __('plugins.generic.sword.automaticDepositComplete', $this->getParamsForCurrentLocale($params));
			case NOTIFICATION_TYPE_SWORD_ENABLED:
				return __('plugins.generic.sword.enabled');
			case NOTIFICATION_TYPE_SWORD_DISABLED:
				return __('plugins.generic.sword.disabled');
			default:
				return parent::getNotificationContents($request, $notification);
		}
	}

	/**
	 * get notification style class
	 * @param $notification Notification
	 * @return string
	 */
	function getStyleClass(&$notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_BOOK_REQUESTED:
			case NOTIFICATION_TYPE_BOOK_CREATED:
			case NOTIFICATION_TYPE_BOOK_UPDATED:
			case NOTIFICATION_TYPE_BOOK_DELETED:
			case NOTIFICATION_TYPE_BOOK_MAILED:
			case NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
			case NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS:
					return 'notifySuccess';
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
					return 'notifyError';
			default: return parent::getStyleClass($notification);
		}
	}

	/**
	 * Return a CSS class containing the icon of this notification type
	 * @param $notification Notification
	 * @return string
	 */
	function getIconClass(&$notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				return 'notifyIconNewPage';
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return 'notifyIconPageAttachment';
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return 'notifyIconEdit';
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
			case NOTIFICATION_TYPE_LAYOUT_COMMENT:
			case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
			case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
			case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
			case NOTIFICATION_TYPE_USER_COMMENT:
				return 'notifyIconNewComment';
			case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
				return 'notifyIconPublished';
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				return 'notifyIconNewAnnouncement';
			case NOTIFICATION_TYPE_BOOK_REQUESTED:
			case NOTIFICATION_TYPE_BOOK_CREATED:
			case NOTIFICATION_TYPE_BOOK_UPDATED:
			case NOTIFICATION_TYPE_BOOK_DELETED:
			case NOTIFICATION_TYPE_BOOK_MAILED:
			case NOTIFICATION_TYPE_BOOK_SETTINGS_SAVED:
			case NOTIFICATION_TYPE_BOOK_SUBMISSION_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_ASSIGNED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_DENIED:
			case NOTIFICATION_TYPE_BOOK_AUTHOR_REMOVED:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS:
				return 'notifyIconSuccess';
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
			case NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
				return 'notifyIconError';
			default: return parent::getIconClass($notification);
		}
	}

	/**
	 * Returns an array of information on the journal's subscription settings
	 * @return array
	 */
	function getSubscriptionSettings(&$request) {
		$journal = $request->getJournal();
		if (!$journal) return array();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		$settings = array('subscriptionsEnabled' => $paymentManager->acceptSubscriptionPayments(),
				'allowRegReviewer' => $journal->getSetting('allowRegReviewer'),
				'allowRegAuthor' => $journal->getSetting('allowRegAuthor'));

		return $settings;
	}
}

?>
