<?php

class Engine
{
    private $data = array(
        'docClass' => null,
        'methods'  => array(),
    );

    private $pathToDir = null;
    private $nameClass = null;

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
        $this->getClassMethods($reflectionClass);
    }

    private function prepareDoc($s)
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

    private function getClassDoc($class)
    {
        $s = $class->getDocComment();

        $b = $this->prepareDoc($s);

        $this->data = array_merge(
            $this->data,
            array(
                'docClass' => implode($b, ' ')
            )
        );
    }

    private function getClassMethods($class)
    {
                 $collection = $class->getMethods();

        foreach ($collection as $reflectonMethod)
        {
            $this->getMethodDoc($reflectonMethod);
        }

            $this->writeFile();
    }

    private function getMethodDoc($method)
    {
        $s          = $method->getDocComment();
        $nameMethod = $method->getName();

        $b = $this->prepareDoc($s);

        if(empty($s))
        {
            return;
        }

        foreach ($b as $line)
        {
                                  $p = array(
                                      'nameMethod' => $nameMethod,
                                      'line'       => trim($line),
                                  );

            $this->lineController($p);
        }
    }

    private function lineController($p)
    {
        switch (substr($p['line'], 0, 2))
        {
            case '@p':
                $this->getMethodDocParam($p);
            break;
            case '@r':
                $this->getMethodDocReturn($p);
            break;
            default:
                $this->getMethodDocDesc($p);
            break;
        }
    }

    private function getMethodDocParam($p)
    {
        $data    = $this->data;
        $methods = $data['methods'];

        if(empty($p['line']))
        {
            $methods[$p['nameMethod']]['listParam'] = array();
            return;
        }

        $lineEx = explode(" ", $p['line']);

                     $lineEx[2] = empty($lineEx[2]) ? 'mixed' : $lineEx[2];


        $paramType = $lineEx[1];
        $paramName = $lineEx[2];

        unset($lineEx[0],$lineEx[1],$lineEx[2]);

        $paramDesc = implode(' ', $lineEx);


                                    if(!isset($methods[$p['nameMethod']]['listParam']))
                                    {
                                              $methods[$p['nameMethod']]['listParam'] = array();
                                    }

                             $methods[$p['nameMethod']] = array_merge(
                                 $methods[$p['nameMethod']],
                                 array(
                                     'listParam' => array_merge(
                                         $methods[$p['nameMethod']]['listParam'],
                                         array(
                                             $paramName => array(
                                                 'name' => $paramName,
                                                 'type' => $paramType,
                                                 'desc' => $paramDesc,
                                             )
                                         )
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

    private function getMethodDocReturn($p)
    {
        $data    = $this->data;
        $methods = $data['methods'];

        if(empty($p['line']))
        {
            $methods[$p['nameMethod']]['listReturn'] = array();
            return;
        }

        $lineEx = explode(" ", $p['line']);

        $returnType = $lineEx[1];

        unset($lineEx[0],$lineEx[1]);

        $returnDesc = implode(' ', $lineEx);

                            if(!isset($methods[$p['nameMethod']]['listReturn']))
                            {
                                      $methods[$p['nameMethod']]['listReturn'] = array();
                            }

                            $methods[$p['nameMethod']] = array_merge(
                                $methods[$p['nameMethod']],
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

    private function getMethodDocDesc($p)
    {
        $data    = $this->data;
        $methods = $data['methods'];

        if(empty($p['line']))
        {
           $methods[$p['nameMethod']]['desc'] = null;
           return;
        }
                             if(!isset($methods[$p['nameMethod']]['desc']))
                             {
                                       $methods[$p['nameMethod']]['desc'] = null;
                             }
                                                                    $descCurrent = $methods[$p['nameMethod']]['desc'];

                                                        $sr = empty($descCurrent) ? '' : ' ';

                             $methods[$p['nameMethod']] = array_merge(
                                 $methods[$p['nameMethod']],
                                 array(
                                     'desc' => $descCurrent .$sr. $p['line']
                                 )
                             );

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

            echo 'Сигнатура: '."\n\n";
            echo "```php"."\n";
                              $listParamSignature = '';
                              $indexNumSignature = 0;

            foreach ($methodDoc['listParam'] as $param)
            {
                              $indexNumSignature++;

                    $sep = ', ';

                if (count($methodDoc['listParam']) === $indexNumSignature)
                {
                    $sep = '';
                }
                                       $name = $param['name'];

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