<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProcesadorNotasRequest;
use App\Http\Requests\UpdateProcesadorNotasRequest;
use App\Models\Apinotas;
use App\Models\ProcesadorNotas;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcesadorNotasController extends Controller {

    //guardar estado de procesamiendo de nota
    public function index() {
        //extreaer consecutivo de procesador de notas
        $consecutivo = Apinotas::max('consecutivo');
        $consecutivo = (strlen($consecutivo) > 0) ?  '7202767' : $consecutivo;

        if ($consecutivo == null) {
            $consecutivo = 0;
        }
        if ($consecutivo == 0) {
            $consecutivo += 1;
        }
        // dd($consecutivo);
        $consecutivo =   (int) $consecutivo;
        $consecutivoHasta =   ($consecutivo + 1);
        // dd($consecutivo , $consecutivoHasta);
        // ciclo para consultar notas y procesarlas
        $idNotas = [
            /* 7262513, 
          7261871, 
          7261936,
          7260730,
          7261903,
          7261418,
          7261853,
          7261786,
          7261763,
          7261488,
          7260651,
          7261469,
          7261507,
          7260929,
          7261451,
          7261099,
          7261216,
          7259384,
          7261081,
          7261320,
          7260974,
          7260894,
          7260623,
          7261049,
          7260802,
          7260865,
          7260848,
          7260912,
          7260777,
          7259619,
          7260819,
          7260701,
          7260539,
          7260385,
          7260332,
          7259840,
          7259401,
          7260037,
          7260154,
          7260137,
          7260072,
          7260186,
          7260055,
          7259822,
          7259995,
          7258472,
          7259767,*/
            // 7256157,
            /* 7259498,
          7259657,
          7259440,
          7259104,
          7259212,
          7259515,
          7259553,
          7259256,
          7259178,
          7259536,
          7259229,
          7259457,
          7258873,
          7258852,
          7259078,
          7259195,
          7258743,
          7258046,
          7258257,*/
            //7258795,
            // 7258778,
            /* 7258423,
          7258592,
          7258760,
          7258455,
          7258495,*/
            //  7258197,
            /*7258026,
          7257982,
          7257949,
          7257928,
          7257874,
          7257832,
          7255286,*/
            //  7255767,
            // 7257781,
            //7257737,
            //  7257526,
            //   7257612,
            //  7257544,
            //   7257561,
            //  7257578,
            //  7257595,
            /*  7257453,
          7257431,
          7257394,
          7257327,
          7257306,
          7257263,*/
            //   7257246
        ];
        $consecutivoHasta = count($idNotas); /**/
        // dd($idNotas);
        for ($i = 0; $i <  2; $i++) {
            //$consecutivoUrl =$idNotas[$i];            
            $consecutivoUrl = '7257595'; // $consecutivo;// 6583041;//6583024;//6582752;//6583302;//6583007;
            $urlBaseNota = env('NEWS_PORLET_URL') . $consecutivoUrl . ".json";
            // dd($urlBaseNota);
            // $consecutivo++;
            //  dd($urlBaseNota);
            //procesar nota
            //   echo $i;
            //consulta procesamiento previo de nota
            $codigoNota =  explode('getArticle/', $urlBaseNota);
            $codigoNota =  explode('.json', $codigoNota[1]);
            $codigoNota =  $codigoNota[0];
            $notaProcesadaPreviamente = $this->getNotaProcesada($codigoNota);
            // dd($notaProcesadaPreviamente);
            //enviar datos a croma
            $notaProcesada = $this->getNotas($urlBaseNota);
            $dataSendCroma = $this->sendDataCroma($notaProcesada);
            // dd($notaProcesada);
            if (isset($dataSendCroma->status)) {
                //   dd($dataSendCroma);
                if ($dataSendCroma->status == 'OK') {
                    if ($notaProcesada !== null && $notaProcesada !== false && $notaProcesada !== '' &&  !empty($notaProcesada) && $notaProcesadaPreviamente == null) {
                        //  dd($notaProcesada);
                        //guardar estado procesado en base de datos
                        //url, codigo_nota, estado_procesamiento
                        $procesadorNotas = new ProcesadorNotas();
                        $procesadorNotas->url = $notaProcesada['link'];
                        $procesadorNotas->codigo_nota = $notaProcesada['id'];
                        $procesadorNotas->estado_procesamiento = 1;
                        $procesadorNotas->save();

                        \Log::info('Nota enviada a croma: ' . json_encode($dataSendCroma));
                        \Log::info('Nota procesada: ' . json_encode($procesadorNotas));
                    } else {
                        $error = [
                            'error' => 'La nota ya fue procesada previamente',
                            'url' => $urlBaseNota
                        ];
                        // $consecutivo +=1;
                        \Log::error(json_encode($error));
                        echo ('no se pudo procesar la nota --> ' . $urlBaseNota . '<br/>');
                    }

                    //actualizar consecutivo de procesador de notas
                    $procesadorNotas = new Apinotas();
                    $procesadorNotas->consecutivo = $consecutivo;
                    $procesadorNotas->save();
                } else  if ($dataSendCroma->status == 'warning') {
                    \Log::info('Nota ya se envio previamente a croma: ' . $urlBaseNota);
                }
            } else {
                $error = [
                    'error' => 'al enviar datos a  ' . env('API_CROMA_URL'),
                    'statusCode' => $dataSendCroma,
                    //'notaProcesada' => $notaProcesada,
                    'urlNota' => $urlBaseNota
                ];

                //actualizar consecutivo de procesador de notas por si el consecutivo no existe
                // error 404 y en la proxima iteraccion no lo tenga en cuenta
                $procesadorNotas = new Apinotas();
                $procesadorNotas->consecutivo = $consecutivo;
                $procesadorNotas->save();
                \Log::error(json_encode($error));
                continue;
                //  dd($error);
            }
        }
    }
    //consultar procesamiento previo de nota
    public function getNotaProcesada($codigoNota) {
        $procesadorNotas = ProcesadorNotas::where('codigo_nota', $codigoNota)->first();
        if ($procesadorNotas !== null && $procesadorNotas !== false && $procesadorNotas !== '' &&  !empty($procesadorNotas)) {
            return $procesadorNotas;
        } else {
            return null;
        }
    }

    //enviar json de nota  a api croma
    public function sendDataCroma($datosNota) {
        $url = env('API_CROMA_URL') . 'api/v1/add';
        // dd($url);
        $postdata = json_encode($datosNota);
        // dd($postdata);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        //convertir respuesta de croma a json
        $result = json_decode($result, true);
        $result = (object) $result;
        //  print_r ($result);
        //   dd($result);
        if (isset($result->status)) {
            return $result;
        } else {
            // dd($result->statusCode);
            return $result; // false;
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNotas($urlBaseNota) {
        //leer datos json de url externa
        //$urlBaseNota = "https://www.eluniversal.com.co/news-portlet/getArticle/";
        //$url = $urlBaseNota . '6574438.json'; //6576848//6574438//6545462//6567346
        //  dd($urlBaseNota);
        $response = Http::get($urlBaseNota);
        $status = $response->status(); //200 o 404 o 500 o 403 etc
        // dd($status);
        $body = $response->body();
        // echo "<pre>"; print_r($body); echo "</pre>"; die;
        //dd($body); //Contendio json
        //convertir body a json
        $json = json_decode($body, true);

        $dataSendCroma = [];
        $arrContent = [];
        //dd($json);
        // si existe nota 
        if (isset($json['article']['content']['component'])) {
            $tituloNota = '';
            foreach ($json['article']['content']['component'] as $key => $value) {
                //eliminar Headline, HTML_Text, Subheadline                 
                if (
                    $json['article']['content']['component'][$key]['name'] !== 'Headline'
                    &&  $json['article']['content']['component'][$key]['name'] !== 'Subheadline'
                    &&  $json['article']['content']['component'][$key]['name'] !== 'HTML_Text'
                ) {
                    //unset($json['article']['content']['component'][$key]);
                    array_push($arrContent, $json['article']['content']['component'][$key]);
                }
            }
            // dd($arrContent);

            $img = '';
            foreach ($arrContent as $keyimg => $valueimg) {
                if ($arrContent[$keyimg]['name'] == 'Image') {
                    $img = '<img src="' . $arrContent[$keyimg]['remoteContent']['href'] . '" alt="">';
                }
            }

            //extraer el autor de la nota
            $nombreAuthor = '';
            //Si exite article->metadata->vocabulary  se toma $json['article']['metadata']['vocabulary']
            if (isset($json['article']['metadata']['vocabulary'])) {
                $datosAutor = $json['article']['metadata']['vocabulary']; 
//dd($datosAutor);
                foreach ($datosAutor as $keyAuthor => $valueAuthor) {
                   
                    if (isset($datosAutor[$keyAuthor]['name']) && $datosAutor[$keyAuthor]['name']  == 'Autores') {
                        $nombreAuthor = $datosAutor[$keyAuthor]['category']['id'] . '@' . $datosAutor[$keyAuthor]['category']['name'];
                    }else{
                        
                        $nombreAuthor = $datosAutor['category']['id'] . '@' . $datosAutor['category']['name'];
                    } 
                }
            } else {
                //si no existe article->metadata->vocabulary  se toma  $json['article']['metadata']['sections']['section']['name']; -->
                // --> puede se un informe empresarial o un especial
                $nombreAuthor = $json['article']['metadata']['sections']['section']['name'];
            }                
            //extraer el autor de la nota
            //extraer el titulo de la nota
            $tituloNota = '';
            $datoTituloNota = $json['article']['content']['component'];
            foreach ($datoTituloNota as $keyTitulo => $valueTitulo) {
                if ($datoTituloNota[$keyTitulo]['name'] == 'Headline') {
                    $tituloNota = $datoTituloNota[$keyTitulo]['__text'];
                }
            }
            //extraer el sumario de la nota
            $sumarioNota = '';
            $datoSumarioNota = $json['article']['content']['component'];
            foreach ($datoSumarioNota as $keySumario => $valueSumario) {
                if ($datoSumarioNota[$keySumario]['name'] == 'Subheadline') {
                    $sumarioNota = $datoSumarioNota[$keySumario]['__text'];
                }
            }


            $urlDeArticulo = $json['article']['metadata']['link']['href'];
            $urlDeArticulo = str_replace(env('NEWS_PORLET_URL'), 'https://www.eluniversal.com.co/', $urlDeArticulo);
           // dd($urlDeArticulo);
            $cuerpoNotaText =  $this->downloadHtml($urlDeArticulo);
            // dd($cuerpoNotaText);
            // $cuerpoNotaText = "$img<br><p>$cuerpoNotaText</p>";
            //  dd($cuerpoNotaText);
            //armar array con datos de nota
            $fechaArticulo = str_replace('-05:00', '', $json['article']['metadata']['properties']['createdate']);
            //dd($fechaArticulo);
            $dataSendCroma['id'] = (int) $json['article']['articleid'];
            $dataSendCroma['title'] =  array('rendered' => $tituloNota);
            $dataSendCroma['excerpt'] = array('rendered' =>  $sumarioNota);
            $dataSendCroma['content'] = array('rendered' => $cuerpoNotaText); //$json['article']['metadata']['link']['href';
            $dataSendCroma['date'] = $fechaArticulo;
            $dataSendCroma['link'] = $urlDeArticulo;
            $dataSendCroma['author'] =  $nombreAuthor; // $json['article']['metadata']['vocabulary'][1]['category']['name'];
            //$dataSendCroma['image'] = $img;
            //  dd($dataSendCroma); //Contendio 
            return $dataSendCroma;
        } else {
            return $dataSendCroma;
        }
    }




    /**
     * Descarga el html de la nota y lo almacena en public/nota.hml
     *
     * @return  retorna el cuerpo de la nota en texto plano
     */
    function downloadHtml($url) {
        $archivoHtml = 'nota.html';
        $html = file_get_contents($url);
        $file = fopen($archivoHtml, "w");
        fwrite($file, $html);
        fclose($file);
        //extraer div de nota.html 
        if (file_exists($archivoHtml)) {
            //exec("tar -xzvf " . $file . " -C " . $destiny);
            try {
                $html = file_get_contents($archivoHtml);
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $dom->preserveWhiteSpace = false;
                $cuerpoNota = $dom->getElementById('content-news-1');
                $cuerpoNota = $cuerpoNota->nodeValue;
                $cuerpoNota = strip_tags($cuerpoNota); //  $this-> html_to_text($cuerpoNota);
                $cuerpoNota = explode("NOTICIAS RECOMENDADAS", $cuerpoNota);
                $cuerpoNotaParte1 = $cuerpoNota[0];
                $cuerpoNotaParte2 = explode('/**/', $cuerpoNota[1]);
                // echo $cuerpoNotaParte2[1];die;
                $cuerpoNota = $cuerpoNotaParte1  . ' ' . $cuerpoNotaParte2[1];
                $cuerpoNota = str_replace('/**/', '', $cuerpoNota);
                return $cuerpoNota;
            } catch (Exception $e) {
                print_r($e);
                return FALSE;
            }
        }
    }
    // function para quitar caracteres especiales
    function quitarCaracteres($body) {
        $body = str_replace('<![CDATA[', '<p>', $body);
        $body = str_replace(']]>', '</p>', $body);
        $body = str_replace('&nbsp;', '', $body);
        return $body;
    }


    function html_to_text($string) {

        if (!empty($string)) {

            $string = str_replace('/', '&#47;', $string);
            $string = str_replace('<', '&lt;', $string);
            $string = str_replace('>', '&gt;', $string);
            $string = str_replace('"', '&#34;', $string);
            $string = str_replace("'", '&#39;', $string);

            return $string;
        }
    }
}
