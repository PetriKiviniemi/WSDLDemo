<?php
// CLIENT
ini_set("soap.wsdl_cache_enabled", "0");

// DANGEROUS! - Use next lines only for debugging purposes.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Function to pretty print the SOAP request and response
function pp_soapenvelope($client) {
    echo "==REQUEST\n";
    $doc = new DOMDocument('1.0');
    $doc->formatOutput = true;
    $request = $client->__getLastRequest();
    if($request)
    {
        $doc->loadXML($request);
        print $doc->saveXML();
    }
    else
    {
        echo "Error: No request available!\n";
    }

    echo "\n\n==RESPONSE\n";
    $doc = new DOMDocument('1.0');
    $doc->formatOutput = true;
    $response = $client->__getLastResponse();
    if($response)
    {
        $doc->loadXML($response);
        print $doc->saveXML();
    }
    else
    {
        echo "Error: No response available!\n";
    }
}

class Debuff{
    public $uuid;
    public $description;
    public $effects;

    public function __construct($uuid, $description, $effects)
    {
        $this->uuid = $uuid;
        $this->description = $description;
        $this->effects = $effects;
    }
}

class NaturalDisasterData {
    public $id;
    public $name;
    public $duration;
    public $timeoccured;
    public $disasterDebuffs;

    public function __construct($id, $name, $duration, $timeoccured, $disasterDebuffs)
    {
        echo "Called";
        $this->id = $id;
        $this->name = $name;
        $this->duration = $duration;
        $this->timeoccured = $timeoccured;
        $this->disasterDebuffs = $disasterDebuffs;
    }
}

function CREATE_OPERATION()
{
    $wsdlUrl = "https://wwwlab.cs.univie.ac.at/~kiviniemip35/player_service.wsdl";

    $opts = array(
        'trace' => 1,
        'exceptions' => 1,
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_NONE,
    );

    //Try to fetch from server
    try {
        $client = new SoapClient($wsdlUrl, $opts);

        header('content-type: text/plain');

        //Create a natural disaster and it's debuffs
        $debuffs = array();
        array_push($debuffs,
            new Debuff(
                "255a7517-6762-47e3-86cd-e59bdd83b8bc",
                "Bleeding debuff",
                array("Lose one health every 2 minutes"),
            )
        );

        $naturalDisasterData = new NaturalDisasterData(
            15, "Earthquake", 20.123,
            "1717429017", $debuffs
        );

        $result = $client->CreateNaturalDisaster(1, 1, $naturalDisasterData);
        pp_soapenvelope($client);

        echo "\n\n==Runtime Object Deserialized from the SOAP Response\n";
        print_r($result);

    } catch (SoapFault $e) {
        echo "SOAP error: (error: {$e->faultcode}, message: {$e->faultstring})";
    }
}

//TODO:: Return JSON
function READ_OPERATION()
{
    $wsdlUrl = "https://wwwlab.cs.univie.ac.at/~kiviniemip35/player_service.wsdl";

    $opts = array(
        'trace' => 1,
        'exceptions' => 1,
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_NONE,
    );

    try
    {
        $client = new SoapClient($wsdlUrl, $opts);
        header('content-type: text/plain');

        $playerName = "Charles Lee";
        $result = $client->GetPlayerStats(1, $playerName);

        pp_soapenvelope($client);
        echo "\n\n==Runtime Object Deserialized from the SOAP Response\n";
        print_r($result);
    } 
    catch (SoapFault $e)
    {
        echo "SOAP error: (error: {$e->faultcode}, message: {$e->faultstring})";
    }
}

function UPDATE_OPERATION()
{
    $wsdlUrl = "https://wwwlab.cs.univie.ac.at/~kiviniemip35/player_service.wsdl";

    $opts = array(
        'trace' => 1,
        'exceptions' => 1,
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_NONE,
    );

    try
    {
        $client = new SoapClient($wsdlUrl, $opts);
        header('content-type: text/plain');

        //Create a natural disaster and it's debuffs
        $debuffs = array();
        array_push($debuffs,
            new Debuff(
                "255a7517-6762-47e3-86cd-e59bdd83b8bc",
                "Bleeding debuff",
                array("Lose one health every 2 minutes"),
            )
        );

        // We are using existing ID, so we should update the 
        // Original disaster with ID 1 to this new one
        $naturalDisasterData = new NaturalDisasterData(
            1, "Latest Earthquake", 20.123,
            "1717429017", $debuffs
        );

        $result = $client->UpdateNaturalDisaster(1, 1, $naturalDisasterData);
        pp_soapenvelope($client);

        echo "\n\n==Runtime Object Deserialized from the SOAP Response\n";
        print_r($result);
    }
    catch(SoapFault $e)
    {
        echo "SOAP error: (error: {$e->faultcode}, message: {$e->faultstring})";
    }
}

function DELETE_OPERATION()
{
    $wsdlUrl = "https://wwwlab.cs.univie.ac.at/~kiviniemip35/player_service.wsdl";

    $opts = array(
        'trace' => 1,
        'exceptions' => 1,
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_NONE,
    );

    try
    {
        $client = new SoapClient($wsdlUrl, $opts);
        header('content-type: text/plain');

        $playerName = "Charles Lee";
        $result = $client->DeleteNaturalDisastersFromTimestamp(1, 1, "2020-10-09T23:55:15.708769");

        pp_soapenvelope($client);
        echo "\n\n==Runtime Object Deserialized from the SOAP Response\n";
        print_r($result);
    } 
    catch (SoapFault $e)
    {
        echo "SOAP error: (error: {$e->faultcode}, message: {$e->faultstring})";
    }
}

if (isset($_POST['action'])) {
  $action = $_POST['action'];
  
  // Implement your CRUD logic based on the action
  // This is a basic example, you'll need to replace it with your actual functionality
  switch ($action) {
    case 'create':
      CREATE_OPERATION();
      break;
    case 'read':
      READ_OPERATION();
      break;
    case 'update':
      UPDATE_OPERATION();
      break;
    case 'delete':
      DELETE_OPERATION();
      break;
    default:
      $message = "Invalid action!";
  }
}

?>
