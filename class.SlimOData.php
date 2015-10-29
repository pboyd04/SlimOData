<?php
require_once('/var/www/html/Slim/Slim.php');
require 'class.ODataServiceDoc.php';
require 'class.CSDLSchema.php';
require 'class.ODataCollection.php';

\Slim\Slim::registerAutoloader();

class SlimOData extends \Slim\Slim
{
    private $serviceDoc;
    private $metadataDoc;

    function __construct($namespace)
    {
        parent::__construct();
        $this->config('debug', true);
        $error_handler = array($this, 'error_handler');
        $this->error($error_handler);
        $this->get('(/)', array($this, 'displayServiceDoc'));
        $this->get('/\$metadata', array($this, 'displayMetadata'));
        $this->serviceDoc = new ODataServiceDoc();
        $this->metadataDoc = new CSDLSchema($namespace);
    }

    function error_handler($e)
    {
        $error = array(
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        );
        $this->response->headers->set('Content-Type', 'application/json');
        echo json_encode($error);
    }

    function displayServiceDoc()
    {
        $fmt = $this->request->headers->get('Accept');
        if(strstr($fmt, 'odata.streaming=true'))
        {
            $this->response->setStatus(406);
            return;
        }
        echo $this->serviceDoc->serialize($fmt, $this);
    }

    function displayMetadata()
    {
        $fmt = $this->request->headers->get('Accept');
        if(strstr($fmt, 'odata.streaming=true'))
        {
            $this->response->setStatus(406);
            return;
        }
        echo $this->metadataDoc->serialize($this);
    }

    public function registerCollection($collection)
    {
        $this->serviceDoc->registerCollection($collection);
        $this->metadataDoc->registerCollection($collection);
        $this->get('/'.$collection->getUrl().'(/(:params+))', array($collection, 'get'));
        $this->patch('/'.$collection->getUrl().'(/(:params+))', array($collection, 'patch'));
        $this->post('/'.$collection->getUrl().'(/(:params+))', array($collection, 'post'));
        $collection->registerApp($this);
    }

    public function overrideServiceDoc($serviceDoc)
    {
        $this->serviceDoc = $serviceDoc;
    }
}
/* vim: set tabstop=4 shiftwidth=4 expandtab: */
?>
