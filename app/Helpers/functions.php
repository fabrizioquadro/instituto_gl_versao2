<?php

if (! function_exists('valorFormDb')) {
    /**
     * Converte um valor digitado no formulário (ex.: "1.234,56") para o
     * formato aceito pelo banco ("1234.56"). Igual à V1.
     */
    function valorFormDb($valor)
    {
        // procurar se foi digitada a vírgula
        $virgula = strpos($valor, ',');

        if ($virgula === false) {
            $valor = str_replace('.', '', $valor);
            $valor = $valor.'.00';

            return $valor;
        }

        $var = explode(',', $valor);
        $variavel = $var[1];
        $var = str_replace('.', '', $var[0]);
        $valor = $var.'.'.$variavel[0].$variavel[1];

        return $valor;
    }
}

if (! function_exists('valorDbForm')) {
    /**
     * Converte um valor do banco (ex.: "1234.56") para exibição
     * ("1.234,56"). Igual à V1.
     */
    function valorDbForm($valor)
    {
        return number_format($valor, 2, ',', '.');
    }
}

if (! function_exists('dataDbForm')) {
    /**
     * Converte uma data do banco (Y-m-d ou Y-m-d H:i:s) para
     * exibição (d/m/Y). Igual à V1.
     */
    function dataDbForm($data)
    {
        if (! $data) {
            return null;
        }

        if (strpos($data, ' ') !== false) {
            $partes = explode(' ', $data);

            return dataDbForm($partes[0]).' '.$partes[1];
        }

        $data = explode('-', $data);

        if (count($data) !== 3) {
            return null;
        }

        return $data[2].'/'.$data[1].'/'.$data[0];
    }
}
