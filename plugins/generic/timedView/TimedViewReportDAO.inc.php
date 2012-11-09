<?php

/**
 * @file plugins/generic/timedView/TimedViewReportDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimedViewReportDAO
 * @ingroup plugins_generic_timedView
 *
 * @brief Timed view report DAO
 */


import('submission.common.Action');

class TimedViewReportDAO extends DAO {
	/**
	 * Get the abstract view count for each article in a journal.
	 * @param $journalId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getAbstractViewCount($journalId, $startDate = null, $endDate = null) {
		if ($startDate && $endDate) {
			$result =& $this->retrieve(
				sprintf('SELECT tvl.article_id, COUNT(tvl.article_id) AS total_abstract_views
						FROM timed_views_log tvl
						WHERE tvl.galley_id IS NULL
							AND tvl.journal_id = ?
							AND tvl.date >= %s
							AND tvl.date <= %s
						GROUP BY article_id',
						$this->datetimeToDB($startDate),
						$this->datetimeToDB($endDate)),
						array((int) $journalId)
				);
		} else {
			$result =& $this->retrieve(
				'SELECT tvl.article_id, COUNT(tvl.article_id) AS total_abstract_views
						FROM timed_views_log tvl
						WHERE tvl.galley_id IS NULL
							AND tvl.journal_id = ?
						GROUP BY article_id',
				array((int) $journalId)
			);
		}
		$abstractViewCount =& new DBRowIterator($result);
		unset($result);

		return $abstractViewCount;
	}

	/**
	 * Get the view count for each article's galleys.
	 * @param $articleId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getGalleyViewCountsForArticle($articleId, $startDate = null, $endDate = null) {
		if ($startDate && $endDate) {
			$result =& $this->retrieve(
				sprintf('SELECT tvl.article_id, tvl.galley_id, COUNT(tvl.galley_id) AS total_galley_views, ag.label
						FROM timed_views_log tvl
						LEFT JOIN article_galleys ag ON (tvl.galley_id = ag.galley_id)
						WHERE tvl.galley_id IS NOT NULL
							AND tvl.date >= %s
							AND tvl.date <= %s
							AND tvl.article_id = ?
						GROUP BY galley_id, article_id',
						$this->datetimeToDB($startDate),
						$this->datetimeToDB($endDate)),
						array((int) $articleId)
				);
		} else {
			$result =& $this->retrieve(
				'SELECT tvl.article_id, tvl.galley_id, COUNT(tvl.galley_id) AS total_galley_views, ag.label
						FROM timed_views_log tvl
						LEFT JOIN article_galleys ag ON (tvl.galley_id = ag.galley_id)
						WHERE tvl.galley_id IS NOT NULL
							AND tvl.article_id = ?
						GROUP BY galley_id, article_id',
				array((int) $articleId)
			);
		}
		$abstractViewCount =& new DBRowIterator($result);
		unset($result);

		return $abstractViewCount;
	}

	/**
	 * Get an array of country_code => views for an article
	 * @param $articleId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getCountryViewCountsForArticle($articleId, $startDate = null, $endDate = null) {
		if ($startDate && $endDate) {
			$result =& $this->retrieve(
				sprintf('SELECT COUNT(*) AS total_views, country_code
					FROM timed_views_log tvl
					WHERE tvl.date >= %s
						AND tvl.date <= %s
						AND tvl.article_id = ?
					GROUP BY country_code',
					$this->datetimeToDB($startDate),
					$this->datetimeToDB($endDate)),
					array((int) $articleId)
				);
		} else {
			$result =& $this->retrieve(
				'SELECT COUNT(*) AS total_views, country_code
					FROM timed_views_log tvl
					WHERE tvl.article_id = ?
					GROUP BY country_code',
				array((int) $articleId)
			);
		}

		$viewCount = array();
		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$code = $row['country_code'];
			$viewCount[$code] = $row['total_views'];
			$result->moveNext();
			unset($row);
		}

		$result->Close();
		unset($result);
	
		return $viewCount;
	}

	/**
	 * Get an array of country codes that have visited a journal's articles
	 * @param $journalId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getAllCountryCodes($journalId, $startDate = null, $endDate = null) {
		if ($startDate && $endDate) {
			$result =& $this->retrieve(
				sprintf('SELECT country_code
					FROM timed_views_log tvl
					WHERE tvl.journal_id = ?
						AND tvl.date >= %s
						AND tvl.date <= %s
					GROUP BY country_code',
					$this->datetimeToDB($startDate),
					$this->datetimeToDB($endDate)),
					array((int) $journalId)
				);
		} else {
			$result =& $this->retrieve(
				'SELECT country_code
					FROM timed_views_log tvl
					WHERE tvl.journal_id = ?
					GROUP BY country_code',
				array((int) $journalId)
			);
		}

		$countries = array();
		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$countries[] = empty($row['country_code']) ? null : $row['country_code'];
			$result->moveNext();
			unset($row);
		}

		$result->Close();
		unset($result);
	
		return $countries;
	}

	/**
	 * Increment the view count for a published article
	 * @param $journalId int
	 * @param $pubId int
	 * @param $ipAddress string
	 * @param $userAgent string
	 */
	function incrementViewCount($journalId, $articleId, $galleyId = null, $ipAddress = null, $userAgent = null, $countryCode = null) {
		$this->update(
			sprintf('INSERT INTO timed_views_log
				(article_id, galley_id, journal_id, date, ip_address, user_agent, country_code)
				VALUES
				(?, ?, ?, %s, ?, ?, ?)',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				(int) $articleId,
				isset($galleyId) ? (int) $galleyId : null,
				(int) $journalId,
				$ipAddress,
				$userAgent,
				$countryCode
			)
		);
	}

	/**
	 * Clear records prior to the given date
	 * @param $dateClear string
	 * @param $journalId int
	 */
	function clearLogs($dateClear, $journalId) {
		return $this->update(sprintf('DELETE FROM timed_views_log WHERE date < %s AND journal_id = ?', $this->datetimeToDB($dateClear)), (int) $journalId);
	}
}

?>
