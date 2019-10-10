<?php

require_once './lib/docClass.php';
require_once './lib/docMethods.php';

class Engine
{
    private $classDocClass   = null;
    private $classDocMethods = null;

    private $data = array(
        'docClass'   => null,
        'docMethods' => array(),
    );

    private $pathToDir = null;

    public function __construct()
    {
        $this->classDocClass   = new docClass($this);
        $this->classDocMethods = new docMethods($this);
    }

    function createReflectionClass($p)
    {
        $this->pathToDir = $p['pathToDir'];

                           require $p['pathToClass'];

        $reflectionClass = new \ReflectionClass($p['nameClassWithNameSpace']);


        $this->classDocClass->getClassDoc($reflectionClass);
        $this->classDocMethods->getMethodsDoc($reflectionClass);

        $this->writeFile();
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

            echo '## Класс '. $this->classDocClass->nameClass ."\n";
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

                     $map = $this->classDocMethods->mapMethodsDoc;
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


                    $filePath = $this->pathToDir .'/'. $this->classDocClass->nameClass .'.md';

        $fp = fopen($filePath, 'w+');

        fwrite($fp, $dataToWrite);
        fclose($fp);
    }

    function __destruct()
    {

    }
}