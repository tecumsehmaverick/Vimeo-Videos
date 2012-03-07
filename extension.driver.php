<?php	
	class extension_vimeo_videos extends Extension {

		public function about() {
			return array(
				'name'			=> 'Field: Vimeo',
				'version'		=> '0.2',
				'release-date'	=> '2012-03-07',
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
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__appendResources'
				)
			);
		}

		public function __appendResources($context){
			$page = Administration::instance()->Page;
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
