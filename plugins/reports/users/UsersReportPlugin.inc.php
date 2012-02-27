<?php

/**
 * @file plugins/reports/users/UsersReportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsersReportPlugin
 * @ingroup plugins_reports_subscription
 *
 * @brief Users report plugin
 */

import('classes.plugins.ReportPlugin');

class UsersReportPlugin extends ReportPlugin {
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
		return 'UsersReportPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String display name of plugin
	 */
	function getDisplayName() {
		return __('plugins.reports.users.displayName');
	}

	/**
	 * Get the description text for this plugin.
	 * @return String description text for this plugin
	 */
	function getDescription() {
		return __('plugins.reports.users.description');
	}

	/**
	 * Generate the subscription report and write CSV contents to file
	 * @param $args array Request arguments 
	 */
	function display(&$args) {
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$userDao =& DAORegistry::getDAO('UserDAO');
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=users-' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'wt');

		$specialties = array('gastroenterology' => 'Gastroenterology',
			'generalPractitioner' => 'General Practitioner/Family Medicine',
			'gynelogicOncology' => 'Gynecologic Oncology',
			'hematology' => 'Hematology',
			'internalMedicine' => 'Internal Medicine',
			'labResearch' => 'Laboratory Research',
			'medicalOncology' => 'Medical Oncology',
			'oncologyNurse' => 'Oncology Nurse',
			'pathology' => 'Pathology',
			'pediatricOncology' => 'Pediatric Oncology',
			'pharmacist' => 'Pharmacist',
			'radiationOncology' => 'Radiation Oncology',
			'surgicalOncology' => 'Surgical Oncology',
			'urologicOncology' => 'Urologic Oncology',
			'other' => 'Other (please specify)');

		$provinces = array('britishColumbia' => 'British Columbia',
			'manitoba' => 'Manitoba',
			'newBrunswick' => 'New Brunswick',
			'newfoundlandAndLab' => 'Newfoundland and Labrador',
			'northwestTerr' => 'Northwest Territories',
			'novaScotia' => 'Nova Scotia',
			'nunavut' => 'Nunavut',
			'ontario' => 'Ontario',
			'pei' => 'Prince Edward Island',
			'quebec' => 'Quebec',
			'saskatchewan' => 'Saskatchewan',
			'yukon' => 'Yukon Territory',
			'usa' => 'United States',
			'otherForeign' => 'Other Foreign');

		// Column headings
		$columns = array(
			'user_id' => __('common.id'),
			'first_name' => __('user.firstName'),
			'middle_name' => __('user.middleName'),
			'last_name' => __('user.lastName'),
			'email' => __('user.email'),
			'phone' => __('user.phone'),
			'fax' => __('user.fax'),
			'specialty' => __('user.specialty'),
			'country' => __('common.country'),
			'province' => __('user.profile.province'),
			'compSubscription' => __('user.profile.form.compSubscriptionRequested'),
			'mailing_address' => __('common.mailingAddress'),
			'date_reg' => __('user.dateRegistered')
		);

		// Write out individual subscription column headings to file
		String::fputcsv($fp, array_values($columns));

		// Iterate over individual users and write out each to file
		$journalUsers = $users =& $roleDao->getUsersByJournalId($journal->getId());
		while ($user =& $journalUsers->next()) {
			
			foreach ($columns as $index => $junk) {
				switch ($index) {
					case 'user_id':
						$columns[$index] = $user->getId();
						break;
					case 'first_name':
						$columns[$index] = $user->getFirstName();
						break;
					case 'middle_name':
						$columns[$index] = $user->getMiddleName();
						break;
					case 'last_name':
						$columns[$index] = $user->getLastName();
						break;
					case 'email':
						$columns[$index] = $user->getEmail();
						break;
					case 'phone':
						$columns[$index] = $user->getPhone();
						break;
					case 'fax':
						$columns[$index] = $user->getFax();
						break;
					case 'specialty':
						$specialty = $user->getLocalizedData('specialty');
						if(isset($specialty)) {
							if($specialty == 'other') $specialty = $user->getLocalizedData('specialtyOther');
							else $specialty = $specialties[$specialty];
						} else $specialty = '';
						$columns[$index] = $specialty;
						break;
					case 'country':
						$columns[$index] = $countryDao->getCountry($user->getCountry());
						break;
					case 'province':
						$province = $user->getLocalizedData('province');
						if($province) $provinceText = $provinces[$province];
						else $provinceText = '';
						$columns[$index] = $provinceText;
						break;
					case 'compSubscription':
						$columns[$index] = $user->getLocalizedData('compSubscription');
						break;
					case 'mailing_address':
						$columns[$index] = $this->_html2text($user->getMailingAddress());
						break;
					case 'date_reg':
						$columns[$index] = $user->getDateRegistered();
						break;
					default:
						$columns[$index] = '';
				}
			}
			String::fputcsv($fp, $columns);
			unset($user);
		}


		fclose($fp);
	}

	/**
	 * Replace HTML "newline" tags (p, li, br) with line feeds. Strip all other tags.
	 * @param $html String Input HTML string
	 * @return String Text with replaced and stripped HTML tags
	 */
	function _html2text($html) {
		$html = String::regexp_replace('/<[\/]?p>/', chr(10), $html);
		$html = String::regexp_replace('/<li>/', '&bull; ', $html);
		$html = String::regexp_replace('/<\/li>/', chr(10), $html);
		$html = String::regexp_replace('/<br[ ]?[\/]?>/', chr(10), $html);
		$html = String::html2utf(strip_tags($html));
		return $html;
	}

}

?>
