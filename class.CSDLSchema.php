<?php
class CSDLSchema
{
    private $namespace;
    private $xml;
    private $schema;
    private $entityContainer;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
        $this->xml = new DOMDocument();
        $root = $this->xml->createElement('edmx:Edmx');
        $root->setAttribute('xmlns:edmx', 'http://docs.oasis-open.org/odata/ns/edmx');
        $root->setAttribute('Version', '4.0');
        $this->xml->appendChild($root);
        $dataService = $this->xml->createElement('edmx:DataServices');
        $root->appendChild($dataService);
        $this->schema = $this->xml->createElement('edm:Schema');
        $this->schema->setAttribute('xmlns:edm', 'http://docs.oasis-open.org/odata/ns/edm');
        $this->schema->setAttribute('Namespace', $namespace);
        $dataService->appendChild($this->schema);
        $this->entityContainer = $this->xml->createElement('edm:EntityContainer');
        $this->entityContainer->setAttribute('Name', 'ODataService');
        $this->schema->appendChild($this->entityContainer);
    }

    public function registerCollection($collection)
    {
        $entitySet = $this->xml->createElement('edm:EntitySet');
        $entitySet->setAttribute('Name', $collection->getName());
        $entitySet->setAttribute('EntityType', $this->namespace.'.'.$collection->getTypeName());
        $this->entityContainer->appendChild($entitySet);
        $schema = $collection->getCSDLSchemaForType();
        if($schema === false || $schema === null) return;
        $types = $this->xml->importNode($schema->documentElement, true);
        $this->schema->insertBefore($types, $this->entityContainer);
    }

    public function serialize($app)
    {
        $app->response->headers->set('Content-Type', 'application/xml');
        return $this->xml->saveXML();
    }
}
?>
