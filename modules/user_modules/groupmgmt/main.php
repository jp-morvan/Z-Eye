<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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

	require_once(dirname(__FILE__)."/../../../lib/FSS/objects/InterfaceModule.FS.class.php");
	require_once(dirname(__FILE__)."/module.php");
	require_once(dirname(__FILE__)."/rules.php");

	if(!class_exists("MGroupMgmt")) {
		class MGroupMgmt extends InterfaceModule {
			function Mgroupmgmt() {
				parent::InterfaceModule();
				$this->conf->modulename = "iGroupMgmt";
				$this->conf->seclevel = 5;
				$this->moduleclass = new iGroupMgmt();
				$this->rulesclass = new rGroupMgmt();
	                        $this->conf->connected = $this->rulesclass->getConnectedState();
			}
		};
	}

	$module = new MGroupMgmt();
?>