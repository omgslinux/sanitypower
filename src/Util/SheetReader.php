<?php

namespace App\Util;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Repository\CompanyRepository as REPO;

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
            //'Nombre' => '', // no hay etiqueta para participadas
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
            //'Tipo' => 'Tipo',
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
    const LASTCOLUMN = 'EZ';
    const VIASTR = 'via its funds';

    private $company;
    private $class; // Para el tipo de fichero, SABI u ORBIS
    private $results=[];
    private $outdir;
    private $prefix; // Prefijo para guardar ficheros de datos
    private $section = "0"; // Section: "0"(all), "M"anagers, "A"ccionistas, "P"articipadas
    private $handlers = [
        'detailManagers' => null,
        'detailShareholders' => null,
        'detailSubsidiaries' => null,
        'summary' => null,
        'summaryManagers' => null,
        'summaryShareholders' => null,
        'summarySubsidiaries' => null,
    ];
    private $repo; // Repo de Company
    private $write; // Para indicar si se guardan ficheros CSV

    public function __construct()
    {
        //$this->repo = $repo;
    }

    public function checkSection($section)
    {
        if ($this->section == self::SECTALL) {
            return true;
        }

        return $this->section = $section;
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

    private function stripCompanyFileName($companyFilename): string
    {
        return REPO::getStrippedFileName($companyFilename);
        $search = ['@@SLASH@@', '@@QUOTE@@', '@@COMMA@@', '@@DOT@@'];
        $replace = ['/', '’', ',', '.'];
        // Obtenemos el nombre formal de la empresa, con todos sus caracteres imprimibles
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = trim(str_replace($search, $replace, strtoupper($companyFilename)));

        return $empresa;
    }

    private function stripCompanyName($company): string
    {
        return REPO::getStrippedCN($company);
        $search = [',', '.', '  '];
        $replace = [' ', '', ' '];
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = trim(str_replace($search, $replace, strtoupper($company)));

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
        $e = explode('/', $inputFileName);
        $result = [];
        $_empresa = $e[count($e)-1];
        //empresa = file.replace("@@SLASH@@", "/").replace("@@QUOTE@@","’")
        $_empresa = substr($_empresa, 0, strpos($_empresa, '.'));
        $result['companyfilename'] = $_empresa;
        $empresa = $this->stripCompanyFileName($_empresa);
        $result['realname'] = $empresa;
        $this->company = $result['company'] = $this->stripCompanyName($empresa);
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
        /**  Advise the Reader that we only want to load cell data  **/
        $reader->setReadDataOnly(true)
        ->setReadEmptyCells(false);
        $spreadsheet = $reader->load($inputFileName);
        //$sheetData = $spreadsheet->getActiveSheet()->toArray(false, true, true, true);
        $this->worksheet = $spreadsheet->getActiveSheet();
        $store = false;
        $result['class'] = '';
        $Mends = ['Leyenda', "Directores y gerentes previos", "Estructura de propiedad" ];
        foreach ($this->worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator('A', 'F');
            // This loops through all cells, even if a cell value is not set.
            // For 'TRUE', we loop through cells only when their value is set.
            $rowIndex = $row->getRowIndex();
            // If this method is not called, the default value is 'false'.
            $cellIterator->setIterateOnlyExistingCells(true);

            foreach ($cellIterator as $cell) {
                if ($cell == 'Directores y gerentes actuales') {
                    if (empty($result['M'])) {
                        $result['M'] = $rowIndex;
                    }
                }
                if ($cell == 'Administradores / contactos actuales') {
                    $Mends = ['Auditores de Cuentas y Bancos'];
                    if (empty($result['M'])) {
                        $result['M'] = $rowIndex;
                        $result['class'] = 'SABI';
                    }
                }
                if (!empty($result['M']) && empty($result['Mend']) && empty($result['A'])) {
                    foreach ($Mends as $Mend) {
                        if ($cell->getValue()==$Mend) {
                            $result['Mend'] = $rowIndex;
                        }
                    }
                }

                if ($cell == 'Accionistas actuales') {
                    $result['A'] = $rowIndex;
                }
                if ($cell == 'Participadas actuales') {
                    if (empty($result['P'])) {
                        $result['P'] = $rowIndex;
                    }
                }
            }
        }
        //$result['class'] = '';
        $result['total'] = $this->worksheet->getHighestRow(); //$rowIndex;
        //dump($result);
        $this->results = $result;
        //die();

        return $this->results['companyfilename'];
    }

    public function generateManagers($write = false)
    {
        $managers = [];
        if (empty($this->results['M']) || empty($this->results['Mend'])) {
            dump($this->results, "No hay managers");
            return $managers;
        }

        if ($this->results['class'] != 'SABI') {
            $managers = $this->managersORBISSearch1();
            if ($managers) return $managers;
            $managers = $this->managersORBISSearch2();
            if ($managers) return $managers;
        } else {
            $colKeys = ['Nombre', 'Título original de la función', 'Comité'];
            $colTitles = [];

            foreach ($this->worksheet->getRowIterator($this->results['M'], $this->results['Mend']) as $row) {
                $cellIterator = $row->getCellIterator('A', 'AZ');
                $cellIterator->setIterateOnlyExistingCells(true);
                $rowIndex = $row->getRowIndex();
                if (count($colTitles)<count($colKeys)) {
                    foreach ($cellIterator as $cell) {
                        $column = $cell->getColumn();
                        $value = $cell->getValue();
                        foreach ($colKeys as $colkey) {
                            //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                            if ($value==$colkey) {
                                $colTitles[$colkey] = $column;
                            }
                        }
                        $index = 1;
                    }
                } else {
                    //dump($colTitles);
                    foreach ($cellIterator as $cell) {
                        $column = $cell->getColumn();
                        $value = $cell->getValue();
                        $line = [];
                        $i = substr($value, 0, strpos($value, '.'));
                        //dump("value = $i, index: $index, icol: ".ord($column));
                        if ($i==$index) {
                            foreach ($colTitles as $colkey => $colletter) {
                                $text = $this->readValue($colletter . $rowIndex);
                                if (strlen($text)<3) {
                                    $nextcol = chr(ord($colletter)+1);
                                    if (strlen($colletter)==1) {
                                        if ($colletter =='Z') {
                                            $nextcol = 'AA';
                                        }
                                    } else {
                                        dump("key: $cell, value: $value, colkey: $colkey, colletter: $colletter");
                                    }
                                    //$text = $this->readValue((chr(ord($colletter)+1)) . $rowIndex);
                                    $text = $this->readValue($nextcol . $rowIndex);
                                }
                                $line[$colkey] = $text;
                            }
                            $managers [] = $line;
                            $index++;
                        }
                    }
                }
            }
        }
        //dump($managers);
        return $managers;
    }

    public function generateShareholders($write = false): array
    {
        // INICIO DE ACCIONISTAS
        $shareholders = [];
        $colTitles = [];
        $keys = $orbisKeys = self::ORBISKEYS['A'];
        $sabiKeys = self::SABIKEYS['A'];
        $end = false;

        if (empty($this->results['A'])) {
            return $shareholders;
        }

        foreach ($this->worksheet->getRowIterator($this->results['A'], $this->results['P']) as $row) {
            $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
            $cellIterator->setIterateOnlyExistingCells(true);
            $rowIndex = $row->getRowIndex();
            $i = ''; // Inicializamos el indice en cada fila
            //dump("A$rowIndex: " .$this->readValue('A'.$rowIndex));
            $line = [];

            if ($end) {
                break;
            }
            //dump("A$rowIndex: " .$this->readValue('A'.$rowIndex));

            if (count($colTitles)<count($keys)) {
                //dump($keys);
                foreach ($cellIterator as $cell) {
                    $key = $cell->getColumn();
                    $value = $cell->getValue();
                    if ($value==$orbisKeys['Nombre'] || ($value==$sabiKeys['Nombre'])) {
                        $colTitles['Nombre'] = $key;
                        if ($value==$sabiKeys['Nombre']) {
                            $keys = $sabiKeys;
                            $class  = 'SABI';
                        } else {
                            $class  = 'ORBIS';
                        }
                        $results['classrow'] = $rowIndex;
                        if ($class != $this->results['class']) {
                            $this->results['class'] = $class;
                            //dump($this->results);
                        }
                    }
                    foreach ($keys as $colkey => $colvalue) {
                        //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                        if ($value==$colvalue) {
                            $colTitles[$colkey] = $key;
                        }
                    }
                }
                $colsfound = false;
            }

            if (!$colsfound) {
                if (count($colTitles)) {
                    $colTitles['empresa'] = $this->company;
                    $colsfound = true;
                    $class = $this->results['class'];

                    //dump($colTitles);
                    $index = 0;
                } else {
                    $keys = $orbisKeys; // Inicializamos por no haber accionistas
                }
            } else {
                if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                    $Nombre = $this->readValue($colTitles['Nombre'].$rowIndex);
                }
                //dump("class: $class");
                if ($class=='ORBIS') {
                    if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                        if ($this->readValue($colTitles['Nombre'].$rowIndex)=='Leyenda') {
                            $end = true;
                        } else {
                            if (strlen($Nombre)<5 && $this->readValue($colTitles['Pais'].$rowIndex)) {
                                foreach ($cellIterator as $cell) {
                                    if ($key==$keys['Pais']) {
                                        break;
                                    }
                                    $key = $cell->getColumn();
                                    $value= $cell->getValue();
                                    //dump("row: $rowIndex, key: $key, value: $value, xfound: $xfound");
                                    if (($key > 'A') && (strlen($value)>3)) {
                                        //$xfound = true;
                                        //$colTitles['Nombre'] = $key;
                                        $Nombre = $value;
                                        //dump($colTitles);
                                        break;
                                    }
                                }
                            }
                            if (strlen($Nombre)>4) {
                                $line = [
                                    'Nombre' => $Nombre,
                                    //'via' => $via,
                                    'Pais' => $this->readValue($colTitles['Pais'].$rowIndex),
                                    'Tipo' => $this->readValue($colTitles['Tipo'].$rowIndex),
                                    'Direct' => str_replace(',', '.', $this->readValue($colTitles['Direct'].$rowIndex)),
                                    'Total' => str_replace(',', '.', $this->readValue($colTitles['Total'].$rowIndex)),
                                    'row' => $rowIndex,
                                ];
                            }
                        }
                    }
                } else {
                    // ACCIONISTAS, SABI
                    if (!empty($this->readValue('A'.$rowIndex))) {
                        $i = $this->readValue('A'.$rowIndex);
                        $i = substr($i, 0, strpos($i, '.'));
                        //dump("i: $i, index: $index");
                        //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                        if (is_numeric($i) && ($i==($index+1))) {
                            if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                                $Nombre = $this->readValue($colTitles['Nombre'].$rowIndex);
                            } else {
                                $Nombre = null;
                                foreach ($cellIterator as $cell) {
                                    // ¡¡¡¡NO SACAR LA LINEA SIGUIENTE, NO FUNCIONA EL BUCLE!!!!
                                    $_pais = $this->readValue($colTitles['Pais'].$rowIndex);
                                    /*dump(
                                        "cell: ".$cell->getValue(). ",
                                        value: ".$this->readValue($colTitles['Pais'].$rowIndex)
                                    ); */
                                    if ($cell->getColumn()>'A' && trim($cell->getValue()) != $_pais) {
                                    //if ($cell->getColumn()>'A' && ($cell->getColumn()<$colTitles['Pais'])) {
                                        $Nombre = $cell->getValue();
                                    } else {
                                        break;
                                    }
                                }
                                //dump("Nombre: $Nombre");
                            }
                            $line = [
                                'index' => ++$index,
                                'Nombre' => $Nombre,
                                //'via' => $via,
                                'Pais' => $this->readValue($colTitles['Pais'].$rowIndex),
                                'Tipo' => $this->readValue($colTitles['Tipo'].$rowIndex),
                                'Direct' => str_replace(',', '.', $this->readValue($colTitles['Direct'].$rowIndex)),
                                'Total' => str_replace(',', '.', $this->readValue($colTitles['Total'].$rowIndex)),
                                'row' => $rowIndex,
                            ];
                        }
                    }
                }
            }
            if (count($line)) {
                /* if (!empty($Nombre)) {
                    //$line['Nombre'] = str_replace($NombreSearch, $NombreReplace, $Nombre);
                    $line['Nombre'] = $this->stripCompanyName($Nombre);
                } */
                $line['S'] = 'A'; // Seccion: accionistas
                //dump($line);
                $shareholders[] = $line;
            }
        }

        return $shareholders;
        // FIN ACCIONISTAS
    }

    public function generateSubsidiaries($write = false)
    {
        $colTitles = $subsidiaries = [];
        if (empty($this->results['P'])) {
            return $subsidiaries;
        }
        $class = $this->results['class']??'ORBIS';
        $keys = self::ORBISKEYS['P'];
        if ($class == 'SABI') {
            $keys = self::SABIKEYS['P'];
        }
        //dump($foundColTitles);
        $end = false;
        $index = 0; // Indice de participadas
        foreach ($this->worksheet->getRowIterator($this->results['P'], $this->results['total']) as $row) {
            $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
            $cellIterator->setIterateOnlyExistingCells(true);
            $rowIndex = $row->getRowIndex();
            $i = ''; // Inicializamos el indice en cada fila
            //dump("A$rowIndex: " .$worksheet->getCell('A'.$rowIndex)->getValue());
            $line = [];

            if ($end) {
                break;
            }

            if (count($colTitles)<count($keys)) {
                //dump($keys);
                foreach ($cellIterator as $cell) {
                    $key = $cell->getColumn();
                    $value = $cell->getValue();
                    if ($value==self::SABIKEYS['P']['Nombre']) {
                        $colTitles['Nombre'] = $key;
                        if ($class == '') {
                            $keys = self::SABIKEYS['P'];
                            dump("(P) Cambio en deteccion por key($value) de class a SABI para ".$this->results['company']);
                        }
                        $class = $this->results['class'] = 'SABI';
                    }
                    foreach ($keys as $colkey => $colvalue) {
                        if (empty($colTitles[$colkey])) {
                            //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                            if ($value==$colvalue) {
                                $colTitles[$colkey] = $key;
                                //dump("(P, $class): Encontrada clave $colkey en columna $key fila $rowIndex");
                                //dump("Van ".count($colTitles)." claves encontradas de $foundColTitles.");
                            }
                        } else {
                            //dump("Ya se encontró $colkey(".$colTitles[$colkey]."). No se evalua.");
                        }
                    }
                }
                $colsfound = false;
            }
            if (!$colsfound) {
                //dump("row: $rowIndex, No colsfound, count: ".count($colTitles) .", foundColTitles: $foundColTitles");
                if (count($colTitles)>=count($keys)) {
                    $colTitles['index']='A';
                    //dump("Halladas todas las columnas: rowIndex($rowIndex), colTitles:", $colTitles);

                    $colsfound = true;
                }
            }
            // El count es por si no hay participadas
            //if (count($colTitles)>$foundColTitles) {
            if ($colsfound) {
                if ((!empty($this->readValue('A'.$rowIndex))) && ($this->readValue('A'.$rowIndex)=='Leyenda')) {
                    $end = true;
                    break;
                }
                if (!empty($this->readValue('A'.$rowIndex))) {
                    $i = trim($this->readValue('A'.$rowIndex));
                    if (substr($i, 0, strpos($i, '.'))) {
                        // SABI
                        $i = substr($i, 0, strpos($i, '.'));
                    } else {
                        // ORBIS
                        $i = rtrim($i);
                    }
                    //dump("S: P, class: $class, row: $rowIndex, i: $i, index: $index");
                    if (is_numeric($i) && ($i==($index+1))) {
                        //dump("i; $i, index: $index");
                        $Nombre = (!empty($colTitles['Nombre'])?$this->readValue($colTitles['Nombre'].$rowIndex):null);
                        $Tipo = (!empty($colTitles['Tipo'])?$this->readValue($colTitles['Tipo'].$rowIndex):'C');
                        /*if (!empty($colTitles['Nombre'])) {
                            //dump("row: $rowIndex, key: $key, value: $value, colTitles:", $colTitles);
                            if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                                $Nombre = $this->readValue($colTitles['Nombre'].$rowIndex);
                            }
                        } */
                        if (null!=$Nombre || strlen($Nombre)<4) {
                            // ORBIS, no tenemos la columna del nombre
                            foreach ($cellIterator as $cell) {
                                $key = $cell->getColumn();
                                if ($key==$keys['Pais']) {
                                    break;
                                }
                                $value= $cell->getValue();
                                //dump("row: $rowIndex, key: $key, value: $value");
                                if (($key != 'A') && (strlen($value)>3)) {
                                    $Nombre = $value;
                                    //dump($colTitles);
                                    break;
                                }
                            }
                        }
                        $line = [
                            'index' => ++$index,
                            'Nombre' => $Nombre, // NO HAY QUE HACER STRIP
                            //'Via' => $via,
                            'Pais' => $this->readValue($colTitles['Pais'].$rowIndex)??'--',
                            'Tipo' => $Tipo,
                            'Direct' => str_replace(',', '.', $this->readValue($colTitles['Direct'].$rowIndex))??0,
                            'Total' => str_replace(',', '.', $this->readValue($colTitles['Total'].$rowIndex))??0,
                            'row' => $rowIndex,
                            'class' => $this->results['class'],
                            'S' => 'P',
                        ];
                        //dump($line);

                        $subsidiaries[] = $line;
                    }
                }
            }
        }

        return $subsidiaries;
    }

    private function managersORBISSearch1()
    {
        $managers = [];
        foreach ($this->worksheet->getRowIterator($this->results['M'], $this->results['Mend']) as $row) {
            $cellIterator = $row->getCellIterator('A', 'AZ');
            $cellIterator->setIterateOnlyExistingCells(true);
            $rowIndex = $row->getRowIndex();
            $search = ["\n"]; // "\xa0"];
            $replace = ["@@"]; //, " "];
            foreach ($cellIterator as $cell) {
                $lines = explode('@@', str_replace($search, $replace, $cell->getValue()));
                //dump($lines);
                if (count($lines)>2) {
                    $managers[] = $lines;
                    break;
                }
            }
        }

        return $managers;
    }

    private function managersORBISSearch2()
    {
        $managers = [];
        $line = [];
        foreach ($this->worksheet->getRowIterator($this->results['M'], $this->results['Mend']) as $row) {
            $cellIterator = $row->getCellIterator('A', 'AZ');
            $cellIterator->setIterateOnlyExistingCells(true);
            $rowIndex = $row->getRowIndex();

            $colIndex = null;
            // Buscamos la primera columna que tenga contenido, y la usamos como columna de contenido
            foreach ($cellIterator as $cell) {
                $column = $cell->getColumn();
                $value = $cell->getValue();
                if (strlen($value)>4 && $column != 'A') {
                    $colIndex = $cell->getColumn();
                    break;
                }
            }
            if (null!=$colIndex) {
                $line[] = $this->readValue($colIndex . $rowIndex);
                if (count($line)==3) {
                    $managers[]=$line;
                    $line=[];
                }
            }
        }

        return $managers;
    }

    public function openResultsFiles()
    {
        if (null!=$this->section) {
            $this->handlers['summary'] = fopen($this->outdir . '__resultados__' . $this->prefix, 'w');
        }
        //if ($this->section == self::SECTALL || $this->section == self::SECTMANAGERS) {
        if ($this->checkSection(self::SECTMANAGERS)) {
            $this->handlers['detailManagers'] = fopen($this->outdir . '__detalles_MANAGERS_' . $this->prefix, 'w');
            $this->handlers['summaryManagers'] = fopen($this->outdir . '__resultados_MANAGERS_' . $this->prefix, 'w');
        }
        //if ($this->section == self::SECTALL || $this->section == self::SECTHOLDERS) {
        if ($this->checkSection(self::SECTHOLDERS)) {
            $this->handlers['detailShareholders'] = fopen($this->outdir . '__detalles_ACCIONISTAS_'.$this->prefix, 'w');
            $this->handlers['summaryShareholders']= fopen($this->outdir.'__resultados_ACCIONISTAS_'.$this->prefix, 'w');
        }
        //if ($this->section == self::SECTALL || $this->section == self::SECTOWNED) {
        if ($this->checkSection(self::SECTOWNED)) {
            $this->handlers['detailSubsidiaries'] = fopen($this->outdir .'__detalles_PARTICIPADAS_'.$this->prefix, 'w');
            $this->handlers['summarySubsidiaries']=fopen($this->outdir.'__resultados_PARTICIPADAS_'.$this->prefix, 'w');
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

        // Inicializamos para evitar error
        $shares = $subs = $managers = [];

        if ($this->checkSection(self::SECTHOLDERS)) {
            $shares = $this->generateShareholders($write);
            if (count($shares)) {
                if ($write) {
                    $fp = $this->openShareholdersDetail($outputfile);

                    // Escribimos los accionistas
                    foreach ($shares as $line) {
                        $array = [
                            $line['Nombre'],
                            //$line['via'],
                            $line['Pais'],
                            $line['Tipo'],
                            $line['Direct'],
                            $line['Total'],
                        ];
                        fputcsv($fp, $array, "\t");
                        fputcsv(
                            $this->handlers['detailShareholders'],
                            array_merge([$this->company], $array),
                            "\t",
                        );
                    }
                    fclose($fp);
                }
                fputcsv($this->handlers['summaryShareholders'], [$this->company, count($shares)]);
            }
        }

        //if ($this->section == self::SECTALL || $this->section == self::SECTOWNED) {
        if ($this->checkSection(self::SECTOWNED)) {
            $subs = $this->generateSubsidiaries($write);
            if (count($subs)) {
                $fp = $this->openSubsidiariesDetail($outputfile);
                // Escribimos las participadas
                foreach ($subs as $line) {
                    $array = [
                            $line['Nombre'],
                            //$line['Via'],
                            $line['Pais'],
                            $line['Tipo'],
                            $line['Direct'],
                            $line['Total'],
                    ];
                    fputcsv($fp, $array, "\t");
                    fputcsv(
                        $this->handlers['detailSubsidiaries'],
                        array_merge([$this->company], $array),
                        "\t"
                    );
                }
                fclose($fp);
            }
            fputcsv($this->handlers['summarySubsidiaries'], [$this->company, count($subs)]);
        }

        //if ($this->section == self::SECTALL || $this->section == self::SECTMANAGERS) {
        if ($this->checkSection(self::SECTMANAGERS)) {
            $managers = $this->generateManagers($write);
            //return fopen($this->outdir . $pattern, 'w');
            if (count($managers)) {
                $fp = $this->openManagersDetail($outputfile);
                //$fp = fopen($this->outdir . $pattern, 'w');
                // Escribimos las participadas
                foreach ($managers as $array) {
                    /*$array = [
                            $line['Nombre'],
                            $line['Fecha'],
                            $line['Cargo'],
                    ];*/
                    fputcsv($fp, $array, "\t");
                    fputcsv(
                        $this->handlers['detailManagers'],
                        array_merge([$this->company], $array),
                        "\t"
                    );
                }
                fclose($fp);
            }
            fputcsv($this->handlers['summaryManagers'], [$this->company, count($managers)]);
        }
        // Escribimos el resumen
        fputcsv($this->handlers['summary'], [$this->company, count($managers), count($shares), count($subs)]);

        $this->worksheet = null;
        //sleep(1);
        unset($this->worksheet);
    }

    public function readValue($cell)
    {
        return $this->worksheet->getCell($cell)->getValue();
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
            /**  Advise the Reader that we only want to load cell data  **/
            $reader->setReadDataOnly(true)
            ->setReadEmptyCells(false);
            $spreadsheet = $reader->load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $result = [];
            $rowIndex = 1;
            $store = false;
            echo '<table>';
            foreach ($worksheet->getRowIterator(100) as $row) {
                $cellIterator = $row->getCellIterator('A', 'AM');
                $cellIterator->setIterateOnlyExistingCells(true); // This loops through all cells,
                //$line = [];
                                                                   //    even if a cell value is not set.
                                                                   // For 'TRUE', we loop through cells
                                                                   //    only when their value is set.
                                                                   // If this method is not called,
                                                                   //    the default value is 'false'.
                foreach ($cellIterator as $cell) {
                    $line = [];
                    $line[$cell->getCoordinate()] = $cell->getValue();
                    if ($cell == 'Accionistas actuales') {
                        if (empty($result['A'])) {
                            $result['A'] = $cell->getRow();
                            $store = true;
                        }
                    }
                    if ($cell == 'Participadas actuales') {
                        if (empty($result['P'])) {
                            $result['P'] = $cell->getRow();
                        }
                    }
                    //echo '<td>' . $cell->getValue(). '</td>' . PHP_EOL;
                    //dump($cell->getCoordinate() . " = " . $cell->getValue());
                    $result[$cell->getRow()] = $line;
                }
            }
            dump($result);
            die();

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
                $via= '';
                if (!empty($contents[$rowIndex][$colTitles['Nombre']])) {
                    $Nombre = $contents[$rowIndex][$colTitles['Nombre']];
                    $funds = stripos($Nombre, self::VIASTR);
                    if ($funds) {
                        $via = self::VIASTR;
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
                            $funds = stripos($Nombre, self::VIASTR);
                            if ($funds) {
                                $via = self::VIASTR;
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
