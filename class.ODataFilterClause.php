<?php
class ODataFilterClause
{
    public $var1;
    public $var2;
    public $op;

    function __construct($string=false)
    {
        if($string !== false) $this->process_filter_string($string);
    }

    static function str_startswith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    protected function process_filter_string($string)
    {
        if(self::str_startswith($string, 'substringof'))
        {
            $this->op   = strtok($string, '(');
            $this->var1 = strtok(',');
            $this->var2 = strtok(')');
            return;
        }
        $field = strtok($string, ' ');
        $op = strtok(' ');
        $rest = strtok("\0");
        switch($op)
        {
            case 'ne':
                $op = '!=';
                break;
            case 'eq':
                $op = '=';
                break;
            case 'lt':
                $op = '<';
                break;
            case 'le':
                $op = '<=';
                break;
            case 'gt':
                $op = '>';
                break;
            case 'ge':
                $op = '>=';
                break;
        }
        $this->var1  = $field;
        $this->op    = $op;
        $this->var2  = $rest;
        if($this->var2 === 'null')
        {
            $this->var2 = null;
        }
    }

    public function filterArray(&$array)
    {
        $res = array();
        foreach($array as $member)
        {
            if(is_object($member))
            {
                $member = json_decode(json_encode($member), true);
            }
            switch($this->op)
            {
                case '=':
                    if($this->var2 === null)
                    {
                        if(strchr($this->var1, '.') !== false && strchr($this->var1, '@') === false)
                        {
                            $parts = explode('.', $this->var1);
                            if(!isset($member[$parts[0]]) || !isset($member[$parts[0]][$parts[1]]) || $member[$parts[0]][$parts[1]] === null)
                            {
                                array_push($res, $member);
                            }
                        }
                        else if(!isset($member[$this->var1]) || $member[$this->var1] === null)
                        {
                            array_push($res, $member);
                        }
                    }
                    else
                    {
                        if(strchr($this->var1, '.') !== false && strchr($this->var1, '@') === false)
                        {
                            $parts = explode('.', $this->var1);
                            if(isset($member[$parts[0]]) && isset($member[$parts[0]][$parts[1]]) && $member[$parts[0]][$parts[1]] == $this->var2)
                            {
                                array_push($res, $member);
                            }
                        }
                        else if(isset($member[$this->var1]) && $member[$this->var1] == $this->var2)
                        {
                            array_push($res, $member);
                        }
                    }
            }
        }
        $array = $res;
    }
}
?>
