<?php

/**
 * @file plugins/generic/articleMetadataImport/ArticleMetadataImportPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetadataImport
 * @ingroup plugins_generic_articleMetadataImport
 *
 * @brief ArticleMetadataImport plugin class
 */

import('lib.pkp.classes.xml.XMLHelper');
import('lib.pkp.classes.plugins.GenericPlugin');

class ArticleMetadataImportPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();

		// Check if we're importing, otherwise just register the callback
		$submissionId = Request::getUserVar('submissionId');
		$saving = Request::getUserVar('saving');
		if(isset($saving)) {
			$this->saveXml($submissionId);
		} elseif(isset($submissionId)) {
			$this->importXml($submissionId);
		}

		if ($success && $this->getEnabled()) {
			HookRegistry::register('Templates::SectionEditor::SubmissionEditing::PreLayout', array($this, 'insertForm'));
		}
		return $success;
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.articleMetadataImport.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.articleMetadataImport.description');
	}

	/**
	 * Callback to insert the ArticleMetadataImport interface into the editing page
	 */
	function insertForm($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];

			$submission =& $smarty->get_template_vars('submission'); /* @var $submission Article */

			$smarty->assign_by_ref('submissionId', $submission->getId());
			$smarty->assign('pluginUrl', $this->smartyPluginUrl(array('path' => 'importXml'), $smarty));

			$output .= $smarty->fetch($this->getTemplatePath() . '/articleMetadataImport.tpl');

			return false;

		}
	}

	function importXml($submissionId) {
		$user =& Request::getUser();
		import('classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_OJS_MANAGER, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION));
		if($temporaryFile = $temporaryFileManager->handleUpload('xmlFile', $user->getId())) {
			$fileContents = $temporaryFileManager->readfile($temporaryFile->getId(), $user->getId());

			// $simple = simplexml_load_string($fileContents);
			// $items = json_decode( json_encode($simple) , 1);

			$xml = new SimpleXMLElement($fileContents);
			//$items = $xml->front->journal-meta;

			$path = $xml->xpath('/article/front/article-meta/title-group/article-title');
			$metadata['articleTitle'] = isset($path[0]) ? $this->_getInnerHtml($path, 'article-title') : null;

			// If the language is not english, this is the title translated to english
			$path = $xml->xpath('/article/front/article-meta/title-group/trans-title-group/trans-title');
			$metadata['transTitle'] = isset($path[0]) ? $this->_getInnerHtml($path, 'trans-title') : null;

			$path = $xml->xpath('/article/front/article-meta/abstract/text()');
			$metadata['abstract'] = isset($path[0]) ? $this->_getInnerHtml($path, 'abstract') : null;

			// If the language is not english, this is the title translated to english
			$path = $xml->xpath('/article/front/article-meta/trans-abstract/text()');
			$metadata['transAbstract'] = isset($path[0]) ? $this->_getInnerHtml($path, 'trans-abstract') : null;

			$path = $xml->xpath('/article/front/article-meta/article-id[@pub-id-type="doi"][1]');
			$metadata['doi'] = isset($path[0]) ? trim((string) $path[0]) : null;

			$keywordXml = $xml->xpath('/article/front/article-meta/kwd-group/@xml:lang|/article/front/article-meta/kwd-group/kwd');
			$keywords = array();
			$i = 1;
			$currentKeywordLocale = 'en_US';
			foreach ($keywordXml as $keyword) {
				$keyword = (string) $keyword;
				if(strlen($keyword) == 2) {
					// This is a 2 letter locale key.  Convert to our locale key format.
					$locale = $this->_getLocaleCodeFrom2Letter(strtolower($keyword));
					$currentKeywordLocale = $locale;
					$i = 1;
				} else $keywords[$currentKeywordLocale]['kwd'.$i] = trim($keyword);
				$i++;
			}
			foreach ($keywords as $keywordLocale => $words) {
				$metadata['keywords'][$keywordLocale] = implode('; ',$words);
			}

			$path = $xml->xpath('/article/@xml:lang');
			$metadata['language'] = isset($path[0]) ? trim((string) $path[0]) : null;

			// Get supporting agencies (saved by <br>'ing together and setting to setSponsor)
			$agencies = array();
			$path = $xml->xpath('/article/back/fn-group/fn[@fn-type="financial-disclosure"][1]');
			if(isset($path[0])) $agencies[] = trim((string) $path[0]);
			$path = $xml->xpath('/article/back/fn-group/fn[@fn-type="conflict"][1]');
			if(isset($path[0])) $agencies[] = trim((string) $path[0]);
			$path = $xml->xpath('/article/back/ack');
			if(isset($path[0])) $agencies[] = trim((string) $path[0]);
			$metadata['agencies'] = empty($agencies) ? null : implode('<br />', $agencies);

			// Get labels for all affiliations (authors crossreference this list)
			$affiliationPaths = $xml->xpath('/article/front/article-meta/contrib-group/aff');
			$affiliations = array();
			$i = 1;
			foreach ($affiliationPaths as $affiliation) {
				$affiliations['aff'.$i] = isset($affiliation[0]) ? $this->_getInnerHtml($affiliation->xpath('text()'), 'aff') : null;

				$this->_getInnerHtml($affiliation);
				$i++;
			}

			$paths = $xml->xpath('/article/front/article-meta/contrib-group/contrib[@contrib-type="author"]');
			$authors = array();
			$authorDao = DAORegistry::getDAO('AuthorDAO');
			$currentAuthors = $authorDao->getAuthorsByArticle($submissionId);
			foreach ($paths as $path) {
				$author = array();

				$author['lastname'] = trim((string) $path->name->surname);
				$author['firstname'] = trim((string) $path->name->{'given-names'});
				$author['primary'] = trim((string) $path['corresp']) == 'yes' ? true : false;

				// Get affiliations
				$authorAffils = $path->xpath('xref[@ref-type="aff"]');
				$authorAffilArray = array();
				foreach ($authorAffils as $authorAffil) {
					// The xref is an index into the affiliations array
					$xrefs = $authorAffil[0]->attributes();
					$xref = (string) $xrefs['rid'];

					if(isset($affiliations[$xref])) $authorAffilArray[] = $affiliations[$xref];
				}
				$author['affiliation'] = implode('<br />', $authorAffilArray);

				// Check if the author exists, otherwise this author won't be used
				$author['authorId'] = 0;
				foreach($currentAuthors as $currentAuthor) {
					if(strtolower($currentAuthor->getFirstName()) == strtolower($author['firstname'])
						&& strtolower($currentAuthor->getLastName()) == strtolower($author['lastname'])) {
						$author['authorId'] = $currentAuthor->getId();
					}
				}

				$authors[] = $author;
			}
			$metadata['authors'] = $authors;

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('submissionId', $submissionId);
			$templateMgr->assign('metadata', $metadata);
			$templateMgr->assign('pluginUrl', $this->smartyPluginUrl(array('path' => 'saveXml'), $templateMgr));
			$templateMgr->display($this->getTemplatePath() . 'importConfirm.tpl');

			// Not here: Publication date, article section

		}
	}

	// Get contents of a node including all tags (i.e. HTML)
	function _getInnerHtml($path, $tag = null) {
		$innerHTML = '';
		foreach ($path as $x) {
			$innerHTML .= $x->asXML();
		}

		// Strip $tag from the string since the outer tag is included
		if($tag && (substr_compare($tag, "aff", 0, 3) === 0) || $tag == 'trans-abstract') {
			$innerHTML = preg_replace('/<'.$tag.'(.*)">/', '', $innerHTML);
			$innerHTML = str_replace('</'.$tag.'>', '', $innerHTML);
		} elseif($tag) {
			$innerHTML = str_replace('<'.$tag.'>', '', $innerHTML);
			$innerHTML = str_replace('</'.$tag.'>', '', $innerHTML);
		}

		return $innerHTML;
	}

	function saveXml($submissionId) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$article = $articleDao->getArticle($submissionId);

		$language = Request::getUserVar('language');
		if($language) {
			$locale = $this->_getLocaleCodeFrom2Letter(strtolower($language));
		}
		if(!isset($locale)) $locale = 'en_US';
		$article->setLanguage($locale);

		// Get data from form to save as article metadata
		$title = Request::getUserVar('articleTitle');
		$transTitle = Request::getUserVar('transTitle');
		$abstract = Request::getUserVar('abstract');
		$transAbstract = Request::getUserVar('transAbstract');
		$doi = Request::getUserVar('doi');
		$keywords = Request::getUserVar('keywords');
		$agencies = Request::getUserVar('agencies');
		$authors = Request::getUserVar('authors');

		if(!empty($title)) $article->setTitle($title, $locale);
		if($transTitle) $article->setTitle($transTitle, 'en_US');

		if(!empty($abstract)) $article->setAbstract($abstract, $locale);
		if($transAbstract) $article->setAbstract($transAbstract, 'en_US');

		if($doi) $article->setStoredDOI($doi);
		if($keywords) {
			foreach($keywords as $keywordLocale => $keywordString) {
				$article->setSubject($keywordString, $keywordLocale);
			}
		}
		if($agencies) $article->setSponsor($agencies);

		// Delete current authors and add from XML
		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$acceptedAuthors = array();
		foreach($authors as $authorId => $author) {
			if(empty($authorId)) continue;
			$currentAuthor = $authorDao->getAuthor($authorId);
			if(!$currentAuthor) continue;
			$acceptedAuthors[] = $authorId;

			$currentAuthor->setFirstName($author['firstname']);
			$currentAuthor->setMiddleName('');
			$currentAuthor->setLastName($author['lastname']);
			$currentAuthor->setAffiliation($author['affiliation'], $locale);
			if($author['primary']) $currentAuthor->setPrimary(true);

			$authorDao->updateAuthor($currentAuthor);
		}

		// Delete authors not in accepted authors (as they don't exist in the XML)
		$authors =& $authorDao->getAuthorsByArticle($submissionId);
		foreach ($authors as $author) {
			if(!in_array($author->getId(), $acceptedAuthors)) $this->deleteAuthor($author);
		}

		$articleDao->updateArticle($article);

		Request::redirect(null, null, 'submissionEditing', $submissionId);
	}

	function _getLocaleCodeFrom2Letter($language) {
		$threeLetter = Locale::get3LetterFrom2LetterIsoLanguage($language);
		$locale = Locale::getLocaleFrom3LetterIso($threeLetter);

		return $locale;
	}
}

?>
