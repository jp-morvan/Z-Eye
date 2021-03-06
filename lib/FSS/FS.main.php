<?php
	/*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
        */

	define('CLASS_EXT','.FS.class.php');

	require_once(dirname(__FILE__)."/Logs".CLASS_EXT);
	require_once(dirname(__FILE__)."/AbstractSQLMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/SecurityMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/modules/MailMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/InterfaceMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/modules/FileMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/../../modules/LocalInterface.class.php");
	require_once(dirname(__FILE__)."/../../modules/Ajax.class.php");
	require_once(dirname(__FILE__)."/SessionMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/Module".CLASS_EXT);

	if(Config::enableSNMP())
		require_once(dirname(__FILE__)."/SNMP".CLASS_EXT);

	class FS {
		function FS() {}

		public static function LoadFSModules() {
			// AbstractSQL connector
			FS::$dbMgr = new AbstractSQLMgr();
			FS::$dbMgr->initForZEye();
			FS::$dbMgr->Connect();

			// Load Security Manager
			FS::$secMgr = new FSSecurityMgr();

			// Load Interface Manager
			FS::$iMgr = new LocalInterface();

			// Load Session Manager
			FS::$sessMgr = new FSSessionMgr();

			// Load Mail Manager
			FS::$mailMgr = new FSMailMgr();

			// Load File Mgr
			FS::$fileMgr = new FSFileMgr();

			// Load Ajax Mgr
			FS::$ajaxMgr = new AjaxManager();

			// Load SNMP Mgr
			if(Config::enableSNMP()) {
				FS::$snmpMgr = new SNMPMgr();
			}

			// Load logger
			FS::$log = new FSLogger();
		}

		public static function isAjaxCall() {
			if(FS::$secMgr->checkAndSecuriseGetData("at"))
				return true;

			return false;
		}

		public static function isActionToDo() {
			if(isset($_GET["act"]) && strlen($_GET["act"]) > 0 && FS::$secMgr->isNumeric($_GET["act"])) {
				FS::$secMgr->SecuriseStringForDB($_GET["act"]);
				return true;
			}
			return false;
		}

		public static $fileMgr;
		public static $dbMgr;
		public static $secMgr;
		public static $iMgr;
		public static $sessMgr;
		public static $mailMgr;
		public static $ajaxMgr;
		public static $snmpMgr;
		public static $log;
	};
?>
