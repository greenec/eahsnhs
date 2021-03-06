<?php

// NOTE: these should be changed to suit your project
// exclude web server configs, frameworks/libraries, etc.
$excludeFiles = array('stats.php', 'NOTES', 'users.php', 'websockets.php', 'chart.js', 'chartUtils.js',
    'XLSXWriter.php', 'XLSXWriter_BuffererWriter.php', 'composer.lock', 'composer.json', 'rawline.css');
// NOTE: ALWAYS keep '.' and '..' in this array
// exclude directories such as fonts and images
$excludeDir = array('.', '..', 'fonts', 'img', 'vendor');

// the directory you'd like to search
// use './' for the current directory, or an absolute path
$dir = './';

// set this to false if you don't want to see all TODOs in a repository
$showTODOs = true;
$TODOs = array();

$dirContents = getDirContents($dir, $excludeFiles, $excludeDir, $TODOs);

$totalSize = 0;
$totalLines = 0;

// NOTE: add or remove these as needed for your setup, but also
// make sure that you make those changes in the getFileInfo function,
// in the loop that creates the table, and in the diagrams
$size = [
    'PHP' => 0,
    'JavaScript' => 0,
    'HTML' => 0,
    'CSS' => 0
];
$lines = $size;

foreach($dirContents as $item) {
	$totalSize += $item->size;
	$totalLines += $item->lines;

	$lines[$item->type] += $item->lines;
	$size[$item->type] += $item->size;
}

$phpLP = calcLP($lines['PHP'], $totalLines);
$phpSP = calcSP($size['PHP'], $totalSize);

$jsLP = calcLP($lines['JavaScript'], $totalLines);
$jsSP = calcSP($size['JavaScript'], $totalSize);

$htmlLP = calcLP($lines['HTML'], $totalLines);
$htmlSP = calcSP($size['HTML'], $totalSize);

$cssLP = calcLP($lines['CSS'], $totalLines);
$cssSP = calcSP($size['CSS'], $totalSize);

// I modified a snippet of code that I found on Stack Overflow to
// recursively search all directories in a given directory for a file
// see http://stackoverflow.com/questions/24783862/list-all-the-files-and-folders-in-a-directory-with-php-recursive-function
function getDirContents($dir, $excludeFiles, $excludeDir, &$TODOs, &$results = array()) {
	$dirContents = scandir($dir);

	foreach($dirContents as $item) {
		$path = realpath($dir.DIRECTORY_SEPARATOR.$item);
		if(!is_dir($path)) { // item is a file
			if(!in_array($item, $excludeFiles) && !strpos($item, '.gz') && !strpos($item, '.log') && !strpos($item, '.min')) {
				$filePathArray = explode(DIRECTORY_SEPARATOR, $path);
				$name = end($filePathArray);
				$results[] = getFileInfo($path, $name);

				// look for TODOs
				$file = file($path);
				for($i = 0; $i < count($file); $i++) {
					if(strpos($file[$i], 'TODO') !== false) {
						$str = $item . ' line ' . ($i + 1) . ': ' . $file[$i];
						array_push($TODOs, $str);
					}
				}
			}
		} else if(!in_array($item, $excludeDir)) { // item is a directory... "We need to go deeper."
			getDirContents($path, $excludeFiles, $excludeDir, $TODOs, $results); // search this directory with the POWER OF RECURSION!
		}
	}
	return $results;
}

function getFileInfo($path, $item) {
	if(strpos($item, '.php') !== false) {
		$type = "PHP";
	} else if(strpos($item, '.js') !== false) {
		$type = "JavaScript";
	} else if(strpos($item, '.html') !== false || strpos($item, '.mustache') !== false) {
		$type = "HTML";
	} else if(strpos($item, '.css') !== false) {
		$type = "CSS";
	}

	// NOTE: this can be changed, but kilobytes made the most sense for my project
	// dividing again by 1024 will give you MB
	// dividing that by 1024 will give you GB, and so on and so forth
	$size = number_format((filesize($path) / 1024), 2, '.', '') . "KB";

	// this is a neat little function to count lines in a file without killing your server
	// see http://stackoverflow.com/questions/2162497/efficiently-counting-the-number-of-lines-of-a-text-file-200mb
	$lines = 0;
	$handle = fopen($path, "r");
	while(!feof($handle)){
		$line = fgets($handle, 4096);
		$lines = $lines + substr_count($line, PHP_EOL);
	}
	fclose($handle);

	return new File($path, $item, $type, $size, $lines);
}

function calcLP($lines, $totalLines) {
	return number_format(($lines / $totalLines) * 100, 2, '.', '') . '%';
}

function calcSP($size, $totalSize) {
	return number_format(($size / $totalSize) * 100, 2, '.', '') . '%';
}

Class File {
	function __construct($path, $name, $type, $size, $lines) {
		$this->path = $path;
		$this->name = $name;
		$this->type = $type;
		$this->size = $size;
		$this->lines = $lines;
	}
}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Repository Stats</title>

	<style>
		body {
			font-family: Arial, sans-serif;
		}
		/* table styles */
		table {
			border-collapse: collapse;
			width: 100%;
		}
		td, th {
			border: 1px solid #dddddd;
			text-align: left;
			padding: 8px;
		}
		tr:nth-child(even) {
			background-color: #dddddd;
		}
		/* diagrams styles */
		.pBar {
			height: 30px;
			display: inline-block;
		}
		.legendColor {
			width: 15px;
			height: 15px;
			display: inline-block;
		}
		.legendText {
			display: inline-block;
		}
		.noLeftMargin {
			margin-left: -5px
		}
		.left-edge {
			border-top-left-radius: 5px;
			border-bottom-left-radius: 5px;
		}
		.right-edge {
			border-top-right-radius: 5px;
			border-bottom-right-radius: 5px;
		}
		.center {
			text-align: center;
		}
		/* colors */
		.green {
			background-color: #79BFA1;
		}
		.blue {
			background-color: #A3CBF1;
		}
		.red {
			background-color: #FB7374;
		}
		.orange {
			background-color: #F5A352;
		}
	</style>
</head>
<body>

<table>
	<tr>
		<th>File Name</th>
		<th>File Type</th>
		<th>File Size</th>
		<th>Line Count</th>
	</tr>
	<?php
	foreach($dirContents as $item) {
		echo
		"<tr>
		<td>$item->name</td>
		<td>$item->type</td>
		<td>$item->size</td>
		<td>$item->lines</td>
		</tr>";
	} ?>
	<tr>
		<th></th>
		<th></th>
		<th><?php echo $totalSize; ?> KB</th>
		<th><?php echo $totalLines; ?> lines of code</th>
	</tr>

	<tr>
		<th></th>
		<th>PHP</th>
		<th><?php echo $size['PHP']; ?> KB</th>
		<th><?php echo $lines['PHP']; ?> lines of PHP</th>
	</tr>

	<tr>
		<th></th>
		<th>JavaScript</th>
		<th><?php echo $size['JavaScript']; ?> KB</th>
		<th><?php echo $lines['JavaScript']; ?> lines of JavaScript</th>
	</tr>

	<tr>
		<th></th>
		<th>HTML</th>
		<th><?php echo $size['HTML']; ?> KB</th>
		<th><?php echo $lines['HTML']; ?> lines of HTML</th>
	</tr>

	<tr>
		<th></th>
		<th>CSS</th>
		<th><?php echo $size['CSS']; ?> KB</th>
		<th><?php echo $lines['CSS']; ?> lines of CSS</th>
	</tr>
</table>

<h3>% by Number of Lines</h3>
<div class='center'>
	<span class='pBar blue left-edge' style="width: <?php echo $phpLP; ?>;"></span>
	<span class='pBar noLeftMargin orange' style="width: <?php echo $jsLP; ?>;"></span>
	<span class='pBar noLeftMargin red' style="width: <?php echo $htmlLP; ?>;"></span>
	<span class='pBar noLeftMargin green right-edge' style="width: <?php echo $cssLP; ?>;"></span>
</div>
<br>
<span class='legendColor blue'></span>
<p class='legendText'>PHP (<?php echo "$phpLP - " . $lines['PHP'] . " lines"; ?>)&nbsp;</p>
<span class='legendColor orange'></span>
<p class='legendText'>JavaScript (<?php echo "$jsLP - " . $lines['JavaScript'] . " lines"; ?>)&nbsp;</p>
<span class='legendColor red'></span>
<p class='legendText'>HTML (<?php echo "$htmlLP - " . $lines['HTML'] . " lines"; ?>)&nbsp;</p>
<span class='legendColor green'></span>
<p class='legendText'>CSS (<?php echo "$cssLP - " . $lines['CSS'] . " lines"; ?>)&nbsp;</p>

<h3>% by File Size</h3>
<div class='center'>
	<span class='pBar blue left-edge' style="width: <?php echo $phpSP; ?>;"></span>
	<span class='pBar noLeftMargin orange' style="width: <?php echo $jsSP; ?>;"></span>
	<span class='pBar noLeftMargin red' style="width: <?php echo $htmlSP; ?>;"></span>
	<span class='pBar noLeftMargin green right-edge' style="width: <?php echo $cssSP; ?>;"></span>
</div>
<br>
<span class='legendColor blue'></span>
<p class='legendText'>PHP (<?php echo "$phpSP - " . $size['PHP'] . " KB"; ?>)&nbsp;</p>
<span class='legendColor orange'></span>
<p class='legendText'>JavaScript (<?php echo "$jsSP - " . $size['JavaScript'] . " KB"; ?>)&nbsp;</p>
<span class='legendColor red'></span>
<p class='legendText'>HTML (<?php echo "$htmlSP - " . $size['HTML'] . " KB"; ?>)&nbsp;</p>
<span class='legendColor green'></span>
<p class='legendText'>CSS (<?php echo "$cssSP - " . $size['CSS'] . " KB"; ?>)&nbsp;</p>

<?php

if($showTODOs) {
	echo "<h3 style='margin-bottom: 0;'>TODO's</h3>";
	foreach($TODOs as $TODO) {
		echo '<br>' . htmlspecialchars($TODO) . '<br>';
	}
	echo "<br>";
}

?>

</body>
</html>
