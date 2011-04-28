#!/usr/bin/env php
<?php
/*
 * Empir
 *
Jeremy Perret <jeremy@devster.org>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
 */

//if colors make any damages on your terminal deactivate them by setting to false this option
define('ACTIVATE_COLORS', true);
 
//Empir version
define('VERSION', '1.0.1');
define('DATE', '2011');

//only run Empir automatically when this file is called directly from the command line
if(isset($argv[0]))
{
	if(version_compare(phpversion(), '5.3.0', '<')){
		echo "ERROR: Empir require php >= 5.3.0 (Your PHP version: ".phpversion().")\n";
		exit(1);
	}

	$empir = new Empir($argv);
    exit($empir->run());
}


/**
 * Command line interface Helper.
 *
 * @package  Empir
 */
class CLI_Interface {
	
	protected function execCommand(){
		if(isset($this->commands[$this->command])){
			$method = $this->commands[$this->command];
			$rcode =  $this->$method();
		}else
			$rcode = $this->error("Command <$this->command> doesn't exist. Try <help>");
		return ($rcode == null) ? 0 : $rcode;
	}
	
	protected function gopt($no){
		if(isset($this->options[$no]))
			return $this->options[$no];
		return null;
	}
	
	protected function glopt($opt){
		foreach($this->options as $option){
			if(strpos($option, "--$opt=") !== false)
				return trim(end(explode('=', $option)), '"');
		}
		return null;
	}
	
	protected function reqopt($no, $name){
		if($this->gopt($no) == null)
			exit($this->error("Param $name is required. Try <help>"));
		return $this->gopt($no);
	}
	
	protected function error($message = '', $errno = 1){
		if($message != '')
			echo Color::str("ERROR: $message\n", Empir::ERR_COLOR);
		return $errno;
	}
	
	protected function success($message){
		echo Color::str("$message\n", Empir::SUCCESS_COLOR);
	}
	
	protected function makeAbsolut($path=''){
		$current = getcwd().'/';
		if($path === "" || $path === false)
			$absolut_path = $current;
		elseif(substr($path, 0, 2) == './')
			$absolut_path = $current.substr($path, 2);
		elseif(strpos($path, ':') === 1 || substr($path, 0, 2) == '\\\\' || substr($path, 0, 1) == '/')
			$absolut_path = $path;
		else
			$absolut_path = $current.$path;
		
		$absolut_path = str_replace('\\', '/', $absolut_path);
		$absolut_path = rtrim($absolut_path, '/');
		
		return $absolut_path;
	}
}


/**
 * Manage phar
 *
 * @package    Empir
 */
class Empir extends CLI_Interface {
	
	const ERR_COLOR = 'red';
	const HELP_COLOR = 'green';
	const PARAM_COLOR = 'purple';
	const SUCCESS_COLOR = 'green';
	
	public $options = array();
	public $command;
	public $commands = array(
		'help' => 'help',
		'?'    => 'help',
		'-h'   => 'help',
		'make' => 'make',
		'convert' => 'convert',
		'extract' => 'extract'
	);
	public $compression_types  = array('gz', 'bz2', 'no');
	public $format_types = array('phar', 'tar', 'zip');
	
	public function __construct($argv){
		$this->options = array_slice($argv, 1);
		
		$vars = array(
			'gz' => array(
				'name' 		=> 'gz',
				'extension' => 'zlib',
				'mime' 		=> '.gz',
				'int_value' => Phar::GZ,
			),
			'bz2' => array(
				'name' 		=> 'bz2',
				'extension' => 'bzip2',
				'mime' 		=> '.bz2',
				'int_value' => Phar::BZ2,
			),
			'no' => array(
				'name' 		=> 'no',
				'extension' => 'not',
				'mime' 		=> '',
				'int_value' => Phar::NONE,
			),
			'phar' => array(
				'name' 		  => 'phar',
				'mime' 		  => '.phar',
				'int_value'   => Phar::PHAR,
				'compression' => true
			),
			'tar' => array(
				'name' 		  => 'tar',
				'mime' 		  => '.tar',
				'int_value'   => Phar::TAR,
				'compression' => true
			),
			'zip' => array(
				'name' 		  => 'zip',
				'mime' 		  => '.zip',
				'int_value'   => Phar::ZIP,
				'compression' => false
			)
		);
		foreach($vars as $name => $attrs)
			$this->$name = $this->array_to_object($attrs);
	}
	
	public function run(){
		$this->command = ($this->gopt(0) != null) ? $this->gopt(0) : 'help';
		$this->options = array_slice($this->options, 1);
		return $this->execCommand();
	}
	
	public function make(){
		$this->_is_phar_writable();
		$phar = $this->makeAbsolut($this->reqopt(0, 'phar filename'));
		$phar_name = end(explode('/', $phar));
		$stub_file = trim($this->reqopt(1, 'stub file'), '/');
		$root_app = $this->makeAbsolut($this->reqopt(2, 'root dir of your app'));
		$_compression = $this->glopt('compress') ?: 'no';
		$_format = $this->glopt('format') ?: 'phar';
		$_exclude = $this->glopt('exclude');
		$_fexclude = $this->glopt('fexclude');
		
		if(!file_exists($root_app)) return $this->error("Root dir of your app doesn't exist.");
		
		if(!empty($_compression) && !in_array($_compression, $this->compression_types)) return $this->error("Unrecognized compression: $_compression");
		if(!empty($_format) && !in_array($_format, $this->format_types)) return $this->error("Unrecognized format: $_format");
		
		if(!empty($_fexclude)){
			$_fexclude = $this->makeAbsolut($_fexclude);
			if(!file_exists($_fexclude)) return $this->error("Exclude file: $_fexclude not found.");
			$_fexclude = file_get_contents($_fexclude);
		}
		
		$shell_masks = explode('|', $_exclude);
		$shell_masks = array_merge($shell_masks, explode("\n", $_fexclude));
		
		$c = $this->get_var($_compression);
		$f = $this->get_var($_format);
		
		@unlink($phar);
		try{
			$p = new Phar($phar, Phar::CURRENT_AS_FILEINFO | Phar::KEY_AS_FILENAME, $phar_name);
			
			echo "Make $phar_name : \n===================\n";
			$p->setStub("<?php Phar::mapPhar(); include 'phar://".$phar_name."/".$stub_file."'; __HALT_COMPILER(); ?>");
			
			$files = $this->_scandir($root_app);
			
			$i=0;
			foreach($files as $file){
				$file_buff = $file;
				$file = str_replace('\\', '/', $file);
				$file = str_replace($root_app.'/', '', $file);
				
				if(!$this->_exclude($file, $shell_masks) && !$this->_exclude($file, array('*/'.$phar_name, $phar_name))){
					$p[$file] = file_get_contents($file_buff);
					echo "add $file\n";
					$i++;
				}
			}
			
			echo "\nTotal: $i files added\n";
			
			if($f->name == 'phar' && $c->name='no'){
				return $this->success("CREATE $phar");
			}
			
			if(!Phar::canCompress($c->int_value)) return $this->error("Unable to compress the phar with $c->name, extension $c->extension not found. But $phar is created.");

			if(!$f->compression) $c->int_value = Phar::NONE;
						
			$phar_copy = $phar.$f->mime.$c->mime;
			@unlink($phar_copy);
			$p = $p->convertToExecutable($f->int_value, $c->int_value);
			$this->success("CREATE $phar_copy");
			@unlink($phar);
		}
		catch(Exception $e){
			return $this->error($e->getMessage());
		}
	}
	
	public function extract(){
		$this->_is_phar_writable();
		$phar_path = $this->makeAbsolut($this->reqopt(0, 'Phar path'));
		$extract_path = $this->makeAbsolut($this->gopt(1, 'Extract path'));
		if(!file_exists($phar_path)) return $this->error("Phar file $phar_path not found.");
		if(!file_exists($extract_path)) return $this->error("Extract path $extract_path not found.");
		
		try{
			$p = new Phar($phar_path);
			
			$c = $this->get_var($p->isCompressed());
			
			if(!Phar::canCompress($c->int_value)) return $this->error("Unable to extract $c->name compressed phar, extension $c->extension not found.");
			echo "Extraction $c->extension compressed phar...\n";
			
			if(!$p->extractTo($extract_path, null, true)) return $this->error("Unable to extract $phar_path");
			$this->success("Extraction DONE.");
		}
		catch(Exception $e){
			return $this->error($e->getMessage());
		}
	}
	
	public function convert(){
		$this->_is_phar_writable();
		$phar_path = $this->makeAbsolut($this->reqopt(0, 'Phar path'));
		$_format = $this->reqopt(1, 'Format');
		$_compression = $this->gopt(2) ?: 'no';
		if(!file_exists($phar_path)) return $this->error("Phar file $phar_path not found.");
		if(!in_array($_format, $this->format_types)) return $this->error("Unrecognized format: $_format");
		if(!empty($_compression) && !in_array($_compression, $this->compression_types)) return $this->error("Unrecognized compression: $_compression");
		
		try{
			$p = new Phar($phar_path);
			
			# original compression
			$co = $this->get_var($p->isCompressed());
			if(!Phar::canCompress($co->int_value)) return $this->error("Unable to converting: original phar compression $co->name doesn't work, extension $co->extension not found.");
			
			# target compression
			$c = $this->get_var($_compression);
			if(!Phar::canCompress($c->int_value)) return $this->error("Unable to converting: target phar compression $c->name doesn't work, extension $c->extension not found.");
			
			$f = $this->get_var($_format);
			if(!$f->compression) $c->int_value = Phar::NONE;
			if($f->name == 'phar') $f->mime = '';
			
			$phar_tab = explode('/',$phar_path);
			$phar_name = explode('.', end($phar_tab));
			$phar_name = $phar_name[0].'.phar'.$f->mime.$c->mime;
			$phar_name = implode('/',array_slice($phar_tab, 0, -1)).'/'.$phar_name;
			@unlink($phar_name);
			
			echo "Convert $phar_path to $phar_name...\n";
			$p->convertToExecutable($f->int_value, $c->int_value);
			@unlink($phar_path);
			$this->success("Converting DONE.");
		}
		catch(Exception $e){
			return $this->error($e->getMessage());
		}
	}
	
	public function help(){
		$help = new Help();
		$command = $this->gopt(0);
		if(empty($command)) return $help->main();
		
		switch($command){
			case 'make': $help->make(); break;
			case 'extract': $help->extract(); break;
			case 'convert': $help->convert(); break;
			default: return $this->error("Command: $command doesn't exist."); break;
		}
	}
	
	private function _is_phar_writable(){
		if(!Phar::canWrite()) exit($this->error("Unable to write phar, phar.readonly must be set to zero in your php.ini otherwise use: $ php -dphar.readonly=0 empir <command> ..."));
	}
	
	private function get_var($var){
		if(is_string($var)){
			if(isset($this->$var))
				return $this->$var;
		}else{
			foreach(array_merge($this->compression_types, $this->format_types) as $v){
				if($this->$v->int_value == $var)
					return $this->$v;
			}
		}
	}
	
	private function _exclude($file, $shell_masks){
		if(!empty($shell_masks)){
			foreach($shell_masks as $mask){
				if(fnmatch(trim($mask), $file))
					return true;
			}
		}
		return false;
	}
	
	private function _scandir($path){
		$items = array();
		$path = rtrim($path, '/');
		if (!$current_dir = opendir($path))
			return $items;			
	
		while(false !== ($filename = readdir($current_dir))){
			if ($filename != "." && $filename != ".."){
				if (is_dir($path.'/'.$filename)){
					$items = array_merge($items, $this->_scandir($path.'/'.$filename));	
				}
				else
					$items[] = $path.'/'.$filename;
			}
		}
		closedir($current_dir);
		return $items;
	}
	
	private function array_to_object($array) {
		$object = new stdClass();
		foreach ($array as $name => $value) {
			$name = strtolower(trim($name));
			if (!empty($name))
				$object->$name = $value;
		}
		return $object;
	}
}


/**
 * Colorizer
 *
 * @package   Empir
 */
class Color {
    
    static public $foreground_colors = array(
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'brown'        => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37'
    );
    
    static public $background_colors = array(
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47'
    );

    static public function str($string, $foreground_color = null, $background_color = null) {
        if(!self::isTermSupportColor()) return $string;
        
        $colored_string = "";

        if (isset(self::$foreground_colors[$foreground_color])) {
            $colored_string .= "\033[".self::$foreground_colors[$foreground_color]."m";
        }

        if (isset(self::$background_colors[$background_color])) {
            $colored_string .= "\033[".self::$background_colors[$background_color]."m";
        }

        $colored_string .=  $string."\033[0m";

        return $colored_string;
    }
    
    static public function random($string){
        $index_foreground = array_rand(self::$foreground_colors, 1);
        $index_background= array_rand(self::$background_colors, 1);
        
        return self::str($string, $index_foreground, $index_background);
    }
    
    static public function isTermSupportColor(){
		return  DIRECTORY_SEPARATOR != '\\' 
				&& function_exists('posix_isatty')
				&& @posix_isatty(STDOUT)
				&& ACTIVATE_COLORS;
    }
}


/**
 * All differents helps.
 *
 * @package   Empir
 */
class Help{
    
    public function main(){
        echo "Empir v".VERSION." ".DATE." (c) Jeremy Perret <jeremy@devster.org>
Empir is a php tool to manage phar.
The setting phar.readonly must be 0 in your php.ini,
otherwise use $ php -dphar.readonly=0 empir <command> ...
If you use Empir from PEAR installation don't care about this php option,
it used directly in the executable file. 
 
".Color::str('Usage', Empir::HELP_COLOR).":
  $ php empir <command> <parameters> [options]

".Color::str('Commands', Empir::HELP_COLOR).":
  ".Color::str('make', Empir::PARAM_COLOR)."     Create a phar from an entire php application.
  ".Color::str('extract', Empir::PARAM_COLOR)."  Extract all files from a phar file.
  ".Color::str('convert', Empir::PARAM_COLOR)."  Converting and compressing phar.

For more help on a command use 'empir help <command>'
";
    }
    
    public function make(){
        echo "Command make allows to create a phar file from an entire php application from its root directory.

".Color::str('Usage', Empir::HELP_COLOR).":
  $ php empir make <phar_file> <stub_file> <root_app> [options]

".Color::str('Parameters', Empir::HELP_COLOR).":
  ".Color::str('phar_file', Empir::PARAM_COLOR)."  Phar file that will be created, accept absolute or relative path.
  ".Color::str('stub_file', Empir::PARAM_COLOR)."  Bootstrap file of your application, from your root app folder.
  ".Color::str('root_app', Empir::PARAM_COLOR)."   Root folder of your application, accept absolute or relative path.

".Color::str('Options', Empir::HELP_COLOR).":
  ".Color::str('--exclude=PATTERN', Empir::PARAM_COLOR)."  Exclude files match PATTERN, seperate several patterns with a pipe.
  ".Color::str('--fexclude=FILE', Empir::PARAM_COLOR)."    Exclude patterns listed in FILE. One pattern per line.
  ".Color::str('--format=FORMAT', Empir::PARAM_COLOR)."    Special phar format, FORMAT can be tar or zip. Don't specify format to keep normal phar.
  ".Color::str('--compress=TYPE', Empir::PARAM_COLOR)."    Specify the phar compression type. TYPE can be gz or bz2.
";
    }
    
    public function extract(){
        echo "This command allows to extract all files from a phar file (compressed or not) as a vulgar archive.

".Color::str('Usage', Empir::HELP_COLOR).":
  $ php empir extract <phar_file> [extract_path]
  
".Color::str('Parameters', Empir::HELP_COLOR).":
  ".Color::str('phar_file', Empir::PARAM_COLOR)."     Absolute or relative path to your phar file to extract.
  ".Color::str('extract_path', Empir::PARAM_COLOR)."  Optional, absolute or relative path to extract, if not specified, extract into the current folder. 
";
    }
    
    public function convert(){
        echo "Convert allows to convert and/or compress a phar file.
        
".Color::str('Usage', Empir::HELP_COLOR).":
  $ php empir convert <phar_file> <format> [compression]

".Color::str('Parameters', Empir::HELP_COLOR).":
  ".Color::str('phar_file', Empir::PARAM_COLOR)."    Absolute or relative path to your phar file to convert.
  ".Color::str('format', Empir::PARAM_COLOR)."       The format in which you want to convert your phar file. Use zip, tar or phar.
  ".Color::str('compression', Empir::PARAM_COLOR)."  Optional, compression type for the convertion. Use gz or bz2.
  
Example:
  '$ php empir convert ./my.phar.tar.gz phar'
  This example converts the compressed phar file into a normal phar.
";
    }
}
?>