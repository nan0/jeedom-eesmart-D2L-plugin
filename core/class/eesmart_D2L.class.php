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
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class eesmart_D2L extends eqLogic
{
    public static $apiUrl = "https://consospyapi.sicame.io";
    public static $apiKey;


    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     *
     */


    public static function cron()
    {
//        log::add('eesmart_D2L', 'debug', 'Cron log for url ' . eesmart_D2L::$apiUrl);

        eesmart_D2L::$apiKey = eesmart_D2L::getApiKey(eesmart_D2L::$apiUrl, config::byKey('consospyLogin', 'eesmart_D2L'), config::byKey('consospyPassword', 'eesmart_D2L'));
//        log::add('eesmart_D2L', 'debug', "apiKey : " . eesmart_D2L::$apiKey);

        $modules = eesmart_D2L::getModules(eesmart_D2L::$apiUrl, eesmart_D2L::$apiKey);

        /* TODO: implement multiple module management */
        $firstModuleId = $modules[0]->{'idModule'};
//        log::add('eesmart_D2L', 'debug', "idModule : " . $firstModuleId);


//        $lastIndexes = eesmart_D2L::getLastIndexes(eesmart_D2L::$apiUrl, eesmart_D2L::$apiKey, $firstModuleId);
//        log::add('eesmart_D2L', 'debug', "idModule : " . json_encode($lastIndexes));
//        $todayConsumption = eesmart_D2L::getIndexesBetween(eesmart_D2L::$apiUrl, eesmart_D2L::$apiKey, $firstModuleId, $todayMorning->format('c'), $now->format('c'));
//        $reduced = [];
//        foreach ($todayConsumption as $data) {
//            array_push($reduced, $data->hchpEjphpmBbrhpjb);
//        }
//        log::add('eesmart_D2L', 'debug', "todayConsumption log: " . json_encode($reduced));

//        $consumption = $reduced[0] - $reduced[count($reduced) - 1];
//        log::add('eesmart_D2L', 'debug', "todayConsumption : " . $consumption)  ;


        $now = new DateTime();
        $todayMorning = new DateTime($now->format('Y-m-d'));
        $beginIndex = eesmart_D2L::getIndexesBetween(eesmart_D2L::$apiUrl, eesmart_D2L::$apiKey, $firstModuleId, $todayMorning->format('c'), $todayMorning->format('c'));
        $beginConsumption = $beginIndex[0]->hchpEjphpmBbrhpjb;

        $aMinuteAgo = new DateTime('-1 minutes');
        $endIndex = eesmart_D2L::getIndexesBetween(eesmart_D2L::$apiUrl, eesmart_D2L::$apiKey, $firstModuleId, $aMinuteAgo->format('c'), $now->format('c'));
        $endConsumption = $endIndex[0]->hchpEjphpmBbrhpjb;

        $todayConsumption = $endConsumption - $beginConsumption;

        log::add('eesmart_D2L', 'debug', "Today consumption : " . ($todayConsumption / 1000) . " KiloWatts");
        log::add('eesmart_D2L', 'debug', "-\n");

    }

    public static function getApiKey($apiUrl, $login, $password)
    {
        $url = $apiUrl . "/api/D2L/Security/GetAPIKey";
        $data = array('login' => $login, 'password' => $password);

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => "Content-type: application/json\n",
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            throw new Exception();
        }

        return json_decode($result)->{'apiKey'};
    }


    public static function getModules($apiUrl, $apiKey)
    {
        $url = $apiUrl . "/api/D2L/D2Ls";
        $options = array(
            'http' => array(
                'header' => "accept: text/plain\r\n" . "APIKey: " . $apiKey . "\r\n",
                'method' => "GET"
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            throw new Exception();
        }

        return json_decode($result);
    }

    public static function getLastIndexes($apiUrl, $apiKey, $idModule)
    {
        $url = $apiUrl . "/api/D2L/D2Ls/" . $idModule . "/LastIndexes";
        $options = array(
            'http' => array(
                'header' => "accept: text/plain\r\n" . "APIKey: " . $apiKey . "\r\n",
                'method' => "GET"
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            throw new Exception();
        }

        return json_decode($result);
    }

    public static function getIndexesBetween($apiUrl, $apiKey, $idModule, $from, $to)
    {

        $fromParam = "?from=" . urlencode($from);
        $toParam = "&to=" . urlencode($to);
        $url = $apiUrl . "/api/D2L/D2Ls/" . $idModule . "/IndexesBetween" . $fromParam . $toParam;

        $options = array(
            'http' => array(
                'header' => "accept: text/plain\r\n" . "APIKey: " . $apiKey . "\r\n",
                'method' => "GET"
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            throw new Exception();
        }

        return json_decode($result);
    }


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */


    /*     * *********************Méthodes d'instance************************* */

    public function preInsert()
    {

    }

    public function postInsert()
    {

    }

    public function preSave()
    {

    }

    public function postSave()
    {

    }

    public function preUpdate()
    {

    }

    public function postUpdate()
    {

    }

    public function preRemove()
    {

    }

    public function postRemove()
    {

    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class eesmart_D2LCmd extends cmd
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array())
    {

    }

    /*     * **********************Getteur Setteur*************************** */
}


