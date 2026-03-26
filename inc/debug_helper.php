<?php
/**
 * MyBB Debug Helper — One Piece Gaiden
 * Carga anticipada desde init.php para capturar TODOS los errores PHP,
 * incluidos los fatales, y escribirlos en inc/debug_log.txt con contexto
 * completo de la petición.
 *
 * Actívalo incluyendo este archivo en inc/init.php justo después de
 * instanciar $error_handler.
 */

// Disallow direct access
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

// ─── Constante de modo debug ─────────────────────────────────────────────────
if(!defined("MYBB_DEBUG_LOG"))
{
	// Cambia a false para deshabilitar sin borrar el código
	define("MYBB_DEBUG_LOG", true);
}

if(!MYBB_DEBUG_LOG)
{
	return;
}

// ─── Ruta del fichero de log ──────────────────────────────────────────────────
if(!defined("MYBB_DEBUG_LOG_FILE"))
{
	define("MYBB_DEBUG_LOG_FILE", MYBB_ROOT . "inc/debug_log.txt");
}

// ─── Máximo tamaño del log antes de rotarlo (5 MB por defecto) ───────────────
if(!defined("MYBB_DEBUG_LOG_MAX_SIZE"))
{
	define("MYBB_DEBUG_LOG_MAX_SIZE", 5 * 1024 * 1024);
}

// ─── Reporte PHP al máximo ────────────────────────────────────────────────────
error_reporting(E_ALL);
@ini_set('display_errors', 0);      // nunca mostrar en pantalla
@ini_set('log_errors',     1);
@ini_set('error_log',      MYBB_DEBUG_LOG_FILE);

/**
 * Clase de log de debug para MyBB.
 * Escribe en inc/debug_log.txt con contexto completo.
 */
class MyBBDebugLogger
{
	/** @var string */
	private $logFile;

	/** @var array Tipos de error PHP legibles */
	private static $errorNames = array(
		E_ERROR             => 'E_ERROR',
		E_WARNING           => 'E_WARNING',
		E_PARSE             => 'E_PARSE',
		E_NOTICE            => 'E_NOTICE',
		E_CORE_ERROR        => 'E_CORE_ERROR',
		E_CORE_WARNING      => 'E_CORE_WARNING',
		E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
		E_USER_ERROR        => 'E_USER_ERROR',
		E_USER_WARNING      => 'E_USER_WARNING',
		E_USER_NOTICE       => 'E_USER_NOTICE',
		E_STRICT            => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED        => 'E_DEPRECATED',
		E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
		20 /* MYBB_SQL */   => 'MYBB_SQL',
		30 /* MYBB_TEMPLATE */ => 'MYBB_TEMPLATE',
		40 /* MYBB_GENERAL */  => 'MYBB_GENERAL',
	);

	public function __construct($logFile)
	{
		$this->logFile = $logFile;
		$this->rotate();
	}

	// ── Rotación del log ──────────────────────────────────────────────────────
	private function rotate()
	{
		if(file_exists($this->logFile) && filesize($this->logFile) > MYBB_DEBUG_LOG_MAX_SIZE)
		{
			$backup = $this->logFile . '.' . date('Ymd_His') . '.bak';
			@rename($this->logFile, $backup);
		}
	}

	// ── Contexto de la petición HTTP ──────────────────────────────────────────
	public function getRequestContext()
	{
		$method  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
		$uri     = isset($_SERVER['REQUEST_URI'])    ? $_SERVER['REQUEST_URI']    : 'N/A';
		$ip      = $this->getClientIp();
		$ua      = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A';
		$referer = isset($_SERVER['HTTP_REFERER'])    ? $_SERVER['HTTP_REFERER']    : 'N/A';
		$script  = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : 'N/A';

		return array(
			'method'  => $method,
			'uri'     => $uri,
			'ip'      => $ip,
			'ua'      => $ua,
			'referer' => $referer,
			'script'  => $script,
		);
	}

	private function getClientIp()
	{
		$keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
		foreach($keys as $k)
		{
			if(!empty($_SERVER[$k]))
			{
				// Si hay múltiples IPs (proxy chain), devolver la primera
				$ip = trim(explode(',', $_SERVER[$k])[0]);
				if(filter_var($ip, FILTER_VALIDATE_IP))
				{
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}

	// ── Backtrace limpio ──────────────────────────────────────────────────────
	public function getBacktrace($strip = 0)
	{
		if(!function_exists('debug_backtrace'))
		{
			return 'N/A';
		}

		$trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$trace  = array_slice($trace, $strip);
		$lines  = array();

		foreach($trace as $i => $frame)
		{
			$file     = isset($frame['file'])     ? str_replace(MYBB_ROOT, '', $frame['file']) : '[internal]';
			$line     = isset($frame['line'])     ? $frame['line']     : '?';
			$function = isset($frame['function']) ? $frame['function'] : '{closure}';
			if(!empty($frame['class']))
			{
				$function = $frame['class'] . $frame['type'] . $function;
			}
			$lines[] = "  #{$i}  {$function}()  [{$file}:{$line}]";
		}

		return implode("\n", $lines);
	}

	// ── Escritura principal al log ────────────────────────────────────────────
	/**
	 * @param int    $type     Constante de tipo de error PHP / MyBB
	 * @param string $message  Mensaje (string o array para SQL)
	 * @param string $file     Archivo donde ocurrió
	 * @param int    $line     Línea
	 * @param int    $btStrip  Frames de backtrace a eliminar del inicio
	 */
	public function write($type, $message, $file = '', $line = 0, $btStrip = 2)
	{
		$typeName = isset(self::$errorNames[$type]) ? self::$errorNames[$type] : "ERROR({$type})";

		// Mensaje legible para errores SQL
		if(is_array($message))
		{
			$sqlMsg = "SQL Error {$message['error_no']}: {$message['error']}";
			if(!empty($message['query']))
			{
				$sqlMsg .= "\n  Query: " . trim($message['query']);
			}
			$message = $sqlMsg;
		}

		// Sanitizar
		$message = str_replace('<?', '< ?', $message);
		$file    = $file ? str_replace(MYBB_ROOT, '', $file) : 'unknown';

		$ctx    = $this->getRequestContext();
		$bt     = $this->getBacktrace($btStrip);
		$now    = date('Y-m-d H:i:s') . '.' . sprintf('%03d', (int)(microtime(true) * 1000) % 1000);
		$pid    = function_exists('getmypid') ? getmypid() : '?';
		$memory = function_exists('memory_get_usage') ? round(memory_get_usage(true) / 1024) . ' KB' : '?';

		$entry  = str_repeat('─', 70) . "\n";
		$entry .= "[{$now}]  TYPE: {$typeName}  PID: {$pid}  MEM: {$memory}\n";
		$entry .= "FILE: {$file}  LINE: {$line}\n";
		$entry .= "REQUEST: {$ctx['method']} {$ctx['uri']}\n";
		$entry .= "SCRIPT:  {$ctx['script']}\n";
		$entry .= "IP:      {$ctx['ip']}   UA: {$ctx['ua']}\n";
		$entry .= "REFERER: {$ctx['referer']}\n";
		$entry .= "MESSAGE:\n  " . str_replace("\n", "\n  ", $message) . "\n";
		$entry .= "BACKTRACE:\n{$bt}\n";

		@file_put_contents($this->logFile, $entry . "\n", FILE_APPEND | LOCK_EX);
	}

	// ── Captura de errores fatales via shutdown ───────────────────────────────
	public function shutdownHandler()
	{
		$error = error_get_last();
		if($error !== null && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR)))
		{
			$this->write($error['type'], "[FATAL/SHUTDOWN] " . $error['message'], $error['file'], $error['line'], 0);
		}
	}
}

// ─── Instancia global ─────────────────────────────────────────────────────────
global $mybb_debug_logger;
$mybb_debug_logger = new MyBBDebugLogger(MYBB_DEBUG_LOG_FILE);

// ─── Shutdown handler para fatales ───────────────────────────────────────────
register_shutdown_function(function() use ($mybb_debug_logger)
{
	$mybb_debug_logger->shutdownHandler();
});

// ─── Función helper global ─────────────────────────────────────────────────────
/**
 * Escribe una entrada de debug personalizada en el log.
 * Uso: mybb_debug_log("Mi mensaje", __FILE__, __LINE__);
 *
 * @param string $message
 * @param string $file
 * @param int    $line
 */
function mybb_debug_log($message, $file = '', $line = 0)
{
	global $mybb_debug_logger;
	if($mybb_debug_logger instanceof MyBBDebugLogger)
	{
		$mybb_debug_logger->write(E_USER_NOTICE, "[MANUAL] " . $message, $file, $line, 1);
	}
}
