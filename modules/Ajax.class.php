<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	*/
	
	require_once(dirname(__FILE__)."/ActionMgr.class.php");
	class AjaxManager {
		function AjaxManager() {}
		
		private function honeyPot() {
		}
		
		public function handle() {
			$type = FS::$secMgr->checkAndSecuriseGetData("at");
			switch($type) {
				case 1: // menu
					$mid = FS::$secMgr->checkAndSecuriseGetData("mid");
					echo FS::$iMgr->LoadMenu($mid);
					break;
				case 2: // module
					$mid = FS::$secMgr->checkAndSecuriseGetData("mod");
					echo FS::$iMgr->loadModule($mid);
					echo FS::$iMgr->renderJS();
					break;
				case 3: // Action Handler
					$aMgr = new ActionMgr();
					$aMgr->DoAction(FS::$secMgr->checkAndSecuriseGetData("act"));
					break;
				default: $this->honeyPot(); break;
			}
		}
	}


?>
