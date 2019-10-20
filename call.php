<?php

// php call.php --pathToDir "/Volumes/data/work/software/web/code/backend/php/packages/cms/project/bitrix/home/lib/gbxtp.d7iblockapidatamanager/src"

require_once __DIR__ . '/Engine.php';


$options = getopt('ptc:ptd:', array(
    'pathToSrc:',
    'pathToDoc:',
));

if (empty($options['pathToSrc']))
{
    die("Need set pathToSrc \n");
}



                     $countLevel = 0;
                     $dir        = $options['pathToSrc'];
                     $listDhNotClosed = array();

         dirReadDown($dir);

function dirReadDown($dir)
{
    global $countLevel, $engine, $listDhNotClosed, $options;

                         $path_parts = pathinfo($dir);

    if (is_file($dir) && $path_parts['extension'] === 'php')
    {
        $namespace = '';

            $handle = @fopen($dir, "r");
        if ($handle)
        {
                $countHandleLine = 0;

            while (($buffer = fgets($handle)) !== false)
            {
                $countHandleLine++;

                if ($countHandleLine > 3)
                {
                    break;
                }

                if(empty(trim($buffer)))
                {
                    continue;
                }

                if($countHandleLine === 1)
                {
                    continue;
                }
                                                       $posSpace = strpos($buffer, ' ');

                                    $bufferTrim = trim($buffer);

                $namespace = substr($bufferTrim, $posSpace + 1, -1);
            }
                       fclose($handle);
        }


            $isClass = strpos('QWERTYUIOPASDFGHJKLZXCVBNM', substr($path_parts['filename'], 0, 1));

        if(!$isClass)
        {
            return;
        }


        $engine = new Engine();

        $engine->createReflectionClass(array(
            'pathToClass'            => $dir,
            'pathToDir'              => $path_parts['dirname'],
            'pathToDoc'              => $options['pathToDoc'],
            'nameClass'              => $path_parts['filename'],
            'nameClassWithNameSpace' => $namespace .'\\'. $path_parts['filename'],
        ));
    }

    if (is_dir($dir))
    {
        $countLevel++;

        if ($countLevel>99)
        {
            var_dump($listDhNotClosed);

            foreach ($listDhNotClosed as $dhNotClosed)
            {
                closedir($dhNotClosed);
            }

            var_dump('max deep');

            return;
        }

        if ($dh = opendir($dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                if (in_array($file, array('.', '..', '.DS_Store')))
                {
                    continue;
                }
                            $filePath = $dir .'/'. $file;

                dirReadDown($filePath);
            }

            $listDhNotClosed[] = $dh;

            closedir($dh);
        }
    }
}