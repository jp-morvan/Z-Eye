<?php
	/*
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
	require_once(dirname(__FILE__)."/../../../lib/FSS/PDFgen.FS.class.php");
	class iRadius extends genModule{
		function iRadius() { parent::genModule(); }
		public function Load() {
			$radalias = FS::$secMgr->checkAndSecuriseGetData("ra");
			$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
			$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
			$radport = FS::$secMgr->checkAndSecuriseGetData("p");
			$rad = $raddb."@".$radhost.":".$radport;
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			if($err && FS::$secMgr->isNumeric($err)) {
				switch($err) {
					case 1: $output = FS::$iMgr->printError("Serveur radius non référencé !"); break;
					case 2: $output = FS::$iMgr->printError("Certaines données entrées sont manquantes ou invalides !"); break;
					case 3: $output = FS::$iMgr->printError("Le groupe/utilisateur inscrit est déjà référencé !"); break;
					case 4: $output = FS::$iMgr->printError("Echec de la suppression, données invalides !"); break;
					case 5: $output = FS::$iMgr->printError("Certains utilisateurs n'ont pas été ajoutés car déjà existants !"); break;
					case 6: $output = FS::$iMgr->printError("La table référencée n'est pas valide !"); break;
				}
			}
			else
				$output = "";
			if(!FS::isAjaxCall()) {
				$found = 0;
				if(FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
					$output .= "<h4>Outil de délégation de l'authentification</h4>";
					$tmpoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1").FS::$iMgr->addList("radius","submit()");
                                        $query = FS::$pgdbMgr->Select("z_eye_radius_db_list","addr,port,dbname,radalias");
                                        while($data = pg_fetch_array($query)) {
                                                if($found == 0) $found = 1;
                                                $radpath = $data["dbname"]."@".$data["addr"].":".$data["port"];
                                                $tmpoutput .= FS::$iMgr->addElementToList($data["radalias"],$radpath,$rad == $radpath);
                                        }
                                        if($found) $output .= $tmpoutput."</select> ".FS::$iMgr->submit("","Gérer")."</form>";
                                        else $output .= FS::$iMgr->printError("Aucun serveur radius référencé");
				}
				else {
					$output .= "<h4>Gestion des utilisateurs/Groupes Radius</h4>";
					$tmpoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1").FS::$iMgr->addList("radius","submit()");
					$query = FS::$pgdbMgr->Select("z_eye_radius_db_list","addr,port,dbname");
	                	        while($data = pg_fetch_array($query)) {
						if($found == 0) $found = 1;
						$radpath = $data["dbname"]."@".$data["addr"].":".$data["port"];
						$tmpoutput .= FS::$iMgr->addElementToList($radpath,$radpath,$rad == $radpath);
					}
					if($found) $output .= $tmpoutput."</select> ".FS::$iMgr->submit("","Administrer")."</form>";
					else $output .= FS::$iMgr->printError("Aucun serveur radius référencé");
				}
			}
			if($raddb && $radhost && $radport) {
				if(FS::$sessMgr->hasRight("mrule_radius_deleg") && FS::$sessMgr->getUid() != 1) {
					$output .= $this->showDelegTool($radalias,$raddb,$radhost,$radport);
				}
				else {
					$radentry = FS::$secMgr->checkAndSecuriseGetData("radentry");
					$radentrytype = FS::$secMgr->checkAndSecuriseGetData("radentrytype");
					if($radentry && $radentrytype && ($radentrytype == 1 || $radentrytype == 2))
						$output .= $this->editRadiusEntry($raddb,$radhost,$radport,$radentry,$radentrytype);
					else
						$output .= $this->showRadiusDatas($raddb,$radhost,$radport);
				}
			}
			return $output;
		}

		private function showDelegTool($radalias,$raddb,$radhost,$radport) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Compte individuel",$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Comptes en masse",$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
                                $output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
			}
			else if(!$sh || $sh == 1) {
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$output .= "<div id=\"adduserres\"></div>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10","adduser");
				$output .= "<table><tr><th>Intitulé</th><th>Valeur</th></tr>";
				$output .= FS::$iMgr->addIndexedLine("Nom *","radname");
				$output .= FS::$iMgr->addIndexedLine("Prénom *","radsurname");
				$output .= FS::$iMgr->addIndexedline("Identifiant de connexion *","radusername");
				$output .= "<tr><td>Profil</td><td>".FS::$iMgr->addList("profil","","").FS::$iMgr->addElementToList("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
				$output .= "<tr><td>Validité</td><td>".FS::$iMgr->radioList("validity",array(1,2),array("Toujours valide","Période"),1);
				$output .= FS::$iMgr->calendar("startdate","","Du")."<br />";
				$output .= FS::$iMgr->hourlist("limhours","limmins")."<br />";
				$output .= FS::$iMgr->calendar("enddate","","Au")."<br />";
				$output .= FS::$iMgr->hourlist("limhoure","limmine",23,59);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->addTableSubmit("","Enregistrer")."</table></form>";

				$output .= "<script type=\"text/javascript\">$('#adduser').submit(function(event) {
					event.preventDefault();
					$.post('index.php?mod=".$this->mid."&at=3&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10', $('#adduser').serialize(), function(data) {
						$('#adduserres').html(data);
					});
				});</script>";
			}
			else if($sh == 2) {
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$output .= "<div id=\"adduserlistres\"></div>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=11","adduserlist");
                                $output .= "<table><tr><th>Intitulé</th><th>Valeur</th></tr>";
				$output .= "<tr><td>Type de génération</td><td style=\"text-align: left;\">".FS::$iMgr->radio("typegen",1,false,"Nom aléatoire")."<br />".FS::$iMgr->radio("typegen",2,false,"Préfixe ").FS::$iMgr->input("prefix","")."</td></tr>";
                                $output .= FS::$iMgr->addIndexedNumericLine("Nombre de comptes *","nbacct","",4,4);
                                $output .= "<tr><td>Profil</td><td>".FS::$iMgr->addList("profil2","","").FS::$iMgr->addElementToList("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
                                $output .= "<tr><td>Validité</td><td>".FS::$iMgr->radioList("validity2",array(1,2),array("Toujours valide","Période"),1);
                                $output .= FS::$iMgr->calendar("startdate2","","Du")."<br />";
				$output .= FS::$iMgr->hourlist("limhours2","limmins2")."<br />";
                                $output .= FS::$iMgr->calendar("enddate2","","Au")."<br />";
                                $output .= FS::$iMgr->hourlist("limhoure2","limmine2",23,59);
                                $output .= "</td></tr>";
                                $output .= FS::$iMgr->addTableSubmit("","Enregistrer")."</table></form>";
			}
			else
				$output .= FS::$iMgr->printError("Onglet invalide !");

			return $output;
		}

		private function showRadiusDatas($raddb,$radhost,$radport) {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Utilisateurs",$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Profils",$sh);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Import de masse",$sh);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Import Auto DHCP",$sh);
				$output .= FS::$iMgr->tabPanElmt(6,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Compte individuel (Deleg)",$sh);
				$output .= FS::$iMgr->tabPanElmt(7,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Comptes en masse (Deleg)",$sh);
				$output .= FS::$iMgr->tabPanElmt(5,"index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport,"Options avancées",$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"Unable to load tab, link may be wrong or page unavailable\");}}});</script>";
			}
			else if(!$sh || $sh == 1) {
                                $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();
				$formoutput = "<script type=\"text/javascript\"> function changeUForm() {
					if(document.getElementsByName('utype')[0].value == 1) {
						$('#userdf').show();
					}
					else if(document.getElementsByName('utype')[0].value == 2) {
						$('#userdf').hide();
					}
					else if(document.getElementsByName('utype')[0].value == 3) {
						$('#userdf').hide();
					}
				}; grpidx = 0; function addGrpForm() {
					$('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->addList("ugroup'+grpidx+'","","Profil").FS::$iMgr->addElementToList("","none").$this->addGroupList($radSQLMgr)."</select>";
				$formoutput .= " <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
					grpidx++;
				}
				function delGrpElmt(grpidx) {
                                        $('.ugroupli'+grpidx).remove();
                                }
				attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Op ".FS::$iMgr->addList("attrop'+attridx+'").
				FS::$iMgr->addElementToList("=","=").
				FS::$iMgr->addElementToList("==","==").
				FS::$iMgr->addElementToList(":=",":=").
				FS::$iMgr->addElementToList("+=","+=").
				FS::$iMgr->addElementToList("!=","!=").
                                FS::$iMgr->addElementToList(">",">").
                                FS::$iMgr->addElementToList(">=",">=").
                                FS::$iMgr->addElementToList("<","<").
				FS::$iMgr->addElementToList("<=","<=").
                                FS::$iMgr->addElementToList("=~","=~").
                                FS::$iMgr->addElementToList("!~","!~").
                                FS::$iMgr->addElementToList("=*","=*").
				FS::$iMgr->addElementToList("!*","!*").
				"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40,"")." Cible ".FS::$iMgr->addList("attrtarget'+attridx+'").
				FS::$iMgr->addElementToList("check",1).
				FS::$iMgr->addElementToList("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertBefore('#formactions');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}</script>";

				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=2");
				$formoutput .= "<ul class=\"ulform\"><li>".
				FS::$iMgr->addList("utype","changeUForm()","Type d'authentification");
				$formoutput .= FS::$iMgr->addElementToList("Utilisateur",1);
				$formoutput .= FS::$iMgr->addElementToList("Adresse MAC",2);
				//$formoutput .= FS::$iMgr->addElementToList("Code PIN",3);
				$formoutput .= "</select></li><li>".
				FS::$iMgr->input("username","",20,40,"Utilisateur")."</li><li>";
				$formoutput .= "<fieldset id=\"userdf\" style=\"border:0; padding:0; margin-left: -1px;\"><li>".
				FS::$iMgr->password("pwd","","Mot de passe")."</li><li>".
				FS::$iMgr->addList("upwdtype","","Type de mot de passe").
				FS::$iMgr->addElementToList("Cleartext-Password",1).
				FS::$iMgr->addElementToList("User-Password",2).
				FS::$iMgr->addElementToList("Crypt-Password",3).
				FS::$iMgr->addElementToList("MD5-Password",4).
				FS::$iMgr->addElementToList("SHA1-Password",5).
				FS::$iMgr->addElementToList("CHAP-Password",6).
				"</select></li></li><li id=\"formactions\">".FS::$iMgr->button("newgrp","Nouveau Groupe","addGrpForm()").
				FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").
				FS::$iMgr->submit("","Enregistrer")."</li></ul></form>";
				$output .= FS::$iMgr->opendiv($formoutput,"Nouvel Utilisateur");
				$found = 0;
				$tmpoutput = "<h4>Liste des Utilisateurs</h4>";
				$tmpoutput .= "<script type=\"text/javascript\">
				$.event.props.push('dataTransfer');
				$('#raduser #dragtd').on({
					mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
					mouseleave: function(e) { $('#trash').hide(); $('#editf').hide(); },
					dragstart: function(e) { $('#trash').show(); $('#editf').show(); e.dataTransfer.setData('text/html', $(this).text()); },
					dragenter: function(e) { e.preventDefault();},
					dragover: function(e) { e.preventDefault(); },
					dragleave: function(e) { },
					drop: function(e) {},
					dragend: function() { $('#trash').hide(); $('#editf').hide();}
				});
				$('#trash').on({
					dragover: function(e) { e.preventDefault(); },
					drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer l\'utilisateur \''+e.dataTransfer.getData('text/html')+'\' ?".
						FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=4").
						FS::$iMgr->addHidden("user","'+e.dataTransfer.getData('text/html')+'").
						FS::$iMgr->addCheck("logdel",false,"Supprimer les logs ?")."<br />".
						FS::$iMgr->addCheck("acctdel",false,"Supprimer l\\'accounting ?")."<br />".
						FS::$iMgr->submit("","Supprimer").
						FS::$iMgr->button("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
						$('#pop').show();
					}
				});
				$('#editf').on({
					dragover: function(e) { e.preventDefault(); },
					drop: function(e) { $(location).attr('href','index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&radentrytype=1&radentry='+e.dataTransfer.getData('text/html')); }
				});
				</script>";
                                $query = $radSQLMgr->Select("radcheck","id,username,value","attribute IN ('Auth-Type','Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')");
				$expirationbuffer = array();
				while($data = mysql_fetch_array($query)) {
					if(!$found) {
                                                $found = 1;
                                                $tmpoutput .= "<table id=\"raduser\" style=\"width:70%\"><tr><th>Id</th><th>Utilisateur</th><th>Mot de passe</th><th>Groupes</th><th>Date d'expiration</th></tr>";
						$query2 = $radSQLMgr->Select("z_eye_radusers","username,expiration","expiration > 0");
						while($data2 = mysql_fetch_array($query2)) {
							if(!isset($expirationbuffer[$data2["username"]])) $expirationbuffer[$data2["username"]] = date("d/m/y h:i",strtotime($data2["expiration"]));
						}
                                        }
                                        $tmpoutput .= "<tr><td>".$data["id"]."</td><td id=\"dragtd\" draggable=\"true\"><a href=\"index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&radentrytype=1&radentry=".$data["username"]."\">".$data["username"]."</a></td><td>".$data["value"]."</td><td>";
					$query2 = $radSQLMgr->Select("radusergroup","groupname","username = '".$data["username"]."'");
					$found2 = 0;
					while($data2 = mysql_fetch_array($query2)) {
						if($found2 == 0) $found2 = 1;
						else $tmpoutput .= "<br />";
						$tmpoutput .= $data2["groupname"];
					}
                                        $tmpoutput .= "</td><td>";

					$tmpoutput .= (isset($expirationbuffer[$data["username"]]) ? $expirationbuffer[$data["username"]] : "Jamais");
					$tmpoutput .= "</td></tr>";
				}
				if($found) $output .= $tmpoutput."</table>";
			}
			else if($sh == 2) {
				$formoutput = "<script type=\"text/javascript\">attridx = 0; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Op ".FS::$iMgr->addList("attrop'+attridx+'").
				FS::$iMgr->addElementToList("=","=").
				FS::$iMgr->addElementToList("==","==").
				FS::$iMgr->addElementToList(":=",":=").
				FS::$iMgr->addElementToList("+=","+=").
				FS::$iMgr->addElementToList("!=","!=").
                                FS::$iMgr->addElementToList(">",">").
                                FS::$iMgr->addElementToList(">=",">=").
                                FS::$iMgr->addElementToList("<","<").
				FS::$iMgr->addElementToList("<=","<=").
                                FS::$iMgr->addElementToList("=~","=~").
                                FS::$iMgr->addElementToList("!~","!~").
                                FS::$iMgr->addElementToList("=*","=*").
				FS::$iMgr->addElementToList("!*","!*").
				"</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40)." Cible ".FS::$iMgr->addList("attrtarget'+attridx+'").
				FS::$iMgr->addElementToList("check",1).
				FS::$iMgr->addElementToList("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertAfter('#groupname');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}
				function addTemplAttributes() {
					switch($('#radgrptpl').val()) {
						case '1':
							addAttrElmt('Tunnel-Private-Group-Id','','=','2');
							addAttrElmt('Tunnel-Type','13','=','2');
							addAttrElmt('Tunnel-Medium-Type','6','=','2');
							break;
					}
				};
				$.event.props.push('dataTransfer');
                                $('#radgrp #dragtd').on({
					mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
                                        mouseleave: function(e) { $('#trash').hide(); $('#editf').hide(); },
                                        dragstart: function(e) { $('#trash').show(); $('#editf').show(); e.dataTransfer.setData('text/html', $(this).text()); },
                                        dragenter: function(e) { e.preventDefault();},
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) {},
                                        dragend: function() { $('#trash').hide(); $('#editf').hide(); }
                                });
				$('#trash').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer le profil \''+e.dataTransfer.getData('text/html')+'\' ?".
						FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=5").
                                                FS::$iMgr->addHidden("group","'+e.dataTransfer.getData('text/html')+'").
                                                FS::$iMgr->submit("","Supprimer").
                                                FS::$iMgr->button("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
                                                $('#pop').show();
                                        }
                                });
				$('#editf').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $(location).attr('href','index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&radentrytype=2&radentry='+e.dataTransfer.getData('text/html')); }
                                });
				</script>";
				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=3");
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->addList("radgrptpl","addTemplAttributes()","Template");
				$formoutput .= FS::$iMgr->addElementToList("Aucun",0);
				$formoutput .= FS::$iMgr->addElementToList("VLAN",1);
				$formoutput .= "</select></li><li>".
				FS::$iMgr->input("groupname","",20,40,"Nom du profil")."</li><li>".
				FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").
				FS::$iMgr->submit("","Enregistrer").
				"</li></ul></form>";
				$output .= FS::$iMgr->opendiv($formoutput,"Nouveau profil");
				$tmpoutput = "<h4>Liste des profils</h4>";
				$found = 0;
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
				$radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$groups=array();
				$query = $radSQLMgr->Select("radgroupreply","distinct groupname");
				while($data = mysql_fetch_array($query)) {
					$rcount = $radSQLMgr->Count("radusergroup","distinct username","groupname = '".$data["groupname"]."'");
					if(!isset($groups[$data["groupname"]]))
						$groups[$data["groupname"]] = $rcount;
				}

				$query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                                while($data = mysql_fetch_array($query)) {
                                        $rcount = $radSQLMgr->Count("radusergroup","distinct username","groupname = '".$data["groupname"]."'");
                                        if(!isset($groups[$data["groupname"]]))
                                                $groups[$data["groupname"]] = $rcount;
					else
						$groups[$data["groupname"]] += $rcount;
                                }
				if(count($groups) > 0) {
					$output .= "<table id=\"radgrp\" style=\"width:30%;\"><tr><th>Groupe</th><th style=\"width:30%\">Nombre d'utilisateurs</th></tr>";
					foreach($groups as $key => $value)
						$output .= "<tr><td id=\"dragtd\" draggable=\"true\">".$key."</td><td>".$value."</td></tr>";
					$output .= "</table>";
				}
			}
			else if($sh == 3) {
                                $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();
				$grouplist= FS::$iMgr->addElementToList("","none");
                                $groups=array();
                                $query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                                while($data = mysql_fetch_array($query)) {
                                        if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
                                }
                                $query = $radSQLMgr->Select("radgroupreply","distinct groupname");
                                while($data = mysql_fetch_array($query)) {
                                        if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
                                }
                                for($i=0;$i<count($groups);$i++) {
                                        $grouplist .= FS::$iMgr->addElementToList($groups[$i],$groups[$i]);
                                }

				$output .= "<h4>Import d'utilisateurs en masse</h4>";
				$output .= "<script type=\"text/javascript\"> function changeUForm() {
                                        if(document.getElementsByName('usertype')[0].value == 1) {
                                                $('#uptype').show(); $('#csvtooltip').html(\"<b>Note: </b>Les noms d'utilisateurs ne peuvent pas contenir d'espace.<br />Les mots de passe doivent être en clair.<br />Caractère de formatage: <b>,</b>\");
                                        }
                                        else if(document.getElementsByName('usertype')[0].value == 2) {
                                                $('#uptype').hide(); $('#csvtooltip').html('<b>Note: </b> Les adresses MAC peuvent être de la forme <b>aa:bb:cc:dd:ee:ff</b>, <b>aa-bb-cc-dd-ee-ff</b> ou <b>aabbccddeeff</b> et ne sont pas sensibles à la casse.');
                                        }
                                        else if(document.getElementsByName('usertype')[0].value == 3) {
                                                $('#uptype').hide(); $('#csvtooltip').html('');
                                        }
                                }; </script>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=6"); // #todo change this
                                $output .= "<ul class=\"ulform\"><li width=\"100%\">".FS::$iMgr->addList("usertype","changeUForm()","Type d'authentification");
                                $output .= FS::$iMgr->addElementToList("Utilisateur",1);
                                $output .= FS::$iMgr->addElementToList("Adresse MAC",2);
                                //$formoutput .= FS::$iMgr->addElementToList("Code PIN",3);
                                $output .= "</select></li><li id=\"uptype\">".FS::$iMgr->addList("upwdtype","","Type de mot de passe").
                                FS::$iMgr->addElementToList("Cleartext-Password",1).
                                FS::$iMgr->addElementToList("User-Password",2).
                                FS::$iMgr->addElementToList("Crypt-Password",3).
                                FS::$iMgr->addElementToList("MD5-Password",4).
                                FS::$iMgr->addElementToList("SHA1-Password",5).
                                FS::$iMgr->addElementToList("CHAP-Password",6).
                                "</select></li><li>".FS::$iMgr->addList("ugroup","","Profil").FS::$iMgr->addElementToList("","none").
				$this->addGroupList($radSQLMgr)."</select></li><li>".FS::$iMgr->textarea("csvlist","",580,330,"Liste des utilisateurs (format CSV)")."</li><li id=\"csvtooltip\">".
				"<b>Note: </b>Les noms d'utilisateurs ne peuvent pas contenir d'espace.<br />Les mots de passe doivent être en clair.<br />Caractère de formatage: <b>,</b></li><li>".
				FS::$iMgr->submit("","Importer")."</li></ul></form>";
			}
			else if($sh == 4) {
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$output = "";

				$found = 0;
				$formoutput = "";
				$formoutput .= "<h4>Nouvel Import Automatique</h4>";
				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=7");
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->addList("subnet","","Subnet DHCP");
                                $query = FS::$pgdbMgr->Select("z_eye_dhcp_subnet_cache","netid,netmask");
                                while($data = pg_fetch_array($query)) {
					if(!$found) $found = 1;
                                        $formoutput .= FS::$iMgr->addElementTolist($data["netid"]."/".$data["netmask"],$data["netid"]);
                                }
                                if($found) {
					$found = 0;
					$formoutput .= "</select></li><li>".FS::$iMgr->addList("radgroup","","Profil Radius");

					$groups=array();
        	                        $query = $radSQLMgr->Select("radgroupreply","distinct groupname");
                	                while($data = mysql_fetch_array($query)) {
                                	        if(!isset($groups[$data["groupname"]]))
                                        	        $groups[$data["groupname"]] = 1;
	                                }

        	                        $query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                	                while($data = mysql_fetch_array($query)) {
                                	        if(!isset($groups[$data["groupname"]]))
                                        	        $groups[$data["groupname"]] = 1;
                	                }
                        	        if(count($groups) > 0) {
						$found = 1;
                                        	foreach($groups as $key => $value)
							$formoutput .= FS::$iMgr->addElementToList($key,$key);
                	                }
					$formoutput .= "</select></li><li>".FS::$iMgr->submit("reg","Ajouter")."</li></ul></form>";
				}
				if($found) {
					$output .= $formoutput;
					$found = 0;
					$tmpoutput = "";
					$tmpoutput .= "<script type=\"text/javascript\">
		                                $.event.props.push('dataTransfer');
                		                $('#radsubnet #dragtd').on({
                                		        mouseover: function(e) { $('#trash').show(); },
		                                        mouseleave: function(e) { $('#trash').hide(); },
                		                        dragstart: function(e) { $('#trash').show(); e.dataTransfer.setData('text/html', $(this).text()); },
                                		        dragenter: function(e) { e.preventDefault();},
	        	                                dragover: function(e) { e.preventDefault(); },
        	        	                        dragleave: function(e) { },
                                	        drop: function(e) {},
                                        	dragend: function() { $('#trash').hide(); $('#editf').hide();}
		                                });
                		                $('#trash').on({
                                		        dragover: function(e) { e.preventDefault(); },
		                                        drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer l\'importation du subnet \''+e.dataTransfer.getData('text/html')+'\' ?".
                		                                FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=8").
                                		                FS::$iMgr->addHidden("subnet","'+e.dataTransfer.getData('text/html')+'").
                		                                FS::$iMgr->submit("","Supprimer").
                                		                FS::$iMgr->button("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
                                                		$('#pop').show();
		                                        }
                		                });
					</script>";
					$query = FS::$pgdbMgr->Select("z_eye_radius_dhcp_import","dhcpsubnet,groupname","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
					while($data = pg_fetch_array($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= "<h4>Imports automatiques existants</h4><table id=\"radsubnet\"><tr><th>Zone DHCP</th><th>Profil Radius</th></tr>";
						}
						$tmpoutput .= "<tr><td draggable=\"true\" id=\"dragtd\">".$data["dhcpsubnet"]."</td><td>".$data["groupname"]."</td></tr>";
					}
					if($found) $output .= $tmpoutput."</table>";
				}
				else
					$output .= FS::$iMgr->printError("Aucun subnet DHCP ou Profil Radius disponible pour la synchronisation");
			}
			else if($sh == 5) {
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$output .= "<h4>Nettoyage des utilisateurs</h4>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=9");
				$output .= "<table>";

				$radexpenable = FS::$pgdbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_enable' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				$radexptable = FS::$pgdbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_table' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				$radexpuser = FS::$pgdbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_user_field' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				$radexpdate = FS::$pgdbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_date_field' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
				
				$output .= FS::$iMgr->addIndexedCheckLine("Activer le nettoyage automatique","cleanradsqlenable",($radexpenable == 1 ? true : false));
				$output .= FS::$iMgr->addIndexedLine("Table SQL","cleanradsqltable",$radexptable);
				$output .= FS::$iMgr->addIndexedLine("Champ utilisateur","cleanradsqluserfield",$radexpuser);
				$output .= FS::$iMgr->addIndexedLine("Champ expiration","cleanradsqlexpfield",$radexpdate);
				$output .= FS::$iMgr->addTableSubmit("","Enregistrer")."</form>";
			}
			else if($sh == 6) {
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$output .= "<div id=\"adduserres\"></div>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10","adduser");
				$output .= "<table><tr><th>Intitulé</th><th>Valeur</th></tr>";
				$output .= FS::$iMgr->addIndexedLine("Nom *","radname");
				$output .= FS::$iMgr->addIndexedLine("Prénom *","radsurname");
				$output .= FS::$iMgr->addIndexedline("Identifiant de connexion *","radusername");
				$output .= "<tr><td>Profil</td><td>".FS::$iMgr->addList("profil","","").FS::$iMgr->addElementToList("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
				$output .= "<tr><td>Validité</td><td>".FS::$iMgr->radioList("validity",array(1,2),array("Toujours valide","Période"),1);
				$output .= FS::$iMgr->calendar("startdate","","Du")."<br />";
				$output .= FS::$iMgr->hourlist("limhours","limmins")."<br />";
				$output .= FS::$iMgr->calendar("enddate","","Au")."<br />";
				$output .= FS::$iMgr->hourlist("limhoure","limmine",23,59);
				$output .= "</td></tr>";
				$output .= FS::$iMgr->addTableSubmit("","Enregistrer")."</table></form>";

				$output .= "<script type=\"text/javascript\">$('#adduser').submit(function(event) {
					event.preventDefault();
					$.post('index.php?mod=".$this->mid."&at=3&r=".$raddb."&h=".$radhost."&p=".$radport."&act=10', $('#adduser').serialize(), function(data) {
						$('#adduserres').html(data);
					});
				});</script>";
			}
			else if($sh == 7) {
				$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                $radSQLMgr = new FSMySQLMgr();
                                $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                $radSQLMgr->Connect();

				$output .= "<div id=\"adduserlistres\"></div>";
				$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=11","adduserlist");
                                $output .= "<table><tr><th>Intitulé</th><th>Valeur</th></tr>";
				$output .= "<tr><td>Type de génération</td><td style=\"text-align: left;\">".FS::$iMgr->radio("typegen",1,false,"Nom aléatoire")."<br />".FS::$iMgr->radio("typegen",2,false,"Préfixe ").FS::$iMgr->input("prefix","")."</td></tr>";
                                $output .= FS::$iMgr->addIndexedNumericLine("Nombre de comptes *","nbacct","",4,4);
                                $output .= "<tr><td>Profil</td><td>".FS::$iMgr->addList("profil2","","").FS::$iMgr->addElementToList("","none").$this->addGroupList($radSQLMgr)."</select></td></tr>";
                                $output .= "<tr><td>Validité</td><td>".FS::$iMgr->radioList("validity2",array(1,2),array("Toujours valide","Période"),1);
                                $output .= FS::$iMgr->calendar("startdate2","","Du")."<br />";
				$output .= FS::$iMgr->hourlist("limhours2","limmins2")."<br />";
                                $output .= FS::$iMgr->calendar("enddate2","","Au")."<br />";
                                $output .= FS::$iMgr->hourlist("limhoure2","limmine2",23,59);
                                $output .= "</td></tr>";
                                $output .= FS::$iMgr->addTableSubmit("","Enregistrer")."</table></form>";
			}
			else {
				$output .= FS::$iMgr->printError("Cet onglet n'existe pas !");
			}
			return $output;
		}

		private function editRadiusEntry($raddb,$radhost,$radport,$radentry,$radentrytype) {
			$output = "";
			FS::$iMgr->showReturnMenu(true);
                	$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                        $radSQLMgr = new FSMySQLMgr();
                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                        $radSQLMgr->Connect();

			if($radentrytype == 1) {
				$userexist = $radSQLMgr->GetOneData("radcheck","username","username = '".$radentry."'");
				if(!$userexist) {
					$output .= FS::$iMgr->printError("Utilisateur inexistant !");
					return $output;
				}
				$userpwd = $radSQLMgr->GetOneData("radcheck","value","username = '".$radentry."' AND op = ':=' AND attribute IN('Cleartext-Password','User-Password','Crypt-Password','MD5-Password','SHA1-Password','CHAP-Password')");
				if($userpwd)
					$upwdtype = $radSQLMgr->GetOneData("radcheck","attribute","username = '".$radentry."' AND op = ':=' AND value = '".$userpwd."'");
				$grpcount = $radSQLMgr->Count("radusergroup","groupname","username = '".$radentry."'");
				$attrcount = $radSQLMgr->Count("radcheck","username","username = '".$radentry."'");
				$attrcount += $radSQLMgr->Count("radreply","username","username = '".$radentry."'");
				$formoutput = "<script type=\"text/javascript\">grpidx = ".$grpcount."; 
				function addGrpForm() {
					$('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->addList("ugroup'+grpidx+'","","Profil").FS::$iMgr->addElementToList("","none").$this->addGroupList($radSQLMgr)."</select> <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
					grpidx++;
				}
				function delGrpElmt(grpidx) {
                                        $('.ugroupli'+grpidx).remove();
                                }
				attridx = ".$attrcount."; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
				FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Valeur".
				FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40,"")." Op ".FS::$iMgr->addList("attrop'+attridx+'").
				FS::$iMgr->addElementToList("=","=").
				FS::$iMgr->addElementToList("==","==").
				FS::$iMgr->addElementToList(":=",":=").
				FS::$iMgr->addElementToList("+=","+=").
				FS::$iMgr->addElementToList("!=","!=").
                                FS::$iMgr->addElementToList(">",">").
                                FS::$iMgr->addElementToList(">=",">=").
                                FS::$iMgr->addElementToList("<","<").
				FS::$iMgr->addElementToList("<=","<=").
                                FS::$iMgr->addElementToList("=~","=~").
                                FS::$iMgr->addElementToList("!~","!~").
                                FS::$iMgr->addElementToList("=*","=*").
				FS::$iMgr->addElementToList("!*","!*").
				"</select> Cible ".FS::$iMgr->addList("attrtarget'+attridx+'").
				FS::$iMgr->addElementToList("check",1).
				FS::$iMgr->addElementToList("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertBefore('#formactions');
				$('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
				$('#attrtarget'+attridx).val(attrtarget); attridx++;};
				function delAttrElmt(attridx) {
					$('.attrli'+attridx).remove();
				}</script>";

				if(FS::$secMgr->isMacAddr($radentry) || preg_match('#^[0-9A-F]{12}$#i', $radentry))
					$utype = 2;
				else
					$utype = 1;
				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=2");
				$formoutput .= FS::$iMgr->addHidden("uedit",1);
				$formoutput .= "<h4>Modification de l'utilisateur '".$radentry."'</h4>";
				$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->addHidden("utype",$utype)."<b>Type d'utilisateur: </b>".
				($utype == 1 ? "Normal" : "Adresse MAC");
				$formoutput .= "</li><li>".
				FS::$iMgr->addHidden("username",$radentry)."</li>";
				if(FS::$pgdbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_enable' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'") == 1) {
					$creadate = $radSQLMgr->GetOneData("z_eye_radusers","creadate","username='".$radentry."'");
                                        $formoutput .= "<li><b>Date de création du compte: </b>".$creadate."</li>";
				}
				if($utype == 1) {
					$formoutput .= "<li><fieldset id=\"userdf\" style=\"border:0;\">";
					$pwd = $radSQLMgr->GetOneData("radcheck","value","username = '".$radentry."' AND attribute = 'Cleartext-Password' AND op = ':='");
					$formoutput .= FS::$iMgr->password("pwd",$pwd,"Mot de passe")."<br />".
					FS::$iMgr->addList("upwdtype","","Type de mot de passe").
					FS::$iMgr->addElementToList("Cleartext-Password",1,($upwdtype && $upwdtype == "Cleartext-Password" ? true : false)).
					FS::$iMgr->addElementToList("User-Password",2,($upwdtype && $upwdtype == "User-Password" ? true : false)).
					FS::$iMgr->addElementToList("Crypt-Password",3,($upwdtype && $upwdtype == "Crypt-Password" ? true : false)).
					FS::$iMgr->addElementToList("MD5-Password",4,($upwdtype && $upwdtype == "MD5-Password" ? true : false)).
					FS::$iMgr->addElementToList("SHA1-Password",5,($upwdtype && $upwdtype == "SHA1-Password" ? true : false)).
					FS::$iMgr->addElementToList("CHAP-Password",6,($upwdtype && $upwdtype == "CHAP-Password" ? true : false)).
					"</select></fieldset></li>";
				}
				$query = $radSQLMgr->Select("radusergroup","groupname","username = '".$radentry."'");
				$grpidx = 0;
				while($data = mysql_fetch_array($query)) {
					$formoutput .= "<li class=\"ugroupli".$grpidx."\">".FS::$iMgr->addList("ugroup".$grpidx,"","Profil").$this->addGroupList($radSQLMgr,$data["groupname"])."</select> <a onclick=\"javascript:delGrpElmt(".$grpidx.");\">X</a></li>";
					$grpidx++;
				}
				$attridx = 0;
				$query = $radSQLMgr->Select("radcheck","attribute,op,value","username = '".$radentry."' AND attribute <> 'Cleartext-Password'");
				while($data = mysql_fetch_array($query)) {
					if(!($utype == 2 && $data["attribute"] == "Auth-Type" && $data["op"] == ":=" && $data["value"] == "Accept")) {
						$formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
        	        	                FS::$iMgr->addList("attrop".$attridx).
						FS::$iMgr->addElementToList("=","=",($data["op"] == "=" ? true : false)).
                		                FS::$iMgr->addElementToList("==","==",($data["op"] == "==" ? true : false)).
        	                	        FS::$iMgr->addElementToList(":=",":=",($data["op"] == ":=" ? true : false)).
	                                	FS::$iMgr->addElementToList("+=","+=",($data["op"] == "+=" ? true : false)).
	                                	FS::$iMgr->addElementToList("!=","!=",($data["op"] == "!=" ? true : false)).
        	                	        FS::$iMgr->addElementToList(">",">",($data["op"] == ">" ? true : false)).
                		                FS::$iMgr->addElementToList(">=",">=",($data["op"] == ">=" ? true : false)).
        	        	                FS::$iMgr->addElementToList("<","<",($data["op"] == "<" ? true : false)).
	                        	        FS::$iMgr->addElementToList("<=","<=",($data["op"] == "<=" ? true : false)).
                                		FS::$iMgr->addElementToList("=~","=~",($data["op"] == "=~" ? true : false)).
	                        	        FS::$iMgr->addElementToList("!~","!~",($data["op"] == "!~" ? true : false)).
        	        	                FS::$iMgr->addElementToList("=*","=*",($data["op"] == "=*" ? true : false)).
        		                        FS::$iMgr->addElementToList("!*","!*",($data["op"] == "!*" ? true : false)).
	                	                "</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->addList("attrtarget".$attridx).
        	                	        FS::$iMgr->addElementToList("check",1,true).
	                                	FS::$iMgr->addElementToList("reply",2)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
						$attridx++;
					}
				}
				$query = $radSQLMgr->Select("radreply","attribute,op,value","username = '".$radentry."'");
                                while($data = mysql_fetch_array($query)) {
                                        $formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
                                        FS::$iMgr->addList("attrop".$attridx).
                                        FS::$iMgr->addElementToList("=","=",($data["op"] == "=" ? true : false)).
                                        FS::$iMgr->addElementToList("==","==",($data["op"] == "==" ? true : false)).
                                        FS::$iMgr->addElementToList(":=",":=",($data["op"] == ":=" ? true : false)).
                                        FS::$iMgr->addElementToList("+=","+=",($data["op"] == "+=" ? true : false)).
                                        FS::$iMgr->addElementToList("!=","!=",($data["op"] == "!=" ? true : false)).
                                        FS::$iMgr->addElementToList(">",">",($data["op"] == ">" ? true : false)).
                                        FS::$iMgr->addElementToList(">=",">=",($data["op"] == ">=" ? true : false)).
                                        FS::$iMgr->addElementToList("<","<",($data["op"] == "<" ? true : false)).
                                        FS::$iMgr->addElementToList("<=","<=",($data["op"] == "<=" ? true : false)).
                                        FS::$iMgr->addElementToList("=~","=~",($data["op"] == "=~" ? true : false)).
                                        FS::$iMgr->addElementToList("!~","!~",($data["op"] == "!~" ? true : false)).
                                        FS::$iMgr->addElementToList("=*","=*",($data["op"] == "=*" ? true : false)).
                                        FS::$iMgr->addElementToList("!*","!*",($data["op"] == "!*" ? true : false)).
                                        "</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->addList("attrtarget".$attridx).
                                        FS::$iMgr->addElementToList("check",1).
                                        FS::$iMgr->addElementToList("reply",2,true)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
                                        $attridx++;
                                }

				// if expiration module is activated, show the options
				if(FS::$pgdbMgr->GetOneData("z_eye_radius_options","optval","optkey = 'rad_expiration_enable' AND addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'") == 1) {
					$expdate = $radSQLMgr->GetOneData("z_eye_radusers","expiration","username='".$radentry."'");
					$startdate = $radSQLMgr->GetOneData("z_eye_radusers","startdate","username='".$radentry."'");
					$formoutput .= "<li>".FS::$iMgr->calendar("starttime",$startdate ? date("d-m-y",strtotime($startdate)) : "","Date d'expiration du compte")."</li>";
					$formoutput .= "<li>".FS::$iMgr->calendar("expiretime",$expdate ? date("d-m-y",strtotime($expdate)) : "","Date d'expiration du compte")."</li>";
				}
				$formoutput .= "<li id=\"formactions\">".FS::$iMgr->button("newgrp","Nouveau Groupe","addGrpForm()").
				FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").
				FS::$iMgr->submit("","Enregistrer")."</form></li></ul>";
				$output .= $formoutput;
			}
			else if($radentrytype == 2) {
				$groupexist = $radSQLMgr->GetOneData("radgroupcheck","groupname","groupname = '".$radentry."'");
				if(!$groupexist)
					$groupexist = $radSQLMgr->GetOneData("radgroupreply","groupname","groupname = '".$radentry."'");
				if(!$groupexist) {
					$output .= FS::$iMgr->printError("Groupe inexistant !");
					return $output;
				}
				$attrcount = $radSQLMgr->Count("radgroupcheck","groupname","groupname = '".$radentry."'");
				$attrcount += $radSQLMgr->Count("radgroupreply","groupname","groupname = '".$radentry."'");
				$formoutput = "<script type=\"text/javascript\">attridx = ".$attrcount."; function addAttrElmt(attrkey,attrval,attrop,attrtarget) { $('<li class=\"attrli'+attridx+'\">".
                                FS::$iMgr->input("attrkey'+attridx+'","'+attrkey+'",20,40,"Attribut")." Op ".FS::$iMgr->addList("attrop'+attridx+'").
                                FS::$iMgr->addElementToList("=","=").
                                FS::$iMgr->addElementToList("==","==").
                                FS::$iMgr->addElementToList(":=",":=").
                                FS::$iMgr->addElementToList("+=","+=").
                                FS::$iMgr->addElementToList("!=","!=").
                                FS::$iMgr->addElementToList(">",">").
                                FS::$iMgr->addElementToList(">=",">=").
                                FS::$iMgr->addElementToList("<","<").
                                FS::$iMgr->addElementToList("<=","<=").
                                FS::$iMgr->addElementToList("=~","=~").
                                FS::$iMgr->addElementToList("!~","!~").
                                FS::$iMgr->addElementToList("=*","=*").
                                FS::$iMgr->addElementToList("!*","!*").
                                "</select> Valeur".FS::$iMgr->input("attrval'+attridx+'","'+attrval+'",10,40)." Cible ".FS::$iMgr->addList("attrtarget'+attridx+'").
                                FS::$iMgr->addElementToList("check",1).
                                FS::$iMgr->addElementToList("reply",2)."</select> <a onclick=\"javascript:delAttrElmt('+attridx+');\">X</a></li>').insertAfter('#groupname');
                                $('#attrkey'+attridx).val(attrkey); $('#attrval'+attridx).val(attrval); $('#attrop'+attridx).val(attrop);
                                $('#attrtarget'+attridx).val(attrtarget); attridx++;};
                                function delAttrElmt(attridx) {
                                        $('.attrli'+attridx).remove();
                                }</script>";
				$formoutput .= "<h4>Modification du groupe '".$radentry."'</h4>";
				$formoutput .= "<ul class=\"ulform\">";
				$formoutput .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&r=".$raddb."&h=".$radhost."&p=".$radport."&act=3");
                                $formoutput .= FS::$iMgr->addHidden("uedit",1).FS::$iMgr->addHidden("groupname",$radentry);
				$attridx = 0;
                                $query = $radSQLMgr->Select("radgroupcheck","attribute,op,value","groupname = '".$radentry."'");
                                while($data = mysql_fetch_array($query)) {
                                         $formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
                                         FS::$iMgr->addList("attrop".$attridx).
                                         FS::$iMgr->addElementToList("=","=",($data["op"] == "=" ? true : false)).
                                         FS::$iMgr->addElementToList("==","==",($data["op"] == "==" ? true : false)).
                                         FS::$iMgr->addElementToList(":=",":=",($data["op"] == ":=" ? true : false)).
                                         FS::$iMgr->addElementToList("+=","+=",($data["op"] == "+=" ? true : false)).
                                         FS::$iMgr->addElementToList("!=","!=",($data["op"] == "!=" ? true : false)).
                                         FS::$iMgr->addElementToList(">",">",($data["op"] == ">" ? true : false)).
                                         FS::$iMgr->addElementToList(">=",">=",($data["op"] == ">=" ? true : false)).
                                         FS::$iMgr->addElementToList("<","<",($data["op"] == "<" ? true : false)).
                                         FS::$iMgr->addElementToList("<=","<=",($data["op"] == "<=" ? true : false)).
                                         FS::$iMgr->addElementToList("=~","=~",($data["op"] == "=~" ? true : false)).
                                         FS::$iMgr->addElementToList("!~","!~",($data["op"] == "!~" ? true : false)).
                                         FS::$iMgr->addElementToList("=*","=*",($data["op"] == "=*" ? true : false)).
                                         FS::$iMgr->addElementToList("!*","!*",($data["op"] == "!*" ? true : false)).
                                         "</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->addList("attrtarget".$attridx).
                                         FS::$iMgr->addElementToList("check",1,true).
                                         FS::$iMgr->addElementToList("reply",2)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
                                         $attridx++;
                                }

                                $query = $radSQLMgr->Select("radgroupreply","attribute,op,value","groupname = '".$radentry."'");
                                while($data = mysql_fetch_array($query)) {
                                        $formoutput .= "<li class=\"attrli".$attridx."\">".FS::$iMgr->input("attrkey".$attridx,$data["attribute"],20,40,"Attribut")." Op ".
                                        FS::$iMgr->addList("attrop".$attridx).
                                        FS::$iMgr->addElementToList("=","=",($data["op"] == "=" ? true : false)).
                                        FS::$iMgr->addElementToList("==","==",($data["op"] == "==" ? true : false)).
                                        FS::$iMgr->addElementToList(":=",":=",($data["op"] == ":=" ? true : false)).
                                        FS::$iMgr->addElementToList("+=","+=",($data["op"] == "+=" ? true : false)).
                                        FS::$iMgr->addElementToList("!=","!=",($data["op"] == "!=" ? true : false)).
                                        FS::$iMgr->addElementToList(">",">",($data["op"] == ">" ? true : false)).
                                        FS::$iMgr->addElementToList(">=",">=",($data["op"] == ">=" ? true : false)).
                                        FS::$iMgr->addElementToList("<","<",($data["op"] == "<" ? true : false)).
                                        FS::$iMgr->addElementToList("<=","<=",($data["op"] == "<=" ? true : false)).
                                        FS::$iMgr->addElementToList("=~","=~",($data["op"] == "=~" ? true : false)).
                                        FS::$iMgr->addElementToList("!~","!~",($data["op"] == "!~" ? true : false)).
                                        FS::$iMgr->addElementToList("=*","=*",($data["op"] == "=*" ? true : false)).
                                        FS::$iMgr->addElementToList("!*","!*",($data["op"] == "!*" ? true : false)).
                                        "</select> Valeur".FS::$iMgr->input("attrval".$attridx,$data["value"],10,40)." Cible ".FS::$iMgr->addList("attrtarget".$attridx).
                                        FS::$iMgr->addElementToList("check",1).
                                        FS::$iMgr->addElementToList("reply",2,true)."</select><a onclick=\"javascript:delAttrElmt(".$attridx.");\">X</a></li>";
                                        $attridx++;
                                }
				$formoutput .= "<li>".FS::$iMgr->button("newattr","Nouvel attribut","addAttrElmt('','','','')").FS::$iMgr->submit("","Enregistrer")."</li></ul></form>";
				$output .= $formoutput;
			}
			else
				$output .= FS::$iMgr->printError("Type d'entrée invalide !");
			return $output;
		}

		private function addGroupList($radSQLMgr,$selectEntry="") {
			$output = "";
			$groups=array();
                        $query = $radSQLMgr->Select("radgroupcheck","distinct groupname");
                        while($data = mysql_fetch_array($query)) {
                                if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
                        }
                        $query = $radSQLMgr->Select("radgroupreply","distinct groupname");
                        while($data = mysql_fetch_array($query)) {
                                if(!in_array($data["groupname"],$groups)) array_push($groups,$data["groupname"]);
                        }
                        for($i=0;$i<count($groups);$i++) {
                                $output .= FS::$iMgr->addElementToList($groups[$i],$groups[$i],($groups[$i] == $selectEntry ? true : false));
                        }
			return $output;
		}

		public function handlePostDatas($act) {
                        switch($act) {
				case 1:
					$rad = FS::$secMgr->checkAndSecurisePostData("radius");
					if(!$rad) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					$radcut1 = preg_split("[@]",$rad);
					if(count($radcut1) != 2) {
						header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
					}
					$radcut2 = preg_split("[:]",$radcut1[1]);
					if(count($radcut2) != 2) {
                                                header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
                                        }
					header("Location: index.php?mod=".$this->mid."&h=".$radcut2[0]."&p=".$radcut2[1]."&r=".$radcut1[0]);
					return;
				case 2:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
					$radhost = FS::$secMgr->checkAndSecuriseGetData("h");
					$radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$utype = FS::$secMgr->checkAndSecurisePostData("utype");
					$username = FS::$secMgr->checkAndSecurisePostData("username");
					$upwd = FS::$secMgr->checkAndSecurisePostData("pwd");
					$upwdtype = FS::$secMgr->checkAndSecurisePostData("upwdtype");

					// Check all fields
					if(!$username || $username == "" || !$utype || !FS::$secMgr->isNumeric($utype) ||
						$utype < 1 || $utype > 3 || 
						($utype == 1 && (!$upwd || $upwd == "" || !$upwdtype || $upwdtype < 1 || $upwdtype > 6))) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					// if type 2: must be a mac addr
					if($utype == 2 && (!FS::$secMgr->isMacAddr($username) && !preg_match('#^[0-9A-F]{12}$#i', $username))) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

                                	$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                               		$radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname='".$raddb."'");
                                	$radSQLMgr = new FSMySQLMgr();
                                	$radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                	$radSQLMgr->Connect();

					// For Edition Only, don't delete acct records
					$edit = FS::$secMgr->checkAndSecurisePostData("uedit");
					if($edit == 1) {
						$radSQLMgr->Delete("radcheck","username = '".$username."'");
        	                                $radSQLMgr->Delete("radreply","username = '".$username."'");
                	                        $radSQLMgr->Delete("radusergroup","username = '".$username."'");
						$radSQLMgr->Delete("z_eye_radusers","username = '".$username."'");
					}
					$userexist = $radSQLMgr->GetOneData("radcheck","username","username = '".$username."'");
					if(!$userexist || $edit == 1) {
						if($utype == 1) {
							switch($upwdtype) {
								case 1: $attr = "Cleartext-Password"; $value = $upwd; break;
								case 2: $attr = "User-Password"; $value = $upwd; break;
								case 3: $attr = "Crypt-Password"; $value = crypt($upwd); break;
								case 4: $attr = "MD5-Password"; $value = md5($upwd); break;
								case 5: $attr = "SHA1-Password"; $value = sha1($upwd); break;
								case 6: $attr = "CHAP-Password"; $value = $upwd; break;
							}
						}
						else {
							if($utype == 2) {
								if(!FS::$secMgr->isMacAddr($username) && !preg_match('#^[0-9A-F]{12}$#i', $username)) {
									header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
			                                                return;
								}
								$username = preg_replace("#[:]#","",$username);
							}
							$attr = "Auth-Type";
							$value = "Accept";
						}
						$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username."','".$attr."',':=','".$value."'");
	                                        foreach($_POST as $key => $value) {
                        			        if(preg_match("#^ugroup#",$key)) {
			                                        $groupfound = $radSQLMgr->GetOneData("radgroupreply","groupname","groupname = '".$value."'");
								if(!$groupfound)
	                        			                $groupfound = $radSQLMgr->GetOneData("radgroupcheck","groupname","groupname = '".$value."'");
								if($groupfound) {
									$usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$username."' AND groupname = '".$value."'");
									if(!$usergroup)
										$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$value."','1'");
								}
							}
						}
						$attrTab = array();
	                                        foreach($_POST as $key => $value) {
        	                                        if(preg_match("#attrval#",$key)) {
                	                                        $key = preg_replace("#attrval#","",$key);
                        	                                if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                	                        $attrTab[$key]["val"] = $value;
                                        	        }
                                                	else if(preg_match("#attrkey#",$key)) {
	                                                        $key = preg_replace("#attrkey#","",$key);
        	                                                if(!isset($attrTab[$key])) $attrTab[$key] = array();
                	                                        $attrTab[$key]["key"] = $value;
                        	                        }
                                	                else if(preg_match("#attrop#",$key)) {
                                        	                $key = preg_replace("#attrop#","",$key);
                                                	        if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        	$attrTab[$key]["op"] = $value;
	                                                }
        	                                        else if(preg_match("#attrtarget#",$key)) {
                	                                        $key = preg_replace("#attrtarget#","",$key);
                        	                                if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                	                        $attrTab[$key]["target"] = $value;
                                        	        }
						}
						foreach($attrTab as $attrKey => $attrEntry) {
	                                                if($attrEntry["target"] == "2") {
        	                                                $radSQLMgr->Insert("radreply","id,username,attribute,op,value","'','".$username.
                	                                        "','".$attrEntry["key"]."','".$attrEntry["op"]."','".$attrEntry["val"]."'");
                        	                        }
                                	                else if($attrEntry["target"] == "1") {
                                        	                $radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username.
                                                	        "','".$attrEntry["key"]."','".$attrEntry["op"]."','".$attrEntry["val"]."'");
	                                                }
        	                                }

						$radSQLMgr->Delete("z_eye_radusers","username = '".$username."'");
						$expiretime = FS::$secMgr->checkAndSecurisePostData("expiretime");
						if($expiretime)
							$radSQLMgr->Insert("z_eye_radusers","username,expiration","'".$username."','".date("y-m-d",strtotime($expiretime))."'");
					}
					else {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
                                                return;
					}
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb);
					break;
				case 3:
                                        $raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$groupname = FS::$secMgr->checkAndSecurisePostData("groupname");

					if(!$groupname) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
                                                return;
                                        }

					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();

					// For Edition Only, don't delete acct/user-group links
                                        $edit = FS::$secMgr->checkAndSecurisePostData("uedit");
                                        if($edit == 1) {
						$radSQLMgr->Delete("radgroupcheck","groupname = '".$groupname."'");
	                                        $radSQLMgr->Delete("radgroupreply","groupname = '".$groupname."'");
                                        }

					$groupexist = $radSQLMgr->GetOneData("radgroupcheck","id","groupname='".$groupname."'");
					if($groupexist && $edit != 1) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
						return;
					}
					$groupexist = $radSQLMgr->GetOneData("radgroupreply","id","groupname='".$groupname."'");
					if($groupexist && $edit != 1) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
						return;
					}
					$attrTab = array();
					foreach($_POST as $key => $value) {
						if(preg_match("#attrval#",$key)) {
							$key = preg_replace("#attrval#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
							$attrTab[$key]["val"] = $value;
						}
						else if(preg_match("#attrkey#",$key)) {
							$key = preg_replace("#attrkey#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        $attrTab[$key]["key"] = $value;
						}
						else if(preg_match("#attrop#",$key)) {
							$key = preg_replace("#attrop#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        $attrTab[$key]["op"] = $value;
						}
						else if(preg_match("#attrtarget#",$key)) {
							$key = preg_replace("#attrtarget#","",$key);
							if(!isset($attrTab[$key])) $attrTab[$key] = array();
                                                        $attrTab[$key]["target"] = $value;
						}
					}
					foreach($attrTab as $attrKey => $attrValue) {
						if($attrValue["target"] == "2") {
							$radSQLMgr->Insert("radgroupreply","id,groupname,attribute,op,value","'','".$groupname.
							"','".$attrValue["key"]."','".$attrValue["op"]."','".$attrValue["val"]."'");
						}
						else if($attrValue["target"] == "1") {
							$radSQLMgr->Insert("radgroupcheck","id,groupname,attribute,op,value","'','".$groupname.
                                                        "','".$attrValue["key"]."','".$attrValue["op"]."','".$attrValue["val"]."'");
						}
					}

					header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."");
					break;
				case 4:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$username = FS::$secMgr->checkAndSecurisePostData("user");
					$acctdel = FS::$secMgr->checkAndSecurisePostData("acctdel");
					$logdel = FS::$secMgr->checkAndSecurisePostData("logdel");

					if(!$raddb || !$radhost || !$radport || !$username) {
						header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."&err=4");
						return;
					}
					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();
					$radSQLMgr->Delete("radcheck","username = '".$username."'");
					$radSQLMgr->Delete("radreply","username = '".$username."'");
					$radSQLMgr->Delete("radusergroup","username = '".$username."'");
					$radSQLMgr->Delete("z_eye_radusers","username ='".$username."'");
					if($logdel == "on") $radSQLMgr->Delete("radpostauth","username = '".$username."'");
					if($acctdel == "on") $radSQLMgr->Delete("radacct","username = '".$username."'");
					header("Location: index.php?mod=".$this->mid."&h=".$radhost."&p=".$radport."&r=".$raddb."");
					return;
				case 5:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$groupname = FS::$secMgr->checkAndSecurisePostData("group");

					if(!$raddb || !$radhost || !$radport || !$groupname) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=4");
						return;
					}
					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();
					$radSQLMgr->Delete("radgroupcheck","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radgroupreply","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radusergroup","groupname = '".$groupname."'");
					$radSQLMgr->Delete("radhuntgroup","groupname = '".$groupname."'");
					FS::$pgdbMgr->Delete("z_eye_radius_dhcp_import","groupname = '".$groupname."'");
					header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."");
					return;

				case 6:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$utype = FS::$secMgr->checkAndSecurisePostData("usertype");
					$pwdtype = FS::$secMgr->checkAndSecurisePostData("upwdtype");
					$group = FS::$secMgr->checkAndSecurisePostData("ugroup");
					$userlist = FS::$secMgr->checkAndSecurisePostData("csvlist");

					if(!$raddb || !$radhost || !$radport) {
                                                header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=1");
                                                return;
                                        }
                                        $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();

					if(!$utype || $utype != 1 && $utype != 2 || !$userlist) {
						header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
						return;
					}

					$groupfound = NULL;
					if($group != "none") {
						$groupfound = $radSQLMgr->GetOneData("radgroupreply","groupname","groupname = '".$group."'");
                                                if(!$groupfound)
                                         	       $groupfound = $radSQLMgr->GetOneData("radgroupcheck","groupname","groupname = '".$group."'");
					}
					if($utype == 1) {
						$userlist = str_replace('\r','\n',$userlist);
	                                        $userlist = str_replace('\n\n',"\n",$userlist);
                	                        $userlist = preg_split("#\\n#",$userlist);
						// Delete empty entries
                                                $userlist = array_filter($userlist);
						$fmtuserlist = array();
						for($i=0;$i<count($userlist);$i++) {
							$tmp = preg_split("#[,]#",$userlist[$i]);
							if(count($tmp) != 2 || preg_match("#[ ]#",$tmp[0])) {
								header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
								return;
							}
							$fmtuserlist[$tmp[0]] = $tmp[1];
						}
						$userfound = 0;
						foreach($fmtuserlist as $user => $upwd) {
							switch($pwdtype) {
        	                                                case 1: $attr = "Cleartext-Password"; $value = $upwd; break;
	                                                        case 2: $attr = "User-Password"; $value = $upwd; break;
                                                        	case 3: $attr = "Crypt-Password"; $value = crypt($upwd); break;
                                                	        case 4: $attr = "MD5-Password"; $value = md5($upwd); break;
                                        	                case 5: $attr = "SHA1-Password"; $value = sha1($upwd); break;
                                	                        case 6: $attr = "CHAP-Password"; $value = $upwd; break;
                        	                                default:
                	                                                header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
        	                                                        return;
	                                                }
                                                        if(!$radSQLMgr->GetOneData("radcheck","username","username = '".$user."'"))
                                                                $radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$user."','".$attr."',':=','".$value."'");
                                                        if($groupfound) {
                                                                $usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$user."' AND groupname = '".$group."'");
                                                                if(!$usergroup)
                                                                        $radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$user."','".$group."','1'");
                                                        }
                                                }
						if($userfound) {
                                                        header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=5");
							return;
						}
					}
                                        else if($utype == 2) {
						if(preg_match("#[,]#",$userlist)) {
							header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
							return;
						}
						$userlist = str_replace('\r','\n',$userlist);
                         	                $userlist = str_replace('\n\n',"\n",$userlist);
                                	        $userlist = preg_split("#\\n#",$userlist);
						// Delete empty entries
						$userlist = array_filter($userlist);
						// Match & format mac addr
						for($i=0;$i<count($userlist);$i++) {
							if(!FS::$secMgr->isMacAddr($userlist[$i]) && !preg_match('#^[0-9A-F]{12}$#i', $userlist[$i]) && !preg_match('#^([0-9A-F]{2}[-]){5}[0-9A-F]{2}$#i', $userlist[$i])) {
								header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
	                                                        return;
							}
							$userlist[$i] = preg_replace("#[:-]#","",$userlist[$i]);
							$userlist[$i] = strtolower($userlist[$i]);
						}
						// Delete duplicate entries
						$userlist = array_unique($userlist);
						$userfound = 0;
						for($i=0;$i<count($userlist);$i++) {
							if(!$radSQLMgr->GetOneData("radcheck","username","username = '".$userlist[$i]."'"))
								$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$userlist[$i]."','Auth-Type',':=','Accept'");
							else $userfound = 1;
                       	                                if($groupfound) {
                                                 		$usergroup = $radSQLMgr->GetOneData("radusergroup","groupname","username = '".$userlist[$i]."' AND groupname = '".$group."'");
								if(!$usergroup)
		                                                      	$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$userlist[$i]."','".$group."','1'");
							}
						}
						if($userfound) {
							header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&err=5");
							return;
						}
					}
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=3");
					return;
				case 7:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$radgroup = FS::$secMgr->checkAndSecurisePostData("radgroup");
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");

                                        if(!$raddb || !$radhost || !$radport) {
                                                header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
                                                return;
                                        }

					if(!$radgroup || !$subnet || !FS::$secMgr->isIP($subnet)) {
                                                header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=2");
                                                return;
                                        }

                                        $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();

					$groupexist = $radSQLMgr->GetOneData("radgroupcheck","id","groupname='".$radgroup."'");
                                        if(!$groupexist)
	                                        $groupexist = $radSQLMgr->GetOneData("radgroupreply","id","groupname='".$radgroup."'");

                                        if(!$groupexist) {
                                                header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
                                                return;
                                        }

					$subnetexist = FS::$pgdbMgr->GetOneData("z_eye_dhcp_subnet_cache","netmask","netid = '".$subnet."'");
					if(!$subnetexist) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
                                                return;
					}
					if(!FS::$pgdbMgr->GetOneData("z_eye_radius_dhcp_import","dhcpsubnet","addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."' AND dhcpsubnet = '".$subnet."'"))
						FS::$pgdbMgr->Insert("z_eye_radius_dhcp_import","addr,port,dbname,dhcpsubnet,groupname","'".$radhost."','".$radport."','".$raddb."','".$subnet."','".$radgroup."'");
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4");
					return;
				case 8:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
                                        $subnet = FS::$secMgr->checkAndSecurisePostData("subnet");

					if(!$raddb || !$radhost || !$radport) {
                                                header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=1");
                                                return;
                                        }

					if(!$subnet) {
                                                header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4&err=2");
                                                return;
                                        }

					FS::$pgdbMgr->Delete("z_eye_radius_dhcp_import","addr = '".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."' AND dhcpsubnet = '".$subnet."'");
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=4");
					return;
				case 9:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					if(!$raddb || !$radhost || !$radport) {
                                                header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
                                                return;
                                        }

					$radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();

					$cleanradenable = FS::$secMgr->checkAndSecurisePostData("cleanradsqlenable");
                                        $cleanradtable = FS::$secMgr->checkAndSecurisePostData("cleanradsqltable");
                                        $cleanradsqluserfield = FS::$secMgr->checkAndSecurisePostData("cleanradsqluserfield");
                                        $cleanradsqlexpfield = FS::$secMgr->checkAndSecurisePostData("cleanradsqlexpfield");

					if($cleanradenable == "on" && (!$cleanradtable || !$cleanradsqluserfield || !$cleanradsqlexpfield)) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
                                                return;
					}

					if($radSQLMgr->Count($cleanradtable,$cleanradsqluserfield) == NULL) {
						header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5&err=6");
                                                return;
                                        }

					FS::$pgdbMgr->Delete("z_eye_radius_options","addr = '".$radhost."' AND port = '".$radport."' and dbname = '".$raddb."'");
					FS::$pgdbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_enable','1'");
					FS::$pgdbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_table','".$cleanradtable."'");
					FS::$pgdbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_user_field','".$cleanradsqluserfield."'");
					FS::$pgdbMgr->Insert("z_eye_radius_options","addr,port,dbname,optkey,optval","'".$radhost."','".$radport."','".$raddb."','rad_expiration_date_field','".$cleanradsqlexpfield."'");
					header("Location: index.php?mod=".$this->mid."&sh=3&h=".$radhost."&p=".$radport."&r=".$raddb."&sh=5");
					return;
				case 10:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$name = FS::$secMgr->checkAndSecurisePostData("radname");
					$surname = FS::$secMgr->checkAndSecurisePostData("radsurname");
					$username = FS::$secMgr->checkAndSecurisePostData("radusername");
					$profil = FS::$secMgr->checkAndSecurisePostData("profil");
					$valid = FS::$secMgr->checkAndSecurisePostData("validity");
					$sdate = FS::$secMgr->checkAndSecurisePostData("startdate");
					$edate = FS::$secMgr->checkAndSecurisePostData("enddate");
					$limhs = FS::$secMgr->checkAndSecurisePostData("limhours");
					$limms = FS::$secMgr->checkAndSecurisePostData("limmins");
					$limhe = FS::$secMgr->checkAndSecurisePostData("limhoure");
                                        $limme = FS::$secMgr->checkAndSecurisePostData("limmine");

					if(!$raddb || !$radhost || !$radport) {
						echo FS::$iMgr->printError("Serveur d'authentification invalide !");
                                                //header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=1");
                                                return;
                                        }
					if(!$name || !$surname || !$username || !$valid || !$profil || ($valid == 2 && (!$sdate || !$edate || $limhs < 0 || $limms < 0 || $limhe < 0 || $limme < 0 
						|| !FS::$secMgr->isNumeric($limhs) || !FS::$secMgr->isNumeric($limms) || !FS::$secMgr->isNumeric($limhe) || !FS::$secMgr->isNumeric($limme) || !preg_match("#^\d{2}[-]\d{2}[-]\d{4}$#",$sdate) 
					))) {
						echo FS::$iMgr->printError("Certains champs sont manquants ou invalides !");
                                                //header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
                                                return;
                                        }

					$sdate = ($valid == 2 ? date("y-m-d",strtotime($sdate))." ".$limhs.":".$limms.":00" : "");
                                        $edate = ($valid == 2 ? date("y-m-d",strtotime($edate))." ".$limhe.":".$limme.":00" : "");
					if(strtotime($sdate) > strtotime($edate)) {
						echo FS::$iMgr->printError("La date de fin est située avant la date d'activation du compte !");
						return;
					}

                                        $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
                                        $radSQLMgr->Connect();

					$exist = $radSQLMgr->GetOneData("radcheck","id","username = '".$username."'");
					if(!$exist) $exist = $radSQLMgr->GetOneData("radreply","id","username = '".$username."'");
					if($exist) {
						echo FS::$iMgr->printError("L'utilisateur demandé existe déjà !");
						//header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=3");
                                                return;
					}

					$password = FS::$secMgr->genRandStr(8);
					$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username."','Cleartext-Password',':=','".$password."'");
					$radSQLMgr->Insert("z_eye_radusers","username,expiration,name,surname,startdate,creator,creadate","'".$username."','".$edate."','".$name."','".$surname."','".$sdate."','".FS::$sessMgr->getUid()."',NOW()");
					$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$profil."',0");

					echo FS::$iMgr->printDebug("Utilisateur créé avec succès !")."<br /><hr><b>Login: </b>".$username."<br /><b>Mot de passe: </b>".$password."<br /><b>Validité: </b>".
						($valid == 2 ? "Du ".$sdate." au ".$edate : "Infinie")."<hr><br />";
					return;
				case 11:
					$raddb = FS::$secMgr->checkAndSecuriseGetData("r");
                                        $radhost = FS::$secMgr->checkAndSecuriseGetData("h");
                                        $radport = FS::$secMgr->checkAndSecuriseGetData("p");
					$typegen = FS::$secMgr->checkAndSecurisePostData("typegen");
					$prefix = FS::$secMgr->checkAndSecurisePostData("prefix");
					$nbacct = FS::$secMgr->checkAndSecurisePostData("nbacct");
					$profil = FS::$secMgr->checkAndSecurisePostData("profil2");
					$valid = FS::$secMgr->checkAndSecurisePostData("validity2");
					$sdate = FS::$secMgr->checkAndSecurisePostData("startdate2");
					$edate = FS::$secMgr->checkAndSecurisePostData("enddate2");
					$limhs = FS::$secMgr->checkAndSecurisePostData("limhours2");
					$limms = FS::$secMgr->checkAndSecurisePostData("limmins2");
					$limhe = FS::$secMgr->checkAndSecurisePostData("limhoure2");
                                        $limme = FS::$secMgr->checkAndSecurisePostData("limmine2");
					if(!$raddb || !$radhost || !$radport) {
						echo FS::$iMgr->printError("Serveur d'authentification invalide !");
                                                //header("Location: index.php?mod=".$this->mid."&sh=1&h=".$radhost."&p=".$radport."&r=".$raddb."&err=1");
                                                return;
                                        }

					if(!$typegen || $typegen == 2 && !$prefix || !$nbacct || !$valid || !$profil || ($valid == 2 && (!$sdate || !$edate || $limhs < 0 || $limms < 0 || $limhe < 0 || $limme < 0 
						|| !FS::$secMgr->isNumeric($nbacct) || $nbacct <= 0 || !FS::$secMgr->isNumeric($limhs) || !FS::$secMgr->isNumeric($limms) || !FS::$secMgr->isNumeric($limhe) || !FS::$secMgr->isNumeric($limme) || !preg_match("#^\d{2}[-]\d{2}[-]\d{4}$#",$sdate) 
					))) {
						echo FS::$iMgr->printError("Certains champs sont manquants ou invalides !");
                                                //header("Location: index.php?mod=".$this->mid."&sh=2&h=".$radhost."&p=".$radport."&r=".$raddb."&err=2");
                                                return;
                                        }
					$sdate = ($valid == 2 ? date("y-m-d",strtotime($sdate))." ".$limhs.":".$limms.":00" : "");
                                        $edate = ($valid == 2 ? date("y-m-d",strtotime($edate))." ".$limhe.":".$limme.":00" : "");
					if(strtotime($sdate) > strtotime($edate)) {
						echo FS::$iMgr->printError("La date de fin est située avant la date d'activation du compte !");
						return;
					}

                                        $radlogin = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","login","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radpwd = FS::$pgdbMgr->GetOneData("z_eye_radius_db_list","pwd","addr='".$radhost."' AND port = '".$radport."' AND dbname = '".$raddb."'");
                                        $radSQLMgr = new FSMySQLMgr();
                                        $radSQLMgr->setConfig($raddb,$radport,$radhost,$radlogin,$radpwd);
					$radSQLMgr->Connect();

					$pdf = new PDFgen();
					$pdf->SetTitle("Compte ".($valid == 1 ? "Permanent" : "Temporaire")." - IOGS");
					for($i=0;$i<$nbacct;$i++) {
						$password = FS::$secMgr->genRandStr(8);
						if($typegen == 2) $username = $prefix.($i+1);
						else $username = FS::$secMgr->genRandStr(12);
						$radSQLMgr->Insert("radcheck","id,username,attribute,op,value","'','".$username."','Cleartext-Password',':=','".$password."'");
						$radSQLMgr->Insert("z_eye_radusers","username,expiration,name,surname,startdate,creator,creadate","'".$username."','".$edate."','".$name."','".$surname."','".$sdate."','".FS::$sessMgr->getUid()."',NOW()");
						$radSQLMgr->Insert("radusergroup","username,groupname,priority","'".$username."','".$profil."',0");

						$pdf->AddPage();
						$pdf->WriteHTML(utf8_decode("<b>Login: </b>".$username."<br /><br /><b>Mot de passe: </b>".$password."<br /><br /><b>Validité: </b>".($valid == 2 ? "Du ".date("d/m/y H:i",strtotime($sdate))." au ".date("d/m/y H:i",strtotime($edate)) : "Infinie")));
					}
					$pdf->CleanOutput();
					return;
			}
		}
	};
?>