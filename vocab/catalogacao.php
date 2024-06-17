<?php
/* TemaTres : aplicación para la gestión de lenguajes documentales #       #

 Copyright (C) 2004-2024 Diego Ferreyra tematres@r020.com.ar
 Distribuido bajo Licencia GNU Public License, versión 2 (de junio de 1.991) Free Software Foundation
*/
require "config.tematres.php";
$metadata = do_meta_tag();

//include_once('formulario/config.ws.php');
//include_once('formulario/common/vocabularyservices.php');

$URL_BASE = 'https://vocabulario.abcd.usp.br/novo/vocab/services.php';

function getURLdata($url)
{

    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    $xml = file_get_contents($url, false, stream_context_create($arrContextOptions)) or die("Could not open a feed called: " . $url);


    $content = new SimpleXMLElement($xml);

    return $content;
}

function HTMLdoSelect($URL_BASE, $term_id)
{

    $vocabData = getURLdata($URL_BASE . '?task=fetchVocabularyData');

    $term_id = (int) $term_id;

    $rows = '<div class="input-group input-group-lg">';

    $dataTerm = getURLdata($URL_BASE . '?task=fetchTerm&arg=' . $term_id);

    $rows .= '<label style="font-weight: bold;" for="tag_' . $term_id . '" title="' . (string) $vocabData->result->title . ' : ' . (string) $dataTerm->result->term->string . '">';
    $rows .= '<p><a href="' . $vocabData->result->uri . 'index.php?tema=' . $term_id . '" title="' . (string) $vocabData->result->title . ' : ' . (string) $dataTerm->result->term->string . '">' . (string) $dataTerm->result->term->string . ':</a>&nbsp;</p></label>';


    $rows .= '<select id="tag_' . $term_id . '" v-model="' . str_replace(' ', '', $dataTerm->result->term->string) . '" placeholder="Escolha um valor" class="form-select">';

    $data = getURLdata($URL_BASE . '?task=fetchDown&arg=' . $term_id);

    if ($data->resume->cant_result > 0) {
        //$rows .= '<option selected>Escolha um valor</option>';
        foreach ($data->result->term as $value) {
            $rows .= '<option value="' . $value->string . '">' . $value->string . '</option>';
        }
    }

    $rows .= '</select>';
    $rows .= '<p> </p>';
    $rows .= '</div><!-- /input-group -->	   ';

    return $rows;
}



?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">

<head>
    <?php echo HTMLheader($metadata); ?>
    <?php
    echo '<link rel="stylesheet" type="text/css" href="' . T3_WEBPATH . 'jq/chartist-js/chartist.min.css">';
    echo '<script type="text/javascript" src="' . T3_WEBPATH . 'jq/chartist-js/chartist.min.js"></script>';
    echo '<script type="text/javascript" src="' . T3_WEBPATH . 'jq/chartist-js/chartist-plugin-axistitle.min.js"></script>';
    ?>
    <style>
    #ct-deep {
        height: 300px;
        width: 100%;
    }

    #ct-lexical {
        height: 400px;
        width: 100%;

    }

    #ct-logic {
        height: 400px;
        width: 100%;
    }

    .ct-label {
        fill: rgba(0, 0, 0, .8);
        color: rgba(0, 0, 0, .8);
        font-size: 1em;
        line-height: 2;
    }
    </style>

    <!-- Script para criar o pop-up do popterms -->
    <script>
    function creaPopup(url) {
        tesauro = window.open(url,
            "Tesauro",
            "directories=no, menubar=no,status=no,toolbar=no,location=no,scrollbars=yes,fullscreen=no,height=600,width=450,left=500,top=0"
        )
    }
    </script>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>

<body>
    <?php echo HTMLnavHeader(); ?>
    <div class="container">
        <div class="container" id="bodyText">

            <div id="app">

                <h2>Construtor de termo para catalogação</h2>


                <div class="form-group">
                    <a href="#" data-popterms-server="https://vocabulario.abcd.usp.br/novo/a/popterms/"
                        data-popterms-vocabulary="VCUSP" data-popterms-target="#vcusp">Consultar o VCUSP</a>
                    <br>
                    <input class="form-control" id="vcusp" type="text" size="100" v-model="TERMO"
                        placeholder="Selecione um termo consultando o VCUSP">
                </div>

                <div class="form-group">
                    <label class="sr-only" for="qualificador">Qualificador</label>
                    <?php echo HTMLdoSelect($URL_BASE, 140064); ?>
                </div>

                <div class="form-group">
                    <label class="sr-only" for="genero">Gênero e Forma</label>
                    <?php echo HTMLdoSelect($URL_BASE, 101829); ?>
                </div>

                <div class="form-group">
                    <label class="sr-only" for="profissoes">Profissões e ocupações</label>
                    <?php echo HTMLdoSelect($URL_BASE, 136770); ?>
                </div>

                <div class="form-group">
                    <?php echo HTMLdoSelect($URL_BASE, 102243); ?>
                    <label class="sr-only" for="geografico">Geográfico</label>
                </div>

                <div class="form-group">
                    <label class="sr-only" for="dataresposta">Data</label>
                    <input type="text" class="form-control" id="dataresposta" placeholder="Data" v-model="DATA">
                </div>

                <!-- Button to trigger addition -->
                <button @click="concatAll">Gerar</button>

                <button @click="clear">Limpar</button>

                <!-- Display result -->
                <br /><br />
                <p class="mt-5">
                <h4>Resultado:</h4>
                </p>
                <p class="bg-info p-5 text-center">{{ result }}</p>
            </div>





        </div><!-- /.container -->
        <!-- ###### Footer ###### -->

        <div id="footer" class="footer">
            <div class="container">
                <div class="row">
                    <a href="https://www.vocabularyserver.com/" title="TemaTres: vocabulary server" target="_blank">
                        <img src="<?php echo T3_WEBPATH; ?>/images/tematres-logo.gif" width="42" alt="TemaTres" /></a>
                    <a href="https://www.vocabularyserver.com/" title="TemaTres: vocabulary server"
                        target="_blank">TemaTres</a>
                    <p class="navbar-text pull-left">
                        <?php
                        //are enable SPARQL
                        if (CFG_ENABLE_SPARQL == 1) {
                            echo '<a class="label label-info" href="' . URL_BASE . 'sparql.php" title="' . LABEL_SPARQLEndpoint . '">' . LABEL_SPARQLEndpoint . '</a>';
                        }

                        if (CFG_SIMPLE_WEB_SERVICE == 1) {
                            echo '  <a class="label label-info" href="' . URL_BASE . 'services.php" title="API"><span class="glyphicon glyphicon-share"></span> API</a>';
                        }

                        echo '  <a class="label label-info" href="' . URL_BASE . 'xml.php?schema=rss" title="RSS"><span class="icon icon-rss"></span> RSS</a>';
                        echo '  <a class="label label-info" href="' . URL_BASE . 'index.php?s=n" title="' . ucfirst(LABEL_showNewsTerm) . '"><span class="glyphicon glyphicon-fire"></span> ' . ucfirst(LABEL_showNewsTerm) . '</a>';
                        ?>
                    </p>
                    <?php echo doMenuLang(); ?>
                </div>
            </div>

        </div>
        <?php echo HTMLjsInclude(); ?>


        <script>
        // Vue app
        const app = Vue.createApp({
            data() {
                return {
                    TERMO: '',
                    TERMO_A: '',
                    QUALIFICADORES: '',
                    QUALIFICADORES_A: '',
                    PROFISSÕES: '',
                    PROFISSÕES_A: '',
                    GÊNEROEFORMA: '',
                    GÊNEROEFORMA_A: '',
                    GEOGRÁFICO: '',
                    GEOGRÁFICO_A: '',
                    DATA: '',
                    DATA_A: '',
                    result: ''
                };
            },
            methods: {
                concatAll() {
                    if (this.TERMO.trim() !== "") {
                        this.TERMO_A = '$a' + this.TERMO;
                    }
                    if (this.QUALIFICADORES.trim() !== "") {
                        this.QUALIFICADORES_A = '$j' + this.QUALIFICADORES;
                    }
                    if (this.PROFISSÕES.trim() !== "") {
                        this.PROFISSÕES_A = '$x' + this.PROFISSÕES;
                    }
                    if (this.GÊNEROEFORMA.trim() !== "") {
                        this.GÊNEROEFORMA_A = '$v' + this.GÊNEROEFORMA;
                    }
                    if (this.GEOGRÁFICO.trim() !== "") {
                        this.GEOGRÁFICO_A = '$z' + this.GEOGRÁFICO;
                    }
                    if (this.DATA.trim() !== "") {
                        this.DATA_A = '$d' + this.DATA;
                    }
                    // Convert values to numbers and add them
                    this.result = this.TERMO_A.trim() + this.QUALIFICADORES_A.trim() + this.PROFISSÕES_A
                        .trim() +
                        this.GÊNEROEFORMA_A.trim() + this.GEOGRÁFICO_A.trim() + this.DATA_A.trim() +
                        '$2larpcal';
                },
                clear() {
                    this.TERMO = '';
                    this.TERMO_A = '';
                    this.QUALIFICADORES = '';
                    this.QUALIFICADORES_A = '';
                    this.PROFISSÕES = '';
                    this.PROFISSÕES_A = '';
                    this.GÊNEROEFORMA = '';
                    this.GÊNEROEFORMA_A = '';
                    this.GEOGRÁFICO = '';
                    this.GEOGRÁFICO_A = '';
                    this.DATA = '';
                    this.DATA_A = '';
                    this.result = '';
                }
            }
        });

        // Mount the app to the #app div
        app.mount('#app');
        </script>

        <!-- jQuery -->
        <script src="js/jquery.js"></script>

        <!-- Bootstrap Core JavaScript -->
        <!-- Latest compiled and minified JavaScript -->
        <script src="js/bootstrap.min.js"></script>

        <!-- Scrolling Nav JavaScript -->
        <script src="js/jquery.easing.min.js"></script>
        <script src="js/scrolling-nav.js"></script>
        <script src="js/popterms.js"></script>
</body>

</html>