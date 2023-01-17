<?php

namespace App\Util;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Util\PhpreaderHelper;

class SheetReader
{

    const ORBISKEYS = [
        'A' => [
            'Nombre' => 'Nombre',
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Direct %',
            'Total' => 'Total %'
        ],
        'P' => [
            'Nombre' => '', // no hay etiqueta para participadas
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Direct %',
            'Total' => 'Total %'
        ],
        'class' => 'ORBIS'
    ];
    const SABIKEYS = [
        'A' => [
            'Nombre' => 'Nombre del accionista',
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Directo (%)',
            'Total' => 'Total (%)'
        ],
        'P' => [
            'Nombre' => 'Nombre participada',
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Directo (%)',
            'Total' => 'Total (%)'
        ],
        'class' => 'SABI'
    ];

    const SECTALL = "0";
    const SECTMANAGERS = "M";
    const SECTHOLDERS = "A";
    const SECTOWNED = "P";

    private $class; // Para el tipo de fichero, SABI u ORBIS
    private $company;
    private $contents=[];
    private $outdir;
    private $prefix; // Prefijo para guardar ficheros de datos
    private $section = "0"; // Section: "0"(all), "M"anagers, "A"ccionistas, "P"articipadas
    private $handlers = [
        'detailManagers' => null,
        'detailShareholders' => null,
        'detailSubsidiaries' => null,
        'summary' => null,
    ];
    private $write; // Para indicar si se guardan ficheros CSV

    public function __construct($prefix = 'TEST')
    {
        $this->prefix = $prefix;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getOutdir(): string
    {
        return $this->outdir;
    }

    public function setOutdir($value, $create = false): bool
    {
        if ($create) {
            if (!is_dir($value)) {
                if (!mkdir($value)) {
                    return false;
                }
                $this->outdir = $value .'/';
                return true;
            }
        }
        if (is_dir($value) && opendir($value)) {
            $this->outdir = $value . '/';
            return true;
        }

        return false;
    }

    public function setPrefix($prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function setSection($value): self
    {
        if ($value == self::SECTALL ||
            $value == self::SECTHOLDERS ||
            $value == self::SECTOWNED ||
            $value == self::SECTMANAGERS) {
            $this->section = $value;
        }

        return $this;
    }

    public function setWrite(bool $value): self
    {
        $this->write = $value;

        return $this;
    }

    private function stripCompanyName($company): string
    {
        $search = ['@@SLASH@@', '@@QUOTE@@', ',', '.'];
        $replace = ['/', '’', ' ', ''];
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = str_replace($search, $replace, strtoupper($company));

        return $empresa;
    }

    private function getManagersFilePattern($outputpattern): string
    {
        return $outputpattern . '_@@MANAGERS@@';
    }

    private function getShareholdersFilePattern($outputpattern): string
    {
        return $outputpattern . '_@@ACCIONISTAS@@';
    }

    private function getSubsidiariesFilePattern($outputpattern): string
    {
        return $outputpattern . '_@@PARTICIPADAS@@';
    }

    public function loadFile($inputFileName): string
    {
        //$inputFileName = __DIR__ . '/../../../sanitypower/migrations/' . $name;
        // Eliminamos rutas y caracteres extra, para obtener el nombre de la empresa
        $e = explode('/', $inputFileName);
        $_empresa = $e[count($e)-1];
        $_empresa = substr($_empresa, 0, strpos($_empresa, '.'));
        //$search = ['@@SLASH@@', '@@QUOTE@@'];
        //$replace = ['/', '’'];
        //$empresa = str_replace($search, $replace, $_empresa);
        $empresa = $this->company = $this->stripCompanyName($_empresa);
        //dump("empresa: $empresa, ($_empresa)");
        // Ruta completa al fichero
        //$filenameFullPath = $this->dir . $inputFileName;
        $inputFileType = IOFactory::identify(
            $inputFileName, //$filenameFullPath,
            [
                IOFactory::READER_XLS,
                IOFactory::READER_XLSX,
            ]
        );
        $reader = IOFactory::createReader($inputFileType);
        $worksheetNames = $reader->listWorksheetNames($inputFileName);
        //$reader->setLoadSheetsOnly($worksheetNames[0]);
        //$helper->log('Loading file ' . /** @scrutinizer ignore-type */ pathinfo($inputFileName, PATHINFO_BASENAME)
        //    . ' using IOFactory to identify the format');
        //$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
        //$filter = new PhpreaderHelper();
        //$reader->setReadFilter($filter);
        /**  Advise the Reader that we only want to load cell data  **/
        $reader->setReadDataOnly(true)
        ->setReadEmptyCells(false);
        $spreadsheet = $reader->load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(false, true, true, true);
        $contents = $this->contents = [];
        $contents['limit'] = count($sheetData);
        $rowIndex = 1;
        $store = false;
        //dump($sheetData);
        // Hacemos limpieza y marcamos las secciones 'A' y 'P'
        foreach ($sheetData as $row) {
            $line = [];
            foreach ($row as $col => $value) {
                if (null!=$value) {
                    $line[$col] = $value;
                    if ($value == 'Directores y gerentes actuales') {
                        if (empty($contents['M'])) {
                            $contents['M'] = $rowIndex;
                            $store = true;
                        }
                    }
                    if ($value == 'Accionistas actuales') {
                        if (empty($contents['A'])) {
                            $contents['A'] = $rowIndex;
                            $store = true;
                        }
                    }
                    if ($value == 'Participadas actuales') {
                        if (empty($contents['P'])) {
                            $contents['P'] = $rowIndex;
                        }
                    }
                }
            }
            if (count($line) && $store) {
                $contents[$rowIndex] = $line;
            }
            $rowIndex++;
        }
        //dump($contents);
        $this->class = 'ORBIS';

        $this->contents = $contents;

        return $_empresa;
    }

    public function generateManagers($write = false)
    {
        $managers = [];
        $contents = $this->contents;
        $rowIndex = $contents['M'];
        $limit = $contents['A'];

        //dump($rowIndex);
        while ($rowIndex<$limit) {
            $line = [];
            if (!empty($contents[$rowIndex]['G'])) {
                $datos = $contents[$rowIndex]['G'];
            }
            //if ($class=='ORBIS') {
            $end = false;
            if (!empty($contents[$rowIndex]['A'])) {
                if ($contents[$rowIndex]['A'] == 'Leyenda') {
                    $rowIndex = $limit;
                    $end = true;
                }
            }
            if (!$end) {
                if (!empty($contents[$rowIndex]['G'])) {
                    $cell = $contents[$rowIndex]['G'];
                    $datos = explode("\n", $cell);
                    $line = [
                        'datos' => $cell,
                        'Nombre' => $datos[0],
                        'Fecha' => $datos[1],
                        'Cargo' => $datos[2],
                        'row' => $rowIndex
                    ];
                }
            }
            //}
            if (count($line)) {
                $managers[] = $line;
            }
            $rowIndex++;
        }
        //dump($managers);
        return $managers;
    }

    public function generateShareholders($write = false)
    {
        // INICIO DE ACCIONISTAS
        $shareholders = [];
        $contents = $this->contents;
        $rowIndex = $contents['A'];
        $colTitles = [];
        $keys = $orbisKeys = self::ORBISKEYS;
        $sabiKeys = self::SABIKEYS;
        $limit = $contents['P'];
        while (count($colTitles)<5 && $rowIndex <$limit) {
            if (!empty($contents[$rowIndex])) {
                foreach ($contents[$rowIndex] as $key => $value) {
                    if ($value==$orbisKeys['A']['Nombre'] || ($value==$sabiKeys['A']['Nombre'])) {
                        $colTitles['Nombre'] = $key;
                        if ($value==$sabiKeys['A']['Nombre']) {
                            $keys = $sabiKeys;
                        }
                    }
                    if ($value==$keys['A']['Pais']) {
                        $colTitles['Pais'] = $key;
                    }
                    if ($value==$keys['A']['Tipo']) {
                        $colTitles['Tipo'] = $key;
                    }
                    if ($value==$keys['A']['Direct']) {
                        $colTitles['Direct'] = $key;
                    }
                    if ($value==$keys['A']['Total']) {
                        $colTitles['Total'] = $key;
                    }
                }
            }
            $rowIndex++;
        }
        if (count($colTitles)) {
            $colTitles['class'] = $keys['class'];
            $colTitles['empresa'] = $this->company;
        } else {
            $keys = $orbisKeys; // Inicializamos por no haber accionistas
        }
        $class = $keys['class'];

        //dump($colTitles);
        $index = 0;
        while ($rowIndex<$limit) {
            $line = [];
            $viastr = 'via its funds';
            $via= '';
            if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                $Nombre = $contents[$rowIndex][$colTitles['Nombre']];
                $funds = stripos($Nombre, $viastr);
                if ($funds) {
                    $via = $viastr;
                    $Nombre = trim(substr($Nombre, 0, $funds));
                }
            }
            if ($class=='ORBIS') {
                if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                    if ($contents[$rowIndex][$colTitles['Nombre']]=='Leyenda') {
                        $rowIndex = $limit;
                    } else {
                        $line = [
                            'Nombre' => $Nombre,
                            'via' => $via,
                            'Pais' => $contents[$rowIndex][$colTitles['Pais']],
                            'Tipo' => $contents[$rowIndex][$colTitles['Tipo']],
                            'Direct' => $contents[$rowIndex][$colTitles['Direct']],
                            'Total' => $contents[$rowIndex][$colTitles['Total']],
                            'row' => $rowIndex
                        ];
                    }
                }
            } else {
                if (!empty($contents[$rowIndex]['A'])) {
                    $i = $contents[$rowIndex]['A'];
                    $i = substr($i, 0, strpos($i, '.'));
                    //dump($i);
                    if (is_numeric($i) && ($i==($index+1))) {
                        if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                            $Nombre = $contents[$rowIndex][$colTitles['Nombre']];
                        } else {
                            foreach ($contents[$rowIndex] as $key => $value) {
                                //dump($key, $value);
                                if ($key>'A' && $value != $contents[$rowIndex][$colTitles['Pais']]) {
                                    $Nombre = $value;
                                    break;
                                }
                            }
                        }
                        $funds = stripos($Nombre, $viastr);
                        if ($funds) {
                            $via = $viastr;
                            $Nombre = substr($Nombre, 0, $funds);
                        }
                        $line = [
                            'index' => ++$index,
                            'Nombre' => $Nombre,
                            'via' => $via,
                            'Pais' => $contents[$rowIndex][$colTitles['Pais']],
                            'Tipo' => $contents[$rowIndex][$colTitles['Tipo']],
                            'Direct' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Direct']]),
                            'Total' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Total']]),
                            'row' => $rowIndex
                        ];
                    }
                }
            }
            if (count($line)) {
                if (!empty($Nombre)) {
                    //$line['Nombre'] = str_replace($NombreSearch, $NombreReplace, $Nombre);
                    $line['Nombre'] = $this->stripCompanyName($Nombre);
                }
                $shareholders[] = $line;
            }
            $rowIndex++;
        }
        $this->class = $keys['class'];

        return $shareholders;
        // FIN ACCIONISTAS
    }

    public function generateSubsidiaries($write = false)
    {
        $contents = $this->contents;
        $colTitles = $subsidiaries = [];
        $colTitles = [
            'index' => 'A',
            'P' => $contents['P'],
        ];
        $keys = self::ORBISKEYS;
        if ($this->class != 'ORBIS') {
            $keys = self::SABIKEYS;
        }
        $limit = $contents['limit'];
        $rowIndex = $contents['P'];
        while (count($colTitles)<5 && $rowIndex <$limit) {
            if (!empty($contents[$rowIndex])) {
                foreach ($contents[$rowIndex] as $key => $value) {
                    if ($value==self::SABIKEYS['P']['Nombre']) {
                        $colTitles['Nombre'] = $key;
                        $keys = self::SABIKEYS;
                        $this->class = 'SABI';
                    }
                    if ($value==$keys['P']['Pais']) {
                        $colTitles['Pais'] = $key;
                    }
                    if ($value==$keys['P']['Tipo']) {
                        $colTitles['Tipo'] = $key;
                    }
                    if ($value==$keys['P']['Direct']) {
                        $colTitles['Direct'] = $key;
                    }
                    if ($value==$keys['P']['Total']) {
                        $colTitles['Total'] = $key;
                        $colTitles['row'] = $rowIndex;
                    }
                }
            }
            $rowIndex++;
        }
        $colTitles['class'] = $keys['class'];
        $colTitles['empresa'] = $this->company;
        //dump("rowIndex($rowIndex), limit($limit), colTitles:", $colTitles);

        $index = 0; // Indice de participadas
        // El count es por si no hay participadas
        while ($rowIndex<$limit && count($colTitles)) {
            if (empty($colTitles['Nombre'])) {
                // ORBIS, no tenemos la columna del nombre
                /*foreach ($contents[$rowIndex] as $key => $value) {
                    dump($key, $value);
                    if ($key>'A' && $value != $contents[$rowIndex][$colTitles['Pais']]) {
                        $colTitles['Nombre'] = $value;
                        //$Nombre = $value;
                        //break;
                    } else {
                        break;
                    }
                }*/
                $x = 0;
                $xfound = false;
                foreach ($contents[$rowIndex] as $key => $value) {
                    $x++;
                    if ($x>1 && (strlen($value)>2) && !$xfound) {
                        $colTitles['Nombre'] = $key;
                        //dump("key: $key, value: $value, Nombre: " . $colTitles['Nombre']);
                        $xfound = true;
                        break;
                    }
                }
                //dump($colTitles);
            }
            if ((!empty($contents[$rowIndex]['A'])) && ($contents[$rowIndex]['A']=='Leyenda')) {
                $rowIndex = $limit;
            } else {
            }
            //dump($colTitles);
            if (!empty($contents[$rowIndex]['A'])) {
                $i = $contents[$rowIndex]['A'];
                if (substr($i, 0, strpos($i, '.'))) {
                    $i = substr($i, 0, strpos($i, '.'));
                } else {
                    $i = rtrim($i);
                }
                //dump("linea: $rowIndex, index: $i");
                if (is_numeric($i) && ($i==($index+1))) {
                    if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                    }
                    if (empty($colTitles['Tipo'])) {
                        $Tipo = 'C';
                    } else {
                        $Tipo = $contents[$rowIndex][$colTitles['Tipo']];
                    }
                    $subsidiaries[] = [
                        'index' => ++$index,
                        'Nombre' => $this->stripCompanyName($contents[$rowIndex][$colTitles['Nombre']]),
                        'Pais' => $contents[$rowIndex][$colTitles['Pais']]??'--',
                        'Tipo' => $Tipo,
                        'Direct' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Direct']])??0,
                        'Total' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Total']])??0,
                        'row' => $rowIndex,
                    ];
                }
            }
            $rowIndex++;
        }

        return $subsidiaries;
    }

    public function openResultsFiles()
    {
        if ($this->section == self::SECTALL) {
            $this->handlers['summary'] = fopen($this->outdir . '__resultados_' . $this->prefix, 'w');
        }
        if ($this->section == self::SECTALL || $this->section == self::SECTMANAGERS) {
            $this->handlers['detailManagers'] = fopen($this->outdir . 'detalles_MANAGERS_' . $this->prefix, 'w');
        }
        if ($this->section == self::SECTALL || $this->section == self::SECTHOLDERS) {
            $this->handlers['detailShareholders'] = fopen($this->outdir . 'detalles_ACCIONISTAS_' . $this->prefix, 'w');
        }
        if ($this->section == self::SECTALL || $this->section == self::SECTOWNED) {
            $this->handlers['detailSubsidiaries'] = fopen($this->outdir . 'detalles_PARTICIPADAS_'.$this->prefix, 'w');
        }
    }

    public function openManagersDetail($outputpattern)
    {
        $pattern = $this->getManagersFilePattern($outputpattern);
        $filenameFullPath = '__detalles_' . $pattern;
        return fopen($this->outdir . $pattern, 'w');
    }

    public function openShareholdersDetail($outputpattern)
    {
        $pattern = $this->getShareholdersFilePattern($outputpattern);
        $filenameFullPath = '__detalles_' . $pattern;
        return fopen($this->outdir . $pattern, 'w');
    }

    public function openSubsidiariesDetail($outputpattern)
    {
        $pattern = $this->getSubsidiariesFilePattern($outputpattern);
        $filenameFullPath = '__detalles_' . $pattern;
        return fopen($this->outdir . $pattern, 'w');
    }

    public function processFile($inputFileName, $write = false)
    {
        $this->setWrite($write);
        $outputfile = $this->loadFile($inputFileName);

        if ($this->section == self::SECTALL || $this->section == self::SECTHOLDERS) {
            $shares = $this->generateShareholders($write);
            if ($write) {
                $fp = $this->openShareholdersDetail($outputfile);

                // Escribimos los accionistas
                foreach ($shares as $line) {
                    $array = [
                        $line['Nombre'],
                        $line['via'],
                        $line['Pais'],
                        $line['Tipo'],
                        $line['Direct'],
                        $line['Total'],
                    ];
                    fputcsv($fp, $array);
                    fputcsv(
                        $this->handlers['detailShareholders'],
                        array_merge([$this->company], $array)
                    );
                }
                fclose($fp);
            }
        }

        if ($this->section == self::SECTALL || $this->section == self::SECTOWNED) {
            $subs = $this->generateSubsidiaries($write);
            $fp = $this->openSubsidiariesDetail($outputfile);
            // Escribimos las participadas
            foreach ($subs as $line) {
                $array = [
                        $line['Nombre'],
                        $line['Pais'],
                        $line['Tipo'],
                        $line['Direct'],
                        $line['Total'],
                ];
                fputcsv($fp, $array);
                fputcsv(
                    $this->handlers['detailSubsidiaries'],
                    array_merge([$this->company], $array)
                );
            }
            fclose($fp);
        }

        // Escribimos el resumen
        if (!empty($shares) && !empty($subs)) {
            fputcsv($this->handlers['summary'], [$this->company, count($shares), count($subs)]);
        }

        if ($this->section == self::SECTALL || $this->section == self::SECTMANAGERS) {
            $managers = $this->generateManagers($write);
            //return fopen($this->outdir . $pattern, 'w');
            $fp = $this->openManagersDetail($outputfile);
            //$fp = fopen($this->outdir . $pattern, 'w');
            // Escribimos las participadas
            foreach ($managers as $line) {
                $array = [
                        $line['Nombre'],
                        $line['Fecha'],
                        $line['Cargo'],
                ];
                fputcsv($fp, $array);
                fputcsv(
                    $this->handlers['detailManagers'],
                    array_merge([$this->company], $array)
                );
            }
            fclose($fp);
        }
    }

    public function processFileOLD($inputFileName, $write = false)
    {
        $this->setWrite($write);
        $outputfile = $this->loadFile($inputFileName);

        $shares = $this->generateShareholders($write);
        $subs = $this->generateSubsidiaries($write);
        if ($write) {
            // Escribimos el resumen
            fputcsv($this->handlers['summary'], [$this->company, count($shares), count($subs)]);
            $fp = $this->openShareholdersDetail($outputfile);

            // Escribimos los accionistas
            foreach ($shares as $line) {
                $array = [
                    $line['Nombre'],
                    $line['via'],
                    $line['Pais'],
                    $line['Tipo'],
                    $line['Direct'],
                    $line['Total'],
                ];
                fputcsv($fp, $array);
                fputcsv(
                    $this->handlers['detailShareholders'],
                    array_merge([$this->company], $array)
                );
            }
            fclose($fp);

            $fp = $this->openSubsidiariesDetail($outputfile);
            // Escribimos las participadas
            foreach ($subs as $line) {
                $array = [
                        $line['Nombre'],
                        $line['Pais'],
                        $line['Tipo'],
                        $line['Direct'],
                        $line['Total'],
                ];
                fputcsv($fp, $array);
                fputcsv(
                    $this->handlers['detailSubsidiaries'],
                    array_merge([$this->company], $array)
                );
            }
            fclose($fp);
        }
    }

    public function test()
    {

        $inputFileNames = [
            '1953 GRUP SOLER CONSTRUCTORA SL.xls',
            'ACCENTURE SLU.xlsx',
            'AMBU A@@SLASH@@S.xlsx',
            'BAIN & COMPANY IBERICA INC SEE.xlsx',
            'BOIRON.xlsx',
            'CH BOEHRINGER SOHN AG & CO KG.xlsx',
            'COFANO FARMACEUTICA NOROESTE SC GALLEGA.xls',
            'COOPERATIVA FARMACEUTICA DE TENERIFE COFARTE SC.xls',
            'ESLINGA SANITARIA SL.xls',
            'FIATC MUTUA DE SEGUROS Y REASEGUROS A PRIMA FIJA.xlsx',
            'GRUPO PLEXUS TECH SL.xls',
            'GRUPO QUIJILIANA SL.xls',
            'LABIANA HEALTH SL.xls',
            'PRICEWATERHOUSECOOPERS LLP.xlsx',
            'REALIZACION DE CONSULTORIOS MEDICOS SL.xls',
            'RIOLACORBET SL.xls',
            'SAINTRA SL.xls',
            'SANI CONSULT SL.xls',
            'SERVICIOS SOCIO SANITARIOS GENERALES SPAIN SL.xls',
            'SIBEL HEALTHCARE SL.xls',
            'THE LAST VAN SL.xls',
            'THINK IN POSITIVE & SMILE SL.xls',
            'TNR SOCIOS INVERSORES SL.xls',
            'USLRM PARENT COMPANY SL.xls'
        ];
        $companies = [];
        foreach ($inputFileNames as $name) {
            //$inputFileName = __DIR__ . '/../../../sanitypower/migrations/ACCENTURE SLU.xlsx';
            $inputFileName = __DIR__ . '/../../../sanitypower/migrations/' . $name;
            $e = explode('/', $inputFileName);
            $_empresa = $e[count($e)-1];
            //empresa = file.replace("@@SLASH@@", "/").replace("@@QUOTE@@","’")
            $_empresa = substr($_empresa, 0, strpos($_empresa, '.'));
            //$search = ['@@SLASH@@', '@@QUOTE@@'];
            //$replace = ['/', '’'];
            //$empresa = str_replace($search, $replace, $_empresa);
            $this->stripCompanyName($_empresa);
            $inputFileType = IOFactory::identify(
                $inputFileName,
                [
                    IOFactory::READER_XLS,
                    IOFactory::READER_XLSX,
                ]
            );
            $reader = IOFactory::createReader($inputFileType);
            $worksheetNames = $reader->listWorksheetNames($inputFileName);
            //$reader->setLoadSheetsOnly($worksheetNames[0]);
            //$helper->log('Loading file ' . /** @scrutinizer ignore-type */ pathinfo($inputFileName, PATHINFO_BASENAME)
            //    . ' using IOFactory to identify the format');
            //$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
            $filter = new PhpreaderHelper();
            //$reader->setReadFilter($filter);
            /**  Advise the Reader that we only want to load cell data  **/
            $reader->setReadDataOnly(true)
            ->setReadEmptyCells(false);
            $spreadsheet = $reader->load($inputFileName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(false, true, true, true);
            $contents = [];
            $rowIndex = 1;
            $store = false;
            //dump($sheetData);
            // Hacemos limpieza y marcamos las secciones 'A' y 'P'
            foreach ($sheetData as $row) {
                $line = [];
                foreach ($row as $col => $value) {
                    if (null!=$value) {
                        $line[$col] = $value;
                        if ($value == 'Accionistas actuales') {
                            if (empty($contents['A'])) {
                                $contents['A'] = $rowIndex;
                                $store = true;
                            }
                        }
                        if ($value == 'Participadas actuales') {
                            if (empty($contents['P'])) {
                                $contents['P'] = $rowIndex;
                            }
                        }
                    }
                }
                if (count($line) && $store) {
                    $contents[$rowIndex] = $line;
                }
                $rowIndex++;
            }
            //dump($contents);

            // INICIO DE ACCIONISTAS
            $shareholders = [];
            $rowIndex = $contents['A'];
            $colTitles = [];
            $keys = $orbisKeys;
            $limit = $contents['P'];
            while (count($colTitles)<5 && $rowIndex <$limit) {
                if (!empty($contents[$rowIndex])) {
                    foreach ($contents[$rowIndex] as $key => $value) {
                        if ($value==$orbisKeys['A']['Nombre'] || ($value==$sabiKeys['A']['Nombre'])) {
                            $colTitles['Nombre'] = $key;
                            if ($value==$sabiKeys['A']['Nombre']) {
                                $keys = $sabiKeys;
                            }
                        }
                        if ($value==$keys['A']['Pais']) {
                            $colTitles['Pais'] = $key;
                        }
                        if ($value==$keys['A']['Tipo']) {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($value==$keys['A']['Direct']) {
                            $colTitles['Direct'] = $key;
                        }
                        if ($value==$keys['A']['Total']) {
                            $colTitles['Total'] = $key;
                        }
                    }
                }
                $rowIndex++;
            }
            if (count($colTitles)) {
                $colTitles['class'] = $keys['class'];
                $colTitles['empresa'] = $empresa;
            } else {
                $keys = $orbisKeys; // Inicializamos por no haber accionistas
            }
            $class = $keys['class'];
            //dump($colTitles);
            $index = 0;
            while ($rowIndex<$limit) {
                $line = [];
                $viastr = 'via its funds';
                $via= '';
                if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                    $Nombre = $contents[$rowIndex][$colTitles['Nombre']];
                    $funds = stripos($Nombre, $viastr);
                    if ($funds) {
                        $via = $viastr;
                        $Nombre = substr($Nombre, 0, $funds);
                    }
                }
                if ($class=='ORBIS') {
                    if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                        if ($contents[$rowIndex][$colTitles['Nombre']]=='Leyenda') {
                            $rowIndex = $limit;
                        } else {
                            $line = [
                                'Nombre' => $Nombre,
                                'via' => $via,
                                'Pais' => $contents[$rowIndex][$colTitles['Pais']],
                                'Tipo' => $contents[$rowIndex][$colTitles['Tipo']],
                                'Direct' => $contents[$rowIndex][$colTitles['Direct']],
                                'Total' => $contents[$rowIndex][$colTitles['Total']],
                                'row' => $rowIndex
                            ];
                        }
                    }
                } else {
                    if (!empty($contents[$rowIndex]['A'])) {
                        $i = $contents[$rowIndex]['A'];
                        $i = substr($i, 0, strpos($i, '.'));
                        //dump($i);
                        if (is_numeric($i) && ($i==($index+1))) {
                            if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                                $Nombre = $contents[$rowIndex][$colTitles['Nombre']];
                            } else {
                                foreach ($contents[$rowIndex] as $key => $value) {
                                    //dump($key, $value);
                                    if ($key>'A' && $value != $contents[$rowIndex][$colTitles['Pais']]) {
                                        $Nombre = $value;
                                        break;
                                    }
                                }
                            }
                            $funds = stripos($Nombre, $viastr);
                            if ($funds) {
                                $via = $viastr;
                                $Nombre = substr($Nombre, 0, $funds);
                            }
                            $line = [
                                'index' => ++$index,
                                'Nombre' => $Nombre,
                                'via' => $via,
                                'Pais' => $contents[$rowIndex][$colTitles['Pais']],
                                'Tipo' => $contents[$rowIndex][$colTitles['Tipo']],
                                'Direct' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Direct']]),
                                'Total' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Total']]),
                                'row' => $rowIndex
                            ];
                        }
                    }
                }
                if (count($line)) {
                    if (!empty($Nombre)) {
                        $line['Nombre'] = str_replace($NombreSearch, $NombreReplace, $Nombre);
                    }
                    $shareholders[] = $line;
                }
                $rowIndex++;
            }

            // FIN ACCIONISTAS
            //dump($shareholders);

            $colTitles = $subsidiaries = [];
            $colTitles = [
                'index' => 'A'
            ];
            $limit = count($sheetData);
            $rowIndex = $contents['P'];
            while (count($colTitles)<5 && $rowIndex <$limit) {
                if (!empty($contents[$rowIndex])) {
                    foreach ($contents[$rowIndex] as $key => $value) {
                        if ($value==$sabiKeys['P']['Nombre']) {
                            $colTitles['Nombre'] = $key;
                            $keys = $sabiKeys;
                        }
                        if ($value==$keys['P']['Pais']) {
                            $colTitles['Pais'] = $key;
                        }
                        if ($value==$keys['P']['Tipo']) {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($value==$keys['P']['Direct']) {
                            $colTitles['Direct'] = $key;
                        }
                        if ($value==$keys['P']['Total']) {
                            $colTitles['Total'] = $key;
                            $colTitles['row'] = $rowIndex;
                        }
                    }
                }
                $rowIndex++;
            }
            $colTitles['class'] = $keys['class'];
            $colTitles['empresa'] = $empresa;
            //dump($colTitles);

            $index = 0; // Indice de participadas
            // El count es por si no hay participadas
            while ($rowIndex<$limit && count($colTitles)) {
                if (empty($colTitles['Nombre'])) {
                    // ORBIS, no tenemos la columna del nombre
                    /*foreach ($contents[$rowIndex] as $key => $value) {
                        dump($key, $value);
                        if ($key>'A' && $value != $contents[$rowIndex][$colTitles['Pais']]) {
                            $colTitles['Nombre'] = $value;
                            //$Nombre = $value;
                            //break;
                        } else {
                            break;
                        }
                    }*/
                    $x = 0;
                    $xfound = false;
                    foreach ($contents[$rowIndex] as $key => $value) {
                        $x++;
                        if ($x>1 && (strlen($value)>2) && !$xfound) {
                            $colTitles['Nombre'] = $key;
                            //dump("key: $key, value: $value, Nombre: " . $colTitles['Nombre']);
                            $xfound = true;
                            break;
                        }
                    }
                    //dump($colTitles);
                }
                if ((!empty($contents[$rowIndex]['A'])) && ($contents[$rowIndex]['A']=='Leyenda')) {
                    $rowIndex = $limit;
                } else {
                }
                //dump($colTitles);
                if (!empty($contents[$rowIndex]['A'])) {
                    $i = $contents[$rowIndex]['A'];
                    if (substr($i, 0, strpos($i, '.'))) {
                        $i = substr($i, 0, strpos($i, '.'));
                    } else {
                        $i = rtrim($i);
                    }
                    //dump("linea: $rowIndex, index: $i");
                    if (is_numeric($i) && ($i==($index+1))) {
                        if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                        }
                        if (empty($colTitles['Tipo'])) {
                            $Tipo = 'C';
                        } else {
                            $Tipo = $contents[$rowIndex][$colTitles['Tipo']];
                        }
                        $subsidiaries[] = [
                            'index' => ++$index,
                            //'Nombre' => str_replace($NombreSearch, $NombreReplace, $contents[$rowIndex][$colTitles['Nombre']]),
                            'Nombre' => $this->stripCompanyName($contents[$rowIndex][$colTitles['Nombre']]),
                            'Pais' => $contents[$rowIndex][$colTitles['Pais']]??'--',
                            'Tipo' => $Tipo,
                            'Direct' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Direct']])??0,
                            'Total' => str_replace(',', '.', $contents[$rowIndex][$colTitles['Total']])??0,
                            'row' => $rowIndex,
                        ];
                    }
                }
                $rowIndex++;
            }
            $companies[] = [
                'name' => $empresa,
                'class' => $class,
                'shareholders' => $shareholders,
                'subsidiaries' => $subsidiaries,
            ];
        }

//dump($subsidiaries);
        return $this->render('phpreader/test.html.twig', [
            'controller_name' => 'PhpreaderController',
            'empresas' => $companies,
        ]);
    }
}
