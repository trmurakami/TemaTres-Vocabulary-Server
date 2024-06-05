<?php
if (!defined('WEBTHES_ABSPATH')) die("no access");
/*
 *      vocabularyservices.php
 *      
 *      Copyright 2014 diego ferreyra <tematres@r020.com.ar>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */


/*
Funciones de consulta de datos
*/

/*
Hacer una consulta y devolver un array
* $uri = url de servicios tematres
* +    & task = consulta a realizar
* +    & arg = argumentos de la consulta
*/
function getURLdata($url) {
	global $CURL_PROXY, $CURL_PROXY_PORT;

	if (extension_loaded('curl')) {
		$rCURL = curl_init();
		curl_setopt($rCURL, CURLOPT_URL, $url);
		if(isset($CURL_PROXY)) {
			curl_setopt($rCURL, CURLOPT_PROXY, $CURL_PROXY);
		}
		if(isset($CURL_PROXYPORT)) {
			curl_setopt($rCURL, CURLOPT_PROXYPORT, $CURL_PROXY_PORT);
		}
		curl_setopt($rCURL, CURLOPT_HEADER, 0);
		curl_setopt($rCURL, CURLOPT_RETURNTRANSFER, 1);
		$xml = curl_exec($rCURL) or die ("Could not open a feed called: " . $url);
		curl_close($rCURL);

	} else {
		$xml = file_get_contents($url) or die ("Could not open a feed called: " . $url);
	}
	$content = new SimpleXMLElement($xml);
	return $content;
}


/*
 * 
 * Funciones de presentación de datos 
 * 
 */

/*  Recibe un objeto con las notas y lo publica como HTML  */
function data2html4Notes($data,$param=array())
{
    GLOBAL $CFG;
    $rows = '';
    if ($data->resume->cant_result > 0) {
        $i = 0;
        $rows.='<div class="well well-small" id="notabnm">';
        $i=0;
            foreach ($data->result->term as $value) :
                $i=++$i;

                    $note_type=(string) $value->note_type;
                    //note_label is one of the standard type of note
                    $note_label=(in_array($note_type,array("NA","NH","NB","NP","NC","CB"))) ? str_replace(array("NA","NH","NB","NP","NC"),array(LABEL_NA,LABEL_NH,LABEL_NB,LABEL_NC),$note_type) : $note_type;

                    //note_label is custom type of note
                    $note_label=(isset($CFG["LOCAL_NOTES"]["$note_type"])) ? $CFG["LOCAL_NOTES"]["$note_type"] : $note_type;
                    $rows.='<div rel="skos:scopeNote">';
                    $rows.='<span class="note_label">'.$note_label.':</span>';
                    $rows.='<p class="note">'.(string) $value->note_text.'</p>';
                    $rows.='</div>';
            endforeach;
        $rows.='</div>';
    }
    return $rows;
};

;


/*  data to letter html  */
function data2html4Letter($data,$param=array())
{
    GLOBAL $URL_BASE;
    $vocab_code=loadVocabularyID(@$param["vocab_code"]);

    $rows.='<div id="term_breadcrumb">';                    
    $rows.='<span typeof="v:Breadcrumb">';
    $rows.='<a rel="v:url" property="v:title" href="'.$CFG_URL_PARAM["url_site"].'index.php?v='.$vocab_code.'" title="'.MENU_Inicio.'">'.MENU_Inicio.'</a>';
    $rows.='</span>  ';
	$rows.='› <span typeof="v:Breadcrumb">'.$param["div_title"].'  <i>'.$data->resume->param->arg.'</i>: '.$data->resume->cant_result.'</span>  ';
    $rows.='</div><hr>  ';            
    if($data->resume->cant_result > 0) {
        $rows.='<ul>';
        foreach ($data->result->term as $value) :
            $i=++$i;
            //Controlar que no sea un resultado unico
            $rows.='<li><span about="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" typeof="skos:Concept">';
            $rows.=(strlen($value->no_term_string)>0) ? $value->no_term_string." <i>".USE_termino."</i> " : "";
            $rows.='<a resource="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" property="skos:prefLabel" href="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" title="'.FixEncoding($value->string).'">'.FixEncoding($value->string).'</a></span></li>';
        endforeach;
        $rows.='</ul>';
    }
    return array("task"=>"letter","results"=>$rows);
}


/*
data to last terms created
*/
function data2html4LastTerms($data, $param = array())
{

	GLOBAL $URL_BASE;


	$rows .= '<h3>' . $param["div_title"] . '</h3>';

	$i = 0;
	if ($data->resume->cant_result > 0) {
		$rows .= '<ul>';
		foreach ($data->result->term as $value) :
			$i = ++$i;

			$term_date = do_date(($value->date_mod > $value->date_create) ? $value->date_mod : $value->date_create);

			$rows .= '<li><span about="' . $URL_BASE . '?task=fetch tTerm&amp;arg=' . $value->term_id . '" typeof="skos:Concept">';
			$rows .= (strlen($value->no_term_string) > 0) ? $value->no_term_string . " " . USE_termino . " " : "";
			$rows .= '<a resource="' . $URL_BASE . '?task=fetchtTerm&amp;arg=' . $value->term_id . '" property="skos:prefLabel" href="' . $PHP_SELF . '?task=fetchTerm&amp;arg=' . $value->term_id . '" title="' . FixEncoding($value->string) . '">' . FixEncoding($value->string) . '</a>';
			$rows .= '  (' . $term_date["ano"] . '/' . $term_date["mes"] . '/' . $term_date["dia"] . ')</span>';
			$rows .= '</li>';
		endforeach;
		$rows .= '</ul>';
	}


	return array("task" => "fetchLast", "results" => $rows);
}


/*  Recibe un objeto con resultados de búsqueda y lo publica como HTML  */
function data2html4Search($data,$string,$param=array())
{
    GLOBAL $message, $URL_BASE;

    $vocab_code=loadVocabularyID(@$param["vocab_code"]);
    $rows=' <div>
                <h3 id="msg_search_result">
                    '.ucfirst(MSG_ResultBusca).' <i>'.(string) $data->resume->param->arg.'</i>: '.(string) $data->resume->cant_result.'
                </h3>
            </div>
            <ul id="list_search_result">';
    $i = 0;
    if ($data->resume->cant_result > 0) {
        foreach ($data->result->term as $value) :
            $i=++$i;
            $term_id        = (int) $value->term_id;
            $term_string    = (string) $value->string;
            $no_term_string = '';
            $no_term_string = (string) $value->no_term_string;
            $rows.='    <li>
                            <span about="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" typeof="skos:Concept" >';
            if ($no_term_string != '')
                $rows.=         $no_term_string.' <strong>use</strong> ';
            $rows.='            <a resource="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" property="skos:prefLabel" href="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'"  title="'.$term_string.'">
                                    '.$term_string.'
                                </a>
                            </span>
                        </li>';
        endforeach;
        $rows.='</ul>';
    } else {
        //No hay resultados, buscar términos similares

        $data=getURLdata($URL_BASE.'?task=fetchSimilar&arg='.urlencode((string) $data->resume->param->arg));
        if($data->resume->cant_result > 0) {
            $rows.='<h4>'.ucfirst(LABEL_TERMINO_SUGERIDO).' <a href="'.redactHREF($vocab_code,"search",(string) $data->result->string).'" title="'.(string) $data->result->string.'">'.(string) $data->result->string.'</a>?</h4>';
        }
    }
    return $rows;
}


/*HTML details for one term*/
function data2htmlTerm($data, $param = array()){
    GLOBAL $URL_BASE, $CFG_URL_PARAM, $CFG_VOCABS ;

    $vocab_code = loadVocabularyID(@$param["vocab_code"]);
    $date_term  = ($data->result->term->date_mod) ? $data->result->term->date_mod : $data->result->term->date_create;
    $date_term  = date_create($date_term);
    $term_id    = (int) $data->result->term->tema_id;
    $term       = (string) $data->result->term->string;
    $class_term = ($data->result->term->isMetaTerm == 1) ? ' class="metaTerm" ' :'';
	

	$arrayRows["termdata"] .= '<span ' . $class_term . ' id="term_prefLabel" property="skos:prefLabel" content="' . FixEncoding($term) . '">' . FixEncoding($term) . '</span>';
	$arrayRows["termdata"] .= HTMLcopyTerm($data->result->term);

	/*notes*/
	$dataNotes = getURLdata($URL_BASE . '?task=fetchNotes&arg=' . $term_id);
	$arrayRows["NOTES"] = data2html4Notes($dataNotes);

	/*broader*/
	$dataTG = getURLdata($URL_BASE . '?task=fetchUp&arg=' . $term_id);
	if ($dataTG->resume->cant_result > 0) {
		$arrayTG["term"]["string"] = $term;
		$arrayRows["breadcrumb"] = data2html4Breadcrumb($dataTG, $term_id, array("vocab_code" => $vocab_code));
	}

	/*Narrower*/
	$dataTE = getURLdata($URL_BASE . '?task=fetchDown&arg=' . $term_id);
	if ($dataTE->resume->cant_result > 0) {
		$arrayRows["NT"]='<div><span class="label_list">'.ucfirst(TE_terminos).':</span>';				
		$arrayRows["NT"].='<div id="treeTerm" data-url="'.$CFG_URL_PARAM["url_site"].'common/treedata.php?node='.$term_id.'&amp;v='.$vocab_code.'"></div></div>';		
		}

	//Fetch data about associated terms (BT,RT,UF)
	$dataDirectTerms = getURLdata($URL_BASE . '?task=fetchDirectTerms&arg=' . $term_id);
	$array2HTMLdirectTerms = data2html4directTerms($dataDirectTerms, array("vocab_code" => $vocab_code));

	if ($array2HTMLdirectTerms["UFcant"] > 0) {
        $arrayRows["UF"]='<div id="alt_terms" class="term_relations"><span class="label_list">'.ucfirst(UP_terminos).':</span><ul class="uf_terms">'.$array2HTMLdirectTerms["UF"].'</ul></div>';
	}

	if ($array2HTMLdirectTerms["RTcant"] > 0) {
        $arrayRows["RT"]='<div id="related_terms" class="term_relations"><span class="label_list">'.ucfirst(TR_terminos).':</span><ul class="rt_terms">'.$array2HTMLdirectTerms["RT"].'</ul></div>';
	}

    if ($array2HTMLdirectTerms["BTcant"] > 0) {
        $arrayRows["BT"]='<div id="broader_terms" class="term_relations"><span class="label_list">'.ucfirst(TG_terminos).':</span><ul class="bt_terms">'.$array2HTMLdirectTerms["BT"].'</ul></div>';        
    }


	return array("task" => "fetchTerm", "results" => $arrayRows);
}




/*HTML details for direct terms*/
function data2html4directTerms($data, $param = array()){
	GLOBAL $URL_BASE,$CFG,$CFG_URL_PARAM;

    $vocab_code=loadVocabularyID(@$param["vocab_code"]);
	
    $i = $iRT = $iBT = $iUF = 0;
    $RT_rows = $BT_rows = $UF_rows = '';

	if ($data->resume->cant_result > "0") {
		foreach ($data->result->term as $value) :
			$i = ++$i;
			$term_id = (int)$value->term_id;
			$term_string = (string)$value->string;

			$class_dd=($value->isMetaTerm==1) ? 'metaTerm ' :'';

			switch ((int)$value->relation_type_id) {
				case '2':
                    $RT_rows.='<li class="rt_term post-tags '.$class_dd.'" id="rt'.$value->term_id.'" about="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" typeof="skos:Concept">';
                    $RT_rows.=($value->code) ? '<span property="skos:notation">'.$value->code.'</span>' :'';
                    $RT_rows.=' <a rel="tag" href="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" title="'.$term_string.'">'.$term_string.'</a>'.HTMLcopyTerm($value, $param = array()).'</li>';				
					break;

				case '3':
					$iBT = ++$iBT;
                    $BT_rows.=' <li class="'.$class_dd.' bt_term post-tags" id="bt'.$value->term_id.'" about="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" typeof="skos:Concept"><a rel="tag" href="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" title="'.$term_string.'">'.$term_string.'</a>'.HTMLcopyTerm($value, $param = array()).'</li>';
					break;

				case '4':
                    if ($value->relation_code !='H') {
                        $iUF=++$iUF;
                        $UF_rows.=' <li class="uf_term alt-tags" typeof="skos:altLabel" property="skos:altLabel" content="'.$term_string.'" xml:lang="'.(string) $value->lang.'">'.$term_string.'</li>';
                    }
					break;
			}
		endforeach;
	}

	return array("RT" => $RT_rows,
		"BT" => $BT_rows,
		"UF" => $UF_rows,
		"RTcant" => $iRT,
		"BTcant" => $iBT,
		"UFcant" => $iUF);
}

function data2html4Breadcrumb($data,$the_term=array(),$param=array()){

    GLOBAL $URL_BASE, $CFG_URL_PARAM;

    $vocab_code=loadVocabularyID(@$param["vocab_code"]);
    
    if ($data->resume->cant_result > 0){

        $rows.='<div id="term_breadcrumb">';                    
        $rows.='<span typeof="v:Breadcrumb">';
        $rows.='<a rel="v:url" property="v:title" href="'.$CFG_URL_PARAM["url_site"].'index.php?v='.$vocab_code.'" title="'.MENU_Inicio.'">'.MENU_Inicio.'</a>';
        $rows.='</span>  ';

        $i=0;

        foreach ($data->result->term as $value):
            $i=++$i;
            if((int) $value->term_id!==$the_term["term_id"])
            {
                $rows.='› <span typeof="v:Breadcrumb">';
                $rows.='<a rel="v:url" property="v:title" href="'.redactHREF($vocab_code,"fetchTerm",$value->term_id).'" title="'.(string) $value->string.'">'.(string) $value->string.'</a>';
                $rows.='</span>  ';
            } else {                
                $rows.='› <span typeof="v:Breadcrumb">';
                $rows.=(string) $value->string;
                $rows.='</span>  ';
            }
        endforeach;

        $rows.='</div>';        
    }        else        {
        //there are only one result

        $rows.='<div id="term_breadcrumb">';                    
        $rows.='<span typeof="v:Breadcrumb">';
        $rows.='<a rel="v:url" property="v:title" href="'.$CFG_URL_PARAM["url_site"].'index.php?v='.$vocab_code.'" title="'.MENU_Inicio.'">'.MENU_Inicio.'</a>';
        $rows.='</span>  ';

        $rows.='› <span typeof="v:Breadcrumb">';
        $rows.=(string) $the_term["term"];
        $rows.='</span>  ';

        $rows.='</div>';
        }

return $rows;
}


//lista alfabética
function HTMLalphaNav($arrayLetras=array(),$param=array(),$select_letra=""){
    GLOBAL $URL_BASE;

    $vocab_code=loadVocabularyID(@$param["vocab_code"]);
    $rows='    <ul class="nav nav-alpha nav-pills">';
    foreach ($arrayLetras as $letra) :
        $class=($select_letra==$letra) ? 'active' : '';
        $rows.='    <li class="'.$class.'">
                        <a href="'.redactHREF($vocab_code,"letter",strtoupper($letra)).'">
                            '.strtoupper($letra).'
                        </a>
                    </li>';
    endforeach;
    $rows.='    </ul>';
    return $rows;
}


//div to copy term
function HTMLcopyTerm($term, $param = array()) {
	global $CFG;
	$_PARAMS = $_SESSION['_PARAMS'];

	if (count($_PARAMS) < 2) return;
	if ($term->isMetaTerm == 1) return;

	switch($CFG["WRITES_BACK"]) {
		case "code" :
			$string = htmlspecialchars($term->code);
			break;
		case "code+label":
			$string = htmlspecialchars($term->code." ".$term->string);
			break;
		case "label":
		default:
			$string = htmlspecialchars($term->string);
			break;
	}
	$insert = ' onClick="return PopTermsWrite(\'' . $string . '\',\'' . $_PARAMS["target_x"] . '\')" ';

	$rows = '  <button type="button" class="btn btn-default btn-xs" ' . $insert . '  data-toggle="tooltip" data-placement="top" title="Select this term">';
	$rows .= '<span class="glyphicon glyphicon-save" aria-hidden="true"></span>';
	$rows .= '</button>';

	return $rows;
}



/*
 * fetch vocabulary metadata
 */
function fetchVocabularyMetadata($url)
{
	$data = getURLdata($url . '?task=fetchVocabularyData');


	if (is_object($data)) {
		$array["title"] = (string)$data->result->title;
		$array["author"] = (string)$data->result->author;
		$array["lang"] = (string)$data->result->lang;
		$array["scope"] = (string)$data->result->scope;
		$array["keywords"] = (string)$data->result->keywords;
		$array["lastMod"] = (string)$data->result->lastMod;
		$array["uri"] = (string)$data->result->uri;
		$array["contributor"] = (string)$data->result->contributor;
		$array["publisher"] = (string)$data->result->publisher;
		$array["rights"] = (string)$data->result->rights;
		$array["createDate"] = $array["cuando"];
		$array["cant_terms"] = (int)$data->result->cant_terms;
	} else {
		$array = array();
	}
	return $array;
}


/*
 * Funciones generales 
 */

// string 2 URL legible
// based on source from http://code.google.com/p/pan-fr/
function string2url($string){
	$string = strtr($string,
		"�������������������������������������������������������",
		"AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuYYyyNn");

	$string = str_replace('�', 'AE', $string);
	$string = str_replace('�', 'ae', $string);
	$string = str_replace('�', 'OE', $string);
	$string = str_replace('�', 'oe', $string);

	$string = preg_replace('/[^a-z0-9_\s\'\:\/\[\]-]/', '', strtolower($string));

	$string = preg_replace('/[\s\'\:\/\[\]-]+/', ' ', trim($string));

	$res = str_replace(' ', '-', $string);

	return $res;
}


//form http://www.compuglobalhipermega.net/php/php-url-semantica/	
function is_utf($t){
	if (@preg_match('/.+/u', $t))
		return 1;
}


/* Banco de vocabularios 2013 */
// XML Entity Mandatory Escape Characters or CDATA
function xmlentities($string, $pcdata = FALSE){
	if ($pcdata == TRUE) {
		return '<![CDATA[ ' . str_replace(array('[[', ']]'), array('', ''), $string) . ' ]]>';
	} else {
		return str_replace(array('&', '"', "'", '<', '>', '[[', ']]'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '', ''), $string);
	}

}


function fixEncoding($input, $output_encoding = "UTF-8"){
	return $input;
	// For some reason this is missing in the php4 in NMT
	$encoding = mb_detect_encoding($input);
	switch ($encoding) {
		case 'ASCII':
		case $output_encoding:
			return $input;
		case '':
			return mb_convert_encoding($input, $output_encoding);
		default:
			return mb_convert_encoding($input, $output_encoding, $encoding);
	}
}


/**
 * Checks to see if a string is utf8 encoded.
 *
 * NOTE: This function checks for 5-Byte sequences, UTF8
 *       has Bytes Sequences with a maximum length of 4.
 *
 * @author bmorel at ssi dot fr (modified)
 * @since 1.2.1
 *
 * @param string $str The string to be checked
 * @return bool True if $str fits a UTF-8 model, false otherwise.
 * From WordPress
 */
function seems_utf8($str)
{
	$length = strlen($str);
	for ($i = 0; $i < $length; $i++) {
		$c = ord($str[$i]);
		if ($c < 0x80) $n = 0; # 0bbbbbbb
		elseif (($c & 0xE0) == 0xC0) $n = 1; # 110bbbbb
		elseif (($c & 0xF0) == 0xE0) $n = 2; # 1110bbbb
		elseif (($c & 0xF8) == 0xF0) $n = 3; # 11110bbb
		elseif (($c & 0xFC) == 0xF8) $n = 4; # 111110bb
		elseif (($c & 0xFE) == 0xFC) $n = 5; # 1111110b
		else return false; # Does not match any model
		for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
			if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
				return false;
		}
	}
	return true;
}


/*
convierte una cadena a latin1
* http://gmt-4.blogspot.com/2008/04/conversion-de-unicode-y-latin1-en-php-5.html
*/
function latin1($txt)
{
	$encoding = mb_detect_encoding($txt, 'ASCII,UTF-8,ISO-8859-1');
	if ($encoding == "UTF-8") {
		$txt = utf8_decode($txt);
	}
	return $txt;
}

/*
convierte una cadena a utf8
* http://gmt-4.blogspot.com/2008/04/conversion-de-unicode-y-latin1-en-php-5.html
*/
function utf8($txt)
{
	$encoding = mb_detect_encoding($txt, 'ASCII,UTF-8,ISO-8859-1');
	if ($encoding == "ISO-8859-1") {
		$txt = utf8_encode($txt);
	}
	return $txt;
}


function clean($val)
{
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	// this prevents some character re-spacing such as <java\0script>
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters
	// this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		// &#x0040 @ search for the hex values
		$val = preg_replace('/(&#[x|X]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
		// &#00064 @ 0{0,7} matches '0' zero to seven times
		$val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
	}

	// now the only remaining whitespace attacks are \t, \n, and \r
	$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
					$pattern .= '|(&#0{0,8}([9][10][13]);?)?';
					$pattern .= ')?';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
	return $val;
}

function XSSprevent($string)
{
	require_once 'htmlpurifier/HTMLPurifier.auto.php';

	$config = HTMLPurifier_Config::createDefault();
	$purifier = new HTMLPurifier($config);
	$clean_string = $purifier->purify($string);

	return $clean_string;
}

#
# Arma un array con una fecha
#
function do_date($time){
	$array = array(
		min => date("i", strtotime($time)),
		hora => date("G", strtotime($time)),
		dia => date("d", strtotime($time)),
		mes => date("m", strtotime($time)),
		ano => date("Y", strtotime($time))
	);
	return $array;
}


function loadVocabularyID($ALIAS){
	GLOBAL $CFG_VOCABS;

	foreach ($CFG_VOCABS as $k => $v) :
		if ($ALIAS == $v["ALIAS"]) return $k;
	endforeach;
	//return default source
	return 1;
}


function redactHREF($v,$task,$arg,$extra=array()){

    GLOBAL $CFG_VOCABS,$CFG,$CFG_URL_PARAM;

    $v=(is_array($CFG_VOCABS[$v])) ? $v : $CFG["DEFVOCAB"];

    $task=(in_array($task,array('fetchTerm','search','letter','last'))) ? $task : 'last' ;

    return $CFG_URL_PARAM["url_site"].$CFG_URL_PARAM["v"].$v.$CFG_URL_PARAM[$task].$arg;
}


/*Check for values and not null in a variable*/
/*Params:
 * - selected value
 * - default value
 * - available value
 * */
function configValue($value,$default=false,$defaultValues=array()){

    if(strlen($value)<1) return $default;

    //si es que ser uno de una lista de valores
    if(count($defaultValues)>0){
        if(!in_array($value,$defaultValues)) return $default;        
    }

    return $value;

}

/* Retorna los datos, acorde al formato de autocompleter */
function getData4Autocompleter($URL_BASE,$searchq){

        $data=getURLdata($URL_BASE.'?task=suggestDetails&arg='.$searchq);       
        $arrayResponse=array("query"=>$searchq,
                             "suggestions"=>array(),
                             "data"=>array());
        if($data->resume->cant_result > 0)  {   
            foreach ($data->result->term as $value) :
                $i=++$i;
                array_push($arrayResponse["suggestions"], (string) $value->string);
                array_push($arrayResponse["data"], (int) $value->term_id);
            endforeach;
        }                   
        return json_encode($arrayResponse);
    };


/* Retorna los datos, acorde al formato de autocompleter UI*/
function getData4AutocompleterUI($URL_BASE,$searchq){

        $data=getURLdata($URL_BASE.'?task=suggestDetails&arg='.$searchq);       
        $arrayResponse=array();
        if($data->resume->cant_result > 0)  {   
            foreach ($data->result->term as $value) :
                $i=++$i;
                array_push($arrayResponse, (string) $value->string);
            endforeach;
        }                   
        return json_encode($arrayResponse);
};



function retriveInternalReferer($URL_SERVER,$params=array()){	
		$task=(@$params["task"]) ? $params["task"] : '';
		$arg=(@$params["arg"]) ? $params["arg"] : '';
		
		if(!in_array(array("letter","fefetchTerm"),$arg)) return $URL_SERVER.'?';
		
		$add_param='?task='.$task.'&arg='.$arg.'&';

	return $URL_SERVER.$add_param;
}
?>
