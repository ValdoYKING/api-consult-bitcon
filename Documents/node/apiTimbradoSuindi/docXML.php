<?php
public function generaNomina($id_nomina)
    {
        /* Cargamos a libreria del cfdi */
        require_once 'application/libraries/cfdi/SWSDK.php';
        header('Content-type: text/plain');
        /* Creamos la carpeta donde se guardara el archivo */
        $path = "assets/ReciboNominas/" . $id_nomina;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        /* TRaemos todos los datos que vamos a utilizar para la generacion del xml */
        $this->load->model('mconsultas');
        $this->load->model("meditar");
        $dataNomina = $this->mconsultas->ConsultaSQL("SELECT * FROM T_Recibo_Nomina WHERE id_recibo_nomina = " . $id_nomina);
        $dataPartidaPercepcion = $this->mconsultas->ConsultaSQL("SELECT * FROM T_partidas_percepciones_nomina WHERE id_recibo_nomina = " . $id_nomina);
        $dataPartidaDeduccion = $this->mconsultas->ConsultaSQL("SELECT * FROM T_partidas_deducciones_nomina WHERE id_recibo_nomina = " . $id_nomina);
        $dataPartidaOtroPago = $this->mconsultas->ConsultaSQL("SELECT * FROM T_partidas_pago WHERE id_recibo_nomina = " . $id_nomina);
        $idDivision = $dataNomina[0]['division'];
        /* $dataDivSucursal = $this->mconsultas->ConsultaSQL("SELECT * FROM t_sucursal WHERE id_sucursal = " . $idDivision);
        $id_razon_social = $dataDivSucursal[0]['fk_id_razon_social']; */
        if (!empty($idDivision)) {
            $dataRazonSocial = $this->mconsultas->ConsultaSQL("SELECT * FROM T_Razon_Social WHERE id_razon_social = " . $idDivision);
        } else {
            $dataRazonSocial = '';
        }
        $dataPersonal = $this->mconsultas->ConsultaSQL("SELECT * FROM t_datos_personales WHERE fk_no_empleado = " . $dataNomina[0]['no_emp_receptor']);
        $dataDatosFiscales = $this->mconsultas->ConsultaSQL("SELECT * FROM t_datos_fiscales WHERE fk_no_empleado = " . $dataNomina[0]['no_emp_receptor']);
        $dataRegimenFiscal = $this->mconsultas->ConsultaSQL("SELECT * FROM t_catalogo_regimen_fiscal WHERE id_cat_regimen = " . $dataRazonSocial[0]['id_regimen_fiscal']);
        $clave_regimen = $dataRegimenFiscal[0]['clave_regimen'];
        $dataRegimenFiscalEmp = $this->mconsultas->ConsultaSQL("SELECT * FROM t_catalogo_regimen_fiscal WHERE id_cat_regimen = " . $dataDatosFiscales[0]['fk_regimen_fiscal']);
        $calve_regimen_emp = $dataRegimenFiscalEmp[0]['clave_regimen'];
        $dataPersonalEmp = $this->mconsultas->ConsultaSQL("SELECT * FROM t_datos_personales WHERE fk_no_empleado = " . $dataNomina[0]['no_emp_receptor']);
        $id_datos_personales = $dataPersonalEmp[0]['id_datos_personales'];
        $dataDireccionEmp = $this->mconsultas->ConsultaSQL("SELECT * FROM t_direccion_empleado WHERE fk_id_datos_personales = " . $id_datos_personales);
        $cpEmp = $dataDireccionEmp[0]['cp'];
        $dataEstados = $this->mconsultas->ConsultaSQL("SELECT * FROM T_estados WHERE id_estado = " . $dataDatosFiscales[0]['fk_entidad_federativa']);
        $clave_sat_estado = $dataEstados[0]['clave_sat'];
        $dataSindicalizado = $this->mconsultas->ConsultaSQL("SELECT * FROM sindicalizado WHERE id_sindicalizado = " . $dataNomina[0]['id_sindicalizado']);
        $claveSatSindicalizado = $dataSindicalizado[0]['clave_sat'];
        $datacTipoRegimen = $this->mconsultas->ConsultaSQL("SELECT * FROM c_tipo_regimen WHERE id = " . $dataDatosFiscales[0]['fk_tipo_regimen']);
        $clave_sat_tipo_regimen = $datacTipoRegimen[0]['clave_sat'];
        $registroPatronalRazonSocial = $dataRazonSocial[0]['registro_patronal'];
        $cpEmpDataFiscales = $dataDatosFiscales[0]['cp_emp_n'];

        $sqlSumatoriaOP = "SELECT
        SUM( importe ) AS totalOP 
        FROM
        T_partidas_pago
        WHERE
        id_recibo_nomina = $id_nomina";
        $dataSumatoriaOP = $this->mconsultas->ConsultaSQL($sqlSumatoriaOP);

        $sqlSumatoriaP = "SELECT
        SUM( importe_grabado ) AS totalPercepcion 
        FROM
        T_partidas_percepciones_nomina 
        WHERE
        id_recibo_nomina = $id_nomina";
        $dataSumatoriaP = $this->mconsultas->ConsultaSQL($sqlSumatoriaP);

        $valorUnitarioS = $dataSumatoriaP[0]['totalPercepcion'] + $dataSumatoriaOP[0]['totalOP'];

        $sqlSumatoriaD = "SELECT
        SUM( importe ) AS totalDeduccion 
        FROM
        T_partidas_deducciones_nomina 
        WHERE
        id_recibo_nomina = $id_nomina";
        $dataSumatoriaD = $this->mconsultas->ConsultaSQL($sqlSumatoriaD);

        $sqlSumOtraDeduccion = "SELECT
        SUM(
            CASE
                WHEN id_concepto <> 'ISR' THEN importe
                ELSE 0
            END
        ) AS totalOtraDeduccion 
        FROM
        T_partidas_deducciones_nomina 
        WHERE
        id_recibo_nomina = $id_nomina";
        $dataSumOtraDeduccion = $this->mconsultas->ConsultaSQL($sqlSumOtraDeduccion);

        $sqlSumatoriaPExcento = "SELECT
        SUM( importe_excento ) AS totalImporteExcento
        FROM
        T_partidas_percepciones_nomina 
        WHERE
        id_recibo_nomina = $id_nomina";
        $dataSumatoriaPExcento = $this->mconsultas->ConsultaSQL($sqlSumatoriaPExcento);

        $sumaTotalSueldos = $dataSumatoriaP[0]["totalPercepcion"] + $dataSumatoriaPExcento[0]["totalImporteExcento"];
        $numeroSD = str_replace(',', '', $sumaTotalSueldos);
        $numeroSD = (float) $numeroSD;
        $numeroSD = round($numeroSD, 2);

        /* Declaramos el constructor para crear el documento xml */
        $xml = new DOMDocument();
        /* creamos la cabezera del archivo */
        $xml->encoding = "UTF-8";
        $xml->xmlVersion = "1.0";
        $xml->formatOutput = true;
        //$xml->standalone = true;
        /* creamos los nuevos elementos */
        $xml_comprobante = $xml->createElement('cfdi:Comprobante');
        $emisor = $xml->createElement('cfdi:Emisor');
        $rec = $xml->createElement('cfdi:Receptor');
        $conceptos = $xml->createElement('cfdi:Conceptos');
        $complementoN = $xml->createElement('cfdi:Complemento');

        /* Creamos los atributos del elemento Comprobante */
        $schemaLocation = $xml->createAttribute('xmlns:cfdi');
        $schemaLocation->value = 'http://www.sat.gob.mx/cfd/4';

        $cfdi = $xml->createAttribute('xmlns:xsi');
        $cfdi->value = 'http://www.w3.org/2001/XMLSchema-instance';

        $xmlns = $xml->createAttribute('xsi:schemaLocation');
        $xmlns->value = 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd http://www.sat.gob.mx/nomina12 http://www.sat.gob.mx/sitio_internet/cfd/nomina/nomina12.xsd';

        $nomina12 = $xml->createAttribute('xmlns:nomina12');
        $nomina12->value = 'http://www.sat.gob.mx/nomina12';

        $version = $xml->createAttribute('Version');
        $version->value = '4.0';

        $serieN = $xml->createAttribute('Serie');
        $serieN->value = $dataNomina[0]["serie_gral"];

        $folio = $xml->createAttribute('Folio');
        $folio->value = $dataNomina[0]["folio_gral"];

        // Obtener la fecha y hora en formato deseado
        date_default_timezone_set('America/Mexico_City');
        $fechaHoraServer = date("Y-m-d\TH:i:s");
        $fecha_hora_prueba = "2023-08-16T20:01:05";

        $fecha = $xml->createAttribute('Fecha');
        $fecha->value = $fechaHoraServer;
        // $fecha->value = $fecha_hora_prueba;

        $sello = $xml->createAttribute('Sello');
        @$sello->value = "";

        $NoCertificado = $xml->createAttribute('NoCertificado');
        @$NoCertificado->value = "";

        $Certificado = $xml->createAttribute('Certificado');
        @$Certificado->value = "";

        /* Subtotal de la nomina que recibe */
        $SubTotal = $xml->createAttribute('SubTotal');
        $SubTotal->value = $numeroSD;

        /* Descuento de nomina */
        $descuentoN = $xml->createAttribute('Descuento');
        $redondea_descuento = number_format($dataSumatoriaD[0]["totalDeduccion"], 2);
        $descuentoN->value = $redondea_descuento;

        $Moneda = $xml->createAttribute('Moneda');
        $Moneda->value = "MXN";

        /*El resultado de restar el descuento al subtotal */
        $totalN = $numeroSD - $dataSumatoriaD[0]["totalDeduccion"];
        $Total = $xml->createAttribute('Total');
        $Total->value = $totalN;

        $TipoDeComprobante = $xml->createAttribute('TipoDeComprobante');
        $TipoDeComprobante->value = "N";

        $MetodoPago = $xml->createAttribute('MetodoPago');
        // $MetodoPago->value = $datosFactura[0]["MetodoPago"];
        $MetodoPago->value = "PUE";

        /* CP DE EMPRESA */
        $LugarExpedicion = $xml->createAttribute('LugarExpedicion');
        $LugarExpedicion->value = $dataRazonSocial[0]["cp"];

        $Exportacion = $xml->createAttribute('Exportacion');
        $Exportacion->value = "01";

        /* Creamos los atributos del elemento Emisor */
        /* Razon social de la empresa que timbra */
        $Rfc = $xml->createAttribute('Rfc');
        $Rfc->value = $dataNomina[0]["rfc_emisor"];

        $Nombre = $xml->createAttribute('Nombre');
        $Nombre->value = $dataRazonSocial[0]["nombre_razon_social"];

        $RegimenFiscal = $xml->createAttribute('RegimenFiscal');
        @$RegimenFiscal->value = "$clave_regimen";

        /* Creamos los atributos del elemento Receptor */
        $RfcR = $xml->createAttribute('Rfc');
        $RfcR->value = $dataPersonal[0]["rfc"];

        /* Datos del empleado que se tramita su nomina */
        $NombreEmp = $dataPersonal[0]["nombre"];
        $ApellidoPEmp = $dataPersonal[0]["a_paterno"];
        $ApellidoMEmp = $dataPersonal[0]["a_materno"];
        $nombreCompleto = $NombreEmp . " " . $ApellidoPEmp . " " . $ApellidoMEmp;
        $nombreMayusculas = $this->convertirNombreMayusculasSinAcentos($nombreCompleto);
        $NombreR = $xml->createAttribute('Nombre');
        $NombreR->value = $nombreMayusculas;

        $UsoCFDI = $xml->createAttribute('UsoCFDI');
        $UsoCFDI->value = 'CN01';

        /* Crear apartado dentro de los datos personales para agergar estos datos fiscales */
        $RegimenFiscalReceptor = $xml->createAttribute('RegimenFiscalReceptor');
        $RegimenFiscalReceptor->value = "$calve_regimen_emp";

        $DomicilioFiscalReceptor = $xml->createAttribute('DomicilioFiscalReceptor');
        $DomicilioFiscalReceptor->value = "$cpEmpDataFiscales";
        // $DomicilioFiscalReceptor->value = '06900';

        $ClaveProdServ = $xml->createAttribute('ClaveProdServ');
        $ClaveProdServ->value = 84111505;
        /* Solo se maneja un concepto */
        $concepto = $xml->createElement('cfdi:Concepto');
        $conceptos->appendChild($concepto);

        $cantidad = $xml->createAttribute('Cantidad');
        $cantidad->value = 1;

        $ClaveUnidad = $xml->createAttribute('ClaveUnidad');
        $ClaveUnidad->value = "ACT";

        $Descripcion = $xml->createAttribute('Descripcion');
        $Descripcion->value = $dataNomina[0]["descripcion_concepto"];

        $numeroVD = str_replace(',', '', $valorUnitarioS);
        $numeroVD = (float) $numeroVD;
        $numeroVD = round($numeroVD, 2);
        $ValorUnitario = $xml->createAttribute('ValorUnitario');
        // $ValorUnitario->value = $numeroVD;
        $ValorUnitario->value = $numeroSD;

        $Importe = $xml->createAttribute('Importe');
        // $Importe->value = $numeroVD;
        $Importe->value = $numeroSD;

        $descuentoCN = $xml->createAttribute("Descuento");
        $descuentoCN->value = $redondea_descuento;

        $ObjetoImp = $xml->createAttribute('ObjetoImp');
        $ObjetoImp->value = '01';

        $concepto->appendChild($cantidad);
        $concepto->appendChild($ClaveProdServ);
        $concepto->appendChild($ClaveUnidad);
        $concepto->appendChild($Descripcion);
        $concepto->appendChild($descuentoCN);
        $concepto->appendChild($Importe);
        $concepto->appendChild($ValorUnitario);
        $concepto->appendChild($ObjetoImp);

        /* Datos para llenado de nomina */
        $nominaVN = $xml->createElement('nomina12:Nomina');
        $complementoN->appendChild($nominaVN);

        $FechaFinalPago = $xml->createAttribute('FechaFinalPago');
        $FechaFinalPago->value = $dataNomina[0]["fecha_final_pago_recibo"];
        $nominaVN->appendChild($FechaFinalPago);

        $FechaInicialPago = $xml->createAttribute('FechaInicialPago');
        $FechaInicialPago->value = $dataNomina[0]["fecha_inicial_pago_recibo"];
        $nominaVN->appendChild($FechaInicialPago);

        $FechaPago = $xml->createAttribute('FechaPago');
        $FechaPago->value = $dataNomina[0]["fecha_pago_recibo"];
        $nominaVN->appendChild($FechaPago);

        $amount = $dataNomina[0]["numero_dias_pagados_recibo"];
        // Si el número tiene decimales, formatea con dos decimales y ceros adicionales
        if (floor($amount) != $amount) {
            $formattedAmount = number_format($amount, 3, '.', '');
        } else {
            $formattedAmount = number_format($amount, 0);
        }
        $NumDiasPagados = $xml->createAttribute('NumDiasPagados');
        $NumDiasPagados->value = $formattedAmount;
        $nominaVN->appendChild($NumDiasPagados);

        $idTipoNomina = $dataNomina[0]['id_tipo_pago_recibo'];
        $dataTipoNomina = $this->mconsultas->ConsultaSQL("SELECT * FROM t_tipo_nomina WHERE id_tipo_nomina = " . $idTipoNomina);
        $TipoNomina = $xml->createAttribute('TipoNomina');
        $TipoNomina->value = $dataTipoNomina[0]["clave_sat"];
        $nominaVN->appendChild($TipoNomina);

        /* La suma de las partidas de deducciones */
        $TotalDeducciones = $xml->createAttribute('TotalDeducciones');
        $dataSumatoriaRedondeo = number_format($dataSumatoriaD[0]["totalDeduccion"], 2);
        $TotalDeducciones->value = $dataSumatoriaRedondeo;
        $nominaVN->appendChild($TotalDeducciones);

        $TotalOtrosPagos = $xml->createAttribute('TotalOtrosPagos');
        $sumaOP = $dataSumatoriaOP[0]["totalOP"];
        if ($sumaOP == 0) {
            $totalOP = '0';
        } else {
            $totalOP = $dataSumatoriaP[0]["totalOP"];
        }
        $TotalOtrosPagos->value = $totalOP;
        $nominaVN->appendChild($TotalOtrosPagos);

        $TotalPercepciones = $xml->createAttribute('TotalPercepciones');
        $TotalPercepciones->value = $numeroSD;
        $nominaVN->appendChild($TotalPercepciones);

        $VersionComN = $xml->createAttribute('Version');
        $VersionComN->value = '1.2';
        $nominaVN->appendChild($VersionComN);

        /* Datos de emisor */
        $emisorCNom = $xml->createElement('nomina12:Emisor');
        $nominaVN->appendChild($emisorCNom);

        $RegistroPatronalEmisor = $xml->createAttribute('RegistroPatronal');
        $RegistroPatronalEmisor->value = "$registroPatronalRazonSocial";
        $emisorCNom->appendChild($RegistroPatronalEmisor);

        /* Datos receptor */
        $ReceptorCNom = $xml->createElement('nomina12:Receptor');
        $nominaVN->appendChild($ReceptorCNom);

        $fechaInicioLaboral = '2004-01-01';
        $generaAntiguedad = $this->calcularAntiguedad($fechaInicioLaboral);
        $yearA = $generaAntiguedad['anios'];
        $monthA = $generaAntiguedad['meses'];
        $dayA = $generaAntiguedad['dias'];
        $formatoYear = 'P' . $yearA . 'Y' . $monthA . 'M' . $dayA . 'D';
        $formatoMonth = 'P' . $monthA . 'M' . $dayA . 'D';
        // $weekD = intval($dayA / 7);
        // $formatoWeek = 'P'.$weekD.'W';

        $generaAntiguedadWeek = $this->calcularAntiguedadEnSemanas($fechaInicioLaboral);
        $formatoWeek = 'P' . $generaAntiguedadWeek . 'W';

        $Antigüedad = $xml->createAttribute('Antigüedad');
        // $Antigüedad->value = $formatoWeek;
        $Antigüedad->value = $dataNomina[0]["antiguedad_recibo"];
        $ReceptorCNom->appendChild($Antigüedad);

        $ClaveEntFed = $xml->createAttribute('ClaveEntFed');
        $ClaveEntFed->value = "$clave_sat_estado";
        // $ClaveEntFed->value = "CMX";
        $ReceptorCNom->appendChild($ClaveEntFed);

        $Curp = $xml->createAttribute('Curp');
        $Curp->value = $dataPersonal[0]["curp"];
        $ReceptorCNom->appendChild($Curp);

        $FechaInicioRelLaboral = $xml->createAttribute('FechaInicioRelLaboral');
        $FechaInicioRelLaboral->value = $dataNomina[0]["fecha_inicio_relacion_recibo"];
        $ReceptorCNom->appendChild($FechaInicioRelLaboral);

        $NumEmpleado = $xml->createAttribute('NumEmpleado');
        $NumEmpleado->value = $dataNomina[0]["no_emp_receptor"];
        $ReceptorCNom->appendChild($NumEmpleado);

        // print_r($dataPersonal);
        $NumSeguridadSocial = $xml->createAttribute('NumSeguridadSocial');
        $NumSeguridadSocial->value = $dataPersonal[0]['no_ss'];
        $ReceptorCNom->appendChild($NumSeguridadSocial);

        $idPeriodicidadPago = $dataNomina[0]["publicidad_pago_receptor"];
        $dataPeriodicidad = $this->mconsultas->ConsultaSQL("SELECT * FROM t_periodicidad_pago_cobro WHERE id_periodicidad = " . $idPeriodicidadPago);
        $PeriodicidadPago = $xml->createAttribute('PeriodicidadPago');
        $PeriodicidadPago->value = $dataPeriodicidad[0]["clave_sat"];
        $ReceptorCNom->appendChild($PeriodicidadPago);

        $Puesto = $xml->createAttribute('Puesto');
        $Puesto->value = $dataNomina[0]["puesto_emp_receptor"];
        $ReceptorCNom->appendChild($Puesto);

        $idRiesgoPuesto = $dataNomina[0]["riesgo_puesto_receptor"];
        $dataRiesgoPuesto = $this->mconsultas->ConsultaSQL("SELECT * FROM t_riesgo_puesto WHERE id_riesgo = " . $idRiesgoPuesto);
        $RiesgoPuesto = $xml->createAttribute('RiesgoPuesto');
        $RiesgoPuesto->value = $dataRiesgoPuesto[0]["clave_sat"];
        $ReceptorCNom->appendChild($RiesgoPuesto);

        $SalarioBaseCotApor = $xml->createAttribute('SalarioBaseCotApor');
        $salario_base_receptor = number_format($dataNomina[0]["salario_base_receptor"], 2);
        $SalarioBaseCotApor->value = $salario_base_receptor;
        $ReceptorCNom->appendChild($SalarioBaseCotApor);

        $SalarioDiarioIntegrado = $xml->createAttribute('SalarioDiarioIntegrado');
        $SalarioDiarioIntegrado->value = "$salario_base_receptor";
        $ReceptorCNom->appendChild($SalarioDiarioIntegrado);

        $Sindicalizado = $xml->createAttribute('Sindicalizado');
        $Sindicalizado->value = "$claveSatSindicalizado";
        // $Sindicalizado->value = "No";
        $ReceptorCNom->appendChild($Sindicalizado);

        $idTipoContrato = $dataNomina[0]["id_tipo_contrato"];
        $dataTipoContrato = $this->mconsultas->ConsultaSQL("SELECT * FROM t_tipo_contrato WHERE id_tipo_contrato = " . $idTipoContrato);
        $TipoContrato = $xml->createAttribute('TipoContrato');
        $TipoContrato->value = $dataTipoContrato[0]["clave_tipo_contrato"];
        $ReceptorCNom->appendChild($TipoContrato);

        $idTipoJornada = $dataNomina[0]["id_tipo_jornada_receptor"];
        $dataTipoJornada = $this->mconsultas->ConsultaSQL("SELECT * FROM t_catalogo_jornada WHERE id_jornada = " . $idTipoJornada);
        $TipoJornada = $xml->createAttribute('TipoJornada');
        $TipoJornada->value = $dataTipoJornada[0]["clave_sat"];
        $ReceptorCNom->appendChild($TipoJornada);

        $TipoRegimen = $xml->createAttribute('TipoRegimen');
        $TipoRegimen->value = "$clave_sat_tipo_regimen";
        $ReceptorCNom->appendChild($TipoRegimen);

        ///------------------------------------------------------------------------------------------------------------
        /* Datos sumatorias percepciones */
        $PercepcionesCNom = $xml->createElement('nomina12:Percepciones');
        $nominaVN->appendChild($PercepcionesCNom);

        $TotalExento = $xml->createAttribute('TotalExento');
        $TotalExento->value = number_format($dataSumatoriaPExcento[0]["totalImporteExcento"], 2);
        $PercepcionesCNom->appendChild($TotalExento);

        $TotalGravado = $xml->createAttribute('TotalGravado');
        $TotalGravado->value = $dataSumatoriaP[0]["totalPercepcion"];
        $PercepcionesCNom->appendChild($TotalGravado);

        $TotalSueldos = $xml->createAttribute('TotalSueldos');
        // $TotalSueldos->value = $dataSumatoriaP[0]["totalPercepcion"];
        $TotalSueldos->value = $numeroSD;
        $PercepcionesCNom->appendChild($TotalSueldos);

        /* Datos partidas percepciones */
        foreach ($dataPartidaPercepcion as $partidaPercepcion) {
            $dataTipoPercepcion = $this->mconsultas->ConsultaSQL("SELECT * FROM t_percepciones WHERE id_percepcion = " . $partidaPercepcion['id_tipo_percepcion']);

            $PercepcionCNom = $xml->createElement('nomina12:Percepcion');
            $PercepcionesCNom->appendChild($PercepcionCNom);

            $ClavePerNom = $xml->createAttribute('Clave');
            $ClavePerNom->value = $partidaPercepcion['clave'];
            $PercepcionCNom->appendChild($ClavePerNom);

            $ConceptoPerNom = $xml->createAttribute('Concepto');
            $ConceptoPerNom->value = $partidaPercepcion['id_concepto'];
            $PercepcionCNom->appendChild($ConceptoPerNom);

            $ImporteExentoPerNom = $xml->createAttribute('ImporteExento');
            $ImporteExentoPerNom->value = number_format($partidaPercepcion['importe_excento'], 2);
            $PercepcionCNom->appendChild($ImporteExentoPerNom);

            $importeGravadoP = $partidaPercepcion['importe_grabado'];
            $numero = str_replace(',', '', $importeGravadoP);
            $numero = (float) $numero;
            $numero = round($numero, 2);
            $ImporteGravadoPerNom = $xml->createAttribute('ImporteGravado');
            $ImporteGravadoPerNom->value = $numero;
            $PercepcionCNom->appendChild($ImporteGravadoPerNom);

            $TipoPercepcionPerNom = $xml->createAttribute('TipoPercepcion');
            $TipoPercepcionPerNom->value = $dataTipoPercepcion[0]["clave_sat"];
            $PercepcionCNom->appendChild($TipoPercepcionPerNom);
        }

        /* Datos sumatorias deducciones */
        $DeduccionesCNom = $xml->createElement('nomina12:Deducciones');
        $nominaVN->appendChild($DeduccionesCNom);

        $dataTotalDeducciones = $this->mconsultas->ConsultaSQL("Select *
        FROM T_partidas_deducciones_nomina
        WHERE id_recibo_nomina = " . $id_nomina . "
        AND id_tipo_deduccion = 1");
        if (!empty($dataTotalDeducciones)) {
            $impuestoRetenido = number_format($dataTotalDeducciones[0]["importe"], 2);
        } else {
            $impuestoRetenido = "0.00";
        }
        $TotalImpuestosRetenidosCNom = $xml->createAttribute('TotalImpuestosRetenidos');
        $TotalImpuestosRetenidosCNom->value = $impuestoRetenido;
        $DeduccionesCNom->appendChild($TotalImpuestosRetenidosCNom);

        $TotalOtrasDeduccionesCNom = $xml->createAttribute('TotalOtrasDeducciones');
        $TotalOtrasDeduccionesCNom->value = $dataSumOtraDeduccion[0]["totalOtraDeduccion"];
        $DeduccionesCNom->appendChild($TotalOtrasDeduccionesCNom);

        /* Datos partidas deducciones */
        foreach ($dataPartidaDeduccion as $partidaDeduccion) {
            $dataFormaDeduccion = $this->mconsultas->ConsultaSQL("SELECT * FROM T_Tipo_Deducciones WHERE id_deduccion = " . $partidaDeduccion['id_tipo_deduccion']);
            $fkDeduccion = $dataFormaDeduccion[0]['fk_deduccion'];
            $dataTipoDeduccion = $this->mconsultas->ConsultaSQL("SELECT * FROM t_deducciones WHERE id_deduccion = " . $fkDeduccion);
            $DeduccionCNom = $xml->createElement('nomina12:Deduccion');
            /* $DeduccionCNom->value = '1';*/
            $DeduccionesCNom->appendChild($DeduccionCNom);

            $Clave = $xml->createAttribute('Clave');
            $Clave->value = $partidaDeduccion['clave'];
            $DeduccionCNom->appendChild($Clave);

            $Concepto = $xml->createAttribute('Concepto');
            $Concepto->value = $partidaDeduccion['id_concepto'];
            $DeduccionCNom->appendChild($Concepto);

            $numeroVDS = str_replace(',', '', $partidaDeduccion['importe']);
            $numeroVDS = (float) $numeroVDS;
            $numeroVDS = round($numeroVDS, 2);
            $Importe = $xml->createAttribute('Importe');
            $Importe->value = $numeroVDS;
            $DeduccionCNom->appendChild($Importe);

            $TipoDeduccion = $xml->createAttribute('TipoDeduccion');
            $TipoDeduccion->value = $dataTipoDeduccion[0]["clave_sat"];
            $DeduccionCNom->appendChild($TipoDeduccion);
        }

        $id_tipo_otro_pago = $dataPartidaOtroPago[0]['id_tipo_otro_pago'];
        if ($id_tipo_otro_pago > 0) {
            $OtrosPagosCNom = $xml->createElement('nomina12:OtrosPagos');
            $nominaVN->appendChild($OtrosPagosCNom);
        } else {
            $OtrosPagosCNom = "";
        }
        /* Otros pagos */

        /* Datos partida otros pagos */
        foreach ($dataPartidaOtroPago as $partidaOtroPago) {
            $id_tipo_otro_pago = $partidaOtroPago['id_tipo_otro_pago'];
            if ($id_tipo_otro_pago > 0) {
                $dataTipoOtroPago = $this->mconsultas->ConsultaSQL("SELECT * FROM t_otro_pago WHERE id_otro_pago = " . $partidaOtroPago['id_tipo_otro_pago']);
                $OtroPagoCNom = $xml->createElement('nomina12:OtroPago');
                $OtrosPagosCNom->appendChild($OtroPagoCNom);

                $ClaveCNom = $xml->createAttribute('Clave');
                $ClaveCNom->value = $partidaOtroPago['clave'];
                $OtroPagoCNom->appendChild($ClaveCNom);

                $ConceptoCNom = $xml->createAttribute('Concepto');
                $ConceptoCNom->value = $partidaOtroPago['id_concepto'];
                $OtroPagoCNom->appendChild($ConceptoCNom);

                if (!empty($partidaOtroPago['importe'])) {
                    $importeOtropago = $partidaOtroPago['importe'];
                } else {
                    $importeOtropago = 0;
                }
                $ImporteCNom = $xml->createAttribute('Importe');
                $ImporteCNom->value = $importeOtropago;
                $OtroPagoCNom->appendChild($ImporteCNom);

                $TipoOtroPagoCNom = $xml->createAttribute('TipoOtroPago');
                $TipoOtroPagoCNom->value = $dataTipoOtroPago[0]["clave_sat"];
                $OtroPagoCNom->appendChild($TipoOtroPagoCNom);

                $SubsidioAlEmpleoCNom = $xml->createElement('nomina12:SubsidioAlEmpleo');
                $OtroPagoCNom->appendChild($SubsidioAlEmpleoCNom);

                $SubsidioCausadoCNom = $xml->createAttribute('SubsidioCausado');
                $SubsidioCausadoCNom->value = number_format($partidaOtroPago['subsidio_causado'], 2);
                $SubsidioAlEmpleoCNom->appendChild($SubsidioCausadoCNom);
            }
        }

        $rec->appendChild($NombreR);
        $rec->appendChild($UsoCFDI);
        $rec->appendChild($RfcR);
        $rec->appendChild($DomicilioFiscalReceptor);
        $rec->appendChild($RegimenFiscalReceptor);

        $emisor->appendChild($Nombre);
        $emisor->appendChild($RegimenFiscal);
        $emisor->appendChild($Rfc);

        $xml_comprobante->appendChild($cfdi);
        $xml_comprobante->appendChild($xmlns);
        $xml_comprobante->appendChild($nomina12);
        $xml_comprobante->appendChild($schemaLocation);
        $xml_comprobante->appendChild($version);
        $xml_comprobante->appendChild($folio);
        $xml_comprobante->appendChild($fecha);
        $xml_comprobante->appendChild($sello);
        $xml_comprobante->appendChild($MetodoPago);
        $xml_comprobante->appendChild($LugarExpedicion);
        $xml_comprobante->appendChild($descuentoN);
        $xml_comprobante->appendChild($serieN);
        $xml_comprobante->appendChild($NoCertificado);
        $xml_comprobante->appendChild($Certificado);
        $xml_comprobante->appendChild($SubTotal);
        $xml_comprobante->appendChild($Moneda);
        $xml_comprobante->appendChild($Total);
        $xml_comprobante->appendChild($TipoDeComprobante);
        $xml_comprobante->appendChild($Exportacion);
        $xml_comprobante->appendChild($emisor);
        $xml_comprobante->appendChild($rec);
        $xml_comprobante->appendChild($conceptos);
        $xml_comprobante->appendChild($complementoN);

        $xml->appendChild($xml_comprobante);

        /* Guardamos el archivo */
        $xml->save("assets/ReciboNominas/" . $id_nomina . "/NOMINA" . $id_nomina . ".xml");

        // Hacer una copia del archivo con un nombre diferente
        $nombreArchivoCopia = "assets/ReciboNominas/" . $id_nomina . "/CopiaNomina" . $id_nomina . ".xml";
        copy("assets/ReciboNominas/" . $id_nomina . "/NOMINA" . $id_nomina . ".xml", $nombreArchivoCopia);

        /* Damos las credenciales para timbrar  */
        /* LOCAL */
        $params = array(
            "url" => "http://services.test.sw.com.mx",
            "user" => "sandbox@conectia.mx",
            "password" => "1234567890"
        );

        /* Produccion */
        /* $params = array(
            "url" => "https://services.sw.com.mx",
            "token" => "T2lYQ0t4L0RHVkR4dHZ5Nkk1VHNEakZ3Y0J4Nk9GODZuRyt4cE1wVm5tbXB3YVZxTHdOdHAwVXY2NTdJb1hkREtXTzE3dk9pMmdMdkFDR2xFWFVPUXpTUm9mTG1ySXdZbFNja3FRa0RlYURqbzdzdlI2UUx1WGJiKzViUWY2dnZGbFloUDJ6RjhFTGF4M1BySnJ4cHF0YjUvbmRyWWpjTkVLN3ppd3RxL0dJPQ.T2lYQ0t4L0RHVkR4dHZ5Nkk1VHNEakZ3Y0J4Nk9GODZuRyt4cE1wVm5tbFlVcU92YUJTZWlHU3pER1kySnlXRTF4alNUS0ZWcUlVS0NhelhqaXdnWTRncklVSWVvZlFZMWNyUjVxYUFxMWFxcStUL1IzdGpHRTJqdS9Zakw2UGR5YmZla3FRYlE1amcrRzBQSGJDcytRakV5SXk4SCtQRHhNYmsyT3dQeThBMms3KzJsaVY4M3Y3bE5yUnVyTGJYNkdkM1I5TzBMdzkwR3RXczJ1cWlNTmF1ZEVkYkttdFA0NHlhVkU4SnhkRzdZM01ka1hxVm5kR1JQem53bzErekc3V3pFVTdDaWlnUzVIRm80cXFRWWdMSWJFeitGTHJPM2RJcFppR3cxelg3NmlVQmtrTFRpcnVVTUROT0pBVjQxbHpLVE16Zitpa1YrTGlwUVNkUzN2Uko1UXVsN25WOHZaOEJmU1J0Z0RlT1NpSkxmZFM5YzhLMTJOL3d6S2NjU2FIMWRUYnhtTjg3NEFBVkZHd0oxdlFzVS9xRGJzMzQzL0p5WjJrOEdBZFRaQjdkM2xyaDgxR2phUmhRUytxTEYvalpZS2NkSHVFOUVMSTFmSEFodklpRlpyZlVYSjdVME5YOFh0TXdiU0xzUjJoWmIremRSWGNGaUl1enpHY0FIdC9WZDdxM1h3dk50VkJ4aW9Sd09CaVVLd1hMUk8vWWZWblh0eTAzVjJvPQ.2JCY1B1HbYu0GZkGtKdQUrAhmIQ_T3fErfBaYzmAq34"
        ); */


        /* Traemos el archivo xml */
        $xml = file_get_contents("assets/ReciboNominas/" . $id_nomina . "/NOMINA" . $id_nomina . ".xml");

        /* Mandamos nuestro xml a timbrar */
        try {
            SWServices\Stamp\EmisionTimbrado::Set($params);
            $resultadoIssue = SWServices\Stamp\EmisionTimbrado::EmisionTimbradoV4($xml);
            var_dump($resultadoIssue);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        /* Guardamos nuestro archivo ya timbrado */
        $archivo = fopen("assets/ReciboNominas/" . $id_nomina . "/NOMINA" . $id_nomina . ".xml", "w+b");

        if (!$resultadoIssue->messageDetail) {
            fwrite($archivo, $resultadoIssue->data->cfdi);
        } else {
            fwrite($archivo, $resultadoIssue->messageDetail);
        }
        /* ALMACENAMIENTO DE XML EN BD */
        $archivoXML = "./assets/ReciboNominas/$id_nomina/NOMINA$id_nomina.xml";
        $xmlString = file_get_contents($archivoXML);
        $xml = simplexml_load_string($xmlString);

        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace("cfdi", $namespaces["cfdi"]);
        $xml->registerXPathNamespace("nomina12", $namespaces["nomina12"]);
        $xml->registerXPathNamespace("tfd", $namespaces["tfd"]);

        $xml_data["Sello"] = (string) $xml->attributes()["Sello"];
        $xml_data["Total"] = (string) $xml->attributes()["Total"];
        $xml_data["NoCertificado"] = (string) $xml->attributes()["NoCertificado"];
        $xml_data["Certificado"] = (string) $xml->attributes()["Certificado"];
        $xml_data["uuid"] = (string) $xml->xpath("//tfd:TimbreFiscalDigital")[0]->attributes()["UUID"];
        $xml_data["FechaTimbrado"] = (string) $xml->xpath("//tfd:TimbreFiscalDigital")[0]->attributes()["FechaTimbrado"];
        $xml_data["SelloSAT"] = (string) $xml->xpath("//tfd:TimbreFiscalDigital")[0]->attributes()["SelloSAT"];
        $xml_data["NoCertificadoSAT"] = (string) $xml->xpath("//tfd:TimbreFiscalDigital")[0]->attributes()["NoCertificadoSAT"];
        $xml_data["SelloCFD"] = (string) $xml->xpath("//tfd:TimbreFiscalDigital")[0]->attributes()["SelloCFD"];

        $certificado_XML = $xml_data["Certificado"];
        $NoCertificado_XML = $xml_data["NoCertificado"];
        $Sello_XML = $xml_data["Sello"];
        $noCertificadoSAT_XML = $xml_data["NoCertificadoSAT"];
        $selloSAT_XML = $xml_data["SelloSAT"];
        $SelloCFD_XML = $xml_data["SelloCFD"];
        $UUID_XML = $xml_data["uuid"];
        $FechaTimbrado_XML = $xml_data["FechaTimbrado"];
        $totalXML = $xml_data["Total"];

        $descuentoConcepto = $dataSumatoriaD[0]["totalDeduccion"];
        $totalSueldosZ = $dataSumatoriaP[0]["totalPercepcion"];
        $totalExcentos = $dataSumatoriaPExcento[0]["totalImporteExcento"];
        $totalOtrasDeducciones = $dataSumOtraDeduccion[0]["totalOtraDeduccion"];
        $totalOtrosPagos = $dataSumatoriaOP[0]["totalOP"];
        $dataSubsidioOtroPago = $this->mconsultas->ConsultaSQL("SELECT subsidio_causado FROM T_partidas_pago WHERE id_recibo_nomina = " . $id_nomina . " AND id_tipo_otro_pago = 2");
        if (empty($dataSubsidioOtroPago)) {
            $subsidioOtroPagoMonto = 0.0;
        } else {
            $subsidioOtroPagoMonto = $dataSubsidioOtroPago[0]["subsidio_causado"];
        }

        if ($noCertificadoSAT_XML) {
            $datos = array(
                "id_nomina" => "$id_nomina",
                "folio_fiscal" => "$UUID_XML",
                "no_serie_csd" => "$NoCertificado_XML",
                "lugar_fecha_hora_emision" => "$FechaTimbrado_XML",
                "valor_unitario_concepto" => "$numeroSD",
                "importe_concepto" => "$numeroSD",
                "descuento_concepto" => "$descuentoConcepto",
                "total_sueldos_p" => "$totalSueldosZ",
                "total_excentos_p" => "$totalExcentos",
                "total_gravado_p" => "$totalSueldosZ",
                "total_otras_deducciones" => "$totalOtrasDeducciones",
                "total_imp_retenidos" => "$impuestoRetenido",
                "subsidio_causado" => "$subsidioOtroPagoMonto",
                "total_percepciones" => "$totalSueldosZ",
                "total_otros_pagos" => "$totalOtrosPagos",
                "total_deducciones" => "$descuentoConcepto",
                "selloxml" => "$Sello_XML",
                "cerificado" => "$certificado_XML",
                "uuid" => "$UUID_XML",
                "sellocfd" => "$SelloCFD_XML",
                "no_certificado_sat" => "$noCertificadoSAT_XML",
                "sello_sat" => "$selloSAT_XML",
                "total_nomina" => "$totalXML",
            );

            $sqldatosXMLNomina = "SELECT * FROM data_xml_nomina WHERE id_nomina = $id_nomina";
            $datosXMLNomina = $this->mconsultas->ConsultaSQL($sqldatosXMLNomina);
            $nominaexistenteXML = $datosXMLNomina[0]['id_nomina'];
            if (empty($nominaexistenteXML)) {
                $this->mconsultas->insertaRegistro($datos, "data_xml_nomina");
                echo "funciono";

                $datos = array(
                    "timbrado" => 1
                );
                $this->meditar->actualizarm("T_Recibo_Nomina", $datos, array("id_recibo_nomina" => "$id_nomina"));
            }
        }
    }