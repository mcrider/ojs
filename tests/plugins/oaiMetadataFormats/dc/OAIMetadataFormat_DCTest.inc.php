<?php

/**
 * @defgroup tests_plugins_oaiMetadataFormats_dc
 */

/**
 * @file tests/plugins/oaiMetadataFormats/dc/OAIMetadataFormat_DCTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DCTest
 * @ingroup tests_plugins_oaiMetadataFormats_dc
 * @see OAIMetadataFormat_DC
 *
 * @brief Test class for OAIMetadataFormat_DC.
 */


import('lib.pkp.tests.PKPTestCase');

import('lib.pkp.classes.oai.OAIStruct');
import('lib.pkp.classes.oai.OAIUtils');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');

class OAIMetadataFormat_DCTest extends PKPTestCase {
	/**
	 * @covers OAIMetadataFormat_DC
	 * @covers Dc11SchemaArticleAdapter
	 */
	public function testToXml() {
		//
		// Create test data.
		//

		// Author
		import('classes.article.Author');
		$author = new Author();
		$author->setFirstName('author-firstname', 'en_US');
		$author->setLastName('author-lastname', 'en_US');
		$author->setAffiliation('author-affiliation', 'en_US');

		// Supplementary file
		import('classes.article.SuppFile');
		$suppFile = new SuppFile();
		$suppFile->setFileId(999);

		// Article
		import('classes.article.PublishedArticle');
		$article = $this->getMock('PublishedArticle', array('getBestArticleId')); /* @var $article PublishedArticle */
		$article->expects($this->any())
		        ->method('getBestArticleId')
		        ->will($this->returnValue(9));
		$article->setId(9);
		$author->setSubmissionId($article->getId());
		$authorDao =& DAORegistry::getDAO('AuthorDAO'); /* @var $authorDao AuthorDAO */
		$authorDao->insertAuthor($author);
		$article->setSuppFiles(array($suppFile));
		$article->setPages(15);
		$article->setType('art-type', 'en_US');
		$article->setTitle('article-title-en', 'en_US');
		$article->setTitle('article-title-de', 'de_DE');
		$article->setDiscipline('article-discipline', 'en_US');
		$article->setSubject('article-subject', 'en_US');
		$article->setSubjectClass('article-subject-class', 'en_US');
		$article->setAbstract('article-abstract', 'en_US');
		$article->setSponsor('article-sponsor', 'en_US');
		$article->setStoredDOI('article-doi');
		$article->setLanguage('en_US');
		$article->setCoverageGeo('article-coverage-geo', 'en_US');
		$article->setCoverageChron('article-coverage-chron', 'en_US');
		$article->setCoverageSample('article-coverage-sample', 'en_US');

		// Galleys
		import('classes.article.ArticleGalley');
		$galley = new ArticleGalley();
		$galley->setFileType('galley-filetype');
		$galleys = array($galley);

		// Journal
		import('classes.journal.Journal');
		$journal = $this->getMock('Journal', array('getSetting')); /* @var $journal Journal */
		$journal->expects($this->any())
		        ->method('getSetting') // includes getTitle()
		        ->will($this->returnCallback(array($this, 'getJournalSetting')));
		$journal->setPrimaryLocale('en_US');
		$journal->setPath('journal-path');

		// Section
		import('classes.journal.Section');
		$section = new Section();
		$section->setIdentifyType('section-identify-type', 'en_US');

		// Issue
		import('classes.issue.Issue');
		$issue = $this->getMock('Issue', array('getIssueIdentification')); /* @var $issue Issue */;
		$issue->expects($this->any())
		      ->method('getIssueIdentification')
		      ->will($this->returnValue('issue-identification'));
		$issue->setDatePublished('2010-11-05');


		//
		// Create infrastructural support objects
		//

		// Router
		import('lib.pkp.classes.core.PKPRouter');
		$router = $this->getMock('PKPRouter', array('url'));
		$application = PKPApplication::getApplication();
		$router->setApplication($application);
		$router->expects($this->any())
		       ->method('url')
		       ->will($this->returnValue('router-url'));

		// Request
		import('classes.core.Request');
		$request = $this->getMock('Request', array('getRouter'));
		$request->expects($this->any())
		        ->method('getRouter')
		        ->will($this->returnValue($router));
		Registry::set('request', $request);


		//
		// Create mock DAOs
		//

		// Create a mocked OAIDAO that returns our test data.
		import('classes.oai.ojs.OAIDAO');
		$oaiDao = $this->getMock('OAIDAO', array('getJournal', 'getSection', 'getIssue'));
		$oaiDao->expects($this->any())
		       ->method('getJournal')
		       ->will($this->returnValue($journal));
		$oaiDao->expects($this->any())
		       ->method('getSection')
		       ->will($this->returnValue($section));
		$oaiDao->expects($this->any())
		       ->method('getIssue')
		       ->will($this->returnValue($issue));
		DAORegistry::registerDAO('OAIDAO', $oaiDao);

		// Create a mocked ArticleGalleyDAO that returns our test data.
		import('classes.article.ArticleGalleyDAO');
		$articleGalleyDao = $this->getMock('OAIDAO', array('getGalleysByArticle'));
		$articleGalleyDao->expects($this->any())
		                 ->method('getGalleysByArticle')
		                 ->will($this->returnValue($galleys));
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);


		//
		// Test
		//

		// OAI record
		$record = new OAIRecord();
		$record->setData('article', $article);
		$record->setData('galleys', $galleys);
		$record->setData('journal', $journal);
		$record->setData('section', $section);
		$record->setData('issue', $issue);

		// Instantiate the OAI meta-data format.
		$prefix = OAIMetadataFormatPlugin_DC::getMetadataPrefix();
		$schema = OAIMetadataFormatPlugin_DC::getSchema();
		$namespace = OAIMetadataFormatPlugin_DC::getNamespace();
		$mdFormat = new OAIMetadataFormat_DC($prefix, $schema, $namespace);

		$xml = $mdFormat->toXml($record);
		self::assertXmlStringEqualsXmlFile('tests/plugins/oaiMetadataFormats/dc/expectedResult.xml', $xml);
	}


	//
	// Public helper methods
	//
	/**
	 * Callback for journal settings.
	 * @param $settingName string
	 */
	function getJournalSetting($settingName) {
		switch ($settingName) {
			case 'title':
				return array('en_US' => 'journal-title');

			case 'copyrightNotice':
				return array('en_US' => 'journal-copyright');

			case 'publisherInstitution':
				return array('journal-publisher');

			default:
				self::fail();
		}
	}
}
?>