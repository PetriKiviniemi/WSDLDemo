<?php

// We need to have this function to convert from the StdClass to DOMNode
function naturalDisasterStdClassToDOMNode($data, $xml) {
    try
    {
        $naturalDisasterElement = $xml->createElement('naturaldisaster');
        $naturalDisasterElement->setAttribute('id', $data->id);
        $naturalDisasterElement->setAttribute('name', $data->name);

        $durationElement = $xml->createElement('duration', $data->duration);
        $naturalDisasterElement->appendChild($durationElement);

        $dateTime = new DateTime("@{$data->timeoccured}");
        $dateTime->setTimezone(new DateTimeZone('UTC'));
        $formattedDateTime = $dateTime->format('Y-m-d\TH:i:s.u'); // Format to 'Y-m-d\TH:i:s.u'
        $timeOccurredElement = $xml->createElement('timeoccurred', $formattedDateTime);
        $naturalDisasterElement->appendChild($timeOccurredElement);

        foreach ($data->disasterDebuffs as $disasterDebuff) {
            $debuffElement = $xml->createElement('debuff');

            $uuidElement = $xml->createElement('uuid', $disasterDebuff->uuid);
            $debuffElement->appendChild($uuidElement);

            $descriptionElement = $xml->createElement('description', $disasterDebuff->description);
            $debuffElement->appendChild($descriptionElement);

            $effectsElement = $xml->createElement('effects');
            foreach ($disasterDebuff->effects as $effect) {
                $effectElement = $xml->createElement('effect', $effect);
                $effectsElement->appendChild($effectElement);
            }

            $debuffElement->appendChild($effectsElement);
            $naturalDisasterElement->appendChild($debuffElement);
        }

        return $naturalDisasterElement;
    } catch (Exception $e)
    {
        echo "Error parsing NaturalDisaster StdClass: {$e}";
        return null;
    }
}

function NaturalDisasterDOMToStdClass ($NDDom)
{
    $updatedNaturalDisasterStdClass = new StdClass;
    $updatedNaturalDisasterStdClass->id = $NDDom->getAttribute('id');
    $updatedNaturalDisasterStdClass->name = $NDDom->getAttribute('name');
    $updatedNaturalDisasterStdClass->duration = $NDDom->getElementsByTagName('duration')->item(0)->nodeValue;
    $updatedNaturalDisasterStdClass->timeoccured= $NDDom->getElementsByTagName('timeoccured')->item(0)->nodeValue;
    $debuffObject = $NDDom->getElementsByTagName("debuff")->item(0);

    $updatedNaturalDisasterStdClass->debuff = new StdClass;
    $updatedNaturalDisasterStdClass->debuff->uuid = $debuffObject->getElementsByTagName("uuid")->item(0)->nodeValue;
    $updatedNaturalDisasterStdClass->debuff->description = $debuffObject->getElementsByTagName("description")->item(0)->nodeValue;

    $effectsElement = $debuffObject->getElementsByTagName("effects")->item(0);
    $updatedNaturalDisasterStdClass->debuff->effects = [];

    if ($effectsElement) {
        // Get all 'effect' nodes within the 'effects' element
        $effectNodes = $effectsElement->getElementsByTagName("effect");

        foreach ($effectNodes as $effectNode) {
            $effectObj = new StdClass;
            $effectObj->effect = $effectNode->nodeValue;

            // Add the effect object to the effects array
            $updatedNaturalDisasterStdClass->debuff->effects[] = $effectObj;
        }
    }

    return $updatedNaturalDisasterStdClass;
}


class PlayerService {
    private $xmlFile = 'output1.xml';

    private function loadXML() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->load($this->xmlFile);
        return $xml;
    }

    private function saveXML($xml) {
        $xml->save($this->xmlFile);
        return $xml;
    }

    public function GetPlayerStats($id, $name) {
        $xml = $this->loadXML();
        $xpath = new DOMXPath($xml);


        //Using xpath to retrieve 
        $query = sprintf("//player/stats[name='%s']", $name);
        $playerStats = $xpath->query($query)->item(0);

        if ($playerStats) {
            $doc = new DOMDocument();
            $doc->appendChild($doc->importNode($playerStats, true));

            $playerStatsString = $doc->saveXML();

            return $playerStatsString;
        } else {
            echo "No player info found with name {$name}";
            return null;
        }
    }

    /*
    PARAMS:
    $playerId: int
    $farmingBotId: int
    $naturalDisasterData: object
        - id: int
        - name: string
        - duration: float
        - timeoccurred: timestamp (unix)
        - arrayOfDebuffs: array of debuff objects
            - debuff: object
                -   uuid: string
                -   description: string
                -   effects: array
                    -   effect: string
    */
    public function CreateNaturalDisaster($playerId, $farmingBotId, $naturalDisasterData)
    {
        // Load the xml
        $xml = $this->loadXML();
        $xpath = new DOMXPath($xml);

        // Check if the natural disaster with that id already exists
        $query = sprintf("//player[@id='%d']//farmingbot[@id='%d']//naturaldisaster[@id='%d']", $playerId, $farmingBotId, $naturalDisasterData->id);
        $createdNaturalDisasterId = $xpath->query($query)->item(0);

        if($createdNaturalDisasterId)
        {
            return -1;
        }

        //Find the farmingbot of where we will be placing the natural disaster
        $farmingBotQuery = sprintf("//player[@id='%d']/farmingbots/farmingbot[@id='%d']/naturaldisasters", $playerId, $farmingBotId);
        $farmingBotNaturalDisasters = $xpath->query($farmingBotQuery)->item(0);

        $naturalDisasterDOMNode = naturalDisasterStdClassToDOMNode($naturalDisasterData, $xml);

        if($farmingBotNaturalDisasters && $naturalDisasterDOMNode)
        {
            $farmingBotNaturalDisasters->appendChild($naturalDisasterDOMNode);
            $xml->save($this->xmlFile);

            $xpath = new DOMXPath($xml);
            // Check if the natural disaster was created correctly
            $query = sprintf("//player[@id='%d']//farmingbot[@id='%d']//naturaldisaster[@id='%d']", $playerId, $farmingBotId, $naturalDisasterData->id);
            $createdNaturalDisasterId = $xpath->query($query)->item(0);
            return $createdNaturalDisasterId->getAttribute("id");
        }

        return -1;
    }

    //This function assumes that we do not have partial but instead the full natural disaster data
    public function UpdateNaturalDisaster($playerId, $farmingBotId, $naturalDisasterData)
    {
        // Load the xml
        $xml = $this->loadXML();
        $xpath = new DOMXPath($xml);

        // Check if the natural disaster with that id already exists
        $query = sprintf("//player[@id='%d']//farmingbot[@id='%d']//naturaldisaster[@id='%d']", $playerId, $farmingBotId, $naturalDisasterData->id);
        $originalNaturalDisaster = $xpath->query($query)->item(0);

        if($originalNaturalDisaster)
        {
            //Lets update the natural disaster
            $updatedNaturalDisasterFromParams = naturalDisasterStdClassToDOMNode($naturalDisasterData, $xml);
            $originalNaturalDisaster = $updatedNaturalDisaster;
            $xml->save($this->xmlFile);

            //Check that it got updated correctly
            $xpath = new DOMXPath($xml);
            // Check if the natural disaster was created correctly
            $query = sprintf("//player[@id='%d']//farmingbot[@id='%d']//naturaldisaster[@id='%d']", $playerId, $farmingBotId, $naturalDisasterData->id);
            $updatedNaturalDisasterFromDOM = $xpath->query($query)->item(0);

            if($updatedNaturalDisasterFromDOM == $updatedNaturalDisasterFromParams)
                return NaturalDisasterDOMToStdClass($updatedNaturalDisasterFromDOM);
        }
        return -1;
    }

    public function DeleteNaturalDisastersFromTimestamp($playerId, $farmingBotId, $timestamp)
    {
        // Load the xml
        $xml = $this->loadXML();
        $xpath = new DOMXPath($xml);

        // Check if the natural disaster with that id already exists
        $query = sprintf(
            "//player[@id='%d']//farmingbot[@id='%d']/naturaldisasters//naturaldisaster",
            $playerId,
            $farmingBotId,
        );

        $naturalDisasters = $xpath->query($query);

        // Lets filter them in PHP since the filtering via XPath does not work
        // I tried using filtering like //naturaldisaster[timeoccurred > '%s'] and $timestamp
        // Yet it yielded 0 results

        $deletedDisasters = [];

        foreach ($naturalDisasters as $ND)
        {
            $NDtimestamp = $ND->getElementsByTagName("timeoccurred")->item(0)->nodeValue;
            if($NDtimestamp >= $timestamp)
            {
                // Store the ID before deleting
                $NDId = $ND->getAttribute("id");
                $parent = $ND->parentNode;
                $parent->removeChild($ND);

                $xml->save($this->xmlFile);
                //Check that it was deleted
                $xpath = new DOMXPath($xml);
                $query = sprintf("//player[@id='%d']//farmingbot[@id='%d']//naturaldisaster[@id='%d']", $playerId, $farmingBotId, $NDId);
                $deletedNaturalDisasterFromDOM = $xpath->query($query)->item(0);

                if(!$deletedNaturalDisasterFromDOM)
                {
                    array_push($deletedDisasters, $NDId);
                }
            }
        }

        return $deletedDisasters;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $test = new PlayerService();
    header('content-type: text/plain');
    print_r($test->GetPlayerInfo('Charles Lee'));
    exit;
}

// Handle requests of a WSDL-SOAP client.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   ini_set("soap.wsdl_cache_enabled","0");
   $opts = array(
       'trace' => 1,
       'exceptions' => 1,
       'soap_version' => SOAP_1_1,
       'cache_wsdl' => WSDL_CACHE_NONE,
   );
   $server = new SoapServer('https://wwwlab.cs.univie.ac.at/~kiviniemip35/player_service.wsdl', $opts);
   $server->setClass('PlayerService');
   $server->handle();
   exit;
}

?>