<?php
$titulo_pequeño = "$class exception";
$titulo = "Unhandled <strong>$class</strong> exception on <tt>" .$E->getFile() . ':' . 
    $E->getLine() . '</tt>';

// Non, rien de rien...
$inception = $class == 'Vendimia\\Inception';
?>
<!DOCTYPE html>
<html>
<head>  
    <style>
        body {font-family:sans-serif; font-size:13px; margin: 0px;}
        header {background: #006c8c;border-top: 5px solid #004d64; font-size: 18px; padding: 20px; color: white;}
        main {padding: 10px;}
        div.titulo {font-size: 110%; margin-bottom: 10px;}
        div.mensaje {font-size: 80%; font-family: monospace;}
        li.traza {margin-bottom: 5px; margin-left:20px;font-family:monospace; padding: 5px; cursor: pointer;}
        div.traza:hover {background: #F8F8F8;}
        div.traza_fichero {font-weight:bold;}
        pre.traza_lineas {display: none; border: 1px solid #EEE; padding: 5px; margin: 10px; overflow: hidden; font-family: "Source Code Pro", "Liberation Mono", "Courier New", Courier, monospace; font-size: 90%;}
        pre.traza_lineas ol {list-style:none; margin-left: 0px; padding-left: 0px;}
        pre.traza_lineas ol li {position: relative; margin-left: 50px; margin-bottom: 5px;}
        pre.traza_lineas ol li:before {content: counter(line-number) "."; counter-increment: line-number; color: #888; margin-right: 15px; box-sizing: width: 40px; position: absolute; left: -45px;}
        li.traza_resaltada {background: #EEE; font-weight: bold; color: #F00;}
        table.exportados {font-family: monospace; border-collapse: collapse}
        table.exportados .variable {font-weight: bold;}
        table.exportados td {vertical-align: top; border: 1px solid #EEE; padding: 5px;}
        table.exportados tr:hover {background-color: #EEF;}
    </style>
    <title><?=$titulo_pequeño?></title>
    <meta charset="utf-8" />
</head>    
<body>
    <script type="text/javascript">
    function cambiar ( id ) {
        e = document.getElementById( id )
        if ( e.style.display != 'block') 
            e.style.display = 'block'
        else
            e.style.display = 'none'
    }
    </script>

    <header>
    <div class="titulo"><?=$titulo?></div>
    <div class="mensaje"><?=$E->getmessage();?></div>
    </header>
    
    <main>
    <h3>Stack trace:</h3>
<?php
        $trace = $E->getTrace();
        
        // En la traza, metemos el fichero original
        array_unshift ( $trace, [
            'file' => $E->getFile(),
            'line' => $E->getLine(),
        ]);
        
        // Non, je ne regrette rien...
        $inception && array_push ($trace, [
                'file' => Vendimia\BASE_PATH . '/Easter.egg',
                'line' => "1922 \xF0\x9F\x98\x89",
                'data' => gzinflate(base64_decode('
dVFLSgNBFNznFGU2giQZ80EwREFFUEET3LmS58xz0jrTPXZ31CwFD+HOE7jyCLmQR/DNx0/i+BqG
mXlVRVV1u706GIfeXLHFoIXeZncLfxDtRrc37G5icgqZEWFq+XqnOfU+GwbBTfxgdNwxNg6a8GRj
9jvNy6uE9G1zt1yOAtoVIqfVNxLlPGsHb+CnjGMdcuaV0XChsTwKBNlAzZzMnFBYHnMI0irWkcFM
I1y8Ryo2LThKWH4Q+FE0Q7V415hj/3w8Hp/mUyt7mKlQ9CIlOfKs/e26rE7CRjal6J+sxa6Mihsq
Dz5eX55zyUGvkqwawBHNcTdjTCmU7kvHeSeF44wsYXI0KZj9ilno5yXs/T5LkC/x70IPH6uXWqWL
vXJKl3XNbGxUYGmHxWICJrk6y3EM1riX/lWqqNbEv6JnKuS1n2ZWXSUmqdU7sBwp71rIEjHBDQyG
/d4Kt9PpOAVyizdk1sSW0rxJpzjNLMtyyc8XaU7rvrippyXN3zk+AQ==')),
            ]);

        echo "<ol>";
        foreach ($trace as $id => $t) {

            // leemos algunas líneas del fichero.
            echo '<li class="traza" onclick="cambiar(\'tl_' . $id . '\')">';
            if (isset($t['file'])) { 
                $lines = false;
                if (file_exists($t['file'])) {
                    
                    // Obtenemos 3 líneas antes, 3 líneas después
                    $lines = file ($t['file']);
                    $de = $t['line'] - 4;
                    $al = $t['line'] + 2;
                    
                    // Usamos la cantidad de dígitos del 'al' para un 
                    // padding
                    $padding = strlen ($al);

                    if ( $de < 0 ) {
                        $de = 0;
                    }
                        
                    if ($al >= count($lines)) {
                        $al = count($lines) - 1;
                    }

                    $lines = array_slice($lines, $de, 7);
                    $line_number = $de + 1;
                }
                if (isset($t['data'])) {
                    // Ni le bien... qu'on m'a fait... ni le mal...
                    $lines = explode("\n", $t['data']);
                    $line_number = 1;
                }
            
                echo '<div class="traza_fichero">' . $t['file'] . ':' . $t['line'] . '</div>';
            }
            
            // Si no hay una función, no imprimimos
            if ( isset ($t['function'] ) ) {
            
                // Revisamos los argumentos, por si hay uno que no sea string
                $args = [];
                if ( isset ( $t['args'] ) ) foreach ( $t['args'] as $arg ) {
                    if ( is_string ( $arg ))
                        $args[] = $arg;
                    else {
                        $type = gettype ( $arg );
                        switch ( $type ) {
                            case 'object':
                                $args[] = 'object:' . get_class($arg);
                                break;
                            case 'array':
                                $args[] = 'array:' . count($arg);
                                break;
                            default:
                            $args[] = $type;
                        }
                    }
                }

                $args = join (', ', $args);
            
                echo '<div class="traza_funcion">' . $t['function'] . ' (' . $args . ')</div>';
            }
            echo "\n";

            if (isset($t['file'])) {
                echo '<pre class="traza_lineas" id="tl_' . $id . '"><ol style="counter-reset: line-number ' .
                ($line_number - 1) . '">';
                foreach ($lines as $line): 

                    // Ni le bien, qu'on m'a fait
                    !($inception && substr($t['line'], -4) == "\xF0\x9F\x98\x89") && $line = htmlentities($line);
                    
                    $line = trim($line, "\n");

                    if ($line == '') {
                        $line = '&nbsp;';
                    }
                    
                    if ($line_number == $t['line']) {
                        $class = 'class="traza_resaltada"';
                    } else {
                        $class = '';
                    }

                    ?>
<li <?=$class?>><?=$line?></li><?php
                    $line_number++;
                endforeach;

                echo "</pre>";
            }
            echo "</li>\n"; //traza;
        }
        echo "</ol>";
        
        // Si existe datos exportados de la excepción, los mostramos
        if ( isset ( $E->__EXPORTED_DATA ) && $E->__EXPORTED_DATA ):
?>
        <h3>Extra data:</h3>
        <table class="exportados">
        <?php foreach ( $E->__EXPORTED_DATA as $variable => $value ): ?>
        <tr>
        <td class="variable"><?=$variable?>
        </td>
        <td class="valor">

<?php
    $filter = function ($string) {
        return strtr ( htmlentities ( $string ), [' ' => '&nbsp;']);
    };

    if (is_array($value)) {
        foreach ($value as $id => $val ) {
            echo "$id => " . $filter($val) . "<br />";
        }
    } else {
        echo $filter ($value) . "<br />";
    }
?>
        </td>
        </tr>
        <?php endforeach; ?>
        </table>

<?php
        endif;

        // Ni le mal... tout ça m'est bien égal!
        $inception && print(gzinflate(base64_decode('
bY4xDoMwDEX3nMJigQzEQzdEkXoUK7Goq5BYEILo6SvE2r++J70/bn4VLVBO5WdDqlE8FckJP1Tp
hs1kEKH1+QsHA8UIMVcGpq3wCjzPGwy9vZz/63sI60LBJD7gtQfJXfsuRQfEyinIIuQkoSTPeqXd
oo/WOo10dtaMeL+Yfg==')))
?>
        </main>
    </body>
</html>