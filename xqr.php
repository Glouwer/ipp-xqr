<?php
#script written in utf-8
#XQR:xjurik08
class Pomocna {
    var $input;
    var $output;
    var $query;
    var $qf;
    var $root;
    var $help;

    var $q_select;
    var $q_from;
    var $q_where;
    var $q_limit;
    var $q_from_el;         //element z from
    var $q_from_atr;        //atribut z from
    var $q_where_left;      //cela podminka WHERE

    var $q_where_left_el;
    var $q_where_left_atr;
    var $param_root;


    var $n;

    var $no_from; // FROM nebyl zadan



    function help(){ 
		echo  "Skript provadi vyhodnoceni zadaneho dotazu, jenz je podobny prikazu SELECT\n"       ;
		echo  "jazyka SQL, nad vstupem ve formatu XML. Vystupem je XML obsahujici elementy\n"      ;
		echo  "splnujici pozadavky dane dotazem. Dotazovaci jazyk ma zjednodusene podminky\n"      ;
		echo  "a syntaxi.\n"                                                                       ;
		echo  "Tento skript bude pracovat s temito parametry:\n"                                   ;
		echo  "       --help                 - vytiskne tuto napovedu\n"                           ;
		echo  "       --input=filename       - zadany vstupni soubor ve formatu XML\n"             ;
		echo  "       --output=filename      - zadany vystupni soubor ve formatu XML\n"            ;
		echo  "                                s obsahem podle zadaneho dotazu\n"                  ;
		echo  "       --query=’dotaz’        - zadany dotaz v dotazovacim jazyce definovanem\n"    ;
		echo  "                                nize (v pripade zadani timto zpusobem nebude\n"     ;
		echo  "                                dotaz obsahovat symbol apostrof)\n"                 ;
		echo  "       --qf=filename          - dotaz v dotazovacim jazyce definovanem nize\n"      ;
		echo  "                                zadany v externim textovem souboru\n"               ;
		echo  "                                (nelze kombinovat s --query)\n"                     ;
		echo  "       -n                     - negenerovat XML hlavicku na vystup skriptu\n"       ;
		echo  "       --root=element         - jmeno paroveho korenoveho elementu obalujici\n"     ;
		echo  "                                vysledky. Pokud nebude zadan, tak se vysledky\n"    ;
		echo  "                                neobaluji korenovym elementem, ac to porusuje\n"    ;
		echo  "                                validitu XML.\n"   ;
		exit(0);
    }
   
    
    //funkce pro overeni zda s --help nebyl zadan jiny parametr
    function validate_help(){    
		if($this->help){
			if(isset($this->input)|| isset($this->output) || isset($this->param_root) || isset($this->query) || isset($this->qf) || $this->n){
				return false;
			}
			else{
				$this->help();
			}
		}
		return true;    
    }


    //funkce pro overeni zadanych parametru
    function validate(){
		if(!$this->validate_help()){
			fwrite(STDERR, "error - nelze kombinovat --help\n");
			//chyby
			exit(1);
		}
		if(isset($this->query) && isset($this->qf)){
			fwrite(STDERR, "error - nelze kombinovat query a qf\n");
			//nelze kombinovat
			exit(1);
		}

		if(!isset($this->query) && !isset($this->qf)){
			fwrite(STDERR, "error - chybi dotaz uplne\n");
			exit(1);
			//chybi dotaz uplne
		}



		if(isset($this->qf)){
			$file=file_get_contents($this->qf);
			$this->query = $file;
		}
		if(empty($this->input)){
			$stdin = fopen("php://stdin","r") or exit(2);
			$in = "";
			while(!feof($stdin)){
				$in .=fgetc($stdin);
			}
			fclose($stdin);
			$xml = simplexml_load_string($in); 
		}
	}
}

$q_where_left="";
$q_from="";


if(($argc > 1)) {
	$P = new Pomocna; 

	/*
	* ********************zpravocani ostatnich parametru***********************
	* - Vyuzivam funkci foreach abych si rozdelil cely string do pole 
	*   a pote pomoci funkce strncmp kontroluju zda se v nem vyskytuje muj parametr
	* - pokud ano tak pres funkci substr pak oddelim podle poctu znaku
	*/


	foreach ($argv as $part) {
		if(!strcmp($argv[0], $part)) {
			continue;
		}
		else if(!strncmp($part, "--input=", 8)) {
			if(isset($P->input))
			{
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->input = substr($part, 8);
				if (file_exists($P->input)) {                        // pokud soubor existuje tak ho pomoci funkce simple xml otevru
					$xml = simplexml_load_file($P->input);
					if (!$xml){                                      // v pripadeze XML soubor neodpovida hlasim chybu 4
						fwrite(STDERR, "error - chyba XML souboru\n");
						exit(4);
					} 
				}
				else {
					fwrite(STDERR, "error - soubor neexistuje\n");     // pokud se se nepodarilo vracim chybu
					exit(2);
				}
			}
		}
		else if(!strncmp($part, "--output=", 9)) {
			if(isset($P->output)){
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->output = substr($part,9);
			}
		}
		else if(!strncmp($part, "--query=",8)) {
			if(isset($P->query))
			{
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->query = substr($part,8);
			}     
		}
		else if(!strncmp($part, "--qf=",5)) {
			if(isset($P->qf))
			{
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->qf = substr($part,5);
			} 
		}
		else if(!strncmp($part, "-n",2)) {
			if($P->n)
			{
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->n = true;
			}			
		}
		else if(!strncmp($part, "--root=",7)) {
			if(isset($P->param_root))
			{
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->param_root = substr($part,7);
			}
		}
		else if($part == '--help') {
			if($P->help)
			{
				fwrite(STDERR, "error - zdvojeny parametr\n");
				exit(1);   
			}
			else{
				$P->help = true;
			}              
		}
		else { 
			fwrite(STDERR,"error - neznamy parametr ".$part."\n") ;
			exit(1);
		}
	}
	$P->validate();
}


if(!isset($P->output))                      //pokud nebyl nastaven output tak ho odkazuju na stdout
	$P->output="php://stdout";
$P->output = @fopen($P->output,'w');
if($P->output == false) {
	fwrite(STDERR, "error - nelze otevrit vystupni soubor\n");
	exit(1);
}


/*
 * ************************zpracovani dotazu**********************
 * - zacinam si zpracovavat dotaz, vyuzivam funkci preg_split,
 *   ktera mi vse rozdeli do pole (indexovane od 1)
 * - pote vyuzivam funkci preg_match, kde postupne kontroluji jestli byl zadan
 *   SELECT, FROM az po WHERE
 */

$value = preg_split("[SELECT\s+|\s+LIMIT\s+|\s+FROM\s+|\s+WHERE\s+]", $P->query);

if(preg_match("/.*(SELECT).*/", $P->query)){
// pokud najde schodu, ulozim si vse za SELECT do promene 

	if(!empty($value[1])){
		$P->q_select=$value[1];

		$P->q_select = trim($P->q_select,'FROM');
		$P->q_select = trim($P->q_select); 
		//echo "select:".$P->q_select."\n";
		if (!preg_match("/^[_a-zA-Z]([_a-zA-Z0-9\-])*$/", $P->q_select)){
			fwrite(STDERR, "error - chybny element\n");
			exit(80);  
		}
	}
	else{
		fwrite(STDERR, "error - byl zadan spatny dotaz, zadejte --help\n");
		exit(80);
	}
}
else { 
	fwrite(STDERR, "error - byl zadan spatny dotaz, zadejte --help\n");
	exit(80);
}

// zde si nastavaji promenou x na 0 z toho duvodu ze LIMIT muze a nemusi byt zadan
// v pripade ze by byl zadan LIMIT tak se mi posunuje cele pole
$x=0;

// pokud se objevil LIMIT nastavim si x=1 a poté dalsi prvky ukladam jako [hodnota+x]
// kde hodnota je cislo podle toho jakou cast resime
if(preg_match("/.*(LIMIT).*/", $P->query)) {
	$x=1;
	$P->q_limit=$value[2];
	//echo "limit:".$P->q_limit."\n";

	if(preg_match("/^\d+$/", $P->q_limit)){
		$cislo=true;
	}
	else{
		fwrite(STDERR, "error - zadana hodnota limit neni cele cislo\n");
		exit(80);
	}
}


/*
 * - pokud se objevil FROM kontroluji jestli se v tom poli nevyskytuje i ROOT
 * - pokud ano tak si root nastavim abych s nim pozdeji mohl pracovat
 * - muze nastat i situace, kdy hned za FROM nasleduje WHERE v takovem pripade
 * si nastavuju zde pozdeji budu vypisovat prazdny sobor
 */
if(preg_match("/.*(FROM).*/", $P->query)) {
	if(!empty($value[2+$x]))
	{
		if(preg_match("/.*ROOT*/",$value[2+$x])) {
			$P->root=1;
		}
		else if(!preg_match("/.*WHERE*/",$value[2+$x])) {
			$P->q_from=$value[2+$x];
			//   regular na kontrolu elementu ci atributu
			if((preg_match("/^[_a-zA-Z]([_a-zA-Z0-9\-])*\.[_a-zA-Z]+([_a-zA-Z0-9\-])*$/",$P->q_from)) || 
				(preg_match("/^\.[_a-zA-Z]+([_a-zA-Z0-9\-])*$/",$P->q_from)) || 
				(preg_match("/^[_a-zA-Z]([_a-zA-Z0-9\-])*$/",$P->q_from))){
					$pieces = explode(".", $P->q_from);               //rozkladam si FROM na pole 0 a 1 
					if(isset($pieces[0]) && ($pieces[0]!="")) {       // pokud byl nastaven tak se ulozi element
						$P->q_from_el=$pieces[0];
					}
					if(isset($pieces[1]) && ($pieces[1]!="")) {       //pokud byl nastaven tak se ulozi atribut
						$P->q_from_atr=$pieces[1];
					}
                    //echo "leva strana from element:".$P->q_from_el."\n"; // piece1
                    //echo "prava strana from atribut:".$P->q_from_atr."\n"; // piece2 
				}
			else{    
				fwrite(STDERR, "error - SPATNY ELM OR ATR\n");
				exit(80);
			} 
		}        
        else {           
			$P->no_from = true;
		}
	}
	else {
		$P->no_from = true;
	}
}
else { //else vypis chybu
	fwrite(STDERR, "error - byl zadan spatny dotaz, zadejte --help\n");
	exit(80);
}

//************************** WHERE******************************
// - kontroluju jestli se mi tam objevilo WHERE pokud ano tak 
// tak si vee od WHERE ulozim pro pozdejsi praci
if(preg_match("/.*(WHERE).*/", $P->query)){
	if(isset($value[3+$x])){
		if(empty($value[3+$x])){
			exit(80);
		}
		else
			$P->q_where_left=$value[3+$x];
	}
}

/*********************** ZPRACOVANI WHERE***************************/
// v teto casti si zpracovam podminku WHERE
// cela trida token slouzi k vytvareni a zpracovani tokenu
class Token {
	var $type;
	var $value;
	var $priority;

	var $left;
	var $right;

	function Token($txt){
		$this->priority = 0;
        
		if(preg_match("/^(<|>|=|CONTAINS|NOT)$/", $txt)){
			$this->type = "op";
			if(preg_match("/(<|>|=|CONTAINS)/", $txt))
				$this->priority = 2;
			else if(preg_match("/NOT/", $txt))
				$this->priority = 1;
		}
		else if(preg_match("/^([a-zA-Z_]([a-zA-Z0-9\-_])*)?(\.[a-zA-Z]([a-zA-Z0-9\-_]*))?$/", $txt))
			$this->type = "elem";
		else if(preg_match("/^(\".*\"|(\-)?\d+)$/", $txt))
			$this->type = "lit";
		else if($txt == '(' || $txt == ')')
			$this->type = "par";
		else{
			fwrite(STDERR, "Neznamy operator -- $txt\n");
			exit(80); 
		}
			
		$this->value = $txt;
	}
}

//pomocna funkce pro vypsani podminky - strom
function printt($node, $i){
	if($node == NULL)
		return NULL;

	printt($node->right, $i+1);

	for($x = 0; $x < $i; $x++)
		echo " ";
	echo "[ ".$node->type." | ".$node->value." ]\n";

	printt($node->left, $i+1);
}

// funkce ktera podle operatoru vytvori uzel stromu (vycisleni)
function make_node($op, &$out, &$stack){
	if($op->priority == 2){
		// < > = CONTAINS
		if(!empty($out))
			$right = array_pop($out);
		else {
			fwrite(STDERR, "error - neobsahuje pravy prvek\n");
			exit(80);
		}
		if(!empty($out))
			$left = array_pop($out);
		else {
			fwrite(STDERR, "error - neobsahuje levy prvek\n");
			exit(80);
		}
		if($left->type == "elem" && $right->type == "lit"){
			$op->left = $left;
			$op->right = $right;
		}
		else {
			fwrite(STDERR, "error - relacni operatory musi mit vlevo element a v pravo literal\n");
			exit(80); 
		}

		array_push($out, $op);
	}
	else if($op->priority == 1){
		if(!empty($out))
			$node = array_pop($out);
		else{
		   fwrite(STDERR, "error - chybi prvek pro NOT\n");
		   exit(80); 
		}
			

		if($node->type == "op"){
			$op->left = $node;
			$op->right = NULL;
		}
		else {
			fwrite(STDERR,"error - not muze byt pouze na operator ($node->type)\n");
			exit(80); 
		}
		array_push($out, $op);
	}
	else
	{
		fwrite(STDERR,"error - neznamy\n");
		exit(80); 
	}
}



// funkce vezme vrchol aniz by ho odstranil - knihovna php totiz umi pouze array_pop
// a array_push, ale neumi vzit hodnotu aniz by ji odstranil
function array_top($stack){
	if(empty($stack))
		return NULL;

	$top = array_pop($stack);
	array_push($stack, $top);

	return $top;
}

//funkce pro qr_split, ktery dela rozdeleni podle urcitych pravidel 
function is_whitespace($ch){
	return ($ch == ' ' || $ch == '\t' || $ch == '\n' || $ch == '\r');
}
function is_operator($ch){
	return ($ch == '(' || $ch == ')' || $ch == '<' || $ch =='>' || $ch == '=');
}
function is_separator($ch){
	return (is_whitespace($ch) || is_operator($ch) || $ch == '"');
}

// Funkce pro rozdeleni textu podminky na tokeny.
function qr_split($qr){
	$arr = array();

	$acc = NULL;
	for($i = 0; $i < strlen($qr); $i++){
		$ch = $qr[$i];

		if(is_separator($ch)){
			// pokud je to separator, je nutno oddelit
			if(!empty($acc)){
				array_push($arr, $acc);
				$acc = NULL;
			}

			if(is_operator($ch))
				array_push($arr, $ch);
			else if($ch == '"'){
				$str = $ch;
				for($i = $i+1; $i < strlen($qr); $i++){
					$ch = $qr[$i];

					if($ch != '"'){
						$str .= $ch;
					}
					else{
						$str .= $ch;
						array_push($arr, $str);
						break;
					}
				}
			}
		}
		else {
			$acc .= $ch;
		}
	}
	if(!empty($acc))
		array_push($arr, $acc);
	return $arr;
}

// Funkce pro zparsovani podminky, nad predanym polem tokenu postavi strom.
function parse_condition($query){
	$pole = array();   
	$spl = qr_split($query);
	
	//var_dump($spl);
	foreach($spl as $exp){
		array_push($pole, new Token($exp));
	}

	$out = array();
	$stack = array();
	foreach($pole as $val){
		if($val->type == "elem" || $val->type == "lit"){
			array_push($out, $val);
		}
		else if($val->type == "op"){
			if(!preg_match("/NOT/", $val->value)){
				$top = array_top($stack);
				
				while(!empty($stack) && ($top->priority >= $val->priority)){
					$t = array_pop($stack);
					make_node($t, $out, $stack);                
					$top = array_top($stack);
				}
			}
			array_push($stack, $val);   
		}
		else if($val->type == "par"){
			if($val->value == '(')
				array_push($stack, $val);
			else {
				$top = array_pop($stack);

				while(!empty($stack) && $top->value != '('){
					make_node($top, $out, $stack);
					$top = array_pop($stack);
				}
				if($top->value != '('){
					fwrite(STDERR,"error - problemy se zavorkami\n");
					exit(80); 
				}
			}
		}
		else {
			fwrite(STDERR,"error - neznamy\n");
			exit(80); 
			//var_dump($val);
		}
	}
	while(!empty($stack)){
		$top = array_pop($stack);
		make_node($top, $out, $stack);
	}

	$tree = array_pop($out);
	if(!empty($out)){
		fwrite(STDERR,"error - vystupni zasobnik neni prazdny\n");
		exit(80);
	}
		

	return $tree;
}

// Vyhledava v predanem prvku zadany element, podle parametru, a vrati seznam hodnot obsazenych v techto prvcich.
function find($node, $elem, $attr){
	if(isset($elem)){
		if($node->getName() == $elem){
			// sedl element
			if(isset($attr)){
				foreach($node->attributes() as $atr=>$val){
					if($atr == $attr){
						return $val;
					}
				}
			}
			else {
				//kontrola chyby 4, zda podelement neosabuje dalsi podelementy
				if(count($node->children()) == 0)
					return $node;
				else
					exit(4);
			}
		}
	}
	else {
		if(isset($attr)){
			foreach($node->attributes() as $atr=>$val){
				if($atr == $attr){
					return $val;
				}
			}
		}
	}

    foreach($node->children() as $child){
		$ret = find($child, $elem, $attr);
		if(isset($ret))
			return $ret;
	}

	return NULL;
}


// Provadi podminku. Postupuje odspodu a vykonava operace.
// Zaroven kontroluji pres regulary veskere hodnoty
function apply_condition($node, $cond){
	if(empty($cond)){
		return NULL;
	}

	// vytazeni vsech poduzlu, pokud jsou
	$left = apply_condition($node, $cond->left);
	$right = apply_condition($node, $cond->right);

	switch($cond->type){
		case "op":
			switch($cond->value){
				case "<":
				case ">":
				case "=":
				case "CONTAINS":
					// pro vsechny prvky zleva                          
					if($cond->value == '<'){
						$right = trim($right, "\"");
						if($left < $right)
							return true;
					}
					else if($cond->value == '>'){
						$right = trim($right, "\"");
						if($left > $right)
							return true;
					}
					else if($cond->value == '='){
						$right = trim($right, "\"");
						if($left == $right)
							return true;
					}
					else if(preg_match("/CONTAINS/", $cond->value)){
						if(!preg_match("/^\".*\"$/",$right)){
							fwrite(STDERR,"error - chyba dotazu\n");
							exit(80);
						}

						$right = trim($right, "\"");

						if(stripos($left, $right) !== false)
							return true;
					}
					return false;
				case "NOT":
					return !$left;
				default:
					fwrite(STDERR,"error - neznamy operator ($cond->value)\n");
					exit(80); 
			}
			break;
		case "lit":
			// jak str tak num musi vratit obsah
			return $cond->value;
		case "elem":
			// podle vyhodnoceni typu identifikatoru se ulozi do nasledujicich promennych nazvy
			$elem = NULL;
			$attr = NULL;
			//je to element.atribut
			if(preg_match("/^[a-zA-Z_]([a-zA-Z0-9_\-])*\.[a-zA-Z_]([a-zA-Z0-9_\-])*$/", $cond->value)){
				$spl = explode(".", $cond->value);
				$elem = $spl[0];
				$attr = $spl[1];
			}
			//je to element
			else if(preg_match("/^[a-zA-Z_]([a-zA-Z0-9_\-])*$/", $cond->value)){
				$elem = $cond->value;
			}
			//je to atribut    
			else if(preg_match("/^\.[a-zA-Z_]([a-zA-Z0-9_\-])*$/", $cond->value)){
				$attr = substr($cond->value, 1);
			}
			//jinak vracim chybu
			else {
				fwrite(STDERR,"error - nepodporovany element\n");
				exit(80); 
			}

			// uvnitr $node najit vsechny vyskyty elem.attr
			// vratit vsechny nalezene hodnoty
			return find($node, $elem, $attr);
		default:
			fwrite(STDERR,"error - neznamy typ\n");
			exit(80); 
	}
}


// *********************XPATH *********************
    /**
     * Zde si provedu vyhledani pro jednoduchy dotaz. 
     * Postupne si skladam cestu do promennych a pote
     * ji volam pres funkci xpath.
     *
     * Nad timto vysledkem pak aplikuji podminku WHERE.
     */
$q_select = "//".$P->q_select;


//******************************delam from ******************************
if(isset($P->root)) {            //pokud byl sepnuty root budu to uvazovat tuto cestu
    $q_from="";
    $q_select = "//".$P->q_select;
}
elseif((isset($P->q_from_el))  && (!isset($P->q_from_atr))) { //pokud byl nastaven pouze element
//echo "element\n";
    $q_from="//" .$P->q_from_el."[1]";
}
elseif((isset($P->q_from_el)) && (isset($P->q_from_atr))) { //element i atribut
//echo "element i atribut\n";
    $q_from = "//".$P->q_from_el."[@".$P->q_from_atr."][1]";
}
elseif((isset($P->q_from_atr)) && (!isset($P->q_from_el))) {    //pouze atribut
//echo "atribut\n";
    $q_from="//*[@".$P->q_from_atr."][1]";
}

//******************************pouziti XPath******************************
$object = $xml->xpath($q_from.$q_select);

$out="";

//pokud byl nastaven LIMIT tak mi vezme cislo pouzije ho v klasickem foru na vypis
if(isset($P->q_limit) && !isset($P->q_where_left)){
	for($i=0; $i<$P->q_limit; $i++){ 
		$out = $out.$object[$i]->asXML();           
	}
}
else {
	foreach ($object as $object_f) {
		$out = $out.$object_f->asXML();
	}
}

// Pokud byl nastavena podminka WHERE
if(isset($P->q_where_left)){
	$out = "<root>\n".$out."\n</root>";

	$xml_out = simplexml_load_string($out);

	$P->q_where_left = trim($P->q_where_left);
	$cond = parse_condition($P->q_where_left);

	$out = "";

	$outarr = array();
	foreach ($xml_out as $element) {
		if(apply_condition($element,$cond)){
			array_push($outarr, $element);
		}
	}

	$count = count($outarr);
	if(isset($P->q_limit))
		$count = $P->q_limit;

	for($i = 0; $i < $count; $i++){
		if(empty($outarr))
			break;

		$out .= array_pop($outarr)->asXML();
	}
}

if($P->no_from) $out = "";


// zde osetruje ruzne pripady zadanych parametru -n -root
// pokud nebyl nastaven parametr -n tak mi generuj hlavicku
if(empty($P->n)){
	fwrite($P->output,"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
}

if(isset($P->param_root))
	fwrite($P->output, "<".$P->param_root.">\n".$out."</".$P->param_root.">\n");
else
	fwrite($P->output, $out);

exit(0);
?>
