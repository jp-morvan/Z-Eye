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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iLogs extends genModule{
		function iLogs() { parent::genModule(); }
		public function Load() {
			$output = $this->showLogs();
			return $output;
		}

		private function showLogs() {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
                        if(!FS::isAjaxCall()) {
                                $output .= "<div id=\"contenttabs\"><ul>";
                                $output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2\">Statistiques</a>";
                                $output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&sh=2\">Collecteur Z_Eye</a>";
                                $output .= "</ul></div>";
                                $output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
                                $output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
                        }
			else if(!$sh || $sh == 1) {
				$output = "";
			}
			else if($sh == 2) {
				$output = "<pre>";
				$fp = fopen(dirname(__FILE__)."/../../../datas/logs/z_eye_collector.log","r");
				fseek($fp,-(sizeof('a')), SEEK_END);
				$linecount = 30;
				$fileoutput = "";
				$line = "";
				while(ftell($fp) > 0 && $linecount > 0) {
					$chr = fgetc($fp);
					if($chr == "\n") {
						$fileoutput .= $line."<br />";
						$line = "";
						$linecount--;
					}
					else
						$line = $chr.$line;
					fseek($fp, -(sizeof('a') * 2), SEEK_CUR);
				}
				fclose($fp);
				$output .= preg_replace("#\[Z\-Eye\]#","",$fileoutput);
				$output .= "</pre>";
			}
			return $output;
		}
	};
?>