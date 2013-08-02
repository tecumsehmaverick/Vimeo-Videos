<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once TOOLKIT . '/class.gateway.php';

	class fieldVimeo_Video extends Field {

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function __construct() {
			parent::__construct();

			$this->_name = __('Vimeo Video');
			$this->_required = true;
		}

		public function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`video_id` varchar(11) default NULL,
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`)
				);"
			);
		}

		public function allowDatasourceOutputGrouping() {
			return false;
		}

		public function allowDatasourceParamOutput() {
			return false;
		}

		public function canFilter() {
			return true;
		}

		public function canPrePopulate() {
			return false;
		}

		public function isSortable() {
			return true;
		}

		public function fetchIncludableElements() {
			return array(
				$this->get('element_name')
			);
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'compact'));

			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);

			$wrapper->appendChild($div);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$value = General::sanitize($data['video_id']);
			$label = Widget::Label($this->get('label'));

			$video_id = new XMLElement('input');
			$video_id->setAttribute('type', 'text');
			$video_id->setAttribute('name', 'fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix);
			$video_id->setAttribute('value', $value);

			if ($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));

			if (is_numeric($value) && is_null($flagWithError)) {
				$video_id->setAttribute('class', 'hidden');

				$video_container = new XMLElement('div');
				$video_container->appendChild(
					self::createPlayer($value)
				);

				$label->appendChild($video_container);
			}

			$label->appendChild($video_id);

			if ($flagWithError != NULL)
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else
				$wrapper->appendChild($label);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			if (empty($data)) return array();

			$data = self::parseData($data);
			$video_id = self::getVideoId($data);

            $return = array(
                'video_id' => $video_id
            );

			return $return;
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			if($data) {
				$data = self::parseData($data);
				$video_id = self::getVideoId($data);

				if(is_null($video_id) || !is_numeric($video_id)) {
					$message = __("%s must be a valid Vimeo Video ID or URL", array(
						$this->get('label')
					));

					return self::__INVALID_FIELDS__;
				}
			}

			return self::__OK__;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null) {
			if(!is_array($data) || empty($data)) return;

			$video = new XMLElement($this->get('element_name'));
			$video->setAttributeArray(array(
				'video-id' => $data['video_id']
			));

            $video->appendChild(
                self::createPlayer($data['video_id'])
            );


			$wrapper->appendChild($video);
		}

		public function prepareTableValue($data, XMLElement $link=NULL){
			if (strlen($data['video_id']) == 0) return NULL;

            $hash = unserialize(@file_get_contents("http://vimeo.com/api/v2/video/" . $data['video_id'] . ".php"));

            if ($hash) {
                $image = '<img src="' . $hash[0]['thumbnail_medium'] . '" width="120" height="90" />';
            } else {
                "Thumbnail not found";
            }


			if ($link) {
				$link->setValue($image);
				return $link->generate();
			} else {
				$link = new XMLElement('span', $image);
				return $link->generate();
			}
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`id` $order");
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND t{$field_id}_{$this->_key}.video_id = '{$value}'
					";
				}

			} else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.video_id IN ('{$data}')
				";
			}

			return true;
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');
			//$refresh = $this->get('refresh');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			//$fields['refresh'] = ($refresh == '') ? '0' : $refresh;

			Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1");

			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

	/*-------------------------------------------------------------------------
		Helpers:
	-------------------------------------------------------------------------*/

		public static function parseData($data) {
			if(is_numeric($data)) {
				return "http://www.vimeo.com/{$data}";
			} else {
                return $data;
            }
		}

		public static function getVideoId($data) {
			//$url = parse_url($data);
			
            $url = $data;
            $result = preg_match('/(\d+)/', $url, $matches);
            if ($result) {
                return $matches[0];
            }

			return null;
	 	}

		public static function createPlayer($video_id = null) {
			if(is_null($video_id)) return null;



			$video_url = 'http://player.vimeo.com/video/' . $video_id . '?title=0&amp;byline=0&amp;portrait=0';

			$video = new XMLElement('iframe');
            $video->setAttribute('src', $video_url);
			$video->setAttribute('width', 560);
			$video->setAttribute('height', 340);
            $video->setAttribute('frameborder', "0");

            $video->setAttribute('webkitAllowFullScreen', "webkitAllowFullScreen");
            $video->setAttribute('mozallowfullscreen', "mozallowfullscreen");
            $video->setAttribute('allowFullScreen', "allowFullScreen");

            $container = new XMLElement('div');
			$container->setAttribute('class', 'vimeo_video_container');
			$container->appendChild($video);

			return $container;
		}

	}
