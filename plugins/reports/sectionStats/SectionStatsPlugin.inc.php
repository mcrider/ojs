<?php

/**
 * @file SectionStatsPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class SectionStatsPlugin
 * @ingroup plugins_reports_review
 * @see SectionStatsDAO
 *
 * @brief Review report plugin
 */

import('classes.plugins.ReportPlugin');

class SectionStatsPlugin extends ReportPlugin {
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
		return 'SectionStatsPlugin';
	}

	function getDisplayName() {
		return __('plugins.reports.sectionStats.displayName');
	}

	function getDescription() {
		return __('plugins.reports.sectionStats.description');
	}

	/**
	 * Get the columns to be displayed in the report (optionally filtered based on what was selected)
	 * @param $request PKPRequest
	 * @param $filterFromRequest bool
	 * @return array
	 */
	function getColumns($request, $filterFromRequest = false) {
		$columns = array(
			//'numPublishedIssues' => __('manager.statistics.statistics.numIssues'),
			'numPublishedSubmissions' => __('manager.statistics.statistics.itemsPublished'),
			'numSubmissions' => __('manager.statistics.statistics.numSubmissions'),
			'numReviewedSubmissions' => __('manager.statistics.statistics.peerReviewed'),
			'submissionsAccept' => __('plugins.reports.sectionStats.numAccepted'),
			'submissionsAcceptPercent' => __('plugins.reports.sectionStats.numAcceptedPercent'),
			'submissionsDecline' => __('plugins.reports.sectionStats.numDeclined'),
			'submissionsDeclinePercent' => __('plugins.reports.sectionStats.numDeclinedPercent'),
			'submissionsRevise' => __('plugins.reports.sectionStats.numResubmitted'),
			'submissionsRevisePercent' => __('plugins.reports.sectionStats.numResubmittedPercent'),
			'daysToPublication' => __('manager.statistics.statistics.daysToPublication'),
			'daysPerReview' => __('manager.statistics.statistics.daysPerReview'),
			//'totalUsersCount' => __('plugins.reports.sectionStats.totalUsers')
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
		$sectionTitles = array();
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
				$sectionTitles[] = $section->getLocalizedTitle() . "($year)";
				$sectionId = $section->getId();

				$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getId(), array($sectionId), $fromDate, $toDate);


				$stats[$year][$sectionId]['numPublishedSubmissions'] = $articleStatistics['numPublishedSubmissions'];
				$stats[$year][$sectionId]['numSubmissions'] = $articleStatistics['numSubmissions'];
				$stats[$year][$sectionId]['numReviewedSubmissions'] = $articleStatistics['numReviewedSubmissions'];
				$stats[$year][$sectionId]['submissionsAccept'] = $articleStatistics['submissionsAccept'];
				$stats[$year][$sectionId]['submissionsAcceptPercent'] = $articleStatistics['submissionsAcceptPercent'];
				$stats[$year][$sectionId]['submissionsDecline'] = $articleStatistics['submissionsDecline'];
				$stats[$year][$sectionId]['submissionsDeclinePercent'] = $articleStatistics['submissionsDeclinePercent'];
				$stats[$year][$sectionId]['submissionsRevise'] = $articleStatistics['submissionsRevise'];
				$stats[$year][$sectionId]['submissionsRevisePercent'] = $articleStatistics['submissionsRevisePercent'];
				$stats[$year][$sectionId]['daysToPublication'] = $articleStatistics['daysToPublication'];
				$stats[$year][$sectionId]['daysPerReview'] = $reviewerStatistics['daysPerReview'];
				$stats[$year][$sectionId]['reviewsCount'] = $reviewerStatistics['reviewsCount'];
				$stats[$year][$sectionId]['reviewerCount'] = $reviewerStatistics['reviewerCount'];
				//$stats[$year][$sectionId]['reviewedSubmissionCount'] = $reviewerStatistics['reviewedSubmissionCount'];
			}
			
		}

		$columns = $this->getColumns($request, true);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		$titleRow = array_merge(array(''), $sectionTitles);
		String::fputcsv($fp, $titleRow);

		foreach ($columns as $key => $label) {
			$row = array();
			array_push($row, $label);

			foreach($stats as $year) {
				foreach ($year as $section) {
					array_push($row, $section[$key]);
				}
			}
			String::fputcsv($fp, $row);
		}
	
		fclose($fp);
	}
}

?>
