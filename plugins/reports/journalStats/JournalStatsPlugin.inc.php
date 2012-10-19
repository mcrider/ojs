<?php

/**
 * @file JournalStatsPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class JournalStatsPlugin
 * @ingroup plugins_reports_review
 * @see JournalStatsDAO
 *
 * @brief Review report plugin
 */

//$Id$

import('classes.plugins.ReportPlugin');

class JournalStatsPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'JournalStatsPlugin';
	}

	function getDisplayName() {
		return __('plugins.reports.journalStats.displayName');
	}

	function getDescription() {
		return __('plugins.reports.journalStats.description');
	}

	/**
	 * Get the columns to be displayed in the report (optionally filtered based on what was selected)
	 * @param $request PKPRequest
	 * @param $filterFromRequest bool
	 * @return array
	 */
	function getColumns($request, $filterFromRequest = false) {
		$columns = array(
			'numPublishedIssues' => __('manager.statistics.statistics.numIssues'),
			'numPublishedSubmissions' => __('manager.statistics.statistics.itemsPublished'),
			'numSubmissions' => __('manager.statistics.statistics.numSubmissions'),
			'numReviewedSubmissions' => __('manager.statistics.statistics.peerReviewed'),
			'submissionsAccept' => __('plugins.reports.journalStats.numAccepted'),
			'submissionsAcceptPercent' => __('plugins.reports.journalStats.numAcceptedPercent'),
			'submissionsDecline' => __('plugins.reports.journalStats.numDeclined'),
			'submissionsDeclinePercent' => __('plugins.reports.journalStats.numDeclinedPercent'),
			'submissionsRevise' => __('plugins.reports.journalStats.numResubmitted'),
			'submissionsRevisePercent' => __('plugins.reports.journalStats.numResubmittedPercent'),
			'daysToPublication' => __('manager.statistics.statistics.daysToPublication'),
			'daysPerReview' => __('manager.statistics.statistics.daysPerReview'),
			'totalUsersCount' => __('plugins.reports.journalStats.totalUsers')
			/*'reader' => __('manager.statistics.statistics.totalNewValue')*/
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

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		// Get publication date for first issue to determine what year to start stats from
		$firstIssue = $issueDao->getFirstCreatedIssue($journal->getId());
		if($firstIssue->getDatePublished()) $startYear = strftime('Y', strtotime($firstIssue->getDatePublished()));
		else $startYear = date("Y");
		$currentYear = date("Y");

		$stats = array();
		for($year = $startYear; $year < $currentYear+1; $year++) {
			$fromDate = mktime(0, 0, 0, 1, 1, (int) $year);
			$toDate = mktime(23, 59, 59, 12, 31, (int) $year);

			$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getId(), $fromDate, $toDate);

			$articleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getId(), null, $fromDate, $toDate);
			$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getId(), $fromDate, $toDate);

			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sections = $sectionDao->getJournalSections($journal->getId());
			$sectionIds = array();
			while ($section =& $sections->next()) {
				$sectionIds[] = $section->getId();
			}
			$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getId(), $sectionIds, $fromDate, $toDate);
			$allUserStatistics = $journalStatisticsDao->getUserStatistics($journal->getId(), null, $toDate);

			$stats[$year]['numPublishedIssues'] = $issueStatistics['numPublishedIssues'];
			$stats[$year]['numPublishedSubmissions'] = $articleStatistics['numPublishedSubmissions'];
			$stats[$year]['numSubmissions'] = $articleStatistics['numSubmissions'];
			$stats[$year]['numReviewedSubmissions'] = $articleStatistics['numReviewedSubmissions'];
			$stats[$year]['submissionsAccept'] = $articleStatistics['submissionsAccept'];
			$stats[$year]['submissionsAcceptPercent'] = $articleStatistics['submissionsAcceptPercent'];
			$stats[$year]['submissionsDecline'] = $articleStatistics['submissionsDecline'];
			$stats[$year]['submissionsDeclinePercent'] = $articleStatistics['submissionsDeclinePercent'];
			$stats[$year]['submissionsRevise'] = $articleStatistics['submissionsRevise'];
			$stats[$year]['submissionsRevisePercent'] = $articleStatistics['submissionsRevisePercent'];
			$stats[$year]['daysToPublication'] = $articleStatistics['daysToPublication'];
			$stats[$year]['daysPerReview'] = $reviewerStatistics['daysPerReview'];
			//$stats[$year]['reviewsCount'] = $reviewerStatistics['reviewsCount'];
			//$stats[$year]['reviewerCount'] = $reviewerStatistics['reviewerCount'];
			//$stats[$year]['reviewedSubmissionCount'] = $reviewerStatistics['reviewedSubmissionCount'];
			$stats[$year]['totalUsersCount'] = $allUserStatistics['totalUsersCount'];
			//$stats[$year]['reader'] = $allUserStatistics['reader'];
		}

		$columns = $this->getColumns($request, true);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		$titleRow = array_merge(array(Locale::translate('common.year')), array_values($columns));
		String::fputcsv($fp, $titleRow);

		for($year = $startYear; $year < $currentYear+1; $year++) {
			$row = array();
			array_push($row, $year);
			foreach($stats[$year] as $key => $stat) {
				if(isset($columns[$key])) array_push($row, $stat);
			}
			String::fputcsv($fp, $row);
		}
	
		fclose($fp);
	}
}

?>
