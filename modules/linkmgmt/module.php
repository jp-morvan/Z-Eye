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

	require_once(dirname(__FILE__)."/locales.php");

	class iLinkMgmt extends FSModule{
		function iLinkMgmt() { parent::FSModule(); $this->loc = new lLinkMgmt(); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-link"));
			$output = "";
			if($do = FS::$secMgr->checkGetData("do")) {
				if($do == 1)
					$output .= $this->showLinkForm();
				else
					$output .= $this->showLinkForm(true);
			}
			else {
				$output .= FS::$iMgr->h3("title-link");
				$output .= "<a href=\"index.php?mod=".$this->mid."&do=1\">".$this->loc->s("New-link")."</a>
				<table id=\"linkSort\" width=\"55%\">
				<thead><tr><th width=\"40px\">Id</th><th width=\"90px\"><center>Type</center></th><th><center>Args</center></th><th width=\"15px\"></th></tr></thead>";
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."http_links","id,type,args","",array("order" => "id","ordersens" => 1));
				while($data = FS::$dbMgr->Fetch($query)) {
					$output .= "<tr id=\"l".$data["id"]."tr\"><td><center><a href=\"index.php?mod=".$this->mid."&do=2&link=".$data["id"]."\">".$data["id"]."</a></center></td><td><center>";
					if($data["type"] == 0)
						$output .= $this->loc->s("Normal");
					else if($data["type"] == 1)
						$output .= $this->loc->s("Action");
					else if($data["type"] == 2)
						$output .= $this->loc->s("Module");
					else if($data["type"] == 3)
						$output .= "JavaScript";
					else if($data["type"] == 4)
						$output .= $this->loc->s("rewr-mod");
					else
						$output .= $this->loc->s("rewr-other");
					$output .= "</center></td><td><center>".$data["args"]."</center></td><td>";
					$output .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=3&link=".$data["id"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-link")."'#".$data["id"]."' ?","Confirm","Cancel")));
					$output .= "</td></tr>";
				}
				$output .= "</table></div>";
				FS::$iMgr->jsSortTable("linkSort");
			}
			return $output;
		}
		
		public function showLinkForm($edit = false) {
			$output = FS::$iMgr->h3($edit ? $this->loc->s("link-edit") : $this->loc->s("link-create"),true);
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=".($edit ? 2 : 1));
			$lnk = NULL;
			if($edit) {
				$lid = FS::$secMgr->checkGetData("link");
				FS::$secMgr->SecuriseStringForDB($lid);
				$output .= FS::$iMgr->hidden("link_id",$lid);
				$lnk = new HTTPLink($lid);
				$lnk->Load();
			}
			
			$output .= "Type ";
			$output .= FS::$iMgr->select("type");
			$output .= FS::$iMgr->selElmt($this->loc->s("Normal"),0,($lnk && $lnk->getType() == 0) ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("Action"),1,($lnk && $lnk->getType() == 1) ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("Module"),2,($lnk && $lnk->getType() == 2) ? true : false);
			$output .= FS::$iMgr->selElmt("JavaScript",3,($lnk && $lnk->getType() == 3) ? true : false);	
			$output .= FS::$iMgr->selElmt($this->loc->s("rewr-mod"),4,($lnk && $lnk->getType() == 4) ? true : false);
			$output .= FS::$iMgr->selElmt($this->loc->s("rewr-other"),5,($lnk && $lnk->getType() == 5) ? true : false);		
			$output .= "</select><br />Arguments ";
			
			$output .= FS::$iMgr->input("args",$lnk ? $lnk->getArgs() : "",25,130);
			$output .= "<hr>";
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"));
			$output .= "</form>";
			return $output;
		}
		
		public function RegisterLink() {
			$link = new HTTPLink(0);
			$args = FS::$secMgr->checkAndSecurisePostData("args");
			$type = FS::$secMgr->checkAndSecurisePostData("type");
			
			if(!$args || !$type)
				return;
				
			$link->setArgs($args);
			$link->setType($type);
			$link->Create();
			FS::$log->i(FS::$sessMgr->getUserName(),"linkmgmt",0,"Link added. Type: ".$type." args: ".$args);
		}
		
		public function EditLink() {
			$args = FS::$secMgr->checkAndSecurisePostData("args");
			$type = FS::$secMgr->checkAndSecurisePostData("type");
			$lid = FS::$secMgr->checkAndSecurisePostData("link_id");
			
			if(!$args || !$type || !$lid)
				return;
				
			$link = new HTTPLink($lid);
			$link->setArgs($args);
			$link->setType($type);
			$link->SaveToDB();
			FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"Link edited. Type: ".$type." args: ".$args);
		}
		
		public function RemoveLink() {
			$lid = FS::$secMgr->checkAndSecuriseGetData("link");
			if(!$lid)
				return;
				
			$link = new HTTPLink($lid);
			$link->Delete();
			FS::$log->i(FS::$sessMgr->getUserName(),"ipmanager",0,"Link removed '".$lid."'");
			if(FS::isAjaxCall())
				FS::$iMgr->ajaxEcho("Done","hideAndRemove('#l".$lid."tr');");
			else
				FS::$iMgr->redir("mod=".$this->mid);
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // new
					$this->RegisterLink();
					FS::$iMgr->redir("mod=".$this->mid);
					break;
				case 2: // edit
					$this->EditLink();
					FS::$iMgr->redir("mod=".$this->mid);
					break;
				case 3: // del
					$this->RemoveLink();
					break;
				default: break;
			}
		}
	};
?>