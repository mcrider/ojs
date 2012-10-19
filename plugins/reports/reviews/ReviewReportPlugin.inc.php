<?php

/**
 * @file ReviewReportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewReportPlugin
 * @ingroup plugins_reports_review
 * @see ReviewReportDAO
 *
 * @brief Review report plugin
 */

//$Id$

import('classes.plugins.ReportPlugin');

class ReviewReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('ReviewReportDAO');
			$reviewReportDAO = new ReviewReportDAO();
			DAORegistry::registerDAO('ReviewReportDAO', $reviewReportDAO);
		}
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ReviewReportPlugin';
	}

	function getDisplayName() {
		return __('plugins.reports.reviews.displayName');
	}

	function getDescription() {
		return __('plugins.reports.reviews.description');
	}

	/**
	 * Get the columns to be displayed in the report (optionally filtered based on what was selected)
	 * @param $request PKPRequest
	 * @param $filterFromRequest bool
	 * @return array
	 */
	function getColumns($request, $filterFromRequest = false) {
		$columns = array(
			'round' => __('plugins.reports.reviews.round'),
			'article' => __('article.articles'),
			'articleid' => __('article.submissionId'),
			'reviewerid' => __('plugins.reports.reviews.reviewerId'),
			'reviewer' => __('plugins.reports.reviews.reviewer'),
			'firstname' => __('user.firstName'),
			'middlename' => __('user.middleName'),
			'lastname' => __('user.lastName'),
			'dateassigned' => __('plugins.reports.reviews.dateAssigned'),
			'datenotified' => __('plugins.reports.reviews.dateNotified'),
			'dateconfirmed' => __('plugins.reports.reviews.dateConfirmed'),
			'datecompleted' => __('plugins.reports.reviews.dateCompleted'),
			'datereminded' => __('plugins.reports.reviews.dateReminded'),
			'declined' => __('submissions.declined'),
			'cancelled' => __('common.cancelled'),
			'recommendation' => __('reviewer.article.recommendation'),
			'comments' => __('comments.commentsOnArticle')
		);

		if($filterFromRequest) {
			$filteredColumns = array();
			foreach ($columns as $columnKey => $columnLabel) {
				$column = $request->getUserVar($columnKey);
				if(isset($column)) $filteredColumns[$columnKey] = $columnLabel;
			}
			$columns = $filteredColumns;
		}

		return $columns;
	}

	function display(&$args, $request) {
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));
		if(!$request->getUserVar('generateReport')) {
			$this->displayColumnPicker($args, $request);
			return false;
		}

		$journal =& Request::getJournal();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=reviews-' . date('Ymd') . '.csv');

		$reviewReportDao =& DAORegistry::getDAO('ReviewReportDAO');
		list($commentsIterator, $reviewsIterator) = $reviewReportDao->getReviewReport($journal->getId());

		$comments = array();
		while ($row =& $commentsIterator->next()) {
			if (isset($comments[$row['article_id']][$row['author_id']])) {
				$comments[$row['article_id']][$row['author_id']] .= "; " . $row['comments'];
			} else {
				$comments[$row['article_id']][$row['author_id']] = $row['comments'];
			}
		}

		$yesnoMessages = array( 0 => __('common.no'), 1 => __('common.yes'));

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$recommendations = ReviewAssignment::getReviewerRecommendationOptions();

		$columns = $this->getColumns($request, true);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		while ($row =& $reviewsIterator->next()) {
			foreach ($columns as $index => $junk) {
				if (in_array($index, $yesNoArray)) {
					$columns[$index] = $yesnoMessages[$row[$index]];
				} elseif ($index == "recommendation") {
					$columns[$index] = (!isset($row[$index])) ? __('common.none') : __($recommendations[$row[$index]]);
				} elseif ($index == "comments") {
					if (isset($comments[$row['articleid']][$row['reviewerid']])) {
						$columns[$index] = $comments[$row['articleid']][$row['reviewerid']];
					} else {
						$columns[$index] = "";
					}
				} else {
					$columns[$index] = $row[$index];
				}
			}
			String::fputcsv($fp, $columns);
			unset($row);
		}
		fclose($fp);
	}
}

?>
