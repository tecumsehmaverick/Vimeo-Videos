<?php	
	class extension_vimeo_videos extends Extension {

		public function about() {
			return array(
				'name'			=> 'Field: Vimeo',
				'version'		=> '0.3',
				'release-date'	=> '2013-08-02',
				'author'		=> array(
					'name'			=> 'Gaya Kessler',
					'website'		=> 'http://gayadesign.com',
					'email'			=> 'gaya.kessler@gmail.com'
				)
			);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__appendResources'
				)
			);
		}

		public function __appendResources($context){
			$page = Administration::instance()->Page;
			$page->addStylesheetToHead(URL . '/extensions/vimeo_videos/assets/vimeo_videos.css', 'screen', 1000, false);
		}

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_vimeo_video`");
		}

		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE `tbl_fields_vimeo_video` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`refresh` int(11) unsigned NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}

	}
