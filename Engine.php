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
     * @var string desc|param|return|prev
     * */
    private $lineType = null;

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

    private function getClassMethods($class)
    {
                 $collection = $class->getMethods();

        foreach ($collection as $reflectonMethod)
        {
            $this->getMethodDoc($reflectonMethod);
            echo "\n";
        }

        //    $this->writeFile();
    }

    private function getMethodDoc($method)
    {
        $s          = $method->getDocComment();
        $nameMethod = $method->getName();

        $b = $this->prepareDocMethod($s);

        if(empty($b))
        {
            return;
        }

        var_dump($nameMethod . "");

        foreach ($b as $line)
        {
                                  $p = array(
                                      'nameMethod' => $nameMethod,
                                      'line'       => $line,
                                  );


            $this->lineType = $this->getlineType($line);

            var_dump($this->lineType);

            //$this->lineController($p);
        }

//        die;
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

    private function lineController($p)
    {
                   $line = $p['line'];

        if (substr($line, 0, 1) !== '@')
        {
                $this->setMethodDocDesc($p);
            return;
        }

        switch (substr($line, 0, 2))
        {
            case '@p':
                $this->setMethodDocParam($p);
            break;
            case '@r':
                $this->setMethodDocReturn($p);
            break;
        }
    }

    private function setMethodDocParam($p)
    {
        $this->lineType = 'param';
    }

    private function setMethodDocReturn($p)
    {
        $this->lineType = 'return';
    }

    private function setMethodDocDesc($p)
    {
        $this->lineType = 'desc';
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


        return;

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