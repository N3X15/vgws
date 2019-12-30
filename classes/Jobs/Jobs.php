<?php
namespace VGWS\Jobs;
class Jobs {
	/**
	 * Add or remove jobs, depending on your distro of SS13.
	 *
	 * Used in jobbans.
	 */
	private static $KnownJobs=[];

	/**
	 * Job categories/regions.
	 *
	 * Used to group jobs for jobbans.
	 */
	private static $Categories=[];
	private static $_Loaded=false;
	private static function Load() {
		if(!self::$_Loaded) {
			self::$Categories = json_decode(file_get_contents(DATA_DIR.'/jobs.json'));
			foreach(self::$Categories as $cat => $jobs) {
				foreach($jobs as $job) {
					self::$KnownJobs[]=$job;
				}
			}
			self::$_Loaded = true;
		}
	}

	public static function GetAllKnownJobs() {
		self::Load();
		return Jobs::$KnownJobs;
	}
	public static function GetCategories() {
		self::Load();
		return Jobs::$Categories;
	}
}
