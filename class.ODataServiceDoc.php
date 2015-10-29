<?php
class ODataServiceDoc
{
    private $collections = array();

    public function registerCollection($collection)
    {
        array_push($this->collections, $collection);
    }

    public function serialize($fmt, $app)
    {
        if(trim($fmt) === '')
        {
            $fmt = 'application/json';
        }
        $types = explode(',', $fmt);
        $count = count($types);
        $base = $app->request->getUrl().$app->request->getRootUri();
        for($i = 0; $i < $count; $i++)
        {
            if(strstr($types[$i], 'application/xml') !== false)
            {
                $app->response->headers->set('Content-Type', 'application/xml;odata.metadata=minimal');
                $xml = new SimpleXmlElement('<?xml version="1.0" encoding="utf-8"?><service/>');
                $xml->addAttribute('xmlns', 'http://www.w3.org/2007/app');
                $xml->addAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
                $xml->addAttribute('xmlns:m', 'http://docs.oasis-open.org/odata/ns/metadata');
                $xml->addAttribute('xml:base', $base);
                $xml->addAttribute('m:context', $base.'/$metadata');
                $workspace = $xml->addChild('workspace');
                $title = $workspace->addChild('atom:title', 'Default');
                $title->addAttribute('type', 'text');
                foreach($this->collections as $collection)
                {
                    $col = $workspace->addChild('collection');
                    $col->addAttribute('href', $collection->getUrl());
                    $title = $col->addChild('atom:title', $collection->getName());
                    $title->addAttribute('type', 'text');
                }
                echo $xml->asXml();
                return;
            }
            else if(strstr($types[$i], 'application/json') !== false)
            {
                $app->response->headers->set('Content-Type', 'application/json;odata.metadata=minimal');
                $resp = array();
                $resp['@odata.context'] = $base.'/$metadata';
                $collections = array();
                foreach($this->collections as $collection)
                {
                    array_push($collections, array('name'=>$collection->getName(), 'kind'=>'EntitySet', 'url'=>$collection->getUrl()));
                }
                $resp['value'] = $collections;
                echo json_encode($resp);
                return;
            }
        }
        $app->response->setStatus(406);
        echo 'Unknown format for Service Doc: '.$fmt;
        error_log($fmt);
    }
}
?>
