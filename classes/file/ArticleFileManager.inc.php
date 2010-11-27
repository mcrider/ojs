<?php

/**
 * @file classes/file/ArticleFileManager.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleFileManager
 * @ingroup file
 *
 * @brief Class defining operations for article file management.
 *
 * Article directory structure:
 * [article id]/note
 * [article id]/public
 * [article id]/submission
 * [article id]/submission/original
 * [article id]/submission/review
 * [article id]/submission/editor
 * [article id]/submission/copyedit
 * [article id]/submission/layout
 * [article id]/supp
 * [article id]/attachment
 */


import('lib.pkp.classes.file.FileManager');
import('classes.article.ArticleFile');

class ArticleFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated article */
	var $articleId;

	/** @var Article the associated article */
	var $article;

	/**
	 * Constructor.
	 * Create a manager for handling article file uploads.
	 * @param $articleId int
	 */
	function ArticleFileManager($articleId) {
		$this->articleId = $articleId;
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->article =& $articleDao->getArticle($articleId);
		$journalId = $this->article->getJournalId();
		$this->filesDir = Config::getVar('files', 'files_dir') . '/journals/' . $journalId .
		'/articles/' . $articleId . '/';
	}

	/**
	 * Upload a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionFile($fileName, $fileId = null, $overwrite = false) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_SUBMISSION, $fileId, $overwrite);
	}

	/**
	 * Upload a file to the review file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_REVIEW, $fileId);
	}

	/**
	 * Upload a file to the editor decision file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadEditorDecisionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_EDITOR, $fileId);
	}

	/**
	 * Upload a file to the copyedit file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadCopyeditFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_COPYEDIT, $fileId);
	}

	/**
	 * Upload a section editor's layout editing file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is null if failure
	 */
	function uploadLayoutFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_LAYOUT, $fileId, $overwrite);
	}

	/**
	 * Upload a supp file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSuppFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Upload a public file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadPublicFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Upload a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionNoteFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, ARTICLE_FILE_USE_CASE_NOTE, $fileId, $overwrite);
	}

	/**
	 * Write a public file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writePublicFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, ARTICLE_FILE_USE_CASE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Copy a public file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyPublicFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, ARTICLE_FILE_USE_CASE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Write a supplemental file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writeSuppFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, ARTICLE_FILE_USE_CASE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy a supplemental file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copySuppFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, ARTICLE_FILE_USE_CASE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy an attachment file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyAttachmentFile($url, $mimeType, $fileId = null, $overwrite = true, $assocId = null) {
		return $this->handleCopy($url, $mimeType, ARTICLE_FILE_USE_CASE_ATTACHMENT, $fileId, $overwrite, $assocId);
	}

	/**
	 * Retrieve file information by file ID.
	 * @return ArticleFile
	 */
	function &getFile($fileId, $revision = null) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile =& $articleFileDao->getArticleFile($fileId, $revision, $this->articleId);
		return $articleFile;
	}

	/**
	 * Read a file's contents.
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function readFile($fileId, $revision = null, $output = false) {
		$articleFile =& $this->getFile($fileId, $revision);

		if (isset($articleFile)) {
			$filePath = $this->filesDir .  $this->useCaseToPath($articleFile->getUseCase()) . '/' . $articleFile->getFileName();

			return parent::readFile($filePath, $output);
		} else {
			return false;
		}
	}

	/**
	 * Delete a file by ID.
	 * If no revision is specified, all revisions of the file are deleted.
	 * @param $fileId int
	 * @param $revision int (optional)
	 * @return int number of files removed
	 */
	function deleteFile($fileId, $revision = null) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$files = array();
		if (isset($revision)) {
			$file =& $articleFileDao->getArticleFile($fileId, $revision);
			if (isset($file)) {
				$files[] = $file;
			}

		} else {
			$files =& $articleFileDao->getArticleFileRevisions($fileId);
		}

		foreach ($files as $f) {
			parent::deleteFile($this->filesDir . $this->useCaseToPath($f->getUseCase()) . '/' . $f->getFileName());
		}

		$articleFileDao->deleteArticleFileById($fileId, $revision);

		return count($files);
	}

	/**
	 * Delete the entire tree of files belonging to an article.
	 */
	function deleteArticleTree() {
		parent::rmtree($this->filesDir);
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $revision int the revision of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $revision = null, $inline = false) {
		$articleFile =& $this->getFile($fileId, $revision);
		if (isset($articleFile)) {
			$fileType = $articleFile->getFileType();
			$filePath = $this->filesDir .  $this->useCaseToPath($articleFile->getUseCase()) . '/' . $articleFile->getFileName();

			return parent::downloadFile($filePath, $fileType, $inline);

		} else {
			return false;
		}
	}

	/**
	 * View a file inline (variant of downloadFile).
	 * @see ArticleFileManager::downloadFile
	 */
	function viewFile($fileId, $revision = null) {
		$this->downloadFile($fileId, $revision, true);
	}

	/**
	 * Copies an existing file to create a review file.
	 * @param $originalFileId int the file id of the original file.
	 * @param $originalRevision int the revision of the original file.
	 * @param $destFileId int the file id of the current review file
	 * @return int the file id of the new file.
	 */
	function copyToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_USE_CASE_REVIEW, $destFileId);
	}

	/**
	 * Copies an existing file to create an editor decision file.
	 * @param $fileId int the file id of the review file.
	 * @param $revision int the revision of the review file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function copyToEditorFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_USE_CASE_EDITOR, $destFileId);
	}

	/**
	 * Copies an existing file to create a copyedit file.
	 * @param $fileId int the file id of the editor file.
	 * @param $revision int the revision of the editor file.
	 * @return int the file id of the new file.
	 */
	function copyToCopyeditFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_USE_CASE_COPYEDIT);
	}

	/**
	 * Copies an existing file to create a layout file.
	 * @param $fileId int the file id of the copyedit file.
	 * @param $revision int the revision of the copyedit file.
	 * @return int the file id of the new file.
	 */
	function copyToLayoutFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, ARTICLE_FILE_USE_CASE_LAYOUT);
	}

	/**
	 * Return path associated with a use case code.
	 * @param $useCase int
	 * @return string
	 */
	function useCaseToPath($useCase) {
		switch ($useCase) {
			case ARTICLE_FILE_USE_CASE_PUBLIC: return 'public';
			case ARTICLE_FILE_USE_CASE_SUPP: return 'supp';
			case ARTICLE_FILE_USE_CASE_NOTE: return 'note';
			case ARTICLE_FILE_USE_CASE_REVIEW: return 'submission/review';
			case ARTICLE_FILE_USE_CASE_EDITOR: return 'submission/editor';
			case ARTICLE_FILE_USE_CASE_COPYEDIT: return 'submission/copyedit';
			case ARTICLE_FILE_USE_CASE_LAYOUT: return 'submission/layout';
			case ARTICLE_FILE_USE_CASE_ATTACHMENT: return 'attachment';
			case ARTICLE_FILE_USE_CASE_SUBMISSION: default: return 'submission/original';
		}
	}

	/**
	 * Return abbreviation associated with a use case code (used for naming files).
	 * @param $useCase int
	 * @return string
	 */
	function useCaseToAbbrev($useCase) {
		switch ($useCase) {
			case ARTICLE_FILE_USE_CASE_PUBLIC: return 'PB';
			case ARTICLE_FILE_USE_CASE_SUPP: return 'SP';
			case ARTICLE_FILE_USE_CASE_NOTE: return 'NT';
			case ARTICLE_FILE_USE_CASE_REVIEW: return 'RV';
			case ARTICLE_FILE_USE_CASE_EDITOR: return 'ED';
			case ARTICLE_FILE_USE_CASE_COPYEDIT: return 'CE';
			case ARTICLE_FILE_USE_CASE_LAYOUT: return 'LE';
			case ARTICLE_FILE_USE_CASE_ATTACHMENT: return 'AT';
			case ARTICLE_FILE_USE_CASE_SUBMISSION: default: return 'SM';
		}
	}

	/**
	 * Copies an existing ArticleFile and renames it.
	 * @param $sourceFileId int
	 * @param $sourceRevision int
	 * @param $useCase int
	 * @param $destFileId int (optional)
	 */
	function copyAndRenameFile($sourceFileId, $sourceRevision, $useCase, $destFileId = null) {
		if (HookRegistry::call('ArticleFileManager::copyAndRenameFile', array(&$sourceFileId, &$sourceRevision, &$useCase, &$destFileId, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();

		$useCasePath = $this->useCaseToPath($useCase);
		$destDir = $this->filesDir . $useCasePath . '/';

		if ($destFileId != null) {
			$currentRevision = $articleFileDao->getRevisionNumber($destFileId);
			$revision = $currentRevision + 1;
		} else {
			$revision = 1;
		}

		$sourceArticleFile = $articleFileDao->getArticleFile($sourceFileId, $sourceRevision, $this->articleId);

		if (!isset($sourceArticleFile)) {
			return false;
		}

		$sourceDir = $this->filesDir .  $this->useCaseToPath($sourceArticleFile->getUseCase()) . '/';

		if ($destFileId != null) {
			$articleFile->setFileId($destFileId);
		}
		$articleFile->setArticleId($this->articleId);
		$articleFile->setSourceFileId($sourceFileId);
		$articleFile->setSourceRevision($sourceRevision);
		$articleFile->setFileName($sourceArticleFile->getFileName());
		$articleFile->setFileType($sourceArticleFile->getFileType());
		$articleFile->setFileSize($sourceArticleFile->getFileSize());
		$articleFile->setOriginalFileName($sourceArticleFile->getFileName());
		$articleFile->setUseCase($useCase);
		$articleFile->setDateUploaded(Core::getCurrentDate());
		$articleFile->setDateModified(Core::getCurrentDate());
		$articleFile->setRound($this->article->getCurrentRound()); // FIXME This field is only applicable for review files?
		$articleFile->setRevision($revision);

		$fileId = $articleFileDao->insertArticleFile($articleFile);

		// Rename the file.
		$fileExtension = $this->parseFileExtension($sourceArticleFile->getFileName());
		$newFileName = $this->articleId.'-'.$fileId.'-'.$revision.'-'.$this->useCaseToAbbrev($useCase).'.'.$fileExtension;

		if (!$this->fileExists($destDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($destDir);
		}

		copy($sourceDir.$sourceArticleFile->getFileName(), $destDir.$newFileName);

		$articleFile->setFileName($newFileName);
		$articleFileDao->updateArticleFile($articleFile);

		return $fileId;
	}

	/**
	 * PRIVATE routine to generate a dummy file. Used in handleUpload.
	 * @param $article object
	 * @return object articleFile
	 */
	function &generateDummyFile(&$article) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFile = new ArticleFile();
		$articleFile->setArticleId($article->getId());
		$articleFile->setFileName('temp');
		$articleFile->setOriginalFileName('temp');
		$articleFile->setFileType('temp');
		$articleFile->setFileSize(0);
		$articleFile->setUseCase(1);
		$articleFile->setDateUploaded(Core::getCurrentDate());
		$articleFile->setDateModified(Core::getCurrentDate());
		$articleFile->setRound(0);
		$articleFile->setRevision(1);

		$articleFile->setFileId($articleFileDao->insertArticleFile($articleFile));

		return $articleFile;
	}

	/**
	 * PRIVATE routine to remove all prior revisions of a file.
	 */
	function removePriorRevisions($fileId, $revision) {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$revisions = $articleFileDao->getArticleFileRevisions($fileId);
		foreach ($revisions as $revisionFile) {
			if ($revisionFile->getRevision() != $revision) {
				$this->deleteFile($fileId, $revisionFile->getRevision());
			}
		}
	}

	/**
	 * PRIVATE routine to generate a filename for an article file. Sets the filename
	 * field in the articleFile to the generated value.
	 * @param $articleFile The article to generate a filename for
	 * @param $useCase The type of the article (e.g. as supplied to handleUpload)
	 * @param $originalName The name of the original file
	 */
	function generateFilename(&$articleFile, $useCase, $originalName) {
		$extension = $this->parseFileExtension($originalName);
		$newFileName = $articleFile->getArticleId().'-'.$articleFile->getFileId().'-'.$articleFile->getRevision().'-'.$this->useCaseToAbbrev($useCase).'.'.$extension;
		$articleFile->setFileName($newFileName);
		return $newFileName;
	}

	/**
	 * PRIVATE routine to upload the file and add it to the database.
	 * @param $fileName string index into the $_FILES array
	 * @param $useCase int identifying use case (defined in ArticleFile)
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleUpload($fileName, $useCase, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('ArticleFileManager::handleUpload', array(&$fileName, &$useCase, &$fileId, &$overwrite, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$useCasePath = $this->useCaseToPath($useCase);
		$dir = $this->filesDir . $useCasePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$articleFile =& $this->generateDummyFile($this->article);
		} else {
			$dummyFile = false;
			$articleFile = new ArticleFile();
			$articleFile->setRevision($articleFileDao->getRevisionNumber($fileId)+1);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileId($fileId);
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
		}

		$articleFile->setFileType($_FILES[$fileName]['type']);
		$articleFile->setFileSize($_FILES[$fileName]['size']);
		$articleFile->setOriginalFileName(ArticleFileManager::truncateFileName($_FILES[$fileName]['name'], 127));
		$articleFile->setUseCase($useCase);
		$articleFile->setRound($this->article->getCurrentRound());

		$newFileName = $this->generateFilename($articleFile, $useCase, $this->getUploadedFileName($fileName));

		if (!$this->uploadFile($fileName, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());

			return false;
		}

		if ($dummyFile) $articleFileDao->updateArticleFile($articleFile);
		else $articleFileDao->insertArticleFile($articleFile);

		if ($overwrite) $this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}

	/**
	 * PRIVATE routine to write an article file and add it to the database.
	 * @param $fileName original filename of the file
	 * @param $contents string contents of the file to write
	 * @param $mimeType string the mime type of the file
	 * @param $useCase string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleWrite($fileName, &$contents, $mimeType, $useCase, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('ArticleFileManager::handleWrite', array(&$fileName, &$contents, &$mimeType, &$fileId, &$overwrite, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$useCasePath = $this->useCaseToPath($useCase);
		$dir = $this->filesDir . $useCasePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$articleFile =& $this->generateDummyFile($this->article);
		} else {
			$dummyFile = false;
			$articleFile = new ArticleFile();
			$articleFile->setRevision($articleFileDao->getRevisionNumber($fileId)+1);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileId($fileId);
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
		}

		$articleFile->setFileType($mimeType);
		$articleFile->setFileSize(strlen($contents));
		$articleFile->setOriginalFileName(ArticleFileManager::truncateFileName($fileName, 127));
		$articleFile->setUseCase($useCase);
		$articleFile->setRound($this->article->getCurrentRound());

		$newFileName = $this->generateFilename($articleFile, $useCase, $fileName);

		if (!$this->writeFile($dir.$newFileName, $contents)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());

			return false;
		}

		if ($dummyFile) $articleFileDao->updateArticleFile($articleFile);
		else $articleFileDao->insertArticleFile($articleFile);

		if ($overwrite) $this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}

	/**
	 * PRIVATE routine to copy an article file and add it to the database.
	 * @param $url original filename/url of the file
	 * @param $mimeType string the mime type of the file
	 * @param $useCase string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleCopy($url, $mimeType, $useCase, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('ArticleFileManager::handleCopy', array(&$url, &$mimeType, &$useCase, &$fileId, &$overwrite, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$useCasePath = $this->useCaseToPath($useCase);
		$dir = $this->filesDir . $useCasePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$articleFile =& $this->generateDummyFile($this->article);
		} else {
			$dummyFile = false;
			$articleFile = new ArticleFile();
			$articleFile->setRevision($articleFileDao->getRevisionNumber($fileId)+1);
			$articleFile->setArticleId($this->articleId);
			$articleFile->setFileId($fileId);
			$articleFile->setDateUploaded(Core::getCurrentDate());
			$articleFile->setDateModified(Core::getCurrentDate());
		}

		$articleFile->setFileType($mimeType);
		$articleFile->setOriginalFileName(ArticleFileManager::truncateFileName(basename($url), 127));
		$articleFile->setUseCase($useCase);
		$articleFile->setRound($this->article->getCurrentRound());

		$newFileName = $this->generateFilename($articleFile, $useCase, $articleFile->getOriginalFileName());

		if (!$this->copyFile($url, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());

			return false;
		}

		$articleFile->setFileSize(filesize($dir.$newFileName));

		if ($dummyFile) $articleFileDao->updateArticleFile($articleFile);
		else $articleFileDao->insertArticleFile($articleFile);

		if ($overwrite) $this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}

	/**
	 * Copy a temporary file to an article file.
	 * @param TemporaryFile
	 * @return int the file ID (false if upload failed)
	 */
	function temporaryFileToArticleFile(&$temporaryFile, $useCase, $assocId = null) {
		if (HookRegistry::call('ArticleFileManager::temporaryFileToArticleFile', array(&$temporaryFile, &$useCase, &$assocId, &$result))) return $result;

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$useCasePath = $this->useCaseToPath($useCase);
		$dir = $this->filesDir . $useCasePath . '/';

		$articleFile =& $this->generateDummyFile($this->article);
		$articleFile->setFileType($temporaryFile->getFileType());
		$articleFile->setOriginalFileName($temporaryFile->getOriginalFileName());
		$articleFile->setUseCase($useCase);
		$articleFile->setRound($this->article->getCurrentRound());
		$articleFile->setAssocId($assocId);

		$newFileName = $this->generateFilename($articleFile, $useCase, $articleFile->getOriginalFileName());

		if (!$this->copyFile($temporaryFile->getFilePath(), $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$articleFileDao->deleteArticleFileById($articleFile->getFileId());

			return false;
		}

		$articleFile->setFileSize(filesize($dir.$newFileName));
		$articleFileDao->updateArticleFile($articleFile);
		$this->removePriorRevisions($articleFile->getFileId(), $articleFile->getRevision());

		return $articleFile->getFileId();
	}
}

?>
