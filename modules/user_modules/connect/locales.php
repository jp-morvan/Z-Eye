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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lConnect extends zLocales {
		function lConnect() {
			$this->locales = array(
				"fr" => array(
					"Connect" => "Connexion",
					"err-bad-user" => "Nom d'utilisateur et/ou mot de passe inconnu",
					"err-unk" => "Erreur inconnue",
					"Login" => "Utilisateur",
					"Password" => "Mot de passe",
					"title-con" => "Connexion à l'espace d'administration",
				),
				"en" => array(
					"Connect" => "Connect",
					"err-bad-user" => "Invalid user/password",
					"err-unk" => "Unknown error",
					"Login" => "User",
					"Password" => "Password",
					"title-con" => "Connect to the admin panel",
				)
			);
		}
	};
?>