<?php
class Steam
{

    private $_steamid;
    private $_friendid;
    private static $esl_email = ""; //Set esl mail address
    private static $esl_pass = ""; Set esl pw

    public function __construct($steam_id="", $friendid="")
    {
        $this->setSteamID($steam_id);
        $this->setFriendID($friendid);
    }

    // getter ... setter
    public function getSteamID()
    {
        return $this->_steamid;
    }

    public function setSteamID($steamId)
    {
        if(substr($steamId, 0, 6)!="STEAM_")
        {
            $steamId = "STEAM_".$steamId;
        }

        if($this->isSteamIdValid($steamId))
        {
            $this->_steamid = $steamId;
            if($this->_friendid=="")
                $this->getFriendIdBySteamId();
            return $this->_steamid;
        }
        return null;
    }

    public function getFriendID()
    {
        return $this->_friendid;
    }

    public function setFriendID($friendid)
    {
        if($this->isFriendIdValid($friendid))
        {
            $this->_friendid = $friendid;
            if($this->_steamid=="")
                $this->getSteamIdByFriendId();
            return $this->_friendid;
        }
        return null;
    }

    // fonctions de verifications
    private function isSteamIdValid($steamId)
    {
        if(substr($steamId, 0, 6)!="STEAM_")
        {
            $steamId = "STEAM_".$steamId;
        }

        $re = '#^STEAM_0:[0-5]:[0-9]{1,12}$#';
        if(preg_match($re, $steamId))
        {
            return(true);
        }
        else
        {
            return(false);
        }
    }

    private function isFriendIdValid($friendId)
    {
        if((substr($friendId, 0, 7)=="7656119")&&(strlen($friendId)==17))
        {
            return(true);
        }
        else
        {
            return(false);
        }
    }

    // calcul des ID
    public function getFriendIdBySteamId()
    {
        $steamId = $this->getSteamID();
        if(!$this->getSteamID())
        {
            throw new Exception('Invalid Steam ID');
        }
        $gameType = 0;
        $authServer = 0;
        $clientId = '';

        // on vire le STEAM_
        $steamId = str_replace('STEAM_', '', $steamId);

        // On le casse en plusieurs morceaux, et on les isoles
        $parts = explode(':', $steamId);
        $gameType = $parts[0];
        $authServer = $parts[1];
        $clientId = $parts[2];

        // On calcul l'id et on le retourne
        $result = bcadd((bcadd('76561197960265728', $authServer)), (bcmul($clientId, '2')));

        $this->setFriendID($result);
        return $result;
    }

    public function getSteamIdByFriendId()
    {
        $friendId = $this->getFriendID();
        if(!$this->isFriendIdValid($friendId))
        {
            throw new Exception('Invalid Friend ID');
        }

        // Si c'est pair, le Y = 0, Sinon il est égal à 1
        $authServer = "1";
        if(bcmod($friendId, "2")=="0")
        {
            $authServer = "0";
        }

        // On calcul le ZZZZZ
        $clientId = bcdiv(bcsub(bcsub($friendId, '76561197960265728'), $authServer), '2');

        // Impossible de determiné si c'est Steam_0 ou Steam_1 .... Dans le doute, on retourne le 0 xD
        $result = 'STEAM_0:'.$authServer.':'.$clientId;

        $this->setSteamID($result);
        return $result;
    }
	
	//ID BO
	public function getBoIdByFriendId()
	{
		$friendId = $this->getFriendID();
		
		if(!$this->isFriendIdValid($friendId))
        {
            throw new Exception('Invalid Friend ID');
        }
		
		$result = $this->bc_base_convert($friendId, 10, 16 );
		
		return $result;
	}
	
	//ID MW3
	public function getMw3IdByFriendId()
	{
		$friendId = $this->getFriendID();
		
		if(!$this->isFriendIdValid($friendId))
        {
            throw new Exception('Invalid Friend ID');
        }
		
		$result = $this->bc_base_convert($friendId, 10, 16 );
		$result = "0".$result;
		
		return $result;
	}
	
	//NoCheatZStatus
	public function getNoCheatZ()
	{
		$steamId = $this->getSteamID();
		
		if(!$this->isSteamIdValid($steamId))
        {
            throw new Exception('Invalid Steam ID');
        }
		
		$data = simplexml_load_file('http://ns14.freeheberg.com/~ncz/gate.php?i='.$steamId, "SimpleXMLElement", LIBXML_NOCDATA);
		
		$retour["STATUS"] = $data->isBanned;
		return $retour;
	}
	
	//Converstion hexa
	public function bc_base_convert($value,$quellformat,$zielformat)
	{
		$vorrat = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		if(max($quellformat,$zielformat) > strlen($vorrat))
		trigger_error('Bad Format max: '.strlen($vorrat),E_USER_ERROR);
		if(min($quellformat,$zielformat) < 2) 
		trigger_error('Bad Format min: 2',E_USER_ERROR);
		$dezi   = '0';
		$level  = 0;
		$result = '';
		$value  = trim((string)$value,"\r\n\t +");
		$vorzeichen = '-' === $value{0}?'-':'';
		$value  = ltrim($value,"-0");
		$len    = strlen($value);
		for($i=0;$i<$len;$i++)
		{
			$wert = strpos($vorrat,$value{$len-1-$i});
			if(FALSE === $wert) trigger_error('Bad Char in input 1',E_USER_ERROR);
			if($wert >= $quellformat) trigger_error('Bad Char in input 2',E_USER_ERROR);
			$dezi = bcadd($dezi,bcmul(bcpow($quellformat,$i),$wert));
		}
		if(10 == $zielformat) return $vorzeichen.$dezi; // abkürzung
		while(1 !== bccomp(bcpow($zielformat,$level++),$dezi));
		for($i=$level-2;$i>=0;$i--)
		{
			$factor  = bcpow($zielformat,$i);
			$zahl    = bcdiv($dezi,$factor,0);
			$dezi    = bcmod($dezi,$factor);
			$result .= $vorrat{$zahl};
		}
		$result = empty($result)?'0':$result;
		
		return $vorzeichen.$result;
	}

    // pêche aux infos
    public function GetUserInfo($idpasse, $date_format="d-m-Y")
    {
        $data=null;
        if($this->isFriendIdValid($idpasse))
        {
            $this->setFriendID($idpasse);
        }
        elseif($this->isSteamIdValid($idpasse))
        {
            $this->setSteamID($idpasse);
        }
        else
        {
            $data = simplexml_load_file('http://steamcommunity.com/id/'.$idpasse.'?xml=1', "SimpleXMLElement", LIBXML_NOCDATA);
            if(isset($data->error))
                    return;
            else
                $this->setFriendID($data->steamID64);
        }

        if($data==null)
            $data = simplexml_load_file('http://steamcommunity.com/profiles/'.$this->getFriendID().'?xml=1', "SimpleXMLElement", LIBXML_NOCDATA);

        $retour["PSEUDO"] = $data->steamID;
		if($retour["PSEUDO"]==""){
			return false;
		}
		    $retour["VISIBLE"] = $data->visibilityState;
        $retour["STEAM_ID"] = $this->_steamid;
        $retour["FRIEND_ID"] = $this->_friendid;
        $retour["ONLINE"] = $data->onlineState;
        $retour["AVATAR"]["PETIT"] = $data->avatarIcon;
        $retour["AVATAR"]["MOYEN"] = $data->avatarMedium;
        $retour["AVATAR"]["GRAND"] = $data->avatarFull;
        $retour["VAC_BANNED"] = $data->vacBanned;
		    $retour["COUNTRY"] = $data->location;
        $retour["DATE_INSCRIPTION"] = $data->memberSince;
        $retour["PUBLIC"] = $data->privacyState;
        $retour["ESL"] = $this->GetEsl();
		    $retour["BO"] = $this->getBoIdByFriendId();
		    $retour["MW3"] = "0".$retour["BO"]; //Pour optimiser on ajoute direct le 0
		    $retour["NOCHEATZ"] = $this->getNoCheatZ();
		    $retour["LGZAC"] = $this->GetLgzAc();
		
        $date = new DateTime($retour["DATE_INSCRIPTION"]);
        $retour["DATE_INSCRIPTION"] = $date->format($date_format);

        return $retour;
    }
	
	public function GetLgzAc()
	{
		$retour = 0;
		$steamId = $this->getSteamID();
		$link_css = 'http://www.leetgamerz.net/fr/anticheat/banlist/gameID/1';
		$link_16 = 'http://www.leetgamerz.net/fr/anticheat/banlist/gameID/2';
		$link_cz = 'http://www.leetgamerz.net/fr/anticheat/banlist/gameID/6';
		$handle_css = fopen($link_css, "r");
		$handle_16 = fopen($link_16, "r");
		$handle_cz = fopen($link_cz, "r");
		if($handle_css){
			while($line = fgets($handle_css)){
				if(preg_match("`".$steamId."`", $line)){
					$retour = 1;
					break;
				}
			}
		}
		if($retour == 0 && $handle_16){
			while($line = fgets($handle_16)){
				if(preg_match("`".$steamId."`", $line)){
					$retour = 1;
					break;
				}
			}			
		}
		if($retour == 0 && $handle_cz){
			while($line = fgets($handle_cz)){
				if(preg_match("`".$steamId."`", $line)){
					$retour = 1;
					break;
				}
			}			
		}
		fclose($handle_cz);
		fclose($handle_16);
		fclose($handle_css);
		
		return $retour;
	}

    public function GetEsl()
    {
        $steamid = $this->getSteamID();
        $steamId = str_replace('STEAM_', '', $steamid);

        $ckfile = tempnam("/tmp", "CURLCOOKIE");
        $post_data = "email_id=".self::$esl_email."&password=".self::$esl_pass."&duration=forever";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "http://www.esl.eu/fr/login/save/");
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $ckfile);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $page = curl_exec($curl);
        curl_close($curl);


        /* STEP 3. visit cookiepage.php */
        $ch = curl_init("http://www.esl.eu/fr/search/?query=".$steamId."&type=gameaccount");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);


        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($output);
        $xpath = new DOMXPath($doc);
        $query = '//div[@class="searchresult"]';
        $entries = $xpath->query($query);

        $xml = simplexml_import_dom($entries->item(1));

        $retour["LIEN"] = "http://www.esl.eu".$xml->div[1];
        $retour["PSEUDO"] = (string) $xml->a;

        if($retour["PSEUDO"]==""){
			return false;
		}
		else{ //Check si le joueur est 12pps cheat
			$retour["CHEAT"] = 0; //Par defaut, le joueur ne cheat pas
			$handle = fopen($retour["LIEN"], "r");
			if($handle){
				while($line = fgets($handle)){
					if(preg_match("`<b>Cheating</b>`", $line)){
						$retour["CHEAT"] = 1;
						break;
					}
				}
			}
			else{
				$retour["CHEAT"] = -1;
			} 
		}

        unlink($ckfile);
        return $retour;
    }

}
