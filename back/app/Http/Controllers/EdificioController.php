<?php

namespace App\Http\Controllers;

use NumberFormatter;

class EdificioController extends Controller
{
    public $ascensores = [];
    public $secuenciass = [];
    public $no_pisos = 3;           // Número de pisos disponibles en el edificio, se tiene en cuenta el piso 0

    public function listarPisos ($hora_evalucion, $no_ascensores)
    {
        /*
         * Se crea el arreglo de ascensores que estaran disponibles,
         * si se necesitan mas solo es necesario agregarlos al arreglo
        */
        for($i=0; $i<$no_ascensores; $i++)
            $this->ascensores[] = new acsendor($i);

        /* Se crea el arreglo de sencuencias que será ejecutado */
        $this->secuencias[] = new secuencia(5,    9,       11,     0,          2);
        $this->secuencias[] = new secuencia(5,    9,       11,     0,          3);
        $this->secuencias[] = new secuencia(10,   9,       10,     0,          1);
        $this->secuencias[] = new secuencia(20,   11,      18.2,   0,          [1,2,3]);
        $this->secuencias[] = new secuencia(4,    14,      15,     [1,2,3],    0);
        $this->secuencias[] = new secuencia(7,    15,      16,     [2,3],      0);
        $this->secuencias[] = new secuencia(7,    15,      16,     0,          [1,3]);
        $this->secuencias[] = new secuencia(3,    18,      20,     [1,2,3],    0);

        // Se encuentra la primera y ultima hora segun las secuencias disponibles
        $hora_inicial = 999;
        $hora_final = 0;
        foreach($this->secuencias as $_secuencia) {
            if ($_secuencia->hora_inicial < $hora_inicial)
                $hora_inicial = $_secuencia->hora_inicial;

            if ($_secuencia->hora_final > $hora_final)
                $hora_final = $_secuencia->hora_final;
        }

        $hora_evalucion = floatval(str_replace("-", ".", $hora_evalucion));

        if ($hora_inicial >$hora_evalucion) return json_encode(["status" => "error", "data" => [$hora_inicial, $hora_final]]);
        if ($hora_final < $hora_evalucion) return json_encode(["status" => "error", "data" => [$hora_inicial, $hora_final]]);

        /*
         * Se ejecuta lazo evaluando desde desde la hora de inicio hasta la hora de evaluacion
         * con incrementos de 1 minto en donde se valida que secuencia debe ser disparada
         */
        $hora_actual = $hora_inicial;
        $_minutos = 0;

        while($hora_actual <= $hora_evalucion) {
            foreach($this->secuencias as $index => $_secuencia) {
                if (in_array($hora_actual, $_secuencia->horas_llamado)) {
                    $this->actuar_ascensor($_secuencia->pisos_llamado, $_secuencia->pisos_destino, $hora_actual, $index);
                }
            }

            $_minutos++;
            if ($_minutos > 59) {
                $_minutos = 0;
                $hora_actual = ceil($hora_actual);
            } else {
                $hora_actual += 0.01;
            }
            $hora_actual = round($hora_actual, 2);
        }
        return json_encode(["status" => "ok", "data" => $this->ascensores]);
    }

    private function actuar_ascensor ($pisos_llamado, $pisos_destino, $hora_actual, $secuencia_actual)
    {
        /*
         * En cada ejecución se puede mover y o varios ascensores, pero cada uno sólo se podran mover una vez
         */
        $ascensores_usados = []; // Arreglo de control no mover el mismo ascensor dos veces
        if( is_array($pisos_llamado)) {
            foreach($pisos_llamado as $_piso_llamado) {
                $idx = $this->buscar_ascensor_cercano($_piso_llamado, $ascensores_usados);
                $this->mover_ascensor($idx, $_piso_llamado, $pisos_destino, $hora_actual, $secuencia_actual);
                $ascensores_usados[] = $idx;
            }
        } else if (is_array($pisos_destino)) {
            foreach($pisos_destino as $_piso_destino) {
                $idx = $this->buscar_ascensor_cercano($pisos_llamado, $ascensores_usados);
                $this->mover_ascensor($idx, $pisos_llamado, $_piso_destino, $hora_actual, $secuencia_actual);
                $ascensores_usados[] = $idx;
            }
        } else {
            $idx = $this->buscar_ascensor_cercano($pisos_llamado, $ascensores_usados);
            $this->mover_ascensor($idx, $pisos_llamado, $pisos_destino, $hora_actual, $secuencia_actual);
            $ascensores_usados[] = $idx;
        }
    }

    private function mover_ascensor ($idx, $piso_llamado, $piso_destino, $hora_actual, $secuencia_actual) {
        $obj_historico = [];

        $obj_historico["hora_movimiento"] = $hora_actual;
        $obj_historico["secuencia_movimiento"] = $secuencia_actual;
        $obj_historico["piso_actual"] = $this->ascensores[$idx]->piso_actual;
        $obj_historico["piso_llamado"] = $piso_llamado;
        $obj_historico["piso_destino"] = $piso_destino;

        $_movidos = abs($this->ascensores[$idx]->piso_actual - $piso_llamado) + abs($piso_llamado - $piso_destino);
        $this->ascensores[$idx]->piso_actual = $piso_destino;
        $this->ascensores[$idx]->pisos_movidos += $_movidos;

        $obj_historico["pisos_movidos"] = $_movidos;
        $obj_historico["total_movidos"] = $this->ascensores[$idx]->pisos_movidos;

        $this->ascensores[$idx]->historico[] = $obj_historico;
    }

    private function buscar_ascensor_cercano ($_piso_llamado, $ascensores_usados) {
        $distancia_llamado = $this->no_pisos;
        $ascensor_seleccionado = 0;

        foreach ($this->ascensores as $index => $_ascensor) {
            if (!in_array($index, $ascensores_usados)) {
                // Se obtiene la distancia minima en valor absoluto porque puede ser subiendo o bajando
                $_dist = abs($_ascensor->piso_actual - $_piso_llamado);
                if($_dist < $distancia_llamado) {
                    $ascensor_seleccionado = $index;
                    $distancia_llamado = $_dist;
                }
            }
        }
        return $ascensor_seleccionado;
    }

    private function renderAscensores () {
        $table = "<table border=1>";
        for($j=0; $j<4; $j++) {
            $table .= "<tr>";
            for($i=0; $i<3; $i++) {
                if($this->ascensores[$i]->piso_actual == (3-$j)) {
                    $table .= "<td style='background-color: red'>&nbsp;</td>";
                } else {
                    $table .= "<td>&nbsp;</td>";
                }
            }
            $table .= "</tr>";
        }
        $table .= "</table>";
        return $table;
    }
}

class acsendor
{
    public $id = 0;
    public $piso_actual = 0;
    public $pisos_movidos = 0;
    public $historico = [];

    public function __construct($_id)
    {
        $this->id = $_id;
    }
}

class secuencia
{
    public $intervalo = 0;          // Intervalo en minutos con que se ejecutará la secuencia
    public $hora_inicial = 0;       // Hora inicial de ejecución de la secuencia
    public $hora_final = 0;         // Hora final de ejecución de la secuencia
    public $piso_llamado = [];      // Arreglo de pisos desde donde seran llamados los asensores
    public $piso_destino = [];      // Arreglo de pisos a donde deberan ir los ascensores
    public $horas_llamado = [];     // Arreglo de horas con minutos en las que será ejecutada esta secuencia

    public function __construct($_intervalo, $_hora_inicial, $_hora_final, $_pisos_llamado, $_pisos_destino)
    {
        $this->intervalo = $_intervalo;
        $this->hora_inicial = $_hora_inicial;
        $this->hora_final = $_hora_final;
        $this->pisos_llamado = $_pisos_llamado;
        $this->pisos_destino = $_pisos_destino;

        // Se genera el arreglo de horas con minutos en las que será ejecutada esta secuencia
        $hora_actual = $_hora_inicial;
        $_horas_llamado = [];
        $_minutos = 0;
        while($hora_actual <= $_hora_final) {
            $_horas_llamado[] = round($hora_actual, 2);
            $_minutos += $_intervalo;
            if ($_minutos > 59) {
                $_minutos = 0;
                $hora_actual = ceil($hora_actual);
            } else {
                $hora_actual += ($_intervalo/100);
            }
        }
        $this->horas_llamado = $_horas_llamado;
    }
}