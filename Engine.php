<?php

class Engine
{
    private $data = array(
        'docClass' => null,
        'methods'  => array(),
    );

    private $pathToDir = null;

    private $nameClass = null;

    /**
     * @var string
     **/
    private $nameMethodCurrent = null;

    /**
     * @var string desc|param|return|prev
     **/
    private $lineTypeTarget = null;

    /**
     * @var string desc|param|return|prev
     **/
    private $lineTypeCurrent = null;

    /**
     * @var string desc|param|return|prev
     **/
    private $lineTypePrev = null;

    /**
     * @var string desc|param|return|prev
     **/
    private $mapMethodsDoc = array();

    /**
     * @var string
     **/
    private $lineTextCurrent = '';

    public function __construct()
    {
    }

    function createReflectionClass($p)
    {
        $this->pathToDir = $p['pathToDir'];
        $this->nameClass = $p['nameClass'];

                           require $p['pathToClass'];

        $reflectionClass = new \ReflectionClass($p['nameClassWithNameSpace']);


//        $this->getClassDoc($reflectionClass);

        $this->createMapMethodsDoc($reflectionClass);
        $this->getMethodsDoc($reflectionClass);
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

    private function prepareDocMethod($s)
    {
        $sT = trim($s);

        $sTExp = explode("\n", $sT);

        $b = array();

        foreach ($sTExp as $line)
        {
            $lineC = str_replace(array('/', '*'), '', trim($line));

            $lineCs = substr($lineC, 1);

            if (!preg_match("/[A-Za-zА-Яа-я]/", $lineCs))
            {
                continue;
            }

            $b[] = $lineCs;
        }

        return $b;
    }

    private function getClassDoc($class)
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

    private function createMapMethodsDoc($class)
    {
                 $collection = $class->getMethods();

        foreach ($collection as $reflectonMethod)
        {
            $this->updateMapMethodDoc($reflectonMethod);
        }

        var_dump($this->mapMethodsDoc);
    }

    private function updateMapMethodDoc($method)
    {
        $s = $method->getDocComment();
        $b = $this->prepareDocMethod($s);

        $methodName = $method->getName();

            $map = array();

        foreach ($b as $line)
        {
            $lT = $this->getlineType($line);

            $map[$methodName][] = array(
                'lineType' => $lT,
                'lineText' => $line,
            );
        }

        $this->mapMethodsDoc = $map;
    }


    private function getMethodsDoc($class)
    {
        $map = $this->mapMethodsDoc;

        foreach ($map as $methodName => $methodMap)
        {
            $this->nameMethodCurrent = $methodName;

            $this->getMethodDoc($methodMap);
        }

//            $this->writeFile();
    }

    private function getMethodDoc($map)
    {
        foreach ($map as $index => $lineParameters)
        {
            $this->lineTypeCurrent = $lineParameters['lineType'];

            $this->setMethodDocController();
        }
    }

    private function getlineType($l)
    {
                    $lExp = explode(' ', $l);


                $t = null;


            if (empty($lExp[0]))
            {
                $t = 'prev';
            }
            else
            {
                $t = 'desc';
            }

        switch (substr($lExp[0], 0, 2))
        {
            case '@p':
                $t = 'param';
            break;
            case '@r':
                $t = 'return';
            break;
        }

        return $t;
    }

    private function lineController()
    {
            $lT = $this->getlineType($this->lineTextCurrent);

        if (empty($this->lineTypeTarget))
        {
                  $this->lineTypeTarget = $lT;              // ??
        }

        if ($this->lineTypeCurrent === 'prev')
        {

        }

                  $this->lineTypeCurrent = $lT;

        $this->setMethodDocController();
    }

    private function setMethodDocController()
    {
        switch ($this->lineTypeCurrent)
        {
            case 'prev':
                $this->setMethodDocPrev();
            break;
            case 'desc':
                $this->setMethodDocDesc();
            break;
            case 'param':
                $this->setMethodDocParam();
            break;
            case 'return':
                $this->setMethodDocReturn();
            break;
        }
    }

    private function setMethodDocParam()
    {
                   $data = $this->data;
        $methods = $data['methods'];

                     $lineEx = explode(" ", $this->lineTextCurrent);

        $paramType = $lineEx[1];
        $paramName = $lineEx[2];

            unset($lineEx[0],$lineEx[1],$lineEx[2]);

        $paramDesc = implode(' ', $lineEx);


        if(!isset($methods[$this->nameMethodCurrent]))
        {
                  $methods[$this->nameMethodCurrent] = array();
        }

        if(!isset($methods[$this->nameMethodCurrent]['listParam']))
        {
                  $methods[$this->nameMethodCurrent]['listParam'] = array();
        }

        $methods[$this->nameMethodCurrent]['listParam'] = array_merge(
            $methods[$this->nameMethodCurrent]['listParam'],
            array(
                $paramName => array(
                    'name' => $paramName,
                    'type' => $paramType,
                    'desc' => $paramDesc,
                )
            )
        );

        $this->data = array_merge(
            $data,
            array(
                'methods' => $methods
            )
        );
    }

    private function setMethodDocReturn()
    {

    }

    private function setMethodDocDesc()
    {

    }

    private function setMethodDocPrev()
    {
                   $data = $this->data;
        $methods = $data['methods'];

        var_dump($this->lineTypeTarget);
    }



    private function dataPrepare()
    {
                 $data = $this->data;

                 $methodsPrepare = array();

        foreach ($data['methods'] as $methodName => $methodDoc)
        {

                $methodsPrepare[$methodName] = $methodDoc;

            if(empty($methodDoc['desc']))
            {
                $methodsPrepare[$methodName]['desc'] = null;
            }

            if(empty($methodDoc['listParam']))
            {
                $methodsPrepare[$methodName]['listParam'] = array();
            }

            if(empty($methodDoc['listReturn']))
            {
                $methodsPrepare[$methodName]['listReturn'] = array();
            }
        }

        $this->data = array_merge(
            $data,
            array(
                'methods' => $methodsPrepare
            )
        );
    }

    private function writeFile()
    {
                $this->dataPrepare();

        $data = $this->data;


                ob_start();

//            echo '## Класс '. $this->nameClass ."\n";
//            echo "\n";
//            echo '### '. $data['docClass'] ."\n";
//            echo "\n";
//
//        foreach ($data['methods'] as $methodName =>$methodDoc)
//        {
//            echo '#### '. $methodName ."\n";
//            echo "\n";
//
//            if(!empty($methodDoc['desc']))
//            {
//                echo 'Описание: '."\n";
//
//                $desc = $methodDoc['desc'];
//
//                echo "$desc"."\n\n";
//            }
//
//            echo 'Сигнатура: '."\n\n";
//            echo "```php"."\n";
//                              $listParamSignature = '';
//                              $indexNumSignature = 0;
//
//            foreach ($methodDoc['listParam'] as $param)
//            {
//                              $indexNumSignature++;
//
//                    $sep = ', ';
//
//                if (count($methodDoc['listParam']) === $indexNumSignature)
//                {
//                    $sep = '';
//                }
//                                       $name = $param['name'];
//
//                $listParamSignature .= $name .$sep;
//            }
//
//            echo "$methodName($listParamSignature)"."\n";
//            echo "```"."\n";
//
//            if(!empty($methodDoc['listParam']))
//            {
//                echo 'Аргументы: '."\n\n";
//                echo "| Название | Тип | Описание |"."\n";
//                echo "| :--- | :--- | :--- |"."\n";
//            }
//
//            foreach ($methodDoc['listParam'] as $param)
//            {
//                $name = $param['name'];
//                $type = $param['type'];
//                $desc = $param['desc'];
//
//                echo "| $name | $type | $desc |"."\n";
//            }
//
//            if(!empty($methodDoc['listReturn']))
//            {
//                echo 'Возвращаемое значение: '."\n\n";
//                echo "| Тип | Описание |"."\n";
//                echo "| :--- | :--- |"."\n";
//
//                $type = $methodDoc['listReturn']['type'];
//                $desc = $methodDoc['listReturn']['desc'];
//
//                echo "| $type | $desc |"."\n";
//            }
//        }

            print_r($data);

        $dataToWrite = ob_get_clean();


                    $filePath = $this->pathToDir .'/'. $this->nameClass .'.txt';

        $fp = fopen($filePath, 'w+');

        fwrite($fp, $dataToWrite);
        fclose($fp);
    }

    function __destruct()
    {

    }
}