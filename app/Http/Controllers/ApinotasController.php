<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreApinotasRequest;
use App\Http\Requests\UpdateApinotasRequest;
use App\Models\Apinotas;
use Illuminate\Support\Facades\Http;
use Embed\Embed;

class ApinotasController extends Controller {
    // convertir tags html a array
    function url_exists($url = NULL) {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init($url);
        // Establecer un tiempo de espera
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        // Establecer NOBODY en true para hacer una solicitud tipo HEAD
        curl_setopt($ch, CURLOPT_NOBODY, true);
        // Permitir seguir redireccionamientos
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Recibir la respuesta como string, no output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Descomentar si tu servidor requiere un user-agent, referrer u otra configuración específica
        // $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36';
        // curl_setopt($ch, CURLOPT_USERAGENT, $agent)
        
        $data = curl_exec($ch);
        //        dd($data);
        // Obtener el código de respuesta
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // dd($httpcode);
        //cerrar conexión
        curl_close($ch);
        
        // Aceptar solo respuesta 200 (Ok), 301 (redirección permanente) o 302 (redirección temporal)
        $accepted_response = array(200, 301, 302);
        if (in_array($httpcode, $accepted_response)) {
            return true;
        } else {
            return false;
        }
    }
    
    //Armamos los datos que se enviaran al API croma
    function armaDatosCroma($body) {
        
        $dataSendCroma = [];
        $body = str_replace('<![CDATA[', '', $body);
        $body = str_replace(']]>', '', $body);
        $body = str_replace('&nbsp;', '', $body);
        $a = htmlentities($body);
        $b = html_entity_decode($a);
        // echo ($a); die;
        //id de la nota
        $idArticulo = explode('<article articleid="', $b);
        $idArticulo = explode('" groupid="', $idArticulo[1]);
        $dataSendCroma['id'] =  $idArticulo[0];
        //fin id de la nota
        // inicio Title o Headline
        $title = explode('<component name="Headline">', $b);
        $title = explode('</component>', $title[1]);
        $title = $title[0];
        $dataSendCroma['title'] = $title;
        // fin title o Headline
        
        //Sumario articulo o excerpt
        $excerpt = explode('<component name="Subheadline">', $b);
        $excerpt = explode('</component>', $excerpt[1]);
        $excerpt = $excerpt[0];
        $dataSendCroma['excerpt'] = $excerpt;
        //Fin Sumario articulo o excerpt
        
        //content articulo
        $content = explode('<content>', $b);
        $content = explode('</content>', $content[1]);
        $content = $content[0];
        $content = htmlentities($content);
        $content = html_entity_decode($content);
        $dataSendCroma['content'] = $content;
        //Fin Sumario articulo
        
        //Fecha de publicacion
        $date = explode('<properties createdate="', $b);
        $date = explode('"', $date[1]);
        $date = $date[0];
        $dataSendCroma['date'] = $date;
        //Fin Fecha de publicacion
        
        //Link de la nota
        $link = explode('<link rel="self" href="', $b);
        $link = explode('"', $link[1]);
        $link = $link[0];
        $dataSendCroma['link'] = $link;
        //Fin Link de la nota
        
        //Autor de la nota
        $author = explode('<vocabulary name="Autores"', $b);
        $author = explode('</vocabulary>', $author[1]);
        $author = $author[0];
        $author = explode(' <category name="', $author);
        $author = explode('"', $author[1]);
        $dataSendCroma['author'] = $author[0];
        //Fin Autor de la nota
        
        //   dd($dataSendCroma);
        return $dataSendCroma;
    }
    // consultar url externa y capturar datos y estatus de solicitud
    public function getNotas() {
        //$urlBaseNota = "https://www.eluniversal.com.co/news-portlet/getArticle/";
        $urlBaseNota = "http://eluniversal-itfr01a.calipso.com.co/news-portlet/getArticle/"; //url de prueba
        $url = $urlBaseNota . '6571934'; //6576848//6574438//6545462//6567346
        $response = Http::get($url);
        $status = $response->status();
        $body = $response->body();
        //dd($body); //Contendio xml
        $dataSendCroma = $this->armaDatosCroma($body);
        
        //dd($dataSendCroma);
        $xml = simplexml_load_string($body);
        
        
        
        $json = json_encode($xml);
        
        $array = json_decode($json, TRUE);
        $urlNota = $array['metadata']['link']['@attributes']['href'];
        //$urlNota = $array['article']['metadata']['link']['href'];
        $urlNota = str_replace(env('NEWS_PORLET_URL'),'https://www.eluniversal.com.co/', $urlNota);
        
        //  dd($urlNota);
        $existeNota = $this->url_exists($urlNota);
        // dd($existeNota);
        if ($existeNota == true) {
            // dd($urlNota);
            $nota = $this->extraerInforUrl($urlNota);
            // dd($nota);
            $nota = array(
                'titulo' => $nota['info_title'],
                'descripcion' => $nota['description'],
                'url' => $nota['url'],
                'estatus' => $status,
            );
            // dd($nota);
        } else {
            dd('no existe nota con esa url');
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //cargar vista
        return view('apinotas.index');
    }
    
    //extraer datos de la nota
    public function extraerInforUrl($url) {
        $embed = new Embed();
        //dd($url);
        $info = $embed->get($url);
        $document = $info->getDocument();
        $document->getDocument(); //get the document instance
        $html = $this->html_to_text($document);
        // $htmls =  html_entity_decode($document);//get the html string
        $htmlNota = explode('content-news-1', (string)  $html);
        $cuerpoNota = $htmlNota[1];
        $cuerpoNota = explode('brBotonGoogleNews', (string)  $cuerpoNota);
        //print_r($cuerpoNota[0]);        die;
        //echo ($html);
        $result = $document->select('.//div');
        $id = $result->str('content-news-1');
        // dd($id);
        $info2 = (array) $info;
        
        
        $info_image = (string) $info->image;
        
        $info_image = explode('?', $info_image);
        $info_image = $info_image[0];
        $info_title = $info->title;
        $metas = $info->getMetas();
        // dd($metas);
        $description = $metas->html('description');
        $alt = $metas->html('description');
        //dd($metas->html('description'));
        
        $datosPost = [
            'info_image' => $info_image,
            'info_title' => $info_title,
            'alt' => $alt,
            'url' => $url,
            'description' => $description,
            'metas' => $metas,
        ];
        
        return $datosPost;
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
