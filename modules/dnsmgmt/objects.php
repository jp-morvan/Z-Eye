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

	require_once(dirname(__FILE__)."/../ipmanager/objects.php");

	final class dnsZone extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_zones";
			$this->sqlAttrId = "zonename";
			$this->readRight = "mrule_dnsmgmt_zone_read";
			$this->writeRight = "mrule_dnsmgmt_zone_write";
			$this->errNotExists = "err-zone-not-exists";
			$this->errAlreadyExists = "err-zone-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnszone",
				"sqltable" => "dns_zones",
				"sqlattrid" => "zonename",
				"attrlist" => array(array("Zone","zonename",""), array("Zone-type","zonetype","s",
					array(1 => "Classic", 2 => "Slave-only", 3 => "Forward-only")),
					array("Desc","description","")),
				"sorted" => true,
				"odivnb" => 10,
				"odivlink" => "zonename=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=12&zonename",
				"rmconfirm" => "confirm-remove-zone",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(9,$this->loc->s("add-zone"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($aclname = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}

			if (!$this->Load($aclname)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}


			$ztsel = FS::$iMgr->select("zonetype"/*JS*/).
				FS::$iMgr->selElmt($this->loc->s("Classic"),"1",$this->zonetype == 1).
				FS::$iMgr->selElmt($this->loc->s("Slave-only"),"2",$this->zonetype == 2).
				FS::$iMgr->selElmt($this->loc->s("Forward-only"),"3",$this->zonetype == 3).
				"</select>";

			// Generate textarea output for forwarders
			$forwardlist = "";
			$count = count($this->forwarders);
			for ($i=0;$i<$count;$i++) {
				$forwardlist .= $this->forwarders[$i];
				if ($i != $count - 1) {
					$forwardlist .= "\n";
				}
			}

			// Generate textarea output for masters 
			$masterlist = "";
			$count = count($this->masters);
			for ($i=0;$i<$count;$i++) {
				$masterlist .= $this->masters[$i];
				if ($i != $count - 1) {
					$masterlist .= "\n";
				}
			}

			$cluster = new dnsCluster();
			$clusterlist = $cluster->getSelect(array("name" => "clusters", "multi" => false,
				"selected" => $this->clusters));

			$acl = new dnsACL();
			$transferlist = $acl->getSelect(array("name" => "transfer", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->transferAcls));

			$updatelist = $acl->getSelect(array("name" => "update", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->updateAcls));

			$querylist = $acl->getSelect(array("name" => "query", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->queryAcls));

			$notifylist = $acl->getSelect(array("name" => "notify", "multi" => true,
				"noneelmt" => true, "heritedelmt" => true, "anyelmt" => true, "selected" => $this->notifyAcls));

			$output = FS::$iMgr->cbkForm("11")."<table>".
				FS::$iMgr->idxLines(array(
					array("Zone","zonename",array("type" => "idxedit", "value" => $this->zonename,
						"length" => "256", "edit" => $this->zonename != "")),
					array("Description","description",array("value" => $this->description)),
					array("Clusters","",array("type" => "raw", "value" => $clusterlist)),
					array("Zone-type","",array("type" => "raw", "value" => $ztsel)),
					array("Forwarders","forwarders",array("type" => "area",
						"value" => $forwardlist, "height" => 150, "width" => 200)),
					array("Masters","masters",array("type" => "area",
						"value" => $masterlist, "height" => 150, "width" => 200)),
					array("allow-transfer","",array("type" => "raw", "value" => $transferlist)),
					array("allow-notify","",array("type" => "raw", "value" => $notifylist)),
					array("allow-update","",array("type" => "raw", "value" => $updatelist)),
					array("allow-query","",array("type" => "raw", "value" => $querylist)),
				)).
				FS::$iMgr->aeTableSubmit($this->zonename != "");

			return $output;
		}

		protected function Load($name = "") {
			$this->zonename = $name;
			$this->description = "";
			$this->zonetype = 0;
			$this->clusters = array();
			$this->forwarders = array();
			$this->masters = array();
			$this->transferAcls = array("herited");
			$this->updateAcls = array("herited");
			$this->queryAcls = array("herited");
			$this->notifyAcls = array("herited");

			if ($this->zonename) {
				$query = FS::$dbMgr->Select($this->sqlTable,"description,zonetype",$this->sqlAttrId." = '".$this->zonename."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->description = $data["description"];
					$this->zonetype = $data["zonetype"];
					$this->clusters = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_clusters","clustername",
						$this->sqlAttrId." = '".$this->zonename."'");
					$this->forwarders = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_forwarders","zoneforwarder",
						$this->sqlAttrId." = '".$this->zonename."'");
					$this->masters = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_masters","zonemaster",
						$this->sqlAttrId." = '".$this->zonename."'");
					$this->transferAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_transfer","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->transferAcls) == 0) {
						$this->transferAcls = array("herited");
					}
					$this->updateAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_update","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->updateAcls) == 0) {
						$this->updateAcls = array("herited");
					}
					$this->queryAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_query","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->queryAcls) == 0) {
						$this->queryAcls = array("herited");
					}
					$this->notifyAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_zone_allow_notify","aclname",
						$this->sqlAttrId." = '".$this->zonename."'");
					if (count($this->notifyAcls) == 0) {
						$this->notifyAcls = array("herited");
					}
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($zonename) {
			FS::$dbMgr->Delete($this->sqlTable,"zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_clusters","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_forwarders","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_masters","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_transfer","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_update","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_query","zonename = '".$zonename."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_zone_allow_notify","zonename = '".$zonename."'");
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$zonename = FS::$secMgr->checkAndSecurisePostData("zonename");
			$description = FS::$secMgr->checkAndSecurisePostData("description");
			$zonetype = FS::$secMgr->checkAndSecurisePostData("zonetype");
			$clusters = FS::$secMgr->checkAndSecurisePostData("clusters");
			$forwarders = FS::$secMgr->checkAndSecurisePostData("forwarders");
			$masters = FS::$secMgr->checkAndSecurisePostData("masters");
			$transferAcls = FS::$secMgr->checkAndSecurisePostData("transfer");
			$updateAcls = FS::$secMgr->checkAndSecurisePostData("update");
			$queryAcls = FS::$secMgr->checkAndSecurisePostData("query");
			$notifyAcls = FS::$secMgr->checkAndSecurisePostData("notify");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$fwdarr = array();
			$masterarr = array();

			if (!$zonename || !$description || !$zonetype || !FS::$secMgr->isNumeric($zonetype) ||
				!$clusters || $transferAcls && !is_array($transferAcls) ||
				$updateAcls && !is_array($updateAcls) || $queryAcls && !is_array($queryAcls) ||
				$notifyAcls && !is_array($notifyAcls) ||
				$edit && $edit != 1) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if (!FS::$secMgr->isDNSName($zonename)) {
				FS::$iMgr->ajaxEchoNC("err-invalid-zonename");
				return;
			}

			$exists = $this->exists($zonename);
			if ($edit) {
				if (!$exists) {
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					FS::$iMgr->ajaxEchoNC($this->errAlreadyExists);
					return;
				}
			}

			if ($zonetype < 1 && $zonetype > 3) {
				FS::$iMgr->ajaxEcho("err-bad-zonetype");
				return;
			}

			$cluster = new dnsCluster();
			// It's a simple value a this time. Must be multi value for forward & slave only
			// JS ?
			if (!$cluster->exists($clusters)) {
				FS::$iMgr->ajaxEcho($cluster->getErrNotExists());
				return;
			}

			if ($forwarders) {
				$fwdarr = FS::$secMgr->getIPList($forwarders);
				if (!$fwdarr) {
					FS::$iMgr->ajaxEchoNC("err-some-ip-invalid");
					return;
				}
			}

			if ($masters) {
				$masterarr = FS::$secMgr->getIPList($masters);
				if (!$masterarr) {
					FS::$iMgr->ajaxEchoNC("err-some-ip-invalid");
					return;
				}
			}

			if ($queryAcls) {
				$count = count($queryAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($queryAcls[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($notifyAcls) {
				$count = count($notifyAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($notifyAcls[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($updateAcls) {
				$count = count($updateAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($updateAcls[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($transferAcls) {
				$count = count($transferAcls);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($transferAcls[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				$this->removeFromDB($zonename);
			}

			FS::$dbMgr->Insert($this->sqlTable,$this->sqlAttrId.",description,zonetype",
				"'".$zonename."','".$description."','".$zonetype."'");

			FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_clusters",$this->sqlAttrId.",clustername",
				"'".$zonename."','".$clusters."'");

			$count = count($fwdarr);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_forwarders",$this->sqlAttrId.",zoneforwarder",
					"'".$zonename."','".$fwdarr[$i]."'");
			}

			$count = count($masterarr);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_masters",$this->sqlAttrId.",zonemaster",
					"'".$zonename."','".$masterarr[$i]."'");
			}

			$count = count($transferAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_transfer",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$transferAcls[$i]."'");
			}

			$count = count($updateAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_update",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$updateAcls[$i]."'");
			}

			$count = count($queryAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_query",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$queryAcls[$i]."'");
			}

			$count = count($notifyAcls);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_zone_allow_notify",$this->sqlAttrId.",aclname",
					"'".$zonename."','".$notifyAcls[$i]."'");
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($zonename,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$zonename = FS::$secMgr->checkAndSecurisePostData("zonename");

			if (!$zonename) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if (!$this->exists($zonename)) {
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDB($zonename);
			FS::$dbMgr->CommitTr();

			$this->log(0,"Removing zone '".$zonename."'");

			$js = $this->tMgr->removeLine($zonename);
			FS::$iMgr->ajaxEcho("Done",$js);
		}
		private $zonename;
		private $description;
		private $zonetype;
		private $clusters;
		private $forwarders;
		private $masters;
		private $transferAcls;
		private $updateAcls;
		private $queryAcls;
		private $notifyAcls;
	};

	final class dnsACL extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_acls";
			$this->sqlAttrId = "aclname";
			$this->readRight = "mrule_dnsmgmt_acl_read";
			$this->writeRight = "mrule_dnsmgmt_acl_write";
			$this->errNotExists = "err-acl-not-exists";
			$this->errAlreadyExists = "err-acl-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnsacl",
				"sqltable" => "dns_acls",
				"sqlattrid" => "aclname",
				"attrlist" => array(array("ACL","aclname",""), array("Desc","description","")),
				"sorted" => true,
				"odivnb" => 6,
				"odivlink" => "aclname=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=8&aclname",
				"rmconfirm" => "confirm-remove-acl",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(5,$this->loc->s("add-acl"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($aclname = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}

			if (!$this->Load($aclname)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}

			$output = FS::$iMgr->cbkForm("7")."<table>".
				FS::$iMgr->idxLines(array(
					array("acl-name","aclname",array("type" => "idxedit", "value" => $this->aclname,
						"length" => "32", "edit" => $this->aclname != "")),
					array("Description","description",array("value" => $this->description))
				));

			// TSIG list
			$selected = array("none");
			if (count($this->tsigs) > 0) {
				$selected = $this->tsigs;
			}

			$tsig = new dnsTSIGKey();
			$tsiglist = $tsig->getSelect(array("name" => "tsiglist", "multi" => true,
				"exclude" => $this->aclname, "noneelmt" => true, "selected" => $selected));
			if ($tsiglist != NULL) {
				$output .= FS::$iMgr->idxLine("tsig-to-include","",array("type" => "raw", "value" => $tsiglist));
			}

			// Subnet list
			$selected = array("none");
			if (count($this->networks) > 0) {
				$selected = $this->networks;
			}

			$sObj = new dhcpSubnet();
			$subnetlist = $sObj->getSelect(array("name" => "subnetlist", "multi" => true,
				"exclude" => $this->aclname, "noneelmt" => true, "selected" => $selected));
			if ($subnetlist != NULL) {
				$output .= FS::$iMgr->idxLine("subnets-to-include","",array("type" => "raw", "value" => $subnetlist));
			}

			// IP List
			$list = "";
			$count = count($this->ips);
			if ($count > 0) {
				for ($i=0;$i<$count;$i++) {
					$list .= $this->ips[$i];
					if ($i < $count-1)
						$list .= "\n";
				}
			}

			$output .= FS::$iMgr->idxLine("ip-to-include","iplist",array("type" => "area", "tooltip" => "tooltip-ipinclude",
				"width" => 300, "height" => "150", "length" => 1024, "value" => $list));

			// ACL list
			$selected = array("none");
			if (count($this->acls) > 0) {
				$selected = $this->acls;
			}

			$acllist = $this->getSelect(array("name" => "acllist", "multi" => true,
				"exclude" => $this->aclname, "noneelmt" => true, "selected" => $selected));
			if ($acllist != NULL) {
				$output .= FS::$iMgr->idxLine("acls-to-include","",array("type" => "raw", "value" => $acllist));
			}

			// DNS Name List
			$list = "";
			$count = count($this->dnsnames);
			if ($count > 0) {
				for ($i=0;$i<$count;$i++) {
					$list .= $this->dnsnames[$i];
					if ($i < $count-1)
						$list .= "\n";
				}
			}

			$output .= FS::$iMgr->idxLine("dns-to-include","dnslist",array("type" => "area", "tooltip" => "tooltip-dnsinclude",
				"width" => 300, "height" => "150", "length" => 4096, "value" => $list));

			$output .= FS::$iMgr->aeTableSubmit($this->aclname != "");

			return $output;
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$herited = (isset($options["heritedelmt"]) && $options["heritedelmt"] == true);
			$any = (isset($options["anyelmt"]) && $options["anyelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none",
					in_array("none",$selected));
			}
			if ($herited) {
				$output .= FS::$iMgr->selElmt($this->loc->s("Herited"),"herited",
					in_array("herited",$selected));
			}
			if ($any) {
				$output .= FS::$iMgr->selElmt($this->loc->s("Any"),"any",
					in_array("any",$selected));
			}

			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId),"selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}

		protected function Load($name = "") {
			$this->aclname = $name;
			$this->description = "";
			$this->ips = array();
			$this->networks = array();
			$this->tsigs = array();
			$this->acls = array();
			$this->dnsnames = array();

			if ($this->aclname) {
				if ($desc = FS::$dbMgr->GetOneData($this->sqlTable,"description","aclname = '".$this->aclname."'")) {
					$this->description = $desc;
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_ip","ip","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->ips[] = $data["ip"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_network","netid","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->networks[] = $data["netid"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_tsig","tsig","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->tsigs[] = $data["tsig"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_acl","aclchild","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->acls[] = $data["aclchild"];
					}
					$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dns_acl_dnsname","dnsname","aclname = '".$this->aclname."'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$this->dnsnames[] = $data["dnsname"];
					}
				}
				else {
					return false;
				}
			}
			return true;
		}

		protected function removeFromDB($aclname) {
			FS::$dbMgr->Delete($this->sqlTable,"aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_ip","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_network","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_tsig","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_acl","aclname = '".$aclname."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_dnsname","aclname = '".$aclname."'");
		}

		protected function exists($id) {
			if ($id == "none" || $id == "herited" || $id == "any") {
				return true;
			}

			if (FS::$dbMgr->GetOneData($this->sqlTable,$this->sqlAttrId,$this->sqlAttrId." = '".$id."'")) {
				return true;
			}
			return false;
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$aclname = FS::$secMgr->checkAndSecurisePostData("aclname");
			$description = FS::$secMgr->checkAndSecurisePostData("description");
			$tsiglist = FS::$secMgr->checkAndSecurisePostData("tsiglist");
			$subnetlist = FS::$secMgr->checkAndSecurisePostData("subnetlist");
			$acllist = FS::$secMgr->checkAndSecurisePostData("acllist");
			$iplist = FS::$secMgr->checkAndSecurisePostData("iplist");
			$dnslist = FS::$secMgr->checkAndSecurisePostData("dnslist");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$iplistarr = array();
			$dnslistarr = array();

			if (!$aclname || !$description) {
				FS::$iMgr->ajaxEchoNC("err-bad-datas");
				$this->log(2,"Some datas are invalid or wrong for modify dns ACL");
				return;
			}

			if ($aclname == "none" || $aclname == "herited" || $aclname == "any") {
				FS::$iMgr->ajaxEchoNC("err-acl-name-protected");
				$this->log(2,"ACL name '".$aclname."' is protected");
				return;
			}

			$exists = $this->exists($aclname);
			if ($edit) {	
				if (!$exists) {
					$this->log(1,"Unable to edit acl '".$aclname."': not exists");
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					$this->log(1,"Unable to add acl '".$aclname ."': already exists");
					FS::$iMgr->ajaxEcho($this->errAlreadyExists);
					return;
				}
			}
			$rulefound = false;

			if ($tsiglist && is_array($tsiglist)) {
				if (!in_array("none",$tsiglist)) {
					$count = count($tsiglist);
					for ($i=0;$i<$count;$i++) {
						$tsig = new dnsTSIGKey();
						if (!$tsig->Load($tsiglist[$i])) {
							FS::$iMgr->ajaxEchoNC("err-tsig-key-not-exists");
							return;
						}
						$rulefound = true;
					}
				}
			}

			if ($subnetlist && is_array($subnetlist)) {
				if (!in_array("none",$subnetlist)) {
					$count = count($subnetlist);
					for ($i=0;$i<$count;$i++) {
						$subnet = new dhcpSubnet();
						if (!$subnet->Load($subnetlist[$i])) {
							FS::$iMgr->ajaxEchoNC("err-subnet-not-exists");
							return;
						}
						$rulefound = true;
					}
				}
			}

			if ($acllist && is_array($acllist)) {
				if (!in_array("none",$acllist)) {
					$count = count($acllist);
					for ($i=0;$i<$count;$i++) {
						$acl = new dnsACL();
						if (!$acl->Load($acllist[$i])) {
							FS::$iMgr->ajaxEchoNC("err-acl-not-exists");
							return;
						}
						$rulefound = true;
					}
				}
			}

			if ($iplist) {
				$iplistarr = FS::$secMgr->getIPList($iplist);
				if (!$iplistarr) {
					FS::$iMgr->ajaxEchoNC("err-some-ip-invalid");
					return;
				}
				$rulefound = true;
			}
			if ($dnslist) {
				$dnslistarr = FS::$secMgr->getDNSNameList($dnslist);
				if (!$dnslistarr) {
					FS::$iMgr->ajaxEchoNC("err-some-dns-invalid");
					return;
				}
				$rulefound = true;
			}

			if (!$rulefound) {
				FS::$iMgr->ajaxEchoNC("err-no-rule-specified");
				return;
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				$this->removeFromDB($aclname);
			}

			FS::$dbMgr->Insert($this->sqlTable,"aclname,description","'".$aclname."','".$description."'");

			if ($tsiglist && is_array($tsiglist)) {
				if (!in_array("none",$tsiglist)) {
					$count = count($tsiglist);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_tsig","aclname,tsig",
							"'".$aclname."','".$tsiglist[$i]."'");
					}
				}
			}

			if ($subnetlist && is_array($subnetlist)) {
				if (!in_array("none",$subnetlist)) {
					$count = count($subnetlist);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_network","aclname,netid",
							"'".$aclname."','".$subnetlist[$i]."'");
					}
				}
			}

			if ($acllist && is_array($acllist)) {
				if (!in_array("none",$acllist)) {
					$count = count($acllist);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_acl","aclname,aclchild",
							"'".$aclname."','".$acllist[$i]."'");
					}
				}
			}

			if ($iplist) {
				$count = count($iplistarr);
				for ($i=0;$i<$count;$i++) {
					if ($iplistarr[$i] == "") {
						continue;
					}

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_ip","aclname,ip",
						"'".$aclname."','".$iplistarr[$i]."'");
				}
			}

			if ($dnslist) {
				$count = count($dnslistarr);
				for ($i=0;$i<$count;$i++) {
					if ($dnslistarr[$i] == "") {
						continue;
					}
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_acl_dnsname","aclname,dnsname",
						"'".$aclname."','".$dnslistarr[$i]."'");
				}
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($aclname,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$aclname = FS::$secMgr->checkAndSecuriseGetData("aclname");
			if (!$aclname) {
				FS::$dbMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if ($aclname == "none" || $aclname == "herited" || $aclname == "any") {
				FS::$iMgr->ajaxEchoNC("err-acl-name-protected");
				$this->log(2,"ACL name '".$aclname."' is protected");
				return;
			}
			$exists = $this->exists($aclname);
			if (!$exists) {
				$this->log(1,"Unable to remove acl '".$aclname."': not exists");
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDB($aclname);
			FS::$dbMgr->CommitTr();
			
			$js = $this->tMgr->removeLine($aclname);
			FS::$iMgr->ajaxEcho("Done",$js);
			return;
		}

		private $aclname;
		private $description;
		private $ips;
		private $networks;
		private $tsigs;
		private $acls;
		private $dnsnames;
	};

	final class dnsCluster extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_clusters";
			$this->sqlAttrId = "clustername";
			$this->readRight = "mrule_dnsmgmt_read";
			$this->writeRight = "mrule_dnsmgmt_write";
			$this->errNotExists = "err-cluster-not-exists";
			$this->errAlreadyExists = "err-cluster-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnsclustr",
				"sqltable" => "dns_clusters",
				"sqlattrid" => "clustername",
				"attrlist" => array(array("Name","clustername",""), array("Desc","description","")), 
				"sorted" => true,
				"odivnb" => 8,
				"odivlink" => "clustername=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=10&clustername",
				"rmconfirm" => "confirm-remove-cluster",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(7,$this->loc->s("add-cluster"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($clustername = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}

			if (!$this->Load($clustername)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}

			$acls = new dnsACL();
			$recurselist = $acls->getSelect(array("name" => "recurse", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->recurseAcls));
			$transferlist = $acls->getSelect(array("name" => "transfer", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->transferAcls));
			$notifylist = $acls->getSelect(array("name" => "notify", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->notifyAcls));
			$updatelist = $acls->getSelect(array("name" => "update", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->updateAcls));
			$querylist = $acls->getSelect(array("name" => "query", "multi" => true,
				"noneelmt" => true, "anyelmt" => true, "selected" => $this->queryAcls));

			$server = new dnsServer();
			$masters = $server->getSelect(array("name" => "masters", "multi" => true,
				"selected" => $this->masterMembers));
			$slaves = $server->getSelect(array("name" => "slaves", "multi" => true,
				"selected" => $this->slaveMembers));
			$caches = $server->getSelect(array("name" => "caches", "multi" => true,
				"selected" => $this->cachingMembers));

			$output = FS::$iMgr->cbkForm("9").FS::$iMgr->tip("tip-dnscluster")."<table>".
				FS::$iMgr->idxLines(array(
					array("clustername","clustername",array("type" => "idxedit", "value" => $this->clustername,
						"length" => "64", "edit" => $this->clustername != "")),
						array("Desc","description",array("value" => $this->description, "length" => "128")),
						array("master-servers","",array("type" => "raw", "value" => $masters)),
						array("slave-servers","",array("type" => "raw", "value" => $slaves)),
						array("caching-servers","",array("type" => "raw", "value" => $caches)),
						array("allow-recurse","",array("type" => "raw", "value" => $recurselist)),
						array("allow-transfer","",array("type" => "raw", "value" => $transferlist)),
						array("allow-notify","",array("type" => "raw", "value" => $notifylist)),
						array("allow-update","",array("type" => "raw", "value" => $updatelist)),
						array("allow-query","",array("type" => "raw", "value" => $querylist)),
				)).
				FS::$iMgr->aeTableSubmit($clustername == "");

			return $output;
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none",
					in_array("none",$selected));
			}

			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId),"selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}

		protected function Load($clustername = "") {
			$this->clustername = $clustername;
			$this->description = "";
			$this->masterMembers = array();
			$this->slaveMembers = array();
			$this->cachingMembers = array();
			// Default options
			$this->recurseAcls = array("none");
			$this->transferAcls = array("none");
			$this->notifyAcls = array("none");
			$this->updateAcls = array("none");
			$this->queryAcls = array("any");

			if ($this->clustername) {
				$query = FS::$dbMgr->Select($this->sqlTable,"description",$this->sqlAttrId."= '".$this->clustername."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->description = $data["description"];

					$this->masterMembers = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_masters","server",
						$this->sqlAttrId." = '".$this->clustername."'");
					$this->slaveMembers = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_slaves","server",
						$this->sqlAttrId." = '".$this->clustername."'");
					$this->cachingMembers = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_caches","server",
						$this->sqlAttrId." = '".$this->clustername."'");

					$this->recurseAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->recurseAcls) == 0) {
						$this->recurseAcls = array("none");
					}

					$this->transferAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->transferAcls) == 0) {
						$this->transferAcls = array("none");
					}

					$this->notifyAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->notifyAcls) == 0) {
						$this->notifyAcls = array("none");
					}

					$this->updateAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_update","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->updateAcls) == 0) {
						$this->updateAcls = array("none");
					}

					$this->queryAcls = FS::$dbMgr->getArray(PgDbConfig::getDbPrefix()."dns_cluster_allow_query","aclname",
						$this->sqlAttrId." = '".$this->clustername."'");
					if(count($this->queryAcls) == 0) {
						$this->queryAcls = array("none");
					}

					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_masters",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_slaves",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_caches",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId." = '".$name."'");
			FS::$dbMgr->Delete($this->sqlTable,$this->sqlAttrId." = '".$name."'");
		}
		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$clustername = FS::$secMgr->checkAndSecurisePostData("clustername");
			$description = FS::$secMgr->checkAndSecurisePostData("description");
			$masters = FS::$secMgr->checkAndSecurisePostData("masters");
			$slaves = FS::$secMgr->checkAndSecurisePostData("slaves");
			$caches = FS::$secMgr->checkAndSecurisePostData("caches");
			$recurse = FS::$secMgr->checkAndSecurisePostData("recurse");
			$transfer = FS::$secMgr->checkAndSecurisePostData("transfer");
			$notify = FS::$secMgr->checkAndSecurisePostData("notify");
			$update = FS::$secMgr->checkAndSecurisePostData("update");
			$query = FS::$secMgr->checkAndSecurisePostData("query");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if (!$clustername || !$description || $masters && !is_array($masters) ||
				$slaves && !is_array($slaves) || $caches && !is_array($caches) ||
				$recurse && !is_array($recurse) || $transfer && !is_array($transfer) ||
				$notify && !is_array($notify) || $update && !is_array($update) ||
				$query && !is_array($query) || $edit && $edit != 1) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}
		
			// Verify cluster existence
			$exists = $this->exists($clustername);
			if ($edit) {
				if (!$exists) {
					$this->log(1,"Unable to edit cluster '".$clustername."': not exists");
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					$this->log(1,"Unable to add cluster '".$clustername."': already exists");
					FS::$iMgr->ajaxEcho($this->errAlreadyExists);
					return;
				}
			}

			$masterfound = false;
			// Verify servers (exist and no duplicates)
			if ($masters) {
				$count = count($masters);
				for ($i=0;$i<$count;$i++) {
					$server = new dnsServer();
					if (!$server->exists($masters[$i])) {
						FS::$iMgr->ajaxEcho($server->getErrNotExists());
						return;
					}
					if ($slaves) {
						if (in_array($masters[$i],$slaves)) {
							FS::$iMgr->ajaxEchoNC("err-cluster-member-only-one-category");
							return;
						}
					}
					if ($caches) {
						if (in_array($masters[$i],$caches)) {
							FS::$iMgr->ajaxEchoNC("err-cluster-member-only-one-category");
							return;
						}
					}
					$masterfound = true;
				}
			}

			// No master found, stop it.
			if (!$masterfound) {
				FS::$iMgr->ajaxEchoNC("err-cluster-need-master");
				return;
			}

			if ($slaves) {
				$count = count($slaves);
				for ($i=0;$i<$count;$i++) {
					$server = new dnsServer();
					if (!$server->exists($slaves[$i])) {
						FS::$iMgr->ajaxEcho($server->getErrNotExists());
						return;
					}
					// Slave - master already checked
					if ($caches) {
						if (in_array($slaves[$i],$caches)) {
							FS::$iMgr->ajaxEchoNC("err-cluster-member-only-one-category");
							return;
						}
					}
				}
			}

			if ($caches) {
				$count = count($caches);
				for ($i=0;$i<$count;$i++) {
					$server = new dnsServer();
					if (!$server->exists($caches[$i])) {
						FS::$iMgr->ajaxEcho($server->getErrNotExists());
						return;
					}
					// All duplicated have been checked at this time
				}
			}

			if ($recurse) {
				$count = count($recurse);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($recurse[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}

			if ($notify) {
				$count = count($notify);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($notify[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}
			if ($transfer) {
				$count = count($transfer);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($transfer[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}
			if ($query) {
				$count = count($query);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($query[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}
			if ($update) {
				$count = count($update);
				for ($i=0;$i<$count;$i++) {
					$acl = new dnsACL();
					if (!$acl->exists($update[$i])) {
						FS::$iMgr->ajaxEcho($acl->getErrNotExists());
						return;
					}
				}
			}

			FS::$dbMgr->BeginTr();
			if ($edit) {
				$this->removeFromDB($clustername);
			}
			FS::$dbMgr->Insert($this->sqlTable,$this->sqlAttrId.",description","'".$clustername."','".$description."'");

			$count = count($masters);
			for ($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_masters",$this->sqlAttrId.",server",
					"'".$clustername."','".$masters[$i]."'");
			}

			if ($slaves) {
				$count = count($slaves);
				for ($i=0;$i<$count;$i++) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_slaves",$this->sqlAttrId.",server",
						"'".$clustername."','".$slaves[$i]."'");
				}
			}

			if ($caches) {
				$count = count($caches);
				for ($i=0;$i<$count;$i++) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_caches",$this->sqlAttrId.",server",
						"'".$clustername."','".$caches[$i]."'");
				}
			}

			if ($recurse) {
				if (in_array("none",$recurse)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$recurse)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($recurse);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_recurse",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$recurse[$i]."'");
					}
				}
			}

			if ($transfer) {
				if (in_array("none",$transfer)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$transfer)) {

					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($tranfer);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_transfer",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$transfer[$i]."'");
					}
				}
			}

			if ($notify) {
				if (in_array("none",$notify)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$notify)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($notify);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_notify",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$notify[$i]."'");
					}
				}
			}

			if ($update) {
				if (in_array("none",$update)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$update)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($update);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_update",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$update[$i]."'");
					}
				}
			}

			if ($query) {
				if (in_array("none",$query)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId.",aclname",
						"'".$clustername."','none'");
				}
				else if (in_array("any",$query)) {
					FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId.",aclname",
						"'".$clustername."','any'");
				}
				else {
					$count = count($query);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PgDbConfig::getDbPrefix()."dns_cluster_allow_query",$this->sqlAttrId.",aclname",
							"'".$clustername."','".$query[$i]."'");
					}
				}
			}

			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($clustername,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$clustername = FS::$secMgr->checkAndSecuriseGetData("clustername");

			if (!$clustername) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if (!$this->exists($clustername)) {
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}

			FS::$dbMgr->BeginTr();
			$this->removeFromDb($clustername);
			FS::$dbMgr->CommitTr();

			$this->log(0,"Removing cluster '".$clustername."'");

			$js = $this->tMgr->removeLine($clustername);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		private $clustername;
		private $description;
		private $masterMembers;
		private $slaveMembers;
		private $cachingMembers;
		private $recurseAcls;
		private $transferAcls;
		private $notifyAcls;
		private $updateAcls;
		private $queryAcls;
	}

			
	final class dnsServer extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_servers";
			$this->sqlAttrId = "addr";
			$this->readRight = "mrule_dnsmgmt_read";
			$this->writeRight = "mrule_dnsmgmt_write";
			$this->errNotExists = "err-server-not-exists";
			$this->errAlreadyExists = "err-server-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "dnssrv",
				"sqltable" => "dns_servers",
				"sqlattrid" => "addr",
				"attrlist" => array(array("Addr","addr",""), array("Login","sshuser",""), array("named-conf-path","namedpath",""),
					array("machine-FQDN","nsfqdn","")),
				"sorted" => true,
				"odivnb" => 2,
				"odivlink" => "addr=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=4&addr",
				"rmconfirm" => "confirm-remove-server",
			));
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(1,$this->loc->s("add-server"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($addr = "") { 
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}

			if (!$this->Load($addr)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}

			$output = FS::$iMgr->cbkForm("3").
				FS::$iMgr->tip("tip-dnsserver")."<table>".
				FS::$iMgr->idxLines(array(
					array("ip-addr","saddr",array("type" => "idxedit", "value" => $this->addr,
						"length" => "128", "edit" => $this->addr != "")),
					array("ssh-user","slogin",array("value" => $this->sshUser)),
					array("Password","spwd",array("type" => "pwd")),
					array("Password-repeat","spwd2",array("type" => "pwd")),
					array("named-conf-path","namedpath",array("value" => $this->namedPath,"tooltip" => "tooltip-rights")),
					array("chroot-path","chrootnamed",array("value" => $this->chrootPath,"tooltip" => "tooltip-chroot")),
					array("machine-FQDN","nsfqdn",array("value" => $this->machineFQDN,"tooltip" => "tooltip-machine-FQDN",
						"star" => 1)),
					array("named-zeye-path","zeyenamedpath",array("value" => $this->zeyeNamedPath,"tooltip" => "tooltip-zeyenamed-path",
						"star" => 1)),
					array("masterzone-path","mzonepath",array("value" => $this->masterZonePath,"tooltip" => "tooltip-masterzone-path",
						"star" => 1)),
					array("slavezone-path","szonepath",array("value" => $this->slaveZonePath,"tooltip" => "tooltip-slavezone-path",
						"star" => 1))
				)).
				FS::$iMgr->aeTableSubmit($addr == "");
			
			return $output;
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none",
					in_array("none",$selected));
			}

			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId),"selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}

		protected function Load($addr = "") {
			$this->addr = $addr;
			$this->sshUser = ""; $this->namedPath = ""; $this->chrootPath = "";
			$this->masterZonePath = ""; $this->slaveZonePath = "";
			$this->machineFQDN = "";

			if ($this->addr) {
				$query = FS::$dbMgr->Select($this->sqlTable,"sshuser,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn","addr = '".$addr."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->sshUser = $data["sshuser"];
					$this->namedPath = $data["namedpath"];
					$this->chrootPath = $data["chrootpath"];
					$this->zeyeNamedPath = $data["zeyenamedpath"];
					$this->masterZonePath = $data["mzonepath"];
					$this->slaveZonePath = $data["szonepath"];
					$this->machineFQDN = $data["nsfqdn"];
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"addr = '".$name."'");
			FS::$dbMgr->CommitTr();
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
			$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
			$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
			$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
			$namedpath = FS::$secMgr->checkAndSecurisePostData("namedpath");
			$chrootnamed = FS::$secMgr->checkAndSecurisePostData("chrootnamed");
			$machineFQDN = FS::$secMgr->checkAndSecurisePostData("nsfqdn");
			$zeyenamedpath = FS::$secMgr->checkAndSecurisePostData("zeyenamedpath");
			$mzonepath = FS::$secMgr->checkAndSecurisePostData("mzonepath");
			$szonepath = FS::$secMgr->checkAndSecurisePostData("szonepath");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if (!$saddr || !FS::$secMgr->isIP($saddr) || !$slogin || !$spwd || !$spwd2 || $spwd != $spwd2 ||
				!$namedpath || !FS::$secMgr->isPath($namedpath) ||
					($chrootnamed && !FS::$secMgr->isPath($chrootnamed)) ||
					($zeyenamedpath && !FS::$secMgr->isPath($zeyenamedpath)) ||
					($mzonepath && !FS::$secMgr->isPath($chrootnamed.$mzonepath)) ||
					($szonepath && !FS::$secMgr->isPath($chrootnamed.$szonepath)) ||
					($machineFQDN && !FS::$secMgr->isDNSName($machineFQDN))
				) {
				$this->log(2,"Some datas are invalid or wrong for add server");
				FS::$iMgr->ajaxEcho("err-miss-bad-fields");
				return;
			}

			if (($zeyenamedpath && (!$mzonepath || !$szonepath || !$machineFQDN)) ||
				($mzonepath && (!$zeyenamedpath || !$szonepath || !$machineFQDN)) ||
				($szonepath && (!$zeyenamedpath || !$mzonepath || !$machineFQDN)) ||
				($machineFQDN && (!$mzonepath || !$szonepath || !$zeyenamedpath))) {
				FS::$iMgr->ajaxEchoNC("err-zeyenamedpath-together");
				return;
			}

			if ($zeyenamedpath == $namedpath) {
				FS::$iMgr->ajaxEchoNC("err-named-zeyenamed-different");
				return;
			}

			$ssh = new SSH($saddr);
			if (!$ssh->Connect()) {
				FS::$iMgr->ajaxEcho("err-unable-conn");
				return;
			}
			if (!$ssh->Authenticate($slogin,$spwd)) {
				FS::$iMgr->ajaxEchoNC("err-bad-login");
				return;
			}
		
			$exists = $this->exists($saddr);
			if ($edit) {	
				if (!$exists) {
					$this->log(1,"Unable to edit server '".$saddr."': not exists");
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}
			else {
				if ($exists) {
					$this->log(1,"Unable to add server '".$saddr."': already exists");
					FS::$iMgr->ajaxEcho($this->errAlreadyExists);
					return;
				}
			}

			FS::$dbMgr->BeginTr();

			if ($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"addr = '".$saddr."'");
			}
			FS::$dbMgr->Insert($this->sqlTable,"addr,sshuser,sshpwd,namedpath,chrootpath,mzonepath,szonepath,zeyenamedpath,nsfqdn",
				"'".$saddr."','".$slogin."','".$spwd."','".$namedpath."','".$chrootnamed."','".$mzonepath.
				"','".$szonepath."','".$zeyenamedpath."','".$machineFQDN."'");

			FS::$dbMgr->CommitTr();

			$this->log(0,"Add/Edit server '".$saddr."'");

			$js = $this->tMgr->addLine($saddr,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
			
			if (!$addr) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if (!$this->exists($addr)) {
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}
			
			$this->removeFromDB($addr);
			$this->log(0,"Removing server '".$addr."'");

			$js = $this->tMgr->removeLine($addr);
			FS::$iMgr->ajaxEcho("Done",$js);
		}
		private $addr;
		private $sshUser;
		private $chrootPath;
		private $namedPath;
		private $machineFQDN;
		private $zeyeNamedPath;
		private $masterZonePath;
		private $slaveZonePath;
	};

	final class dnsTSIGKey extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dns_tsig";
			$this->sqlAttrId = "keyalias";
			$this->readRight = "mrule_dnsmgmt_read";
			$this->writeRight = "mrule_dnsmgmt_write";
			$this->errNotExists = "err-tsig-key-not-exists";
			$this->errAlreadyExists = "err-tsig-key-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "tsig",
				"sqltable" => "dns_tsig",
				"sqlattrid" => "keyalias",
				"attrlist" => array(array("key-alias","keyalias",""), array("key-id","keyid",""),
					array("algorithm","keyalgo","sr",array(1 => "HMAC-MD5", 2 => "HMAC-SHA1", 3 => "HMAC-SHA256")),
					array("Value","keyvalue","")),
				"sorted" => true,
				"odivnb" => 4,
				"odivlink" => "keyalias=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=6&keyalias",
				"rmconfirm" => "confirm-remove-tsig",
			));
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? $this->sqlAttrId." != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none",
					in_array("none",$selected));
			}

			$found = false;
			$elements = FS::$iMgr->selElmtFromDB($this->sqlTable,$this->sqlAttrId,array("sqlcond" => $sqlcond,
				"sqlopts" => array("order" => $this->sqlAttrId), "selected" => $selected));
			if ($elements == "" && $none == false) {
				return NULL;
			}
				
			$output .= $elements."</select>";
			return $output;
		}

		protected function Load($name = "") {
			$this->name = $name;
			$this->keyid = ""; $this->keyvalue = ""; $this->keyalgo = "";

			if ($this->name) {
				if ($data = FS::$dbMgr->GetOneEntry($this->sqlTable,"keyid,keyalgo,keyvalue",
					"keyalias = '".$name."'")) {
					$this->keyid = $data["keyid"];
					$this->keyalgo = $data["keyalgo"];
					$this->keyvalue = $data["keyvalue"];
					return true;
				}
				return false;
			}
			return true;
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"keyalias = '".$name."'");
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."dns_acl_tsig","keyalias = '".$aclname."'");
			FS::$dbMgr->CommitTr();
		}

		public function renderAll() {
			$output = FS::$iMgr->opendiv(3,$this->loc->s("define-tsig-key"),array("line" => true));
			$output .= $this->tMgr->render();
			return $output;
		}

		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}

			if (!$this->Load($name)) {
				return FS::$iMgr->printError($this->loc->s($this->errNotExists));
			}

			$output = FS::$iMgr->cbkForm("5")."<table>".
				FS::$iMgr->idxLines(array(
					array("key-alias","keyalias",array("value" => $this->name, "type" => "idxedit", "length" => 64,
						"edit" => $this->name != "")),
					array("key-id","keyid",array("length" => 32, "value" => $this->keyid)),
					array("algorithm","",array("type" => "raw", "value" => FS::$iMgr->select("keyalgo").
						FS::$iMgr->selElmt("HMAC-MD5",1,$this->keyalgo == 1).FS::$iMgr->selElmt("HMAC-SHA1",2,$this->keyalgo == 2).
						FS::$iMgr->selElmt("HMAC-SHA256",3,$this->keyalgo == 3)."</select>")),
					array("Value","keyvalue",array("length" => 128, "size" => 30, "value" => $this->keyvalue))
				)).
				FS::$iMgr->aeTableSubmit($this->name == "");

			return $output;
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$keyalias = FS::$secMgr->checkAndSecurisePostData("keyalias");
			$keyid = FS::$secMgr->checkAndSecurisePostData("keyid");
			$keyalgo = FS::$secMgr->checkAndSecurisePostData("keyalgo");
			$keyvalue = FS::$secMgr->checkAndSecurisePostData("keyvalue");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if (!$keyalias || !$keyid || !$keyalgo || !FS::$secMgr->isNumeric($keyalgo) || !$keyvalue ||
				$edit && $edit != 1) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}

			if (!FS::$secMgr->isBase64($keyvalue)) {
				FS::$iMgr->ajaxEchoNC("err-tsig-not-base64");
				return;
			}

			$exist = $this->exists($keyalias);
			if ($edit) {
				if (!$exist) {
					FS::$iMgr->ajaxEcho($this->errNotExists);
					return;
				}
			}
			else {
				if ($exist) {
					FS::$iMgr->ajaxEcho($this->errAlreadyExists);
					return;
				}
				$exist = FS::$dbMgr->GetOneEntry($this->sqlTable,"keyalias","keyid = '".$keyid.
					"' AND keyalgo = '".$keyalgo."' AND keyvalue = '".$keyvalue."'");
				if ($exist) {
					FS::$iMgr->ajaxEcho("err-tsig-key-exactly-same");
					return;
				}
			}
			
			if (!FS::$secMgr->isHostname($keyid)) {
				FS::$iMgr->ajaxEcho("err-tsig-key-id-invalid");
				return;
			}

			if ($keyalgo < 1 || $keyalgo > 3) {
				FS::$iMgr->ajaxecho("err-tsig-key-algo-invalid");
				return;
			}

			FS::$dbMgr->BeginTr();
			if ($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"keyalias = '".$keyalias."'");
			}
			FS::$dbMgr->Insert($this->sqlTable,"keyalias,keyid,keyalgo,keyvalue","'".$keyalias."','".
				$keyid."','".$keyalgo."','".$keyvalue."'");
			FS::$dbMgr->CommitTr();

			$js = $this->tMgr->addLine($keyalias,$edit);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 
			$keyalias = FS::$secMgr->checkAndSecuriseGetData("keyalias");
			if (!$keyalias) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}
			
			if (!$this->exists($keyalias)) {
				FS::$iMgr->ajaxEcho($this->errNotExists);
				return;
			}

			$this->removeFromDB($keyalias);

			$js = $this->tMgr->removeLine($keyalias);
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		private $name;
		private $keyid;
		private $keyvalue;
		private $keyalgo;
	};
?>