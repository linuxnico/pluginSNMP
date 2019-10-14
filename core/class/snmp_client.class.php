<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class snmp_client extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */
    //gestion des dependances
    public static function dependancy_info() {
       $return = array();
       $return['progress_file'] = '/tmp/snmp_client_dep';
       $return['log'] = 'snmp_client_dep';
       $test = exec("sudo dpkg-query -l 'php*-snmp*' | grep php", $ping, $retour);
       if(count($ping)>0)
       {
         $return['state'] = 'ok';
       } else {
         $return['state'] = 'nok';
       }
       return $return;
     }
    //install des dependances
    public function dependancy_install() {
      log::add('snmp_client','info','Installation des dependances php-snmp');
      passthru('sudo apt install php-snmp -y >> ' . log::getPathToLog('snmp_client_dep') . ' 2>&1 &');
    }
    // creation de staches cron suivant config de l'equipement
    public static function cron() {
  		$dateRun = new DateTime();
      // log::add('snmp_client', 'debug', "on passe par le cron");
  		foreach (eqLogic::byType('snmp_client') as $eqLogic) {
  			$autorefresh = $eqLogic->getConfiguration('autorefresh');
  			if ($eqLogic->getIsEnable() == 1 && $autorefresh != '') {
  				try {
  					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
  					if ($c->isDue($dateRun)) {
              $cmd = $eqLogic->getCmd(null, 'refresh');//retourne la commande "refresh si elle existe
    				  if (!is_object($cmd)) {//Si la commande n'existe pas
                // log::add('snmp_client', 'debug', "pas de commande refresh ". $eqLogic->getHumanName());
    				  	continue; //continue la boucle
    				  }
              // log::add('snmp_client', 'debug', "on passe par le cron ET on refresh ". $eqLogic->getHumanName());
    				  $cmd->execCmd(); // la commande existe on la lance
  					}
  				} catch (Exception $exc) {
  					log::add('snmp_client', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
  				}
  			}
  		}
  	}


    /*     * *********************Méthodes d'instance************************* */

    //fonction de recuperation d'un oid numerique
    public function recupNumerique($oid) {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", $oid);
      // log::add('snmp_client', 'debug',"recupnumerique: oid: -".$oid."- et ip: -".$ip."- et resultat: -".$val."-");
      $val = substr($val, strpos($val, ':')+2);
      return $val;
    }
    //fonction de recuperation d'un oid binaire
    public function recupBinaire($oid) {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", $oid);
      // log::add('snmp_client', 'debug',"recupbinaire: oid: -".$oid."- et ip: -".$ip."- et resultat: -".$val."-");
      $val = substr($val, strpos($val, ':')+1);
      //   log::add('snmp_client', 'debug',"inversion".$this->getConfiguration('invertBinary'));
      // if ($this->getConfiguration('invertBinary')==1) {
      //   log::add('snmp_client', 'debug',"inversion".$this->getConfiguration('invertBinary'));
      // }
      return $val;
    }
    //fonction de vrification de la presence de l'equipement sur le reseau
    public function ping() {
      log::add('snmp_client', 'debug', "ping");
      $ip = $this->getConfiguration("ip");
      $ping = exec("ping -c 1 ".$ip, $ping, $return);
      if($return=='1') // y a une erreur
      {
         return 0;
      }
      else
      {
         return 1;
      }
    }

    public function preInsert() {
      log::add('snmp_client', 'debug', "preinsert");

    }

    public function postInsert() {
      log::add('snmp_client', 'debug', "postinsert");

    }
    // renseigne l'autorefresh si vide
    public function preSave() {
      log::add('snmp_client', 'debug', "presave");
      if ($this->getConfiguration('autorefresh') == '') {
			     $this->setConfiguration('autorefresh', '*/30 * * * *');
		  }

    }

    public function postSave() {
      log::add('snmp_client', 'debug', "postsave");
  // creation commande refresh
      $refresh = $this->getCmd(null, 'refresh');
  		if (!is_object($refresh)) {
  			$refresh = new snmp_clientCmd();
  			$refresh->setName(__('Rafraichir', __FILE__));
  		}
  		$refresh->setEqLogic_id($this->getId());
  		$refresh->setLogicalId('refresh');
  		$refresh->setType('action');
  		$refresh->setSubType('other');
      $refresh->setOrder(1);
      $refresh->setIsHistorized(0);
  		$refresh->save();

      // on ajoute un info de ping si besoin
      if ($this->getConfiguration('ping') == True) {
        log::add('snmp_client', 'debug', "on teste le ping");
        // creation commande refresh
          $ping = $this->getCmd(null, 'presence');
      		if (!is_object($ping)) {
      			$ping = new snmp_clientCmd();
      			$ping->setName(__('Presence', __FILE__));
      		}
      		$ping->setEqLogic_id($this->getId());
      		$ping->setLogicalId('presence');
      		$ping->setType('info');
      		$ping->setSubType('binary');
          $ping->setOrder(200);
          $ping->setAlert('dangerif', '#value#=0');
          $ping->setIsHistorized(1);
      		$ping->save();
      	}
      else {
        $ping = $this->getCmd(null, 'presence');
            if (is_object($ping)) {
              $ping->setAlert('dangerif', '');
              $ping->remove();
            }

      }

    }


    public function preUpdate() {
      log::add('snmp_client', 'debug', "preupdate");
      // on verifie au'il y a bien une ip de definie
      if ($this->getConfiguration('ip') == '') {
      			throw new Exception(__('L\'adresse IP ne peut etre vide', __FILE__));
      		}
      // foreach ( $this->getCmd('info') as $commande) {
      //   log::add('snmp_client', 'debug', "result: -".$commande->getConfiguration('oid')."- de la commande: ".$commande->getHumanName());
      //   if ($commande->getConfiguration('oid') == '') {
      //         throw new Exception(__('L\'OID ne peut etre vide', __FILE__));
      //       }
      // }



    }

    public function postUpdate() {
     // log::add('snmp_client', 'debug', "postupdate");
      // on fait un refresh a la creation et a la mise a jour
      //$cmd = $this->getCmd(null, 'refresh'); // On recherche la commande refresh de l’équipement
		 // if (is_object($cmd) and $this->getIsEnable() == 1 ) { //elle existe et l'equipement est active, on lance la commande
		//	     $cmd->execCmd();
		//  }

    }

    public function preRemove() {

    }

    public function postRemove() {

    }

    /*     * **********************Getteur Setteur*************************** */
}

class snmp_clientCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */

    public function execute($_options = array()) {
        $eqlogic = $this->getEqLogic(); //récupère l'éqlogic (l'equipement) de la commande $this
        // log::add('snmp_client', 'debug', "on refresh une commande numerique -".$this->getConfiguration('oiid').'-');
        // return;
		    switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
			       case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe  .
               foreach ( $eqlogic->getCmd('info') as $commande) {
                 log::add('snmp_client', 'debug', "on refresh une commande numero: ".$commande->getId()." de type: ".$commande->getSubType()." avec oid: ".$commande->getConfiguration('oid').'-'.count($eqlogic->getCmd('info')));
                   // log::add('snmp_client', 'debug', "on passe par le refresh de : ".$commande->getHumanName()."avec oid: -".$commande->getConfiguration('oid')."-");
                   $res='';
                   if ($commande->getSubType()=='numeric') {
                         $res = $eqlogic->recupNumerique($commande->getConfiguration('oid'));
                         // log::add('snmp_client', 'debug', "valeur: -".$commande->getValue()."- -".$res."-");
  				               $maj = $eqlogic->checkAndUpdateCmd($commande, $res); // on met à jour la commande
                         // log::add('snmp_client', 'debug', "valeur: -".$commande->getValue().'-');
                         // log::add('snmp_client', 'debug', "on refresh une commande numerique -".$commande->getHumanName().'- avec comme resultat: -'.$res."-".$maj);
                   } else {
                          $res = $eqlogic->recupBinaire($commande->getConfiguration('oid'));
                         $maj = $eqlogic->checkAndUpdateCmd($commande, $res); // on met à jour la commande
                         // log::add('snmp_client', 'debug', "on refresh une commande binaire -".$commande->getHumanName().'- avec comme resultat: -'.$res."-".$maj);
                   }
                   log::add('snmp_client', 'debug', "valeur: -".$commande->getValue()."- mesuree: -".$res."-");
                 }
                 if ($eqlogic->getConfiguration('ping') == True) {
                   $eqlogic->checkAndUpdateCmd('presence', $eqlogic->ping());
                 }
				         break;
		         }

    }

    /*     * **********************Getteur Setteur*************************** */
}
