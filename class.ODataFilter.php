<?php
class ODataFilter
{
    protected $children = array();
    protected $string;

    public function __construct($filter)
    {
        if($filter !== false)
        {
            $this->string = $filter;
            $this->children = self::process_string($this->string);
        }
    }

    static public function process_string($string)
    {
        $parens = false;
        //First check for parenthesis...
        if($string[0] === '(' && substr($string, -1) === ')')
        {
            $string = substr($string, 1, strlen($string)-2);
            $parens = true;
        }
        if(preg_match('/(.+)( and | or )(.+)/', $string, $clauses) === 0)
        {
            return array(new ODataFilterClause($string));
        }
        $children = array();
        if($parens) array_push($children, '(');
        $children = array_merge($children, self::process_string($clauses[1]));
        array_push($children, trim($clauses[2]));
        $children = array_merge($children, self::process_string($clauses[3]));
        if($parens) array_push($children, ')');
        return $children;
    }

    public function filterArray(&$array, $children = false)
    {
        if($children === false)
        {
            $children = $this->children;
        }
        $count = count($children);
        if($count === 1)
        {
            $children[0]->filterArray($array);
        }
        else
        {
            $processedArray = array();
            $opArray = array();
            //Process parens first
            $parens = array();
            for($i = 0; $i < $count; $i++)
            {
                if(!isset($children[$i])) continue;
                if($children[$i] === '(')
                {
                    unset($children[$i]);
                    for($j = $i+1; $j < $count; $j++)
                    {
                        if($children[$j] === ')')
                        {
                            unset($children[$j]); break;
                        }
                        array_push($parens, $children[$j]); 
                        unset($children[$j]);
                    }
                    $index = array_push($processedArray, $array);
                    $this->filterArray($processedArray[$index-1], $parens);
                    $parens = null;
                }
            }
            for($i = 0; $i < $count; $i++)
            {
                if(!isset($children[$i])) continue;
                if(is_object($children[$i]))
                {
                    $index = array_push($processedArray, $array);
                    $children[$i]->filterArray($processedArray[$index-1]);
                }
                else
                {
                    array_push($opArray, $children[$i]);
                }
            }
            $i = 1;
            foreach($opArray as $op)
            {
                if($op === 'and')
                {
                    $tmp = array_uintersect($processedArray[0], $processedArray[$i], array($this, 'compareAll'));
                    $processedArray[0] = $tmp;
                    $i++;
                }
                else
                {
                    throw new Exception('Not handling or yet!');
                }
            }
            $array = $processedArray[0];
        }
    }

    private function compareAll($val1, $val2)
    {
        if($val1 === $val2) return 0;
        return 1;
    }
}
?>
