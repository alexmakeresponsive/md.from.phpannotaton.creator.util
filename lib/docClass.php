<?php

class docClass
{
    private $classEngine = null;

    public $nameClass = null;

    public function __construct($classEngine)
    {
        $this->classEngine = $classEngine;
    }

    private function prepareDocClass($s)
    {
        $sT = trim($s);

        $sTExp = explode("\n", $sT);

        $b = array();

        foreach ($sTExp as $line)
        {
            $lineC = trim(str_replace(array('/', '*'), '', $line));

            if (empty($lineC))
            {
                continue;
            }

            $b[] = $lineC;
        }

        return $b;
    }

    public function getClassDoc($class)
    {
        $s = $class->getDocComment();

        $b = $this->prepareDocClass($s);

        $this->data = array_merge(
            $this->data,
            array(
                'docClass' => implode($b, ' ')
            )
        );
    }
}