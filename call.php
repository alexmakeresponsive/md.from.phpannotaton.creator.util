<?php

// php call.php --pathToDir "/Volumes/data/work/software/web/code/backend/php/packages/cms/project/bitrix/home/lib/gbxtp.d7iblockapidatamanager/src"

require_once __DIR__ . '/Engine.php';


$options = getopt('ptc:', array(
    'pathToDir:',
));

if (empty($options['pathToDir']))
{
    die("Need set pathToDir \n");
}



                     $countLevel = 0;
                     $dir        = $options['pathToDir'];
                     $listDhNotClosed = array();

         dirReadDown($dir);

function dirReadDown($dir)
{
    global $countLevel, $engine, $listDhNotClosed;

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

        $engine = new Engine();

        $engine->createReflectionClass(array(
            'pathToClass'            => $dir,
            'pathToDir'              => $path_parts['dirname'],
            'nameClass'              => $path_parts['filename'],
            'nameClassWithNameSpace' => $namespace .'\\'. $path_parts['filename'],
        ));
    }

    if (is_dir($dir))
    {
        $countLevel++;

        if ($countLevel>9)
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