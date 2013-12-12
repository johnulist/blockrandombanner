<?php
/*Definimos el prefijo para la BD*/
if (!defined('_PS_VERSION_'))
    exit;
/*Creamos nuestra clase*/
class BlockRandomBanner extends Module {
    /* Titulo de la imagen */
    public $banner_titulo;
    /* Link de la imagen */
    public $banner_link;
    /* Nombre de la imagen sin extension */
    public $banner_nombreimg;
    /* Nombre imagen con extension */
    public $banner_img;
    /* Variable para debuggear*/
	public $debug;

    /*Creamos nuestro constructor con las variables que necesitamos*/
    public function __construct() {
        $this->name = 'blockrandombanner';
        $this->tab = 'random_banner';
        $this->version = '1';
        $this->author = 'Marc Bernabeu, Mauro Sempere, Ximo Peidro';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Block Random Banner');
        $this->description = $this->l('Añade un bloque de publicidad en tu web que cambiara aleatoriamente.');
    }

    /*Metodo el cual es llamado al instalar*/
    public function install() {
        $this->installDb();

        /*Esto coloca nuestro modulo en la columna izquierda*/
        return (parent::install() && $this->registerHook('leftColumn'));
    }

    /*Método el cual crea la BD*/
    public function installDb() {
        /*Llama al método general de la BD para ejecutar la SQL*/
        Db::getInstance()->Execute('
    CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'randombanner` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
      `link` VARCHAR( 255 ) NOT NULL,
      `title` VARCHAR( 128 ) NOT NULL,
      `img` VARCHAR( 128 ) NOT NULL
    ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;');
        return true;
    }

    /*Método que desisntala nuestro modulo, destrullendo la BD*/
    public function uninstall() {
        Db::getInstance()->Execute('DROP TABLE ' . _DB_PREFIX_ . 'randombanner');
        return (parent::uninstall());
    }

    /*Método inserta en la BD, el link, titulo, img*/
    public function insertarBanner($link, $title, $img) {
        Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'randombanner`(`link`, `title`, `img`) VALUES ("'
         . $link . '","' . $title . '","' . $img . '");');
        $db=Db::getInstance();
		return Db::getInstance()->Insert_ID();
    }
     /*Método borra de la BD, el link, titulo, img, del id seleccionado*/
    public function borrarBanner($id) {
        Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'ps_randombanner` WHERE id LIKE ' . $id . ';');
    }

    /*Método que obtine de la BD el banner que tenga el id seleccionado, en caso de no tener id, los cogeria todos.*/
    public function obtenerBanner($id = 0) {
        if (!isset($id) || $id == 0) {
            $result = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'randombanner`;');
        } else {
            $result = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'randombanner` WHERE id LIKE '
             . $id . ';');
        }
		$this->debug=$result;
        return $result;
    }

    /*Obtiene un banner aleatorio y lo guarda en $this*/
    public function obtenerRandomBanner(){
    	$array=$this->obtenerBanner();
    	$len = count($array)-1;
    	$rand = rand(0,$len);
    	$array[$rand];
    	$this->banner_img = Tools::getMediaServer($this->name) . _MODULE_DIR_ . $this->name . '/banners/' 
        . $array[$rand]["id"] . '.' . $array[$rand]["img"];
        $this->banner_link = $array[$rand]["link"];
        $this->banner_titulo = $array[$rand]["title"];
    }

    /*En caso de pulsar el boton de submit guarda la imagen; y si es pulsado el de borrar lo borra*/
    public function postProcess() {
        if (Tools::getValue('deletehidden')!=null){
            $this->borrarBanner(Tools::getValue('deletehidden'));
		}
        $errors = '';
        if (Tools::isSubmit('submitAdvConf')) {
            if (isset($_FILES['banner_img']) && isset($_FILES['banner_img']['tmp_name']) &&
             !empty($_FILES['banner_img']['tmp_name'])) {
                if ($error = ImageManager::validateUpload($_FILES['banner_img'], Tools::convertBytes(
                    ini_get('upload_max_filesize'))))
                    $errors .= $error;
                else {
                    $this->banner_nombreimg =  $this->insertarBanner(Tools::getValue('banner_link'), 
                        Tools::getValue('banner_titulo'), substr($_FILES['banner_img']['name'], 
                        strrpos($_FILES['banner_img']['name'], '.') + 1));

                    if (!move_uploaded_file($_FILES['banner_img']['tmp_name'], _PS_MODULE_DIR_ . $this->name 
                        . '/banners/' . $this->banner_nombreimg . '.' . Configuration::get('BLOCKADVERT_IMG_EXT')))
                        $errors .= $this->l('File upload error.');
                }
            }
        }
        if ($errors)
            echo $this->displayError($errors);
    }

    /**
     * getContent usado para mostrar la pantalla del admin del modulo
     * @return string content
     */
    public function getContent() {
        $this->postProcess();
        $output = '
		<form action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) 
        . '" method="post" id="formdelete" enctype="multipart/form-data">
		<input type="hidden" name="deletehidden" id="deletehidden" value="" />
			<fieldset>
				<legend>'.$this->l('Configuracion Random Banner').'</legend>';
				$array = $this->obtenerBanner();
				$rutajs=$this->context->link->protocol_content.Tools::getMediaServer($this->name)
                ._MODULE_DIR_.$this->name;
				$ruta_img=$rutajs.'/lib/timthumb.php?src='.$rutajs.'/banners/';
				
				$tabla='<div style="width: 940px;margin: 0 auto;">';
				foreach ($array as $clave => $valor) {
				
				$link=$valor['link'];
					$ruta=$ruta_img.$valor['id'].'.'.$valor['img'].'&w=100&h=100';
						$tabla.="
							<img id=".$valor['id']." src='$ruta' onclick='borrar(this.id)'>
						";
				}

				$tabla.='</div>';
				$output .= '
				<label for="banner_img">' . $this->l('Change image') . '&nbsp;&nbsp;</label>
				<div class="margin-form">
					<input id="banner_img" type="file" name="banner_img" />
					<p>' . $this->l('Image will be displayed as 155x163') . '</p>
				</div>
				<br class="clear"/>
				<label for="banner_link">' . $this->l('Image link') . '</label>
				<div class="margin-form">
					<input id="banner_link" type="text" name="banner_link" value="' . $this->banner_link 
                    . '" style="width:250px" />
				</div>
				<br class="clear"/>
				<label for="banner_titulo">' . $this->l('Title') . '</label>
				<div class="margin-form">
					<input id="banner_titulo" type="text" name="banner_titulo" value="' . $this->banner_titulo 
                    . '" style="width:250px" />
				</div>
				<br class="clear"/>
				<div class="margin-form">
					<input class="button" type="submit" name="submitAdvConf" value="' . $this->l('Validate') . '"/>
				</div>
				<br class="clear"/>
				'.$tabla.'
			</fieldset>
		</form>
		<script src="'.$rutajs.'/js/script.js"></script>';
        return $output;
    }

    // HOOK BANNER
    /*Hook, lugares donde se puede colocar el modulo, en la vista de la tienda*/
    public function hookRightColumn($params) {
	$this->obtenerRandomBanner();
        $this->smarty->assign(array(
            'image' => $this->context->link->protocol_content . $this->banner_img,
            'banner_link' => $this->banner_link,
            'banner_titulo' => $this->banner_titulo,
        ));

        return $this->display(__FILE__, 'blockrandombanner.tpl');
    }

    public function hookLeftColumn($params) {
        return $this->hookRightColumn($params);
    }

}
