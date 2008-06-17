<?php
/**
 * ************************************************************
 *  Copyright notice
 *
 *  (c) Krystian Szymukowicz (typo3@prolabium.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 *
 */

/**
 * Extended cache managment for tt_news extension
 *
 * $Id: ttnewscache_tcemainproc $
 *
 * @author    Krystian Szymukowicz <typo3@prolabium.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   60: class tx_ttnewscache_tcemainproc
 *   79:     function init()
 *  107:     function processDatamap_preProcessFieldArray($incomingArray, $table, $id, &$thisRef)
 *  153:     function processCmdmap_preProcess ($command, $table, $id, $value, &$thisRef)
 *  206:     function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$thisRef)
 *  673:     function writeOutputAsHTMLReport ($newsData, $viewsData, $fieldArray, &$thisRef, $processingTime, $reasonNotToClear)
 *  809:     function getTTnewsViewsDataFromTTcontent($viewsTypes)
 *  855:     function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit=0)
 *  891:     function getArrayOfTTnewsCategories($id)
 *  905:     function getArrayOfTTnewsRelated($id)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Extended cache managment for tt_news extension
 *
 * @author    Krystian Szymukowicz <typo3@prolabium.com>
 */
class tx_ttnewscache_tcemainproc {

	var $extKey = 'ttnewscache';
	var $initialized = 0;

	var $debug = 0;
	var $htmlReport = 1;
	var $clearCache = 0;
	var $automaticViewsSearch = 1;
	var $viewsCacheFileName = 'views_cache';
	var $reportFileName = 'report.html';

	var $newsRelatedBeforeUpdate;
	var $newsCategoriesBeforeUpdate;

	/**
	 * Initialize some configurations settings.
	 *
	 * @return	void
	 */
	function init() {

		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ttnewscache']);

		if (isset($confArr['debug'])) $this->debug = $confArr['debug'];
		if (isset($confArr['clearCache'])) $this->clearCache = $confArr['clearCache'];
		if (isset($confArr['htmlReport'])) $this->htmlReport = $confArr['htmlReport'];
		if (isset($confArr['automaticViewsSearch'])) $this->automaticViewsSearch = $confArr['automaticViewsSearch'];
		if (isset($confArr['viewsCacheFileName']) && strlen($confArr['viewsCacheFileName']) > 0) $this->viewsCacheFileName = $confArr['viewsCacheFileName'];
		if (isset($confArr['reportFileName']) && strlen($confArr['reportFileName']) > 0) $this->reportFileName = $confArr['reportFileName'];

		if ($this->debug || $this->htmlReport) {
			require_once(PATH_t3lib.'class.t3lib_timetrack.php');
			$this->TT = new t3lib_timeTrack;
			$this->TT->start();
		}
		$this->initialized = 1;
	}

	/**
	 * TCEmain hook used to get old values of 'categories' and 'related' in case of 'update' of the record.
	 *
	 * @param	array		Form fields.
	 * @param	array		Table the record belongs to.
	 * @param	integer		Id of the record.
	 * @param	object		Parent object.
	 * @return	void
	 */
	function processDatamap_preProcessFieldArray($incomingArray, $table, $id, &$thisRef) {

		if ($table == 'tt_news') {

			if(!$this->initialized) $this->init();

			if ($this->debug){
				$startTime = $this->TT->mtime();
				t3lib_div::devLog('[BF][START] (Get values before update)  processDatamap_preProcessFieldArray', $this->extKey);
			}

			// categories before update
			$this->newsCategoriesBeforeUpdate = $this->getArrayOfTTnewsCategories($id);
			if (count($this->newsCategoriesBeforeUpdate)){
				$this->newsCategoriesBeforeUpdate = implode(',',$this->newsCategoriesBeforeUpdate);
				if ($this->debug) t3lib_div::devLog('[BF] newsCategoriesBeforeUpdate: '.$this->newsCategoriesBeforeUpdate,$this->extKey);
			} else {
				if ($this->debug) t3lib_div::devLog('[BF] newsCategoriesBeforeUpdate: no categories',$this->extKey);
			}

			// related before update
			$this->newsRelatedBeforeUpdate = $this->getArrayOfTTnewsRelated($id);
			if (count($this->newsRelatedBeforeUpdate)) {
				$this->newsRelatedBeforeUpdate = implode(',',$this->newsRelatedBeforeUpdate);
				if ($this->debug) t3lib_div::devLog('[BF] newRelatedBeforeUpdate: '.$this->newsRelatedBeforeUpdate,$this->extKey);
			} else {
				if ($this->debug) t3lib_div::devLog('[BF] newsRelatedBeforeUpdate: no realated',$this->extKey);
			}

			if ($this->debug) {
				$endTime = $this->TT->mtime();
				t3lib_div::devLog('[BF][END] processDatamap_preProcessFieldArray (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
			}
		}
	}

	/**
	 * TCEmain hook used to take care of 'delete' record.
	 *
	 * @param	string		Command from tcemain.
	 * @param	string		Table the comman process on.
	 * @param	integer		Id of the record.
	 * @param	string		Value for the command.
	 * @param	object		Parent object.
	 * @return	void
	 */
	function processCmdmap_preProcess ($command, $table, $id, $value, &$thisRef) {

		if ($command == 'delete' && $table == 'tt_news') {

			if(!$this->initialized) $this->init();

			if ($this->debug) {
				$startTime = $this->TT->mtime();
				t3lib_div::devLog('[DL][START] (Delete) processCmdmap_preProcess',$this->extKey);
			}
			// recreate structure $thisRef->datamap['tt_news'][$id] to use in $this->processDatamap_afterDatabaseOperations

			// 1. category
			$thisRef->datamap['tt_news'][$id]['category'] = $this->getArrayOfTTnewsCategories($id);
			if (is_array($thisRef->datamap['tt_news'][$id]['category'])) $thisRef->datamap['tt_news'][$id]['category'] = implode(',', $this->getArrayOfTTnewsCategories($id));
			if ($this->debug) t3lib_div::devLog('[DL] Prepare for delete - newsCategories: '.$thisRef->datamap['tt_news'][$id]['category'],$this->extKey);

			// 2. related
			$thisRef->datamap['tt_news'][$id]['related'] = $this->getArrayOfTTnewsRelated($id);
			if (is_array($thisRef->datamap['tt_news'][$id]['related'])) {
				$fieldArray['related'] = 1;
				$thisRef->datamap['tt_news'][$id]['related'] = implode(',', $thisRef->datamap['tt_news'][$id]['related']);
				if ($this->debug) t3lib_div::devLog('[DL] Prepare for delete - newsRelated: '.$thisRef->datamap['tt_news'][$id]['related'],$this->extKey);
			}

			// 3. pid, title
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid, title, hidden', 'tt_news', 'uid='.intval($id));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$thisRef->checkValue_currentRecord['pid'] = $row['pid'];
				$thisRef->datamap['tt_news'][$id]['title'] = $row['title'];
				$thisRef->datamap['tt_news'][$id]['hidden'] = $row['hidden'];
			}

			if ($this->debug){
				$endTime = $this->TT->mtime();
				t3lib_div::devLog('[DL][END] processCmdmap_preProcess  (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
				t3lib_div::devLog('[DL] call processDatamap_afterDatabaseOperations', $this->extKey);
			}

			$this->processDatamap_afterDatabaseOperations($command, $table, $id, $fieldArray, $thisRef);
		}
	}

	/**
	 * TCEmain hook used to take care of 'new', 'update' and 'delete' record. 'delete' is kind "fake" - is called from processCmdmap_preProcess.
	 *
	 * @param	string		Operation status. Can be 'new','update'. Call from processCmdmap_preProcess add third status 'delete'.
	 * @param	string		Table the operation was processed on.
	 * @param	integer		Id of the record.
	 * @param	array		Fields that have been changed.
	 * @param	object		Parent object.
	 * @return	void
	 */
	function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$thisRef) {


		switch ($table) {
			case 'pages':
				// only if TSconfig updated
				// maybe the extension configuration was changed so clear the file with views data

				if (isset($fieldArray['TSconfig'])) {

					if(!$this->initialized) $this->init();
					$viewsCacheFile = PATH_site .'typo3temp/ttnewscache/'.$this->viewsCacheFileName;

					if (file_exists($viewsCacheFile)) @unlink($viewsCacheFile);
					if ($this->debug) t3lib_div::devLog('Change on PageTS config (page id:'.$id.') so clear the cache file with views data at: '.$viewsCacheFile, 'ttnewscache');
				}
				break;

			case 'tt_content':
				// only if tt_content with tt_news plugin inside updated
				// the plugin was changed so clear the file with views data

				if ($thisRef->checkValue_currentRecord['list_type'] == 9) {

					if(!$this->initialized) $this->init();
					$viewsCacheFile = PATH_site .'typo3temp/ttnewscache/'.$this->viewsCacheFileName;

					if (file_exists($viewsCacheFile)) @unlink($viewsCacheFile);
					if ($this->debug) t3lib_div::devLog('Change on tt_content with tt_news plugin inside so clear the cache file with views data at: '.$viewsCacheFile, 'ttnewscache',0,$fieldArray);
				}
				break;

			case 'tt_news':

				// CATEGORY CHANGE BUG? [START]
				// Problem: if the number of selected categories does NOT change then $status==update but $fieldArray is empty
				// Solution: check if there really was category update

				if(!$this->initialized) $this->init();
				$viewsCacheFile = PATH_site .'typo3temp/ttnewscache/'.$this->viewsCacheFileName;

				$newsCategories = $thisRef->datamap['tt_news'][$id]['category'];
				$newsCategories = t3lib_div::rm_endcomma($newsCategories);

				if ($status == 'update') {
					$a = explode(',',$newsCategories);
					$b = explode(',',$this->newsCategoriesBeforeUpdate);
					sort($a);
					sort($b);
					if ($a != $b)$fieldArray['category'] = 1;
				}
				// CATEGORY CHANGE BUG [END]

				// RELATED CHANGE BUG? [START]
				// Problem: if the number of selected related does NOT change then $status==update but $fieldArray is empty
				// Solution: check if there really was related update
				$newsRelated = $thisRef->datamap['tt_news'][$id]['related'];
				$newsRelated = t3lib_div::rm_endcomma($newsRelated);

				if ($status == 'update') {
					$a = explode(',',$newsRelated);
					$b = explode(',',$this->newsRelatedBeforeUpdate);
					sort($a);
					sort($b);
					if ($a != $b)$fieldArray['related'] = 1;
				}
				// RELATED CHANGE BUG [END]

				// first check main conditions
				if ( (!$thisRef->datamap['tt_news'][$id]['hidden'] && $status != 'update')  || ($fieldArray['hidden'] && $status == 'update') || (count($fieldArray) && $status == 'update' && $thisRef->datamap['tt_news'][$id]['hidden'] != 1)) {

					/////////////
					//
					// [TS] READ PAGE TS
					//

					if ($this->debug){
						$this->TT->start();
						$startTime = $this->TT->mtime();
						t3lib_div::devLog('[TS][START] (PageTSConfig)  /TT time reset/', $this->extKey);
					}

					$pageId = $thisRef->checkValue_currentRecord['pid'];
					$pageTS = t3lib_beFunc::getPagesTSconfig($pageId);
					$pageTSparams = t3lib_BEfunc::implodeTSParams($pageTS);

					if ($this->debug){
						$endTime = $this->TT->mtime();
						t3lib_div::devLog('[TS][END] (PageTSConfig)  (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
					}


					/////////////
					//
					// [VD] VIEWS DATA - build views data array
					//
					if ($this->automaticViewsSearch) {

						if ($this->debug){
							$startTime = $this->TT->mtime();
							t3lib_div::devLog('[VD][START] (Automatic Views Data) ', $this->extKey);
						}
						if (file_exists($viewsCacheFile)) {
							$viewsData = unserialize(t3lib_div::getURL($viewsCacheFile));
						} else {
							//first file generation or typo3temp was cleaned
							$viewsTypes = t3lib_div::trimExplode(',', $pageTSparams['tx_ttnewscache.viewsTypes'], true);
							$viewsData = $this->getTTnewsViewsDataFromTTcontent($viewsTypes);
							$fileStatus = t3lib_div::writeFileToTypo3tempDir($viewsCacheFile, serialize($viewsData));
							if ($this->debug && $fileStatus)t3lib_div::devLog('Error on automatic views generation: '.$fileStatus, $this->extKey, 3);
						}
						//merge manually added views (from PageTS) with automatically detected from tt_content
						if(is_array($viewsData)){
							$viewsData = t3lib_div::array_merge_recursive_overrule($viewsData, $pageTS['tx_ttnewscache.']['views.']);
						}else{
							t3lib_div::devLog('[VD] Seems like no tt_news plugins in content elements.', $this->extKey, 2);
							$viewsData = $pageTS['tx_ttnewscache.']['views.'];
						}

						if ($this->debug){
							$endTime = $this->TT->mtime();
							t3lib_div::devLog('[VD][END] (Automatic Views Data) (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
						}
					} else {
						// get only manually added views (from PageTS)
						$viewsData = $pageTS['tx_ttnewscache.']['views.'];
					}


					if ($status == 'update') $updatedFields = array_keys($fieldArray);


					/////////////
					//
					// [BI] BIDIRECTIONAL - check if bidirectional relations are on - add related before update and work on join
					//
					if ($pageTSparams['tx_ttnewscache.bidirectionalRelations']) {

						if (isset($pageTSparams['tx_ttnewscache.views.single.relatedClearFields'])) {
							$relatedClearFields = $pageTSparams['tx_ttnewscache.views.single.relatedClearFields'];
						}

						$clearRelated = 0;
						if($status == 'update'){
							foreach($updatedFields as $updatedField) {
								if (t3lib_div::inList($relatedClearFields,$updatedField)) {
									$clearRelated = 1;
								}
							}
						}
						if ($pageTSparams['tx_ttnewscache.bidirectionalRelationsCatMatch']) {
							if ($clearRelated || $status != 'update') {

								if ($this->debug){
									$startTime = $this->TT->mtime();
									t3lib_div::devLog('[BI][START] (BidirectionalRelationsCatMatch)', $this->extKey);
								}

								if (isset($thisRef->datamap['tt_news'][$id]['related'])) $related = $thisRef->datamap['tt_news'][$id]['related'];
								if (strlen($this->newsRelatedBeforeUpdate)) $related .= ','. $this->newsRelatedBeforeUpdate;


								if (strlen($related)) {
									$related = t3lib_div::uniqueList($related);
									if ($this->debug) t3lib_div::devLog('[BI] Realted: '.$related, $this->extKey);
									$related = explode(',',$related);

									foreach($related as $rel) {
										$relRecord = t3lib_BEfunc::splitTable_Uid($rel);
										if ($relRecord[0] == 'tt_news') {
											$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_foreign','tt_news','tt_news_cat_mm','tt_news_cat',' AND uid_local='.intval($relRecord[1]));
											$newsRelations[] = $relRecord;

											$newsRelCats = array();
											while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
												$newsRelCats[] = $row['uid_foreign'];
											}
											$newsData['related-'.$relRecord[1]]['uid'] = intval($relRecord[1]);
											$newsData['related-'.$relRecord[1]]['categories'] = implode(',',$newsRelCats);
										}
									}
								}
								if ($this->debug){
									$endTime = $this->TT->mtime();
									t3lib_div::devLog('[BI][END] (BidirectionalRelationsCatMatch) (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
								}
							}
						}
					}

					//////////////////
					//
					// [CC] CATEGORY CHECK - get only those views that match the news categories
					//
					if ($this->debug){
						$startTime = $this->TT->mtime();
						t3lib_div::devLog('[CC][START] (Category Check)', $this->extKey);
					}

					if ($status == 'new') $id = $thisRef->substNEWwithIDs[$id];

					$newsData['main-new']['categories'] = $newsCategories;
					$newsData['main-new']['uid'] = $id;

					if (isset($fieldArray['category']) && $status == 'update') {
						// category updated - we need to clear cache fot views with old and news categories
						$newsData['main-old']['categories'] = $this->newsCategoriesBeforeUpdate;
						$newsData['main-old']['uid'] = $id;
						if ($this->debug && isset($fieldArray['category']))t3lib_div::devLog('[CC] Category was updated. Need 2 loops of CC for views that needs to be cleared. One for "new categories", one for "old categories".', $this->extKey);
					}

					// finally check category match - we can check cat match for 3 type of news:
					// main-new  - check for views that match new categories
					// main-old  - check for views that match old categories
					// related-xx  - this one is checked only if bidirectionalRelationCatMatch is on. It allows to figure out in what pid can the related news be. This is needed in those methods of clearing cache that search for markers in HTML code of page.

					foreach($newsData as $typeOfNews => $newsInfo) {

						if ($this->debug) {
							t3lib_div::devLog('[CC] Loop with "'.$typeOfNews.'" START.', $this->extKey);
							t3lib_div::devLog('[CC] News categories: '. ($newsInfo['categories']? $newsInfo['categories'] :' no categories!'), $this->extKey);
						}

						foreach(array_keys($viewsData) as $typeOfView) {
							$typeOfView = substr($typeOfView,0,-1); //no dot at the end $typeOfView

							// news type related-xx is only checked for SINGLE views
							if (preg_match('/related-.*/',$typeOfNews) && $typeOfView != 'single') continue;

							if(is_array($viewsData[$typeOfView.'.']['data.'])){
								foreach($viewsData[$typeOfView.'.']['data.'] as $uid => $view) {
									$uid = substr($uid, 0, -1);

									$viewCategories = t3lib_div::trimExplode(',',$view['categorySelection'],true);

									if ($newsInfo['categories']) {
										$commonCategories = array();
										foreach($viewCategories as $viewCategory) {
											if (t3lib_div::inList($newsInfo['categories'], $viewCategory)) {
												$commonCategories[] = $viewCategory;
											}
										}
										if (isset($commonCategories)) {$commonCategoriesCommaSeparated = implode(',',$commonCategories);}
									}

									$isMatch = 0;
									switch ($view['categoryMode']) {
										case 0:
											// SHOW ALWAYS
											$viewsData[$typeOfView.'.']['data.'][$uid.'.']['categoryMatch'] = '1';
											if ($this->debug) t3lib_div::devLog('[CC] Add "'.$typeOfView.'" (pid:'.$view['pid'].', uid:'.$uid.' categoryMode: SHOW ALL)', $this->extKey);
											$isMatch = 1;
											break;

										case 1:
											// OR - at least one category from view must belong to record categories
											if (count($commonCategories) > 0) {
												$viewsData[$typeOfView.'.']['data.'][$uid.'.']['categoryMatch'] = '1';
												if ($this->debug) t3lib_div::devLog('[CC] Add "'.$typeOfView.'" (pid:'.$view['pid'].', uid:'.$uid.' categoryMode: OR, this view cats:'. $view['categorySelection'] .', common cats: '. $commonCategoriesCommaSeparated .')', $this->extKey);
												$isMatch = 1;
											}
											break;

										case 2:
											// AND - all categories from view must belong to record categories
											if (count($commonCategories) == count($viewCategories)) {
												$viewsData[$typeOfView.'.']['data.'][$uid.'.']['categoryMatch'] = '1';
												if ($this->debug) t3lib_div::devLog('[CC] Add "'.$typeOfView.'" (pid:'.$view['pid'].', uid:'.$uid.' categoryMode: AND, this view cats:'. $view['categorySelection'] .', common cats: '. $commonCategoriesCommaSeparated .')', $this->extKey);
												$isMatch = 1;
											}
											break;

										case -1:
											// AND (NOT SHOW) - do not show news at this view if the news has all views categories

											if ( !(count($commonCategories) == count($viewCategories)) ) {
												$viewsData[$typeOfView.'.']['data.'][$uid.'.']['categoryMatch'] = '1';
												if ($this->debug) t3lib_div::devLog('[CC] Add "'.$typeOfView.'" (pid:'.$view['pid'].', uid:'.$uid.' categoryMode: NOT AND/OR, this view cats:'. $view['categorySelection'] .', common cats: '. $commonCategoriesCommaSeparated .')', $this->extKey);
												$isMatch = 1;
											}
											break;

										case -2:
											// OR (NOT SHOW) - do not show news at this view if the news has at lest one of the view categories

											if ( count($commonCategories) == 0 ) {
												$viewsData[$typeOfView.'.']['data.'][$uid.'.']['categoryMatch'] = '1';
												if ($this->debug) t3lib_div::devLog('[CC] Add "'.$typeOfView.'" (pid:'.$view['pid'].', uid:'.$uid.' categoryMode: NOT AND/OR, this view cats:'. $view['categorySelection'] .', common cats: '. $commonCategoriesCommaSeparated .')', $this->extKey);
												$isMatch = 1;
											}
											break;

										default:
											if ($this->debug) t3lib_div::devLog('CategoryMode is not known.', $this->extKey,3);
									}

									if ($isMatch && $typeOfView == 'single' ) {
										$viewsData[$typeOfView.'.']['data.'][$uid.'.']['clearUids'] .= ','.$newsInfo['uid'];
										$viewsData[$typeOfView.'.']['data.'][$uid.'.']['clearUids'] = t3lib_div::uniqueList($viewsData[$typeOfView.'.']['data.'][$uid.'.']['clearUids']);
									}

									$viewsCategoryMatchCounter += $isMatch;
								}
							}
							if ($this->debug) t3lib_div::devLog('[CC] View "'.$typeOfView.'" DONE!', $this->extKey);
						}
						if ($this->debug && isset($fieldArray['category'])) t3lib_div::devLog('[CC] Loop with "'.$typeOfNews.'" END.', $this->extKey);
					}
					if ($this->debug) {
						$endTime = $this->TT->mtime();
						t3lib_div::devLog('[CC][END] (Category Check) (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
					}


					////////////////////////
					//
					// [AS] ACTION SET - work only on views that match the categories (['categoryMatch'] = '1')
					//
					if ($viewsCategoryMatchCounter) {
						if ($this->debug) {
							$startTime = $this->TT->mtime();
							t3lib_div::devLog('[AS][START] Action Set - operates only on views accepted by [CC] (Category Check).', $this->extKey);
						}
						switch ($status) {
							case 'new':
							case 'delete':
								foreach(array_keys($viewsData) as $typeOfView) {
									$typeOfView = substr($typeOfView,0,-1); //no dot at the end $typeOfView

									// if the news was hidden and is now deleted then do nothing
									if($status == 'delete' && $thisRef->datamap['tt_news'][$id]['hidden']) continue;

									if(is_array($viewsData[$typeOfView.'.']['data.'])){
										foreach($viewsData[$typeOfView.'.']['data.'] as $uid => $view) {
											$uid = substr($uid, 0 ,-1);
											if ($view['categoryMatch'] == 1) {
												$viewsData[$typeOfView.'.']['data.'][$uid.'.']['action'] = 'view-marker';
												if ($this->debug) t3lib_div::devLog('[AS] View "'.$typeOfView.'" - action "view-marker" added.', $this->extKey);
											}
										}
									}
								}
								break;

							case 'update':
								if ($this->debug) t3lib_div::devLog('[AS] Updated fields: '. implode(',',array_keys($fieldArray)), $this->extKey);
								// loop through all views and figure out which views needs to be updated because of field update
								foreach(array_keys($viewsData) as $typeOfView) {
									$typeOfView = substr($typeOfView,0,-1); //no dot at the end
									if(is_array($viewsData[$typeOfView.'.']['data.'])){
										foreach($viewsData[$typeOfView.'.']['data.'] as $uid => $view) {
											$uid = substr($uid, 0 ,-1);

											// check only those views that were check as matched in Category Check
											if ($viewsData[$typeOfView.'.']['data.'][$uid.'.']['categoryMatch'] == 1) {

												// each view can have individual list of fields which changes will require check
												if (isset($pageTSparams['tx_ttnewscache.views.'.$typeOfView.'.data.'.$uid.'.selectiveViewClearFields'])) {
													$selectiveViewClearFields = $pageTSparams['tx_ttnewscache.views.'.$typeOfView.'.data.'.$uid.'.selectiveViewClearFields'];
												} else {
													$selectiveViewClearFields = $pageTSparams['tx_ttnewscache.views.'.$typeOfView.'.selectiveViewClearFields'];
												}

												if (isset($pageTSparams['tx_ttnewscache.views.'.$typeOfView.'.data.'.$uid.'.wholeViewClearFields'])) {
													$wholeViewClearFields = $pageTSparams['tx_ttnewscache.views.'.$typeOfView.'.data.'.$uid.'.wholeViewClearFields'];
												} else {
													$wholeViewClearFields = $pageTSparams['tx_ttnewscache.views.'.$typeOfView.'.wholeViewClearFields'];
												}

												//single view do not need to check for update - just give it an action "viewMarker"
												if (preg_match('/^single.*/i',$typeOfView)) {
													// no matter of fields changed single view cache will be cleared
													$viewsData[$typeOfView.'.']['data.'][$uid.'.']['action'] = 'view-marker';
													if ($this->debug) t3lib_div::devLog('[AS] Set "view marker" action on that view because it is SINGLE view (view: '.$typeOfView.', uid='. $uid .', pid='. $view['pid'] .')',$this->extKey);
												} else {
													$actionRecordMarker = $actionViewMarker = 0;

													// viewMarker first
													foreach($updatedFields as $updatedField) {
														if (t3lib_div::inList($wholeViewClearFields,$updatedField)) {
															$actionViewMarker = 1;
															$viewsData[$typeOfView.'.']['data.'][$uid.'.']['action'] = 'view-marker';
															if ($this->debug) t3lib_div::devLog('[AS] Set "view marker" action on that view because it was update of field "'. $updatedField .'" that belongs to wholeViewClearFields (view: '.$typeOfView.', uid='. $uid .', pid='. $view['pid'] .')',$this->extKey);
															break;
														}
													}
													// recordMarker then if there was no match on viewMarker field
													if (!$actionViewMarker) {
														foreach($updatedFields as $updatedField) {
															if (t3lib_div::inList($selectiveViewClearFields,$updatedField)) {
																$actionRecordMarker = 1;
																$viewsData[$typeOfView.'.']['data.'][$uid.'.']['action'] = 'record-marker';
																if ($this->debug) t3lib_div::devLog('[AS] Set "record marker" action on that view because it was update of field "'. $updatedField .'" that belongs to selectiveViewClearFields (view: '.$typeOfView.', uid='. $uid .', pid='. $view['pid'] .')',$this->extKey);
																break;
															}
														}
													}
													if (!$actionViewMarker && !$actionRecordMarker) {
														//if update was on any other field than (selectiveViewClearFields and wholeViewClearFields) - then do nothing with this view
														if ($this->debug) t3lib_div::devLog('[AS] Update of field "'. $updatedField .'" that belongs neither to wholeViewClearFields or selectiveViewClearFields. (view: '.$typeOfView.', uid='. $uid .', pid='. $view['pid'] .')',$this->extKey);
													}
												}
											}
										}
									}
								}
								if ($this->debug) {
									$endTime = $this->TT->mtime();
									t3lib_div::devLog('[AS][END] Action Set  (time: '. $endTime .' - '. $startTime . ' = ' . ($endTime - $startTime) .'ms)', $this->extKey);
								}
								//debug($viewsData['single.']['data.'],'single');
								//debug($viewsData['list.']['data.'],'list');
								//debug($viewsData['latest.']['data.'],'latest');
						}//switch new,update

						//hook for clear cache functions
						if ($this->clearCache) {
							// set the order the extensions are executed - if anyone has better idea how to solve it - plz share
							// extension can set priority by defining variable ttnewscache_priority in ext_conf_template.txt
							if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ttnewscache']['clearCache'])) {

								foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ttnewscache']['clearCache'] as $ext) {

									preg_match('/EXT:([a-z0-9_]+)\//',$ext,$matches);

									if(t3lib_extMgm::isLoaded($matches[1])){

										if($this->debug) t3lib_div::devLog('Hook Priority Check: extension "'.$matches[1].'" detected.', $this->extKey);
										$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$matches[1]]);

										//if extension has no priority set it to 1 - will be executed as first
										if(!isset($confArr['ttnewscache_priority'])) $confArr['ttnewscache_priority'] = 1;

										$extPriority[] = $confArr['ttnewscache_priority'];
										$extClass[] = $ext;

									}else{
										if($this->debug) t3lib_div::devLog('Hook Priority Check: extension "'.$matches[1].'" not found. Ext:('.$ext.')', $this->extKey, 2);
									}
								}
								if(is_array($extPriority)){
									$prioritizedClasses = array ($extPriority,$extClass);
									array_multisort ($prioritizedClasses[0],$prioritizedClasses[1]);
								}
							}
							//order set - now call the functions if any
							if (is_array($prioritizedClasses[1])) {
								$newsData = array(
														'status' => $status,
														'id' => $id,
														'relations' => $thisRef->datamap['tt_news'][$id]['related'],
														'categories' => $newsCategories,
														'relationsBefore' => $this->newsRelatedBeforeUpdate,
														'categoriesBefore' => $this->newsCategoriesBeforeUpdate,
														'clearRelated' => $clearRelated
								);

								$params = array(
														'newsData' => $newsData,
														'viewsData' => $viewsData,
														'pObj' => &$thisRef,
								);
								foreach($prioritizedClasses[1] as $func) {
									t3lib_div::callUserFunction($func, $params, $this, '');
								}
							}
						}
					} else {//viewsCategoryMatchCounter - no match on view so no cache clear
						t3lib_div::devLog('No need to cache clear. Reason: no views that match the categories of the news.', $this->extKey);
					}
				} else {
					//news is hidden or there was no update so *no* cache clear
					if ($this->debug) {
						if ($status == 'delete' && $thisRef->datamap['tt_news'][$id]['hidden']) {
							$reasonNotToClear = 'hidden record was deleted';
						}
						if ($status == 'new' && $fieldArray['hidden']) {
							$reasonNotToClear = 'hidden record was created';
						}
						if ($status == 'update' && $thisRef->datamap['tt_news'][$id]['hidden']) {
							$reasonNotToClear = 'hidden record was updated';
						}
						if ($status == 'update' && !count($fieldArray)) {
							$reasonNotToClear = 'no field was updateded';
						}
						t3lib_div::devLog('No need to cache clear. Reason: '. $reasonNotToClear . '.', $this->extKey);
					}
				}

				if ($this->htmlReport && !$badViewsParams) {
					$newsData = array('status' => $status, 'id' => $id, 'relations' => $newsRelations, 'categories' => $newsCategories , 'viewsCategoryMatchCounter' => $viewsCategoryMatchCounter);
					$this->writeOutputAsHTMLReport($newsData, $viewsData, $fieldArray, &$thisRef, $this->TT->mtime(), $reasonNotToClear);
				}

				if ($this->debug) {
					t3lib_div::devLog('function processDatamap_afterDatabaseOperations [END]', $this->extKey);
					t3lib_div::devLog('function processDatamap_afterDatabaseOperations - processing time: '.$this->TT->mtime() .'ms', $this->extKey, 0);
				}
		}//end of switch $table pages/tt_content/tt_news
	}

	/**
	 * Write tt_news cache analysis to file in typo3temp/ttnewscache/report.html. Can be turn on in EM config.
	 *
	 * @param	array		Assoc array with information about the news that was modified
	 * @param	array		Array with information about the views including information which one match the categories, should be cleared etc.
	 * @param	array		Fields modified.
	 * @param	object		Reference to the calling object.
	 * @param	integer		Time processing in miliseconds.
	 * @param	string		Resaon the news change will not clear the cache.
	 * @return	void
	 */
	function writeOutputAsHTMLReport ($newsData, $viewsData, $fieldArray, &$thisRef, $processingTime, $reasonNotToClear) {

		$content .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
		$content .= '<html>';
		$content .= '<meta content="text/html; charset=utf-8" http-equiv="content-type" />';
		$content .= '<title>tt_news cache analyze summary</title><body>';
		$content .= '<h1>tt_news cache analyze summary</h1>';
		$content .= '<p>Creation date: '.strftime("%Y-%m-%d %H:%M:%S").'<br />';
		$content .= 'Processing time: '. $processingTime .'ms</p>';

		//get all catogries names
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title','tt_news_cat',' deleted=0');

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$newsCategoriesNames[$row['uid']] =  $row['title'];
		}

		$content .= '<h2>News Information</h2>';
		$content .= '<ul>';
		$content .= '<li><b>title</b>: '.$thisRef->datamap['tt_news'][$newsData['id']]['title'].'</li>';
		$content .= '<li><b>id</b>: '.$newsData['id'].'</li>';

		if ($newsData['status'] == 'update') {
			$content .= '<li><b>status:</b> update</li>';
			$content .= '<li><b>updated fields</b>: '. implode(',',array_keys($fieldArray)) .'</li>';
		} else {
			$content .= '<li><b>status:</b> '. $newsData['status'] .'</li>';
		}

		$content .= '<li><b>categories</b>:<ul>';
		if (strlen($newsData['categories'])){
			foreach(explode(',',$newsData['categories']) as $catUid) {
				$content .= '<li>[pid: '.$catUid.'] '.$newsCategoriesNames[$catUid].'</li>';
			}
		} else {
			$content .= '<li>No categories.</li>';
		}
		$content .= '</ul></li>';

		if (count($newsData['relations'])) {
			$content .= '<li><b>relations</b>: ';
			$content .= '<ul>';
			foreach($newsData['relations'] as $relation) {
				$content .= '<li>'. $relation[0] .':'. $relation[1] .'</li>';
			}
			$content .= '</ul></li>';
		}
		$content .= '</ul>';

		if (count($viewsData)) {
			if($newsData['viewsCategoryMatchCounter']){

				$categoryModeNames = array(
				0 => '<b>ALL</b> - show records at this view no matter of news categories',
				1 => '<b>OR</b> - show records at this view only if at least one category of this view is in record categories',
				2 => '<b>AND</b> - show records at this view only if all categories of this view are in record categories',
				3 => '<b>AND NOT</b> - show records in this view only if the record do NOT have any of the view categories',
				4 => '<b>OR NOT</b> - show records in this view only if the record do NOT have any of the view categories'
				);
				$typeOfClears = array ('record' => 'selective','view' => 'whole');


				$content .= '<br /><h2>Views to clear</h2>';
				$content .= '<ol>';

				foreach(array_keys($viewsData) as $typeOfView) {
					$typeOfView = substr($typeOfView, 0 ,-1);

					$content .= '<li><h3>'.$typeOfView.' views to clear</h3>';
					if(is_array($viewsData[$typeOfView.'.']['data.'])){
						$content .= '<ul>';
						foreach($typeOfClears as $typeOfClearMarker => $typeOfClear){

							$atLeastOne = 0;
							$content1 = '';
							foreach($viewsData[$typeOfView.'.']['data.'] as $uid => $view) {
								$uid = substr($uid, 0 ,-1);

								if ($view['action'] == $typeOfClearMarker.'-marker') {
									$content1 .= '<li><b>'. strtoupper($typeOfView) .'</b> at <b>'.$this->getRecordPath($view['pid'], '', '', 0) .'</b><br />Detail of this view:'; // li of one view start
									$content1 .= '<ul>'; //ul details start
									$content1 .= '<li>PID: <a href="/index.php?id='. $view['pid'] .'">'. $view['pid'] .'</a> UID: '.$uid.'</li>';

									if ($typeOfView == 'single' && isset($view['clearUids'])) $content1 .= '<li>uids to clear: '. $view['clearUids'] .'</li>';

									$content1 .= '<li>Category mode: '. $categoryModeNames[$view['categoryMode']] .'</li>';

									$content1 .= '<li>Category selection: <ul>';
									if (strlen($view['categorySelection'])){
										foreach(explode(',',$view['categorySelection']) as $catUid) {
											$content1 .= '<li>[pid: '.$catUid.'] '.$newsCategoriesNames[$catUid].'</li>';
										}
									} else {
										$content1 .= '<li>No categories.</li>';
									}
									$content1 .= '</ul><br />';
									$content1 .= '</ul>'; //ul details end
									$content1 .= '</li>'; //li of one view end

									$atLeastOne = 1;
								}

							}
							if($atLeastOne) {
								$content .= '<li>';
								$content .= '<h4>'.$typeOfClear.' clear</h4>';
								$content .= '<ol>';
								$content .= $content1;
								$content .= '</ol>';
								$content .= '</li>';
							}

						}
					}else{
						$content .= 'None.';
					}
					$content .= '</ul>';
				}
				$content .= '</ol><br /><br /><br />--end of report--<br /><br /><br />';
			} else {
				$content .= 'No need to clear the page_cache. <b>Reason: there was no view that matched the news categories';
			}
		} else {
			$content .= 'No need to clear the page_cache. <b>Reason: '. $reasonNotToClear .'.</b>';
		}
		$content .= '</body></html>';
		$fileStatus = t3lib_div::writeFileToTypo3tempDir(PATH_site.'typo3temp/ttnewscache/'.$this->reportFileName,$content);
	}


	/**
	 * Get array with views info. Info is created on base of tt_content.
	 *
	 * @param	string		Comma separated views to search for in tt_news plugin in tt_content
	 * @return	array		Array of views, formatted TS like.
	 */
	function getTTnewsViewsDataFromTTcontent($viewsTypes) {

		/**
		 *
		 * Returned array structure looks like:
		 *
		 * [type_of_view].data.[uid_of_tt_news_plugin] {
		 *	 pid =
		 *   categoryMode =
		 *   categorySelection =
		 * }
		 *
		 */

		if (count($viewsTypes)) {
			foreach($viewsTypes as $viewType) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, pid, pi_flexform','tt_content','list_type = \'9\' AND pi_flexform LIKE \'%>'. $GLOBALS['TYPO3_DB']->escapeStrForLike($viewType,'tt_content') .'<%\' AND deleted=0 AND hidden=0');
				$viewTypeLower = strtolower($viewType);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$xmlContentArr = t3lib_div::xml2array($row['pi_flexform']);
					if (is_array($xmlContentArr['data'])) {
						$viewsData[$viewTypeLower.'.']['data.'][$row['uid'].'.'] = array (
							'pid' => intval($row['pid']),
							'categoryMode' => intval($xmlContentArr['data']['sDEF']['lDEF']['categoryMode']['vDEF']),
							'categorySelection' => $xmlContentArr['data']['sDEF']['lDEF']['categorySelection']['vDEF'],
						);
					}
				}
			}
			if ($this->debug) t3lib_div::devLog('[VD] Automatic views generated.', $this->extKey);
		}else{
			if ($this->debug) t3lib_div::devLog('No view types set. Can not generate views array.', $this->extKey);
		}
		//debug($viewsData);
		return $viewsData;
	}

	/**
	 * Get the path for the id given. This function comes from extension KJ TYPO3 Recycler (kj_recycler)
	 *
	 * @param	integer		Page uid
	 * @param	string		Additional clause to 'where'
	 * @param	integer		Number of chars the limit can have
	 * @param	boolean
	 * @return	string		Path to given id separated with '/'
	 */
	function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit=0)	{
		if (!$titleLimit) { $titleLimit=1000; }
		$loopCheck = 100;
		$output = $fullOutput = ' / ';

		while ($uid!=0 && $loopCheck>0)	{
			$loopCheck--;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,title','pages','uid='.intval($uid).(strlen(trim($clause)) ? ' AND '.$clause : ''));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				t3lib_BEfunc::fixVersioningPid('pages',$row);
				if ($row['_ORIG_pid'])	{
					$output = ' [#VEP#]'.$output;		// Adding visual token - Versioning Entry Point - that tells that THIS position was where the versionized branch got connected to the main tree. I will have to find a better name or something...
				}

				$uid = $row['pid'];
				$output = ' / '.t3lib_div::fixed_lgd_cs(strip_tags($row['title']),$titleLimit).$output;
				if ($fullTitleLimit) $fullOutput = ' / '.t3lib_div::fixed_lgd_cs(strip_tags($row['title']),$fullTitleLimit).$fullOutput;

			} else {
				break;
			}
		}

		if ($fullTitleLimit)	{
			return array($output, $fullOutput);
		} else {
			return $output;
		}
	}

	/**
	 * Get related categories
	 *
	 * @param	integer		Id of the news to search for cateogries.
	 * @return	array		Array of categories ids.
	 */
	function getArrayOfTTnewsCategories($id) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_foreign','tt_news','tt_news_cat_mm','tt_news_cat',' AND uid_local='. intval($id));
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$cats[] = $row['uid_foreign'];
		}
		return $cats;
	}

	/**
	 * Get related news - it take into account only tt_news relations
	 *
	 * @param	integer		Id of the news to search for related.
	 * @return	array		Array of related news in format tt_news_x.
	 */
	function getArrayOfTTnewsRelated($id) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('uid_foreign','tt_news','tt_news_related_mm','tt_news',' AND uid_local='. intval($id));
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$rel[] = 'tt_news_'.$row['uid_foreign'];
		}
		return $rel;

	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ttnewscache/class.tx_ttnewscache_tcemainproc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ttnewscache/class.tx_ttnewscache_tcemainproc.php']);
}

?>