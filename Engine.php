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
     * @var array
     **/
    private $lineCurrent = array();

    /**
     * @var array
     **/
    private $mapMethodsDoc = array();

    public function __construct()
    {
    }

    function createReflectionClass($p)
    {
        $this->pathToDir = $p['pathToDir'];
        $this->nameClass = $p['nameClass'];

                           require $p['pathToClass'];

        $reflectionClass = new \ReflectionClass($p['nameClassWithNameSpace']);


        $this->getClassDoc($reflectionClass);

        $this->createMapMethodsDoc($reflectionClass);

        $this->getMethodsDoc();
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

    private function createMapMethodsDoc($class)
    {
                 $collection = $class->getMethods();

        foreach ($collection as $reflectonMethod)
        {
            $this->updateMapMethodDoc($reflectonMethod);
        }
    }

    private function updateMapMethodDoc($method)
    {
        $s = $method->getDocComment();
        $b = $this->prepareDocMethod($s);

        $methodName = $method->getName();

            $map = array();

            $map[$methodName]['parameters'] = $method->getParameters();

        foreach ($b as $index => $line)
        {
            $lT = $this->getlineType($line);

            $map[$methodName]['doc'][$index] = array(
                'lineType'       => $lT,
                'lineTypeParent' => null,
                'lineText' => $line,
                'parent'   => null,
                'key'      => null,
            );

            if($lT === 'param')
            {
                $lineEx = explode(" ", $line);

                $map[$methodName]['doc'][$index]['key'] = $lineEx[2];

                $map[$methodName]['doc'][$index]['parent'] = false;
            }

            if($lT === 'return')
            {
                $map[$methodName]['doc'][$index]['key'] = 'return';

                $map[$methodName]['doc'][$index]['parent'] = false;
            }

            if($lT === 'prev')
            {
                $map[$methodName]['doc'][$index]['parent'] = $map[$methodName]['doc'][$index -1]['parent'] === false ? $map[$methodName]['doc'][$index -1]['key'] : $map[$methodName]['doc'][$index -1]['parent'];

                $map[$methodName]['doc'][$index]['lineTypeParent'] = $map[$methodName]['doc'][$index -1]['parent'] === false ? $map[$methodName]['doc'][$index -1]['lineType'] : $map[$methodName]['doc'][$index -1]['lineTypeParent'];
            }
        }

        $this->mapMethodsDoc = $map;
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

    private function getMethodsDoc()
    {
                 $map = $this->mapMethodsDoc;

        foreach ($map as $methodName => $methodMap)
        {
            $this->nameMethodCurrent = $methodName;

            $this->lineCurrent = array();

            $this->getMethodDoc($methodMap['doc']);
        }

            $this->writeFile();
    }

    private function getMethodDoc($map)
    {
        foreach ($map as $index => $lineParameters)
        {
            $this->lineCurrent = array(
                'lineType' => $lineParameters['lineType'],
                'lineTypeParent' => $lineParameters['lineTypeParent'],
                'lineText' => $lineParameters['lineText'],
                'parent'   => $lineParameters['parent'],
                'key'      => $lineParameters['key'],
            );

            $this->setMethodDocController();
        }
    }

    private function setMethodDocController()
    {
                        $lineCurrent = $this->lineCurrent;

                $type = $lineCurrent['lineType'];

        switch ($type)
        {
            case 'prev':
                $this->setMethodDocControllerPrev();
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

                           $lineCurrent = $this->lineCurrent;
        $lineTextCurrent = $lineCurrent['lineText'];


                     $lineEx = explode(" ", $lineTextCurrent);

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
                   $data = $this->data;
        $methods = $data['methods'];

                           $lineCurrent = $this->lineCurrent;
        $lineTextCurrent = $lineCurrent['lineText'];

            $lineEx = explode(" ", $lineTextCurrent);

        $returnType = $lineEx[1];

            unset($lineEx[0],$lineEx[1]);

        $returnDesc = implode(' ', $lineEx);

        if(!isset($methods[$this->nameMethodCurrent]))
        {
            $methods[$this->nameMethodCurrent] = array();
        }

        if(!isset($methods[$this->nameMethodCurrent]['listReturn']))
        {
            $methods[$this->nameMethodCurrent]['listReturn'] = array();
        }

        $methods[$this->nameMethodCurrent] = array_merge(
            $methods[$this->nameMethodCurrent],
            array(
                'listReturn' => array(
                    'type' => $returnType,
                    'desc' => $returnDesc,
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

    private function setMethodDocDesc()
    {
                   $data = $this->data;
        $methods = $data['methods'];

        if(!isset($methods[$this->nameMethodCurrent]))
        {
                  $methods[$this->nameMethodCurrent] = array();
        }

        if(!isset($methods[$this->nameMethodCurrent]['desc']))
        {
                  $methods[$this->nameMethodCurrent]['desc'] = '';
        }

        $descCurrent = $methods[$this->nameMethodCurrent]['desc'];

        $sr = empty($descCurrent) ? '' : ' ';


                           $lineCurrent = $this->lineCurrent;
        $lineTextCurrent = $lineCurrent['lineText'];

        $methods[$this->nameMethodCurrent] = array_merge(
            $methods[$this->nameMethodCurrent],
            array(
                'desc' => $descCurrent .$sr. $lineTextCurrent
            )
        );

        $this->data = array_merge(
            $data,
            array(
                'methods' => $methods
            )
        );
    }

    private function setMethodDocControllerPrev()
    {
                $lineCurrent = $this->lineCurrent;

        switch ($lineCurrent['lineTypeParent'])
        {
            case 'param':
                $this->setMethodDocParamPrev();
            break;
            case 'return':
                $this->setMethodDocReturnPrev();
            break;
        }
    }

    private function setMethodDocParamPrev()
    {
                   $data = $this->data;
        $methods = $data['methods'];

                     $lineCurrent = $this->lineCurrent;

        $paramName = $lineCurrent['parent'];


                   $descCurrent = $methods[$this->nameMethodCurrent]['listParam'][$paramName]['desc'];

                                 $sr = empty($descCurrent) ? '' : ' ';

        $descNew = $descCurrent .$sr. trim($lineCurrent['lineText']);

                                 $methods[$this->nameMethodCurrent]['listParam'][$paramName]['desc'] = $descNew;

        $this->data = array_merge(
            $data,
            array(
                'methods' => $methods
            )
        );
    }

    private function setMethodDocReturnPrev()
    {
                   $data = $this->data;
        $methods = $data['methods'];

        $descCurrent = $methods[$this->nameMethodCurrent]['listReturn']['desc'];

                                 $sr = empty($descCurrent) ? '' : ' ';

                                           $lineCurrent = $this->lineCurrent;

        $descNew = $descCurrent .$sr. trim($lineCurrent['lineText']);

        $methods[$this->nameMethodCurrent]['listReturn']['desc'] = $descNew;

        $this->data = array_merge(
            $data,
            array(
                'methods' => $methods
            )
        );
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

            echo '## Класс '. $this->nameClass ."\n";
            echo "\n";
            echo '### '. $data['docClass'] ."\n";
            echo "\n";

        foreach ($data['methods'] as $methodName =>$methodDoc)
        {
            echo '#### '. $methodName ."\n";
            echo "\n";

            if(!empty($methodDoc['desc']))
            {
                echo 'Описание: '."\n";

                $desc = $methodDoc['desc'];

                echo "$desc"."\n\n";
            }

            echo 'Сигнатура: '."\n\n";
            echo "```php"."\n";
                              $listParamSignature = '';
                              $indexNumSignature = 0;

                     $map = $this->mapMethodsDoc;
            $listP = $map[$methodName]['parameters'];

            foreach ($listP as $param)
            {
                              $indexNumSignature++;

                    $sep = ', ';

                if (count($listP) === $indexNumSignature)
                {
                    $sep = '';
                }
                                       $name = '$'. $param->getName();

                $listParamSignature .= $name .$sep;
            }

            echo "$methodName($listParamSignature)"."\n";
            echo "```"."\n";

            if(!empty($methodDoc['listParam']))
            {
                echo 'Аргументы: '."\n\n";
                echo "| Название | Тип | Описание |"."\n";
                echo "| :--- | :--- | :--- |"."\n";
            }

            foreach ($methodDoc['listParam'] as $param)
            {
                $name = $param['name'];
                $type = $param['type'];
                $desc = $param['desc'];

                echo "| $name | $type | $desc |"."\n";
            }

            if(!empty($methodDoc['listReturn']))
            {
                echo 'Возвращаемое значение: '."\n\n";
                echo "| Тип | Описание |"."\n";
                echo "| :--- | :--- |"."\n";

                $type = $methodDoc['listReturn']['type'];
                $desc = $methodDoc['listReturn']['desc'];

                echo "| $type | $desc |"."\n";
            }
        }

        $dataToWrite = ob_get_clean();


                    $filePath = $this->pathToDir .'/'. $this->nameClass .'.md';

        $fp = fopen($filePath, 'w+');

        fwrite($fp, $dataToWrite);
        fclose($fp);
    }

    function __destruct()
    {

    }
}