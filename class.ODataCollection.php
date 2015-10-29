<?php
abstract class ODataCollection
{
    protected $name;
    protected $url;
    protected $type;
    protected $app;

    public function __construct($name = null, $url = null)
    {
        $this->name = $name;
        $this->url  = $url;
        $this->type = array();
    }

    private function makeArray($objs, $value)
    {
        array_shift($objs);
        $res = array();
        $count = count($objs);
        if($count === 1)
        {
            $res[$objs[0]] = $value;
        }
        else
        {
            $res[$objs[0]] = $this->makeArray($objs, $value);
        }
        return $res;
    }

    protected function cleanupArray(&$array)
    {
        foreach($array as $key=>$value)
        {
            if($key[0] === '@' || strstr($key, '@')) continue;
            $objs = explode('.', $key);
            $count = count($objs);
            if($count > 1)
            {
                if(isset($array[$objs[0]]))
                {
                    $array[$objs[0]] = array_merge($array[$objs[0]], $this->makeArray($objs, $value));
                }
                else
                {
                    $array[$objs[0]] = $this->makeArray($objs, $value);
                }
                unset($array[$key]);
            }
        }
        foreach($array as $key=>&$value)
        {
            if(is_array($value))
            {
                $this->cleanupArray($value);
            }
        }
    }

    public function get()
    {
        $args = func_get_args();
        $params = $this->app->request->params();
        $res = false;
        if(empty($args))
        {
            $res = $this->getAll($params);
        }
        else
        {
            $args = $args[0];
            $child = $this->getChild($args[0]);
            if($child === false)
            {
                $this->app->notFound();
            }
            else if(isset($args[1]))
            {
                array_shift($args);
                $res = $child->processArgs($args);
            }
            else
            {
                $res = $child;
            }
        }
        if(is_object($res))
        {
            $res = json_decode(json_encode($res), true);
        }
        if(!isset($res['@odata.id']))
        {
            $res['@odata.id'] = $this->app->request->getPath();
        }
        $this->cleanupArray($res);
        if(isset($params['$select']))
        {
            $select = explode(',', $params['$select']);
            $res = array_intersect_key($res, array_flip($select));
        }
        if(isset($params['$top']) || isset($params['$skip']))
        {
            $top = 0;
            $skip = 0;
            if(isset($params['$top']))
            {
                $top = $params['$top'];
            }
            if(isset($params['$skip']))
            {
                $skip = $params['$skip'];
            }
            if(isset($res['Members']))
            {
                $res['Members'] = array_slice($res['Members'], $skip, $top);
            }
            else
            {
                $res = array_slice($res, $skip, $top);
            }
        }
        if(isset($params['$count']) && strcasecmp($params['$count'], 'false') === 0)
        {
            if(isset($res['Members@odata.count']))
            {
                unset($res['Members@odata.count']);
            }
        }
        $this->app->response->headers->set('Content-Type', 'application/json');
        echo json_encode($res);
    }

    public abstract function getAll(&$params=false);
    public abstract function getChild($Id);

    public function patch($args)
    {
        if(empty($args))
        {
            $this->opNotAllowed();
        }
        else
        {
            $obj = json_decode($this->app->request->getBody());
            $res = $this->patchChild($args, $obj);
            $this->app->response->headers->set('Content-Type', 'application/json');
            echo json_encode($res);
        }
    }

    public function patchChild($args, $obj)
    {
        $this->opNotAllowed();
    }

    public function post($args)
    {
        if(empty($args))
        {
            $this->opNotAllowed();
        }
        else
        {
            $obj = json_decode($this->app->request->getBody());
            $res = $this->postChild($args, $obj);
            $this->app->response->headers->set('Content-Type', 'application/json');
            echo json_encode($res);
        }
    }

    public function postChild($args, $obj)
    {
        $this->opNotAllowed();
    }

    protected function opNotAllowed()
    {
        $this->app->halt(405);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getTypeName()
    {
        $keys = array_keys($this->type);
        if(isset($keys[0]))
        {
            return $keys[0];
        }
        return false;
    }

    public function getCSDLSchemaForType()
    {
        $values = array_values($this->type);
        if(isset($values[0]))
        {
            return $values[0];
        }
        return false;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setType($name, $csdl = false)
    {
        $this->type = array();
        if($csdl !== false)
        {
            $this->type[$name] = DOMDocument::load($csdl, LIBXML_NOERROR | LIBXML_NOWARNING);
        }
        else
        {
            $this->type[$name] = false;
        }
    }

    public function registerApp($app)
    {
        $this->app = $app;
    }

    protected function parseParams()
    {
        if($this->app == false) return;
        $ret = array();
        $params = $this->app->request->params();
        $count = count($params);
        if($count === 0)
        {
            return;
        }
        foreach($params as $name=>$value)
        {
            if($name[0] === '$')
            {
                switch($name)
                {
                    case '$top':
                        if(is_numeric($value))
                        {
                            $ret[$name] = intval($value);
                            unset($params[$name]);
                        }
                        else
                        {
                            throw new Exception('Incorrect format for $top argument \''.$value.'\'');
                        }
                        break;
                    case '$select':
                        $ret[$name] = explode(',', $value);
                        unset($params[$name]);
                        break;
                }
            }
            else
            {
                $ret[$name] = $value;
                unset($params[$name]);
            }
        }
        if(count($params) > 0)
        {
            print_r($params); die();
        }
        if(!isset($ret['$top']))
        {
            $ret['$top'] = false;
        }
        if(!isset($ret['$select']))
        {
            $ret['$select'] = false;
        }
        return $ret;
    }
}
?>
